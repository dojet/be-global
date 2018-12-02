<?php
/**
 *
 * @author liyan
 * @since 2015 7 17
 */
class DBMysql implements DBAdapter {

    protected $config;
    protected $db;

    function __construct($config) {
        $this->config = $config;
    }

    public function connect() {
        $config = $this->config;
        $hostport = $config['hosts'][array_rand($config['hosts'])];
        $host = $hostport['h'];
        $port = $hostport['p'];
        $username = $config['username'];
        $password = $config['password'];
        $dbname = $config['dbname'];
        $charset = $config['charset'];
        $mysqli = new mysqli($host, $username, $password, $dbname, $port);
        if ($mysqli->connect_errno) {
            throw new Exception($mysqli->connect_error, $mysqli->connect_errno);
        }
        $mysqli->set_charset($charset);
        $this->db = $mysqli;
        return $this;
    }

    public function close() {
        $this->db->close();
    }

    public function db() {
        return $this->db;
    }

    public function query($sql) {
        return $this->db->query($sql);
    }

    public function escape($escapestr) {
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

    public function rs2array($sql) {
        $rs = $this->query($sql);
        $ret = array();
        while ($row = $rs->fetch_assoc()) {
            $ret[] = $row;
        }
        return $ret;
    }

    public function rs2rowline($sql) {
        $list = $this->rs2array($sql);
        return is_array($list) ? array_shift($list) : null;
    }

    public function rs2value($sql) {
        $rowline = $this->rs2rowline($sql);
        return is_array($rowline) ? array_shift($rowline) : null;
    }

    public function insert($table, $fields_values) {
        $sql = $this->insertStatement($table, $fields_values);
        return $this->query($sql);
    }

    protected function insertStatement($table, $fields_values) {
        $arrayFields = array();
        $arrayValues = array();
        foreach ($fields_values as $field => $value) {
            $arrayFields[] = '`'.$field.'`';
            $arrayValues[] = "'".$this->escape($value)."'";
        }
        $strFields = join(', ', $arrayFields);
        $strValues = join(', ', $arrayValues);
        return "INSERT INTO $table($strFields) VALUES($strValues)";
    }

}
