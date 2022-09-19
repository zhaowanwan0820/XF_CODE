<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/10
 * Time: 14:37
 */

namespace openapi\conf\adddealconf\angli;

class AngliConf {
    const REDIS_INCR_KEY = 'ANGLI_PROJECT_INCR_KEY_20170304';
    //需要额外传的参数
    static $_PRIVATE_PARAMS = array();
    static $_ANGLI_COMMON_CONFIG = array(
        'productName' => '信石1号', //产品名称
        'productClass' => '消费贷', //产品大类
        'advisoryId' => 153, //咨询方编号
        'agencyId' => 0, //担保方编号
        'advanceAgencyId' => 215, //资金垫付方ID
        'productTypeId' => 35,
//        'singleUseMaxMoney' => 2000.0000, //单笔最大限额
//        'singleUseMinMoney' => 500.0000, //单笔最小限额
        'loanType' => '5', //还款方式   到期一次性还本付息
        'repayPeriodType' => 1, // 此参数需过信贷核对，暂无法验证, 1代表P2P侧的天 2代表月
        'contractTplType' => 432, //合同类型
        'prepayRate' => 0.5, // 提前还款违约金系数 product_fee_rate_version.overdue_rate
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
        'warrant' => 0, //担保范围 无
        'cavedBindcard' => 2, //是否绑卡校验 否
        'entrustAgencyId' => 0, //委托机构id (信贷系统中)受托方编号
        'invalidTime' => '2018/8/31', //失效时间
        'generationRechargeId' => 399,//代充值机构ID
    );
    static $_ANGLI_PRODUCT_CONFIG = array(
        14 => array(
            'zixunRate' => 0, //年化咨询费
            'danbaoRate' => 0.00, //年化担保费
            'thirdPayRate' => 0.00, //年化第三方支付费
            'platformRate' => 6.7, //年化平台费
            'profitRate' => 5.2, //投资人收益率
            'prepayPenaltyDays' => 0, // 提前还款罚息天数
            'prepayDaysLimit' => 14, // 提前还款锁定期
            'overdueRate' => 0.05, // 逾期还款违约金系数
            'overdueDay' => 0, // 代偿时间
            'overdueBreakDays' => 0, // 中途逾期强还天数
            'loanFeeRateType' => 2, // 平台服务费收费方式 -后收
            'consultFeeRateType' => 2, // 咨询费收费方式-后收
            'guaranteeFeeRateType' => 2, // 担保费收费方式-后收
            'payFeeRateType' => 2, // 第三方支付收费方式-后收
            'loanApplicationType' => '日常消费', //借款用途分类
        )
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
