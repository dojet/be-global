<?php
/**
 * description
 *
 * Filename: MResponse.class.php
 *
 * @author liyan
 * @since 2015 1 15
 */
abstract class MResponse {

    protected $result = array();

    const SUCCESS = 0;
    const FAIL = 1;

    function __construct() {
        $keys = $this->keys();
        DAssert::assert(is_array($keys), 'keys must be array');

        //  init result
        foreach ($keys as $keyname) {
            $this->result[$keyname] = null;
        }
    }

    public function toJson() {
        return json_encode($this->result);
    }

    public function __set($key, $value) {
        assert(array_key_exists($key, $this->result));
        $this->result[$key] = $value;
    }

    public function __get($key) {
        assert(array_key_exists($key, $this->result));
        return $this->result[$key];
    }

    abstract protected function keys();

}
