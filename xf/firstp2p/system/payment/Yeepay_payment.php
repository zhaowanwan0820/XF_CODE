<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

$payment_lang = array(
    'name' => '易宝支付',
    'yeepay_account' => '商户编号',
    'yeepay_key' => '商户密钥',
);
$config = array(
    'yeepay_account' => array(
        'INPUT_TYPE' => '0',
    ), //商户编号
    'yeepay_key' => array(
        'INPUT_TYPE' => '0'
    ), //商户密钥
);
/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true) {
    $module['class_name'] = 'Yeepay';

    /* 名称 */
    $module['name'] = $payment_lang['name'];


    /* 支付方式：1：在线支付；0：线下支付 */
    $module['online_pay'] = '1';

    /* 配送 */
    $module['config'] = $config;

    $module['lang'] = $payment_lang;

    return $module;
}

// 易宝支付模型
require_once(APP_ROOT_PATH . 'system/libs/payment.php');
class Yeepay_payment implements payment
{

    public function get_payment_code($payment_notice_id, $pd_FrpId = "")
    {
        $pid = $_REQUEST['site'];
        $myproduct = $GLOBALS['sys_config']['PAYMENT_PID'][$pid];
        $payment_notice = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment_notice where id = " . $payment_notice_id);
        $order_sn = $GLOBALS['db']->getOne("select order_sn from " . DB_PREFIX . "deal_order where id = " . $payment_notice['order_id']);
        $money = round($payment_notice['money'], 2);
        $payment_info = $GLOBALS['db']->getRow("select id,config,logo from " . DB_PREFIX . "payment where id=" . intval($payment_notice['payment_id']));
        $payment_info['config'] = unserialize($payment_info['config']);


        $data_return_url = $GLOBALS['sys_config']['PAYMENT_PID']['firstp2p']['url'] . '/payment/response?class_name=Yeepay';
//		$data_return_url = get_domain().APP_ROOT.'/index.php?ctl=payment&act=response&class_name=Yeepay';


        $data_merchant_id = trim($payment_info['config']['yeepay_account']);
        $data_order_id = $payment_notice['notice_sn'];
        $data_amount = $money;
        $message_type = 'Buy';
        $data_cur = 'CNY';
        if (empty($myproduct)) {
            $product_id = '';
            $product_cat = '';
            $product_desc = '';
        } else {
            $product_id = $myproduct['name'];
            $product_cat = $myproduct['type'];
            $product_desc = $myproduct['desc'];
        }
        $address_flag = '0';


        $data_pay_key = trim($payment_info['config']['yeepay_key']);
        $data_pay_account = trim($payment_info['config']['yeepay_account']);
        $mct_properties = $payment_notice['notice_sn'];
        $def_url = $message_type . $data_merchant_id . $data_order_id . $data_amount . $data_cur . $product_id . $product_cat
            . $product_desc . $data_return_url . $address_flag . $mct_properties . $pd_FrpId;
        $MD5KEY = $this->HmacMd5($def_url, $data_pay_key);

        $product_id = iconv('UTF-8', 'GB2312', $myproduct['name']);
        $product_cat = iconv('UTF-8', 'GB2312', $myproduct['type']);
        $product_desc = iconv('UTF-8', 'GB2312', $myproduct['desc']);

        $code = "\n<form action='https://www.yeepay.com/app-merchant-proxy/node' method='post' accept-charset='GBK'>\n";
        $code .= "<input type='hidden' name='p0_Cmd' value='" . $message_type . "'>\n";
        $code .= "<input type='hidden' name='p1_MerId' value='" . $data_merchant_id . "'>\n";
        $code .= "<input type='hidden' name='p2_Order' value='" . $data_order_id . "'>\n";
        $code .= "<input type='hidden' name='p3_Amt' value='" . $data_amount . "'>\n";
        $code .= "<input type='hidden' name='p4_Cur' value='" . $data_cur . "'>\n";
        $code .= "<input type='hidden' name='p5_Pid' value='" . $product_id . "'>\n";
        $code .= "<input type='hidden' name='p6_Pcat' value='" . $product_cat . "'>\n";
        $code .= "<input type='hidden' name='p7_Pdesc' value='" . $product_desc . "'>\n";
        $code .= "<input type='hidden' name='p8_Url' value='" . $data_return_url . "'>\n";
        $code .= "<input type='hidden' name='p9_SAF' value='" . $address_flag . "'>\n";
        $code .= "<input type='hidden' name='pa_MP' value='" . $mct_properties . "'>\n";
        $code .= "<input type='hidden' name='pd_FrpId' value='" . $pd_FrpId . "'>\n";
        $code .= "<input type='hidden' name='pd_NeedResponse' value='1'>\n";
        $code .= "<input type='hidden' name='hmac' value='" . $MD5KEY . "'>\n";

        if (!empty($payment_info['logo']))
            $code .= "<input type='image' src='" . APP_ROOT . $payment_info['logo'] . "' style='border:solid 1px #ccc;'><div class='blank'></div>";

        $code .= "<input type='submit' class='paybutton' value='前往易宝在线支付'>";

        $code .= "</form>\n";


        $code .= "<br /><div style='text-align:center' class='red'>" . $GLOBALS['lang']['PAY_TOTAL_PRICE'] . ":" . format_price($money) . "</div>";

        return $code;

    }

    public function response($request)
    {
        $return_res = array(
            'info' => '',
            'status' => false,
        );
        $payment = $GLOBALS['db']->getRow("select id,config from " . DB_PREFIX . "payment where class_name='Yeepay'");
        $payment['config'] = unserialize($payment['config']);


        /* 检查数字签名是否正确 */
        $merchant_id = $payment['config']['yeepay_account']; // 获取商户编号
        $merchant_key = $payment['config']['yeepay_key']; // 获取秘钥

        $message_type = trim($request['r0_Cmd']);
        $succeed = trim($request['r1_Code']); // 获取交易结果,1成功,-1失败
        $trxId = trim($request['r2_TrxId']); //易宝的交易流水号

        $amount = trim($request['r3_Amt']); // 获取订单金额
        $cur = trim($request['r4_Cur']); // 获取订单货币单位
        $product_id = trim($request['r5_Pid']); // 获取产品ID
        $orderid = trim($request['r6_Order']); // 获取订单ID
        $userId = trim($request['r7_Uid']); // 获取产品ID
        $merchant_param = trim($request['r8_MP']); // 获取商户私有参数
        $bType = trim($request['r9_BType']); // 获取订单ID

        $mac = trim($request['hmac']); // 获取安全加密串

        ///生成加密串,注意顺序
        $ScrtStr = $merchant_id . $message_type . $succeed . $trxId . $amount . $cur . $product_id .
            $orderid . $userId . $merchant_param . $bType;
        if (isset($request['op']) && $request['op'] == 1) { // check=1,本地主动查询数据,用户点击"完成支付"
            $ScrtStr = $message_type . $succeed . $trxId . $amount . $cur . $product_id .
                $orderid . $merchant_param;
            $ScrtStr .= $request['rb_PayStatus'] . $request['rc_RefundCount'] . $request['rd_RefundAmt'];
        }
        $mymac = $this->HmacMd5($ScrtStr, $merchant_key, true);


        $payment_notice_sn = $orderid;
        $money = $amount;
        $outer_notice_sn = $trxId;

        // 或者是本地主动查询数据
        if (strtoupper($mac) == strtoupper($mymac))
        //if (strtoupper($mac) == strtoupper($mymac)  ||  $request['op']) // 本地主动查询的安全隐患
        {
            $myproduct = $GLOBALS['sys_config']['PAYMENT_PID'];
            $product_id = iconv("GB2312", "UTF-8", $product_id);
            foreach ($myproduct as $k =>$prow) {
                if ($prow['name'] == $product_id) {
                    $domain_url = 'http://'.$GLOBALS['sys_config']['SITE_DOMAIN'][$k];
                }
            }

            $payment_notice = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment_notice where notice_sn = '" . $payment_notice_sn . "'");
            $order_info = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "deal_order where id = " . $payment_notice['order_id']);

            if ($succeed == "1") #if(2=="1")
            {
                require_once APP_ROOT_PATH . "system/libs/cart.php";
                $rs = payment_paid($payment_notice['id']);

                if ($rs) {
                    $rs = order_paid($payment_notice['order_id']);
                    if ($rs) {
                        //开始更新相应的outer_notice_sn
                        $GLOBALS['db']->query("update " . DB_PREFIX . "payment_notice set outer_notice_sn = '" . $outer_notice_sn . "' where id = " . $payment_notice['id']);

                        // 记录日志文件
                        require_once APP_ROOT_PATH . "system/utils/logger.php";
                        $log = array(
                            'type' => 'payment',
                            'user_name' => $_SESSION['fanweuser_info']['user_name'],
                            'money' => $payment_notice['money'],
                            'notice_sn' => $payment_notice['notice_sn'],
                            'outer_notice_sn' => $outer_notice_sn,
                            'path' => __FILE__,
                            'function' => 'response',
                            'msg' => '易宝支付成功',
                            'time' => time(),
                        );
                        logger::wLog($log);

                        if ($bType == "2") {
                            echo "success";
                            exit;
                        }
                        if ($order_info['type'] == 0)
                            return app_redirect($domain_url . url("index", "payment#done", array("id" => $payment_notice['order_id']))); //支付成功
                        else
                            return app_redirect($domain_url . url("index", "payment#incharge_done", array("id" => $payment_notice['order_id']))); //支付成功
                    } else {
                        if ($bType == "2") {
                            echo "success";
                            exit;
                        }
                        if ($order_info['pay_status'] == 2) {
                            if ($order_info['type'] == 0)
                                return app_redirect($domain_url . url("index", "payment#done", array("id" => $payment_notice['order_id']))); //支付成功
                            else
                                return app_redirect($domain_url . url("index", "payment#incharge_done", array("id" => $payment_notice['order_id']))); //支付成功
                        } else
                            return app_redirect($domain_url . url("index", "payment#pay", array("id" => $payment_notice['id'])));
                    }
                } else {
                    if ($bType == "2") {
                        echo "success";
                        exit;
                    }
                    return app_redirect($domain_url . url("index", "payment#pay", array("id" => $payment_notice['id'])));
                }
            } else {
                // 记录日志文件
                require_once APP_ROOT_PATH . "system/utils/logger.php";
                $log = array(
                    'type' => 'payment',
                    'user_name' => $_SESSION['fanweuser_info']['user_name'],
                    'money' => $payment_notice['money'],
                    'notice_sn' => $payment_notice['notice_sn'],
                    'outer_notice_sn' => $outer_notice_sn,
                    'path' => __FILE__,
                    'function' => 'response',
                    'msg' => '易宝支付失败',
                    'time' => time(),
                );
                logger::wLog($log);

                $msgarr = array(
                    "title" => '易宝支付失败！',
                    "order_sn" => $order_info['order_sn'],
                    "user_id" => $GLOBALS['user_info']['user_id'],
                    "user_name" => $GLOBALS['user_info']['user_name'],
                    "mobile" => $GLOBALS['user_info']['mobile'],
                    "real_name" => $GLOBALS['user_info']['real_name'] ? $GLOBALS['user_info']['real_name'] : $GLOBALS['user_info']['user_name'],
                    "deal_total_price" => $payment_notice['money'],
                    "bank_id" => $order_info['bank_id'],
                    "create_time" => time(),
                );

                $this->send_warn_msg($msgarr);
                showErr($GLOBALS['payment_lang']["PAY_FAILED"]);
            }
        } else {

            // 记录日志文件
            require_once APP_ROOT_PATH . "system/utils/logger.php";
            $log = array(
                'type' => 'payment',
                'user_name' => $_SESSION['fanweuser_info']['user_name'],
                'money' => $payment_notice['money'],
                'notice_sn' => $payment_notice['notice_sn'],
                'outer_notice_sn' => $outer_notice_sn,
                'path' => __FILE__,
                'function' => 'response',
                'msg' => '易宝支付失败,加密串不匹配.',
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
        $payment_item = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment where class_name='Yeepay'");
        if ($payment_item) {
            $html = "<div class='payment-type-container clearfix' id='payment-type-" . $payment_item['id'] . "'>";
            if ($payment_item['logo'] != '') {
                $html .= "<div class='payment-type-logo'><img src='" . APP_ROOT . $payment_item['logo'] . "' /></div>";
            }
            $html .= "<div class='payment-type-description'>" . nl2br($payment_item['description']) . "</div>";

            $html .= "<div style='clear:both;padding-top:10px;'>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='ICBC-NET-B2C' />&nbsp;<img src='/images/bank/acbc.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CMBCHINA-NET-B2C' />&nbsp;<img src='/images/bank/cmbc.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='ABC-NET-B2C' />&nbsp;<img src='/images/bank/nongye.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CCB-NET-B2C' />&nbsp;<img src='/images/bank/cbc.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='BCCB-NET-B2C' />&nbsp;<img src='/images/bank/beijing.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='BOCO-NET-B2C' />&nbsp;<img src='/images/bank/jiaotong.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CIB-NET-B2C' />&nbsp;<img src='/images/bank/xingye.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='NJCB-NET-B2C' />&nbsp;<img src='/images/bank/nanjing.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CMBC-NET-B2C' />&nbsp;<img src='/images/bank/minsheng.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CEB-NET-B2C' />&nbsp;<img src='/images/bank/guangda.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='BOC-NET-B2C' />&nbsp;<img src='/images/bank/bc.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='PINGANBANK-NET' />&nbsp;<img src='/images/bank/pingan.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CBHB-NET-B2C' />&nbsp;<img src='/images/bank/buohai.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='HKBEA-NET-B2C' />&nbsp;<img src='/images/bank/dongya.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='NBCB-NET-B2C' />&nbsp;<img src='/images/bank/ningbo.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='ECITIC-NET-B2C' />&nbsp;<img src='/images/bank/ccb.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='SDB-NET-B2C' />&nbsp;<img src='/images/bank/sdbcl.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='GDB-NET-B2C' />&nbsp;<img src='/images/bank/gdb.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='SHB-NET-B2C' />&nbsp;<img src='/images/bank/shanghaibank.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='SPDB-NET-B2C' />&nbsp;<img src='/images/bank/spdb.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='POST-NET-B2C' />&nbsp;<img src='/images/bank/youzheng.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='BJRCB-NET-B2C' />&nbsp;<img src='/images/bank/nongcunshangye.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='CZ-NET-B2C' />&nbsp;<img src='/images/bank/zheshang.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='HZBANK-NET-B2C' />&nbsp;<img src='/images/bank/hangzhou.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='SRCB-NET-B2C' />&nbsp;<img width='154' height='33' src='/images/bank/shangnongshang.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='NCBBANK-NET-B2C' />&nbsp;<img src='/images/bank/nanyanbank.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='SCCB-NET-B2C' />&nbsp;<img src='/images/bank/hebei.gif' /></label></div>";
            $html .= "<div class='bank-container'><label><input type='radio' name='pd_FrpId' value='ZJTLCB-NET-B2C' />&nbsp;<img src='/images/bank/tailong.gif' /></label></div>";
            $html .= "</div></div>";

            return $html;
        } else {
            return '';
        }
    }

    //验证银行支付
    private function check_bank_pay($id,$flag = false) {
        $list = array();
        if(!empty($id)) {
        	$sql    = 'SELECT id,class_name , online_pay,name FROM  '.DB_PREFIX.'payment WHERE is_effect = 1 AND id = '.intval($id);
        	$result = $GLOBALS['db']->getRow($sql);
        	if(!empty($result) && $result['class_name'] == 'Yeepay') {//易宝支付
        	     return true;
        	}else 
        	    return false;
        }else
        	return false;
    }
    
    /**
     * 银行列表信息
     */
    public function get_bank_list(){
        $list   = array();
        $sql    = 'SELECT id,name,value,img,short_name,payment_id FROM  '.DB_PREFIX.'bank_charge WHERE STATUS = 0 order by id asc ';
        $result = $GLOBALS['db']->getAll($sql);
        if(!empty($result)) {
        	foreach ($result as $key=>$val) {
        		$list[$key] = array(  'name'=>$val['name'],
        		                      'short_name'=>$val['short_name'],
                                      'value'=>$val['value'] ,
                                      'image_name'=> !empty($val['img']) ? get_attr($val['img'],1,0) : '',
                                      'kuaijie'=>$this->check_bank_pay($val['payment_id']),
                                      'id'=>$val['id'],
        		);
        	}
        }
        //print_r(($list));exit;
        /* $list['ICBC']=array('name'=>'中国工商银行','value'=>'ICBC-NET-B2C' , 'image_name'=>'icbc_1301.png','kuaijie'=>true);
        $list['CMBCHINA']=array('name'=>'招商银行','value'=>'CMBCHINA-NET-B2C' , 'image_name'=>'cmb_1301.png','kuaijie'=>true);
        $list['ABC']=array('name'=>'中国农业银行','value'=>'ABC-NET-B2C' , 'image_name'=>'abc_1301.png','kuaijie'=>false);
        $list['CCB']=array('name'=>'中国建设银行','value'=>'CCB-NET-B2C' , 'image_name'=>'ccb_1301.png','kuaijie'=>true);
        $list['BCCB']=array('name'=>'北京银行','value'=>'BCCB-NET-B2C' , 'image_name'=>'bob_1301.png','kuaijie'=>false);
        $list['BOCO']=array('name'=>'交通银行','value'=>'BOCO-NET-B2C' , 'image_name'=>'bcom_1301.png','kuaijie'=>false);
        $list['CIB']=array('name'=>'兴业银行','value'=>'CIB-NET-B2C' , 'image_name'=>'cib_1301.png','kuaijie'=>true);
        $list['NJCB']=array('name'=>'南京银行','value'=>'NJCB-NET-B2C' , 'image_name'=>'njcb_1301.png','kuaijie'=>false);
        $list['CMBC']=array('name'=>'中国民生银行','value'=>'CMBC-NET-B2C' , 'image_name'=>'cmbc_1301.png','kuaijie'=>true);
        $list['CEB']=array('name'=>'中国光大银行','value'=>'CEB-NET-B2C' , 'image_name'=>'ceb_1301.png','kuaijie'=>false);
        $list['BOC']=array('name'=>'中国银行','value'=>'BOC-NET-B2C' , 'image_name'=>'boc_1301.png','kuaijie'=>true);
        $list['PINGANBANK']=array('name'=>'平安银行','value'=>'PINGANBANK-NET' , 'image_name'=>'pab_1301.png','kuaijie'=>true);
        $list['CBHB']=array('name'=>'渤海银行','value'=>'CBHB-NET-B2C' , 'image_name'=>'cbhb_1301.png','kuaijie'=>false);
        $list['HKBEA']=array('name'=>'东亚银行','value'=>'HKBEA-NET-B2C' , 'image_name'=>'bea_1301.png','kuaijie'=>false);
        $list['NBCB']=array('name'=>'宁波银行','value'=>'NBCB-NET-B2C' , 'image_name'=>'nbcb_1301.png','kuaijie'=>false);
        $list['ECITIC']=array('name'=>'中信银行','value'=>'ECITIC-NET-B2C' , 'image_name'=>'citic_1301.png','kuaijie'=>true);
        $list['SDB']=array('name'=>'深圳发展银行','value'=>'SDB-NET-B2C' , 'image_name'=>'sdb_1301.png','kuaijie'=>true);
        $list['GDB']=array('name'=>'广东发展银行','value'=>'GDB-NET-B2C' , 'image_name'=>'gdb_1301.png','kuaijie'=>true);
        $list['SHB']=array('name'=>'上海银行','value'=>'SHB-NET-B2C' , 'image_name'=>'shb_1301.png','kuaijie'=>false);
        $list['SPDB']=array('name'=>'上海浦东发展银行','value'=>'SPDB-NET-B2C' , 'image_name'=>'spdb_1301.png','kuaijie'=>true);
        $list['POST']=array('name'=>'中国邮政储蓄银行','value'=>'POST-NET-B2C' , 'image_name'=>'post_1301.png','kuaijie'=>true);
        $list['BJRCB']=array('name'=>'北京农村商业银行','value'=>'BJRCB-NET-B2C' , 'image_name'=>'bjb_1301.png','kuaijie'=>false);
        $list['CZ']=array('name'=>'浙商银行','value'=>'CZ-NET-B2C' , 'image_name'=>'zsb_1301.png','kuaijie'=>false);
        $list['HZBANK']=array('name'=>'杭州银行','value'=>'HZBANK-NET-B2C' , 'image_name'=>'hzb_1301.png','kuaijie'=>false);
        $list['SRCB']=array('name'=>'上海农村商业银行','value'=>'SRCB-NET-B2C' , 'image_name'=>'srcb_1301.png','kuaijie'=>false);
        $list['NCBBANK']=array('name'=>'南洋商业银行','value'=>'NCBBANK-NET-B2C' , 'image_name'=>'ncb_1301.png','kuaijie'=>false);
        $list['SCCB']=array('name'=>'河北银行','value'=>'SCCB-NET-B2C' , 'image_name'=>'hbb_1301.png','kuaijie'=>false);
        $list['ZJTLCB']=array('name'=>'浙江泰隆商业银行','value'=>'ZJTLCB-NET-B2C' , 'image_name'=>'zjb_1301.png','kuaijie'=>false);
         */
        return $list;
    }


    private function HmacMd5($data, $key, $iconv = false)
    {
        // RFC 2104 HMAC implementation for php.
        // Creates an md5 HMAC.
        // Eliminates the need to install mhash to compute a HMAC
        // Hacked by Lance Rushing(NOTE: Hacked means written)
        if ($iconv) {
            //需要配置环境支持iconv，否则中文参数不能正常处理
            $key = iconv("GB2312", "UTF-8", $key);
            $data = iconv("GB2312", "UTF-8", $data);
        }
        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }

    /* 跟据本地系统的订单号查询交易信息
        返回信息：
        r1_Code 查询结果  为“1”: 查询正常; 为“50”: 订单不存在.
        r2_TrxId 易宝支付交易流水号
        r3_Amt 支付金额
        r6_Order 商户订单号
        r8_MP 商户扩展信息
        rb_PayStatus 支付状态  “INIT” 未支付;“CANCELED” 已取消;“SUCCESS” 已支付.
        rc_RefundCount 已退款次数
        rd_RefundAmt 已退款金额
    */
    public function queryOrd($ordid)
    {
        require_once 'HttpClient.class.php';

        $payment = $GLOBALS['db']->getRow("select id,config from " . DB_PREFIX . "payment where class_name='Yeepay'");
        $payment['config'] = unserialize($payment['config']);
        #print_r($payment['config']);

        $queryOrdURL = "https://www.yeepay.com/app-merchant-proxy/command";

        $p1_MerId = $payment['config']['yeepay_account']; // 商户编号
        $merchantKey = $payment['config']['yeepay_key']; // 秘钥
        $p0_Cmd = "QueryOrdDetail"; // 接口类型
        $p2_Order = $ordid; // 本地订单号

        $da = $p0_Cmd . $p1_MerId . $p2_Order; // 必须按照这个顺序拼接
        $hmac = $this->HmacMd5($da, $merchantKey);
        $params = array('p0_Cmd' => $p0_Cmd,
            'p1_MerId' => $p1_MerId,
            'p2_Order' => $p2_Order,
            'hmac' => $hmac
        );

        $pageContents = HttpClient::quickPost($queryOrdURL, $params);

        // 把返回的字符串转换成数组
        $rest = str_replace("\n", '&', $pageContents);
        parse_str($rest, $result);

        return $result;
    }

    private function send_warn_msg($msg) {
        //给配置用户发送消息
        FP::import("libs.libs.msgcenter");
        $msgcenter = new Msgcenter();
        FP::import("libs.common.dict");

        //发邮件
        $email_arr = dict::get('PAY_WARN_EMAIL');
        if (count($email_arr) > 0) {
            foreach ($email_arr as $email) {
                $msgcenter->setMsg($email, 0, $msg, 'TPL_PAY_WARN_MAIL', $msg['title']);
            }
        }
    }
}

?>
