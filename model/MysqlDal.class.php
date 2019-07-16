<?php
/**
 * dal base
 *
 * Filename: MysqlDal.class.php
 *
 * @author liyan
 * @since 2014 4 24
 */
abstract class MysqlDal {

    protected static $cache = array();
    protected static $useCache = false;
    protected static $forceReload = false;

    protected static function defaultDB() {
        return null;
    }

    public static function getDBQuery($db = null, $rw = 'w') {
        if (is_null($db)) {
            $db = static::defaultDB();
        }
        DAssert::assert(!is_null($db), 'db should not be null! class:'.get_called_class());
        DAssert::assert(in_array($rw, array('r', 'w')), 'illegal rw');

        $dbConfig = Config::rc('database.$.'.$db);
        $dbReadWrite = new DBReadWrite($dbConfig);
        $dbConnection = $dbReadWrite->getConnection('DBMysql', $rw);
        $dbQuery = new DBQuery($dbConnection);

        return $dbQuery;
    }

    protected static function result($ret, DBQuery $dbQuery) {
        if (false === $ret) {
            throw new Exception($dbQuery->error(), $dbQuery->errno());
        }
        return $ret;
    }

    protected static function setUseCache($useCache) {
        self::$useCache = $useCache;
    }

    public static function setForceReload($forceReload) {
        self::$forceReload = $forceReload;
    }

    private static function doQuery($sql, $db, $rw) {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->doQuery($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function doSelect($sql, $db = null, $rw = 'r') {
        $ret = self::doQuery($sql, $db, $rw);
        return $ret;
    }

    protected static function doInsert($table, $fields_values, $db = null, $rw = 'w') {
        $sql = self::insertStatement($table, $fields_values);
        return self::doQuery($sql, $db, $rw);
    }

    protected static function doUpdate($table, $updates, $where, $limit = 0x7fffffff, $db = null, $rw = 'w') {
        $sql = self::updateStatement($table, $updates, $where, $limit);
        return self::doQuery($sql, $db, $rw);
    }

    protected static function doInsertUpdate($table, $arrIns, $arrUpd, $db = null, $rw = 'w') {
        $sql = self::insertOrUpdateStatement($table, $arrIns, $arrUpd);
        return self::doQuery($sql, $db, $rw);
    }

    protected static function doDelete($sql, $db = null, $rw = 'w') {
        return self::doQuery($sql, $db, $rw);
    }

    protected static function rs2array($sql, $db = null, $rw = 'r') {
        if (self::$useCache) {
            $key = sha1(serialize($sql, $db, $rw));
            if (!self::$forceReload && isset(self::$cache[$key])) {
                return self::$cache[$key];
            }
        }

        $rs = self::doSelect($sql, $db, $rw);
        $ret = array();
        while ($row = $rs->fetch_assoc()) {
            $ret[] = $row;
        }

        if (self::$useCache) {
            self::$cache[$key] = $ret;
        }
        return $ret;
    }

    protected static function rs2keyarray($sql, $key, $db = null, $rw = 'r') {
        $list = self::rs2array($sql, $db, $rw);
        $ret = array();
        foreach ($list as $row) {
            $ret[$row[$key]] = $row;
        }
        return $ret;
    }

    protected static function rs2grouparray($sql, $groupkey, $rowkey = null, $db = null, $rw = 'r') {
        $list = self::rs2array($sql, $db, $rw);
        $ret = array();
        foreach ($list as $row) {
            $resultKey = $row[$groupkey];
            if ($rowkey) {
                $ret[$resultKey][$row[$rowkey]] = $row;
            } else {
                $ret[$resultKey][] = $row;
            }
        }
        return $ret;
    }

    protected static function rs2rowline($sql, $db = null, $rw = 'r') {
        $list = self::rs2array($sql, $db, $rw);
        return array_shift($list);
    }

    protected static function rs2value($sql, $db = null, $rw = 'r') {
        $row = self::rs2rowline($sql, $db, $rw);
        $ret = null;
        if (is_array($row)) {
            $ret = array_shift($row);
        }
        return $ret;
    }

    protected static function rs2rowcount($sql, $db = null, $rw = 'r') {
        return self::rs2value($sql, $db, $rw);
    }

    protected static function rs2foundrows($db = null, $rw = 'r') {
        return self::rs2value("SELECT FOUND_ROWS()", $db, $rw);
    }

    protected static function rs2oneColumnArray($sql, $db = null, $rw = 'r') {
        $list = self::rs2array($sql, $db, $rw);
        $ret = array();
        foreach ($list as $row) {
            $ret[] = array_shift($row);
        }
        return $ret;
    }

    protected static function escape(&$str, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $str = $dbQuery->escape($str);
        return $str;
    }

    protected static function wherein($fieldname, $numids) {
        DAssert::assertNotEmptyNumericArray($numids);
        $wherein = join(',', $numids);
        return "$fieldname IN ($wherein)";
    }

    public static function insertID($db = null, $rw = 'w') {
        return static::rs2value("SELECT LAST_INSERT_ID()", $db, $rw);
    }

    public static function affectedRows($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->affectedRows();
        return static::result($ret, $dbQuery);
    }

    public static function beginTransaction($db = null, $rw = 'w') {
        return static::doQuery('BEGIN', $db, $rw);
    }

    public static function endTransaction($db = null, $rw = 'w') {
        return static::doQuery('END', $db, $rw);
    }

    public static function commit($db = null, $rw = 'w') {
        return static::doQuery('COMMIT', $db, $rw);
    }

    public static function rollback($db = null, $rw = 'w') {
        return static::doQuery('ROLLBACK', $db, $rw);
    }

    ##
    ## statements
    ##
    protected static function insertStatement($table, $fields_values) {
        $arrayFields = array();
        $arrayValues = array();
        foreach ($fields_values as $field => $value) {
            $arrayFields[] = '`'.$field.'`';
            $arrayValues[] = "'".static::escape($value)."'";
        }
        $strFields = join(', ', $arrayFields);
        $strValues = join(', ', $arrayValues);
        $sql = "INSERT INTO $table($strFields) VALUES($strValues)";
        return $sql;
    }

    protected static function insertOrUpdateStatement($table, $fields_values, $updates) {
        $arrayFields = array();
        $arrayValues = array();
        foreach ($fields_values as $field => $value) {
            $arrayFields[] = '`'.$field.'`';
            $arrayValues[] = "'".static::escape($value)."'";
        }
        $strFields = join(', ', $arrayFields);
        $strValues = join(', ', $arrayValues);

        $arrayUpdates = array();
        foreach ($updates as $upKey => $upValue) {
            $arrayUpdates[] = static::updateOption($upKey, $upValue);
        }
        $strUpdates = join(', ', $arrayUpdates);

        $sql = "INSERT INTO $table($strFields)
                VALUES($strValues)
                ON DUPLICATE KEY UPDATE $strUpdates";

        return $sql;
    }

    protected static function updateStatement($table, $updates, $where, $limit = 0x7fffffff) {
        $arrayUpdates = array();
        foreach ($updates as $upKey => $upValue) {
            $arrayUpdates[] = static::updateOption($upKey, $upValue);
        }
        $strUpdates = join(', ', $arrayUpdates);

        $sql = "UPDATE $table
                SET $strUpdates
                WHERE $where
                LIMIT ".intval($limit);

        return $sql;
    }

    private static function updateOption($upKey, $upValue) {
        $statement = null;

        $realUpKey = static::escape($upKey);
        if (is_array($upValue)) {
            if (array_key_exists('inc', $upValue)) {
                $inc = $upValue['inc'];
                DAssert::assertNumeric($inc);
                $statement = "`$realUpKey`=`$realUpKey`+($inc)";
            }
        } else {
            $statement = "`".$realUpKey."`='".static::escape($upValue)."'";
        }

        DAssert::assert($statement !== null,
            'illegal update option, key='.$upKey.' value='.$upValue);
        return $statement;
    }

}
