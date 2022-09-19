<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/14
 * Time: 10:20
 */

namespace openapi\conf\adddealconf\gfd;

class GfdConf {
    const REDIS_INCR_KEY = 'GFD_PROJECT_INCR_KEY_20170814';
    //需要额外传的参数
    static $_PRIVATE_PARAMS = array();

    static $_COMMON_CONFIG = array(
        'productName' => '功夫贷', //产品名称
        'productClass' => '消费贷', //产品大类
        'advisoryId' => 421, //咨询方编号
        'agencyId' => 429, //担保方编号
        'advanceAgencyId' => 422, //资金垫付方ID
        'typeId' => 38,//借款类型
        'singleUseMaxMoney' => 80000.0000, //单笔最大限额
        'singleUseMinMoney' => 1000.0000, //单笔最小限额
        'loanType' => '2', //还款方式   按月等额还款
        'repayPeriodType' => 2, // 此参数需过信贷核对, 1代表P2P侧的天 2代表月
        'contractTplType' => 442, //合同类型
        'prepayRate' => 0.00, // 提前还款违约金系数 product_fee_rate_version.overdue_rate
        'leasingContractNum' => '', // 基础合同编号
        'lesseeRealName' => '', // 基础合同
        'leasingMoney' => 0.0000, // 基础合同交易金额
        'entrustedLoanBorrowContractNum' => '', // 委托贷款委托合同编号
        'entrustedLoanEntrustedContractNum' => '', // 委托贷款借款合同编号
        'baseContractRepayTime' => 0, // 基础合同借款到期日
        'lineSiteId' => 1, // 上线网站编号 product_parameter.ONLINENO
        'lineSiteName' => '网信理财', // 上线网站名称 product_parameter.ONLINENAME
        'leasingContractTitle' => '', //基础合同名称 (线上全是空的)
        'contractTransferType' => 0, //转让资产类型 (线上全是空的)
        'loanMoneyType' => 1, //放款方式 实际放款
        'entrustSign' => 1, //借款合同是否委托签署
        'entrustAgencySign' => 1, //担保合同是否委托签署
        'entrustAdvisorySign' => 1, //资产管理方合同是否委托签署
        'warrant' => 2, //担保范围 本金和利息
        'cavedBindcard' => 2, //是否绑卡校验 否
        'entrustAgencyId' => 0, //委托机构id (信贷系统中)受托方编号
        'invalidTime' => '2018/3/4', //失效时间
        'generationRechargeId' => 433,//代充值机构ID  有待确认
    );
    static $_PRODUCT_CONFIG = array(
        6 => array(
            //'zixunRate' => 228.88, //年化咨询费
            'danbaoRate' => 0.00, //年化担保费
            'thirdPayRate' => 0.00, //年化第三方支付费
            'platformRate' => 2.86, //年化平台费
            'profitRate' => 9, //投资人收益率
            'prepayPenaltyDays' => 0, // 提前还款罚息天数
            'prepayDaysLimit' => 0, // 提前还款锁定期
            'overdueRate' => 0.05, // 逾期还款违约金系数
            'overdueDay' => 0, // 代偿时间
            'overdueBreakDays' => 0, // 中途逾期强还天数
            'loanFeeRateType' => 1, // 平台服务费收费方式，1-年化前收
            'consultFeeRateType' => 1, // 咨询费收费方式，1-年化前收
            'guaranteeFeeRateType' => 1, // 担保费收费方式，1-年化前收
            'payFeeRateType' => 1, // 第三方支付收费方式，1-年化前收
            'loanApplicationType' => '日常消费', //借款用途分类
        ),
        12 => array(
            //'zixunRate' => 218.57, //年化咨询费
            'danbaoRate' => 0.00, //年化担保费
            'thirdPayRate' => 0.00, //年化第三方支付费
            'platformRate' => 2.86, //年化平台费
            'profitRate' => 10, //投资人收益率
            'prepayPenaltyDays' => 0, // 提前还款罚息天数
            'prepayDaysLimit' => 0, // 提前还款锁定期
            'overdueRate' =>  0.05, // 逾期还款违约金系数
            'overdueDay' => 0, // 代偿时间
            'overdueBreakDays' => 0, // 中途逾期强还天数
            'loanFeeRateType' => 1, // 平台服务费收费方式，1-年化前收
            'consultFeeRateType' => 1, // 咨询费收费方式，1-年化前收
            'guaranteeFeeRateType' => 1, // 担保费收费方式，1-年化前收
            'payFeeRateType' => 1, // 第三方支付收费方式，1-年化前收
            'loanApplicationType' => '日常消费', //借款用途分类
        ),
        18 => array(
           // 'zixunRate' => 209.10, //年化咨询费
            'danbaoRate' => 0.00, //年化担保费
            'thirdPayRate' => 0.00, //年化第三方支付费
            'platformRate' => 2.86, //年化平台费
            'profitRate' => 10.5, //投资人收益率
            'prepayPenaltyDays' => 0, // 提前还款罚息天数
            'prepayDaysLimit' => 0, // 提前还款锁定期
            'overdueRate' =>  0.05, // 逾期还款违约金系数
            'overdueDay' => 0, // 代偿时间
            'overdueBreakDays' => 0, // 中途逾期强还天数
            'loanFeeRateType' => 1, // 平台服务费收费方式，1-年化前收
            'consultFeeRateType' => 1, // 咨询费收费方式，1-年化前收
            'guaranteeFeeRateType' => 1, // 担保费收费方式，1-年化前收
            'payFeeRateType' => 1, // 第三方支付收费方式，1-年化前收
            'loanApplicationType' => '日常消费', //借款用途分类
        ),
        24 => array(
            //'zixunRate' => 200.41, //年化咨询费
            'danbaoRate' => 0.00, //年化担保费
            'thirdPayRate' => 0.00, //年化第三方支付费
            'platformRate' => 2.76, //年化平台费
            'profitRate' => 11.8, //投资人收益率
            'prepayPenaltyDays' => 0, // 提前还款罚息天数
            'prepayDaysLimit' => 0, // 提前还款锁定期
            'overdueRate' =>  0.05, // 逾期还款违约金系数
            'overdueDay' => 0, // 代偿时间
            'overdueBreakDays' => 0, // 中途逾期强还天数
            'loanFeeRateType' => 1, // 平台服务费收费方式，1-年化前收
            'consultFeeRateType' => 1, // 咨询费收费方式，1-年化前收
            'guaranteeFeeRateType' => 1, // 担保费收费方式，1-年化前收
            'payFeeRateType' => 1, // 第三方支付收费方式，1-年化前收
            'loanApplicationType' => '日常消费', //借款用途分类
        ),
    );

    /**
     * @return array
     */
    function getCommonConf() {
        return self::$_COMMON_CONFIG;
    }

    /**
     * @return array
     */
    public function getProductconf()
    {
        return self::$_PRODUCT_CONFIG;
    }

    /**
     * @return array
     */
    public function getIncrKey()
    {
        return self::REDIS_INCR_KEY;
    }

    /**
     * @return array
     */
    public function getPrivateParams()
    {
        return self::$_PRIVATE_PARAMS;
    }
}
