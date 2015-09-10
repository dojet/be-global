<?php
class MJsonRespond {

    protected $result = array('status'=>null, 'msg'=>null, 'data'=>null);

    const SUCCESS = 0;
    const FAIL = 1;

    /**
     * json respond工厂方法
     *
     * @param unknown_type $status
     * @param unknown_type $msg
     * @param unknown_type $data
     * @return MJsonRespond
     */
    public static function respond($status, $msg = null, $data = null) {
        $class = get_called_class();
        $respond = new $class($status, $msg, $data);
        return $respond;
    }

    public static function respondFromJson($json) {
        $array = json_decode($json, true);
        if (!$array) {
            return false;
        }

        if (!key_exists('status', $array) ||
            !key_exists('msg', $array) ||
            !key_exists('data', $array)) {
            return false;
        }

        return static::respond($array['status'], $array['msg'], $array['data']);
    }

    public static function respondSuccess($msg = null, $data = null) {
        return self::respond(self::SUCCESS, $msg, $data);
    }

    public static function respondFail($msg = null, $data = null) {
        return self::respond(self::FAIL, $msg, $data);
    }

    public function __construct($status, $msg, $data) {
        $this->status = $status;
        $this->msg = $msg;
        $this->data = $data;
    }

    public function toJson() {
        return json_encode($this->result);
    }

    public function __set($key, $value) {
        assert(array_key_exists($key, $this->result));
        if ('status' === $key) {
            $errorMessage = Config::configForKeyPath('error');
            if (is_array($errorMessage) && key_exists($value, $errorMessage)) {
                $this->msg = $errorMessage[$value];
            }
        } elseif ('msg' === $key) {
            if (null === $value) {
                return ;
            }
        }
        $this->result[$key] = $value;
    }

    public function __get($key) {
        assert(array_key_exists($key, $this->result));
        return $this->result[$key];
    }

}
