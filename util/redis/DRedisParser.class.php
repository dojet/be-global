<?php
/**
 * Filename: DRedisParser.class.php
 *
 * @author liyan
 * @since 2017 9 13
 */
class DRedisParser {

    private $pos = 0;
    private $recv = '';
    private $reply;
    private $reader;

    function __construct(IRedisReader $reader) {
        // $this->recv = $recv;
        $this->reader = $reader;
    }

    protected function read($n) {
        if ($this->pos >= strlen($this->recv)) {
            $this->recv.= $this->reader->read();
        }
        Trace::debug("redis read ========");
        Trace::debug("redis read $n bytes");
        $out = substr($this->recv, $this->pos, $n);
        Trace::debug("content: [$out]");
        $hex = array_map(function($e) {
            return sprintf("%x", ord($e));
        }, str_split($out));
        Trace::debug("hex: [".join(" ", $hex)."]");
        $this->pos+= $n;
        return $out;
    }

    protected function readUntil($find) {
        $end = strpos($this->recv, $find, $this->pos);
        return $this->read($end - $this->pos);
    }

    protected function readline() {
        $out = $this->readUntil("\r\n");
        $this->read(strlen("\r\n"));
        return $out;
    }

    public static function parse(IRedisReader $reader) {
        $parser = new DRedisParser($reader);
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
            $this->dumpError();
            throw new Exception("unknown reply ", 1);
        }
        throw new Exception("parse reply error", 1);
    }

    protected function dumpError() {
        Trace::fatal('redis parser error begin =================');
        $p = 0;
        $hex = array_map(function($e) {
            global $p;
            $fmt = "%02X";
            if ($p++ == $this->pos) {
                $fmt = "->%02X";
            }
            return sprintf($fmt, ord($e));
        }, str_split($this->recv));
        Trace::fatal("recv: ".$this->recv);
        Trace::fatal("pos: ".$this->pos);
        Trace::fatal("hex: [".join("  ", $hex)."]");
        Trace::fatal('redis parser error end   =================');
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
