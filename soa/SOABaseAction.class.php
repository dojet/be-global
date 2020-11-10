<?php
/**
 * description
 *
 * Filename: SOABaseAction.class.php
 *
 * @author liyan
 * @since 2016 4 3
 */
abstract class SOABaseAction extends XBaseAction {

    final public function execute() {
        $jsonParams = MRequest::post('params');
        $params = json_decode($jsonParams, true);
        if (false === $params) {
            return $this->illegalSOARequest();
        }

        $response = $this->soaExecute($params);

        DAssert::assert($response instanceof SOAResponse, 'illegal soa response');

        return $this->displaySOAResponse($response);
    }

    abstract protected function soaExecute($params);

    protected function illegalSOARequest() {
        print 'illegal soa request';
    }

    private function displaySOAResponse(SOAResponse $response) {
        $this->addHeader('Content-Type', 'application/json');
        print $response->toJson();
    }

}
