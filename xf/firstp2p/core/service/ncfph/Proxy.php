<?php

namespace core\service\ncfph;

use libs\utils\Logger;

class Proxy
{
    private $_ncfwxApiHost;
    private $_translatedUri;
    private $_dataReturn;

    public function __construct()
    {
        $this->_ncfwxApiHost = $this->_translatedUri = trim(rtrim(app_conf("NCFPH_API_SERVER_URL"), "/"));
        if ($this->_translatedUri == "") {
            return;
        }

        $this->_ncfwxApiHost .= "/";

        $requestUri = '';
        if (!empty($_SERVER['REQUEST_URI'])) {
            $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }

        if (!empty($_SERVER['QUERY_STRING'])) {
            $requestUri .= '?' . $_SERVER['QUERY_STRING'];
        }

        if (!empty($requestUri)) {
            $this->_translatedUri .= $requestUri;
        } else {
            $this->_translatedUri .= '/';
        }
    }

    function getRequestHeaders($multipartDelimiter = null)
    {
        $headers = array();
        foreach($_SERVER as $key => $value) {
            if(preg_match("/^HTTP/", $key)) {
                if(preg_match("/^HTTP_HOST/", $key) == 0 && preg_match("/^HTTP_ORIGIN/", $key) == 0 && preg_match("/^HTTP_CONTENT_LEN/", $key) == 0) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                    array_push($headers, "$key: $value");
                }
            } elseif (preg_match("/^CONTENT_TYPE/", $key)) {
                if(preg_match("/^multipart/", strtolower($value)) && $multipartDelimiter) {
                    $key = "Content-Type";
                    $value = "multipart/form-data; boundary=" . $multipartDelimiter;
                    array_push($headers, "$key: $value");
                }
            }
        }

        return $headers;
    }

    function buildMultipartDataFiles($delimiter, $fields, $files)
    {
        $data = '';
        $eol = "\r\n";

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
                . $content . $eol;
        }

        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
                . 'Content-Transfer-Encoding: binary'.$eol
                ;
            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--".$eol;

        return $data;
    }

    public function setDataReturn()
    {
        $this->_dataReturn = true;
        return $this;
    }

    function execute()
    {
        if ($this->_translatedUri == "") {
            return;
        }

        $start = microtime(true);
        $curl = curl_init($this->_translatedUri);

        $headers = $this->getRequestHeaders();
        array_push($headers, 'platform: wxapp');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            $postData = file_get_contents("php://input");
            if (preg_match("/^multipart/", strtolower($_SERVER['CONTENT_TYPE']))) {
                $delimiter = '-------------' . uniqid();
                $postData = $this->buildMultipartDataFiles($delimiter, $_POST, $_FILES);
                curl_setopt($curl, CURLOPT_HTTPHEADER, getRequestHeaders($delimiter));
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        }

        $contents = curl_exec($curl);
        curl_close($curl);
        list($header_text, $contents) = preg_split('/([\r\n][\r\n])\\1/', $contents, 2);
        $headers = preg_split('/[\r\n]+/', $header_text);

        foreach ($headers as $header) {
            if (!preg_match('/^Transfer-Encoding:/i', $header)) {
                if (preg_match('/^Location:/i', $header)) {
                    $header = str_replace($this->_ncfwxApiHost, "/", $header);
                }
                header($header);
            }
        }
        $end = microtime(true);

        //处理接口返回数据
        $result = json_decode($contents, true);
        if ($result['errno'] == 0) {
            Logger::info(implode(" | ", [__METHOD__, $this->_translatedUri, ($end - $start)]));
        } else {
            Logger::error(implode(" | ", [__METHOD__, 'requestNcfphApiFailed', $this->_translatedUri, ($end - $start), json_encode($_POST), $contents]));
            // 为防止普惠接口错误信息影响网信客户端逻辑 所以判断如果返回错误数据，则返回空
           // $result = ['errno' => 0, 'error' => '调用普惠接口失败', 'data' => ''];
           // $contents = json_encode($result);
        }

        //返回请求数据
        if ($this->_dataReturn == true) {
            return $result['data'];
        }
        echo $contents;
        exit;
    }
}
