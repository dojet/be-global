<?php
/**
 * Model
 *
 * Filename: SOANode.class.php
 *
 * @author liyan
 * @since 2016 4 2
 */
class SOANode {

    protected $info;

    function __construct($info) {
        DAssert::assert(is_array($info), 'illegal soa node info');
        $this->info = $info;
    }

    public static function nodeFromDomain($domain) {
        return new SOANode(array('domain' => $domain));
    }

    public static function nodeFromJson($json) {
        return new SOANode(json_decode($json, true));
    }

    public function domain() {
        return $this->info['domain'];
    }

    public function toJson() {
        return json_encode($this->info);
    }

}
