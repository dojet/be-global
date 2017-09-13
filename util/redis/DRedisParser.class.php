<?php
/**
 * dal base
 *
 * Filename: DRedisParser.class.php
 *
 * @author liyan
 * @since 2017 9 13
 */
class DRedisParser {

    public static function parse($recv) {
        $reply = explode("\r\n", $recv);
        array_pop($reply);
        var_dump($reply);
        $parser = new DRedisParser();
        return $parser->parseReply($reply);
    }

    public function parseReply(&$reply) {
        while (list($key, $line) = each($reply)) {
            $what = $line{0};
            switch ($what) {
            case '*':
                return $this->multiBulkReply($reply);
                break;
            case '$':
                return $this->bulkReply($reply);
                break;
            case ':':
                return $this->integerReply($reply);
                break;
            case '+':
                throw DRedisException::ReplyStatusException(substr($line, 1));
                break;
            case '-':
                throw DRedisException::ReplyErrorException(substr($line, 1));
                break;
            default:
                throw new Exception("unknown reply", 1);
            }
        }
        throw new Exception("parse reply error", 1);
    }

    protected function bulkReply(&$reply) {
        $line = array_shift($reply);
        $len = (int)substr($line, 1);
        $line = array_shift($reply);
        if ($len == -1) {
            return false;
        }
        return substr($line, 0, $len);
    }

    protected function multiBulkReply(&$reply) {
        $bulks = [];
        $line = array_shift($reply);
        $size = (int)substr($line, 1);
        for ($i = 0; $i < $size; $i++) {
            $bulks[] = $this->parseReply($reply);
        }
        return $bulks;
    }

    protected function integerReply(&$reply) {
        $line = array_shift($reply);
        return (int)substr($line, 1);
    }

}
