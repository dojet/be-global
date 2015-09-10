<?php
/**
 * DBProxy
 *
 * deprecated since 2015 7 20
 *
 * @author liyan
 * @since 2014 4 24
 */
class DBProxy_Deprecated {

    protected static $stat = array('select' => 0, 'insert' => 0, 'update' => 0, 'delete' => 0, 'read' => 0, 'write' => 0);

    protected $dbConfig;

    private $conn_r;
    private $conn_w;

    private $lastUsedConn;

    private static $dbAdapter;

    function __construct($dbConfig) {
        $this->dbConfig = $dbConfig;
        $this->conn_r = $this->conn_w = null;
    }

    protected function getConnection($rw = 'w') {
        DAssert::assert(in_array($rw, array('r', 'w')), 'illegal rw');

        $var_conn = 'conn_'.$rw;
        $conn = &$this->$var_conn;

        if (!is_object($conn)) {
            $config = Config::configForKeyPath($rw, $this->dbConfig);
            $conn = $this->connect($config);
        }

        $this->lastUsedConn = $conn;

        return $conn;
    }

    public static function setDBAdapter(DBAdapter $dbAdapter) {
        self::$dbAdapter = $dbAdapter;
    }

    public static function getDBAdapter() {
        if (is_null(self::$dbAdapter)) {
            self::$dbAdapter = ConfigHelper::getInstance()->getDBAdapter();
        }
        return self::$dbAdapter;
    }

    private function connect($config) {
        srand(time());
        $dbIndex = rand(0, count($config['hosts']) - 1);
        $host = $config['hosts'][$dbIndex]['h'];
        $port = $config['hosts'][$dbIndex]['p'];
        $username = $config['username'];
        $password = $config['password'];
        $dbname = $config['dbname'];

        $dbAdapter = self::getDBAdapter();
        $dbAdapter->host = $host;
        $dbAdapter->port = $port;
        $dbAdapter->username = $username;
        $dbAdapter->password = $password;
        $dbAdapter->dbname = $dbname;
        $dbAdapter->connect();

        $dbAdapter->query('SET NAMES "UTF8"');

        return $dbAdapter;
    }

    public function beginTransaction() {
        $this->getConnection('w')->query('BEGIN');
    }

    public function endTransaction() {
        $this->getConnection('w')->query('END');
    }

    public function commit() {
        $this->getConnection('w')->query('COMMIT');
    }

    public function rollback() {
        $this->getConnection('w')->query('ROLLBACK');
    }

    protected function doQuery($sql, $rw) {
        if ('r' === $rw) {
            return $this->doRead($sql);
        } else {
            return $this->doWrite($sql);
        }
    }

    public function doInsert($sql, $rw = 'w')
    {
        self::$stat['insert']++;
        return $this->doQuery($sql, $rw);
    }

    public function doUpdate($sql, $rw = 'w')
    {
        self::$stat['update']++;
        return $this->doQuery($sql, $rw);
    }

    public function doDelete($sql, $rw = 'w')
    {
        self::$stat['delete']++;
        return $this->doQuery($sql, $rw);
    }

    public function doSelect($sql, $rw = 'r')
    {
        self::$stat['select']++;
        return $this->doQuery($sql, $rw);
    }

    public function doRead($sql) {
        self::$stat['read']++;
        return $this->getConnection('r')->query($sql);
    }

    public function doWrite($sql) {
        self::$stat['write']++;
        return $this->getConnection('w')->query($sql);
    }

    public function affectedRows() {
        return $this->getConnection('w')->affectedRows();
    }

    public function insertID() {
        return $this->getConnection('w')->insertID();
    }

    public function error() {
        return $this->lastUsedConn->error();
    }

    public function errno() {
        return $this->lastUsedConn->errno();
    }

    public function rs2array($sql, $rw = 'r')
    {
        $rs = $this->doSelect($sql, $rw);
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

    public function rs2oneColumnArray($sql, $rw = 'r') {
        $rs = $this->doSelect($sql, $rw);
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

    public function rs2keyarray($sql, $key, $rw = 'r') {
        $rs = $this->doSelect($sql, $rw);
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

    public function rs2grouparray($sql, $groupkey, $rowkey = null, $rw = 'r') {
        $rs = $this->doSelect($sql, $rw);
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

    public function rs2rowline($sql, $rw = 'r')
    {
        $rs = $this->doSelect($sql, $rw);
        if (false === $rs) {
            return false;
        }

        $ret = $rs->fetch_assoc();
        if (false === $ret) {
            return null;
        }
        return  $ret;
    }

    public function rs2rowcount($sql, $rw = 'r')
    {
        $ret = $this->rs2firstvalue($sql, $rw);
        return $ret;
    }

    public function rs2firstvalue($sql, $rw = 'r')
    {
        $row = $this->rs2rowline($sql, $rw);
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

    public function rs2foundrows($rw = 'r') {
        return $this->rs2firstvalue("SELECT FOUND_ROWS()", $rw);
    }

    public function realEscapeString($string) {
        $conn = defaultNullValue($this->lastUsedConn, $this->getConnection('w'));
        return $conn->realEscapeString($string);
    }

    public static function getInsertNum()
    {
        return self::$stat['insert'];
    }

    public static function getDeleteNum()
    {
        return self::$stat['delete'];
    }

    public static function getUpdateNum()
    {
        return self::$stat['update'];
    }

    public static function getSelectNum()
    {
        return self::$stat['select'];
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

class DBMysqli extends DBAdapter {

    private $mysqli;

    // function __construct($host, $username, $password, $dbname, $port = 3306) {
    //     $this->host = $host;
    //     $this->username = $username;
    //     $this->password = $password;
    //     $this->dbname = $dbname;
    //     $this->port = $port;
    //     $this->connect();
    // }

    public function connect() {
        $mysqli = new mysqli($this->host, $this->username, $this->password, $this->dbname, $this->port);
        if ($mysqli->connect_errno) {
            trigger_error('connect db failed. error:'.$mysqli->errno);
        }
        $this->db = $mysqli;
        return $this->db;
    }

    public function close() {
        $this->db->close();
    }

    public function query($sql) {
        $retry = 3;
        while ($retry-- > 0) {
            $ret = $this->db->query($sql);
            $errno = $this->db->errno;
            $error = $this->db->error;
            if ($errno === 0) {
                return $ret;
            }

            if (in_array($errno, array(1062))) {
                return $ret;
            }

            $this->connect();
        }

        Trace::fatal('query failed. errno:'.$errno.' error:'.$error, __FILE__, __LINE__);
        Trace::fatal('sql: '.$sql, __FILE__, __LINE__);
        trigger_error('query failed. errno:'.$errno.' error:'.$error);
    }

    public function realEscapeString($escapestr) {
        return $this->db->real_escape_string($escapestr);
    }

    public function error() {
        return $this->db->error;
    }

    public function errno() {
        return $this->db->errno;
    }

    public function affectedRows() {
        return $this->db->affected_rows;
    }

    public function insertID() {
        return $this->db->insert_id;
    }

}
