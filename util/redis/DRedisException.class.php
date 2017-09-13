<?php
/**
 * dal base
 *
 * Filename: DRedisException.class.php
 *
 * @author liyan
 * @since 2017 9 13
 */
class DRedisException extends Exception {

    const REPLY_ERROR = -1;
    const REPLY_STATUS = 1;

    public static function ReplyErrorException($msg) {
        return new DRedisException($msg, self::REPLY_ERROR);
    }

    public static function ReplyStatusException($msg) {
        return new DRedisException($msg, self::REPLY_STATUS);
    }

}
