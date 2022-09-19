<?php

class AuthUserClass{

    private function checkLegal($accessKey){
        $secretKey = $this->getSecretKey($accessKey);
        return $secretKey;
    }
    
    public function checkSign() {
        if(!empty($_GET)) {
            $requestMethod = 'GET';
        }else if(!empty($_POST)) {
            $requestMethod = 'POST';
        } else {
            $info = '请求错误，请输入请求参数信息并只能用POST或GET方式请求数据';
            $this->echoJson(array(), 11002, $info); 
            $this->setHttpStatus(403);
            return false;
        }
        
        $userInfo = $this->getAccessKeyAndSignature();
        $accessKey = $userInfo['Credential'];
        $Signature = $userInfo['Signature'];
        if (empty($accessKey)) {
            $info = '请传入Credential';
            $this->echoJson(array(), 13001, $info);  
            $this->setHttpStatus(403);
            return false;
        }
        if (empty($Signature)) {
            $info = '请传入Signature';
            $this->echoJson(array(), 13003, $info);  
            $this->setHttpStatus(403);
            return false;
        }
        //$reqData = $this->getRequestArr($requestMethod);
        $signStr = $this->getSignStr($accessKey, $requestMethod);
        $secretKey = $this->checkLegal($accessKey);
        if (!$secretKey) {
            return false;
        }
        $itouziSignature = $this->getSignature($signStr, $secretKey);
        if ($itouziSignature !== $Signature) {
            $info = '签名验证失败!';
            $this->echoJson(array(), 13002, $info);  
            $this->setHttpStatus(403);
            return false;
        }
        return $accessKey;
    }
    
    //生成签名
    private function getSignature($signStr, $secretKey) {
        return hash_hmac('sha256',$signStr, $secretKey);
    }
    
    private function getAccessKeyAndSignature() {
        
        $headers = $this->getHeaderInfo();
        $accessKey = '';
        $signature = '';
        if(isset($headers['HTTP_X_ITZ_AUTHORIZATION'])) {
            parse_str($headers['HTTP_X_ITZ_AUTHORIZATION'], $authorizationArr);
            if(isset($authorizationArr['Credential']) && isset($authorizationArr['Signature'])) {
                $accessKey = $authorizationArr['Credential'];
                $signature = $authorizationArr['Signature'];
            } 
        }else {
            if(!empty($_GET)) {
                $accessKey = $_GET['Credential'];
                $signature = $_GET['Signature'];
            }else if(!empty($_POST)) {
                $accessKey = $_POST['Credential'];
                $signature = $_POST['Signature'];
            }
        } 
        return array('Credential' => $accessKey, 'Signature' => $signature );
    }

    protected function getSignStr($accessKey, $req = 'GET') {
        $queryStr = $this->getQueryString($accessKey, $req);
        $body = $this->getBody();
        $req = strtoupper($req);
        //$purl = parse_url(Yii::app()->request->getUrl());
        $path = Yii::app()->request->getPathInfo();
        $strToSign = $req . "\n" . //GET/PUT
                $path . "\n" . //Canonical_Request_Path,URI,/-?
                $queryStr . "\n" . //Canonical_Query_String
                "content-type:" . $_SERVER['CONTENT_TYPE'] . "\n" .
                "host:" . $_SERVER['HTTP_HOST'] . "\n" .
                "x-itz-date:" . $_SERVER['HTTP_X_ITZ_DATE'] . "\n" .
                hash('sha256', $body); //Hashed_Payload(BODY)
        return $strToSign;
    }

    private function getBody($req = 'POST') {
        if($req == 'POST') {
            $body = @file_get_contents('php://input');
            parse_str($body, $bodyArr);
            unset($bodyArr['Signature']);
            unset($bodyArr['Credential']);
            $body = http_build_query($bodyArr);
        } else {
            $body = '';
        }
        return $body;
    }

    //获取请求数据并以字符串返回
    private function getQueryString($accessKey, $req = 'GET') {
        $queryArr = array();
        $queryArrEnc = array();
        $queryStr = '';
        $queryArr = $this->getRequestArr($req);
        unset($queryArr['Credential']);
        unset($queryArr['Signature']);
        foreach ($queryArr as $key => $val) {
            $keyEnc = urldecode($key);
            $valEnc = urldecode($val);
            $queryArrEnc[$key] = $keyEnc . "=" . $valEnc;
        }
        //krsort($queryArrEnc);
        $queryStr = implode('&', $queryArrEnc);
        return 'Credential='.$accessKey.'&'.$queryStr;
    }

    private function echoJson($data = array(), $code = 0, $info = '') {
        $data = ArrayUtil::getArray($data);
        $res["data"] = $data;
        $res['code'] = intval($code);
        $res['info'] = $info;
        echo json_encode($res);
    }

    /*protected function checkAccessId($accessKey) {
        if(empty($accessKey) || $accessKey !== $_REQUEST['Credential']){
            $info = 'Identity error1';
            $this->echoJson(array(), self::C10003, $info);
            exit;
        }
    }*/
    
    /*private function checkRequestDate() {
        $xitzdate = $_SERVER['HTTP_X_ITZ_DATE'];
        $ymd = substr($xitzdate, 0, 8);
        $hms = substr($xitzdate, 9, 6);
        $requestTime = strtotime($ymd.$hms);
        $nowTime = time();
        if($nowTime-$requestTime > 60*30){
            $info = '错误:参数错误2param';
            $this->echoJson(array(), self::C10003, $info);
            exit;
        }
    }*/
    
    private function getRequestArr($req='POST'){
        $req = strtoupper($req);
        switch ($req) {
            case 'POST':
                return $_POST;
                break;
            case 'GET':
                return $_GET;
            default:
                break;
        }
    }

   /* protected function getAgentInfo() {
        $agentData = array(
            'access_key_id' => $_REQUEST['Credential']
        );
        $agentInfo = BaseCrudService::getInstance()->get("Agent","",0,0,"",$agentData);
        //var_dump($agentInfo);
        return $agentInfo;
    }*/

    private function getSecretKey($accessKeyId) {
        try{
            $conn = Yii::app()->db;
            $command = $conn->createCommand()
                            ->select('secret_key')
                            ->from('itz_access_key')
                            ->where('access_key_id=:access_key_id and status=1')
                            ->limit(1);
            $command->bindParam(':access_key_id', $accessKeyId);
            $res = $command->queryAll();
            if(!empty($res)) {
                foreach($res as $val) {
                    return $val['secret_key'];
                }
            } else {
                $info = '身份认证失败，无效的accessKey';
                $this->echoJson(array(),13000, $info);
                $this->setHttpStatus(403);
                return false;
            }
        } catch(Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, __METHOD__);
            $info = '系统错误，请联系相关技术人员';
            $this->echoJson(array(), 11000, $info); 
            $this->setHttpStatus(500);
            return false;
        }
    }

    //设置http返回状态
    public function setHttpStatus($statusCode) {
        $httpStatus = array(
                        200 => "HTTP/1.1 200 OK", 

                        400 => "HTTP/1.1 400 Bad Request", 
                        401 => "HTTP/1.1 401 Unauthorized", 
                        402 => "HTTP/1.1 402 Payment Required", 
                        403 => "HTTP/1.1 403 Forbidden", 
                        404 => "HTTP/1.1 404 Not Found", 
                        405 => "HTTP/1.1 405 Method Not Allowed", 
                        406 => "HTTP/1.1 406 Not Acceptable", 

                        500 => "HTTP/1.1 500 Internal Server Error", 
                        501 => "HTTP/1.1 501 Not Implemented", 
                        502 => "HTTP/1.1 502 Bad Gateway", 
                        503 => "HTTP/1.1 503 Service Unavailable", 
                        504 => "HTTP/1.1 504 Gateway Time-out"  
                     );
        header($httpStatus[$statusCode]); 
    }

    private function getHeaderInfo() {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[$name] = $value;
            } 
        }
        return $headers;
    }
}