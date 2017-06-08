<?php
/**
 * Model
 *
 * Filename: SOAResponse.class.php
 *
 * @author liyan
 * @since 2016 4 2
 */
class SOAResponse {

    private $errno;
    private $message;
    private $data;

    function __construct($errno, $message, $data) {
        $this->errno = $errno;
        $this->message = $message;
        $this->data = $data;
    }

    public static function responseFromJson($json) {
        $info = json_decode($json, true);
        if (!array_key_exists('errno', $info) ||
            !array_key_exists('message', $info) ||
            !array_key_exists('data', $info) ||
            throw new Exception("illegal soa response json", 1);
        }
        return new SOAResponse($info['errno'], $info['message'], $info['data']);
    }

    public function errno() {
        return $this->errno;
    }

    public function message() {
        return $this->message;
    }

    public function data($key = null) {
        if (is_null($key)) {
            return $this->data;
        }

        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    public function toJson() {
        return json_encode(array(
            'errno' => $this->errno(),
            'message' => $this->message(),
            'data' => $this->data(),
            )
        );
    }

}
