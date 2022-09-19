<?php
/**
 *@author longbo
 */
namespace core\service\partner\common;

use libs\utils\Alarm;
use libs\utils\Logger;

class RequestBase
{
    protected $config = [
            'unsetKey' => ['sign'],
            'signKey' => ['time' => 'timestamp', 'sign' => 'sign'],
            'signType' => '0',
            'signCase' => 'lower',
            'timeType' => 0,
            'output' => 'array',
            'timeout' => 10,
            'retries' => 2,
            'requestJson' => 0,
            'responseFormat' => [
                'code' => 'errorCode',
                'codeVal' => '0',
                'msg' => 'errorMsg',
                'data' => 'data',
                ],
        ];

    protected $rData = [
            'method' => 'get',
            'secret' => '',
            'host' => '',
            'action' => '',
            'getParams' => [],
            'postParams' => [],
            'header' => [],
            'cookie' => '',
        ];

    public static $response = null;
    public static $execTime = null;

    protected $allParams = [];
    protected $queryParams = [];
    protected $sign = null;
    protected $url = null;

    public function __construct() {}

    public function setRequestData($request = [])
    {
        $this->rData = array_merge($this->rData, $request);
        return $this;
    }

    public function execute()
    {
        return $this->call('before')
            ->call('setAllParams')
            ->call('setSign')
            ->call('setQueryParams')
            ->call('setCompleteUrl')
            ->call('execRequest')
            ->call('after');
    }

    protected function call($method)
    {
        if (is_callable(array($this, $method))) {
            $this->$method();
        }
        return $this;
    }

    protected function before()
    {
        return $this;
    }

    protected function setAllParams()
    {
        if (isset($this->config['signKey']['time'])) {
            $time = $this->config['timeType'] == 1 ? date("Y-m-d H:i:s", time()) : time();
            if ($this->config['signType'] == 1) {
                $this->rData['postParams'][$this->config['signKey']['time']] = $time;
            } else {
                $this->rData['getParams'][$this->config['signKey']['time']] = $time;
            }
        }

        $this->allParams = array_merge(
            $this->rData['getParams'],
            $this->rData['postParams']
        );
        return $this;
    }

    protected function setSign()
    {
        $dataParams = $this->allParams;
        if (isset($this->config['unsetKey'])) {
            foreach ($this->config['unsetKey'] as $val) {
                unset($dataParams[$val]);
            }
        }
        $signStr = $this->rData['secret'];
        ksort($dataParams);
        reset($dataParams);
        foreach($dataParams as $key => $value) {
            $signStr .= $key.$value;
        }
        $signStr .= $this->rData['secret'];
        $this->sign = $this->config['signCase'] == 'lower' ?
                    strtolower(md5($signStr)) :
                    strtoupper(md5($signStr));

        Logger::debug("RequestSignStr:".$signStr.",Sign:".$this->sign);
        return $this;
    }

    protected function setQueryParams()
    {
        if ($this->config['signType'] == 1) {
            $this->rData['postParams'][$this->config['signKey']['sign']] = $this->sign;
        } else {
            $getParams = $this->rData['getParams'];
            $getParams[$this->config['signKey']['sign']] = $this->sign;
            $this->queryParams = $getParams;
        }
        return $this;
    }

    protected function setCompleteUrl()
    {
        $query = empty($this->queryParams) ?
                '' : '?' . http_build_query($this->queryParams);

        $this->url = trim($this->rData['host'], '/').'/'
            .trim($this->rData['action'], '/')
            .$query;

        return $this;
    }

    protected function execRequest()
    {
        $response = new Curl();

        $response->setUrl($this->url)
                 ->setHeader($this->rData['header'])
                 ->setCookie($this->rData['cookie'])
                 ->setTimeout($this->config['timeout']);

        if ($this->_isPost()) {
            $response->setPostData($this->rData['postParams']);
        }

        if ($this->config['requestJson'] == 1) {
             $response->setRequestJson();
        }

        $retries = min($this->config['retries'], 3);
        $response->request();
        while (false === $response::$result && $retries > 0) {
            $response->request();
            $retries--;
        }

        if ($response::$errno) {
            throw new \Exception($response::$error);
        }

        if ($response::$httpCode != 200) {
            Alarm::push('RequestThirdException', $response::$httpCode, 'Url:'.$this->url.', Post:'.json_encode($this->rData['postParams']).' Error:'.$response::$result);
            throw new \Exception('HttpCode:'.$response::$httpCode."\n".$response::$result);
        }
        self::$response = $response::$result;
        self::$execTime = $response::$execTime;
        return $this;
    }

    protected function after()
    {
        Logger::info("RequestUrl:".$this->getUrl());
        Logger::info("RequestParams:".json_encode($this->rData));
        Logger::info("RawResponse:".var_export(self::$response, true).", ExecTime:".self::$execTime."ms");
        if ($this->config['output'] == 'pretty') {
            if ($data = json_decode(self::$response)) {
                self::$response = json_encode(
                    $data,
                    JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT
                );
            }
        }

        if ($this->config['output'] == 'array') {
            self::$response = json_decode(self::$response, true);
        }

        if ($this->config['output'] == 'format') {
            $this->responseFormat();
        }

        return $this;
    }

    protected function responseFormat()
    {
        $res = json_decode(self::$response, true);
        $errorCode = $this->config['responseFormat']['code'];
        $codeVal = $this->config['responseFormat']['codeVal'];
        $data = $this->config['responseFormat']['data'];
        $msg = $this->config['responseFormat']['msg'];
        if (isset($res[$errorCode]) && $res[$errorCode] == $codeVal) {
            self::$response = isset($res[$data]) ? $res[$data] : [];
        } elseif (isset($res[$msg])) {
            throw new \Exception($res[$msg]);
        } else {
            throw new \Exception('数据异常');
        }
    }

    public function getUrl()
    {
        return $this->url;
    }

    private function _isPost()
    {
        return !empty($this->rData['postParams']) ||
            (strtolower($this->rData['method']) == 'post');
    }
}
