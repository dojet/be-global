<?php
/**
 * AES 加密
 *
 * Filename: AESMcrypt.class.php
 *
 * @author liyan
 * @since 2014 6 25
 */
class AESMcrypt {

    private $iv = null;
    private $key = null;
    private $bit = 128;
    private $cipher;

    public function __construct($bit, $key, $iv, $mode) {
        if(empty($bit) || empty($key) || empty($iv) || empty($mode))
            return NULL;

        $this->bit = $bit;
        $this->key = $key;
        $this->iv = $iv;
        $this->mode = $mode;
        switch($this->bit) {
            case 192:$this->cipher = MCRYPT_RIJNDAEL_192; break;
            case 256:$this->cipher = MCRYPT_RIJNDAEL_256; break;
            default: $this->cipher = MCRYPT_RIJNDAEL_128;
        }
        switch($this->mode) {
            case 'ecb':$this->mode = MCRYPT_MODE_ECB; break;
            case 'cfb':$this->mode = MCRYPT_MODE_CFB; break;
            case 'ofb':$this->mode = MCRYPT_MODE_OFB; break;
            case 'nofb':$this->mode = MCRYPT_MODE_NOFB; break;
            default: $this->mode = MCRYPT_MODE_CBC;
        }
    }

    public static function AES128CBC($key, $iv) {
        return new AESMcrypt(128, $key, $iv, 'cbc');
    }

    public function encrypt($data) {
        $data = self::pad2Length($data, 16);
        $data = mcrypt_encrypt($this->cipher, $this->key, $data, $this->mode, $this->iv);
        return $data;
    }

    public function decrypt($data) {
        $data = mcrypt_decrypt($this->cipher, $this->key, $data, $this->mode, $this->iv);
        $data = $this->trimEnd($data);
        return $data;
    }

    private static function pad2Length($text, $padlen) {
        $len = strlen($text) % $padlen;
        $res = $text;
        $span = $padlen - $len;
        for($i = 0; $i < $span; $i++){
            $res.= chr($span);
        }
        return $res;
    }

    private function trimEnd($data) {
        $len = strlen($data);
        $c = $data[$len - 1];
        if(ord($c) < $len)
        {
            for($i = $len - ord($c); $i < $len; $i++){
                if($data[$i] != $c){
                    return $data;
                }
            }
            return substr($data, 0, $len - ord($c));
        }
        return $data;
    }

}
