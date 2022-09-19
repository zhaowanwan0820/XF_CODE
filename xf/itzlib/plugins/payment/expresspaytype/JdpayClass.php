<?php
/**
 * 京东
 */
Yii::import('itzlib.plugins.payment.AbstractExpressPaymentClass');//引如抽象类
Yii::import('itzlib.plugins.payment.interface.*');//引入接口类

class JdpayClass extends  AbstractExpressPaymentClass implements ApiInterface{
    protected $paymentNid= 'jdpay';

    #京东接口地址
    protected $requse_url = 'https://quick.chinabank.com.cn/express.htm'; 

    #ITZ异步回调地址
    protected $itz_url = 'https://www.xxx.com/newuser/paymentNotify/Jd';

    function __construct(){
        //版本号
 		$this->version='1.0.0';
 		//终端号
 		$this->terminal= '00000001';

        $this->getPaymentConfig();
    }

    #获取商户信息
    protected function getPaymentConfig(){
        if(empty($this->merchant)){            
            $paymentRecord = Payment::model()->findByAttributes(array('nid' => $this->paymentNid));
            if(empty($paymentRecord)){
                Yii::log('ebatong GetInfoByNid error:'.$this->paymentNid,'error');
            }else{
                $config = unserialize($paymentRecord->config);

                //商户号
                $this->merchant=$config['merchant'];
                //DES密钥
                $this->des = $config['des'];
                //md5密钥
                $this->md5=$config['md5'];
            }
        }
    }

    #失败
    public function returnError($msg,$data = array()){
        return array(
            'code'  =>100,
            'msg'   =>$msg,
            'data'  =>$data
            );
    }

    #成功
    public function returnSuccess($data){
        return array(
            'code'  =>0,
            'msg'   =>'操作成功',
            'data'  =>$data
            );
    }

    #绑卡并充值
    public function bindAndRecharge($data,$safeCard,$userInfo){
        return $this->recharge($data,$safeCard,$userInfo);
    }

    #充值
    public function recharge($data,$cardInfo,$userInfo){

        $card_bank = $this->getBankCode($cardInfo['card_number']);#银行编码
        $card_type = 'D';#卡类型（信用卡：C借记卡：D）
        $card_no = $cardInfo['card_number'];#卡号
        $card_exp = '';#信用卡相关
        $card_cvv2 = '';#信用卡相关
        $card_name = $userInfo['realname'];#持卡人姓名
        $card_idtype = 'I';#持卡人证件类型(I:身份证)
        $card_idno = $userInfo['card_id'];#持卡人证件号码
        $card_phone = $cardInfo['phone'];#持卡人电话号码
        $trade_type = 'V';#交易类型
        $trade_id = $data['trade_no'];#交易ID
        $trade_amount = round($data['money']*100);#金额 单位 分
        $trade_currency = 'CNY';#货币类型

        $data_xml = $this->v_data_xml_create($card_bank,$card_type,$card_no,$card_exp,$card_cvv2,$card_name,$card_idtype,$card_idno,$card_phone,$trade_type,$trade_id,$trade_amount,$trade_currency);

        //发起交易至快捷支付
        $resp = $this->trade($data_xml);
        $resp['send'] = $data_xml;
        $resp['get']  = $resp;
        if($resp['CODE']=='0000')
        {
            return $this->returnSuccess($resp);
        }
        else
        {
            return $this->returnError($resp['DESC'],$resp);
        }
    }

    #绑卡并充值确认
    public function bindAndRechargeVerify($data,$safeCard,$userInfo){
        return $this->rechargeVerify($data,$safeCard,$userInfo);
    }

    #充值确认
    public function rechargeVerify($data,$cardInfo,$userInfo){

        #交易信息
        $recharge=AccountRecharge::model()->findByAttributes(array('user_id'=>$data['user_id'],'trade_no'=>$data['trade_no']));

        //收集index_v.php请求参数
        $card_bank = $this->getBankCode($cardInfo['card_number']);#银行编码
        $card_type = 'D';#卡类型（信用卡：C借记卡：D）
        $card_no = $cardInfo['card_number'];#卡号
        $card_exp = '';#信用卡相关
        $card_cvv2 = '';#信用卡相关
        $card_name = $userInfo['realname'];#持卡人姓名
        $card_idtype = 'I';#持卡人证件类型(I:身份证)
        $card_idno = $userInfo['card_id'];#持卡人证件号码
        $card_phone = $cardInfo['phone'];#持卡人电话号码
        $trade_type = 'S';#交易类型
        $trade_id = $data['trade_no'];#交易ID
        $trade_amount = round($recharge['money']*100);#金额 单位 分
        $trade_currency = 'CNY';#货币类型
        $trade_date = date('Ymd');#日期
        $trade_time = date('His');#时间
        $trade_notice = $this->itz_url;#通知地址（如果填写，则异步发送结果通知到指定地址）
        $trade_note = '充值';#备注
        $trade_code = $data['verify_code'];#验证码

        $data_xml = $this->s_data_xml_create($card_bank,$card_type,$card_no,
                                        $card_exp,$card_cvv2,$card_name,
                                        $card_idtype,$card_idno,$card_phone,
                                        $trade_type,$trade_id,$trade_amount,
                                        $trade_currency,$trade_date,$trade_time,
                                        $trade_notice,$trade_note,$trade_code);

        //发起交易至快捷支付
        $resp = $this->trade($data_xml);

        $resp['send'] = $data_xml;
        $resp['get']  = $resp;
        if($resp['CODE']=='0000' && $resp['STATUS']=='0')
        {
            return $this->returnSuccess($resp);
        }
        else
        {
            return $this->returnError($resp['DESC'],$resp);
        }
    }
    
    #绑卡并充值_重新发送验证码
    public function bindAndRechargeSms($data,$safeCard,$userInfo){
        return $this->rechargeSms($data,$safeCard,$userInfo);
    }


    #充值_重新发送验证码
    public function rechargeSms($data,$safeCard,$userInfo){

        #交易信息
        $recharge=AccountRecharge::model()->findByAttributes(array('user_id'=>$data['user_id'],'trade_no'=>$data['trade_no']));
        $data['money'] = $recharge['money'];
        return $this->recharge($data,$safeCard,$userInfo);
    }

    //获取支付通知参数
    public function getNoticeData(){
    }
    
    //传入请求地址函数，用于向指定url传入数据
    public function request($path, $data){
    }   
    

    //用户绑卡
    public function bindCard($data,$userInfo){
        return $this->returnError('京东支付不支持绑卡');
    }
    
    //绑卡确认
    public function bindVerfy($data){
        return $this->returnError('京东支付不支持绑卡');
    }
    
    //解绑
    public function bankCardUnbind($data,$userInfo,$safe_card){
        return $this->returnError('京东支付不支持绑卡');
    }


    //获取支付通知结果
    public function noticeResult($data){}
    
    //获取支付回调结果
    public function returnResult($data){}

	/**
	 * 发起快捷支付方法
	 * @param $data_xml交易的xml格式数据
	 */
	function trade($data_xml){
		//把data元素des加密
		$desObj = new DES($this->des);
		$dataDES = $desObj->encrypt($data_xml);
		$sign = $this->myMd5($this->version.$this->merchant.$this->terminal.$dataDES,$this->md5);
		$xml = $this->xml_create($this->version,$this->merchant,$this->terminal,$dataDES,$sign);
		//使用方法
		$param ='charset=UTF-8&req='.urlencode(base64_encode($xml));
		$resp = $this->post($param);
        return $this->operate($resp);
	}
	/**
	 * @param $resp 网银在线返回的数据
	 * 数据的解析步骤：
	 * 1：截取resp=后面的xml数据
	 * 2: base64解码
	 * 3: 验证签名
	 * 4: 解析交易数据处理自己的业务逻辑
	 */
	function operate($resp){

        $Errors = $this->getError();
        $xs = substr($resp,5);
        if(isset($Errors[$xs]))
        {
            return array('CODE'=>'1111','DESC'=>$Errors[$xs]);
        }
		$temResp = base64_decode($xs);
		$xml = simplexml_load_string($temResp);
		//验证签名, version.merchant.terminal.data
		$text = $xml->VERSION.$xml->MERCHANT.$xml->TERMINAL.$xml->DATA;


		if(!$this->md5_verify($text,$this->md5,$xml->SIGN)){
            return array('CODE'=>'1111','DESC'=>'SIGN没通过验证');
		}


		//des密钥要网银在线后台设置
		$des = new DES($this->des);
		$decodedXML = $des->decrypt($xml->DATA);

		$dataXml = simplexml_load_string($decodedXML);
        return $this->parse_xml($dataXml->asXML());
	}
    function getError(){
        return array(
        '0001'=>'处理中',
        'EEE0001'=>'系统异常',
        'EEE0002'=>'网络异常',
        'EEE0003'=>'银行异常',
        'EEE0004'=>'数据库异常',
        'EES0001'=>'报文解析异常',
        'EES0002'=>'字符集不正确',
        'EES0003'=>'版本号不正确',
        'EES0004'=>'商户号不正确',
        'EES0005'=>'终端号不正确',
        'EES0006'=>'交易数据不正确',
        'EES0007'=>'数据签名不正确',
        'EES0008'=>'权限不正确',
        'EES0009'=>'密钥不正确',
        'EES0010'=>'发卡行不正确',
        'EES0011'=>'卡类型不正确',
        'EES0012'=>'交易卡号不正确',
        'EES0013'=>'卡有效期不正确',
        'EES0014'=>'卡安全码不正确',
        'EES0015'=>'持卡人姓名不正确',
        'EES0016'=>'持卡人证件类型不正确',
        'EES0017'=>'持卡人证件号不正确',
        'EES0018'=>'持卡人手机号不正确',
        'EES0019'=>'交易类型不正确',
        'EES0020'=>'交易号不正确',
        'EES0021'=>'交易金额不正确',
        'EES0022'=>'交易币种不正确',
        'EES0023'=>'交易日期不正确',
        'EES0024'=>'交易时间不正确',
        'EES0025'=>'交易通知地址不正确',
        'EES0026'=>'交易备注不正确',
        'EES0027'=>'交易验证码不正确',
        'EES0028'=>'交易卡号网银不受理',
        'EES0029'=>'交易卡号商户不受理',
        'EES0030'=>'交易受理银行繁忙',
        'EES0031'=>'交易受理渠道繁忙',
        'EES0032'=>'交易重复',
        'EES0033'=>'交易号重复',
        'EES0034'=>'交易验证码申请不受理',
        'EES0035'=>'交易验证码过期',
        'EES0036'=>'交易不存在',
        'EES0037'=>'原交易号不正确',
        'EES0038'=>'原交易不允许此操作',
        'EES0039'=>'原交易处理中',
        'EES0040'=>'退款余额不足',
        'EES0041'=>'查询银行列表错误',
        'EES0042'=>'找不到相应的银行列表信息',
        'EES0043'=>'卡号未签约',
        'EES0044'=>'卡号未做签约申请',
        'EEB0001'=>'银行交易不支持',
        'EEB0002'=>'银行签约失败',
        'EEB0003'=>'银行解约失败',
        'EEB0004'=>'银行交易失败',
        'EEB0005'=>'银行签约姓名校验失败',
        'EEB0006'=>'银行签约手机号校验失败',
        'EEB0007'=>'银行签约证件号校验失败',
        'EEB0008'=>'银行签约卡有效期校验失败',
        'EEB0009'=>'银行签约卡安全码校验失败',
        'EEB0010'=>'银行不支持的卡类型',
        'EEB0011'=>'银行不支持的卡号',
        'EEB0012'=>'银行卡号状态异常',
        'EEB0013'=>'银行卡号未开通快捷业务',
        'EEB0014'=>'银行卡号余额不足',
        'EEB0015'=>'银行单笔金额超限',
        'EEB0016'=>'银行日交易金额超限',
        'EEB0017'=>'银行日交易次数超限',
        'EER0001'=>'风险校验失败'
        );
    }
	function post($param){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$this->requse_url);
		curl_setopt($ch, CURLOPT_PORT, 443);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //信任任何证书
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名,0不验证
		//curl_setopt($ch, CURLOPT_VERBOSE, 1); //debug模式
//			curl_setopt($ch, CURLOPT_SSLCERT, dirname(__FILE__).'/quick.cer'); //client.crt文件路径
		//curl_setopt($ch, CURLOPT_SSLCERTPASSWD, ""); //client证书密码
		//curl_setopt($ch, CURLOPT_SSLKEY, "chinabank");
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
		$file_contents = curl_exec($ch); // 执行操作
		curl_close($ch);
	    return $file_contents;
	}
	/**
	 * 发送请求至快捷支付地址
	 * 只支持post方式
	 * 测试时，请确认本地curl环境是否可用
	 * @param 请求参数
	 * 此方法废弃
	 */
	function post1($param){//curl
		$ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $this->requse_url);
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;
	}

	function xml_create($version,$merchant,$terminal,$data,$sign){
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><chinabank/>');
		$xml->addChild('version',$version);
		$xml->addChild('merchant',$merchant);
		$xml->addChild('terminal',$terminal);
		$xml->addChild('data',$data);
		$xml->addChild('sign',$sign);

		return $xml->asXML();
	}

	function v_data_xml_create($card_bank,$card_type,$card_no,
								$card_exp,$card_cvv2,$card_name,
								$card_idtype,$card_idno,$card_phone,
								$trade_type,$trade_id,$trade_amount,$trade_currency){
		$v_data = '<?xml version="1.0" encoding="UTF-8"?>'.
					'<DATA>'.
						'<CARD>'.
							'<BANK>'.$card_bank.'</BANK>'.
							'<TYPE>'.$card_type.'</TYPE>'.
							'<NO>'.$card_no.'</NO>'.
							'<EXP>'.$card_exp.'</EXP>'.
							'<CVV2>'.$card_cvv2.'</CVV2>'.
							'<NAME>'.$card_name.'</NAME>'.
							'<IDTYPE>'.$card_idtype.'</IDTYPE>'.
							'<IDNO>'.$card_idno.'</IDNO>'.
							'<PHONE>'.$card_phone.'</PHONE>'.
						'</CARD>'.
						'<TRADE>'.
							'<TYPE>'.$trade_type.'</TYPE>'.
							'<ID>'.$trade_id.'</ID>'.
							'<AMOUNT>'.$trade_amount.'</AMOUNT>'.
							'<CURRENCY>'.$trade_currency.'</CURRENCY>'.
						'</TRADE>'.
					'</DATA>';
		return $v_data;
	}
	function s_data_xml_create($card_bank,$card_type,$card_no,
								$card_exp,$card_cvv2,$card_name,
								$card_idtype,$card_idno,$card_phone,
								$trade_type,$trade_id,$trade_amount,$trade_currency,
								$trade_date,$trade_time,$trade_notice,$trade_note,$trade_code){
		$v_data = '<?xml version="1.0" encoding="UTF-8"?>'.
					'<DATA>'.
						'<CARD>'.
							'<BANK>'.$card_bank.'</BANK>'.
							'<TYPE>'.$card_type.'</TYPE>'.
							'<NO>'.$card_no.'</NO>'.
							'<EXP>'.$card_exp.'</EXP>'.
							'<CVV2>'.$card_cvv2.'</CVV2>'.
							'<NAME>'.$card_name.'</NAME>'.
							'<IDTYPE>'.$card_idtype.'</IDTYPE>'.
							'<IDNO>'.$card_idno.'</IDNO>'.
							'<PHONE>'.$card_phone.'</PHONE>'.
						'</CARD>'.
						'<TRADE>'.
							'<TYPE>'.$trade_type.'</TYPE>'.
							'<ID>'.$trade_id.'</ID>'.
							'<AMOUNT>'.$trade_amount.'</AMOUNT>'.
							'<CURRENCY>'.$trade_currency.'</CURRENCY>'.
							'<DATE>'.$trade_date.'</DATE>'.
							'<TIME>'.$trade_time.'</TIME>'.
							'<NOTICE>'.$trade_notice.'</NOTICE>'.
							'<NOTE>'.$trade_note.'</NOTE>'.
							'<CODE>'.$trade_code.'</CODE>'.
						'</TRADE>'.
					'</DATA>';
		return $v_data;
	}
	function r_data_xml_create($trade_type,$trade_id,$trade_oid,$trade_amount,
									$trade_currency,$trade_date,$trade_time,$trade_notice,$trade_note){
		$v_data = '<?xml version="1.0" encoding="UTF-8"?>'.
			'<DATA>'.
				'<TRADE>'.
					'<TYPE>'.$trade_type.'</TYPE>'.
					'<ID>'.$trade_id.'</ID>'.
					'<OID>'.$trade_oid.'</OID>'.
					'<AMOUNT>'.$trade_amount.'</AMOUNT>'.
					'<CURRENCY>'.$trade_currency.'</CURRENCY>'.
					'<DATE>'.$trade_date.'</DATE>'.
					'<TIME>'.$trade_time.'</TIME>'.
					'<NOTICE>'.$trade_notice.'</NOTICE>'.
					'<NOTE>'.$trade_note.'</NOTE>'.
				'</TRADE>'.
			'</DATA>';
		return $v_data;
	}
	function q_data_xml_create($trade_type,$trade_id){
		$v_data = '<?xml version="1.0" encoding="UTF-8"?>'.
			'<DATA>'.
				'<TRADE>'.
					'<TYPE>'.$trade_type.'</TYPE>'.
					'<ID>'.$trade_id.'</ID>'.
				'</TRADE>'.
			'</DATA>';
		return $v_data;
	}

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
	/**
	 * md5加密方法
	 */
    function myMd5($text,$key){
		return md5($text.$key);
    }
    /**
     * 验证签名方法
     */
	function md5_verify($text,$key,$md5){
		$md5Text = $this->myMd5($text,$key);
		return $md5Text==$md5;
	}

    #获得银行编码
    public function getBankCode($card_number){

        $factory=new ExpressPaymentFactory;

        #查寻银行卡接口
        $yeepay=$factory->getPayment('yeepay');#ebatong
        $bank_name =$yeepay->bankcardCheck($card_number);

        $bank_code = '';
        if(strpos('.'.$bank_name['bankname'],'华夏'))
        {
            $bank_code = 'HXB';
        }
        if(strpos('.'.$bank_name['bankname'],'交通'))
        {
            $bank_code = 'BCM';
        }
        if(strpos('.'.$bank_name['bankname'],'上海'))
        {
            $bank_code = 'BOS';
        }
        if(strpos('.'.$bank_name['bankname'],'杭州'))
        {
            $bank_code = 'HZB';
        }
        if(strpos('.'.$bank_name['bankname'],'浦发'))
        {
            $bank_code = 'SPDB';
        }
        if(strpos('.'.$bank_name['bankname'],'招'))
        {
            $bank_code = 'CMB';
        }
        if(strpos('.'.$bank_name['bankname'],'工商'))
        {
            $bank_code = 'ICBC';
        }
        if(strpos('.'.$bank_name['bankname'],'农业'))
        {
            $bank_code = 'ABC';
        }
        if(strpos('.'.$bank_name['bankname'],'建设'))
        {
            $bank_code = 'CCB';
        }
        if(strpos('.'.$bank_name['bankname'],'民生'))
        {
            $bank_code = 'CMBC';
        }
        if(strpos('.'.$bank_name['bankname'],'中国银行'))
        {
            $bank_code = 'BOC';
        }
        if(strpos('.'.$bank_name['bankname'],'兴业'))
        {
            $bank_code = 'CIB';
        }
        if(strpos('.'.$bank_name['bankname'],'光大'))
        {
            $bank_code = 'CEB';
        }
        if(strpos('.'.$bank_name['bankname'],'广'))
        {
            $bank_code = 'CGB';
        }
        if(strpos('.'.$bank_name['bankname'],'中信'))
        {
            $bank_code = 'CITIC';
        }
        if(strpos('.'.$bank_name['bankname'],'南京'))
        {
            $bank_code = 'NJCB';
        }
        return $bank_code;
    }

    #用户签约信息查询API接口
    public function getBindCrasList($info,$userInfo) {
        $bank_list = array();
        return $bank_list;
    }
}
class DES {
    var $key;
    function DES($key) {
        //$this->key = $key;
		$this->key = base64_decode($key);

    }
    function encrypt($input) {
        $size = mcrypt_get_block_size('des', 'ecb');
        $input = $this->pkcs5_pad($input, $size);
        $key = $this->key;
        $td = mcrypt_module_open('des', '', 'ecb', '');
        $iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        @mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }
    function decrypt($encrypted) {
        $encrypted = base64_decode($encrypted);
        $key =$this->key;
        $td = mcrypt_module_open('des','','ecb','');
        //使用MCRYPT_DES算法,cbc模式
        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        $ks = mcrypt_enc_get_key_size($td);
        @mcrypt_generic_init($td, $key, $iv);
        //初始处理
        $decrypted = mdecrypt_generic($td, $encrypted);
        //解密
        mcrypt_generic_deinit($td);
        //结束
        mcrypt_module_close($td);
        $y=$this->pkcs5_unpad($decrypted);
        return $y;
    }
    function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    function pkcs5_unpad($text) {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
            return false;
        return substr($text, 0, -1 * $pad);
    }
}