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

    protected static $instance;

    private static function getInstance() {
        if (!self::$instance) {
            $class = get_called_class();
            self::$instance = new $class;
        }
        return self::$instance;
    }

    public static function module() {
        $module = self::getInstance();
        return $module;
    }

    public static function init() {

    }

}
