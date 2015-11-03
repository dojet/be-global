<?php
/**
 * description
 *
 * Filename: MJsonEMD.class.php
 *
 * @author liyan
 * @since 2015 11 3
 */
class MJsonEMD extends MJsonResponse {

    protected $errno;
    protected $message;
    protected $data;

    const SUCCESS = 0;
    const FAIL = 1;

    public static function response($errno, $message, $data) {
        return new MJsonEMD(array('errno' => $errno, 'message' => $message, 'data' => $data));
    }

    public static function responseSuccess($message = 'success', $data = null) {
        return MJsonEMD::response(self::SUCCESS, $message, $data);
    }

    public static function responseFail($message = 'fail', $data = null) {
        return MJsonEMD::response(self::FAIL, $message, $data);
    }

}
