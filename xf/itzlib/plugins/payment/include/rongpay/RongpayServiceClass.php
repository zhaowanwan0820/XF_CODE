<?php

include_once( dirname(__FILE__).'/RongpayBase.php');

class RongpayServiceClass extends RongpayBase {

	var $gateway;			//网关地址
	var $_key;				//安全校验码
	var $mysign;			//签名结果
	var $sign_type;			//签名类型
	var $parameter;			//需要签名的参数数组
	var $charset;    //字符编码格式
	
	/**
	 * 构造函数
	 * 从配置文件及入口文件中初始化变量
	 * $parameter 需要签名的参数数组
	 * $key 安全校验码
	 * $sign_type 签名类型
	 */
	function __construct($merchant_ID, $key, $sign_type, $charset = "UTF-8") {
		$this->gateway		= "https://epay.reapal.com/portal?";
		$this->_key  		= $key;
		$this->sign_type	= $sign_type;
		$this->merchant_ID  = $merchant_ID;
		
		//设定charset的值,为空值的情况下默认为UTF-8
		if($charset == '') {
			$charset = 'UTF-8';
		}
		$this->charset = $charset;
		
	}
	
	/**
	 * 功能：构造表单提交HTML
	 * @param merchant_ID 合作身份者ID
	 * @param seller_email 签约融宝支付账号或卖家融宝支付帐户
	 * @param return_url 付完款后跳转的页面 要用 以http开头格式的完整路径，不允许加?id=123这类自定义参数
	 * @param notify_url 交易过程中服务器通知的页面 要用 以http开格式的完整路径，不允许加?id=123这类自定义参数
	 * @param order_no 请与贵网站订单系统中的唯一订单号匹配
	 * @param subject 订单名称，显示在融宝支付收银台里的“商品名称”里，显示在融宝支付的交易管理的“商品名称”的列表里。
	 * @param body 订单描述、订单详细、订单备注，显示在融宝支付收银台里的“商品描述”里
	 * @param total_fee 订单总金额，显示在融宝支付收银台里的“交易金额”里
	 * @param buyer_email 默认买家融宝支付账号
	 * @param input_charset 字符编码格式 目前支持 GBK 或 utf-8
	 * @param key 安全校验码
	 * @param sign_type 签名方式 不需修改
	 * @return 表单提交HTML文本
	 */
	function BuildForm($parameter) {
		
		$newparameter = self::paraFilter($parameter);
		
		//获得签名结果
		$sortArray = self::argSort($newparameter);    //得到从字母a到z排序后的签名参数数组
		$mysign = self::buildMySign($sortArray, $this->_key, $this->sign_type);
		
		$sHtml = "<form id='rongpaysubmit' name='rongpaysubmit' action='".$this->gateway."charset=".$this->charset."' method='post'>";
		
		foreach($parameter as $key => $val) {
			$sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
		}
	
		$sHtml = $sHtml."<input type='hidden' name='sign' value='".$mysign."'/>";
		$sHtml = $sHtml."<input type='hidden' name='sign_type' value='".$this->sign_type."'/>";
		
		$sHtml = $sHtml."<script>document.getElementById('rongpaysubmit').submit();</script>";
		return $sHtml;
	}
	
	/**
	 * 查询接口，订单查询
	 * @param string $order_no 商户订单编号
	 * @param string $trade_no 融宝流水编号
	 * @return: array $result
	 * is_success: T表示成功,F表示失败
	 * result_code: 错误代码，具体含义见文件底部"查询和退款的错误代码"
	 */
	public function query($order_no, $trade_no = '') {
		$result = array();
		$params = array ("merchant_ID" => $this->merchant_ID,
				"charset" => $this->charset,
				"order_no" => $order_no,
				"trade_no" => $trade_no
			);
		
		$post = self::paraFilter($params);
		$sort_post = self::argSort($post);
		$sign= self::buildMySign($sort_post, $this->_key);
		$str = self::createLinkstring($params);
		$url = "http://interface.reapal.com/query/payment?";
		$paramstr=$url.$str."&sign=".$sign."&sign_type=".$this->sign_type;
		
		$xml=new DOMDocument();
		
		$xml->load($paramstr);
		/* 融宝返回给商户的信息 */
		/*申请查询是否成功*/
		$value_tmp=$xml->getElementsByTagName("is_success");
		$result['is_success'] = $value_tmp->item(0)->nodeValue;
		/* 错误代码 */
		$value_tmp=$xml->getElementsByTagName("result_code");
		$result['result_code'] = $value_tmp->item(0)->nodeValue;
		/*时间戳*/
		$value_tmp=$xml->getElementsByTagName("timestamp");
		$result['timestamp'] =$value_tmp->item(0)->nodeValue;
		
		$value_tmp=$xml->getElementsByTagName("trade_no");
		$result['trade_no'] = $value_tmp->item(0)->nodeValue;
		
		$value_tmp=$xml->getElementsByTagName("order_no");
		$result['order_no'] = $value_tmp->item(0)->nodeValue;
		
		$value_tmp=$xml->getElementsByTagName("trade_type");
		$result['trade_type'] =$value_tmp->item(0)->nodeValue;
		
		$value_tmp=$xml->getElementsByTagName("amount");
		$result['amount'] = $value_tmp->item(0)->nodeValue;
		
		$value_tmp=$xml->getElementsByTagName("fee_amount");
		$result['fee_amount'] = $value_tmp->item(0)->nodeValue;
		
		$value_tmp=$xml->getElementsByTagName("subject");
		$result['subject'] =$value_tmp->item(0)->nodeValue;
		
		$value_tmp=$xml->getElementsByTagName("trade_date");
		$result['trade_date'] = $value_tmp->item(0)->nodeValue;
		
		$value_tmp=$xml->getElementsByTagName("created_time");
		$result['created_time'] = $value_tmp->item(0)->nodeValue;
		
		$value_tmp=$xml->getElementsByTagName("status");
		$result['status'] =$value_tmp->item(0)->nodeValue;
		
		return $result;
	}
	
	/**
	 * 退款接口
	 * @param string $orig_order_no
	 * @param number $amount
	 * @param string $note
	 * @return array $result
	 *  is_success: T表示成功,F表示失败
	 *  result_code: 错误代码，具体含义见文件底部"查询和退款的错误代码"
	 *  timestamp: 时间戳
	 */
	public function refund($orig_order_no, $amount = 0, $note = '') {
		$result = array();
		$params = array ("merchant_ID" => $this->merchant_ID,
				"charset" => $this->$charset,
				"orig_order_no" => $orig_order_no,
				"amount" => $amount,
				"note" => $note);
		
		$post = self::paraFilter($params);
		$sort_post = self::argSort($post);
		$sign = self::buildMySign($sort_post, $this->_key);
		$str = self::createLinkstring($params);
		
		$url = "http://interface.reapal.com/service/refund?";
		$paramstr=$url.$str."&sign=".$sign."&sign_type=".$this->sign_type;
		
		$xml=new DOMDocument();
		
		$xml->load($paramstr);
		
		/* 融宝返回给商户的信息 */
		/* 申请退款是否成功 */
		$value_tmp = $xml->getElementsByTagName("is_success");
		$result['is_success'] = $value_tmp->item(0)->nodeValue;
		
		/* 错误代码 */
		$value_tmp = $xml->getElementsByTagName("result_code");
		$result['result_code'] = $value_tmp->item(0)->nodeValue;
		
		/*时间戳*/
		$value_tmp = $xml->getElementsByTagName("timestamp");
		$result['timestamp'] = $value_tmp->item(0)->nodeValue;
		
		return $result;
	}
	
	/**
	 * 查询和退款的错误代码
	 * 错误码                    错误说明
	 * SUCCESS                    成功
	 * GENERAL_FAIL                一般性错误
	 * ILLEGAL_PARAMETER            参数错误
	 * ILLEGAL_MERCHANT_ID            合作伙伴错误
	 * ILLEGAL_SIGN                签名错误
	 * SERVICE_NOT_SUPPORT            不支持此服务
	 * ILLEGAL_ROYALTY_AMOUNT            错误的纷扰金额
	 * OUT_TRADE_NO_REPEAT            外部提交的交易号重复
	 * ILLEGAL_CHARSET                错误字符编码
	 * USER_NOT_EXIST                用户不存在
	 * BINDING_NOT_EXIST            分润绑定关系不存在
	 * ACCOUNT_STATUS_NOT_ALLOW        账户状态不允许
	 * AVAILABLE_AMOUNT_NOT_ENOUGH        可用余额不足
	 * TRADE_NOT_EXIST                交易不存在
	 * TRADE_STATUS_NOT_ALLOW            交易状态不允许
	 * GREATER_REFUND_MONEY            没有足够的退款金额
	 * GREATER_UNFROZEN_MONEY            没有足够的解冻金额
	 * SYSTEM_BUSY                系统忙
	 */
	

}