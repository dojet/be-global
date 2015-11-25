<?php
/**
 * description
 *
 * Filename: BaseModuleDal.class.php
 *
 * @author liyan
 * @since 2015 7 16
 */
abstract class BaseModuleDal extends MysqlDal implements IBaseModuleDal, IDBQueryDelegate {

    protected $tryCreateTable = false;

    protected static function defaultDB() {
        $module = static::module();
        DAssert::assert($module instanceof IDatabaseModule, 'module must be database module');
        return $module->database();
    }

    public static function getDBQuery($db = null, $rw = 'w') {
        $dbQuery = parent::getDBQuery($db, $rw);
        $dbQuery->setDelegate(new static);
        return $dbQuery;
    }

    public function dbQueryShouldRetry(DBAdapter $adapter, $sql) {
        if ($this->tryCreateTable) {
            return false;
        }

        if (1146 == $adapter->errno()) {   # Table doesn't exist, errno: 1146
            $this->tryCreateTable = true;
            static::init();
            return true;
        }

        return false;
    }

    protected static function doCreateTable($sql, $db = null) {
        $dbQuery = static::getDBQuery($db, 'w');
        $ret = $dbQuery->doQuery($sql);
        return static::result($ret, $dbQuery);
    }

}
