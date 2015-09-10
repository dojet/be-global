<?php
/**
 * Model
 *
 * DCache
 *
 * @author liyan
 * @since Wed Mar 20 06:47:31 GMT 2013
 */
class DCache {

    private static $stat;

    /**
     * @var LERamcache
     */
    private static $ramcache;

    /**
     * @var ICache
     */
    protected static $cache;

    protected static function getCache() {
        if (!self::$cache) {
            self::$ramcache = new DRamcache();

            if (!self::$cache && DRediscache::isAvailable()) {
                try {
                    self::$cache = new DRediscache();
                } catch (Exception $e) {
                    self::$cache = null;
                }
            }

            if (!self::$cache && DMemcache::isAvailable()) {
                try {
                    self::$cache = new DMemcache();
                } catch (Exception $e) {
                    self::$cache = null;
                }
            }

            if (!self::$cache) {
                //  dont use ramcache
                // self::$cache = new DRamcache();
            }

            self::$stat['get'] = self::$stat['set'] = self::$stat['hits'] = 0;
            self::$stat['get_timespan'] = 0;
        }
        return self::$cache;
    }

    public static function getStat() {
        return self::$stat;
    }

    public static function get($key, &$flag = 0) {
        DAssert::assert(is_string($key), 'key must be string', __FILE__, __LINE__);
        $cache = self::getCache();
        $ret = null;
        if ($cache) {
            if (self::$ramcache) {
                $ret = self::$ramcache->get($key);
            }

            if (is_null($ret)) {
                $ret = $cache->get($key, $flag);
            }

        }
        return $ret;
    }

    public static function set($key, $value, $flag = 0, $expire = 0) {
        $cache = self::getCache();
        $ret = null;
        if ($cache) {
            $ret = $cache->set($key, $value, $flag, $expire);
            if (self::$ramcache) {
                self::$ramcache->set($key, $value, $flag, $expire);
            }
        }
        return $ret;
    }

    public static function remove($key) {
        $cache = self::getCache();
        $ret = null;
        if ($cache) {
            $ret = $cache->remove($key);
            if (self::$ramcache) {
                self::$ramcache->remove($key);
            }
        }
        return $ret;
    }

}