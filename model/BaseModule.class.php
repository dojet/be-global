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
        return SingletonFactory::getInstance(get_called_class());
    }

    final public static function init() {
        $depends = $this->depends();
        foreach ($depends as $module) {
            $moduleInit = __DIR__.'/../'.$module.'/init.php';
            require $moduleInit;
        }
    }

    abstract protected function depends();

}
