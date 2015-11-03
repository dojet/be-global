<?php
/**
 * description
 *
 * Filename: MJsonResponse.class.php
 *
 * @author liyan
 * @since 2015 11 3
 */
class MJsonResponse extends MJson {

    protected $errno;
    protected $message;
    protected $data;

    const SUCCESS = 0;
    const FAIL = 1;

    public static function response($errno, $message, $data) {
        return new MJsonResponse(array('errno' => $errno, 'message' => $message, 'data' => $data));
    }

    public static function responseSuccess($message, $data) {
        return MJsonResponse::response(self::SUCCESS, $message, $data);
    }

    public static function responseFail($message, $data) {
        return MJsonResponse::response(self::FAIL, $message, $data);
    }

}
