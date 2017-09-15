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
        $result = @socket_connect($this->socket, $address, $port);
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
        $buf = $resp = '';
        $length = 2048;
        do {
            $buf = socket_read($this->socket, $length);
            $resp.= $buf;
        } while (strlen($buf) == $length);
        return $resp;
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

    function __call($method, $args) {
        $callback = [$this, '_'.$method];
        if (!is_callable($callback)) {
            return;
        }

        try {
            return call_user_func_array($callback, $args);
        } catch (DRedisException $e) {
            if ($e->getCode() === DRedisException::REPLY_STATUS) {
                return true;
            }
            throw $e;
        }
    }

    public function get($key) {
        $cmd = ["GET", $key];
        return $this->process($cmd);
    }

    public function set($key, $value) {
        $cmd = ["SET", $key, $value];
        return $this->process($cmd);
    }

    public function scard($key) {
        $cmd = ["SCARD", $key];
        return $this->process($cmd);
    }

    protected function cluster($subcmd) {
        $cmd = ["CLUSTER", $subcmd];
        return $this->process($cmd);
    }

    public function cluster_nodes() {
        return $this->cluster("NODES");
    }

    public function cluster_info() {
        return $this->cluster("INFO");
    }

    public function cluster_slots() {
        return $this->cluster("SLOTS");
    }

    public function cluster_meet($ip, $port) {
        $cmd = ["CLUSTER", "MEET", $ip, $port];
        try {
            $this->process($cmd);
        } catch (DRedisException $e) {
            return $e->getMessage();
        }
        return false;
    }

    public function _cluster_forget($node_id) {
        $cmd = ["CLUSTER", "FORGET", $node_id];
        return $this->process($cmd);
    }

    public function _cluster_addslots($slots) {
        $cmd = ["CLUSTER", "ADDSLOTS"];
        $cmd = array_merge($cmd, $slots);
        return $this->process($cmd);
    }

    public function scan($cursor) {
        $cmd = ["SCAN", $cursor];
        return $this->process($cmd);
    }

    public function info() {
        $cmd = ["INFO"];
        return $this->process($cmd);
    }

}

