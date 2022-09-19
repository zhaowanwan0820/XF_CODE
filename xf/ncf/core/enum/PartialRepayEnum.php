<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class PartialRepayEnum extends AbstractEnum {

    const REPAY_TYPE_BORROWER       = 0; //借款人还款
    const REPAY_TYPE_COMPENSATORY   = 1; //担保代偿

    const STATUS_NORMAL     = 0; //正常状态
    const STATUS_HANDLED    = 1; //已处理
    const ORDER_INSERT_SPLIT_NUM    = 500; //保存订单拆分数量

    const FEE_TYPE_PRINCIPAL    = 1; //本金
    const FEE_TYPE_INTEREST     = 2; //利息
    const FEE_TYPE_SX           = 3; // 平台手续费
    const FEE_TYPE_ZX           = 4; // 借款咨询费
    const FEE_TYPE_DB           = 5; // 借款担保费
    const FEE_TYPE_FW           = 6; // 服务费
    const FEE_TYPE_GL           = 7; // 管理费
    const FEE_TYPE_QD           = 8; // 渠道费
    const FEE_TYPE_YQ           = 9; // 逾期罚息
    const FEE_TYPE_COMPEN       = 10;//提前还款补偿金
    const FEE_TYPE_UGL          = 11; // DTB 收取投资人的管理费

    const RATIO_TYPE_PRINCIPAL    = 'principal'; //比例类型本金
    const RATIO_TYPE_INTEREST     = 'interest'; //比例类型利息
    const RATIO_TYPE_FEE          = 'fee'; //比例类型费用

    const REPAY_EXTRA_MONEY_LIMIT   = 100; //农担贷代偿还款额外金额限制
    const REPAY_EXTRA_MONEY_LIMIT_DZH = 20; //多账户提前结清还款额外金额限制

    const REPAY_BIZ_TYPE_PARTIAL = 0; // 部分还款
    const REPAY_BIZ_TYPE_DZH_PREPAY = 1; //多账户提前结清

}
