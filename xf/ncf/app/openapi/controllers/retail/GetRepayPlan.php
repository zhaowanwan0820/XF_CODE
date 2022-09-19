<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/9/15
 * Time: 13:50
 */
namespace openapi\controllers\retail;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\deal\DealService;
use core\service\repay\DealRepayService;
use libs\utils\Block;

class GetRepayPlan extends BaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "approveNumber" => array("filter" => "required", "message" => "approveNumber is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;

        /* 暂时屏蔽
        $checkCounts = Block::check('RETAIL_GETREPAYPLAN_DOWN_MINUTE','retail_getrepayplan_down_minute');//一分钟60次
        if ($checkCounts === false) {
            $this->setErr('ERR_MANUAL_REASON','请不要频繁发送请求');
            return false;
        }
        */

        if (empty($params['approveNumber'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'approveNumber不能为空');
            return false;
        }

        $dealService = new DealService();
        // 标的信息
        $ret = $dealService->getDealByApproveNumber($params['approveNumber']);
        if (empty($ret)) {
            $this->setErr("ERR_MANUAL_REASON", '没有查询到相关的标的记录');
            return false;
        }

        // 标的附加信息，包含"年化收益基本利率"
        $retExt = $dealService->getExtManualColumnsVal(intval($ret['id']), "income_base_rate");
        if (empty($retExt)) {
            $this->setErr("ERR_MANUAL_REASON", '没有查询到相关的标的的附加信息记录');
            return false;
        }
        if($ret['is_has_loans'] == 2){
            $this->setErr("ERR_IS_DURING_LOAN", '正在放款，还款计划还没有生成好');
            return false;
        }
        if (in_array($ret['deal_status'],array(4,5)) && ($ret['is_has_loans'] == 1)) {//还款计划只有是还款中
            $dealRepayService = new DealRepayService();
            $planList = \SiteApp::init()->dataCache->call($dealRepayService, 'getDealRepayListByDealId', array(intval($ret['id'])), 60);
            $result = $this->handlList($planList, $ret, $retExt);
        } else {
            $this->setErr("ERR_MANUAL_REASON", '该标的没有还款计划');
            return false;
        };
        $this->json_data = $result;
    }

    public function handlList($list, $ret, $retExt) {
        $loan_list = array();
        if (empty($list)) {
            return $loan_list;
        }
        foreach($list as $key => $value) {
            $loan_list[$key]['id'] = $value['id'];//还款ID
            $loan_list[$key]['deal_id'] = $value['deal_id'];//标的ID
            $loan_list[$key]['repay_day'] = to_date($value['repay_time'], 'Y-m-d');//当次最晚还款时间
            $loan_list[$key]['true_repay_time'] = to_date($value['true_repay_time'], 'Y-m-d');//当次最晚还款时间
            $loan_list[$key]['repay_type'] = $value['status'] == 0 ? number_format($value['repay_money'], 2) : 0;
            $loan_list[$key]['repay_money'] = $value['repay_money'];//还款金额
            $loan_list[$key]['status_text'] = $this->getLoanStatus($value['status']);
            $loan_list[$key]['principal'] = $value['principal'];//待还本金
            $loan_list[$key]['interest'] = $value['interest'];//待还利息
            $loan_list[$key]['loan_fee'] = $value['loan_fee'];//手续费
            $loan_list[$key]['consult_fee'] = $value['consult_fee'];//咨询费
            $loan_list[$key]['guarantee_fee'] = $value['guarantee_fee'];//担保费
            $loan_list[$key]['pay_fee'] = $value['pay_fee'];//支付服务费
            $loan_list[$key]['managment_fee'] = $value['management_fee'];//管理服务费
            $loan_list[$key]['consult_fee'] = $value['consult_fee'];//咨询费
            $loan_list[$key]['status'] = $value['status'];//状态 0待还,1准时 2逾期 3严重逾期 4提前
            $loan_list[$key]['impose_money'] = $value->feeOfOverdue();//罚息
            $loan_list[$key]['income_base_rate'] = $retExt['income_base_rate']; //年化收益基本利率
            $loan_list[$key]['loan_fee_rate'] = $ret['loan_fee_rate']; //借款平台手续费率
            $loan_list[$key]['pay_fee_rate'] = $ret['pay_fee_rate']; //年化支付服务费率
            $loan_list[$key]['guarantee_fee_rate'] = $ret['guarantee_fee_rate']; //年化借款担保费率
            $loan_list[$key]['consult_fee_rate'] = $ret['consult_fee_rate']; //年化借款咨询费率
        }
        return $loan_list;
    }
    private function getLoanStatus($status_id){
        $status = array(
            0 => '待还',
            1 => '准时还款',
            2 => '逾期还款',
            3 => '严重逾期',
            4 => '提前还款'
        );
        return $status[$status_id];
    }
}
