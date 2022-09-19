<?php

/**
 * CouponLog.php
 * @date 2014-10-08
 * @author longbo <longbo@ucfgroup.com>
 * 邀请记录接口，app3.3版本后使用
 */

namespace api\controllers\account;

use libs\web\Form;
use api\conf\Error;
use api\controllers\AppBaseAction;
use core\service\CouponService;
use core\service\CouponLogService;

class CouponLog extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "type" => array("filter" => "string", "message" => "type is required"),
            "dataType" => array("filter" => "string", "message" => "dataType is required"),
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
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
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $offset = isset($data['offset']) ? $data['offset'] : 0;
        $count = isset($data['count']) ? $data['count'] : 50;
        $type = $data['type'];
        $dataType = isset($data['dataType'])? $data['dataType']:0;
        $code = '';
        $type_access = array('p2p','reg','duotou','third');
        if ($type && array_search($type,$type_access) === false) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
        $couponLogService = new CouponLogService($type,$dataType);
        $result = $couponLogService->getLogPaid($type, $user['id'], $offset, $count, $code);
        $coupon_log_list = $result['data']['list'];

        //如果有type参数，则走新的逻辑，否则为了兼容以前的版本，还是以前的逻辑
        $res = array();
        if ($coupon_log_list) {
            if ($type) {
                if ($type == 'reg') {
                    foreach ($coupon_log_list as $item) {
                        $res[] = array(
                                'consume_real_name' => $item['consume_real_name'],
                                'mobile' => $item['mobile'],
                                'create_time' => $item['create_time'],
                                'note' => $item['create_time'],
                                'status_text' => $item['pay_status_text'],
                        );
                    }
                } else {
                    foreach ($coupon_log_list as $item) {
                        $res[] = array(
                                'consume_real_name' => $item['consume_real_name'],
                                'consume_user_name' => $item['consume_user_name'],
                                'type' => $item['type'],
                                'rebate_status' =>  $item['rebate_status'],
                                'status_text' =>  $item['pay_status_text'],
                                'note' =>  $item['note'],
                                'pay_time' => $item['pay_time'],
                                'create_time' => $item['create_time'],
                                'rebate_money' => $item['pay_money'],
                                'platform_info' => isset($item['platform_info'])? $item['platform_info']:'',
                                'is_compound' => ($item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND) ? 1 : 0,
                                'compound_count' => (!empty($item['count_pay'])) ? intval($item['count_pay']) : 0,
                                'compound_sum' => (!empty($item['sum_pay_refer_amount'])) ? number_format($item['sum_pay_refer_amount'],2) : '0.00',
                                'mobile' => (empty($item['mobile']))? '' : format_mobile($item['mobile']),
                        );
                    }
                }
            } else {
                foreach ($coupon_log_list as $item) {
                    $pay_money = number_format($item['referer_rebate_amount_2part'], 2);
                    $is_compound = ($item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND) ? 1 : 0;
                    $log_item = array();
                    $log_item['consume_real_name'] = $item['consume_real_name'];
                    $log_item['consume_user_name'] = $item['consume_user_name'];
                    $log_item['note'] =  $item['note'];
                    $log_item['rebate_status'] =  $item['rebate_status'];
                    $log_item['rebate_money'] = $pay_money;
                    $log_item['pay_time'] = $item['pay_time'];
                    $log_item['create_time'] = to_date($item['create_time']);
                    $log_item['type'] = $item['type'];
                    $log_item['is_compound'] = $is_compound;
                    $log_item['compound_count'] = intval($item['count_pay']);
                    $log_item['compound_sum'] = empty($item['sum_pay_refer_amount']) ? 0.00 : $item['sum_pay_refer_amount'];
                    $log_item['mobile'] = (empty($item['mobile']))? '' : format_mobile($item['mobile']);
                    $res[] = $log_item;
                }
            }
        }
        $this->json_data = $res;
    }

}
