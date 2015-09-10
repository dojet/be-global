<?php
/**
 * description
 *
 * Filename: DBAdapter.class.php
 *
 * @author liyan
 * @since 2015 2 4
 */
abstract class DBAdapter {

    protected $db;

    public $host;
    public $username;
    public $password;
    public $dbname;
    public $port;

    abstract public function connect();
    abstract public function close();
    abstract public function query($sql);
    abstract public function realEscapeString($escapestr);
    abstract public function error();
    abstract public function errno();
    abstract public function affectedRows();
    abstract public function insertID();

}