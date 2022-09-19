<?php
/******************************
 * $File: sms.php
 * $Description: sms
 * $Author: changqi 
 * $Time:2014-01-15
 * $Update:None 
 * $UpdateDate:None 
******************************/


return array(
		
		// 默认短信发送网关
		'defaultGateway' => '1',
		
		// 根据手机运营商选择短信发送网关的配置
		'switchGatewayByoperator' => array(
				'CM' => array(),
				'CU' => array(),
				'CT' => array(),
		),
		
		// 根据要发送的短信类型选择短信发送网关的配置
		'switchGatewayByContentType' => array(
				'default' => '1',
				'rubbish' => '3',
				'mob_auth' => '5', //4
				'vcode' => '5',  
		),
		//有解当前触模板code
		'yj_sms_code_list' => array(
			'wx_buyer_order_create',
			'wx_seller_order_create',
			'wx_seller_order_cancel_by_buyer',
			'wx_seller_order_cancel_expire',
			'wx_seller_order_cancel_no_pay',
			'wx_seller_buyer_pay',
			'wx_buyer_seller_receive_money_fail',
			'wx_buyer_seller_receive_money_success',
			'wx_seller_seller_receive_money_success',
			'wx_buyer_seller_no_confirm_cert',
			'wx_seller_seller_no_confirm_cert',
			'wx_buyer_trade_success',
			'wx_seller_trade_success',
			'wx_buyer_trade_fail',
			'wx_seller_trade_fail',
			'wx_bank_card_auth_success',
			'wx_bank_card_auth_fail',
		),

		
);
