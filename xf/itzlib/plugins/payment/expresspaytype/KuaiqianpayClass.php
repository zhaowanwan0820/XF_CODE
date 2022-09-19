<?php
/**
 * 快钱
 */
Yii::import('itzlib.plugins.payment.AbstractExpressPaymentClass');//引如抽象类
Yii::import('itzlib.plugins.payment.interface.*');//引入接口类
class KuaiqianpayClass extends  AbstractExpressPaymentClass implements ApiInterface{
    protected $paymentNid= 'kuaiqianpay';//在ITZ的nid

    #商户号
    protected $merchantId   = "";

    #终端号码
    protected $terminalId   = "";

    #商户密码
    protected $certPas = "";

    #key
    protected $certFileName = "/itzlib/plugins/payment/include/kuaiqianpay/81231006011007090.pem";

    #key 快钱回调使用
    protected static $cerFileName = "/itzlib/plugins/payment/include/kuaiqianpay/vposPHP.cer";

    #模拟客户端 浏览器
    protected $USERAGENT    = 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';

	public function __construct() {
        $this->certFileName = WWW_DIR.$this->certFileName;

        #获取配置信息
        $this->getPaymentConfig();

        #商户号
        $this->merchantId   = $this->config['merchantId'];

        #商户密码
        $this->certPas = $this->config['certPassword'];

        #终端号码
        $this->terminalId   = $this->config['terminalId'];
    }

    #获取商户信息
    protected function getPaymentConfig(){
        if(empty($this->config)){            
            $paymentRecord = Payment::model()->findByAttributes(array('nid' => $this->paymentNid));
            if(empty($paymentRecord)){
                Yii::log('yeepay GetInfoByNid error:'.$this->paymentNid,'error');
            }else{
                $this->config = unserialize($paymentRecord->config);
            }
        }
    }

    public function rechargeVerify($data,$ItzSafeCard,$userInfo){


        #获得交易信息
        $recharge=AccountRecharge::model()->findByAttributes(array('user_id'=>$data['user_id'],'trade_no'=>$data['trade_no']));
        if(empty($recharge['api_once_return']))
        {
            return $this->result(100,'交易信息不完整[AccountRecharge]');
        }
        $api_once_return = json_decode($recharge['api_once_return'],true);
        if(empty($api_once_return['data']))
        {
            return $this->result(100,'交易信息不完整[AccountRecharge]');
        }
		
        #判别通道
        $isChannel = $isChannel = $this->getChannel($data['user_id']);
        $pan = $ItzSafeCard['card_number'];
        $xmlstr = '';
		if($isChannel){ //一键支付
			$xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
			<MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">
			<version>1.0</version>
			<TxnMsgContent>
				<interactiveStatus>TR1</interactiveStatus>
				<txnType>PUR</txnType>
				<merchantId>'.$this->merchantId.'</merchantId>
				<terminalId>'.$this->terminalId.'</terminalId>
				<tr3Url>https://www.xxx.com/newuser/paymentNotify/Kuaiqian</tr3Url>
				<entryTime>'.date("YmdHis").'</entryTime>
				<storableCardNo>'.substr($pan,0,6).substr($pan,-4,4).'</storableCardNo>
				<amount>'.$recharge['money'].'</amount>
				<externalRefNumber>'.$data['trade_no'].'</externalRefNumber>
				<customerId>'.$userInfo['hash_id'].'</customerId>
				<spFlag>QuickPay</spFlag>
				<extMap>
					<extDate><key>validCode</key><value>'.$data['verify_code'].'</value></extDate>
					<extDate><key>savePciFlag</key><value>0</value></extDate>
					<extDate><key>token</key><value>'.$api_once_return['data']['TOKEN'].'</value></extDate>
					<extDate><key>payBatch</key><value>2</value></extDate>
				</extMap>
			</TxnMsgContent>
			</MasMessage>';
		}else{// 消费鉴权
	        $xmlstr = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
	        $xmlstr.= "<MasMessage xmlns=\"http://www.99bill.com/mas_cnp_merchant_interface\">";
	        $xmlstr.= "<version>1.0</version>";
	        $xmlstr.= "<TxnMsgContent>";
	            $xmlstr.= "<txnType>PUR</txnType>";
	            $xmlstr.= "<interactiveStatus>TR1</interactiveStatus>";
	            $xmlstr.= "<cardNo>".$ItzSafeCard['card_number']."</cardNo>";
	            $xmlstr.= "<amount>".$recharge['money']."</amount>";
	            $xmlstr.= "<merchantId>".$this->merchantId."</merchantId>";
	            $xmlstr.= "<terminalId>".$this->terminalId."</terminalId>";
	            $xmlstr.= "<entryTime>".date("YmdHis")."</entryTime>";
	            $xmlstr.= "<externalRefNumber>".$data['trade_no']."</externalRefNumber>";
	            $xmlstr.= "<customerId>".$userInfo['hash_id']."</customerId>";
	            $xmlstr.= "<cardHolderName>".$userInfo['realname']."</cardHolderName>";
	            $xmlstr.= "<cardHolderId>".strtoupper($userInfo['card_id'])."</cardHolderId>";
	            $xmlstr.= "<idType>0</idType>";
	            $xmlstr.= "<spFlag>QuickPay</spFlag>";#QPay01 QPay02
	            $xmlstr.= "<tr3Url>https://www.xxx.com/newuser/paymentNotify/Kuaiqian</tr3Url>";
	            $xmlstr.= "<extMap>";
	            $xmlstr.= "<extDate><key>phone</key><value>".$ItzSafeCard['phone']."</value></extDate>";
	            $xmlstr.= "<extDate><key>validCode</key><value>".$data['verify_code']."</value></extDate>";
	            $xmlstr.= "<extDate><key>savePciFlag</key><value>1</value></extDate>";#是否保存鉴权信息
	            $xmlstr.= "<extDate><key>token</key><value>".$api_once_return['data']['TOKEN']."</value></extDate>";
	            $xmlstr.= "<extDate><key>payBatch</key><value>1</value></extDate>";#快捷支付批次
	            $xmlstr.= "</extMap>";
	        $xmlstr.= "</TxnMsgContent>";
	        $xmlstr.= "</MasMessage>";
        
		}
        
        $info = $this->sendTr1('https://mas.99bill.com/cnp/purchase',$xmlstr);

        if($info['RESPONSECODE']=='00')
        {
            return $this->result(0,'',array(
                'data'      =>$info,
                'send'      =>FunctionUtil::parse_xml($xmlstr),
                'get'       =>$info
                ));
        }
        else
        {
            #第三方服务器返回异常
            
            $code = $info['RESPONSECODE'];
            $info['send']   = FunctionUtil::parse_xml($xmlstr);
            $info['get']    = $info;
            return $this->result($code,'充值失败['.$info['RESPONSECODE'].'-'.$this->errorInfo($info).']',$info );
        }
    }

    #快捷支付手机动态鉴权
    public function recharge($data,$ItzSafeCard,$userInfo){

        $customerId         = $userInfo['hash_id'];#客户id
        $externalRefNumber  = $data['trade_no'];#外部跟踪编号
        $amount             = $data['money'];#金额
        $cardHolderName     = $userInfo['realname'];#持卡人姓名
        $cardHolderId       = strtoupper($userInfo['card_id']);#持卡人身份证号
        $pan                = $ItzSafeCard['card_number'];#卡号
        $phoneNO            = $ItzSafeCard['phone'];#手机号码
        
        #判别通道
        $isChannel = $this->getChannel($userInfo['user_id']);
        #组合 xml 数据
        $xmlstr = '';
        if($isChannel){ //再次充值（一键支付）
	        $xmlstr ='<?xml version="1.0" encoding="UTF-8"?>
			<MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">
			<version>1.0</version>
			<GetDynNumContent>
				<merchantId>'.$this->merchantId.'</merchantId>
				<customerId>'.$customerId.'</customerId>
				<externalRefNumber>'.$externalRefNumber.'</externalRefNumber>
				<storablePan>'.substr($pan,0,6).substr($pan,-4,4).'</storablePan>
				<amount>'.$amount.'</amount>
			</GetDynNumContent>
			</MasMessage>';
        }else { // 首次鉴权
        	$xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
	        <MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">
	        <version>1.0</version>
	        <GetDynNumContent>
	            <merchantId>'.$this->merchantId.'</merchantId>
	            <customerId>'.$customerId.'</customerId>
	            <externalRefNumber>'.$externalRefNumber.'</externalRefNumber>
	            <cardHolderName>'.$cardHolderName.'</cardHolderName>
	            <idType>0</idType>
	            <cardHolderId>'.$cardHolderId.'</cardHolderId>
	            <pan>'.$pan.'</pan>
	            <phoneNO>'.$phoneNO.'</phoneNO>
	            <amount>'.$amount.'</amount>
	        </GetDynNumContent>
	        </MasMessage>';
        	 
        }
        $info = $this->sendTr1('https://mas.99bill.com/cnp/getDynNum',$xmlstr);
        if($info['RESPONSECODE']=='00')
        {
            return $this->result(0,'',array(
                'send'      =>FunctionUtil::parse_xml($xmlstr),
                'get'       =>$info,
                'data'      =>$info
                ));
        }
        else
        {
            return $this->result($info['RESPONSECODE'],'充值失败['.$info['RESPONSECODE'].'-'.$this->errorInfo($info).']',array(
                'send'      =>FunctionUtil::parse_xml($xmlstr),
                'get'       =>$info
                ) );
        }
    }

    #绑卡
    public function bindCard($param,$userInfo){

        $customerId         = $userInfo['hash_id'];#客户id
        $externalRefNumber  = time().($userInfo['user_id']%9).rand(10000,99999);;#外部跟踪编号
        $cardHolderName     = $userInfo['realname'];#持卡人姓名
        $cardHolderId       = strtoupper($userInfo['card_id']);#持卡人身份证号
        $pan                = $param['card'];#卡号
        $phoneNO            = $param['phone'];#手机号码

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">
                    <version>1.0</version>
                    <indAuthContent>
                        <merchantId>'.$this->merchantId.'</merchantId>
                        <terminalId>'.$this->terminalId.'</terminalId>
                        <customerId>'.$customerId.'</customerId>
                        <externalRefNumber>'.$externalRefNumber.'</externalRefNumber>
                        <pan>'.$pan.'</pan>
                        <cardHolderName>'.$cardHolderName.'</cardHolderName>
                        <idType>0</idType>
                        <cardHolderId>'.$cardHolderId.'</cardHolderId>
                        <phoneNO>'.$phoneNO.'</phoneNO>
                    </indAuthContent>
                </MasMessage>';
        $info = $this->sendTr1('https://mas.99bill.com/cnp/ind_auth',$xml);

        if($info['RESPONSECODE']=='00')
        {
            return $this->result(0,$info['RESPONSETEXTMESSAGE'],array(
                'send'      =>FunctionUtil::parse_xml($xml),
                'get'       =>$info,
                'data'      =>$info,
                'bind_no'   =>$info['EXTERNALREFNUMBER']
                ));
        }
        else
        {
            return $this->result($info['RESPONSECODE'],$this->errorInfo($info),array(
                'send'      =>FunctionUtil::parse_xml($xml),
                'get'       =>$info
                ));
        }
    }

    #绑卡
    public function bindVerfy($data){

        #获取用户信息
        $userInfo = User::model()->findByPk($data['user_id']);

        #获得绑卡信息
        $ItzSafeCardExt=ItzSafeCardExpresspayment::model()->find("`bind_token`=:token and user_id=".$data['user_id'],array(':token'=>$data['bind_no']));
        if(empty($ItzSafeCardExt))
        {
            return $this->result(100,'订单失效，请重新绑卡');
        }
        $bind_result = json_decode($ItzSafeCardExt['bind_result'],true);

        #获得银行信息
        $ItzSafeCard=ItzSafeCard::model()->find("`id`='".$ItzSafeCardExt->safe_card_id."'");
        if(empty($ItzSafeCard))
        {
            return $this->result(100,'订单失效，请重新绑卡');
        }

        $customerId         = $userInfo['hash_id'];#客户id
        $cardHolderName     = $userInfo['realname'];#持卡人姓名
        $cardHolderId       = strtoupper($userInfo['card_id']);#持卡人身份证号
        $pan                = $ItzSafeCard['card_number'];#卡号
        $phoneNO            = $ItzSafeCard['phone'];#手机号码
        $externalRefNumber  = $data['bind_no'];#外部跟踪编号

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">
                <version>1.0</version>
                <indAuthDynVerifyContent>
                    <merchantId>'.$this->merchantId.'</merchantId>
                    <customerId>'.$customerId.'</customerId>
                    <externalRefNumber>'.$externalRefNumber.'</externalRefNumber>
                    <pan>'.$pan.'</pan>
                    <phoneNO>'.$phoneNO.'</phoneNO>
                    <validCode>'.$data['verify_code'].'</validCode>
                    <token>'.$bind_result['TOKEN'].'</token>
                </indAuthDynVerifyContent>
                </MasMessage>';
        $info = $this->sendTr1('https://mas.99bill.com/cnp/ind_auth_verify',$xml);
        if($info['RESPONSECODE']=='00')
        {
            return $this->result(0,$info['RESPONSETEXTMESSAGE'],array(
                'data'      =>$info,
                'bind_no'   =>$info['EXTERNALREFNUMBER'],
                'send'      =>FunctionUtil::parse_xml($xml),
                'get'       =>$info
                ));
        }
        else
        {
            return $this->result($info['RESPONSECODE'],$this->errorInfo($info),array(
                'send'      =>FunctionUtil::parse_xml($xml),
                'get'       =>$info
                ));
        }
    }

    #查绑卡信息
    public function bindCardGet($customerId){
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">
        <version>1.0</version>
        <PciQueryContent>
            <merchantId>'.$this->merchantId.'</merchantId>
            <customerId>'.$customerId.'</customerId>
            <cardType>0002</cardType>
        </PciQueryContent>
        </MasMessage>';
        return $this->sendTr1('https://mas.99bill.com/cnp/pci_query',$xml);

    }

    #发送信息
    function sendTr1( $url, $reqXml ){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
        curl_setopt($ch, CURLOPT_USERAGENT,$this->USERAGENT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_CAINFO, $this->certFileName);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certFileName);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certPas);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqXml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(	"Authorization: Basic " . base64_encode($this->merchantId.":".$this->certPas)));

        $tr2Xml=curl_exec($ch);

        if (curl_error($ch))
        {
            return array(
                'RESPONSECODE'          =>68,
                'RESPONSETEXTMESSAGE'   =>'系统繁忙，请稍后再试[快钱]'
                );
        }
        curl_close ($ch);

        if($tr2Xml)
        {
            return $this->parse_xml($tr2Xml);
        }
        else
        {
            return array(
                'RESPONSECODE'          =>68,
                'RESPONSETEXTMESSAGE'   =>'系统繁忙，请稍后再试[快钱]'
                );
        }
    }

    #解析xml 文档
    function parse_xml($str)
    {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $str, $vals, $index);
        xml_parser_free($p);
        $result = array();
        if($vals)
        {
            foreach($vals as $v)
            {
                if(isset($v['value']))
                {
                    $result[$v['tag']] = $v['value'];
                }
            }
        }
        return $result;
    }

    #绑卡并充值
    public function bindAndRecharge($data,$safeCard,$userInfo){
        return $this->recharge($data,$safeCard,$userInfo);
    }
    
    #绑卡并充值确认
    public function bindAndRechargeVerify($data,$safeCard,$userInfo){
        return $this->rechargeVerify($data,$safeCard,$userInfo);
    }
    
    #绑卡并充值_重新发送验证码
    public function bindAndRechargeSms($data,$safeCard,$userInfo){

        #获得绑卡信息
        $AccountRecharge=AccountRecharge::model()->find("`trade_no`='".$data['trade_no']."'");
        if($AccountRecharge)
        {
            $data['money'] = $AccountRecharge->money;
            $this->recharge($data,$safeCard,$userInfo);
        }
        return $this->result(0,'发送成功',array('send'=>'','get'=>''));
    }

    #充值_重新发送验证码
    public function rechargeSms($data,$safeCard,$userInfo){
        return $this->bindAndRechargeSms($data,$safeCard,$userInfo);
    }

    //解绑确认
    public function bankCardUnbind($data,$userInfo,$safe_card){

        $customerId = $userInfo['hash_id'];#客户id
        $bank = $this->bindCardGet($customerId);

        $pan = $safe_card['card_number'];#卡号

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">
                <version>1.0</version>
                <PciDeleteContent>
                    <merchantId>'.$this->merchantId.'</merchantId>
                    <customerId>'.$customerId.'</customerId>
                    <pan>'.$pan.'</pan>
                    <bankId>'.$bank['BANKID'].'</bankId>
                    <storablePan>'.substr($pan,0,6).substr($pan,-4,4).'</storablePan>
                </PciDeleteContent>
                </MasMessage>';
        $info = $this->sendTr1('https://mas.99bill.com/cnp/pci_del',$xml);
        if($info['RESPONSECODE']=='00')
        {
            return $this->result(0,$info['RESPONSETEXTMESSAGE'],array('data'=>$info));
        }
        else
        {
            return $this->result($info['RESPONSECODE'],$this->errorInfo($info));
        }

    }

    //获取支付通知结果
    public function noticeResult($data){}
    
    //获取支付回调结果
    public function returnResult($data){}
    
    //获取支付通知参数
    public function getNoticeData(){}

    //传入请求地址函数，用于向指定url传入数据
    public function request($path, $params,$json = true){}

    #返回结果
    public function result( $code, $msg, $data = array()){
    	if($code && $code!='100'){ // 替换msg
    		$exception = array('C0','68');
    		$info = ReturnService::getInstance()->getReturn('kuaiqianpay',$code);
    		$msg = empty($info) ? $msg : $info;
    		
    		if(in_array($code, $exception)){
    			$code = 111; #系统处理中
    		}else{
    			$code = 100;
    		}
    	}
        return array(
            'code'  =>$code,
            'msg'   =>$msg,
            'data'  =>$data
            );
    }

    #用户签约信息查询API接口
    public function getBindCrasList($info,$userInfo) {

        #快捷卡信息
        $cardInfo = ItzSafeCard::model()->findByAttributes(array('user_id'=>$userInfo['user_id']));
        if(empty($cardInfo))
        {
            return array();
        }
        
        #获取银行信息
        $bank = ItzBank::model()->findByAttributes(array("bank_id"=>$cardInfo['bank_id']));
        if(empty($bank['bank_name']))
        {
            return array();
        }

        $bank_list = array();

        $r = $this->bindCardGet($userInfo['hash_id']);
        if(!empty($r['STORABLEPAN']))
        {
            $bank_list[] = array(
                'channel_name'  =>'快钱支付',
                'bank_name'     =>$r['BANKID'].'（'.$bank['bank_name'].'）',
                'card_no'       =>substr($r['STORABLEPAN'],0,6).'****'.substr($r['STORABLEPAN'],6),
                'no_agree'      =>'无',
                'tel'           =>$r['SHORTPHONENO']
                );
        }
        return $bank_list;
    }
    public function errorInfo($info){
        $msg = '';
        if(!empty($info['RESPONSETEXTMESSAGE']))
        {
            $msg = $info['RESPONSETEXTMESSAGE'];
        }
        elseif(!empty($info['ERRORMESSAGE']))
        {
            $msg = $info['ERRORMESSAGE'];
        }
        return $msg;
    }

    public static function Rsaverify($data, $sign) {

        //读取支付公钥文件
        $pubKey = file_get_contents(WWW_DIR.self::$cerFileName);

        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA1);
        
        //释放资源
        openssl_free_key($res);

        //返回资源是否成功
        return $result;
    }

    #查询订单状态
    public function orderInfoQuery($recharge){
        $xmlstr = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">
                    <version>1.0</version>
                    <QryTxnMsgContent>
                        <externalRefNumber>'.$recharge->trade_no.'</externalRefNumber>
                        <txnType>PUR</txnType>
                        <merchantId>'.$this->merchantId.'</merchantId>
                        <terminalId>'.$this->terminalId.'</terminalId>
                    </QryTxnMsgContent>
                </MasMessage>';
        $info = $this->sendTr1('https://mas.99bill.com/cnp/query_txn',$xmlstr);
        if($info['TXNSTATUS']=='S')
        {
            return array('code'=>0,'msg'=>'','data'=>array());
        }
        else
        {
            $msg = '';
            if(isset($info['ERRORCODE']))
            {
                $msg = $info['ERRORCODE'].$info['ERRORMESSAGE'];
            }
            return array('code'=>1,'msg'=>$msg,'data'=>array());
        }
    }
    
    public function getChannel($user_id=0){
    	$user_id = intval($user_id);
    	if(empty($user_id)){
    		return 0;
    	}
    	$isBindCard = ItzSafeCardExpresspayment::model()->findByAttributes(array('user_id'=>$user_id,'state'=>'2','nid'=>'kuaiqianpay'));
    	if($isBindCard){
    		//查询一键支付通道是否开启
    		$channl = ItzExpresspaymentBanklimit::model()->findByAttributes(array('type_id'=>1,'status'=>1));
    		if($channl){
    			return 1;
    		}else {
    			return 0;
    		}
    	}
    	return 0;
    }
    
}
