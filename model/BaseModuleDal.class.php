<?php
/**
 * description
 *
 * Filename: BaseModuleDal.class.php
 *
 * @author liyan
 * @since 2015 7 16
 */
abstract class BaseModuleDal extends BaseDal implements IBaseModuleDal, IDBQueryDelegate {

    protected static $tryCreateTable = false;

    protected static function defaultDB() {
        $module = static::module();
        DAssert::assert($module instanceof IDatabaseModule, 'module must be database module');
        return $module->database();
    }

    public static function getDBQuery($db = null, $rw = 'w') {
        $dbQuery = parent::getDBQuery($db, $rw);
        $className = get_called_class();
        $dbQuery->setDelegate(new static);
        return $dbQuery;
    }

    public function dbQueryFail(DBAdapter $adapter, $sql) {

    }

    public function dbQueryShouldRetry(DBAdapter $adapter, $sql) {
        if (static::$tryCreateTable) {
            static::$tryCreateTable = false;
            return false;
        }

        if (1146 == $adapter->errno()) {   # Table doesn't exist, errno: 1146
            static::$tryCreateTable = true;
            static::createTable();
            return true;
        }

        return false;
    }

    protected static function doCreateTable($sql, $db = null) {
        $dbQuery = static::getDBQuery($db, 'w');
        $ret = $dbQuery->doCreateTable($sql);
        return static::result($ret, $dbQuery);
    }

}
