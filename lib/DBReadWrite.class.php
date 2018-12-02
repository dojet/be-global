<?php
/**
 *
 * @author liyan
 * @since 2015 7 17
 */
class DBReadWrite {

    protected static $connPool = array();

    protected $dbConfig;

    function __construct($dbConfig) {
        $this->dbConfig = $dbConfig;
    }

    public function getConnection($dbAdapterClass, $rw = 'w') {
        $rw = $rw === 'w' ? 'w' : 'r';

        $config = Config::c($rw, $this->dbConfig);
        $connKey = md5(serialize(array($config, $dbAdapterClass)));

        if (isset(self::$connPool[$connKey])) {
            $conn = self::$connPool[$connKey];
        } else {
            DAssert::assert(class_exists($dbAdapterClass), 'illegal DBAdapter class name');
            $dbAdapter = new $dbAdapterClass;
            DAssert::assert($dbAdapter instanceof DBAdapter, 'dbAdapterClass must be DBAdapter');

            $dbConnection = new DBConnection($dbAdapter);
            $conn = $dbConnection->connect($config);
            self::$connPool[$connKey] = $conn;
        }

        return $conn;
    }

}
