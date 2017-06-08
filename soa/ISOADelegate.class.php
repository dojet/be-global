<?php
/**
 * @author liyan
 * @since 2017 6 5
 */
interface ISOADelegate {

    public function didReceivedSOAResponse(SOAResponse $response);
    public function receivedSOAError($errmsg);

}