<?php
/**
 * 微信基类
 *
 * Filename: WXBaseAction.class.php
 *
 * @author liyan
 * @since 2014 12 2
 */
abstract class WXBaseAction extends BaseAction {

    protected $xmlObj;
    protected $fromUser;
    private $meUser;

    final public function execute() {

        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        Trace::debug('post: '.serialize($postStr));

        $echostr = MRequest::get('echostr');
        if (!empty($echostr)) {
            Trace::debug('echostr '.$echostr);
            echo $echostr;
            return;
        }
//*
        if (!$this->checkSignature()) {
            $this->signatureFailed();
            return;
        }
//*/

        //extract post data
        if (empty($postStr)){
            Trace::debug('empty poststr');
            $this->errorOccured();
            return;
        }

        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

        $this->fromUser = $postObj->FromUserName;
        $this->meUser = $postObj->ToUserName;

        $this->xmlObj = $postObj;

        $msgtype = strval($postObj->MsgType);

        if ('text' === $msgtype) {
            $text = strval($postObj->Content);
            $this->received_text($postObj, $text);
        } elseif ('event' === $msgtype) {
            $event = strval($postObj->Event);
            $eventKey = strval($postObj->EventKey);
            $this->received_event($postObj, $event, $eventKey);
        } else {
            $this->received_unknown($postObj);
        }

    }

    protected function received_text($postObj, $text) {}

    protected function received_event($postObj, $event, $eventKey) {}

    protected function received_unknown($postObj) {}

    protected function signatureFailed() {
        Trace::debug('check signature failed');
    }

    protected function errorOccured() {
        Trace::debug('someting wrong!');
    }

    protected function displayXML($xml) {
        print $xml;
    }

    protected function respondText($content, $flag = 0) {
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>%u</FuncFlag>
                    </xml>";
        $response = sprintf($textTpl, $this->fromUser, $this->meUser, time(), 'text', $content, $flag);
        $this->displayXML($response);
    }

    protected function respondNews($arrNewsList, $flag = 0) {
        $tpl = <<<heredoc
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>%s</Articles>
<FuncFlag>%s</FuncFlag>
</xml>
heredoc;

        $itemTpl = <<<heredoc
<item>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<PicUrl><![CDATA[%s]]></PicUrl>
<Url><![CDATA[%s]]></Url>
</item>
heredoc;

        $strArticles = '';
        foreach ($arrNewsList as $news) {
            $strItem = sprintf($itemTpl, $news['title'], $news['desc'], $news['picurl'], $news['url']);
            $strArticles.= $strItem;
        }

        $strRespond = sprintf($tpl, $this->fromUser, $this->meUser, time(),
            count($arrNewsList), $strArticles, $flag);
        $this->displayXML($strRespond);
    }

    protected function respondTextNews($arrNewsList, $flag = 0) {
        $tpl = <<<heredoc
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>%s</Articles>
<FuncFlag>%s</FuncFlag>
</xml>
heredoc;

        $itemTpl = <<<heredoc
<item>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<PicUrl><![CDATA[]]></PicUrl>
</item>
heredoc;

        $strArticles = '';
        foreach ($arrNewsList as $news) {
            $strItem = sprintf($itemTpl, $news['title'], $news['desc']);
            $strArticles.= $strItem;
        }

        $strRespond = sprintf($tpl, $this->fromUser, $this->meUser, time(),
            count($arrNewsList), $strArticles, $flag);
        $this->displayXML($strRespond);
    }

    private function checkSignature() {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = WXTOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr !== $signature ){
            Trace::debug('check signature: '.serialize($_GET));
            Trace::debug('tmpstr: '.$tmpStr);
            Trace::debug('debugsigfail:'.serialize($_GET).$tmpStr);
            return false;
        }
        return true;
    }

}
