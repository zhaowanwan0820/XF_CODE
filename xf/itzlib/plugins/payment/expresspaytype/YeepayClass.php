<?php
/**
 * 易宝
 */
Yii::import('itzlib.plugins.payment.AbstractExpressPaymentClass');//引如抽象类
Yii::import('itzlib.plugins.payment.interface.*');//引入接口类

class YeepayClass extends  AbstractExpressPaymentClass implements ApiInterface{
    protected $paymentNid= 'yeepay';//在ITZ的nid

	// CURL 参数
	public $http_info;
	public $http_header = array();
	public $http_code;
	public $useragent = 'Yeepay MobilePay PHPSDK v1.1x';
	public $connecttimeout = 30;
	public $timeout = 30;
	public $ssl_verifypeer = FALSE;
	// Yeepay 参数
	private $merchantAccount;
	private $merchartPublicKey;
	private $merchantPrivateKey;
	private $yeepayPublicKey;
	private $bindBankcardURL;
	private $unbindBankcardURL;
	private $confirmBindBankcardURL;
	private $directBindPayURL;
	private $paymentQueryURL;
	private $paymentConfirmURL;
	private $withdrawURL;
	private $queryWithdrawURL;
	private $queryAuthbindListURL;
	private $bankCardCheckURL;
	private $payClearDataURL;
	private $refundURL;
	private $refundQueryURL;
	private $refundClearDataURL;
	// 加密
	private $RSA;
	private $AES;
    private $AESKey;

    #config
    private $config = array();

	public function __construct() {
        #include_once(WWW_DIR . "/itzlib/plugins/payment/include/liandong/mer2Plat.php");
        include_once(WWW_DIR . "/itzlib/plugins/payment/include/yeepay/crypt/Rijndael.php");
        include_once(WWW_DIR . "/itzlib/plugins/payment/include/yeepay/crypt/AES.php");
        include_once(WWW_DIR . "/itzlib/plugins/payment/include/yeepay/crypt/DES.php");
        include_once(WWW_DIR . "/itzlib/plugins/payment/include/yeepay/crypt/Hash.php");
        include_once(WWW_DIR . "/itzlib/plugins/payment/include/yeepay/crypt/RSA.php");
        include_once(WWW_DIR . "/itzlib/plugins/payment/include/yeepay/crypt/TripleDES.php");
        include_once(WWW_DIR . "/itzlib/plugins/payment/include/yeepay/math/BigInteger.php");

		// 加密类
		$this->RSA = new Crypt_RSA();
		$this->RSA->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		$this->RSA->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->AES = new Crypt_AES(CRYPT_AES_MODE_ECB);

        $this->getPaymentConfig();

		// 商户配置
		$this->merchantAccount = $this->config['merchantAccount'];
		$this->merchartPublicKey = $this->config['merchantPublicKey'];
		$this->merchantPrivateKey = $this->config['merchantPrivateKey'];
		$this->yeepayPublicKey = $this->config['yeepayPublicKey'];

		// API URI 配置
		$this->bindBankcardURL = 'https://ok.yeepay.com/payapi/api/tzt/invokebindbankcard';
		$this->unbindBankcardURL = 'https://ok.yeepay.com/payapi/api/tzt/unbind';
		$this->confirmBindBankcardURL = 'https://ok.yeepay.com/payapi/api/tzt/confirmbindbankcard';
		$this->directBindPayURL = 'https://ok.yeepay.com/payapi/api/tzt/pay/bind/reuqest';
		$this->sendValidateCodeURL = 'https://ok.yeepay.com/payapi/api/tzt/pay/validatecode/send';
		$this->paymentQueryURL = 'https://ok.yeepay.com/merchant/query_server/pay_single';
		$this->paymentConfirmURL = 'https://ok.yeepay.com/payapi/api/tzt/pay/confirm/validatecode';
		$this->withdrawURL = 'https://ok.yeepay.com/payapi/api/tzt/withdraw';
		$this->queryWithdrawURL = 'https://ok.yeepay.com/payapi/api/tzt/drawrecord';
		$this->queryAuthbindListURL = 'https://ok.yeepay.com/payapi/api/bankcard/bind/list';
		$this->bankCardCheckURL = 'https://ok.yeepay.com/payapi/api/bankcard/check';
		$this->payClearDataURL = 'https://ok.yeepay.com/merchant/query_server/pay_clear_data';
        $this->QueryOrderURL = 'https://ok.yeepay.com/payapi/api/query/order';
		$this->refundURL = '';
		$this->refundQueryURL = '';
		$this->refundClearDataURL = '';
	}

	/**
	 * 获取商户编号
	 * @return type
	 */
	public function getMerchartAccount() {
		return $this->merchantAccount;
	}

	/**
	 * 获取商户私匙
	 * @return type
	 */
	public function getMerchantPrivateKey() {
		return $this->merchantPrivateKey;
	}

	/**
	 * 获取商户AESKey
	 * @return type
	 */
	public function getmerchantAESKey() {
		return $this->random(16, 1);
	}

	/**
	 * 获取易宝公匙
	 * @return type
	 */
	public function getYeepayPublicKey() {
		return $this->yeepayPublicKey;
	}

	/**
	 * 格式化字符串
	 * @param type $text
	 * @return type
	 */
	public function formatString($text) {
		return $text == '' || empty($text) || is_null($text) ? '' : trim($text);
	}

	/**
	 * String2Integer
	 * @param type $text
	 * @return type
	 */
	public function string2Int($text) {
		return $text == '' || empty($text) || is_nan($text) ? 0 : intval($text);
	}

	/**
	 * 生成随机字符串
	 * @param type $length 字符串长度
	 * @param type $numeric 数字模式
	 * @return type string
	 */
	public function random($length, $numeric = 0) {
		$seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
		$hash = '';
		$max = strlen($seed) - 1;
		for ($i = 0; $i < $length; $i++) {
			$hash .= $seed{mt_rand(0, $max)};
		}
		return $hash;
	}

	/**
	 * 绑卡请求接口请求地址
	 * @return type
	 */
	public function getBindBankcardURL() {
		return $this->bindBankcardURL;
	}

	/**
	 * 查询订单url
	 * @return type
	 */
	public function getQueryOrderURL() {
		return $this->QueryOrderURL;
	}

	/**
	 * 绑卡解除请求接口请求地址
	 * @return type
	 */
	public function getunBindBankcardURL() {
		return $this->unbindBankcardURL;
	}

	/**
	 * 绑卡确认接口请求地址
	 * @return type
	 */
	public function getConfirmBindBankcardURL() {
		return $this->confirmBindBankcardURL;
	}

	/**
	 * 发送短信验证码接口地址
	 * @return type
	 */
	public function getsendValidateCodeURL() {
		return $this->sendValidateCodeURL;
	}
	/**
	 * 支付接口请求地址
	 * @return type
	 */
	public function getDirectBindPayURL() {
		return $this->directBindPayURL;
	}

	/**
	 * 订单查询请求地址
	 * @return type
	 */
	public function getPaymentQueryURL() {
		return $this->paymentQueryURL;
	}

	/**
	 * 确定支付请求地址
	 * @return type
	 */
	public function getpaymentConfirmURL() {
		return $this->paymentConfirmURL;
	}

	/**
	 * 取现接口请求地址
	 * @return type
	 */
	public function getWithdrawURL() {
		return $this->withdrawURL;
	}

	/**
	 * 取现查询请求地址
	 * @return type
	 */
	public function getQueryWithdrawURL() {
		return $this->queryWithdrawURL;
	}

	/**
	 * 取现查询请求地址
	 * @return type
	 */
	public function getQueryAuthbindListURL() {
		return $this->queryAuthbindListURL;
	}

	/**
	 * 银行卡信息查询请求地址
	 * @return type
	 */
	public function getBankCardCheckURL() {
		return $this->bankCardCheckURL;
	}

	/**
	 * 支付清算文件下载请求地址
	 * @return type
	 */
	public function getPayClearDataURL() {
		return $this->payClearDataURL;
	}

	/**
	 * 单笔退款请求地址
	 * @return type
	 */
	public function getRefundURL() {
		return $this->refundURL;
	}

	/**
	 * 退款查询请求地址
	 * @return type
	 */
	public function getRefundQueryURL() {
		return $this->refundQueryURL;
	}

	/**
	 * 退款清算文件请求地址
	 * @return type
	 */
	public function getRefundClearDataURL() {
		return $this->refundClearDataURL;
	}

	/**
	 * 绑定银行卡
	 * @param type $identityid
	 * @param type $identitytype
	 * @param type $requestid
	 * @param type $cardno
	 * @param type $idcardno
	 * @param type $username
	 * @param type $phone
	 * @param type $registerphone
	 * @param type $registerdate
	 * @param type $registerip
	 * @param type $registeridcardno
	 * @param type $registercontact
	 * @param type $os
	 * @param type $imei
	 * @param type $userip
	 * @param type $ua
	 * @return type
	 */
	public function bindCard($data,$userInfo) {
        if(!empty($data['requestid']))
        {
            $requestid = $data['requestid'];
        }
        else
        {
            $requestid = time().($userInfo['user_id']%9).rand(10000,99999);
        }

        $query = array(
		    'merchantaccount'   => $this->getMerchartAccount(),
		    'identityid'        => $userInfo['hash_id'],#用户标识 
		    'identitytype'      => 2,#用户标识类型 2 用户id
		    'requestid'         => $requestid,#绑卡请求号
		    'cardno'            => $data['card'],#卡号
		    'idcardtype'        => '01',
		    'idcardno'          => $userInfo['card_id'],#身份证号
		    'username'          => $userInfo['realname'],#持卡人姓名
		    'phone'             => $data['phone'],#银行预留手机
            'registerip'        => $userInfo['addip'],#用户注册 ip
		    'userip'            => $_SERVER['REMOTE_ADDR']
		);
		$data = $this->post($this->getBindBankcardURL(), $query);
        if(!empty($data['code']))
        {
            $data['data']['send']   = $query;
            $data['data']['get']    = $data;
            return $data;
        }
        return array(
            'code'  =>0,
            'msg'   =>'',
            'data'  =>array(
                'data'      =>$data,
                'bind_no'   =>$data['requestid'],
                'send'      =>$query,
                'get'       =>$data
            )
            );
	}

	/**
	 * 确定绑卡
	 * @param type $requestid
	 * @param type $validatecode
	 * @return type
	 */
	public function bindVerfy($data) {

        $query = array(
		    'merchantaccount'   => $this->getMerchartAccount(),
		    'requestid'         => $data['bind_no'],
		    'validatecode'      => $data['verify_code']
		);
        $data = $this->post($this->getConfirmBindBankcardURL(), $query);
        if(!empty($data['code']))
        {
            $data['data']['send']   = $query;
            $data['data']['get']    = $data;
            return $data;
        }
        return array(
            'code'  =>0,
            'msg'   =>'',
            'data'  =>array(
                'data'      =>$data,
                'no_agree'  =>$data['requestid'],
                'send'      =>$query,
                'get'       =>$data
            )
            );
    }

	/**
	 * 解除绑卡
	 * @param type $requestid
	 * @param type $validatecode
	 * @return type
	 */
	public function bankCardUnbind($data,$userInfo,$safe_card) {
        $query = array(
		    'merchantaccount'   => $this->getMerchartAccount(),
		    'card_top'          => substr($safe_card['card_number'],0,6),
		    'card_last'         => substr($safe_card['card_number'],-4,4),
		    'identityid'        => $userInfo['hash_id'],
		    'identitytype'      => 2
		);

        $data = $this->post($this->getunBindBankcardURL(), $query);

        #返回结果
        if(!empty($data['code']))
        {
            return $data;
        }
        return array(
            'code'  =>0,
            'msg'   =>'',
            'data'  =>$data
            );
        /*
            Array
            (
                [code] => 0
                [msg] => 
                [data] => Array
                    (
                        [data] => Array
                            (
                                [card_last] => 3862
                                [card_top] => 621483
                                [identityid] => ttttasdrf
                                [identitytype] => 2
                                [merchantaccount] => 10000419568
                            )
                    )
            )
            */
    }

	/**
	 * 获取绑卡记录
	 * @param type $identityid
	 * @param type $identitytype
	 * @return type
	 */
	public function bankcardList($data = array()) {
		$query = array(
		    'merchantaccount'   => $this->getMerchartAccount(),
		    'identityid'        => $data['identityid'],
		    'identitytype'      => 2
		);
		return $this->get($this->getQueryAuthbindListURL(), $query);
	}

	/**
	 * 发送短信验证码接口
	 * @param type $orderid
	 * @return type
	 */
	public function rechargeSms( $data ,$safeCard,$userInfo) {
		$query = array(
		    'merchantaccount'   => $this->getMerchartAccount(),
		    'orderid'           => $data['trade_no'],#商户订单号
		);
        $data = $this->post($this->getsendValidateCodeURL(), $query);

        #返回结果
        if(!empty($data['code']))
        {
            $data['data']['send'] = $query;
            $data['data']['get'] = $data;
            return $data;
        }
        
        $data['send']   = $query;
        $data['get']    = $data;
        return array(
            'code'  =>0,
            'msg'   =>'',
            'data'  =>$data
            );
	}

	/**
	 * 获取绑卡支付请求
	 * @param type $orderid
	 * @param type $transtime
	 * @param type $amount
	 * @param type $productname
	 * @param type $productdesc
	 * @param type $identityid
	 * @param type $identitytype
	 * @param type $card_top
	 * @param type $card_last
	 * @param type $orderexpdate
	 * @param type $callbackurl
	 * @param type $imei
	 * @param type $userip
	 * @param type $ua
	 * @return type
	 */
	public function recharge( $data ,$cardInfo,$userInfo) {

        $nosendCode = 0;
        if(!empty($data['nosendCode']))
        {
            $nosendCode = 1;
        }

		$query = array(
		    'merchantaccount'   => $this->getMerchartAccount(),
		    'orderid'           => $data['trade_no'],#商户订单号
		    'transtime'         => time(),#交易时间 
		    'currency'          => 156,#交易币种
		    'amount'            => intval(bcmul($data['money'], 100)),# 交易金额 √ int 以"分"为单位的整型
		    'productname'       => 'ITZ充值',#商品名称 √ string 最长50位
		    'productdesc'       => '充值',
		    'identityid'        => $userInfo['hash_id'],#用户标识 
		    'identitytype'      => 2,#用户标识类型
		    'card_top'          => substr($cardInfo['card_number'],0,6),#卡号前6位
		    'card_last'         => substr($cardInfo['card_number'],-4,4),#卡号后4位
		    'orderexpdate'      => 180,#订单有效期
		    'callbackurl'       => 'https://www.xxx.com/newuser/paymentNotify/Yeepay',
		    'userip'            => $_SERVER['REMOTE_ADDR']
		);

        $data = $this->post($this->getDirectBindPayURL(), $query);

        #返回结果
        if(!empty($data['code']))
        {
            $data['data']['send']   =$query;
            $data['data']['get']    =$data;
            return $data;
        }
        
        if($nosendCode==0)
        {
            $info = $this->rechargeSms(array('trade_no'=>$data['orderid']));
            if($info['code']=='0')
            {
                return $info;
            }
        }

        $data['send']   =$query;
        $data['get']    =$data;
        return array(
            'code'  =>0,
            'msg'   =>'',
            'data'  =>$data
            );
        /*
        Array
        (
            [merchantaccount] => 10000419568
            [orderid] => 14343666794598
            [phone] => 13161741900
            [smsconfirm] => 0
        )
        */
	}

	/**
	 * 确认支付
	 * @param type $orderid
	 * @param type $validatecode
	 * @return type
	 */
	public function rechargeVerify($data,$safeCard,$userInfo) {
		$query = array(
		    'merchantaccount'   => $this->getMerchartAccount(),
		    'orderid'           => $data['trade_no'],
		    'validatecode'      => $data['verify_code']
		);
        $data = $this->post($this->getpaymentConfirmURL(), $query);

        if(!empty($data['code']))
        {
            if(in_array($data['data']['error_code'],array('600010','600097')))
            {
                $data['code'] = '111';
            }

            $data['data']['send']   = $query;
            $data['data']['get']    = $data;
            return $data;
        }

        #第三方系统异常
        if(empty($data['amount']))
        {
            $data['code'] = '111';
            $data['data']['send']   = $query;
            $data['data']['get']    = $data;
            return $data;
        }

        $data['send']   = $query;
        $data['get']    = $data;
        return array(
            'code'  =>0,
            'msg'   =>'',
            'data'  =>$data
            );
	}

	/**
	 * 交易记录查询
	 * @param type $orderid
	 * @param type $yborderid
	 * @return type
	 */
	public function paymentQuery($orderid, $yborderid) {
		$query = array(
		    'merchantaccount' => $this->getMerchartAccount(),
		    'orderid' => $orderid,
		    'yborderid' => $yborderid
		);
		return $this->get($this->getPaymentQueryURL(), $query);
	}

	/**
	 * 提现
	 * @param type $requestid
	 * @param type $identityid
	 * @param type $identitytype
	 * @param type $card_top
	 * @param type $card_last
	 * @param type $amount
	 * @param type $imei
	 * @param type $userip
	 * @param type $ua
	 * @return type
	 */
	public function withdraw($requestid, $identityid, $identitytype, $card_top, $card_last, $amount, $imei, $userip, $ua) {
		$query = array(
		    'merchantaccount' => $this->getMerchartAccount(),
		    'requestid' => $requestid,
		    'identityid' => $identityid,
		    'identitytype' => $identitytype,
		    'card_top' => $card_top,
		    'card_last' => $card_last,
		    'amount' => $amount,
		    'currency' => 156,
		    'drawtype' => 'NATRALDAY_NORMAL',
		    'imei' => $imei,
		    'userip' => $userip,
		    'ua' => $ua
		);
		return $this->post($this->getWithdrawURL(), $query);
	}

	/**
	 * 银行卡信息查询
	 * @param type $cardno
	 * @return type
	 */
	public function bankcardCheck($cardno) {
		$query = array(
		    'merchantaccount' => $this->getMerchartAccount(),
		    'cardno' => $cardno
		);
		return $this->post($this->getBankCardCheckURL(), $query);
	}

	/**
	 * 提现查询
	 * @param type $requestid
	 * @param type $ybdrawflowid
	 * @return type
	 */
	public function withdrawQuery($requestid, $ybdrawflowid) {
		$query = array(
		    'merchantaccount' => $this->getMerchartAccount(),
		    'requestid' => $requestid,
		    'ybdrawflowid' => $ybdrawflowid
		);
		return $this->get($this->getQueryWithdrawURL(), $query);
	}

	/**
	 * 获取支付清算文件
	 * @param type $startdate
	 * @param type $enddate
	 * @return type
	 */
	public function payClearData($startdate, $enddate) {
		$query = array(
		    'merchantaccount' => $this->getMerchartAccount(),
		    'startdate' => $startdate,
		    'enddate' => $enddate
		);
		
		$url = $this->getUrl($this->getPayClearDataURL(), $query);
		$data = $this->http($url, 'GET');
		if ($this->http_info['http_code'] == 405) {
			return $this->yeepayException('此接口不支持使用GET方法请求', 1003);
		}
		return $data;
	}

	/**
	 * 
	 * @param string $url
	 * @param type $query
	 * @return string
	 */
	public function getUrl($url, $query) {
		$request = $this->buildRequest($query);
		$url .= '?' . http_build_query($request);
		return $url;
	}

	public function buildRequest($query) {
		if (!array_key_exists('merchantaccount', $query)) {
			$query['merchantaccount'] = $this->getMerchartAccount();
		}
		$sign = $this->RSASign($query);
		$query['sign'] = $sign;
		$request = array();
		$request['merchantaccount'] = $this->getMerchartAccount();
		$request['encryptkey'] = $this->getEncryptkey();
		$request['data'] = $this->AESEncryptRequest($query);
		return $request;
	}

	/**
	 * 用RSA 签名请求
	 * @param array $query
	 * @return string
	 */
	protected function RSASign(array $query) {
		if (array_key_exists('sign', $query)) {
			unset($query['sign']);
		}
		ksort($query);
		$this->RSA->loadKey($this->getMerchantPrivateKey());
		$sign = base64_encode($this->RSA->sign(join('', $query)));
		return $sign;
	}

	/**
	 * 通过RSA，使用易宝公钥，加密本次请求的AESKey
	 * @return string
	 */
	protected function getEncryptkey() {
		if (!$this->AESKey) {
			$this->generateAESKey();
		}
		$this->RSA->loadKey($this->yeepayPublicKey);
		$encryptKey = base64_encode($this->RSA->encrypt($this->AESKey));
		return $encryptKey;
	}

	/**
	 * 生成一个随机的字符串作为AES密钥
	 * @param number $length
	 * @return string
	 */
	protected function generateAESKey($length = 16) {
		$baseString = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$AESKey = '';
		$_len = strlen($baseString);
		for ($i = 1; $i <= $length; $i++) {
			$AESKey .= $baseString[rand(0, $_len - 1)];
		}
		$this->AESKey = $AESKey;
		return $AESKey;
	}

	/**
	 * 返回易宝返回数据的AESKey
	 * @param unknown $encryptkey
	 * @return Ambigous <string, boolean, unknown>
	 */
	protected function getYeepayAESKey($encryptkey) {
		$this->RSA->loadKey($this->merchantPrivateKey);
		$yeepayAESKey = $this->RSA->decrypt(base64_decode($encryptkey));
		return $yeepayAESKey;
	}

	/**
	 * 通过AES加密请求数据
	 * @param array $query
	 * @return string
	 */
	protected function AESEncryptRequest(array $query) {
		if (!$this->AESKey) {
			$this->generateAESKey();
		}
		$this->AES->setKey($this->AESKey);
		return base64_encode($this->AES->encrypt(json_encode($query)));
	}

	/**
	 * 模拟HTTP协议
	 * @param string $url
	 * @param string $method
	 * @param string $postfields
	 * @return mixed
	 */
	protected function http($url, $method, $postfields = NULL) {
		$this->http_info = array();
		$ci = curl_init();
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ci, CURLOPT_HEADER, FALSE);
		$method = strtoupper($method);
		switch ($method) {
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($postfields)) {
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
				}
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($postfields)) {
					$url = "{$url}?{$postfields}";
				}
		}
		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;
		curl_close($ci);
		return $response;
	}

	/**
	 * Get the header info to store.
	 * @param type $ch
	 * @param type $header
	 * @return type
	 */
	public function getHeader($ch, $header) {
		$i = strpos($header, ':');
		if (!empty($i)) {
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->http_header[$key] = $value;
		}
		return strlen($header);
	}

	/**
	 * 解析返回数据
	 * @param type $data
	 * @return type
	 * @throws yeepayException
	 */
	protected function parseReturnData($data) {
		$return = json_decode($data, true);
		if (array_key_exists('error_code', $return) && !array_key_exists('status', $return)) {
			return $this->yeepayException($return['error_msg'], $return['error_code'],$return);
		}
		return $this->parseReturn($return['data'], $return['encryptkey']);
	}

	/**
	 * 解析返回数据
	 * @param type $data
	 * @param type $encryptkey
	 * @return type
	 * @throws yeepayException
	 */
	protected function parseReturn($data, $encryptkey) {
		$AESKey = $this->getYeepayAESKey($encryptkey);
		$return = $this->AESDecryptData($data, $AESKey);
		$return = json_decode($return, true);
		if (!array_key_exists('sign', $return)) {
			if (array_key_exists('error_code', $return)) {
				return $this->yeepayException($return['error_msg'], $return['error_code']);
			}
			return $this->yeepayException('请求返回异常', 1001);
		} else {
			if (!$this->RSAVerify($return, $return['sign'])) {
				return $this->yeepayException('请求返回签名验证失败', 1002);
			}
		}
		if (array_key_exists('error_code', $return) && !array_key_exists('status', $return)) {
			return $this->yeepayException($return['error_msg'], $return['error_code'],$return);
		}
		unset($return['sign']);
		return $return;
	}

	/**
	 * 通过AES解密易宝返回的数据
	 * @param string $data
	 * @param string $AESKey
	 * @return Ambigous <boolean, string, unknown>
	 */
	protected function AESDecryptData($data, $AESKey) {
		$this->AES->setKey($AESKey);
		return $this->AES->decrypt(base64_decode($data));
	}

	/**
	 * 使用易宝公钥检测易宝返回数据签名是否正确
	 * @param array $query
	 * @param string $sign
	 * @return boolean
	 */
	protected function RSAVerify(array $return, $sign) {
		if (array_key_exists('sign', $return)) {
			unset($return['sign']);
		}
		ksort($return);
		$this->RSA->loadKey($this->yeepayPublicKey);
		foreach ($return as $k => $val) {
			if (is_array($val)) {
				$return[$k] = self::cn_json_encode($val);
			}
		}
		return $this->RSA->verify(join('', $return), base64_decode($sign));
	}

	/**
	 * json_encode
	 * @param type $value
	 * @return type
	 */
	public static function cn_json_encode($value) {
		if (defined('JSON_UNESCAPED_UNICODE')) {
			return json_encode($value, JSON_UNESCAPED_UNICODE);
		} else {
			$encoded = urldecode(json_encode(self::array_urlencode($value)));
			return preg_replace(array('/\r/', '/\n/'), array('\\r', '\\n'), $encoded);
		}
	}

	/**
	 * urlencode
	 * @param type $value
	 * @return type
	 */
	public static function array_urlencode($value) {
		if (is_array($value)) {
			return array_map(array('yeepay', 'array_urlencode'), $value);
		} elseif (is_bool($value) || is_numeric($value)) {
			return $value;
		} else {
			return urlencode(addslashes($value));
		}
	}


	/**
	 * 使用POST的方式发出API请求
	 * @param type $url
	 * @param type $query
	 * @return type
	 * @throws yeepayException
	 */
	protected function post($url, $query) {
		$request = $this->buildRequest($query);
		$data = $this->http($url, 'POST', http_build_query($request));
        #echo $url."\n";print_r($query);
		if ($this->http_info['http_code'] == 405) {
			return $this->yeepayException('此接口不支持使用POST方法请求', 1004);
		}


        return $this->parseReturnData($data);
	}

	/**
	 * 使用GET的模式发出API请求
	 * @param string $type
	 * @param string $method
	 * @param array $query
	 * @return array
	 */
	protected function get($url, $query) {
		$request = $this->buildRequest($query);
		$url .= '?' . http_build_query($request);
		$data = $this->http($url, 'GET');
		if ($this->http_info['http_code'] == 405) {
			return $this->yeepayException('此接口不支持使用GET方法请求', 1003);
		}
		return $this->parseReturnData($data);
	}

    #异常特殊处理
    public function yeepayException($msg,$code,$data = array()){
    	if($code){ // 替换msg
    		$info = ReturnService::getInstance()->getReturn('yeepay',$code);
    		$msg = empty($info) ? $msg : $info;
    	}
        return array(
            'code'  =>100,
            'msg'   =>$msg,
            'data'  =>$data
       );
    }

    public function noticeResult($data){}
    public function returnResult($data){}

    #充值回调
    public function getNoticeData(){
        if($_POST)
        {
            return $this->parseReturnData(json_encode($_POST));
        }
        return array();
    }

    public function request($path, $data){}
    public function mobileForm($data){}

    #绑卡并充值
    public function bindAndRecharge($data,$cardInfo,$userInfo){

        $data = array(
            'card'      =>$cardInfo['card_number'],
            'phone'     =>$cardInfo['phone'],
            'requestid' =>$data['trade_no'],#充值订单号
            );
        $info = $this->bindCard($data,$userInfo);

        #绑卡失败（如果）
        if($info['code']!=0)
        {
            #解绑
            $this->bankCardUnbind(array(),$userInfo,$cardInfo);

        }

        return $info;
    }
    
    #绑卡并充值确认
    public function bindAndRechargeVerify($data,$cardInfo,$userInfo){

        #绑卡
        $info = $this->bindVerfy(array(
            'bind_no'       =>$data['trade_no'],
            'verify_code'   =>$data['verify_code']
            ));

        #绑卡失败（如果）
        if($info['code']!=0)
        {
            #解绑
            $this->bankCardUnbind(array(),$userInfo,$cardInfo);

            return $info;
        }

        #判断订单是否存在
        $recharge=AccountRecharge::model()->findByAttributes(array('user_id'=>$data['user_id'],'trade_no'=>$data['trade_no']));
        if(empty($recharge)){
            
            #解绑
            $this->bankCardUnbind(array(),$userInfo,$cardInfo);

            return array(
                'code'  =>'1',
                'msg'   =>'充值订单不存在',
                'data'  =>''
            );
        }

        #充值
        $info = $this->recharge(array(
            'user_id'   =>$data['user_id'],
            'trade_no'  =>$data['trade_no'],
            'money'     =>$recharge['money'],
            'nosendCode'=>1
            ),$cardInfo,$userInfo);
        if($info['code']!='0')
        {
            #解绑
            $this->bankCardUnbind(array(),$userInfo,$cardInfo);
        }
        if($info['code']==0 || strpos($info['msg'],'重复'))
        {
            $info = $this->rechargeVerify(array('trade_no'=>$data['trade_no']));
            
            if($info['code']==0)
            {
                $info['usr_pay_agreement_id']='';

                $info['send'] = $info['data']['send'];
                $info['get'] = $info['data']['get'];
                return array(
                    'code'  =>0,
                    'msg'   =>'',
                    'data'  =>$info
                );
            }
        }
        return $info;

        
    }
    
    #绑卡并充值_重新发送验证码
    public function bindAndRechargeSms($data,$safeCard,$userInfo){
        return $this->bindAndRecharge($data,$safeCard,$userInfo);
    }

    //连连支付，获取商户信息
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
    
    #用户签约信息查询API接口
    public function getBindCrasList($info,$userInfo) {

		$query = array(
		    'merchantaccount'   => $this->getMerchartAccount(),
		    'identityid'        => $userInfo->hash_id,
		    'identitytype'      => 2
		);
        $result = $this->get($this->getQueryAuthbindListURL(), $query);
        $bank_list = array();
        if(!empty($result['cardlist']))
        {
            foreach($result['cardlist'] as $v)
            {
                $bank_list[] = array(
                    'channel_name'  =>'易宝支付',
                    'bank_name'     =>$v['card_name'],
                    'card_no'       =>$v['card_top'].'****'.$v['card_last'],
                    'no_agree'      =>'无',
                    'tel'           =>$v['phone']
                    );
            }
        }
        return $bank_list;
    }

    #查询订单状态
    public function orderInfoQuery($recharge){
		$query = array(
		    'merchantaccount'   => $this->getMerchartAccount(),
            'orderid'           => $recharge->trade_no,#商户订单号
		);
        $data = $this->get($this->getQueryOrderURL(), $query);
        if($data['status']==1)
        {
            return array('code'=>0,'msg'=>'','data'=>array());
        }
        else
        {
            $msg = $data['msg'];
            if(isset($data['errorcode']))
            {
                $msg = $data['errorcode'].$data['errormsg'];
            }
            return array('code'=>1,'msg'=>$msg,'data'=>array());
        }
    }
}
