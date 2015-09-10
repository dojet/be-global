<?php
/**
 * Model
 *
 * Ramcache
 *
 * @author liyan
 * @since Sat Aug 10 10:38:36 GMT 2013
 */
class DRamcache implements ICache {

    protected static $ram;

    function __construct() {
        if (!self::$ram) {
            self::$ram = array();
        }
    }

    public function get($key) {
        $ret = isset(self::$ram[$key]) ? self::$ram[$key] : null;
        return $ret;
    }

    public function set($key, $value, $flag = 0, $expire = 0) {
        self::$ram[$key] = $value;
    }

    public function remove($key) {
        if (isset(self::$ram[$key])) {
            unset(self::$ram[$key]);
        }
    }

}