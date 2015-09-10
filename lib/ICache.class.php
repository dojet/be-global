<?php
/**
 * Model
 *
 * Cache
 *
 * @author liyan
 * @since Sat Aug 10 10:26:31 GMT 2013
 */
interface ICache {
    public function set($key, $value, $flag = 0, $expire = 0);
    public function get($key);
    public function remove($key);
}