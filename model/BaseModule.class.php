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

    public static function module() {
        $module = SingletonFactory::getInstance(get_called_class());
        return $module;
    }

    public static function init() {

    }

}
