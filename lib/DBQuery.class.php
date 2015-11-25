<?php
/**
 *
 * @author liyan
 * @since 2015 7 17
 */
class DBQuery {

    protected $dbConnection;
    protected $delegate;

    function __construct(DBConnection $dbConnection) {
        $this->dbConnection = $dbConnection;
    }

    public function setDelegate(IDBQueryDelegate $delegate) {
        $this->delegate = $delegate;
    }

    protected function dbAdapter() {
        return $this->dbConnection->dbAdapter();
    }

    public function doQuery($sql) {
        $dbAdapter = $this->dbAdapter();
        $shouldRetry = false;
        do {
            $ret = $dbAdapter->query($sql);
            if (false !== $ret) {
                break;
            }

            $delegate = $this->delegate;
            if (!$delegate) {
                break;
            }
            DAssert::assert($delegate instanceof IDBQueryDelegate, 'illegal IDBQueryDelegate');

            $shouldRetry = $delegate->dbQueryShouldRetry($dbAdapter, $sql);
        } while ($shouldRetry);

        return $ret;
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

    public function realEscapeString($string) {
        return $this->dbAdapter()->realEscapeString($string);
    }

}
