<?php
namespace core\service\report;

use libs\utils\Curl;
use libs\utils\Logger;
use libs\utils\Monitor;

class ReportBase
{
    private $config;
    private $ifaCryptUtil;
    private $ifaSmUtil;
    private $ifaPubx;
    private $ifaPuby;
    private $ifaSBankCode;
    private $ifaPublicKey;
    private $ifaPrivateKey;
    private $reportUrl;

    const IFA_REPORT_TYPE_DEAL = '1'; //IFA上报类型:标的
    const IFA_REPORT_TYPE_DEAL_STATUS = '2'; //IFA上报类型:标的状态
    const IFA_REPORT_BORROWER_TYPE_PERSON = '01'; //IFA上报标的借款人类型:个人
    const IFA_REPORT_BORROWER_TYPE_ENTERPRISE = '02'; //IFA上报标的借款人类型:企业

    const REPORT_STATUS_NOTIFY_FAILD = '-2'; //回调失败
    const REPORT_STATUS_SEND_FAILD = '-1'; //发送失败
    const REPORT_STATUS_INIT = '0'; //初始状态
    const REPORT_STATUS_SUCCESS = '1'; //发送完成
    const REPORT_STATUS_WAIT_NOTIFY = '2'; //等待回调
    const REPORT_STATUS_NOTIFY_SUCCESS = '3'; //已回调,完成

    const BAIHANG_REPORT_TYPE_LOAN = '3'; //百行上报类型:放款
    const BAIHANG_REPORT_TYPE_REPAY = '4'; //百行上报类型:还款

    const P2P_LOAN_PURPOSE_OTHERS = '0';  //P2P借款用途：其他
    const P2P_LOAN_PURPOSE_BUSINESS = '1'; //p2p借款用途：企业经营
    const P2P_LOAN_PURPOSE_SHORT_TERM_WORK = '2'; //p2p借款用途:短期周转
    const P2P_LOAN_PURPOSE_DAILY_CONSUMPTION = '3'; //p2p借款用途:日常消费

    const BAIHANG_LOAN_PURPOSE_BUSINESS = '10'; //百行借款用途：企业经营
    const BAIHANG_LOAN_PURPOSE_DAILY_CONSUMPTION = '13'; //百行借款用途：日常消费
    const BAIHANG_LOAN_PURPOSE_UNKNOWN = '99'; //百行借款用途：默认未知

    const LOAN_STATUS_NORMAL = '1';   //正常还款
    const LOAN_STATUS_OVERDUE = '2';  //逾期
    const LOAN_STATUS_CLEARED = '3';  //结清
    const LOAN_STATUS_REVOKE = '4'; //撤销

    const BAIHANG_TERM_STATUS_NORMAL = 'normal';    //百行本期还款状态 正常
    const BAIHANG_TERM_STATUS_OVERDUE = 'overdue';  //百行本期还款状态 逾期

    const BAIHANG_LOAN_STATUS_NORMAL = '1';  //百行本期贷款状态 正常
    const BAIHANG_LOAN_STATUS_CLEARED = '3';  //百行本期贷款状态 结清

    public function __construct()
    {

        $this->config = require("config/".get_cfg_var("phalcon.env").".php");
        $this->reportUrl = $this->config['IFA']['REPORT_URL'];
        \FP::import("libs.java.java");
        $this->ifaCryptUtil = new \Java("com.cfcc.jaf.crypto.CryptoUtil");
        $this->ifaSmUtil = new \Java("com.cfcc.jaf.crypto.sm.SMUtil");
        $this->ifaPubx = $this->ifaCryptUtil->toByteArray($this->config['IFA']['PUBLIC_KEY_X']);
        $this->ifaPuby = $this->ifaCryptUtil->toByteArray($this->config['IFA']['PUBLIC_KEY_Y']);

        $this->ifaSBankCode = $this->config['IFA']['UNIFORM_SOCIAL_CREDIT_CODE'];

        $this->ifaPublicKey = $this->ifaSmUtil->createECPoint($this->ifaPubx,$this->ifaPuby);
        $this->ifaPrivateKey = $this->ifaCryptUtil->toByteArray($this->config['IFA']['PRIVATE_KEY']);

        $this->baihangPublicKeyPath = $this->config['BAIHANG']['PUBLIC_KEY_FILE_PATH'];
        $this->baihangReportUrl = $this->config['BAIHANG']['REPORT_URL'];
        $this->baihangAesKey = $this->config['BAIHANG']['AES_KEY'];
        $this->agencyName = $this->config['BAIHANG']['AGENCY_NAME'];
        $this->baihangFtpConfig =  $this->config['BAIHANG']['FTP'];

    }

    //获取ifa签名
    public function ifaSign($content){
        if(!empty($content)){
            $sign = $this->ifaSmUtil->sign($this->ifaSBankCode,$this->ifaPrivateKey,$this->ifaPublicKey,$content);
        }else{
            return false;
        }
        return $sign;
    }

    //ifa加密
    public function ifaEncrypt($content){
        return $this->ifaSmUtil->encryptBySM2($this->ifaPublicKey,$content);
    }

    //ifa解密
    public function ifaDecrypt($content){
        return $this->ifaSmUtil->decryptBySM2($this->ifaPrivateKey,$content);
    }

    //发送数据
    public function ifaPush($data)
    {
        if (empty($data)) {
            Logger::error(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 没有报备数据 ".json_encode($id));
            throw new \Exception('没有需要上报的数据');
        }

        $headers = array(
            'sbankcode:'. $data['sbankcode'],
            'sdatacode:' . $data['sdatacode'],
            'scode:' . $data['scode'],
            'sign:' . $data['sign'],
            'Accept:application/json',
        );

        $request['sdata'] = $data['sdata'];
        $res = Curl::post_json($this->reportUrl, json_encode($request,JSON_UNESCAPED_UNICODE),  10, $headers);

        $logStr = "cost:" . Curl::$cost . ",err:" . Curl::$error . ",httpCode:" . Curl::$httpCode . " ,data:" . json_encode($request) . ", return:" . ($res);

        if (!$res) {
            Logger::info(__CLASS__ . "," . __FUNCTION__ . ",push data fail:" . $logStr);
            return false;
        } else {
            Logger::info(__CLASS__ . "," . __FUNCTION__ . ",push data fail:" . $logStr);
            $res = json_decode($res, true);
            return $res;
        }
    }

    public function getIfaSBankCode(){
        return $this->config['IFA']['UNIFORM_SOCIAL_CREDIT_CODE'];
    }

    //获取零售or对公信贷项目信息
    public function getProjectInfo($type,$approveNumber){
        $requestData = array();

        if($type === self::IFA_REPORT_BORROWER_TYPE_PERSON){
            $url = $this->config['RCMS']['RETAIL_PROJECT']['URL'];
            $requestData['clientNumber'] = $this->config['RCMS']['RETAIL_PROJECT']['CLIENT_NUMBER'];


        }elseif($type === self::IFA_REPORT_BORROWER_TYPE_ENTERPRISE){
            $requestData['clientNumber'] = $this->config['RCMS']['BUSINESS_PROJECT']['CLIENT_NUMBER'];
            $url = $this->config['RCMS']['BUSINESS_PROJECT']['URL'];
        }else{
            return false;
        }


        list($msec, $sec) = explode(' ', microtime());
        $requestData['timestamp'] = intval(sprintf('%.0f',(floatval($msec)+floatval($sec))*1000));
        $requestData['approveNumber'] = $approveNumber;

        $requestArr = array();
        foreach($requestData as $rk => $rv){
            $requestArr[] = $rk.'='.$rv;
        }

        if($type === self::IFA_REPORT_BORROWER_TYPE_PERSON){
            $sign = $this ->getRcmsSign($requestData,$this->config['RCMS']['RETAIL_PROJECT']['CLIENT_SECRET']);
        }elseif($type === self::IFA_REPORT_BORROWER_TYPE_ENTERPRISE){
            $sign = $this ->getRcmsSign($requestData,$this->config['RCMS']['BUSINESS_PROJECT']['CLIENT_SECRET']);
        }

        $requestStr = implode('&',$requestArr);
        $url = $url.'?'.$requestStr.'&sign='.$sign;

        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",url:" . $url);

        $res = Curl::get($url);
        if($res){
            $res = json_decode($res,true);

            if($res['success'] == true){
                return $res['data'];
            };
        }

        return false;
    }


    //信贷接口签名生成
    private function getRcmsSign($data,$secret){
        ksort($data);

        $signStr = $secret;
        foreach($data as $dataKey => $dataValue){
            $signStr .= $dataKey;
            $signStr .= $dataValue;
        }

        $signStr .= $secret;
        return  md5($signStr);

    }
    public function getBaihangLoanPurpose($loanPurpose){

        if ($loanPurpose == self::P2P_LOAN_PURPOSE_BUSINESS){
            return self::BAIHANG_LOAN_PURPOSE_BUSINESS;
        }elseif(in_array($loanPurpose,array(self::P2P_LOAN_PURPOSE_OTHERS, self::P2P_LOAN_PURPOSE_SHORT_TERM_WORK, self::P2P_LOAN_PURPOSE_DAILY_CONSUMPTION))){
            return self::BAIHANG_LOAN_PURPOSE_DAILY_CONSUMPTION;
        }else{
            return self::BAIHANG_LOAN_PURPOSE_UNKNOWN;
            //throw new \Exception('错误的贷款用途');
        }
    }
    public function baihangEncrypt($data){

        foreach($data as $key =>$value){
            if(in_array($key,array('name','pid','mobile'))){
                $data[$key] = $this->rsaEncrypt($value);
            }
        }

        return $data;

    }
    public function rsaEncrypt($string){
        $publicKey = openssl_pkey_get_public(file_get_contents($this->baihangPublicKeyPath));
        $encryptValue = '';
        openssl_public_encrypt($string,$encryptValue,$publicKey);
        return base64_encode($encryptValue);
    }
    public function mask($data){
        if ($data['name']){
            $data['name'] = $this->maskName($data['name']);
        }
        if ($data['pid']){
            $data['pid'] = $this->maskPid($data['pid']);
        }
        if ($data['mobile']){
            $data['mobile'] = $this->maskMobile($data['mobile']);
        }
        return $data;
    }
    //姓名脱敏
    public function maskName($name,$replace='某'){
        $maskName = '';
        if ($name){
            $maskName = mb_substr($name, 0, 1) . str_repeat($replace, (mb_strlen($name) - 1));
        }
        return $maskName;
    }
    //身份证号脱敏
    public function maskPid($pid,$replace='0'){
        $maskPid = '';
        if ($pid){
            $maskPid = substr($pid,0,3).'00000000000'.substr($pid,-4);
        }
        return $maskPid;
    }
    //手机号码脱敏
    public function maskMobile($mobile,$replace='0'){
        $maskMobile = '';
        if($mobile){
            $maskMobile = substr($mobile,0,3).'0000'.substr($mobile,-4);
        }
        return $maskMobile;
    }

    public function aesEncode($encrypt, $key){
        $blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $pad = $blockSize - (strlen($encrypt) % $blockSize);
        $paddedData = $encrypt.str_repeat(chr($pad), $pad);
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $key2 = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key2, $paddedData, MCRYPT_MODE_ECB, $iv);
        return base64_encode($encrypted);
    }


    public function baihangPush($data)
    {
        if (empty($data)) {
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 没有报备数据 ");
            throw new \Exception('没有报备数据');
        }

        $headers = array(
            'Authorization:'.$data['authorization'],
            'Accept:application/json',
        );

        $request = $data['sdata'];
        $reportUrl = $data['reportUrl'];
        $res = Curl::post_json($reportUrl, json_encode($request,JSON_UNESCAPED_UNICODE),  10, $headers);

        $logStr = "cost:" . Curl::$cost . ",err:" . Curl::$error . ",httpCode:" . Curl::$httpCode . " ,data:" . json_encode($request) . ", return:" . ($res);

        if (!$res) {
            Logger::info(__CLASS__ . "," . __FUNCTION__ . ",push data fail:" . $logStr);
            return false;
        } else {
            Logger::info(__CLASS__ . "," . __FUNCTION__ . ",push data:" . $logStr);
            $res = json_decode($res, true);
            return $res;
        }
    }
    public function getBaihangAuthorization(){
        $admin = $this->config['BAIHANG']['ADMIN'];
        $password = $this->config['BAIHANG']['PASSWORD'];
        $authorization = 'Basic '.base64_encode($admin.':'.$password);
        return $authorization;
    }
    public function getBaihangReportUrl($recordType){
        switch($recordType){
            case self::BAIHANG_REPORT_TYPE_LOAN:
                $reportUrl = $this->baihangReportUrl['D2'];
                break;
            case self::BAIHANG_REPORT_TYPE_REPAY:
                $reportUrl = $this->baihangReportUrl['D3'];
                break;

        }
        return $reportUrl;

    }
}
