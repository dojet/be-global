<?php
/**
 * description
 *
 * Filename: MJsonResponse.class.php
 *
 * @author liyan
 * @since 2015 11 3
 */
class MJsonResponse {

    protected $array = array();

    function __construct($array) {
        $this->array = $array;
    }

    public function toJson() {
        return json_encode($this->array);
    }

}
