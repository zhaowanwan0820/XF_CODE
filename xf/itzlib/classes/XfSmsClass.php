<?php
//namespace App\Services\Message;

//use App\Jobs\SendSms;

class XfSmsClass {
    private $url;
    private $accountId;
    private $password;
    private $util;

    public function __construct() {
        $this->url       = 'http://api.51welink.com/EncryptionSubmit/SendSms.ashx';
        $this->accountId = ConfUtil::get("xf_sms_accountId");
        $this->password  = ConfUtil::get("xf_sms_password");
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
    public function send($phoneNumbers, $msg,  $productid='1012818', $corpid='', $key='', $begindate='') {
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url;
        $data = new \stdClass();
        $data->AccountId = $this->accountId;
        $data->Timestamp = $curTime;
        $data->ExtendNo = $corpid;
        $data->Random = $random;
        $data->ProductId = $productid;
        $data->PhoneNos = $phoneNumbers;
        $data->Content = $msg;
        $data->SendTime = $begindate;
        $data->OutId = $key;
        $data->AccessKey = $this->util->calculateSignSender($this->accountId, $this->password, $random, $curTime, $phoneNumbers);
        $return_ret = $this->util->sendCurlPost($wholeUrl, $data);
        return $this->util->dealReturnData($return_ret) ;
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


