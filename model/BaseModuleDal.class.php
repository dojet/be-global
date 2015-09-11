<?php
/**
 * description
 *
 * Filename: BaseModuleDal.class.php
 *
 * @author liyan
 * @since 2015 7 16
 */
abstract class BaseModuleDal extends BaseDal implements IBaseModuleDal {

    private static $lastQueryMethod;
    private static $lastArgs;

    private static $tryCreateTable = false;

    protected static function defaultDB() {
        $module = static::module();
        DAssert::assert($module instanceof IDatabaseModule, 'module must be database module');
        return $module->database();
    }

    protected static function result($ret, DBQuery $dbQuery) {
        try {
            return parent::result($ret, $dbQuery);
        } catch (DBException $e) {
            if (self::$tryCreateTable) {
                self::$tryCreateTable = false;
                throw $e;
            }

            if (1146 == $e->getCode()) {   # Table doesn't exist, errno: 1146
                self::$tryCreateTable = true;
                static::createTable();
                return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
            }
            throw $e;
        }
    }

    protected static function doDelete($sql, $db = null, $rw = 'w') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function doUpdate($table, $updates, $where, $limit = 0x7fffffff, $db = null, $rw = 'w') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function doInsertUpdate($table, $arrIns, $arrUpd, $db = null, $rw = 'w') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function doInsert($table, $fields_values, $db = null, $rw = 'w') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function doCreateTable($sql, $db = null) {
        $dbQuery = static::getDBQuery($db, 'w');
        $ret = $dbQuery->doCreateTable($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function rs2array($sql, $db = null, $rw = 'r') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function rs2keyarray($sql, $key, $db = null, $rw = 'r') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function rs2grouparray($sql, $groupkey, $rowkey = null, $db = null, $rw = 'r') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function rs2rowline($sql, $db = null, $rw = 'r') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function rs2rowcount($sql, $db = null, $rw = 'r') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function rs2firstvalue($sql, $db = null, $rw = 'r') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

    protected static function rs2oneColumnArray($sql, $db = null, $rw = 'r') {
        self::$lastQueryMethod = __FUNCTION__;
        self::$lastArgs = func_get_args();
        return call_user_func_array(array('parent', self::$lastQueryMethod), self::$lastArgs);
    }

}
