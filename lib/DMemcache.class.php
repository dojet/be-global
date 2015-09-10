<?php
/**
 * Model
 *
 * Memcache
 *
 * @author liyan
 * @since Sat Aug 10 10:28:05 GMT 2013
 */
class DMemcache implements ICache {

    protected static $memcache;

    function __construct() {
        if (!self::$memcache) {
            self::$memcache = new Memcache();
            $arrayServers = Config::runtimeConfigForKeyPath('memcache.server');
            if (!$arrayServers) {
                throw new Exception("empty memcache servers", 1);
            }

            $isAvailable = false;
            foreach ($arrayServers as $server) {
                $host = $server['host'];
                $port = $server['port'];

                $retry = 3;
                while ($retry > 0) {
                    $isServerAdded = self::$memcache->addServer($host, $port);
                    if ($isServerAdded) {
                        break;
                    }
                    $retry--;
                }

                if (!$isServerAdded) {
                    Trace::warn('memcache addserver fail. host:'.$host.' port:'.$port);
                    LibSMS::alertCache('memcache addserver fail. host:'.$host.' port:'.$port);
                }

                $isAvailable |= $isServerAdded;
            }

            if (false === $isAvailable) {
                Trace::warn('memcache is not available!', __FILE__, __LINE__);
                LibSMS::alertCache('memcache not available');
                throw new Exception("memcache unavailable", 1);
            }
        }
    }

    public static function isAvailable() {
        if (class_exists('Memcache', false)) {
            return true;
        }
        Trace::warn('memcache is not available!', __FILE__, __LINE__);
        return false;
    }

    public function get($key) {
        $ret = self::$memcache->get($key);
        return $ret;
    }

    public function set($key, $value, $flag = 0, $expire = 0) {
        $ret = @self::$memcache->set($key, $value, $flag, $expire);
        Trace::debug('set memcache, ret='.intval($ret).' key='.$key.' expire='.$expire);
        Trace::verbose('set memcache, key='.$key.' value='.serialize($value));
        return $ret;
    }

    public function remove($key) {
        $ret = @self::$memcache->delete($key);
        return $ret;
    }

}