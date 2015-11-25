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

    protected static function doSelect($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->doQuery($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function doInsert($table, $fields_values, $db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $sql = self::insertStatement($table, $fields_values);
        $ret = $dbQuery->doQuery($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function doUpdate($table, $updates, $where, $limit = 0x7fffffff, $db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $sql = self::updateStatement($table, $updates, $where, $limit);
        $ret = $dbQuery->doQuery($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function doInsertUpdate($table, $arrIns, $arrUpd, $db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $sql = self::insertOrUpdateStatement($table, $arrIns, $arrUpd);
        $ret = $dbQuery->doQuery($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function doDelete($sql, $db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = $dbQuery->doQuery($sql);
        return static::result($ret, $dbQuery);
    }

    protected static function rs2array($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $rs = self::doSelect($sql);
        if (false === $rs) {
            return false;
        }
        $ret = array();
        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                $ret[] = $row;
            }
        }
        return static::result($ret, $dbQuery);
    }

    protected static function rs2keyarray($sql, $key, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        while (!($ret = false)) {
            $rs = self::doSelect($sql, $db, $rw);
            if (false === $rs) {
                break;
            }
            $ret = array();
            if ($rs) {
                while ($row = $rs->fetch_assoc()) {
                    $resultKey = $row[$key];
                    $ret[$resultKey] = $row;
                }
            }
        }
        return static::result($ret, $dbQuery);
    }

    protected static function rs2grouparray($sql, $groupkey, $rowkey = null, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        while (!($ret = false)) {
            $rs = self::doSelect($sql, $db, $rw);
            if (false === $rs) {
                break;
            }
            $ret = array();
            while ($row = $rs->fetch_assoc()) {
                $resultKey = $row[$groupkey];
                if ($rowkey) {
                    $ret[$resultKey][$row[$rowkey]] = $row;
                } else {
                    $ret[$resultKey][] = $row;
                }
            }
        }
        return static::result($ret, $dbQuery);
    }

    protected static function rs2rowline($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        while (!($ret = false)) {
            $rs = self::doSelect($sql);
            if (false === $rs) {
                break;
            }

            $ret = $rs->fetch_assoc();
            if (false === $ret) {
                $ret = null;
            }
            break;
        }
        return static::result($ret, $dbQuery);
    }

    protected static function rs2rowcount($sql, $db = null, $rw = 'r') {
        return self::rs2firstvalue($sql, $db, $rw);
    }

    protected static function rs2foundrows($db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $ret = self::rs2firstvalue("SELECT FOUND_ROWS()");
        return static::result($ret, $dbQuery);
    }

    protected static function rs2firstvalue($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        $row = self::rs2rowline($sql);
        while (!($ret = false)) {
            if (false === $row) {
                break;
            } elseif (null === $row) {
                $ret = null;
                break;
            }

            if (!is_array($row)) {
                break;
            }

            $ret = array_values($row);
            $ret = $ret[0];
            break;
        }
        return static::result($ret, $dbQuery);
    }

    protected static function rs2oneColumnArray($sql, $db = null, $rw = 'r') {
        $dbQuery = static::getDBQuery($db, $rw);
        while (!($ret = false)) {
            $rs = self::doSelect($sql, $db, $rw);
            if (false === $rs) {
                break;
            }
            $ret = array();
            if ($rs) {
                while ($row = $rs->fetch_row()) {
                    $ret[] = $row[0];
                }
            }
        }
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
        return $dbQuery->doQuery('BEGIN');
    }

    protected static function endTransaction($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        return $dbQuery->doQuery('END');
    }

    protected static function commit($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        return $dbQuery->doQuery('COMMIT');
    }

    protected static function rollback($db = null, $rw = 'w') {
        $dbQuery = static::getDBQuery($db, $rw);
        return $dbQuery->doQuery('ROLLBACK');
    }

    ##
    ## statements
    ##
    protected static function insertStatement($table, $fields_values) {
        $arrayFields = array();
        $arrayValues = array();
        foreach ($fields_values as $field => $value) {
            $arrayFields[] = '`'.$field.'`';
            $arrayValues[] = "'".self::realEscapeString($value)."'";
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
            $arrayValues[] = "'".self::realEscapeString($value)."'";
        }
        $strFields = join(', ', $arrayFields);
        $strValues = join(', ', $arrayValues);

        $arrayUpdates = array();
        foreach ($updates as $upKey => $upValue) {
            $arrayUpdates[] = self::updateOption($upKey, $upValue);
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
            $arrayUpdates[] = self::updateOption($upKey, $upValue);
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

        $realUpKey = self::realEscapeString($upKey);
        if (is_array($upValue)) {
            if (array_key_exists('inc', $upValue)) {
                $inc = $upValue['inc'];
                $statement = "`$realUpKey`=`$realUpKey`+$inc";
            }
        } else {
            $statement = "`".$realUpKey."`='".self::realEscapeString($upValue)."'";
        }

        DAssert::assert($statement !== null,
            'illegal update option, key='.$upKey.' value='.$upValue);
        return $statement;
    }

}
