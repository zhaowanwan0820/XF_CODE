<?php
/**
 * Created by PhpStorm.
 * User: jinhaidong
 * Date: 2018-06-11
 * Time: 21:59
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use libs\utils\Monitor;
use libs\utils\Alarm;
use libs\utils\Logger;

use openapi\controllers\BaseAction;
use openapi\conf\adddealconf\retail\RetailConf;
use openapi\conf\adddealconf\common\CommonConf;
use core\service\deal\add\AddDealService;
use core\service\deal\DealAgencyService;
use core\service\supervision\SupervisionService;

class AddDeal extends BaseAction {

    protected $data = array();

    public function init() {
        // 1 fpm重试请求会导致全局变量$_POST丢失，尝试从原始数据流中读取
        if (empty($_POST)) {
            parse_str(file_get_contents('php://input'), $post);
            if ($_POST = $post) {
                $_REQUEST = array_merge($post, $_REQUEST);
            }
        }

        // 2 存管降级不能上标
        if(SupervisionService::isServiceDown()){
            $this->setErr('ERR_SYSTEM_MAINTENANCE');
            return false;
        }

        parent::init();
        // 3 form-rule-验签
        $this->form = new Form();
        $confObj = RetailConf::instance($this->form);
        $this->form->rules = array_merge($this->sys_param_rules, $confObj->getSignRules());

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
   }

    public function invoke() {

        // 1 验证clientId
        $platform = CommonConf::getAllowPlatformClientId($_REQUEST['client_id']);
        if(empty($platform)){
            $this->setErr("ERR_PARAMS_ERROR", 'clientID非法');
        }
        // 2 form-rule-验证参数
        $validateForm = new Form();
        $confObj = RetailConf::instance($validateForm);
        $validateForm->rules = $confObj->getParams();
        if (!$validateForm->validate()) {
            $key = $validateForm->getErrorKey();
            if (!empty($key)){
                $this->errorCode = $key;
                $this->errorMsg = $validateForm->getErrorMsg();
                Logger::error('ANGLI', 'PtpBackend返回异常导致上标失败', $validateForm->getErrorMsg());
            }else{
                Logger::error('ANGLI', 'PtpBackend返回异常导致上标失败', $validateForm->getErrorMsg());
                $this->setErr('ERR_PARAMS_ERROR', $validateForm->getErrorMsg());
            }
            return false;
        }
        $this->data = $confObj->check->getFinalData();

        // 3 获取标的数据
        $res = $this->transferData($this->data);

        // 4 保存标的数据
        try{
            $service = new AddDealService($res['dealData']['approve_number']);
            $addResult = $service->addDeal($res['dealProjectData'],$res['dealData'],$res['dealExtData'],$res['otherData'],$res['dealExtraData']);
        }catch(\Exception $e){
            $this->errorCode = -100;
            $this->errorMsg = "上标失败 " . $e->getMessage();
            Alarm::push('ANGLI', $res['dealData']['approveNumber'].'PtpBackend参数错误导致上标失败', '. msg:' . $e->getMessage());
            Logger::error('ANGLI', $res['dealData']['approveNumber'].'PtpBackend参数错误导致上标失败', '. msg:' . $e->getMessage());
            return false;
        }
        if(!$addResult){
            $this->errorCode = 1;
            $this->errorMsg = 'insert dealProject failed';
            return false;
        }
        $this->errorCode = 0;
        $this->errorMsg = 'ok';
        $this->json_data = $addResult['dealId'];
    }

    protected function transferData($data){
        $data['approveNumber'] = !empty($data['relativeSerialno']) ? $data['relativeSerialno'] : $data['approveNumber'];
        $data['credit'] = !empty($data['credit']) ? $data['credit'] : $data['borrowAmount'];
        $projectData = array(
            'name' => $data['name'],
            'user_id' => $data['userId'],
            'approve_number' => $data['approveNumber'],
            'borrow_amount' => $data['borrowAmount'],
            'credit' => $data['credit'],
            'loantype' => $data['loanType'],
            'rate' => $data['rate'],
            'repay_time' => $data['repayPeriod'],
            'intro' => $data['projectInfoUrl'],
            'create_time' => get_gmtime(),
            'card_name' => $data['cardName'],
            'bankcard' => isset( $data['bankCard']) ?  $data['bankCard'] : '',
            'bankzone' => isset($data['bankZone']) ? $data['bankZone'] : '',
            'bank_id' => isset($data['bankId']) ? $data['bankId'] : 0,
            'loan_money_type' => $data['loanMoneyType'],
            'borrow_fee_type' => $data['loanFeeRateType'],
            'entrust_sign' => $data['entrustSign'],
            'status' => 0,
            'deal_type' => 0,
            'entrust_agency_sign' => $data['entrustAgencySign'],
            'entrust_advisory_sign' => $data['entrustAdvisorySign'],
            'product_class' => $data['productClass'],
            'product_name' => $data['productName'],
            'fixed_value_date' => isset($data['fixedValueDate']) ? $data['fixedValueDate'] : 0,//可能不需要该参数
            'card_type' => $data['cardType'],
            'risk_bearing' => $data['riskBearing'],
            'product_mix_1' => $data['productMix1'],
            'product_mix_2' => $data['productMix2'],
            'product_mix_3' => $data['productMix3'],
            'assets_desc' => '',
        );
        $dealData = array(
            'site_id' => 0,
            'agency_id' => $data['agencyId'],
            'pay_agency_id' => (new DealAgencyService())->getUcfPayAgencyId(),
            'deal_type' => 0,  //网贷
            'name' => $data['name'],
            'consult_fee_rate' => $data['consultFeeRate'],
            'packing_rate' => $data['packingRate'],
            'guarantee_fee_rate' => $data['guaranteeFeeRate'],
            'advance_agency_id' => $data['advanceAgencyId'],
            'deal_tag_name' => '',
            'deal_tag_desc' => '',
            'entrust_agency_id' => 0,
            'sub_name' => '',
            'cate_id' => 3,
            'manager' => '',
            'manager_mobile' => '',
            'description' => '',
            'is_effect' =>  0, //等待确认和无效的标的,再pc端不可见
            'is_delete' => 0,
            'sort' => 1,
            'icon_type' => 1,
            'enddate' => 20,
            'voffice' => 0,
            'vposition' => 0,
            'services_fee' => 0,
            'publish_wait' => 0,
            'is_send_bad_msg' => 0,
            'bad_msg' => '',
            'send_half_msg_time' => 0,
            'send_three_msg_time' => 0,
            'is_has_loans' => 0,
            'loantype' => $data['loanType'],
            'warrant' => $data['warrant'],
            'min_loan_money' => 100,
            'max_loan_money' => 0,
            'update_json' => '',
            'manage_fee_text' => '',
            'note' => '',
            'coupon_type' => 1,
            'approve_number' => $data['approveNumber'],
            'pay_fee_rate' => $data['annualPaymentRate'],
            'borrow_amount' => $data['borrowAmount'],
            'repay_time' => $data['repayPeriod'],
            'advisory_id' => $data['advisoryId'],
            'day' => $data['overdueDay'],
            'user_id' => $data['userId'],
            'type_id' => $data['typeId'],
            'loan_fee_rate' => $data['manageFeeRate'],
            'income_fee_rate' => $data['rateYields'],
            'rate' => $data['rateYields'],
            'prepay_rate' => $data['prepayRate'],
            'prepay_penalty_days' => $data['prepayPenaltyDays'],
            'prepay_days_limit' => $data['prepayDaysLimit'],
            'overdue_rate' => $data['overdueRate'],
            'overdue_day' => $data['overdueDay'],
            'contract_tpl_type' => $data['contractTplType'],
            'create_time' => get_gmtime(),
            'annual_payment_rate' => $data['annualPaymentRate'],
            'update_time' => 0,
            'generation_recharge_id' => $data['generationRechargeId'],
            'is_float_min_loan' => 0,
            'canal_agency_id' => isset($data['canalAgencyId']) ? $data['canalAgencyId'] : 0, //可能不需要该参数
            'canal_fee_rate' => isset( $data['canalFeeRate']) ?  $data['canalFeeRate'] : 0.00000, //可能不需要该参数
            'consult_fee_period_rate' => isset($data['consultFeePeriodRate']) ? $data['consultFeePeriodRate'] : 0,
            'product_class_type' => $data['productClassType'],
            'loan_user_customer_type' => $data['loanUserCustomerType'],
            'holiday_repay_type' => $data['holidayRepayType'],
        );
        $dealExtData = array(
            'income_base_rate' => $data['rateYields'],
            'leasing_contract_num' => '',
            'lessee_real_name' => '',
            'leasing_money' => 0.00,
            'entrusted_loan_entrusted_contract_num' => '',
            'entrusted_loan_borrow_contract_num' => '',
            'base_contract_repay_time' => 0,
            'leasing_contract_title' => '',
            'contract_transfer_type' => 0,
            'discount_rate' => $data['discountRate'],
            'line_site_id' => $data['lineSiteId'],
            'line_site_name' => $data['lineSiteName'],
            'guarantee_fee_rate_type' => $data['guaranteeFeeRateType'],
            'loan_application_type' => 0,
            'loan_fee_rate_type' => $data['loanFeeRateType'],
            'pay_fee_rate_type' => $data['payFeeRateType'],
            'consult_fee_rate_type' => $data['consultFeeRateType'],
            'overdue_break_days' => $data['overdueBreakDays'],
            'first_repay_interest_day' => isset($data['fixedReplay']) ? $data['fixedReplay'] : 0,
            'deal_name_prefix' => '',
            'pay_fee_ext' => '',
            'guarantee_fee_ext' =>'',
            'consult_fee_ext' => '',
            'loan_fee_ext' => isset($data['loanFeeExt']) ? $data['loanFeeExt'] : '',//可能不需要该参数
            'management_fee_ext' => '',
            'loan_type' => $data['extLoanType'],//默认值为0
            'use_info' => $data['loanApplicationType'],
            'canal_fee_ext' => '',
        );

        $dealExtraData = array(
            'create_time' => get_gmtime(),
            'recourse_user' => $data['recourseUser'],
            'recourse_time' => $data['recourseTime'],
            'recourse_type' => $data['recourseType'],
            'lawsuit_address' => $data['lawsuitAddress'],
            'arbitrate_address' => $data['arbitrateAddress'],
        );

        $otherData  = array(
            'platform_management' => array(
                'use_money' => isset($data['advisoryWarningUseMoney']) ? $data['advisoryWarningUseMoney'] : 0,
                'is_warning' => isset($data['advisoryWarningLevel']) ? $data['advisoryWarningLevel'] : 0,
                'advisory_id' => $data['advisoryId'],
            ),
            'product_management' => array(
                'use_money' => isset($data['productWarningUseMoney']) ? $data['productWarningUseMoney'] : 0,
                'is_warning' => isset($data['productWarningLevel']) ? $data['productWarningLevel'] : 0,
                'product_name' => $data['productName'],
            ),
            'repay_period' => $data['repayPeriod'],
            'repay_period_type' => $data['repayPeriodType'],
        );
        return array(
            'dealData' => $dealData,
            'dealProjectData' => $projectData,
            'dealExtData' => $dealExtData,
            'dealExtraData' => $dealExtraData,
            'otherData' => $otherData,
        );
    }
}
