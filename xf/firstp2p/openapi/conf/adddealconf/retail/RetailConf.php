<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/10
 * Time: 14:37
 */

namespace openapi\conf\adddealconf\retail;

class RetailConf {
    const REDIS_INCR_KEY = 'ANGLI_PROJECT_INCR_KEY_20170304';
    //需要额外传的参数
    static $_PRIVATE_PARAMS = array(
        "financAdd" => array("filter" => "string", 'option' => array('optional' => true)), //融资地址
        "openID" => array("filter" => "string", 'option' => array('optional' => true)), //网信openID
        "name" => array("filter" => "required", "message" => "name is required"),//项目名称（订单名称）
        "realName" => array("filter" => "required", "message" => "realName is required"),//借款人姓名
        "idno" => array("filter" => "required", "message" => "idno is required"),//借款人身份证号
        "mobile" => array("filter" => "string", 'option' => array('optional' => true)),//借款人注册手机号
        "loanType" => array("filter" => "required", "message" => "loanType is required"),//还款方式
        "repayPeriod" => array("filter" => "required", "message" => "repayPeriod is required"),//借款期限
        "rate" => array("filter" => "required", "message" => "rate is required"),//借款综合成本(年化)（年化打包费率）
        "projectInfoUrl" => array("filter" => "required", "message" => "projectInfoUrl is required"),//项目简介
        "borrowAmount" => array("filter" => "required", "message" => "borrowAmount is required"),
        "advisoryId" => array("filter" => "required", "message" => "advisoryId is required"),//咨询机构id
        "agencyId" => array("filter" => "int", 'option' => array('optional' => true)),//担保机构id
        "typeId" => array("filter" => "required", "message" => "typeId is required"),//产品类别（借款用途）
        "manageFeeRate" => array("filter" => "required", "message" => "manageFeeRate is required"),//年化借款平台手续费
        "annualPaymentRate" => array("filter" => "required", "message" => "annualPaymentRate is required"),//年化支付费率
        "guaranteeFeeRate" => array("filter" => "string", 'option' => array('optional' => true)),//借款担保费
        "prepayRate" => array("filter" => "required", "message" => "prepayRate is required"),//提前还款违约金系数
        "prepayPenaltyDays" => array("filter" => "required", "message" => "prepayPenaltyDays is required"),//提前还款罚息天数
        "prepayDaysLimit" => array("filter" => "required", "message" => "prepayDaysLimit is required"),//提前还款限制（提前还款锁定天数(天)）
        "overdueRate" => array("filter" => "required", "message" => "overdueRate is required"),//逾期还款罚息系数
        "overdueDay" => array("filter" => "required", "message" => "overdueDay is required"),//代偿时间(天)
        "repayPeriodType" => array("filter" => "required", "message" => "repayPeriodType is required"),//借款期限类型(1:天,2:月，3年)
        "contractTplType" => array("filter" => "required", "message" => "contractTplType is required"),//合同类型
        "lineSiteId" => array("filter" => "required", "message" => "lineSiteId is not string type"),//上线网站编号
        "lineSiteName" => array("filter" => "required", "message" => "lineSiteName is required"),//上线网站名称
        "overdueBreakDays" => array("filter" => "required", "message" => "overdueBreakDays is required"),//中途逾期强还天数
        "loanFeeRateType" => array("filter" => "required", "message" => "loanFeeRateType is required"),//年化借款平台手续费类型
        "consultFeeRateType" => array("filter" => "required", "message" => "consultFeeRateType is required"),//年化借款咨询费类型
        "guaranteeFeeRateType" => array("filter" => "required", "message" => "guaranteeFeeRateType is required"),//年化借款担保费类型
        "payFeeRateType" => array("filter" => "required", "message" => "payFeeRateType is required"),//年化支付服务费
        "loanApplicationType" => array("filter" => "required", "message" => "loanApplicationType is required"),//借款用途分类
        "bankCard" => array("filter" => "string", 'option' => array('optional' => true)),//银行卡号
        "loanMoneyType" => array("filter" => "required", "message" => "loanMoneyType is required"),//放款方式(1实际放款2非实际放款3受托支付)
        "cardName" => array("filter" => "required", "message" => "cardName is required"),//开户人姓名
        "bankNum" => array("filter" => "int", 'option' => array('optional' => true)),//银行联行号
        "loanBankCard" => array("filter" => "string", 'option' => array('optional' => true)),//受托支付银行卡号
        "bankShortName" => array("filter" => "string", 'option' => array('optional' => true)),//银行简码
        "cardType" => array("filter" => "int", 'option' => array('optional' => true)),//银行卡对公对私
        "rateYields" => array("filter" => "required", "message" => "rateYields is required"),//投资人收益率
        "entrustSign" => array("filter" => "int", 'option' => array('optional' => true)),//借款合同是否委托签署，1：是；0：否
        "fixedReplay" => array("filter" => "int", 'option' => array('optional' => true)),//固定还款日
        "advanceAgencyId" => array("filter" => "int", 'option' => array('optional' => true)),//代偿机构id
        "entrustAgencySign" => array("filter" => "int", 'option' => array('optional' => true)),//项目担保方合同是否被委托签署1：是；0：否
        "entrustAdvisorySign" => array("filter" => "int", 'option' => array('optional' => true)),//项目资产管理方合同是否被委托签署1：是；0：否
        "warrant" => array("filter" => "int", 'option' => array('optional' => true)),//担保范围,0:无 1:本金 2:本金和利息，3:有担保公司未指定4：第三方资产收购,默认为2
        "cavedBindcard" => array("filter" => "int", 'option' => array('optional' => true)),//是否校验绑卡,1:绑卡校验,2不绑卡校验,默认1
        "productClass" => array("filter" => "string", 'option' => array('optional' => true)),//产品类别
        "productName" => array("filter" => "required", "message" => "productName is required"),//产品名称
        "generationRechargeId" => array("filter" => "int", 'option' => array('optional' => true)),//代充值机构ID
        "extLoanType" => array("filter" => "int", 'option' => array('optional' => true)),//放款类型 数字0代表直接放款；数字1代表先计息后放款；数字2代表收费后放款'
        "consultFeePeriodRate" => array("filter" => "string", 'option' => array('optional' => true)),//分期咨询费
        "chnAgencyId" => array("filter" => "int", 'option' => array('optional' => true)),
        "chnFeeRate" => array("filter" => "string", 'option' => array('optional' => true)),
        "chnFeeRateType" => array("filter" => "int", 'option' => array('optional' => true)),

        //其他平台借款额
        "otherBorrowing" => array("filter" => "string", 'option' => array('optional' => true)),
        //借款客群 (1:普通消费者,2:小微企业,3:个体工商户4:自就业者)
        "loanUserCustomerType" => array("filter" => "int", 'option' => array('optional' => true)),
        "clearingType" => array("filter" => "int", 'option' => array('optional' => true)),
    );
    static $_ANGLI_COMMON_CONFIG = array(

    );
    static $_ANGLI_PRODUCT_CONFIG = array(

    );

    /**
     * @return array
     */
    public function getCommonConf()
    {
        return self::$_ANGLI_COMMON_CONFIG;
    }

    /**
     * @return array
     */
    public function getProductConf()
    {
        return self::$_ANGLI_PRODUCT_CONFIG;
    }

    /**
     * @return array
     */
    public function getPrivateParams()
    {
        return self::$_PRIVATE_PARAMS;
    }
}
