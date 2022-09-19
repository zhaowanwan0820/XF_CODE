<?php
/**
 * Created by PhpStorm.
 * User: duxuefeng
 * Date: 2018/6/14
 * Time: 14:37
 */

namespace openapi\conf\adddealconf\retail;

use openapi\conf\adddealconf\retail\DealFormValidate;

class RetailConf {

    private $_params ;

    //错误码数据
    const ERR_PROJECT_ALREADY_EXISTS = -1;
    const ERR_PROJECT_NAME_ALREADY_EXISTS = -2;
    const ERR_INSERT_DEALPROJECT_FAILED = 1;
    const ERR_ADVISORY_OVER_LIMIT = -3;
    const ERR_PRODUCT_OVER_LIMIT = -4;
    const ERR_ADVISORY_OUT_OF_DATE = -5;
    const ERR_PRODUCT_OUT_OF_DATE = -6;
    const ERR_CONTRACT_BEFORE_BORROW_UNSIGN = -10;
    const ERR_CONTRACT_BEFORE_BORROW_TYPE = -11;
    const ERR_USER_AUTH_FAIL = 4;
    const ERR_USER_ERR = 3;
    const ERR_USER_NOT_EXIST = 2;
    const ERR_USER_OVER_LIMIT_MONEY = 5;
    const ERR_SYSTEM = 1001;
    const BANK_CARD_INCONFORMITY = 1002;
    const ERR_RELATED_USER = 1003;
    const ERR_RECOURSE_EMPTY = -12;


    public static $codeMsgList = array(
        RetailConf::ERR_PROJECT_ALREADY_EXISTS =>'Project already exists',
        RetailConf::ERR_PROJECT_NAME_ALREADY_EXISTS =>'Project name already exists',
        RetailConf::ERR_INSERT_DEALPROJECT_FAILED =>'insert dealProject failed',
        RetailConf::ERR_ADVISORY_OVER_LIMIT =>'该咨询机构的上标金额已超出平台限额，不能上标',
        RetailConf::ERR_PRODUCT_OVER_LIMIT =>'该产品的上标金额已超出平台限额，不能上标',
        RetailConf::ERR_ADVISORY_OUT_OF_DATE =>'不在咨询机构的有效期内，不能上标',
        RetailConf::ERR_PRODUCT_OUT_OF_DATE =>'不在产品限额有效期内，不能上标',
        RetailConf::ERR_CONTRACT_BEFORE_BORROW_UNSIGN =>'该标的未签署前置协议',
        RetailConf::ERR_CONTRACT_BEFORE_BORROW_TYPE =>'合同类型不一致',
        RetailConf::ERR_USER_AUTH_FAIL =>'p2p标的校验存管是否开户失败',
        RetailConf::ERR_USER_ERR =>'用户信息有误',
        RetailConf::ERR_USER_NOT_EXIST =>'该用户不存在',
        RetailConf::ERR_USER_OVER_LIMIT_MONEY =>'用户在途借款本金超过限额',
        RetailConf::ERR_SYSTEM =>'系统错误',
        RetailConf::BANK_CARD_INCONFORMITY => '银行卡与用户绑定银行卡不一致',
        RetailConf::ERR_RELATED_USER => '借款人为关联用户，不能上标',
        RetailConf::ERR_RECOURSE_EMPTY => '争议解决地点为空，不能上标',
    );

    public $check;

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }


    private function __construct($form){
        $this->check = $validateObj = new DealFormValidate($form);
        $this->_params = array(
            //****************************必传  验证取值范围**************************************************
            "typeId" => array("filter" => array($validateObj, "checkTypeId")),//产品类别（借款用途）
            "contractTplType" => array("filter" => array($validateObj, "contractTplTypeCheck")),//合同类型
            "repayPeriod" => array("filter" => array($validateObj, "greaterThanZero")), ///提款期限  不包含数字以外的字符  且大于0
            "consultFeeRateType" => array("filter" => array($validateObj, "greaterThanZero")),//年化借款咨询费类型
            "name" => array("filter" => array($validateObj, "notEmpty")),//项目名称（订单名称）
            "loanType" => array("filter" => array($validateObj, "greaterThanZero")),//还款方式
            "rate" => array("filter" => array($validateObj, "notLessThanZero")),//借款综合成本(年化)（年化打包费率） 大于等于0
            "manageFeeRate" => array("filter" => array($validateObj, "notLessThanZero")),//年化借款平台手续费
            "annualPaymentRate" => array("filter" => array($validateObj, "notLessThanZero")),//年化支付费率
            "prepayRate" => array("filter" => array($validateObj, "notLessThanZero")),//提前还款违约金系数
            "prepayPenaltyDays" => array("filter" => array($validateObj, "notLessThanZero")),//提前还款罚息天数
            "prepayDaysLimit" => array("filter" =>  array($validateObj, "notLessThanZero")),//提前还款限制（提前还款锁定天数(天)）
            "overdueRate" => array("filter" => array($validateObj, "notLessThanZero")),//逾期还款罚息系数
            "overdueDay" => array("filter" => array($validateObj, "notLessThanZero")),//代偿时间(天)
            "repayPeriodType" => array("filter" => array($validateObj, "greaterThanZero")),//借款期限类型(1:天,2:月，3年)
            //TODO repayPeriodType需要使用枚举值进行控制吗？
            "lineSiteId" => array("filter" => array($validateObj, "notLessThanZero")),//上线网站编号
            "lineSiteName" => array("filter" => array($validateObj, "notEmpty")),//上线网站名称
            "overdueBreakDays" => array("filter" => array($validateObj, "notLessThanZero")),//中途逾期强还天数
            "loanFeeRateType" => array("filter" => array($validateObj, "notLessThanZero")),//年化借款平台手续费类型
            "guaranteeFeeRateType" => array("filter" => array($validateObj, "notLessThanZero")),//年化借款担保费类型
            "payFeeRateType" => array("filter" => array($validateObj, "notLessThanZero")),//年化支付服务费
            "loanApplicationType" => array("filter" => array($validateObj, "notEmpty")),//借款用途分类
            "rateYields" => array("filter" => array($validateObj, "notLessThanZero")),//投资人收益率
            "holidayRepayType" => array("filter" => array($validateObj, "greaterThanZero")),//节假日还款类型
            "recourseType" => array("filter" => array($validateObj, "recourseCheck")),//争议解决方式


            //*********************************可选参数 可以设置默认值************************************************
            "projectInfoUrl" => array("filter" => array($validateObj, "optionalDecode")),//项目简介
            "agencyId" => array("filter" => array($validateObj, "agencyOptionalCheckAuth")),//担保机构id
            "advanceAgencyId" => array("filter" => array($validateObj, "agencyOptionalCheckAuth")),//代偿机构id
            "generationRechargeId" => array("filter" => array($validateObj, "agencyOptionalCheckAuth")),//代充值机构ID
            "cardName" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option' => ''),//开户人姓名 可选 如果未传，则默认值为''
            "consultFeeRate" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option' => 0.000000), //年化借款咨询费率
            "guaranteeFeeRate" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option' => 0.000000),//借款担保费
            "entrustSign" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option'  => 0),//借款合同是否委托签署，1：是；0：否
            "fixedReplay" => array("filter" => array($validateObj, "toGmtTime")),//固定还款日 可选 默认为0
            "entrustAgencySign" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option' => 0),//项目担保方合同是否被委托签署1：是；0：否
            "entrustAdvisorySign" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option'  => 0),//项目资产管理方合同是否被委托签署1：是；0：否
            "warrant" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option'  => 2),//担保范围,0:无 1:本金 2:本金和利息，3:有担保公司未指定4：第三方资产收购,默认为2
            "extLoanType" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option'  => 0),//放款类型 数字0代表直接放款；数字1代表先计息后放款；数字2代表收费后放款'
            "loanUserCustomerType" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option'  => 1),//借款客群 (1:普通消费者,2:小微企业,3:个体工商户4:自就业者)
            "consultFeePeriodRate" => array("filter" => array($validateObj, "optionalCheck")),//分期咨询费
            "lawsuitAddress" => array("filter" => array($validateObj, "optionalCheck")),//诉讼解决地点
            "arbitrateAddress" => array("filter" => array($validateObj, "optionalCheck")),//诉仲裁解决地点
            "recourseTime" => array("filter" => array($validateObj, "optionalCheck")),//追索款项偿付日
            "recourseUser" => array("filter" => array($validateObj, "optionalCheck")),//债务追索方
            //对公上标接口参数

            //**************approve_number relativeSerialno 2个参数关联校验  区分零售和对公*******
            "approveNumber" => array("filter" => 'string'), //对公-客户端业务主键
            "relativeSerialno" => array("filter" => array($validateObj, "isRetail")), //零售-客户端业务主键


            //*************** userType,idno,mobile,realName,userName,borrowAmount,otherBorrowing 7个参数关联校验*******
            "userName" => array("filter" => array($validateObj, "optionalCheck")),
            "mobile" => array("filter" => array($validateObj, "optionalCheck")), //手机号码 userType为2时必传
            "realName" =>  array("filter" => array($validateObj, "notEmpty")), //客户名称,
            "borrowAmount" => array("filter" => array($validateObj, "greaterThanZero")), //提款金额
            "otherBorrowing" => array("filter" => array($validateObj, "optionalCheck")),//其他平台借款额
            "idno" => array("filter" => array($validateObj, "notEmpty")), //身份证号码
            "userType" => array("filter" => array($validateObj, "checkCreditUser")), //借款人用户类型 1-公司 2-个人(零售信贷一般是个人) 可选，不传时默认 以2-个人的逻辑进行校验
            'bindBankCard' => array("filter" => array($validateObj, "bindBankCradCheck")),// 绑定的银行卡

            //***********loanMoneyType loanBankCard  bankShortName  cardType  bankNum 5个参数关联判断*************************
            "bankNum" => array("filter" => array($validateObj, "optionalCheck")),//银行联行号
            "loanBankCard" => array("filter" => array($validateObj, "optionalCheck")),//受托支付银行卡号
            "bankShortName" => array("filter" => array($validateObj, "optionalCheck")),//银行简码
            "cardType" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option'  => 0),//银行卡对公对私 default 0-对私 1-对公
            "loanMoneyType" => array("filter" => array($validateObj, "loanMoneyTypeIsEntrust")), //放款方式(1实际放款2非实际放款3受托支付)

            // *****************************productName productClass borrowAmount  3个参数关联验证****************************************************
            "productClass" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option'  => '消费贷'),// 产品类别 默认值“消费贷”
            "productName" => array("filter" => array($validateObj, "productStructuredValidation")),//产品名称

            // *****************************advisoryId  borrowAmount  2个参数关联验证****************************************************
            "advisoryId" => array("filter" => array($validateObj, "advisoryLimitCheck")),//咨询机构id 必传

            //*******************************对公上标接口需要，而零售上标接口欧不需要*********************************
            "credit" => array("filter" => array($validateObj, "optionalCheck")), //如果是对公，则credit必传
            "packingRate" => array("filter" => array($validateObj, "optionalCheckDefault"), 'option'  => 0.000000), //default 0.000000
            "discountRate" => array("filter" => array($validateObj, "discountRateCheck")), //平台费折扣率  0~100  default 100

        );

    }

    /**
     *
     * 不能使用单例模式,因为DealFormValidate的data和form都需要更新
     *
     */
    public static function instance($form){
        return new RetailConf($form);
    }

    /**
     *
     * 根据$this->_params新建用于验签的rule
     *
     */
    public function getSignRules(){
        $rules = array();
        foreach($this->_params as $k => $v){
            $rules[$k] = array('filter'=>'string', 'option'=>array('optional'=>true));
        }
        return $rules;
    }
}
