<?php
/**
 *
 * @author liyan
 * @since 2015 10 10
 */
interface IDBQueryDelegate {

    public function dbQueryShouldRetry(DBAdapter $adapter, $sql);

}