<?php
/**
 *
 * @author liyan
 * @since 2016 4 2
 */
class SOA {

    public static function call(ISOADelegate $delegate, $service, $method, $params) {
        try {
            $node = SOARegistry::getNode($service);
        } catch (Exception $e) {
            throw $e;
        }

        $domain = $node->domain();
        $url = sprintf("%s/%s", $service, $method);

        $response = self::sendSOARequest($url, $params);
        $soaResponse = self::resolveSOAResponse($response);
        DAssert::assert($soaResponse instanceof SOAResponse, 'illegal soa response');

        $delegate->didReceivedResponse($soaResponse);
    }

    protected static function sendSOARequest($url, $params) {
        DAssert::assert(is_array($params), 'illegal soa params, must be array');
        $postFields = array('params' => json_encode($params));
        $curl = MCurl::curlPostRequest($url, $postFields);
        $response = $curl->sendRequest();
        return $response;
    }

    protected static function resolveSOAResponse($response) {
        $soaResponse = SOAResponse::responseFromJson($response);
        return $soaResponse;
    }

}
