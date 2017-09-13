<?php
/**
 * Redis
 *
 * @author liyan
 * @since 2017 9 12
 */
class DRedisIns {

    protected static $single;
    protected $config;
    protected $redis;
    private $socket;

    /**
     * init redis with config
     * @param  array $config
     *   array(
     *       'address' => '10.100.86.33',
     *       'port' => 6379,
     *       'password' => '',
     *       'timeout' => 1, //sec
     *   )
     */
    public static function redis($config) {
        $redis = new DRedisIns($config);
        return $redis->connect();
    }

    function __construct($config) {
        $this->config = $config;
    }

    function __destruct() {
        if (is_resource($this->socket)) {
            socket_close($this->socket);
        }
    }

    private function conf($key) {
        DAssert::assert(is_array($this->config), 'config must be array');
        DAssert::assert(array_key_exists($key, $this->config), 'config key '.$key.' not exists');
        return $this->config[$key];
    }

    public function connect() {
        $address = $this->conf('address');
        $port = $this->conf('port');
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $result = socket_connect($this->socket, $address, $port);
        if (false === $result) {
            throw new Exception("socket connect failed", 1);
        }
        return $this;
    }

    protected function build_cmd($cmd) {
        $cmdstr = '';
        $cmdstr.= sprintf("*%d\r\n", count($cmd));
        foreach ($cmd as $param) {
            $cmdstr.= sprintf("$%d\r\n%s\r\n", strlen($param), $param);
        }
        return $cmdstr;
    }

    protected function write($cmdstr) {
        return socket_write($this->socket, $cmdstr, strlen($cmdstr));
    }

    protected function read() {
        return socket_read($this->socket, 2048);
    }

    protected function buildAndWrite($cmd) {
        $cmdstr = $this->build_cmd($cmd);
        return $this->write($cmdstr);
    }

    protected function process($cmd) {
        $this->buildAndWrite($cmd);
        $recv = $this->read();
        $reply = DRedisParser::parse($recv);
        return $reply;
    }

    public function get($key) {
        $cmd = ["GET", $key];
        return $this->process($cmd);
    }

    public function set($key, $value) {
        $cmd = ["SET", $key, $value, 1];
        return $this->process($cmd);
    }

    public function cluster_nodes() {
        $cmd = ["CLUSTER", "NODES"];
        return $this->process($cmd);
    }

    public function scan($cursor) {
        $cmd = ["SCAN", $cursor];
        return $this->process($cmd);
    }

}

