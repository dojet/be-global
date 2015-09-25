<?php
/**
 * description
 *
 * Filename: BaseModule.class.php
 *
 * @author liyan
 * @since 2015 7 16
 */
abstract class BaseModule {

    protected static $arrInstance = array();

    private static function getInstance() {
        $class = get_called_class();
        if (!array_key_exists($class, self::$arrInstance)) {
            self::$arrInstance[$class] = new $class;
        }
        return self::$arrInstance[$class];
    }

    public static function module() {
        $module = self::getInstance();
        return $module;
    }

    public static function init() {

    }

}
