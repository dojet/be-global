<?php
/**
 * dal base
 *
 * Filename: MysqlProxy.class.php
 *
 * @author liyan
 * @since 2016 8 22
 */
class MysqlProxy extends MysqlDal {

    protected static $db;

    protected static function defaultDB() {
        return self::$db;
    }

    public static function proxy($db) {
        self::$db = $db;
    }

    public static function rs2rowline($sql, $db = null, $rw = 'r') {
        return parent::rs2rowline($sql, $db, $rw);
    }

    public static function doInsert($table, $fields_values, $db = null, $rw = 'w') {
        return parent::doInsert($table, $fields_values, $db, $rw);
    }

}
