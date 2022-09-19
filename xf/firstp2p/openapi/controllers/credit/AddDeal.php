<?php

/**
 * @abstract openapi  添加项目接口
 * @author gengkuan <gengkuan@ucfgroup.com>
 * @date 2018-11-02
 */

namespace openapi\controllers\credit;

use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\ConstDefine;
use libs\utils\Alarm;
use NCFGroup\Protos\Ptp\ProtoUser;
use core\service\ncfph\AccountService;
use core\dao\DealModel;
/**
 * 添加项目信息
 *
 * Class AddDeal
 * @package openapi\controllers\credit
 */
class AddDeal extends BaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "approve_number" => array("filter" => "required", "message" => "approve_number is required"),
            "name" => array("filter" => "required", "message" => "name is required"),
            "real_name" => array("filter" => "required", "message" => "real_name is required"),
            "idno" => array("filter" => "required", "message" => "idno is required"),
            "loan_type" => array("filter" => "required", "message" => "loan_type is required"),
            "repay_period" => array("filter" => "int", "message" => "repay_period is not int type"),
            "rate" => array("filter" => "float", "message" => "rate is not float type"),
            "credit" => array("filter" => "required", "message" => "credit is required"),
            "project_info_url" => array("filter" => "required", "message" => "project_info_url is required"),
            "deal_type" => array("filter" => "required", "message" => "deal_type is required"),
            "borrow_amount" => array("filter" => "required", "message" => "borrow_amount is required"),
            "advisory_id" => array("filter" => "required", "message" => "advisory_id is required"),
            "agency_id" => array("filter" => "int", "option"=>array("optional"=>true)),
            "type_id" => array("filter" => "required", "message" => "type_id is required"),
            "manage_fee_rate" => array("filter" => "float", "message" => "manage_fee_rate is not float type"),
            "guarantee_fee_rate" => array("filter" => "float", "option"=>array("optional"=>true)),
            "consult_fee_rate" => array("filter" => "float", "message" => "consult_fee_rate is not float type"),
            "prepay_rate" => array("filter" => "float", "message" => "prepay_rate is not float type"),
            "prepay_penalty_days" => array("filter" => "int", "message" => "prepay_penalty_days is not int type"),
            "prepay_days_limit" => array("filter" => "int", "message" => "prepay_days_limit is not int type"),
            "overdue_rate" => array("filter" => "float", "message" => "overdue_rate is not float type"),
            "overdue_day" => array("filter" => "int", "message" => "overdue_day is not int type"),
            "repay_period_type" => array("filter" => "int", "message" => "repay_period_type is not int type"),
            "contract_tpl_type" => array("filter" => "string", "message" => "contract_tpl_type is not string type"),
            "leasing_contract_num" => array("filter" => "string", "message" => "leasing_contract_num is not string type"),
            "lessee_real_name" => array("filter" => "string", "message" => "lessee_real_name is not string type"),

            "entrusted_loan_borrow_contract_num" => array("filter" => "string", "message" => "entrusted_loan_borrow_contract_num is not string type"),
            "entrusted_loan_entrusted_contract_num" => array("filter" => "string", "message" => "entrusted_loan_entrusted_contract_num is not string type"),
            "base_contract_repay_time" => array("filter" => "int", "message" => "base_contract_repay_time is not int type"),
            "overdue_break_days" => array("filter" => "int"),
            "consult_fee_rate_type" => array("filter" => "int", "message" => "consult_fee_rate_type is not int type"),

            "leasing_contract_title" => array("filter" => "string"),
            "contract_transfer_type" =>  array("filter" => "int"),
            "loan_application_type" => array("filter" => "required","message" => "loan_application_type is required"),
            "loan_money_type" => array("filter" => "required","message" => "loan_money_type is required"),
            "card_name" => array("filter" => "required","message" => "card_name is required"),
            "bankzone" => array("filter" => "required","message" => "bankzone is required"),
            "bankid" =>  array("filter" => "required","message" => "bankid is required"),
            "bankcard" => array("filter" => "required","message" => "bankcard is required"),
            "rate_yields" => array("filter" => "required", "message" => "rate_yields is required"),
            "entrust_sign" => array("filter" => "int"),
            "advance_agency_id" => array("filter" => "required", "message" => "advance_agency_id is required"),
            "entrust_agency_sign" => array("filter" => "int"),
            "entrust_advisory_sign" => array("filter" => "int"),
            "warrant" => array("filter" => "int", "option"=>array("optional"=>true)),
            "product_class" => array("filter" => "string"),
            "product_name" => array("filter" => "string"),
            "card_type" => array("filter" => "required", "message" => "card_type is required"),
            "user_name" => array("filter" => "required", "message" => "user_name is required"),
            "jys_id" => array("filter" => "int", "option"=>array("optional"=>true)),//交易所ID
            "jys_record_number" => array("filter" => "string", "option"=>array("optional"=>true)),//交易所备案产品号
            "discount_rate" => array("filter" => "required", "message" => "discount_rate is required"), //平台费折扣率
            "loan_fee_rate_type" => array("filter" => "int","message" => "loan_fee_rate_type is not float type"),
            "leasing_money" => array("filter" => "string"),
             "guarantee_fee_rate_type" => array("filter" => "string"),
             "assets_desc" => array("filter" => "string", "option"=>array("optional"=>true)),//基础资产描述
             "clearingType" => array("filter" => "required", "message" => "clearingType is required"),
             "ext_loan_type" => array("filter" => "required", "message" => "ext_loan_type is required"),//放款类型 数字0代表直接放款；数字1代表先计息后放款；数字2代表收费后放款'
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
        //只支持大金锁和专享的标
        $params['deal_type'] = intval( $params['deal_type']);
        if ($params['deal_type'] == 2) {
            if (empty($params['jys_id']) || empty($params['jys_record_number'])) {
                $this->setErr("ERR_PARAMS_ERROR", "jys_id,jys_record_number传参有误");
                return false;
            }
            $agencyInfo = $this->rpc->local('DealAgencyService\getDealAgency', array(intval($params['jys_id'])));
            if (empty($agencyInfo)) {
                $this->setErr("ERR_PARAMS_ERROR", "jys_id传参有误");
                return false;
            }
        }else if($params['deal_type'] == 3){
            //如果不是交易所，则agencyId必传
            if(empty($params['agency_id'])){
                $this->setErr("ERR_PARAMS_ERROR", "agency_id不能为空");
                return false;
            }
        }else{
            $this->setErr("ERR_PARAMS_ERROR", "deal_type只能为大金锁和专享的标");
            return false;
        }

        if(empty($params['repay_period'])){
            $this->setErr("ERR_PARAMS_ERROR", "repay_period 不能为空");
            return false;
        }
        if(!isset($params['repay_period_type'])){
            $this->setErr("ERR_PARAMS_ERROR", "repay_period_type 不能为空");
            return false;
        }
        if(!isset($params['rate'])){
            $this->setErr("ERR_PARAMS_ERROR", "rate 不能为空");
            return false;
        }
        if(!isset($params['prepay_rate'])){
            $this->setErr("ERR_PARAMS_ERROR", "prepay_rate 不能为空");
            return false;
        }
        if(!isset($params['prepay_penalty_days'])){
            $this->setErr("ERR_PARAMS_ERROR", "prepay_penalty_days 不能为空");
            return false;
        }
        if(!isset($params['prepay_days_limit'])){
            $this->setErr("ERR_PARAMS_ERROR", "prepay_days_limit 不能为空");
            return false;
        }
        if(!isset($params['overdue_rate'])){
            $this->setErr("ERR_PARAMS_ERROR", "overdue_rate 不能为空");
            return false;
        }
        if(!isset($params['overdue_day'])){
            $this->setErr("ERR_PARAMS_ERROR", "overdue_day 不能为空");
            return false;
        }
        if(empty($params['contract_tpl_type'])){
            $this->setErr("ERR_PARAMS_ERROR", "contract_tpl_type 不能为空");
            return false;
        }
        if(!isset($params['manage_fee_rate'])){
            $this->setErr("ERR_PARAMS_ERROR", "manage_fee_rate 不能为空");
            return false;
        }
        if(!isset($params['loan_fee_rate_type'])){
            $this->setErr("ERR_PARAMS_ERROR", "loan_fee_rate_type 不能为空");
            return false;
        }
        if(!isset($params['consult_fee_rate'])){
            $this->setErr("ERR_PARAMS_ERROR", "consult_fee_rate 不能为空");
            return false;
        }
        if(!isset($params['consult_fee_rate_type'])){
            $this->setErr("ERR_PARAMS_ERROR", "consult_fee_rate_type 不能为空");
            return false;
        }
        if(!isset($params['overdue_break_days'])){
            $this->setErr("ERR_PARAMS_ERROR", "overdue_break_days 不能为空");
            return false;
        }


        $userId = $this->checkCreditUserinfo($params);
        if ($userId == -1) {
            $this->errorCode = 2;
            $this->errorMsg  = '该企业不存在';
            return false;
        } elseif ($userId == -2) {
            $this->errorCode = 3;
            $this->errorMsg = '该企业未绑定银行卡';
            return false;
        }
        $params['user_name'] = htmlspecialchars(trim($params['user_name']));
        if (!isset($params['card_type']) || !in_array($params['card_type'], ConstDefine::$_CARD_TYPES)) {
            $this->setErr("ERR_PARAMS_ERROR", "card_type is error");
            return false;
        }
        //产品结构化校验产品名称是否存在有效
        $params['product_name'] = addslashes(trim($params['product_name']));
        $params['product_class'] = addslashes(trim($params['product_class']));
        $ProductNameCheck = $this->rpc->local('DealTypeGradeService\getAllLevelByName', array($params['product_class'], $params['product_name']));
        if (empty($ProductNameCheck['level2']) || empty($ProductNameCheck['level3'])) {
            $this->setErr("ERR_PARAMS_ERROR", "二级分类({$params['product_class']})和三级分类({$params['product_name']})无效");
            return false;
        }
        //二级分类id
        $productClassType = $ProductNameCheck['id2'];
        $riskBearing = $this->rpc->local('DealProjectRiskAssessmentService\getByScoreAssesment', array($ProductNameCheck['score']));
        if (!$riskBearing) {
            $riskBearing = array('id' => 0);
        }
        //代销分期处理,如果字段is_proxy_sale是代销分期，则loan_fee_rate_type必须为4，is_proxy_sale这个字段暂时是只作为是否为代销分期使用，暂不存库（已找产品确定）
        $loanFeeExt = '';
        //交易所判断,如果是交易所的标，交易所ID和交易所备案产品号，必传


        //业务处理开始
        $borrow_amount = doubleval($params['borrow_amount']);
        if (!$borrow_amount) {
            $this->setErr("ERR_PARAMS_ERROR", "borrow_amount is not double");
            return false;
        }

        $credit = doubleval($params['credit']);
        if (!$credit) {
            $this->setErr("ERR_PARAMS_ERROR", "credit is not double");
            return false;
        }


        // discount_rate 只能为0到100之间的数值，可以等于0,100
        if(!is_numeric($params['discount_rate']) || ($params['discount_rate'] < 0) || ($params['discount_rate'] > 100)){
           $this->setErr("ERR_PARAMS_ERROR", "discount_rate只能为0到100的数值");
           return false;
        }

        //是否是关联方
        $idno = trim($params['idno']);
        $ncfphAccountService = new AccountService();
        $isRelated = $ncfphAccountService->checkRelatedUser($idno,0);//只能是企业用户 所以写死
        if($isRelated == 1 ) {
            $this->setErr("ERR_PARAMS_ERROR", "该借款人为关联方，不能借款!");
            return false;
        } else if($isRelated == -1 )  {
            $this->setErr("ERR_PARAMS_ERROR", "查询关联方信息超时，请稍后再试!");
            return false;
        }

        $params['type_id']   = ($params['type_id'] == 'null') ? 1 : $params['type_id'];
        $params['leasing_contract_title']   = (string)$params['leasing_contract_title'];
        $params['overdue_break_days']   = (int)$params['overdue_break_days'];
        $params['guarantee_fee_rate_type'] = ($params['guarantee_fee_rate_type'] == 'null' || $params['guarantee_fee_rate_type'] == null) ? 1 : (int)$params['guarantee_fee_rate_type'];
        $params['loan_fee_rate_type'] = ($params['loan_fee_rate_type'] == 'null' || $params['loan_fee_rate_type'] == null) ? 1 : (int)$params['loan_fee_rate_type'];
        $params['loan_application_type'] = ($params['loan_application_type'] == 'null' || $params['loan_application_type'] == null) ? '' : (string)$params['loan_application_type'];
        $params['consult_fee_rate_type'] = ($params['consult_fee_rate_type'] == 'null' || $params['consult_fee_rate_type'] == null) ? 1 : (int)$params['consult_fee_rate_type'];
        $params['contract_transfer_type'] = ($params['contract_transfer_type'] == 'null' || $params['contract_transfer_type'] == null) ? 0 : (int)$params['contract_transfer_type'];
        $params['repay_period_type'] = ($params['repay_period_type'] == 'null' || $params['repay_period_type'] == null) ? 1 : (int)$params['repay_period_type'];
        $params['guarantee_fee_rate'] = isset($params['guarantee_fee_rate']) ? $params['guarantee_fee_rate'] : 0.000000;
        $params['consult_fee_rate']   = isset($params['consult_fee_rate']) ? $params['consult_fee_rate'] : 0.000000;
        $params['project_info_url']   = isset($params['project_info_url']) ? base64_decode(urldecode(str_replace('!_!', '%',$params['project_info_url']))) : '';
        $params['base_contract_repay_time'] = !empty($_REQUEST['base_contract_repay_time']) ? intval($_REQUEST['base_contract_repay_time']): 0;
        $params['lessee_real_name']     = !empty($_REQUEST['lessee_real_name']) ? $_REQUEST['lessee_real_name'] : '';
        $params['leasing_contract_num'] = isset($_REQUEST['leasing_contract_num']) ? $_REQUEST['leasing_contract_num'] : '';
        $params['leasing_money']        = isset($_REQUEST['leasing_money']) ? (float)$_REQUEST['leasing_money'] : 0.00;
        $params['entrusted_loan_borrow_contract_num']    = (empty($_REQUEST['entrusted_loan_borrow_contract_num']) || $_REQUEST['entrusted_loan_borrow_contract_num'] == 'null') ? '' : $_REQUEST['entrusted_loan_borrow_contract_num'];
        $params['entrusted_loan_entrusted_contract_num'] = (empty($_REQUEST['entrusted_loan_entrusted_contract_num']) || $_REQUEST['entrusted_loan_entrusted_contract_num'] == 'null') ? '' :$_REQUEST['entrusted_loan_entrusted_contract_num'];
        $params['contract_tpl_type']    = !empty($_REQUEST['contract_tpl_type']) ? $_REQUEST['contract_tpl_type'] : '';
        $params['loan_money_type']      = !empty($_REQUEST['loan_money_type']) ? (int)($_REQUEST['loan_money_type']) : 0;
        $params['card_name']            = !empty($_REQUEST['card_name']) ? $_REQUEST['card_name'] : '';
        $params['bankzone']             = !empty($_REQUEST['bankzone']) ? $_REQUEST['bankzone'] : '';
        $params['bankid']               = !empty($_REQUEST['bankid']) ? (int)($_REQUEST['bankid']) : 0;
        $params['bankcard']             = !empty($_REQUEST['bankcard']) ? $_REQUEST['bankcard'] : '';
        $params['entrust_sign']         = !empty($_REQUEST['entrust_sign']) ? $_REQUEST['entrust_sign'] : 0;
        $params['advance_agency_id']    = !empty($_REQUEST['advance_agency_id']) ? (int)$_REQUEST['advance_agency_id'] : 0;
        $params['entrust_agency_sign']  = !empty($_REQUEST['entrust_agency_sign']) ? (int)$_REQUEST['entrust_agency_sign'] : 0;
        $params['entrust_advisory_sign'] = !empty($_REQUEST['entrust_advisory_sign']) ? (int)$_REQUEST['entrust_advisory_sign'] : 0;
        $params['warrant']               = isset($_REQUEST['warrant']) ? (int)$_REQUEST['warrant'] : 2;
        $params['product_class']         = !empty($_REQUEST['product_class']) ? $_REQUEST['product_class'] : '';
        $params['product_name']          = !empty($params['product_name']) ? $params['product_name'] : '';
        $params['jys_id'] = isset($params['jys_id']) ? intval($params['jys_id']) : 0;
        $params['jys_record_number'] = isset($params['jys_record_number']) ? addslashes($params['jys_record_number']) : '';
        $params['assets_desc'] = isset($params['assets_desc']) ? addslashes($params['assets_desc']) : '';
        //交易所和专享标的起投金额判断，http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-5107
        $minLoanMoney = (in_array($params['deal_type'],array(2,3))) ? bcmul(ceil(bcdiv($borrow_amount, 200*1000,5)),1000,2) : 100;
        //是否启用浮动起投金额 0--否 1--是
        $isFloatMinLoan =  in_array($params['deal_type'],array(2,3)) ? 1 : 0;

        // 读取最低起投金额
        $jySminLoanMoney = (new DealModel())->getJYSMinLoanMony($params['jys_id']);
        if (!empty($minLoanMoney) && $isFloatMinLoan == DealModel::DEAL_FLOAT_MIN_LOAN_MONEY_YES && $minLoanMoney < $jySminLoanMoney){
            $minLoanMoney = $jySminLoanMoney;
        }
        $request = new \NCFGroup\Protos\Ptp\ProtoProjectDeal();
        try {
            $request->setApproveNumber($params['approve_number']);
            $request->setUserId((int)$userId);
            $request->setBorrowAmount($borrow_amount);
            $request->setProjectBorrowAmout($borrow_amount);
            $request->setCredit($credit);
            $request->setLoanType(intval($params['loan_type']));
            $request->setName($params['name']);
            $request->setProjectName($params['name']);
            $request->setRate($params['rate']);
            $request->setRepayReriod($params['repay_period']);
            $request->setRepayPeriodType($params['repay_period_type']);
            $request->setProjectInfoUrl($params['project_info_url']);
            $request->setDealType($params['deal_type']);
            $request->setAdvisoryId($params['advisory_id']);
            $request->setTypeId($params['type_id']);
            $request->setManageFeeRate($params['manage_fee_rate']);
            $request->setConsultFeeRate($params['consult_fee_rate']);
            $request->setPrepayRate($params['prepay_rate']);
            $request->setPrepayPenaltyDays($params['prepay_penalty_days']);
            $request->setPrepayDaysLimit($params['prepay_days_limit']);
            $request->setOverdueRate($params['overdue_rate']);
            $request->setOverdueDay($params['overdue_day']);
            $request->setLoanFeeRateType($params['loan_fee_rate_type']);
            $request->setContractTplType($params['contract_tpl_type']);
            $request->setLeasingContractNum($params['leasing_contract_num']);
            $request->setLesseeRealName($params['lessee_real_name']);
            $request->setLeasingMoney($params['leasing_money']);
            $request->setEntrustedLoanEntrustedContractNum($params['entrusted_loan_entrusted_contract_num']);
            $request->setEntrustedLoanBorrowContractNum($params['entrusted_loan_borrow_contract_num']);
            $request->setBaseContractRepayTime($params['base_contract_repay_time']);
            $request->setOverdueBreakDays($params['overdue_break_days']);
            $request->setConsultFeeRateType($params['consult_fee_rate_type']);
            $request->setLeasingContractTitle($params['leasing_contract_title']);
            $request->setContractTransferType($params['contract_transfer_type']);
            $request->setLoanApplicationType($params['loan_application_type']);
            $request->setRateYields($params['rate_yields']);
            $request->setLoanMoneyType($params['loan_money_type']);
            $request->setCardName($params['card_name']);
            $request->setBankCard($params['bankcard']);
            $request->setBankZone($params['bankzone']);
            $request->setBankId($params['bankid']);
            $request->setEntrustSign($params['entrust_sign']);
            $request->setAdvanceAgencyId($params['advance_agency_id']);
            $request->setEntrustAgencySign($params['entrust_agency_sign']);
            $request->setEntrustAdvisorySign($params['entrust_advisory_sign']);
            $request->setIsCredit(1);
            $request->setProductClass($params['product_class']);
            $request->setProductName($params['product_name']);
            $request->setDealTagName('');
            $request->setDealTagDesc('');
            $request->setMinLoanMoney($minLoanMoney);
            $request->setMaxLoanMoney(0);
            $request->setBusinessLines('mulandaicn');
            $request->setIsEffect(1);
            $request->setCardType(intval($params['card_type']));
            $request->setRiskBearing(intval($riskBearing['id']));
            $request->setProductMix1($ProductNameCheck['level1']);
            $request->setProductMix2($ProductNameCheck['level2']);
            $request->setProductMix3($ProductNameCheck['level3']);
            $request->setLoanFeeExt($loanFeeExt);
            $request->setJysId($params['jys_id']);
            $request->setJysRecordNumber(addslashes($params['jys_record_number']));
            $request->setIsFloatMinLoan($isFloatMinLoan);
            $request->setDiscountRate($params['discount_rate']);
            $request->setClearingType(intval($params['clearingType']));
            $request->setExtLoanType(intval($params['ext_loan_type']));
            $request->setAssetsDesc($params['assets_desc']);
            $request->setAnnualPaymentRate(0);
            if(!empty($params['agency_id'])){
                $request->setAgencyId($params['agency_id']);
                $request->setGuaranteeFeeRateType($params['guarantee_fee_rate_type']);
                $request->setGuaranteeFeeRate($params['guarantee_fee_rate']);
                $request->setWarrant($params['warrant']);
            }else{
                // 为交易所，并且agency_id为空时,担保费类型默认为1-前收,其他为0
                $request->setGuaranteeFeeRateType(1);
                $request->setWarrant(0);
                $request->setAgencyId(0);
                $request->setGuaranteeFeeRate(0);
            }
            $request->setProductClassType($productClassType);
        } catch (\Exception $exc) {
            $this->errorCode = -100;
            $this->errorMsg = "api param set errors";
            return false;
        }
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpProjectDeal',
            'method' => 'addProjectDeal',
            'args' => $request
        ));
        $codeMsgList  = array('-1'=>'Project already exists',
                              '-2'=>'Project name already exists',
                              '1'=>'insert dealProject failed',
                              '-3'=>'该咨询机构的上标金额已超出平台限额，不能上标',
                              '-4'=>'该产品的上标金额已超出平台限额，不能上标',
                              '-5'=>'不在咨询机构的有效期内，不能上标',
                              '-6'=>'不在产品限额有效期内，不能上标',
                              '-8'=>'业务正在处理中',
        );
        $code = $response->resCode;
        while(in_array($code, array_keys($codeMsgList))){
            $this->errorCode = $code;
            $this->errorMsg  = $codeMsgList[$code];
            Alarm::push('SetProjectDealInfo', '一键上标返回异常导致上标失败', $this->errorMsg . ', approve_number:' . $params['approve_number']);
            return false;
        }

        $this->errorCode = 0;
        $this->errorMsg  = ($code == -7) ?   '该项目标的已经存在' : 'ok';
        $this->json_data = $response->dealId;
    }

    //检查用户信息
    public function checkCreditUserinfo($info_list){
        $request = new ProtoUser();
        $user_id = 0;
        $request->setIdno($info_list['idno']);
        $request->setUserTypes(1);//默认企业用户
        $request->setRealName($info_list['real_name']);
        $request->setUserName($info_list['user_name']);
        $userResponse = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpUser', 'method' => 'getUserInfoByINM', 'args' => $request));
        if($userResponse->resCode) return -1;
        $user_id = $userResponse->getUserId();
        unset($userResponse->resCode);
        $userBankResponse = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpUser', 'method' => 'getBankInfoByUserid', 'args' => $userResponse));
        if($userBankResponse->resCode) return -2;
        return $user_id ?: -1;
    }


}
