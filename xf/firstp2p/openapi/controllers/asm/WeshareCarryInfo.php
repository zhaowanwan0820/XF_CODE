<?php

/**
 * @abstract openapi  获取标的提现状态
 * @author xiaoan <xiaoan@ucfgroup.com>
 * @date 2016-05-03
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\UserCarryService;
use core\dao\UserCarryModel;
use core\service\DealLoanTypeService;
use core\service\DealService;
use core\dao\DealLoanTypeModel;
use libs\utils\Alarm;
use libs\utils\Logger;
use core\service\DealRepayService;
use core\service\SupervisionWithdrawService;

/**
 * 获取标的提现状态
 *
 * @package openapi\controllers\asm
 */
class WeshareCarryInfo extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "RelativeSerialno" => array("filter" => "required", "message" => "RelativeSerialno is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $data['RelativeSerialno'] = trim($data['RelativeSerialno']);
        if (empty($data['RelativeSerialno'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'RelativeSerialno错误');
            return false;
        }

        $deal_obj = new DealService();
        $deal_ret = $deal_obj->getDealByApproveNumber($data['RelativeSerialno'], 'id, type_id, is_delete, deal_status, repay_start_time');
        if (empty($deal_ret)) {
            $this->errorCode = 1;
            $this->errorMsg = "该标的不存在！";
            return false;
        }
        if (intval($deal_ret['is_delete']) == 1 || intval($deal_ret['deal_status']) == 3) {
            $this->errorCode = 5;
            $this->errorMsg = "该标的已经无效！";
            return false;
        }

        $type_id = (int) $deal_ret['type_id'];
        $deal_id = $deal_ret['id'];

        //$type_obj = new DealLoanTypeService();
        //$xffq_type_id = (int) $type_obj->getIdByTag(DealLoanTypeModel::TYPE_XFFQ);
        //$zzjr_type_id = (int) $type_obj->getIdByTag(DealLoanTypeModel::TYPE_ZHANGZHONG);
        //if (!in_array($type_id, array($xffq_type_id, $zzjr_type_id))) {
        //    $this->errorCode = 2;
        //    $this->errorMsg = '该标的类型暂不提供查询';
        //    return false;
        //}

        $dealRepayService = new DealRepayService();
        $isP2p = $deal_obj->isP2pPath(intval($deal_id));
        if ($isP2p) {
            $svWithdraw = new SupervisionWithdrawService();
            $userP2pCarryInfo = $svWithdraw->getLatestByDealId($deal_id);
            if (empty($userP2pCarryInfo)) {
                $this->errorCode = 2;
                $this->errorMsg = "没有满标";
                return false;
            }
            if ($userP2pCarryInfo['withdraw_status'] == 1) { //成功
                $this->errorCode = 0;
                $this->errorMsg = '提现成功';
                $this->json_data = [
                    'loanTime' => date('Y-m-d H:i:s', $userP2pCarryInfo['update_time']),
                    'repayTime' => date('Y-m-d H:i:s', $dealRepayService->getFinalRepayTimeByDealId($deal_id) + 28800),
                    'valueDate' => to_date($deal_ret['repay_start_time'], "Y-m-d"),
                        ];
                return false;
            } elseif ($userP2pCarryInfo['withdraw_status'] == 2) { //失败
                $this->errorCode = 4;
                $this->errorMsg = '提现失败';
                $this->json_data_err = ['withdrawOrderId' => $userP2pCarryInfo['out_order_id']];
                return false;
            } else {
                $this->errorCode = 3;
                $this->errorMsg = '处理中';
                return false;
            }
        }

        try {
            $userCarryService = new UserCarryService();
            $userCarryInfo = $userCarryService->getByDealIdStatus($deal_id);
        } catch (\Exception $e) {
            $this->setErr("ERR_PARAMS_ERROR", '参数不完整');
            return false;
        }

        if (empty($userCarryInfo)) {
            $this->errorCode = 2;
            $this->errorMsg = "没有满标";
            return false;
        }

        $carray_status = (int) $userCarryInfo['status'];
        if ($carray_status >= 0 || $carray_status <= 4) {
            $status_key_list = array(0 => 4, 1 => 5, 2 => 6, 3 => 7, 4 => 8);
            $status_val_list = array(4 => '运营待处理', 5 => '财务待处理', 6 => '运营拒绝', 7 => '批准', 8 => '财务拒绝');
            $code = $status_key_list[$carray_status];
            $msg = $status_val_list[$code];
        }

        $msg = '单号:' . $data['RelativeSerialno'];

        Logger::info('WESHARE_CARRY_INFO.' . $msg . ', carry_status:' . $carray_status . ', withdraw_status:' . $userCarryInfo['withdraw_status']);

        // 默认处理中
        $this->errorCode = 3;
        $this->errorMsg = '处理中';

        // 如果不是批准状态，直接返回处理中
        if ($code !== 7) {
            return false;
        }

        // 如果网信已批准，返回支付状态
        // 提现失败
        if ($userCarryInfo['withdraw_status'] == UserCarryModel::WITHDRAW_STATUS_FAILED) {
            $this->errorCode = 4;
            $this->errorMsg = '提现失败';
            $this->json_data_err = ['withdrawOrderId' => $userP2pCarryInfo['out_order_id']];
            return false;
        }

        // 成功
        if ($userCarryInfo['withdraw_status'] == UserCarryModel::WITHDRAW_STATUS_SUCCESS) {
            $this->errorCode = 0;
            $this->errorMsg='提现成功';
            $this->json_data = [
                'loanTime' => date('Y-m-d H:i:s', $userCarryInfo['withdraw_time'] + 28800),
                'repayTime' => date('Y-m-d H:i:s', $dealRepayService->getFinalRepayTimeByDealId($deal_id) + 28800),
            ];
            return false;
        }

        // 异常，返回处理中，报警
        if (!array_key_exists($userCarryInfo['withdraw_status'], UserCarryModel::$withdrawDesc)) {
            Alarm::push('WESHARE', '提现信息查询异常', $msg . ' 提现状态异常,withdraw_status:' . $userCarryInfo['withdraw_status']);
        }
    }
}
