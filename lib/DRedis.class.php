<?php
/**
 * Redis
 *
 * @author liyan
 * @since 2016 3 7
 */
class DRedis {

    protected static $redis;
    protected static $config;

    /**
     * init redis with config
     * @param  array $config
     *   array(
     *       'host' => '10.100.86.33',
     *       'port' => 6379,
     *       'password' => '',
     *       'timeout' => 1, //sec
     *   )
     */
    public static function init($config) {
        self::$config = $config;
    }

    private static function conf($key) {
        DAssert::assert(is_array(self::$config), 'config must be array');
        DAssert::assert(array_key_exists($key, self::$config), 'config key '.$key.' not exists');
        return self::$config[$key];
    }

    private static function key($key) {
        DAssert::assert(is_array(self::$config), 'config must be array');
        $prefix = '';
        if (array_key_exists('key_prefix', self::$config)) {
            $prefix = self::$config['key_prefix'];
        }
        return $prefix.$key;
    }

    protected static function getRedis() {
        if (!self::$redis) {
            $host = self::conf('host');
            $port = self::conf('port');
            $timeout = self::conf('timeout');
            $password = self::conf('password');

            self::$redis = self::connect($host, $port, $password, $timeout);
        }
        return self::$redis;
    }

    private static function connect($host, $port, $password, $timeout) {
        $redis = new Redis();
        $retry = 3;
        while ($retry-- > 0) {
            try {
                $redis->connect($host, $port, $timeout);
                $redis->auth($password);
                break;
            } catch (Exception $e) {
                if ($retry >= 0) {
                    Trace::debug('redis connect fail, retry: '.$retry.' errcode='.$e->getCode().' error='.$e->getMessage());
                    continue;
                }
                Trace::warn('redis connect fail. host:'.$host.' port:'.$port.' errcode='.$e->getCode().' error='.$e->getMessage());
                throw $e;
            }
        }
        return $redis;
    }

    public static function isAvailable() {
        if (class_exists('Redis', false)) {
            return true;
        }
        Trace::warn('redis is not available!', __FILE__, __LINE__);
        return false;
    }

    //  key
    public static function del($key) {
        try {
            $ret = self::getRedis()->del(self::key($key));
        } catch (Exception $e) {
            Trace::warn('delete redis key failed. error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function expire($key, $seconds) {
        try {
            $ret = self::getRedis()->expire(self::key($key), $seconds);
        } catch (Exception $e) {
            Trace::warn('expire redis key failed. error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    //  string
    public static function get($key) {
        try {
            $ret = self::getRedis()->get(self::key($key));
        } catch (Exception $e) {
            Trace::warn('get redis failed. error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function set($key, $value) {
        try {
            $ret = self::getRedis()->set(self::key($key), $value);
        } catch (Exception $e) {
            Trace::warn('set redis failed. error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function setex($key, $expire, $value) {
        try {
            $ret = self::getRedis()->setex(self::key($key), $expire, $value);
        } catch (Exception $e) {
            Trace::warn('set redis failed. error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function decr($key) {
        try {
            $ret = self::getRedis()->decr(self::key($key));
        } catch (Exception $e) {
            Trace::warn('decr redis key failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function decrBy($key, $by) {
        try {
            $ret = self::getRedis()->decrBy(self::key($key), $by);
        } catch (Exception $e) {
            Trace::warn('decrby redis key failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function incr($key) {
        try {
            $ret = self::getRedis()->incr(self::key($key));
        } catch (Exception $e) {
            Trace::warn('incr redis key failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function incrBy($key, $by) {
        try {
            $ret = self::getRedis()->incrBy(self::key($key), $by);
        } catch (Exception $e) {
            Trace::warn('incrby redis key failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    //  hash
    public static function hset($key, $field, $value) {
        try {
            $ret = self::getRedis()->hset(self::key($key), $field, $value);
        } catch (Exception $e) {
            Trace::warn(sprintf('redis %s failed. key:%s error:%s', __METHOD__, $key, $e->getMessage()));
            throw $e;
        }
        return $ret;
    }

    public static function hget($key, $field) {
        try {
            $ret = self::getRedis()->hget(self::key($key), $field);
        } catch (Exception $e) {
            Trace::warn(sprintf('redis %s failed. key:%s error:%s', __METHOD__, $key, $e->getMessage()));
            throw $e;
        }
        return $ret;
    }

    //  set
    public static function sAdd($key, $member) {
        try {
            $ret = self::getRedis()->sAdd(self::key($key), $member);
        } catch (Exception $e) {
            Trace::warn('redis sAdd failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function sRem($key, $member) {
        try {
            $ret = self::getRedis()->sRem(self::key($key), $member);
        } catch (Exception $e) {
            Trace::warn('redis sRem failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function sCard($key) {
        try {
            $ret = self::getRedis()->sCard(self::key($key), $member);
        } catch (Exception $e) {
            Trace::warn('redis sCard failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function sRandMember($key, $count = 1) {
        try {
            $ret = self::getRedis()->sRandMember(self::key($key), $count);
        } catch (Exception $e) {
            Trace::warn('redis sRandMember failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function sMembers($key) {
        try {
            $ret = self::getRedis()->sMembers(self::key($key));
        } catch (Exception $e) {
            Trace::warn('redis sMembers failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function sIsMember($key, $member) {
        try {
            $ret = self::getRedis()->sIsMember(self::key($key), $member);
        } catch (Exception $e) {
            Trace::warn('redis sIsMembers failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    //  list
    public static function ltrim($key, $from, $to) {
        try {
            $ret = self::getRedis()->ltrim(self::key($key), $from, $to);
        } catch (Exception $e) {
            Trace::warn('redis sMembers failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }


    public static function lindex($key, $index){
        try {
            $ret = self::getRedis()->lindex(self::key($key), $index);
        } catch (Exception $e) {
            Trace::warn('redis lindex failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function lrem($key, $value, $count){
        try {
            $ret = self::getRedis()->lrem(self::key($key), $value, $count);
        } catch (Exception $e) {
            Trace::warn('redis lrem failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function llen($key){
        try {
            $ret = self::getRedis()->llen(self::key($key));
        } catch (Exception $e) {
            Trace::warn('redis llen failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function lrange($key, $start, $stop){
        try {
            $ret = self::getRedis()->lrange(self::key($key), $start, $stop);
        } catch (Exception $e) {
            Trace::warn('redis lrange failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function lpop($key){
        try {
            $ret = self::getRedis()->lpop(self::key($key));
        } catch (Exception $e) {
            Trace::warn('redis lpop failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function rpop($key){
        try {
            $ret = self::getRedis()->rpop(self::key($key));
        } catch (Exception $e) {
            Trace::warn('redis rpop failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function lpush($key, $value){
        try {
            $ret = self::getRedis()->lpush(self::key($key), $value);
        } catch (Exception $e) {
            Trace::warn('redis lpush failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

    public static function rpush($key, $value){
        try {
            $ret = self::getRedis()->rpush(self::key($key), $value);
        } catch (Exception $e) {
            Trace::warn('redis rpush failed. key:'.$key.' error:'.$e->getMessage());
            throw $e;
        }
        return $ret;
    }

}
