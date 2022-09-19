<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

#error_reporting(E_ALL);
//class paymentModule extends SiteBaseModule
//{
//        public function startpay()
//        {
//        $pd_FrpId = $_REQUEST['pd_FrpId'];
//        $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".intval($_REQUEST['id']));
//
//        if($payment_notice)
//        {
//            $payment_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where id = ".$payment_notice['payment_id']);
//            FP::import("libs.payment.".$payment_info['class_name']."_payment");
//                        $payment_class = $payment_info['class_name']."_payment";
//                        $payment_object = new $payment_class();
//                        $payment_code = $payment_object->get_payment_code($payment_notice['id'], $pd_FrpId);
//            $GLOBALS['tmpl']->assign("payment_code",$payment_code);
//            $GLOBALS['tmpl']->display("page/payment_startpay.html");
//                        header("Content-Type:text/html;charset=gb2312");
//
//                }
//
//        }
//    public function pay()
//    {
//        $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".intval($_REQUEST['id']));
//
//        if($payment_notice)
//        {
//            if($payment_notice['is_paid'] == 0)
//            {
//                $payment_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where id = ".$payment_notice['payment_id']);
//                $order = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
//                if($order['pay_status']==2)
//                {
//                    if($order['after_sale']==0)
//                    {
//                        return app_redirect(url("shop","payment#done",array("id"=>$order['id'])));
//                        exit;
//                    }
//                    else
//                    {
//                        return showErr($GLOBALS['lang']['DEAL_ERROR_COMMON'],0,APP_ROOT."/",1);
//                    }
//                }
//                FP::import("libs.payment.".$payment_info['class_name']."_payment");
//
//                $pd_FrpId = $_REQUEST['pd_FrpId'];
//                if($payment_info['class_name'] == 'Yeepay'||$payment_info['class_name'] == 'Xfjr')
//                                {
//                                    $pd_FrpId = str_replace("_", "-", $pd_FrpId);
//                                }
//
//                                $payment_notice['money']=format_price($payment_notice['money']);
//                                $payment_action='//'.$GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p'].url("index","payment#startpay",array("id"=>$_REQUEST['id'],'pd_FrpId'=>$pd_FrpId,'site'=>$GLOBALS['sys_config']['APP_SITE']));
//                $payment_class = $payment_info['class_name']."_payment";
//                $payment_object = new $payment_class();
//                $payment_code = $payment_object->get_payment_code($payment_notice['id'], $pd_FrpId);
//                $GLOBALS['tmpl']->assign("payment_id",$payment_notice['payment_id']);//支付平台ID
//                $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['PAY_NOW']);
//                $GLOBALS['tmpl']->assign("payment_code",$payment_code);
//                $GLOBALS['tmpl']->assign("order",$order);
//                $GLOBALS['tmpl']->assign("payment_notice",$payment_notice);
//                $GLOBALS['tmpl']->assign("payment_action",$payment_action);
//                if(intval($_REQUEST['check'])==1)
//                {
//                        /* 如果用户充值完后手动点页面上的已完成支付，但上面代码又检测充值失败则主动发送请求验证 */
//                        if($payment_info['class_name'] == 'Yeepay')  // 只对易宝
//                        {
//                              $ordInfo = $payment_object->queryOrd($payment_notice['notice_sn']);
//
//                              if($ordInfo['r1_Code'] == 1 && $ordInfo['rb_PayStatus'] == 'SUCCESS') // 如果查询正常且用户已支付
//                              {
//                                      $ordInfo['op'] = 1;
//                                      $payment_object->response($ordInfo);
//                              }
//                        }
//                        elseif($payment_info['class_name'] == 'Xfjr') // 先锋金融
//                        {
//                            $tranData = $payment_object->queryOrd($payment_notice['notice_sn']);
//
//                            $ordInfo = base64_decode($tranData);
//                            $ordInfo = iconv("UTF-8","GB2312//IGNORE",$ordInfo);
//                            $ordInfo = simplexml_load_string(stripslashes($ordInfo));
//
//                            // 0-“未支付”；1-“已支付”；2-“支付失败”
//                            if($ordInfo->tranStat == 1) // 如果查询正常且用户已支付
//                            {
//                                $payment_object->response( array('tranData' => $tranData, 'op' => 1) );
//                            }
//                        }
//
//
//                    return showErr($GLOBALS['lang']['PAYMENT_NOT_PAID_RENOTICE']);
//                }
//                $GLOBALS['tmpl']->display("page/payment_pay.html");
//            }
//            else
//            {
//                $order = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
//                if($order['pay_status']==2)
//                {
//                    if($order['after_sale']==0)
//                    return app_redirect(url("shop","payment#done",array("id"=>$order['id'])));
//                    else
//                    return showErr($GLOBALS['lang']['DEAL_ERROR_COMMON'],0,APP_ROOT."/",1);
//                }
//                else
//                return showSuccess($GLOBALS['lang']['NOTICE_PAY_SUCCESS'],0,APP_ROOT."/",1);
//            }
//        }
//        else
//        {
//            return showErr($GLOBALS['lang']['NOTICE_SN_NOT_EXIST'],0,APP_ROOT."/",1);
//        }
//    }
//
//    /* 标记删除充值订单 */
//    public function delpay()
//    {
//        $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = '".intval($_REQUEST['id']) . "' and user_id = '".$GLOBALS['user_info']['id']."' ");
//
//        if($payment_notice)
//        {
//            $now = get_gmtime();
//            $sql = "update ".DB_PREFIX."deal_order set user_delete = 1, update_time = $now where id = '".$payment_notice['order_id']."' and pay_status != 2";
//
//            $GLOBALS['db']->query($sql);
//            return showSuccess('充值订单删除成功',0,url("shop","uc_money#incharge"),0);
//        }
//
//        return showErr('充值订单删除失败',0,url("shop","uc_money#incharge"),0);
//    }
//
//    public function tip()
//    {
//        $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".intval($_REQUEST['id']));
//        $GLOBALS['tmpl']->assign("payment_notice",$payment_notice);
//        $GLOBALS['tmpl']->display("page/payment_tip.html");
//    }
//
//    /**
//     * 2015.3.27已弃用
//     */
//    public function done()
//    {
//        $order_id = intval($_REQUEST['id']);
//        $order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$order_id);
//
//        $deal_ids = $GLOBALS['db']->getOne("select group_concat(deal_id) from ".DB_PREFIX."deal_order_item where order_id = ".$order_id);
//        if(!$deal_ids)
//        $deal_ids = 0;
//        $order_deals = $GLOBALS['db']->getAll("select d.* from ".DB_PREFIX."deal as d where id in (".$deal_ids.")");
//
//        $GLOBALS['tmpl']->assign("order_info",$order_info);
//        $GLOBALS['tmpl']->assign("order_deals",$order_deals);
//        $is_coupon = 0;
//        $send_coupon_sms = 0;
//        foreach($order_deals as $k=>$v)
//        {
//            if($v['is_coupon'] == 1&&$v['buy_status']>0)
//            {
//                $is_coupon = 1;
//                break;
//            }
//        }
//
//        foreach($order_deals as $k=>$v)
//        {
//            if($v['forbid_sms'] == 0)
//            {
//                $send_coupon_sms = 1;
//                break;
//            }
//        }
//
//        $is_lottery = 0;
//        foreach($order_deals as $k=>$v)
//        {
//            if($v['is_lottery'] == 1&&$v['buy_status']>0)
//            {
//                $is_lottery = 1;
//                break;
//            }
//        }
//
//        $GLOBALS['tmpl']->assign("is_lottery",$is_lottery);
//        $GLOBALS['tmpl']->assign("is_coupon",$is_coupon);
//        $GLOBALS['tmpl']->assign("send_coupon_sms",$send_coupon_sms);
//        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['PAY_SUCCESS']);
//        //$GLOBALS['tmpl']->display("page/payment_done.html");
//
//        $refresh_time = empty($_REQUEST['autoCheck']) ? 3 : 30;
//
//        return showSuccess("恭喜，支付成功！", 0, APP_ROOT.'/uc_center', 0, array(), $refresh_time);
//    }
//
//    public function incharge_done()
//    {
//        $order_id = intval($_REQUEST['id']);
//        //$order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$order_id);
//        //$order_deals = $GLOBALS['db']->getAll("select d.* from ".DB_PREFIX."deal as d where id in (select distinct deal_id from ".DB_PREFIX."deal_order_item where order_id = ".$order_id.")");
//        //$GLOBALS['tmpl']->assign("order_info",$order_info);
//        //$GLOBALS['tmpl']->assign("order_deals",$order_deals);
//
//        //$GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['PAY_SUCCESS']);
//        //$GLOBALS['tmpl']->display("page/payment_done.html");
//        return showSuccess("恭喜，支付成功！",0,APP_ROOT.'/uc_center');
//    }
//
//    public function response()
//    {
//        //支付跳转返回页
//        if($GLOBALS['pay_req']['class_name'])
//            $_REQUEST['class_name'] = $GLOBALS['pay_req']['class_name'];
//
//        $class_name = addslashes(trim($_REQUEST['class_name']));
//        $payment_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where class_name = '".$class_name."'");
//        if($payment_info)
//        {
//            FP::import("libs.payment.".$payment_info['class_name']."_payment");
//            $payment_class = $payment_info['class_name']."_payment";
//            $payment_object = new $payment_class();
//            adddeepslashes($_REQUEST);
//            $payment_code = $payment_object->response($_REQUEST);
//        }
//        else
//        {
//            return showErr($GLOBALS['lang']['PAYMENT_NOT_EXIST']);
//        }
//    }
//
//    public function notify()
//    {
//        //支付跳转返回页
//        $class_name = addslashes(trim($_REQUEST['class_name']));
//        $payment_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where class_name = '".$class_name."'");
//        if($payment_info)
//        {
//            FP::import("libs.payment.".$payment_info['class_name']."_payment");
//            $payment_class = $payment_info['class_name']."_payment";
//            $payment_object = new $payment_class();
//            adddeepslashes($_REQUEST);
//            $payment_code = $payment_object->notify($_REQUEST);
//        }
//        else
//        {
//            return showErr($GLOBALS['lang']['PAYMENT_NOT_EXIST']);
//        }
//    }
//
//        function test()
//        {
//           #FP::import("libs.payment.Yeepay_payment");
//           #$payment_object = new Yeepay_payment();
//
//            FP::import("libs.payment.Xfjr_payment");
//            $payment_object = new Xfjr_payment();
//               $tranData = $payment_object->queryOrd('2013073006221955');
//               $tranData = base64_decode($tranData);
//               echo $tranData;
//               $tranData = iconv("UTF-8","GB2312//IGNORE",$tranData);
//               #var_dump($tranData);
//               $retXml = simplexml_load_string(stripslashes($tranData));
//               var_dump($retXml);
//        }
//
//}
?>
