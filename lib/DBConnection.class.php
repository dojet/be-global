<?php
/**
 *
 * @author liyan
 * @since 2015 7 16
 */
class DBConnection {

    protected $dbAdapter;

    function __construct(DBAdapter $dbAdapter) {
        $this->dbAdapter = $dbAdapter;
    }

    public function dbAdapter() {
        return $this->dbAdapter;
    }

    public function connect($config) {
        $dbAdapter = $this->dbAdapter;
        try {
            $dbAdapter->connect($config);
        } catch (Exception $e) {
            throw new Exception("connect db failed", 1);
        }

        return $this;
    }

    public function close() {
        $this->dbAdapter->close();
    }

}
