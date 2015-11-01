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
        DAssert::assert(in_array($rw, array('r', 'w')), 'illegal rw');

        $config = Config::configForKeyPath($rw, $this->dbConfig);
        $connKey = md5(serialize(array($config, $dbAdapterClass)));

        if (isset($connPool[$connKey])) {
            $conn = $connPool[$connKey];
        } else {
            DAssert::assert(class_exists($dbAdapterClass), 'illegal DBAdapter class name');
            $dbAdapter = new $dbAdapterClass;
            DAssert::assert($dbAdapter instanceof DBAdapter, 'dbAdapterClass must be DBAdapter');

            $dbConnection = new DBConnection($dbAdapter);
            $conn = $dbConnection->connect($config);
            $connPool[$connKey] = $conn;
        }

        return $conn;
    }

}
