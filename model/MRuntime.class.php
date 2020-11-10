<?php
/**
 * Model
 *
 * Runtime
 *
 * @author liyan
 * @since Sat Mar 23 02:43:18 GMT 2013
 */
class MRuntime {

    protected static $stack = array();

    /**
     * 获取runtime
     *
     * @param string $runtimeKeyPath
     * @return string
     */
    public static function currentRuntime($runtimeKeyPath = 'runtime') {
        return Config::configForKeyPath($runtimeKeyPath);
    }

    public static function setRuntime($newRuntime) {
        Trace::debug('set runtime:'.$newRuntime, __FILE__, __LINE__);
        $currentRuntime = &Config::rc('runtime');
        $currentRuntime = $newRuntime;
    }

    public static function switchRuntime($newRuntime) {
        $currentRuntime = self::currentRuntime();
        array_push(self::$stack, $currentRuntime);
        self::setRuntime($newRuntime);
    }

    public function restoreRuntime() {
        $lastRuntime = array_pop(self::$stack);
        if (is_null($lastRuntime)) {
            return ;
        }
        self::setRuntime($lastRuntime);
    }
}
