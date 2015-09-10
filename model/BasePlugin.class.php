<?php
/**
 * 插件基类
 *
 * @author liyan
 * @since 2013 11 18
 */
abstract class BasePlugin {

    /**
     * 事件绑定
     *  array(
     *      'mall_stat' => 'start',
     *  );
     * @return array
     */
    abstract protected function bindActions();

    /**
     * 插件是否应该被激活
     *
     * @return bool
     */
    protected static function shouldActive() {
        return true;
    }

    /**
     * 调用此方法激活插件
     *
     */
    public static function active() {
        if (!static::shouldActive()) {
            return ;
        }

        $instance = SingletonFactory::getInstance(get_called_class());
        $arrActions = $instance->bindActions();
        foreach ($arrActions as $notify => $action) {
            NotificationCenter::addObserver($notify, array($instance, $action));
        }
    }

}