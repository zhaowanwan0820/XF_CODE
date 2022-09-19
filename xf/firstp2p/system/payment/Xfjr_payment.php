<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

$payment_lang = array(
	'name'	=>	'先锋支付',
	'xfjr_account'	=>	'商户编号',
	'xfjr_key'	=>	'商户密钥',
	'xfjr_url' => '支付网关地址',
	'xfjr_query' => '订单查询地址',
);
$config = array(
	'xfjr_account'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户编号
	'xfjr_key'	=>	array(
		'INPUT_TYPE'	=>	'0'
	), //商户密钥 
	'xfjr_url'	=>	array(
		'INPUT_TYPE'	=>	'0'
	), //支付网关URL
	'xfjr_query'	=>	array(
		'INPUT_TYPE'	=>	'0'
	),
);
/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'Xfjr';

    /* 名称 */
    $module['name']    = $payment_lang['name'];


    /* 支付方式：1：在线支付；0：线下支付 */
    $module['online_pay'] = '1';

    /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
    
    return $module;
}

// 支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');

class Xfjr_payment implements payment {

	public function get_payment_code($payment_notice_id, $pd_FrpId = "")
	{
		$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
		$order_sn = $GLOBALS['db']->getOne("select order_sn from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
		$money = round($payment_notice['money'],2);
		$payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']));
		$payment_info['config'] = unserialize($payment_info['config']);

		
//		$data_return_url = get_domain().APP_ROOT.'/index.php?ctl=payment&act=response&class_name=Xfjr&site='.$_REQUEST['site'];
		$data_return_url = \libs\web\Url::getConfDomain('payment','response').APP_ROOT.'/payment/response?class_name=Xfjr&site='.$_REQUEST['site'];
                $serverNotifyURL = \libs\web\Url::getConfDomain('payment','response').APP_ROOT.'/payment/response?class_name=Xfjr&notify=1';
                
		
	//获取先锋支付能够识别的银行KEY值
//        $bankshort = explode('-', addslashes(htmlspecialchars(trim($pd_FrpId))));
//        $pd_FrpId=$bankshort[0];
                
        $data_order_id    = $payment_notice['notice_sn'];
        $data_amount      = $money;
        
        $interfaceName = 'PayOrder';
        $version = 'B2C1.0';
        $curType = 'CNY';
        $bankId = trim($payment_info['config']['xfjr_bankid']);
        $merchantId = trim($payment_info['config']['xfjr_account']);
        $keyValue = trim($payment_info['config']['xfjr_key']);
        $xfjr_url = trim($payment_info['config']['xfjr_url']);
//        $serverNotifyURL = $data_return_url;
        
        $cardType_bank = formatConf(app_conf('XFJR_ALLCARDTYPE_BANK'));
        $cardType_bank = explode(',', $cardType_bank);
        if(!empty($cardType_bank) && in_array($pd_FrpId, $cardType_bank))
        {
            $cardType = '99';            
        }
        else
        {
            $cardType = '01';            
        }
        // 交易数据
        $tranData = "<?xml version=\"1.0\" encoding=\"GBK\"?><B2CReq><merId>{$merchantId}</merId><curType>{$curType}</curType><cardType>{$cardType}</cardType><returnURL>{$data_return_url}</returnURL><notifyURL>{$serverNotifyURL}</notifyURL><orderNo>{$data_order_id}</orderNo><orderAmt>{$data_amount}</orderAmt><remark></remark></B2CReq>";
//		echo $tranData;exit();
		
        /*
         <?xml version="1.0" encoding="GBK"?><B2CReq><merId>M100000001</merId><curType>CNY</curType><returnURL>http://106.120.128.52:8989/shopDemo/b2c/callback.jsp</returnURL><notifyURL>http://106.120.128.52:8989/shopDemo/b2c/callback.jsp</notifyURL><orderNo>6745979869010</orderNo><orderAmt>2.00</orderAmt><remark>111111</remark></B2CReq>
        */
        
        
        $base64_tranData = base64_encode($tranData);
        
        // 订单签名数据
        #$merSignMsg = $this->getHmacMd5($tranData, $keyValue, $merchantId);
        $merSignMsg = $this->HmacMd5($tranData, $keyValue);
        
        $code = <<<EOT
        	<form action='{$xfjr_url}' method='post' >
			<input type='hidden' name='interfaceName' value='{$interfaceName}'>
			<input type='hidden' name='version' value='{$version}'>
			<input type='hidden' name='tranData' value='{$base64_tranData}'>
			<input type='hidden' name='bankId' value='{$pd_FrpId}'>
			<input type='hidden' name='merSignMsg' value='{$merSignMsg}'>
			<input type='hidden' name='merchantId' value='{$merchantId}'>
EOT;
		
		if(!empty($payment_info['logo']))
			$code .= "<input type='image' src='".APP_ROOT.$payment_info['logo']."' style='border:solid 1px #ccc;'><div class='blank'></div>";
			
        $code .= "<input type='submit' class='paybutton' value='前往先锋在线支付'>";
		
        $code .= "</form>\n";


		$code.="<br /><div style='text-align:center' class='red'>".$GLOBALS['lang']['PAY_TOTAL_PRICE'].":".format_price($money)."</div>";
		
        return $code;

	}
	
	public function response($request)
	{
		$return_res = array(
			'info'=>'',
			'status'=>false,
		);
		$payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Xfjr'");  
    	$payment['config'] = unserialize($payment['config']);
    	$merchantId = trim($payment['config']['xfjr_account']); // 获取商户编号
    	$keyValue = trim($payment['config']['xfjr_key']);  // 获取秘钥
    	
        $interfaceName = $request['interfaceName'];
        $version = $request['version'];
        $tranData = base64_decode($request['tranData']);
        $signMsg = $request['signData'];
    	
        // 获取签名数据进行比对
        #$myMerSignMsg = $this->getHmacMd5($tranData, $keyValue, $merchantId);
        $merSignMsg = $this->HmacMd5($tranData, $keyValue);
        
		 // 或者是本地主动查询数据
		if (strtoupper($signMsg) == strtoupper($merSignMsg)  ||  $request['op'])
		{
			//对返回的XML数据进行解析
			$tranData = iconv("UTF-8","GB2312//IGNORE",$tranData);
			$retXml = simplexml_load_string(stripslashes($tranData));
			
 			$tranStat = $retXml->tranStat;
			 
			$payment_notice_sn = $retXml->orderNo;  // 本系统订单号
			$money = $retXml->orderAmt; // 支付金额
			$outer_notice_sn = $retXml->tranSerialNo; // 先锋订单流水号
			if($tranStat == "1")
			{
                    //判断订单来源站点
                    if(!empty($_REQUEST['site']))
                    {
                        $domain_url = 'http://'.$GLOBALS['sys_config']['SITE_DOMAIN'][$_REQUEST['site']];
                    }
                    else
                    {
                        $domain_url='';
                    }
				$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where notice_sn = '".$payment_notice_sn."'");
				$order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
				require_once APP_ROOT_PATH."system/libs/cart.php";
				$rs = payment_paid($payment_notice['id']);	
		
				if($rs)
				{
					$rs = order_paid($payment_notice['order_id']);				
					if($rs)
					{
						//开始更新相应的outer_notice_sn					
						$GLOBALS['db']->query("update ".DB_PREFIX."payment_notice set outer_notice_sn = '".$outer_notice_sn."' where id = ".$payment_notice['id']);
						
						// 记录日志文件
				                require_once APP_ROOT_PATH."system/utils/logger.php";
				                    $log = array(
				                        'type' => 'payment',
				                        'user_name' => $_SESSION['fanweuser_info']['user_name'],
				                        'money' => $payment_notice['money'],
				                        'notice_sn' => $payment_notice['notice_sn'],
				                        'outer_notice_sn' => $outer_notice_sn,
				                        'path' =>  __FILE__,
				                        'merSignMsg' => $merSignMsg,
		                                'signMsg' => $signMsg,
				                        'function' => 'response',
				                        'msg' => '先锋金融支付成功',
				                        'time' => time(),
				                    );
				                    logger::wLog($log);
						
						if($request['notify'] == "1"){
                                                    echo "SUCCESS";
                                                    exit;                                                    
                                                }
						if($order_info['type']==0)
						return app_redirect($domain_url.url("index","payment#done",array("id"=>$payment_notice['order_id']))); //支付成功
						else
						return app_redirect($domain_url.url("index","payment#incharge_done",array("id"=>$payment_notice['order_id']))); //支付成功
					}
					else 
					{
						#if($bType=="2"){echo "success";	exit;}
						if($order_info['pay_status'] == 2)
						{
							if($order_info['type']==0)
							return app_redirect($domain_url.url("index","payment#done",array("id"=>$payment_notice['order_id']))); //支付成功
							else
							return app_redirect($domain_url.url("index","payment#incharge_done",array("id"=>$payment_notice['order_id']))); //支付成功
						}
						else
						return app_redirect($domain_url.url("index","payment#pay",array("id"=>$payment_notice['id']))); 
					}
				}
				else
				{
					#if($bType=="2"){echo "success";	exit;}
					return app_redirect($domain_url.url("index","payment#pay",array("id"=>$payment_notice['id']))); 
				}
			}
			else
			{
			        // 记录日志文件
		                require_once APP_ROOT_PATH."system/utils/logger.php";
		                    $log = array(
		                        'type' => 'payment',
		                        'user_name' => $_SESSION['fanweuser_info']['user_name'],
		                        'money' => $payment_notice['money'],
		                        'notice_sn' => $payment_notice['notice_sn'],
		                        'outer_notice_sn' => $outer_notice_sn,
		                        'path' =>  __FILE__,
		                        
		                        'function' => 'response',
		                        'msg' => '先锋金融支付失败',
		                        'time' => time(),
		                    );
		                    logger::wLog($log);
                            showTip('');
                // showErr($GLOBALS['payment_lang']["PAY_FAILED"]);
			}
		}else{
		    
		    // 记录日志文件
                    require_once APP_ROOT_PATH."system/utils/logger.php";
                    $log = array(
                        'type' => 'payment',
                        'user_name' => $_SESSION['fanweuser_info']['user_name'],
                        'money' => $payment_notice['money'],
                        'notice_sn' => $payment_notice['notice_sn'],
                        'outer_notice_sn' => $outer_notice_sn,
                        'path' =>  __FILE__,
                        'function' => 'response',
                        'merSignMsg' => $merSignMsg,
		                'signMsg' => $signMsg,
		                'tranData' => $tranData,
                        'msg' => '先锋金融支付失败,加密串不匹配.',
                        'time' => time(),
                    );
                    logger::wLog($log);
		    
		    showErr($GLOBALS['payment_lang']["PAY_FAILED"]);
		}
	}
	
	public function notify($request)
	{
		return false;
	}
	
	public function get_display_code()
	{
		$payment_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where class_name='Xfjr'");
		if($payment_item)
		{
			$html = "<div class='payment-type-container clearfix' id='payment-type-".$payment_item['id']."'>";
			if($payment_item['logo']!='')
			{
				$html .= "<div class='payment-type-logo'><img src='".APP_ROOT.$payment_item['logo']."' /></div>";
			}
			$html .= "<div class='payment-type-description'>".nl2br($payment_item['description'])."</div>";
			
			$html .= "<div style='clear:both;padding-top:10px;'>";
			$html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='BOC' />&nbsp;<img src='/public/images/bank/bc.gif' /></label></div>";
			$html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CMBCHINA' />&nbsp;<img src='/public/images/bank/cmbc.gif' /></label></div>";
			$html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CCB' />&nbsp;<img src='/public/images/bank/cbc.gif' /></label></div>";
			$html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='ABC' />&nbsp;<img src='/public/images/bank/nongye.gif' /></label></div>";
			$html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='ICBC' />&nbsp;<img src='/public/images/bank/acbc.gif' /></label></div>";
			$html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='BOCO' />&nbsp;<img src='/public/images/bank/jiaotong.gif' /></label></div>";
			$html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CUPC' />&nbsp;<img src='/public/images/bank/cupc.gif' /></label></div>";
			$html .= "</div></div>";
			
			return $html;
		}
		else
		{
			return '';
		}
	}
		

	function HmacMd5($data,$key)
	{
		// RFC 2104 HMAC implementation for php.
		// Creates an md5 HMAC.
		// Eliminates the need to install mhash to compute a HMAC
		// written by shihh
			
		//需要配置环境支持iconv，否则中文参数不能正常处理
		//$key = iconv("GB2312","UTF-8",$key);
		//$data = iconv("GB2312","UTF-8",$data);
			
		$b = 64; // byte length for md5
		if (strlen($key) > $b) {
			$key = pack("H*",md5($key));
		}
		$key = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;
			
		return md5($k_opad . pack("H*",md5($k_ipad . $data)));
	}
	
	
	function getMd5($data)
	{
		return strtoupper(md5($str));
	}
	
	function getHmacMd5($data,$key,$merchantId)
	{
		$enkey= $this->getMd5($key) + $merchantId;
		$enkey= $this->getMd5($enkey);
		return $this->HmacMd5($data,$enkey);
	}
	
	
	/* 跟据本地系统的订单号查询交易信息 
	    返回信息：
	*/
	public function queryOrd($ordid)
	{
	    require_once 'HttpClient.class.php';
	    
	    $payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Xfjr'");  
    	    $payment['config'] = unserialize($payment['config']);
    	    $queryOrdURL = trim($payment['config']['xfjr_query']);
    	    #print_r($payment['config']);
    	    
    	    
	    	$merchantId = $payment['config']['xfjr_account'];       // 商户编号
            $merchantKey = $payment['config']['xfjr_key'];           // 秘钥

            // 交易数据
            $tranData = "<?xml version=\"1.0\" encoding=\"GBK\"?><B2CReq><merId>{$merchantId}</merId><orderNo>{$ordid}</orderNo></B2CReq>";
            #echo $tranData;
            
            $base64_tranData = base64_encode($tranData);
            
            // 订单签名数据
            #$merSignMsg = $this->getHmacMd5($tranData, $merchantKey, $merchantId);
            $merSignMsg = $this->HmacMd5($tranData,$merchantKey);
            
            // 提交参数
            $params = array('interfaceName' => 'QueryOrder',
                'version' =>  'B2C1.0',
                'tranData'	=>  $base64_tranData,
                'merSignMsg' => $merSignMsg,
            	'merchantId' => $merchantId,
            );
            
            #echo $queryOrdURL;
            #print_r($params);
           
            
            $pageContents = HttpClient::quickPost($queryOrdURL, $params);
            #var_dump($pageContents);
            #exit;
            
            // 订单不存在
            /*
            if($pageContents == 'AMEROQ01')
            	return 50;
            */
            
            return $pageContents;
        }
}
?>
