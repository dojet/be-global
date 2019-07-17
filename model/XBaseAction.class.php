<?php
/**
 * description
 *
 * Filename: XBaseAction.class.php
 *
 * @author liyan
 * @since 2014 8 19
 */
abstract class XBaseAction extends BaseAction {

    protected $arrHeader;

    function __construct(WebService $webService) {
        parent::__construct($webService);
        $this->arrHeader = array();
    }

    protected function templatePrefix($template) {
        return $this->webService->root().'template/';
    }

    protected function fullTemplate($template) {
        $prefix = $this->templatePrefix($template);
        return $prefix.$template;
    }

    protected function displayTemplate($template) {
        $template = $this->fullTemplate($template);
        return $this->display($template);
    }

    protected function shouldDisplay($template) {
        return true;
    }

    protected function display($template) {
        if (!$this->shouldDisplay($template)) {
            return;
        }

        foreach ($this->arrHeader as $key => $value) {
            header($key.":".$value);
        }

        return parent::display($template);
    }

    protected function displayJson(MJson $json) {
        $this->addHeader('Content-Type', 'application/json');
        $this->assign('json', $json);
        $this->display(dirname(__FILE__).'/../template/jsonresponse.tpl.php');
    }

    protected function displayJsonSuccess($data = null, $message = 'success') {
        $jsonResponse = MJsonResponse::responseSuccess($message, $data);
        $this->displayJson($jsonResponse);
    }

    protected function displayJsonFail($data = null, $message = 'fail') {
        $jsonResponse = MJsonResponse::responseFail($message, $data);
        $this->displayJson($jsonResponse);
    }

    protected function displayDebug() {
        printa($this->tplData);
    }

    protected function addHeader($key, $value) {
        DAssert::assert(is_string($key) && is_string($value), 'header must be string',
            __FILE__, __LINE__);
        $this->arrHeader[$key] = $value;
    }

    protected function setExpire($timestamp) {
        $gmtime = date("r", $timestamp);
        $this->addHeader('Expires', $gmtime);
    }

}
