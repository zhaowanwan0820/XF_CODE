<?php

namespace core\enum\contract;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ContractTplIdentifierEnum extends AbstractEnum
{
    // 签署方权限位 对应为为1，代表可以签署
    const SIGN_ROLE_NOBODY         = 0b00000000; // 没有签署方
    const SIGN_ROLE_USER           = 0b00000001; // 投资人
    const SIGN_ROLE_BORROWER       = 0b00000010; // 借款人
    const SIGN_ROLE_AGENCY         = 0b00000100; // 担保方
    const SIGN_ROLE_ADVISORY       = 0b00001000; // 咨询方
    const SIGN_ROLE_ENTRUST_AGENCY = 0b00010000; // 委托方
    const SIGN_ROLE_CANAL          = 0b00100000; // 资金渠道方

    // 映射签署方 二进制值与名字之间的关系
    public static $sign_role_map = array(
        self::SIGN_ROLE_USER            => '投资人',
        self::SIGN_ROLE_BORROWER        => '借款人',
        self::SIGN_ROLE_AGENCY          => '担保方',
        self::SIGN_ROLE_ADVISORY        => '咨询方',
        self::SIGN_ROLE_ENTRUST_AGENCY  => '委托方',
        self::SIGN_ROLE_CANAL           => '资金渠道方',
        self::SIGN_ROLE_NOBODY          => '无',
    );

    const CONTRACT_SEND_NODE_BID_DEAL = 0; // 投资时生成
    const CONTRACT_SEND_NODE_FULL_DEAL = 1; // 满标时生成
    const CONTRACT_SEND_NODE_BEFORE_BORROW = 2; // 上标前

    // 服务类型
    const SERVICE_TYPE_DEAL     = 1; // 标的
    const SERVICE_TYPE_PROJECT  = 2; // 项目

    // 映射服务类型
    public static $service_type_map = array(
        self::SERVICE_TYPE_DEAL     => '标的',
        self::SERVICE_TYPE_PROJECT  => '项目',
    );

    // 合同类型
    const CONTRACT_TYPE_OTHER                   = 0; // 其他
    const CONTRACT_TYPE_LOAN                    = 1; // 借款合同
    const CONTRACT_TYPE_ENTRUST_WARRANT         = 2; // 委托担保合同
    const CONTRACT_TYPE_WARRANDICE              = 3; // 保证反担保合同
    const CONTRACT_TYPE_WARRANT                 = 4; // 保证合同
    const CONTRACT_TYPE_LENDER_PROTOCAL         = 5; // 出借人平台服务协议
    const CONTRACT_TYPE_BORROWER_PROTOCAL       = 6; // 借款人平台服务协议
    const CONTRACT_TYPE_DEAL_PAYMENT_ORDER      = 7; // 付款委托书
    const CONTRACT_TYPE_BUYBACK_NOTIFICATION    = 8; // 资产收益权回购通知
    const CONTRACT_TYPE_ENTRUST_ZX              = 9; // 委托专享合同
    const CONTRACT_TYPE_DTB_INVEST_PROTOCA      = 10; // 多投宝协议
    const CONTRACT_TYPE_DTB_LOAN_TRANSFER       = 11; // 多投宝资产转让协议
    const CONTRACT_TYPE_PROJECT_LOAN_TRANSFER   = 12; // 项目转让合同
    const CONTRACT_TYPE_EXCHANGE_MUJISHUOMINGSHU   = 13; // 交易所-募集说明书

    public static $contract_type_map = array(
        self::CONTRACT_TYPE_OTHER   => '其他',
        self::CONTRACT_TYPE_LOAN                    => '借款合同',
        self::CONTRACT_TYPE_ENTRUST_WARRANT         => '委托担保合同',
        self::CONTRACT_TYPE_WARRANDICE              => '保证反担保合同',
        self::CONTRACT_TYPE_WARRANT                 => '保证合同',
        self::CONTRACT_TYPE_LENDER_PROTOCAL         => '出借人平台服务协议',
        self::CONTRACT_TYPE_BORROWER_PROTOCAL       => '借款人平台服务协议',
        self::CONTRACT_TYPE_DEAL_PAYMENT_ORDER      => '付款委托书',
        self::CONTRACT_TYPE_BUYBACK_NOTIFICATION    => '资产收益权回购通知',
        self::CONTRACT_TYPE_ENTRUST_ZX              => '委托专享合同',
        self::CONTRACT_TYPE_DTB_INVEST_PROTOCA      => '多投宝协议',
        self::CONTRACT_TYPE_DTB_LOAN_TRANSFER       => '多投宝资产转让协议',
        self::CONTRACT_TYPE_PROJECT_LOAN_TRANSFER   => '项目转让合同',
        self::CONTRACT_TYPE_EXCHANGE_MUJISHUOMINGSHU   => '交易所-募集说明书',
    );

    const LOAN_CONT = 'TPL_LOAN_CONTRACT';
    const WARRANT_CONT = 'TPL_WARRANT_CONTRACT';
    const LENDER_CONT = 'TPL_LENDER_PROTOCAL';
    const BUYBACK_CONT = 'TPL_BUYBACK_NOTIFICATION';
    const DTB_CONT = 'TPL_DTB_INVEST_PROTOCAL';   // 智多新  投资顾问 标识
    const DTB_TRANSFER = 'TPL_DTB_LOAN_TRANSFER';  // 智多新  债转 标识
    const ENTRUST_CONT = "TPL_ENTRUST_ZX_CONTRACT";
    const RESERVATION_CONT = "TPL_RESERVATION_CONTRACT";  // 随心约 预约协议标识
}
