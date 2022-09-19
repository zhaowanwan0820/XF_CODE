<?php
/**
 * 互亿流量
 */
class HuYiClass
{
    const SUCCESS = 1;
    const FAIL = 0;
    const HUYI_URL = "http://api.ihuyi.com";
    // 正式帐号
    private $apikey = "zUdc5celZ3b51X3UM5q8";
    private $appid = "16377869";
    // 测试帐号
    // private $apikey = "6j3ao593wMNQRz4Zo4ao";
    // private $appid = "cf_testapi";
    private $mobile = "";
    private $package = 0;
    private $returnDetail;
    private $orderid;
    function HuYiClass($mobile,$package,$orderid)
    {
        $this->mobile = $mobile;
        $this->package = $package;
        $this->orderid = $orderid;
    }

    // 流量充值
    public function recharge(){
        $url = HuYiClass::HUYI_URL."/f/v2?action=recharge";
        $timestamp = date("YmdHis");
        $body = array(
            "username" => $this->appid,
            "mobile" => $this->mobile,
            "package" => (string)$this->package,
            "orderid" => $this->orderid,
            "timestamp" =>$timestamp,
        );
        $body['sign'] = $this->getSign($body);
        $result = CurlUtil::post($url,$body);
        $ret = json_decode($result['content'],true);
        $this->returnDetail = $ret;
        if($ret['code'] == 1){
            return HuYiClass::SUCCESS;
        }else{
            return HuYiClass::FAIL;
        }
    }
    // 余额查询
    public function balance(){
        $url = HuYiClass::HUYI_URL."/f/v2?action=getbalance";
        $timestamp = date("YmdHis");
        $body = array(
            "username" => $this->appid,
            "timestamp" =>$timestamp,
        );
        $body['sign'] = $this->getSign($body);
        $result = CurlUtil::post($url,$body);
        $ret = json_decode($result['content'],true);
        $this->returnDetail = $ret;
        if($ret['code'] == 1){
            return HuYiClass::SUCCESS;
        }else{
            return HuYiClass::FAIL;
        }
    }
    // 订单信息查询
    public function getOrderInfo(){
        $url = HuYiClass::HUYI_URL."/f/v2?action=getorderinfo";
        $timestamp = date("YmdHis");
        $body = array(
            "username" => $this->appid,
            "orderid" => $this->orderid,
            "timestamp" =>$timestamp,
        );
        $body['sign'] = $this->getSign($body);
        $result = CurlUtil::post($url,$body);
        $ret = json_decode($result['content'],true);
        return $ret;
    }
    // 获取流量包档位
    public function getPackages(){
        $url = HuYiClass::HUYI_URL."/f/v2?action=getpackages";
        $timestamp = date("YmdHis");
        $body = array(
            "username" => $this->appid,
            "timestamp" =>$timestamp,
        );
        $body['sign'] = $this->getSign($body);
        $result = CurlUtil::post($url,$body);
        $ret = json_decode($result['content'],true);
        return $ret;
    }
    public function getReturnDetail(){
        return $this->returnDetail;
    }

    public function getSign($data){
        if(!is_array($data)){
            return false;
        }
        $data['apikey'] = $this->apikey;
        ksort($data);
        $signArr = [];
        foreach ($data as $k => $v) {
           $signArr[] = $k."=".$v;
        }
        $sign = implode("&", $signArr);
        return md5($sign);
    }

}
