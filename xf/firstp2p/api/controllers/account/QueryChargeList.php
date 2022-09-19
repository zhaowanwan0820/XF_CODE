<?php
/*
 *
 * @date 2014-08-21
 * @author xiaoan <zhaoxiaoan@ucfgroup.com>
 */

namespace api\controllers\account;


use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\dao\PaymentNoticeModel;

/**
 * 获取用户最近7天充值记录
 *
 *
 * Class queryChargeList
 * @package api\controllers\account
 */
class queryChargeList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['token'])) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $rspn = $this->rpc->local('UserLogService\get_charge_list', array($user['id']));
        //查询大额充值
        $largeOrders = $this->rpc->local('PaymentCheckService\queryLastDaysLargeOrders', array($user['id'], 7));

        $result = array();
        if (!empty($rspn)) {
            foreach ($rspn as $key =>$rv){
                $result[$key]['notice_sn'] = $rv['notice_sn'];
                // 基金格式化下订单数据
                if ($rv['platform'] == PaymentNoticeModel::PLATFORM_FUND_REDEEM)
                {
                    $fundInfo = explode(',', $rv['memo']);
                    $suffix = mb_strlen($fundInfo[1], 'UTF-8') > 10 ? '...' : '';
                    $fundTitle = mb_substr($fundInfo[1], 0, 10, 'UTF-8').$suffix;

                    $result[$key]['notice_sn'] = $rv['notice_sn'].','.$fundTitle;
                }
                $result[$key]['url'] = '';

                $result[$key]['status_cn'] = $rv['status_cn'];
                $result[$key]['pay_time'] = empty($rv['pay_time'])? '-' : to_date($rv['pay_time'],'Y-m-d H:i:s');
                $result[$key]['create_time'] = to_date($rv['create_time'],'Y-m-d H:i:s');
                $result[$key]['time'] = $rv['create_time'] + 28800;
                $result[$key]['money'] = format_price($rv['money'],false);
                $result[$key]['type_name'] = $rv['payment_id'] == PaymentNoticeModel::PAYMENT_YEEPAY ? '易宝支付' : '先锋支付';
                if (in_array($rv['platform'], [PaymentNoticeModel::PLATFORM_OFFLINE_V2, PaymentNoticeModel::PLATFORM_APPTOPC_CHARGE])) {
                    $result[$key]['type_name'] = '大额充值';
                }
                if (in_array($rv['platform'], [PaymentNoticeModel::PLATFORM_WEB])) {
                    $result[$key]['type_name'] = '网银收银台';
                }
            }
        }

        foreach ($largeOrders as $order) {
            //只取处理中和失败的订单
            if (!in_array($order['orderStatus'], ['I', 'F'])) {
                continue;
            }
            $temp = [];
            $temp['notice_sn'] = $order['outOrderId'];
            $temp['status_cn'] = $order['orderStatus'] == 'I' ? '处理中' : ( $order['orderStatus'] == 'F' ? '失败' : '成功' );
            $temp['url'] = '';
            if ($order['orderStatus'] == 'I') {
                $temp['url'] = $this->rpc->local('PaymentService\getLargeOrderInfoUrl', [['userId' => $user['id'], 'outOrderId' => $order['outOrderId'], 'returnUrl'=>'firstp2p://api?type=closeall']]);
            }
            $temp['pay_time'] = $order['gmtFinished'];
            $temp['create_time'] = $order['gmtCreate'];
            $temp['time'] = strtotime($order['gmtCreate']);
            $temp['money'] = bcdiv($order['amount'], 100, 2);
            $temp['type_name'] = '大额充值';
            $result[] = $temp;
        }

        //排序
        usort($result, function($a, $b) {
            return $b['time'] - $a['time'];
        });
        $this->json_data = $result;
    }

}

