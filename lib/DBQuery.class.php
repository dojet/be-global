<?php
/**
 * @author liyan
 * @since 2015 7 17
 */
class DBQuery {

    protected $dbConnection;

    function __construct(DBConnection $dbConnection) {
        $this->dbConnection = $dbConnection;
    }

    public function beginTransaction() {
        $this->doQuery('BEGIN');
    }

    public function endTransaction() {
        $this->doQuery('END');
    }

    public function commit() {
        $this->doQuery('COMMIT');
    }

    public function rollback() {
        $this->doQuery('ROLLBACK');
    }

    protected function dbAdapter() {
        return $this->dbConnection->dbAdapter();
    }

    public function doQuery($sql) {
        return $this->dbAdapter()->query($sql);
    }

    public function doInsert($sql) {
        return $this->doQuery($sql);
    }

    public function doUpdate($sql) {
        return $this->doQuery($sql);
    }

    public function doDelete($sql) {
        return $this->doQuery($sql);
    }

    public function doSelect($sql) {
        return $this->doQuery($sql);
    }

    public function doCreateTable($sql) {
        return $this->doQuery($sql);
    }

    public function error() {
        return $this->dbAdapter()->error();
    }

    public function errno() {
        return $this->dbAdapter()->errno();
    }

    public function affectedRows() {
        return $this->dbAdapter()->affectedRows();
    }

    public function rs2array($sql) {
        $rs = $this->doSelect($sql);
        if (false === $rs) {
            return false;
        }
        $ret = array();
        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                $ret[] = $row;
            }
        }
        return  $ret;
    }

    public function rs2oneColumnArray($sql) {
        $rs = $this->doSelect($sql);
        if (false === $rs) {
            return false;
        }
        $ret = array();
        if ($rs) {
            while ($row = $rs->fetch_row()) {
                $ret[] = $row[0];
            }
        }
        return  $ret;
    }

    public function rs2keyarray($sql, $key) {
        $rs = $this->doSelect($sql);
        if (false === $rs) {
            return false;
        }
        $ret = array();
        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                $resultKey = $row[$key];
                $ret[$resultKey] = $row;
            }
        }
        return  $ret;
    }

    public function rs2grouparray($sql, $groupkey, $rowkey = null) {
        $rs = $this->doSelect($sql);
        if (false === $rs) {
            return false;
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
        return $ret;
    }

    public function rs2rowline($sql) {
        $rs = $this->doSelect($sql);
        if (false === $rs) {
            return false;
        }

        $ret = $rs->fetch_assoc();
        if (false === $ret) {
            return null;
        }
        return $ret;
    }

    public function rs2rowcount($sql) {
        return $this->rs2firstvalue($sql);
    }

    public function rs2firstvalue($sql) {
        $row = $this->rs2rowline($sql);
        if (false === $row) {
            return false;
        } elseif (null === $row) {
            return null;
        }

        if (!is_array($row)) {
            return false;
        }

        $ret = array_values($row);
        return $ret[0];
    }

    public function rs2foundrows() {
        return $this->rs2firstvalue("SELECT FOUND_ROWS()");
    }

    public function realEscapeString($string) {
        return $this->dbAdapter()->realEscapeString($string);
    }

    /**
     * make up insert statement
     *
     * @param string $table
     * @param string $fields_values
     * @param DBProxy $dbProxy
     */
    public function insertStatement($table, $fields_values) {
        $arrayFields = array();
        $arrayValues = array();
        foreach ($fields_values as $field => $value) {
            $arrayFields[] = '`'.$field.'`';
            $arrayValues[] = "'".$this->realEscapeString($value)."'";
        }
        $strFields = join(', ', $arrayFields);
        $strValues = join(', ', $arrayValues);
        $sql = "INSERT INTO $table($strFields) VALUES($strValues)";
        return $sql;
    }

    /**
     * make up insert values statement
     *
     * @param string $table
     * @param array $fields
     * @param array $valueSet
     */
    public function insertMultiStatement($table, $fields, $valueSet) {
        $arrayFields = array();
        $arrayValues = array();
        foreach ($fields as $field) {
            $arrayFields[] = '`'.$field.'`';
        }

        $arrValueSet = array();
        foreach ($valueSet as $values) {
            $arrayValues = array();
            foreach ($values as $val) {
                $arrayValues[] = "'".$val."'";
                // $arrayValues[] = "'".$this->realEscapeString($val)."'";
            }
            $arrValueSet[] = '('.join(',',$arrayValues).')';
        }
        $strFields = join(',', $arrayFields);
        $strValues = join(',', $arrValueSet);
        $sql = "INSERT INTO $table($strFields) VALUES $strValues";
        return $sql;
    }

    /**
     * make up insert or update statement
     *
     * @param string $table
     * @param string $fields_values
     * @param string $updates
     * @param DBProxy $dbProxy
     */
    public function insertOrUpdateStatement($table, $fields_values, $updates) {
        $arrayFields = array();
        $arrayValues = array();
        foreach ($fields_values as $field => $value) {
            $arrayFields[] = '`'.$field.'`';
            $arrayValues[] = "'".$this->realEscapeString($value)."'";
        }
        $strFields = join(', ', $arrayFields);
        $strValues = join(', ', $arrayValues);

        $arrayUpdates = array();
        foreach ($updates as $upKey => $upValue) {
            $arrayUpdates[] = $this->updateOption($upKey, $upValue);
        }
        $strUpdates = join(', ', $arrayUpdates);

        $sql = "INSERT INTO $table($strFields)
                VALUES($strValues)
                ON DUPLICATE KEY UPDATE $strUpdates";

        return $sql;
    }

    public function updateStatement($table, $updates, $where, $limit = 0x7fffffff) {
        $arrayUpdates = array();
        foreach ($updates as $upKey => $upValue) {
            $arrayUpdates[] = $this->updateOption($upKey, $upValue);
        }
        $strUpdates = join(', ', $arrayUpdates);

        $sql = "UPDATE $table
                SET $strUpdates
                WHERE $where
                LIMIT ".intval($limit);

        return $sql;
    }

    private function updateOption($upKey, $upValue) {
        $statement = null;

        $realUpKey = $this->realEscapeString($upKey);
        if (is_array($upValue)) {
            if (array_key_exists('inc', $upValue)) {
                $inc = $upValue['inc'];
                $statement = "`$realUpKey`=`$realUpKey`+$inc";
            }
        } else {
            $statement = "`".$realUpKey."`='".$this->realEscapeString($upValue)."'";
        }

        DAssert::assert($statement !== null,
            'illegal update option, key='.$upKey.' value='.$upValue);
        return $statement;
    }

}
