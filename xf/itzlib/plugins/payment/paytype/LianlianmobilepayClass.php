<?php
include_once('LianlianBase.php');
class LianlianmobilepayClass extends LianlianBase{
    public $description = "连连手机支付";
    

    public function buildBindForm(&$formData){
        
        $buildformData = array(); 
        
        if($this->debug) {// 测试入口
            $submitUrl = self::$qarequestHost.'/llpayh5/signApply.htm';
        } else {// 正式入口
            $submitUrl = self::$requestHost.'/llpayh5/signApply.htm';
        }
        $post_data = array(
            //"version"      => "1.0",
            "oid_partner"  => $formData['memberID'],
//            "user_id"      => substr($formData["tradeNo"],9),
            'user_id'      => $formData['user_id'],
            "busi_partner" => "101001",
            "no_order"     => $formData["tradeNo"],
            //"timestamp"    => date("YmdHis"),
            "dt_order"     => date("YmdHis"),
            //"name_goods" =>  "",
            //"info_order" =>  "",
            "money_order"  => strval(round($formData["orderAmount"], 2)),
            "notify_url"   => $formData['notifyUrl'],
            //"url_return"   => $formData['returnUrl'],
            //"userreq_ip"   => $formData['clientIp'],
            //"url_order"  => "",
            "valid_order"=> '10080',
            'info_order' => '',
            //"bank_code"    => $formData["bankCode"],
            "pay_type"   => '2',
            //"risk_item"  => ""
            "no_agree"   => $formData['no_agree'],
            "partner_sign_type"    => "MD5", 
        );
        
        $post_data_order = array(
            "oid_partner"  => $post_data['oid_partner'],
            "busi_partner" => $post_data['busi_partner'],
            "no_order"     => $post_data['no_order'],
            "dt_order"     => $post_data['dt_order'],
            "money_order"  => $post_data['money_order'],
            "notify_url"   => $post_data['notify_url'],
            "valid_order"=> $post_data['valid_order'],
            'info_order' => $post_data['info_order'],
            "sign_type"    => $post_data['partner_sign_type'],
        );
        ksort($post_data_order);
        reset($post_data_order);

        $param = '';
        foreach ($post_data_order AS $key => $val){
            if($val){
                 $param .= "$key=" .$val. "&";
            }
        }
        $param    = substr($param, 0, -1). '&key=' . $formData['privateKey'];
        $post_data["sign"]    = md5($param);    //签名字符串 不可空
        return $post_data;
    }

    
    /**
     * 移动端充值操作
     * @param type $formData
     * @return type
     */
    public function mobileForm(&$formData){
		
		$buildformData = array();
        $post_data = array(
            //"version"      => "1.0",
            "oid_partner"  => $formData['memberID'],
//            "user_id"      => substr($formData["tradeNo"],9),
            'user_id'      => $formData['user_id'],
            'id_no'        => $formData['id_no'],
            'acct_name'    => $formData['realname'],
            "busi_partner" => "101001",
            "no_order"     => $formData["tradeNo"],
            //"timestamp"    => date("YmdHis"),
            "dt_order"     => date("YmdHis"),
//            "name_goods"    => "ITZ充值功能",
            //"info_order" =>  "",
            "money_order"  => strval(round($formData["orderAmount"], 2)),
            "notify_url"   => $formData['notifyUrl'],
            //"url_return"   => $formData['returnUrl'],
            //"userreq_ip"   => $formData['clientIp'],
            //"url_order"  => "",
            "valid_order"=> '10080',
            'info_order' => '',
            //"bank_code"    => $formData["bankCode"],
            "pay_type"   => '2',
            //"risk_item"  => "",
            "no_agree"   => $formData['no_agree'],
            "partner_sign_type"    => "MD5", 
        );
//        $post_data_order = $post_data;
        $post_data_order = array(
            "oid_partner"  => $post_data['oid_partner'],
            "busi_partner" => $post_data['busi_partner'],
            "no_order"     => $post_data['no_order'],
            "dt_order"     => $post_data['dt_order'],
            "money_order"  => $post_data['money_order'],
            "notify_url"   => $post_data['notify_url'],
            "valid_order"=> $post_data['valid_order'],
            'info_order' => $post_data['info_order'],
            "sign_type"    => $post_data['partner_sign_type'],
        );
        ksort($post_data_order);
        reset($post_data_order);

        $param = '';
        foreach ($post_data_order AS $key => $val){
            if($val){
                 $param .= "$key=" .$val. "&";
            }
        }
        $param    = substr($param, 0, -1). '&key=' . $formData['privateKey'];
        $post_data["sign"]    = md5($param);    //签名字符串 不可空
        return $post_data;
	}
    
    
    /**
     * 获取支付通知结果
     * @param array $data
     *      memberID: 商户ID
     *      privateKey: 私钥
     * @return boolean
     */
    public function noticeResult($data) {
        $payResult = false; // 支付结果
         
        if(!isset($data['privateKey']) || empty($data['privateKey'])) {
            $this->_errorInfo = 'data has not privateKey.';
            return false;
        }
        if(!isset($data['signType']) || empty($data['signType'])) {
            $signType = 'MD5';
        } else {
            $signType = $data['signType'];
        }
        // 通知参数
        $noticeData = $this->getNoticeData();
        
        $srcArr = array();
        foreach($noticeData as $key => $value) {
            if($key != 'sign' && $value) {
                $srcArr[$key] = $value;
            }
        }
        ksort($srcArr);
        reset($srcArr);
        $src = '';
        foreach ($srcArr AS $key => $val){
            if($val){
                 $src .= "$key=" .$val. "&";
            }
        }
        $src = substr($src, 0, -1). '&key=' . $data['privateKey'];
        
        $verifyResult = false;
        if($signType == 'MD5') {
            $srcSign = md5($src);
            if($srcSign == $noticeData['sign']) {
                $verifyResult = true;
            } else {
                $this->_errorInfo = 'Verify signature is not consistent.';
                $verifyResult = false;
            }
        } elseif($signType == 'RSA') {
            $this->_errorInfo = 'RSA signtype has not supported.';
            $verifyResult = false;
        } else {
            $this->_errorInfo = $signType.' signtype is not supported ';
            $verifyResult = false;
        }
        
        if($verifyResult) {
            if ($noticeData["result_pay"] == 'SUCCESS'){
                $this->noticeSuccessCode = json_encode(array("ret_code" => "0000","ret_msg" => "交易成功"));
                $payResult = true;
            } else{
                $payResult = false;
                $this->_errorInfo = $noticeData["result_pay"];
            } 
        } else {
            $payResult = false;
            $this->_errorInfo = 'Verify signature is not consistent.';
        }
        if($this->_errorInfo) Yii::log("thirdpay error info :lianlianmobpay notice : ".$this->_errorInfo." noticedata :".print_r($noticeData,true)
        ." src :".$src." md5 src:".md5($src),"error");
        return $payResult;
    }
    
    /**
     * 获取支付回调结果
     * @param array $data
     *      memberID: 商户ID
     *      privateKey: 私钥
     * @return boolean
     */
    public function returnResult($data) {
        $payResult = false; // 支付结果
        if(!isset($data['privateKey']) || empty($data['privateKey'])) {
            $this->_errorInfo = 'data has not privateKey.';
            return false;
        }
        if(!isset($data['signType']) || empty($data['signType'])) {
            $signType = 'MD5';
        } else {
            $signType = $data['signType'];
        }
        
        $noticeData = $this->getReturnData();
        
        $srcArr = array();
        foreach($noticeData as $key => $value) {
            if($key != 'sign' && $value) {
                $srcArr[$key] = $value;
            }
        }
        ksort($srcArr);
        reset($srcArr);
        $src = '';
        foreach ($srcArr AS $key => $val){
            if($val){
                 $src .= "$key=" .$val. "&";
            }
        }
        $src = substr($src, 0, -1). '&key=' . $data['privateKey'];
        
        $verifyResult = false;
        if($signType == 'MD5') {
            $srcSign = md5($src);
            if($srcSign == $noticeData['sign']) {
                $verifyResult = true;
            } else {
                $this->_errorInfo = 'Verify signature is not consistent.';
                $verifyResult = false;
            }
        } elseif($signType == 'RSA') {
            $this->_errorInfo = 'RSA signtype has not supported.';
            $verifyResult = false;
        } else {
            $this->_errorInfo = $signType.' signtype is not supported ';
            $verifyResult = false;
        }
        
        if($verifyResult) {
            if ($noticeData["result_pay"] == 'SUCCESS'){
                $payResult = true;
            } else{
                $payResult = false;
                $this->_errorInfo = $noticeData["result_pay"];
            } 
        } else {
            $payResult = false;
            $this->_errorInfo = 'Verify signature is not consistent.';
        }
        if($this->_errorInfo) Yii::log("thirdpay error info :lianlianmobile return".$this->_errorInfo." noticedata :".print_r($noticeData,true)
        ." src :".$src." md5 src:".md5($src),"error");
        return $payResult;
    }
    
    /**
     * 获取支付通知参数
     * @return boolean
     */
    public function &getNoticeData() {
        if(!empty($this->_noticeData)) {
            return $this->_noticeData;
        }
        $this->_noticeData = array();
        
        $str = file_get_contents("php://input");
        Yii::trace($str);
        $val = json_decode($str,1);
        Yii::trace(print_r($val,true));
        $this->_noticeData['oid_partner'] = $val["oid_partner"];
        $this->_noticeData['sign_type']   = $val["sign_type"];
        $this->_noticeData['sign']        = $val["sign"];
        $this->_noticeData['dt_order']    = $val["dt_order"];
        $this->_noticeData['no_order']    = $val["no_order"];
        $this->_noticeData['oid_paybill'] = $val["oid_paybill"];
        $this->_noticeData['money_order'] = $val["money_order"];
        $this->_noticeData['result_pay']  = $val["result_pay"];
        $this->_noticeData['settle_date'] = isset($val["settle_date"])?$val["settle_date"]:"";
        $this->_noticeData['info_order']  = isset($val["info_order"])?$val["info_order"]:"";
        $this->_noticeData['pay_type']    = isset($val["pay_type"])?$val["pay_type"]:"";
        $this->_noticeData['bank_code']   = isset($val["bank_code"])?$val["bank_code"]:"";
        return $this->_noticeData;
    }

    public function getErrorInfo() {
        return $this->_errorInfo;
    }
    
    public function getNoticeSuccessCode(){
        return $this->noticeSuccessCode; 
    }
    
}
