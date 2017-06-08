<?php
/**
 *
 * @author liyan
 * @since 2016 4 2
 */
class SOA {

    public static function call(ISOADelegate $delegate, $service, $method, $params, $context = null) {
        // try {
        //     $node = SOARegistry::getNode($service);
        // } catch (Exception $e) {
        //     throw $e;
        // }

        // $domain = $node->domain();
        // $url = sprintf("%s/%s", $domain, $method);
        $url = self::getSOARequestUrl($service, $method);

        $response = self::sendSOARequest($url, $params);
        try {
            $soaResponse = self::resolveSOAResponse($response);
        } catch (Exception $e) {
            return $delegate->receivedSOAError($e->getMessage());
        }

        DAssert::assert($soaResponse instanceof SOAResponse, 'illegal soa response');

        $delegate->didReceivedSOAResponse($soaResponse, $context);
    }

    protected static function getSOARequestUrl($service, $method) {
        return Config::runtimeConfigForKeyPath(sprintf('soa.service.%s.$.%s', $service, $method));
    }

    protected static function sendSOARequest($url, $params) {
        DAssert::assert(is_array($params), 'illegal soa params, must be array');
        $curl = MCurl::curlPostRequest($url, json_encode($params));
        $response = $curl->sendRequest();
        return $response;
    }

    protected static function resolveSOAResponse($response) {
        $soaResponse = SOAResponse::responseFromJson($response);
        return $soaResponse;
    }

}
