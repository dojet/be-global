<?php
/**
 * dal base
 *
 * Filename: BaseDal.class.php
 *
 * @author liyan
 * @since 2014 4 24
 */
abstract class BaseDal {

    protected static function defaultDB() {
        return null;
    }

    public static function getDBQuery($db = null, $rw = 'w') {
        if (is_null($db)) {
            $db = static::defaultDB();
        }
        DAssert::assert(!is_null($db), 'db should not be null! class:'.get_called_class());
        DAssert::assert(in_array($rw, array('r', 'w')), 'illegal rw');

        $dbConfig = Config::runtimeConfigForKeyPath('database.$.'.$db);
        $dbReadWrite = new DBReadWrite($dbConfig);
        $dbConnection = $dbReadWrite->getConnection('DBMysqli', $rw);
        $dbQuery = new DBQuery($dbConnection);

        return $dbQuery;
    }

    protected static function result($ret, DBQuery $dbQuery) {
        if (false === $ret) {
            throw new Exception($dbQuery->error(), $dbQuery->errno());
        }
        return $ret;
    }

    protected static function doDelete($sql, $db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->doDelete($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function doUpdate($table, $updates, $where, $limit = 0x7fffffff, $db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $sql = $dbQuery->updateStatement($table, $updates, $where, $limit);
        $ret = $dbQuery->doUpdate($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function doInsertUpdate($table, $arrIns, $arrUpd, $db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $sql = $dbQuery->insertOrUpdateStatement($table, $arrIns, $arrUpd);
        $ret = $dbQuery->doInsert($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function doInsert($table, $fields_values, $db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $sql = $dbQuery->insertStatement($table, $fields_values);
        $ret = $dbQuery->doInsert($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function rs2array($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->rs2array($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function rs2keyarray($sql, $key, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->rs2keyarray($sql, $key);
        return static::result($ret, $dbQuery);
    }

    protected static function rs2grouparray($sql, $groupkey, $rowkey = null, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->rs2grouparray($sql, $groupkey, $rowkey);
        return static::result($ret, $dbQuery);
    }

    protected static function rs2rowline($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->rs2rowline($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function rs2rowcount($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->rs2rowcount($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function rs2foundrows($db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->rs2foundrows();
        return static::result($ret, $dbQuery);
    }

    protected static function rs2firstvalue($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->rs2firstvalue($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function rs2oneColumnArray($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->rs2oneColumnArray($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function realEscapeString(&$str, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $str = $dbQuery->realEscapeString($str);
        return $str;
    }

    protected static function insertID($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->insertID();
        return static::result($ret, $dbQuery);
    }

    protected static function affectedRows($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->affectedRows();
        return static::result($ret, $dbQuery);
    }

    protected static function beginTransaction($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        return $dbQuery->beginTransaction();
    }

    protected static function endTransaction($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        return $dbQuery->endTransaction();
    }

    protected static function commit($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        return $dbQuery->commit();
    }

    protected static function rollback($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        return $dbQuery->rollback();
    }

}
