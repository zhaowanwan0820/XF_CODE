<?php

/**
 * @abstract openapi  添加项目接口
 * @author zhaohui3 <zhaohui3@ucfgroup.com>
 * @date 2015-07-08
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\RequestUserMobile;
use core\dao\DealLoanTypeModel;
use core\service\DealLoanTypeService;
use openapi\conf\ConstDefine;
use core\dao\DealModel;
use core\service\ncfph\AccountService;
use app\models\service\Finance;
use libs\payment\supervision\Supervision;
use libs\utils\Alarm;

/**
 * 添加项目信息
 *
 * Class AddProjectInfo
 * @package openapi\controllers\asm
 */
class SetProjectDealInfo extends BaseAction
{
    private $user_class = array(1 => '企业', 2 => '个人');

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "access_token" => array("filter" => "required", "message" => "access_token is required"),
            "wx_open_id" => array("filter" => "string"),
            "approve_number" => array("filter" => "required", "message" => "approve_number is required"),
            "name" => array("filter" => "required", "message" => "name is required"),
            "real_name" => array("filter" => "required", "message" => "real_name is required"),
            "idno" => array("filter" => "required", "message" => "idno is required"),
            "mobile" => array("filter" => "string"),
            "loan_type" => array("filter" => "int", "message" => "loan_type is not int type"),
            "repay_period" => array("filter" => "int", "message" => "repay_period is not int type"),
            "rate" => array("filter" => "float", "message" => "rate is not float type"),
            "credit" => array("filter" => "required", "message" => "credit is required"),
            "project_info_url" => array("filter" => "required", "message" => "project_info_url is required"),
            "project_extrainfo_url" => array("filter" => "string", "message" => "project_extrainfo_url is not string type"),
            "deal_type" => array("filter" => "int", "message" => "deal_type is not int type"),
            "lock_period" => array("filter" => "int", "message" => "lock_period is not int type"),
            "redemption_period" => array("filter" => "int", "message" => "redemption_period is not int type"),
            "borrow_amount" => array("filter" => "required", "message" => "borrow_amount is required"),
            "advisory_id" => array("filter" => "required", "message" => "advisory_id is required"),
            "agency_id" => array("filter" => "int", "option"=>array("optional"=>true)),
            "type_id" => array("filter" => "required", "message" => "type_id is required"),
            "manage_fee_rate" => array("filter" => "float", "message" => "manage_fee_rate is not float type"),
            "annual_payment_rate" => array("filter" => "float", "message" => "annual_payment_rate is not float type"),
            "guarantee_fee_rate" => array("filter" => "float", "option"=>array("optional"=>true)),
            "consult_fee_rate" => array("filter" => "float", "message" => "consult_fee_rate is not float type"),
            "packing_rate" => array("filter" => "float", "message" => "packing_rate is not float type"),
            "prepay_rate" => array("filter" => "float", "message" => "prepay_rate is not float type"),
            "prepay_penalty_days" => array("filter" => "int", "message" => "prepay_penalty_days is not int type"),
            "prepay_days_limit" => array("filter" => "int", "message" => "prepay_days_limit is not int type"),
            "overdue_rate" => array("filter" => "float", "message" => "overdue_rate is not float type"),
            "overdue_day" => array("filter" => "int", "message" => "overdue_day is not int type"),
            "repay_period_type" => array("filter" => "int", "message" => "repay_period_type is not int type"),
            "contract_tpl_type" => array("filter" => "string", "message" => "contract_tpl_type is not string type"),
            "leasing_contract_num" => array("filter" => "string", "message" => "leasing_contract_num is not string type"),
            "lessee_real_name" => array("filter" => "string", "message" => "lessee_real_name is not string type"),
            "leasing_money" => array("filter" => "float", "message" => "leasing_money is not float type"),
            "entrusted_loan_borrow_contract_num" => array("filter" => "string", "message" => "entrusted_loan_borrow_contract_num is not string type"),
            "entrusted_loan_entrusted_contract_num" => array("filter" => "string", "message" => "entrusted_loan_entrusted_contract_num is not string type"),
            "base_contract_repay_time" => array("filter" => "string", "message" => "base_contract_repay_time is not string type"),
            "line_site_id" => array("filter" => "int"),
            "line_site_name" => array("filter" => "string"),
            "overdue_break_days" => array("filter" => "int"),
            "loan_fee_rate_type" => array("filter" => "int"),
            "consult_fee_rate_type" => array("filter" => "int"),
            "guarantee_fee_rate_type" => array("filter" => "int", "option"=>array("optional"=>true)),
            "pay_fee_rate_type" => array("filter" => "int"),
            "leasing_contract_title" => array("filter" => "string"),
            "contract_transfer_type" => array("filter" => "int"),
            "loan_application_type" => array("filter" => "string"),
            "loan_money_type" => array("filter" => "int"),
            "card_name" => array("filter" => "string"),
            "bankzone" => array("filter" => "string"),
            "bankid" => array("filter" => "int"),
            "bankcard" => array("filter" => "string"),
            "rate_yields" => array("filter" => "float", "message" => "rate_yields is required"),
            "entrust_sign" => array("filter" => "int"),
            "user_types" => array("filter" => "int"),
            "fixed_replay" => array("filter" => "int"),
            "advance_agency_id" => array("filter" => "int"),
            "entrust_agency_sign" => array("filter" => "int"),
            "entrust_advisory_sign" => array("filter" => "int"),
            "warrant" => array("filter" => "int", "option"=>array("optional"=>true)),
            "caved_bindcard" => array("filter" => "int"),
            "entrust_agency_id"=> array("filter"=>"int"),
            "product_class" => array("filter" => "string"),
            "product_name" => array("filter" => "string"),
            'entrust_investment_desc' => array("filter"=>"string"),
            'fixed_value_date' => array("filter"=>"int","option"=>array("optional"=>true)),
            "card_type" => array("filter" => "required", "message" => "card_type is required"),
            "is_proxy_sale" => array("filter" => "required", "message" => "is_proxy_sale is required"),//是否为代销项目，0： 否     1是
            "start_yield_rate" => array("filter" => "required", "message" => "start_yield_rate is required"),//零期年化收益率(非代销为0)
            "end_yield_rate" => array("filter" => "required", "message" => "end_yield_rate is required"),//尾期年化收益率(非代销为0)
            "user_name" => array("filter" => "string"),
            "generation_recharge_id" => array("filter" => "int", "option"=>array("optional"=>true)),//代充值机构id
            "assets_desc" => array("filter" => "string", "option"=>array("optional"=>true)),//基础资产描述
            "jys_id" => array("filter" => "int", "option"=>array("optional"=>true)),//交易所ID
            "jys_record_number" => array("filter" => "string", "option"=>array("optional"=>true)),//交易所备案产品号
            "ext_loan_type" => array("filter" => "int"),//放款类型 数字0代表直接放款；数字1代表先计息后放款；数字2代表收费后放款'
            "discount_rate" => array("filter" => "required", "message" => "discount_rate is required"), //平台费折扣率

            "chnAgencyId" => array("filter" => "int", 'option' => array('optional' => true)),
            "chnFeeRate" => array("filter" => "string", 'option' => array('optional' => true)),
            "chnFeeRateType" => array("filter" => "int", 'option' => array('optional' => true)),
            "otherBorrowing" => array("filter" => "string", 'option' => array('optional' => true)),
            "loanUserCustomerType" => array("filter" => "int", 'option' => array('optional' => true)),
            "clearingType" => array("filter" => "int", 'option' => array('optional' => true)),
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
        // 该接口不支持上网贷标的
        if($params['deal_type'] == 0){
            $this->setErr('ERR_PARAMS_ERROR','不支持deal_type为0,不支持网贷上标');
            return false;
        }

        if(Supervision::isServiceDown() && $params['deal_type'] == 0){ //存管降级不能上标
            $this->setErr('ERR_SYSTEM_MAINTENANCE');
            return false;
        }
        $user_types = isset($params['user_types']) ? (($params['user_types'] >2 || $params['user_types'] <1) ? 2 : (int) $params['user_types']) : 2;
        $caved_bindcard = (!empty($params['caved_bindcard']) && $params['caved_bindcard'] == 2) ? true : false;
        if(empty($params['wx_open_id']) && $user_types == 2 && !is_mobile($params['mobile'])) {
            $this->errorCode = 1;
            $this->errorMsg = "个人、代理人校验手机号必填";

            return false;
        }
        $params['ext_loan_type'] = isset($params['ext_loan_type']) ? intval($params['ext_loan_type']) : 0 ;
        $params['user_name'] = isset($params['user_name']) ? htmlspecialchars(trim($params['user_name'])) : '';
        if ($user_types == 1 && empty($params['user_name'])) {
            $this->setErr("ERR_PARAMS_ERROR", "user_name is required");
            return false;
        }
        if (!isset($params['card_type']) || !in_array($params['card_type'], ConstDefine::$_CARD_TYPES)) {
            $this->setErr("ERR_PARAMS_ERROR", "card_type is error");
            return false;
        }
        //产品结构化校验产品名称是否存在有效
        if (empty($params['product_name'])) {
            $this->setErr("ERR_PARAMS_ERROR", "product_name不能为空");
            return false;
        }

        //是否是关联方
        $idno = trim($params['idno']);
        if ($user_types == 2) {
            $userType = 1;
        } elseif ($user_types == 1) {
            $userType = 0;
        }
        $ncfphAccountService = new AccountService();
        $isRelated = $ncfphAccountService->checkRelatedUser($idno,$userType);
        if($isRelated == 1 ) {
            $this->setErr("ERR_PARAMS_ERROR", "该借款人为关联方，不能借款!");
            return false;
        } else if($isRelated == -1 )  {
            $this->setErr("ERR_PARAMS_ERROR", "查询关联方信息超时，请稍后再试!");
            return false;
        }

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
        if ($params['is_proxy_sale'] == 1) {
            //如果是代销分期，则零期年化收益率和尾期年化收益率之和应该等于年化借款手续费
            if (bccomp($params['manage_fee_rate'],bcadd($params['start_yield_rate'], $params['end_yield_rate'], 8),8) != 0) {
                $this->setErr("ERR_PARAMS_ERROR", "start_yield_rate,end_yield_rate传参有误");
                return false;
            }
            if ($params['loan_fee_rate_type'] != 4) {
                $this->setErr("ERR_PARAMS_ERROR", "loan_fee_rate_type传参有误");
                return false;
            }
            //根据还款方式，借款期限，计算需要拆分为多少期进行还款
            $repayTimes = DealModel::getRepayTimesByLoantypeAndRepaytime($params['loan_type'], $params['repay_period']);
            //根据还款方式将年化利率转换为期间利率
            $periodRate = Finance::convertToPeriodRate($params['loan_type'], $params['manage_fee_rate'], $params['repay_period']);
            $totalMoney = ceilfix(doubleval($params['borrow_amount']) * $periodRate / 100.0);
            $loanFirstFee = ceilfix($totalMoney * $params['start_yield_rate'] / $params['manage_fee_rate']);
            $loanLastFee = ceilfix($totalMoney - $loanFirstFee);
            $loanFeeExt = json_encode(array('0'=>$loanFirstFee,$repayTimes=>$loanLastFee));
        } else {
            if ($params['loan_fee_rate_type'] == 4) {
                $this->setErr("ERR_PARAMS_ERROR", "loan_fee_rate_type传参有误");
                return false;
            }
        }

        //交易所判断,如果是交易所的标，交易所ID和交易所备案产品号，必传
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
        }else{
            //如果不是交易所，则agencyId必传
            if(empty($params['agency_id'])){
                $this->setErr("ERR_PARAMS_ERROR", "agency_id不能为空");
                return false;
            }
        }
        $params['user_types'] = $user_types;
        $params['deal_type'] = empty($params['deal_type']) ? 0 : (($params['deal_type']>=0 && $params['deal_type']<=5) ? (int)$params['deal_type'] : 0);
        $userId = $this->checkCreditUser($params, $caved_bindcard);
        if ($userId == -1) {
            $this->errorCode = 2;
            $this->errorMsg  = '该' . $this->user_class[$user_types] . '不存在';
            return false;
        } elseif ($userId == -2) {
            $this->errorCode = 3;
            $this->errorMsg  = '该' . $this->user_class[$user_types] . '未绑定银行卡';
            return false;
        } elseif ($userId == -3) {
            $this->errorCode = 4;
            $this->errorMsg  = '该' . $this->user_class[$user_types] . '存管未开户';
            return false;
        } elseif ($userId == -4) {
            $this->errorCode = 5;
            $this->errorMsg  = ($params['user_types'] == 2) ? '个人用户在途借款本金不得大于20万元' : '企业用户在途借款本金不得大于100万元';
            return false;
        } elseif ($userId == -6) {
            $this->errorCode = 5;
            $this->errorMsg  = ($params['user_types'] == 2) ? '个人用户跨平台借款本金不得大于100万元' : '企业用户跨平台借款本金不得大于500万元';
            return false;
        } elseif ($userId == -5) {
            $this->errorCode = 4;
            $this->errorMsg  = '该' . $this->user_class[$user_types] . '存管非借款户';
            return false;
        }

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

        $params['type_id']   = ($params['type_id'] == 'null') ? 1 : $params['type_id'];
        $params['line_site_id']   = (int)$params['line_site_id'];
        $params['leasing_contract_title']   = (string)$params['leasing_contract_title'];
        $params['overdue_break_days']   = (int)$params['overdue_break_days'];
        $params['line_site_name'] = ($params['line_site_name'] == 'null' || $params['line_site_name'] == null) ? '' : (string)$params['line_site_name'];
        $params['guarantee_fee_rate_type'] = ($params['guarantee_fee_rate_type'] == 'null' || $params['guarantee_fee_rate_type'] == null) ? 1 : (int)$params['guarantee_fee_rate_type'];
        $params['loan_fee_rate_type'] = ($params['loan_fee_rate_type'] == 'null' || $params['loan_fee_rate_type'] == null) ? 1 : (int)$params['loan_fee_rate_type'];
        $params['loan_application_type'] = ($params['loan_application_type'] == 'null' || $params['loan_application_type'] == null) ? '' : (string)$params['loan_application_type'];
        $params['consult_fee_rate_type'] = ($params['consult_fee_rate_type'] == 'null' || $params['consult_fee_rate_type'] == null) ? 1 : (int)$params['consult_fee_rate_type'];
        $params['contract_transfer_type'] = ($params['contract_transfer_type'] == 'null' || $params['contract_transfer_type'] == null) ? 0 : (int)$params['contract_transfer_type'];
        $params['pay_fee_rate_type'] = ($params['pay_fee_rate_type'] == 'null' || $params['pay_fee_rate_type'] == null) ? 1 : (int)$params['pay_fee_rate_type'];
        $params['repay_period_type'] = ($params['repay_period_type'] == 'null' || $params['repay_period_type'] == null) ? 1 : (int)$params['repay_period_type'];
        $params['annual_payment_rate'] = ($params['annual_payment_rate'] == 'null' || $params['annual_payment_rate'] == null) ? 0.000000 : $params['annual_payment_rate'];
        $params['guarantee_fee_rate'] = isset($params['guarantee_fee_rate']) ? $params['guarantee_fee_rate'] : 0.000000;
        $params['packing_rate']       = isset($params['packing_rate']) ? $params['packing_rate'] : 0.000000;
        $params['consult_fee_rate']   = isset($params['consult_fee_rate']) ? $params['consult_fee_rate'] : 0.000000;
        $params['project_info_url']   = isset($params['project_info_url']) ? base64_decode(urldecode(str_replace('!_!', '%',$params['project_info_url']))) : '';
        $params['base_contract_repay_time'] = !empty($_REQUEST['base_contract_repay_time']) ? strtotime($_REQUEST['base_contract_repay_time']): 0;
        $params['lessee_real_name']     = !empty($_REQUEST['lessee_real_name']) ? $_REQUEST['lessee_real_name'] : '';
        $params['leasing_contract_num'] = isset($_REQUEST['leasing_contract_num']) ? $_REQUEST['leasing_contract_num'] : '';
        $params['leasing_money']        = isset($_REQUEST['leasing_money']) ? $_REQUEST['leasing_money'] : 0.00;
        $params['entrusted_loan_borrow_contract_num']    = (empty($_REQUEST['entrusted_loan_borrow_contract_num']) || $_REQUEST['entrusted_loan_borrow_contract_num'] == 'null') ? '' : $_REQUEST['entrusted_loan_borrow_contract_num'];
        $params['entrusted_loan_entrusted_contract_num'] = (empty($_REQUEST['entrusted_loan_entrusted_contract_num']) || $_REQUEST['entrusted_loan_entrusted_contract_num'] == 'null') ? '' :$_REQUEST['entrusted_loan_entrusted_contract_num'];
        $params['contract_tpl_type']    = !empty($_REQUEST['contract_tpl_type']) ? $_REQUEST['contract_tpl_type'] : '';
        $params['loan_money_type']      = !empty($_REQUEST['loan_money_type']) ? $_REQUEST['loan_money_type'] : 0;
        $params['card_name']            = !empty($_REQUEST['card_name']) ? $_REQUEST['card_name'] : '';
        $params['bankzone']             = !empty($_REQUEST['bankzone']) ? $_REQUEST['bankzone'] : '';
        $params['bankid']               = !empty($_REQUEST['bankid']) ? $_REQUEST['bankid'] : 0;
        $params['bankcard']             = !empty($_REQUEST['bankcard']) ? $_REQUEST['bankcard'] : '';
        $params['lock_period']          = !empty($_REQUEST['lock_period']) ? $_REQUEST['lock_period'] : 0;
        $params['entrust_sign']         = !empty($_REQUEST['entrust_sign']) ? $_REQUEST['entrust_sign'] : 0;
        $params['fixed_replay']         = !empty($_REQUEST['fixed_replay']) ? to_timespan((date('Y-m-d',(int)$_REQUEST['fixed_replay']) . ' 00:00:00')) : 0;
        $params['advance_agency_id']    = !empty($_REQUEST['advance_agency_id']) ? (int)$_REQUEST['advance_agency_id'] : 0;
        $params['entrust_agency_sign']  = !empty($_REQUEST['entrust_agency_sign']) ? (int)$_REQUEST['entrust_agency_sign'] : 0;
        $params['entrust_advisory_sign'] = !empty($_REQUEST['entrust_advisory_sign']) ? (int)$_REQUEST['entrust_advisory_sign'] : 0;
        $params['warrant']               = isset($_REQUEST['warrant']) ? (int)$_REQUEST['warrant'] : 2;
        $params['product_class']         = !empty($_REQUEST['product_class']) ? $_REQUEST['product_class'] : '';
        $params['product_name']          = !empty($params['product_name']) ? $params['product_name'] : '';
        $params['entrust_agency_id']     = !empty($_REQUEST['entrust_agency_id']) ? $_REQUEST['entrust_agency_id'] : 0;
        $params['entrust_investment_desc']  = !empty($_REQUEST['entrust_investment_desc']) ? base64_decode(urldecode(str_replace('!_!', '%',$_REQUEST['entrust_investment_desc']))) : '';
        //找产品确认的方案，信贷传过来是天数day，当天0点时间+day=固定起息日,如果传空则认为非1.75，反之是1.75，如果是0，则是当前0点时间
        $params['fixed_value_date'] = !($params['fixed_value_date'] === null || $params['fixed_value_date'] === '') ?
        (mktime(0,0,0,date("m"),date("d"),date("Y"))+intval($params['fixed_value_date'])*3600*24-date('Z')) : 0;
        $params['generation_recharge_id'] = !empty($params['generation_recharge_id']) ? intval($params['generation_recharge_id']) : 0;
        $params['assets_desc'] = isset($params['assets_desc']) ? addslashes($params['assets_desc']) : '';
        $params['jys_id'] = isset($params['jys_id']) ? intval($params['jys_id']) : 0;
        $params['jys_record_number'] = isset($params['jys_record_number']) ? addslashes($params['jys_record_number']) : '';

        //交易所和专享标的起投金额判断，http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-5107
        $minLoanMoney = (in_array($params['deal_type'],array(2,3))) ? bcmul(ceil(bcdiv($borrow_amount, 200*1000,5)),1000,2) : 100;

        //是否启用浮动起投金额 0--否 1--是
        $isFloatMinLoan =  in_array($params['deal_type'],array(2,3)) ? 1 : 0;

        // 读取最低起投金额 开启浮动起投的时候
        $jySminLoanMoney = (new DealModel())->getJYSMinLoanMony($params['jys_id']);
        if (!empty($minLoanMoney) && $isFloatMinLoan == DealModel::DEAL_FLOAT_MIN_LOAN_MONEY_YES && $minLoanMoney < $jySminLoanMoney){
            $minLoanMoney = $jySminLoanMoney;
        }

        $params['generation_recharge_id'] = (!empty($params['generation_recharge_id']) && $params['deal_type'] == 0) ? intval($params['generation_recharge_id']) : 0;
        $params['chnFeeRate']   = isset($params['chnFeeRate']) ? $params['chnFeeRate'] : 0.00000;

        $params['loanUserCustomerType']   = isset($params['loanUserCustomerType']) ? $params['loanUserCustomerType'] : 0;
        $params['clearingType'] = isset($params['clearingType']) ? $params['clearingType'] : 0;
        $request = new \NCFGroup\Protos\Ptp\ProtoProjectDeal();
        try {
            $request->setApproveNumber($params['approve_number']);
            $request->setUserId((int)$userId);
            $request->setBorrowAmount($borrow_amount);
            $request->setProjectBorrowAmout($borrow_amount);
            $request->setCredit($credit);
            $request->setLoanType($params['loan_type']);
            $request->setName($params['name']);
            $request->setProjectName($params['name']);
            $request->setRate($params['rate']);
            $request->setPackingRate($params['packing_rate']);
            $request->setRepayReriod($params['repay_period']);
            $request->setRepayPeriodType($params['repay_period_type']);
            $request->setProjectInfoUrl($params['project_info_url']);
            $request->setDealType($params['deal_type']);
            $request->setLockPeriod($params['lock_period']);
            $request->setRedemptionPeriod($params['redemption_period']);
            $request->setAdvisoryId($params['advisory_id']);
            $request->setTypeId($params['type_id']);
            $request->setManageFeeRate($params['manage_fee_rate']);
            $request->setConsultFeeRate($params['consult_fee_rate']);
            $request->setPrepayRate($params['prepay_rate']);
            $request->setPrepayPenaltyDays($params['prepay_penalty_days']);
            $request->setPrepayDaysLimit($params['prepay_days_limit']);
            $request->setOverdueRate($params['overdue_rate']);
            $request->setOverdueDay($params['overdue_day']);
            $request->setContractTplType($params['contract_tpl_type']);
            $request->setLeasingContractNum($params['leasing_contract_num']);
            $request->setLesseeRealName($params['lessee_real_name']);
            $request->setLeasingMoney($params['leasing_money']);
            $request->setEntrustedLoanEntrustedContractNum($params['entrusted_loan_entrusted_contract_num']);
            $request->setEntrustedLoanBorrowContractNum($params['entrusted_loan_borrow_contract_num']);
            $request->setBaseContractRepayTime($params['base_contract_repay_time']);
            $request->setAnnualPaymentRate($params['annual_payment_rate']);
            $request->setLineSiteId($params['line_site_id']);
            $request->setLineSiteName($params['line_site_name']);
            $request->setOverdueBreakDays($params['overdue_break_days']);
            $request->setLoanFeeRateType($params['loan_fee_rate_type']);
            $request->setConsultFeeRateType($params['consult_fee_rate_type']);
            $request->setPayFeeRateType($params['pay_fee_rate_type']);
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
            $request->setFixedReplay($params['fixed_replay']);
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
            $request->setEntrustAgencyId($params['entrust_agency_id']);
            $request->setEntrustInvestmentDesc($params['entrust_investment_desc']);
            $request->setFixedValueDate($params['fixed_value_date']);
            $request->setCardType(intval($params['card_type']));
            $request->setRiskBearing(intval($riskBearing['id']));
            $request->setProductMix1($ProductNameCheck['level1']);
            $request->setProductMix2($ProductNameCheck['level2']);
            $request->setProductMix3($ProductNameCheck['level3']);
            $request->setLoanFeeExt($loanFeeExt);
            $request->setGenerationRechargeId($params['generation_recharge_id']);
            $request->setJysId($params['jys_id']);
            $request->setJysRecordNumber(addslashes($params['jys_record_number']));
            $request->setAssetsDesc($params['assets_desc']);
            $request->setExtLoanType(intval($params['ext_loan_type']));
            $request->setIsFloatMinLoan($isFloatMinLoan);
            $request->setDiscountRate($params['discount_rate']);
            $request->setClearingType($params['clearingType']);
            if (!empty($params['chnAgencyId'])) {
                $request->setCanalAgencyId($params['chnAgencyId']);
                $request->setCanalFeeRate($params['chnFeeRate']);
                $request->setCanalFeeRateType($params['chnFeeRateType']);
            }
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
            $request->setLoanUserCustomerType($params['loanUserCustomerType']);
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

}
