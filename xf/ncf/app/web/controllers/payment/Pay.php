<?php
/**
 * 个人中心充值操作
 * @author caolong<caolong@ucfgroup.com>
 */
namespace web\controllers\payment;

use libs\web\Form;
use web\controllers\BaseAction;
//use core\dao\PaymentModel;
//use core\dao\PaymentNoticeModel;
use libs\web\Url;
use core\service\user\UserService;
use core\service\user\UserBindService;

class Pay extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("get");
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'pd_FrpId'=>array('filter'=>'string'),
            'check'=>array('filter'=>'int'),
        );
        $this->form->validate();
    }


    public function invoke() {
        /**
         * 非企业用户增加支付平台开户校验
         */
        $addbankUrl = '/account/addbank';
        $isEnterprise = UserService::isEnterprise($GLOBALS['user_info']['id']);
        if( ! $isEnterprise)
        {
            $addbankUrl = '/deal/promptCompany';
            if(empty($GLOBALS['user_info']['payment_user_id'])){
                return $this->show_error('无法充值', '', 0, 0, '/account');
            }
        }

        //如果未绑定手机
        $checkBindCardRet = UserBindService::isBindBankCard($GLOBALS['user_info']['id']);
        if (false == $checkBindCardRet['ret'] && ($checkBindCardRet['respCode'] == UserBindService::STATUS_BINDCARD_IDCARD || $checkBindCardRet['respCode'] == UserBindService::STATUS_BINDCARD_MOBILE))
        {
            return $this->show_error('请先填写身份证信息', '', 0, 0, $addbankUrl);
        }

        $data     = $this->form->data;
        $pd_FrpId = $data['pd_FrpId'];
        if(empty($data['id'])) {
            return $this->show_error($GLOBALS['lang']['NOTICE_SN_NOT_EXIST'], "", 0, 0, APP_ROOT."/",1);
        }
        $payment_notice = PaymentNoticeModel::instance()->find($data['id']);

        if (empty($payment_notice) || $payment_notice['user_id'] != $GLOBALS['user_info']['id']) {
            return $this->show_error("当前访问发生问题，请稍后再试");
        }

        if($payment_notice['is_paid'] == 0) {
            $payment_notice['format_money']=format_price($payment_notice['money']);
            $this->tpl->assign("payment_id",$payment_notice['payment_id']);//支付平台ID
            $tip_action = Url::gene('payment','tip',array('id'=>$payment_notice['id']));

            $actionUrl = Url::gene('payment','payCheck',array('id'=>$payment_notice['id'],'check'=>1));
            $this->tpl->assign('actionUrl',$actionUrl);
            $reUrl = Url::gene('account', 'charge');
            $this->tpl->assign("reUrl",$reUrl);

            $payment_action= Url::gene('payment', 'startpay',array("id"=>$data['id'],'pd_FrpId'=>$pd_FrpId,'site'=>$GLOBALS['sys_config']['APP_SITE']),1);
            $this->tpl->assign("payment_action",$payment_action);
            $this->tpl->assign("payment_notice",$payment_notice);

            return;
        }
        else if($payment_notice['is_paid'] == 1) {
            return $this->show_success('充值成功', '', 0, 0, '/account/charge');
        }
        else if($payment_notice['is_paid'] == 2) {
            return $this->show_error("当前充值单为待支付状态，请重新充值", "",0,0,APP_ROOT."/account/charge");
        }
        else if($payment_notice['is_paid'] == 3) {
            return $this->show_error("当前充值单支付失败状态，请重新充值", "",0,0,APP_ROOT."/account/charge");
        }
    }

    /**
     * 旧的支付逻辑
     * @return boolean
     */
    public function oldInvoke()
    {
        $data = $this->form->data;
        $pd_FrpId = $data['pd_FrpId'];
        if (empty($data['id'])) {
            return $this->show_error($GLOBALS['lang']['NOTICE_SN_NOT_EXIST'], "", 0, APP_ROOT . "/", 1);
        }
        $payment_notice = PaymentNoticeModel::instance()->find($data['id']);
        if ($payment_notice['is_paid'] == 0) {
            $payment_info = PaymentModel::instance()->find($payment_notice['payment_id']);
            \FP::import("libs.payment." . $payment_info['class_name'] . "_payment");
            if ($payment_info['class_name'] == 'Yeepay' || $payment_info['class_name'] == 'Xfjr') {
                $pd_FrpId = str_replace("_", "-", $pd_FrpId);
            }

            $payment_notice['money'] = format_price($payment_notice['money']);
//$payment_action='//'.$GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p'].url("index","payment#startpay",array("id"=>$_REQUEST['id'],'pd_FrpId'=>$pd_FrpId,'site'=>$GLOBALS['sys_config']['APP_SITE']));
            $payment_class = $payment_info['class_name'] . "_payment";
            $payment_object = new $payment_class();
            $payment_code = $payment_object->get_payment_code($payment_notice['id'], $pd_FrpId);

            if (intval($data['check']) == 1) {
                /* 如果用户充值完后手动点页面上的已完成支付，但上面代码又检测充值失败则主动发送请求验证 */
                if ($payment_info['class_name'] == 'Yeepay') { // 只对易宝
                    $ordInfo = $payment_object->queryOrd($payment_notice['notice_sn']);

                    if ($ordInfo['r1_Code'] == 1 && $ordInfo['rb_PayStatus'] == 'SUCCESS') { // 如果查询正常且用户已支付
                        $ordInfo['op'] = 1;
                        $payment_object->response($ordInfo);
                    }
                } elseif ($payment_info['class_name'] == 'Xfjr') { //先锋支付
                    $tranData = $payment_object->queryOrd($payment_notice['notice_sn']);
                    $ordInfo = base64_decode($tranData);
                    $ordInfo = iconv("UTF-8", "GB2312//IGNORE", $ordInfo);
                    $ordInfo = simplexml_load_string(stripslashes($ordInfo));
// 0-“未支付”；1-“已支付”；2-“支付失败”
                    if ($ordInfo->tranStat == 1) { // 如果查询正常且用户已支付
                        $payment_object->response(array('tranData' => $tranData, 'op' => 1));
                    } else {
                        return $this->show_tips("订单正在处理，可能有5-30分钟延迟，请耐心等待。", "订单处理中");
                    }
                }
                return $this->show_error($GLOBALS['lang']['PAYMENT_NOT_PAID_RENOTICE']);
            }
            $this->tpl->assign("payment_id", $payment_notice['payment_id']); //支付平台ID
            $this->tpl->assign("page_title", $GLOBALS['lang']['PAY_NOW']);
            $this->tpl->assign("payment_code", $payment_code);
            $tip_action = Url::gene('payment', 'tip', array('id' => $payment_notice['id']));

            $actionUrl = Url::gene('payment', 'pay', array('id' => $payment_notice['id'], 'check' => 1));
            $this->tpl->assign('actionUrl', $actionUrl);
            $reUrl = Url::gene('order', 'modify', array('id' => $payment_notice['order_id']));
            $this->tpl->assign("reUrl", $reUrl);

            if ($payment_info['class_name'] == 'Yeepay') {
                $site = 'firstp2p'; //易宝只支持callback回firstp2p
            } else {
                $site = $GLOBALS['sys_config']['APP_SITE'];
            }


            $payment_action = '//' . $GLOBALS['sys_config']['SITE_DOMAIN'][$site] . Url::gene('payment', 'startpay', array("id" => $data['id'], 'pd_FrpId' => $pd_FrpId, 'site' => $GLOBALS['sys_config']['APP_SITE']), 1);
            $this->tpl->assign('redict_url', Url::gene('order', 'modify', array("id" => $payment_notice['id'],)));
            $this->tpl->assign('tip_action', $tip_action);
            $this->tpl->assign("payment_notice", $payment_notice);
            $this->tpl->assign("payment_action", $payment_action);

            $this->template = "web/views/payment/old_pay.html";
            return;
        }
        else if($payment_notice['is_paid'] == 1) {
            return $this->show_success('充值成功', '', 0, 0, '/account/charge');
        }
        else if($payment_notice['is_paid'] == 2) {
            return $this->show_error("当前充值单为待支付状态，请重新充值", "",0,0,APP_ROOT."/account/charge");
        }
        else if($payment_notice['is_paid'] == 3) {
            return $this->show_error("当前充值单支付失败状态，请重新充值", "",0,0,APP_ROOT."/account/charge");
        }
    }
}
