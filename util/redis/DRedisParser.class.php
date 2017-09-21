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

    private $pos = 0;
    private $recv = '';
    private $reply;

    function __construct($recv) {
        $this->recv = $recv;
    }

    protected function read($n) {
        $out = substr($this->recv, $this->pos, $n);
        $this->pos+= $n;
        return $out;
    }

    protected function readUntil($find) {
        $end = strpos($this->recv, $find, $this->pos);
        return $this->read($end - $this->pos);
    }

    protected function readline() {
        $out = $this->readUntil("\r\n");
        $this->pos+= 2;
        return $out;
    }

    public static function parse($recv) {
        $parser = new DRedisParser($recv);
        return $parser->parseReply();
    }

    public function parseReply() {
        $what = $this->read(1);
        switch ($what) {
        case '*':
            return $this->multiBulkReply();
            break;
        case '$':
            return $this->bulkReply();
            break;
        case ':':
            return $this->integerReply();
            break;
        case '+':
            throw DRedisException::ReplyStatusException(substr($this->recv, $this->pos));
            break;
        case '-':
            throw DRedisException::ReplyErrorException(substr($this->recv, $this->pos));
            break;
        default:
            throw new Exception("unknown reply ", 1);
        }
        throw new Exception("parse reply error", 1);
    }

    protected function bulkReply() {
        $len = $this->readline();
        if ($len == -1) {
            return false;
        }
        $bulk = $this->read($len);
        $this->pos+= strlen("\r\n");
        return $bulk;
    }

    protected function multiBulkReply() {
        $bulks = [];
        $size = (int)$this->readline();
        for ($i = 0; $i < $size; $i++) {
            $bulks[] = $this->parseReply();
        }
        return $bulks;
    }

    protected function integerReply() {
        return (int)$this->readline();
    }

}
