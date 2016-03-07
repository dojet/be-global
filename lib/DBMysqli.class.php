<?php
/**
 *
 * @author liyan
 * @since 2015 7 17
 */
class DBMysqli extends DBAdapter {

    public function connect($config) {
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

}
