<?php
/**
 * 通知中心
 *
 * @author liyan
 * @since 2013 11 4
 */
class NotificationCenter {

    protected static $hooks = array();

    private static function notifyKey($notify) {
        return md5(serialize($notify));
    }

    public static function addObserver($notify, $callback) {
        DAssert::assert(is_callable($callback), 'callback must be callable');
        $key = self::notifyKey($notify);
        if (!key_exists($key, self::$hooks)) {
            self::$hooks[$key] = array();
        }
        array_push(self::$hooks[$key], $callback);
    }

    public static function removeObserver($notify, $callback) {
        $key = self::notifyKey($notify);
        if (!key_exists($key, self::$hooks)) {
            return ;
        }
        self::$hooks[$key] = array_diff(self::$hooks[$key], array($callback));
    }

    public static function postNotify($notify) {
        $key = self::notifyKey($notify);
        if (!isset(self::$hooks[$key])) {
            return ;
        }

        $args = func_get_args();
        array_shift($args);

        foreach (self::$hooks[$key] as $callback) {
            call_user_func_array($callback, $args);
        }
    }

}