<?php
//namespace App\Services\Message;

//use App\Jobs\SendSms;

class WjSmsClass {
    private $url;
    private $accountId;
    private $password;
    private $util;

    public function __construct() {
        $this->url       = 'https://api.yizhanda.net/sms';
        $this->accountId = ConfUtil::get("wj_sms_accountId");
        $this->password  = ConfUtil::get("wj_sms_password");
        $this->util = new SmsSenderUtil();
    }

    /**
     * 发送短信队列
     * @param $message
     * @param string $method
     * @return mixed
     */
    public static function queue($message,$method='sendToUserByPhone'){
        $res = dispatch((new SendSms([$method=>$message]))->onQueue('sms_queue'));
        return $res;
    }

    /**
     * 发送方法
     * @param $phoneNumbers
     * @param $msg
     * @param string $productid
     * @param string $corpid
     * @param string $key
     * @param string $begindate
     * @return array
     */
    public function send($phoneNumbers, $msg) {
        $wholeUrl = $this->url."/api/sendMessageOne";
        $timestamp = $this->getMillisecond();
        $data = [
            "userName" => $this->accountId,
            "messageList" => [
                [
                    "phone" => $phoneNumbers,
                    "content" => $msg,
                ],
            ],
            "timestamp" => $timestamp,
            "sign"=>$this->calculateSignSender($this->accountId, $this->password, $timestamp),
        ];
        $return_ret = $this->sendCurlPost($wholeUrl, $data);
        return $this->dealReturnData($return_ret) ;
    }

    public function calculateSignSender($account, $password, $time)
    {
        $md5_password = md5($password);
        return md5($account.$time.$md5_password);
    }

    protected function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    public function dealReturnData($result)
    {
        $return_result = [
            'data' => [],
            'code' => 100,
            'info' => 'success',
        ];
        if (!is_array($result)) {
            $return_result = json_decode($result, true);
        }

        Yii::log(' wj send sms:'.print_r($return_result, true).' message:'.$return_result['message']);
        return $return_result;
    }

    /**
     * 发送请求
     *
     * @param string $url     请求地址
     * @param array  $dataObj 请求内容
     *
     * @return string 应答Json字符串
     */
    public function sendCurlPost($url, $dataObj)
    {
        $headers = [
            'Content-type: application/json;charset="utf-8"',
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dataObj));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $ret = curl_exec($curl);
        if (false == $ret) {
            $result = '{ "State":'.-2 .',"MsgState":"'.curl_error($curl).'"}';
        } else {
            $rsp = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                $result = '{ \"State\":'.-1 .',"MsgState":"'.$rsp.' '.curl_error($curl).'"}';
            } else {
                $result = $ret;
            }
        }
        curl_close($curl);

        return $result;
    }

    /**
     * 通过手机号发送短信
     * @param $message
     * @return bool|mixed|string
     */
    public function sendToUserByPhone($message){
        if(!empty($message['sms_code'])){
            $message['code'] = $message['sms_code'];
        }

        if(!empty($message['mobile'])){
            $message['phone'] = $message['mobile'];
        }

        if(!isset($message['code'])){
            return  $this->util->dealReturnData(['Reason'=>'触发点不能为空']);
        }
        $messageConf = $this->util->getMessageConfByCode($message['code']);
        if(isset($messageConf['sms'])){
            $content = $this->util->replaceCode2Name($message, $messageConf['sms']);
            if ($p = ''){
                $message['phone'] = $p;
            }
            Yii::log(' send sms phone:'.$message['phone'].' content:'.$content);
            $productid = strlen($message['phone'])==11 ? '1012818':'1012809';
            return $this->send($message['phone'], $content,$productid);
        }
        return  $this->util->dealReturnData(['Reason'=>'短信触发点未配置']);
    }


}


