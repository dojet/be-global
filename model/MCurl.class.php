<?php
/**
 * curl
 *
 * Filename: MCurl.class.php
 *
 * @author liyan
 * @since 2014 5 19
 */
class MCurl {

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    protected $url;
    protected $method;

    protected $headers;
    protected $postFields;

    protected $useProxy;
    protected $useGZip;
    protected $timeout;

    protected $retry;
    protected $retrySleep;

    protected $headerCallback;

    protected static $error;

    protected $arrOptions;

    function __construct($url, $method) {
        DAssert::assert(in_array($method, array(self::METHOD_GET, self::METHOD_POST)));

        $this->url = $url;
        $this->method = $method;

        $this->headers = array();
        $this->postFields = array();

        $this->useProxy = false;
        $this->useGZip = false;
        $this->timeout = 30;

        $this->retry = 3;
        $this->retrySleep = 0;

        $this->arrOptions = array();
    }

    public static function curlGetRequest($url) {
        $curl = new MCurl($url, MCurl::METHOD_GET);
        return $curl;
    }

    public static function curlPostRequest($url, $postFields) {
        $curl = new MCurl($url, MCurl::METHOD_POST);
        $curl->setPostFields($postFields);
        return $curl;
    }

    public function setPostFields($postFields) {
        $this->postFields = $postFields;
    }

    public function setTimeout($timeout) {
        DAssert::assertIntNumeric($timeout);
        $this->timeout = $timeout;
    }

    public function setUseProxy($isUseProxy) {
        $this->useProxy = (bool)$isUseProxy;
    }

    public function setUseGZip($isUseGZip) {
        $this->useGZip = (bool)$isUseGZip;
    }

    public function setHeaders($arrRequestHeaders) {
        DAssert::assert(is_array($arrRequestHeaders), 'request header must be array');
        $this->headers = $arrRequestHeaders;
    }

    public function setOption($key, $value) {
        $this->arrOptions[$key] = $value;
    }

    public function setRetry($retry) {
        $this->retry = $retry;
    }

    public function setRetrySleep($retrySleep) {
        $this->retrySleep = $retrySleep;
    }

    public function setHeaderCallback($headerCallback) {
        DAssert::assert(is_callable($headerCallback), 'illegal callback');
        $this->headerCallback = $headerCallback;
    }

    public function removeOption($key) {
        if (isset($this->arrOptions[$key])) {
            unset($this->arrOptions[$key]);
        }
    }

    public function removeAllOptions() {
        $this->arrOptions = array();
    }

    public function sendRequest() {
        Trace::debug('=== Start curl ===');

        $retry = $this->retry;

        $startTime = microtime(true);
        do {
            Trace::debug('url: '.$this->url);
            Trace::verbose('get url: '.$this->url);
            Trace::debug('method: '.$this->method);
            Trace::debug('header: '.http_build_query($this->headers));
            Trace::debug('post_fields: '.serialize($this->postFields));
            Trace::debug('timeout: '.$this->timeout);

            $ch = curl_init($this->url);
            if ($this->method === MCurl::METHOD_POST) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postFields);
            }
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            // curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            // curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            if ($this->headers) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            if($this->useGZip)
            {
                curl_setopt($ch,CURLOPT_ENCODING ,'gzip');
            }
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
            if($this->useProxy)
            {
                $config = Config::runtimeConfigForKeyPath('proxy');
                if($config)
                {
                    Trace::debug('use proxy: '.$config['username'].'@'.$config['host'].':'.$config['port']);
                    curl_setopt($ch, CURLOPT_PROXY,$config['host'].":".$config['port'] );
                    if (isset($config['username']) && isset($config['password'])) {
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $config['username'].':'.$config['password']);
                    }
                }
            }

            if ($this->headerCallback) {
                curl_setopt($ch, CURLOPT_HEADERFUNCTION, $this->headerCallback);
            }

            //  apply user options
            curl_setopt_array($ch, $this->arrOptions);

            $response = curl_exec($ch);

            if (false === $response) {
                self::$error = curl_errno($ch);
                Trace::debug('curl error: '.curl_error($ch));
                Trace::verbose('curl error: '.curl_error($ch));
                Trace::debug('retry: '.$retry);
                sleep($this->retrySleep);
                $retry--;
            } else {
                self::$error = 0;
                Trace::debug('response length: '.strlen($response));
                Trace::verbose('response: '.var_export($response, true));
                $retry = 0;
            }

            curl_close($ch);

        } while ($retry > 0);

        $timespan = microtime(true) - $startTime;
        Trace::debug('timespan: '.$timespan);
        Trace::debug('=== End curl ===');

        return $response;
    }

}
