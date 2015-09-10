<?php
/**
 * Model
 *
 * Rediscache
 *
 * @author liyan
 * @since 2014 3 25
 */
class DRediscache implements ICache {

    protected static $redis;

    function __construct() {
        if (!self::$redis) {
            self::$redis = new Redis();
            $config = Config::runtimeConfigForKeyPath('redis.server');
            if (!$config) {
                return;
            }

            $host = $config['hosts'][0];
            $port = $config['port'];
            $timeout = $config['timeout'];
            $password = $config['password'];

            $retry = 3;
            while ($retry > 0) {
                try {
                    self::$redis->connect($host, $port, $timeout);
                    self::$redis->auth($password);
                    break;
                } catch (Exception $e) {
                    $retry--;
                    if ($retry > 0) {
                        Trace::debug('redis connect fail, retry: '.$retry.' and error code='.$e->getCode().' and error message='.$e->getMessage(),__FILE__, __LINE__);
                        continue;
                    } else {
                        Trace::warn('redis connect fail. host:'.$host.' port:'.$port.' and error code='.$e->getCode().' and error message='.$e->getMessage(), __FILE__, __LINE__);
                        LibSMS::alertCache('redis connect fail. host:'.$host.' port:'.$port);
                        throw $e;
                    }
                }
            }
        }
    }

    public static function isAvailable() {
        if (class_exists('Redis', false)) {
            return true;
        }
        Trace::warn('redis is not available!', __FILE__, __LINE__);
        return false;
    }

    public function get($key) {
        $key = Config::configForKeyPath('redis.keyprefix').$key;
        for($i=0;$i<3;$i++){
            try {
                $ret = self::$redis->get($key);
            } catch (Exception $e) {
                Trace::warn('get redis failed. key='.$key.' and error code='.$e->getCode().' and error message='.$e->getMessage(), __FILE__, __LINE__);
                if($i<2)
                {
                   continue;
                }
                $ret = null;
            }
            return $ret;
        }
        return $ret;
    }

    public function set($key, $value, $flag = 0, $expire = 0) {
        $key = Config::configForKeyPath('redis.keyprefix').$key;
        for($i=0;$i<3;$i++){
            try {
                if ($expire > 0) {
                    $ret = self::$redis->setex($key, $expire, $value);
                } else {
                    $ret = self::$redis->set($key, $value);
                }
            }catch (Exception $e) {
                Trace::warn('set redis failed. key='.$key.'and value='.$value.' and error code='.$e->getCode().' and error message='.$e->getMessage(), __FILE__, __LINE__);
                if($i<2)
                {
                   continue;
                }
                $ret = false;
            }
            Trace::debug('set redis, ret='.intval($ret).' key='.$key.' expire='.$expire);
            Trace::verbose('set redis, key='.$key.' value='.serialize($value));
            return $ret;
        }
        return $ret;
    }

    public function remove($key) {
        $key = Config::configForKeyPath('redis.keyprefix').$key;
        try {
            $ret = self::$redis->delete($key);
        } catch (Exception $e) {
            Trace::warn('remove redis key failed. error:'.$e->getMessage());
        }
        return $ret;
    }

}
