<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class P2pDepositoryEnum extends AbstractEnum
{
    const ALARM_BANK_CALLBAK = 'supervision_order_bank_callback';

    const ALARM_DT_DEPOSITORY = 'DT_DEPOSITORY'; // 智多鑫存管报警相关key

    const REQUEST_BIZ_REPAY = 'RP_';  // 提前还款业务号
    const REQUEST_BIZ_PREPAY = 'PRP_'; // 正常还款业务号
    const REQUEST_BIZ_GRANT = 'GRANT_'; // 放款业务号


    const FEE_SX = 'SX_'; // 手续费
    const FEE_ZX = 'ZX_'; // 咨询费
    const FEE_DB = 'DB_'; // 担保费
    const FEE_FW = 'FW_'; // 服务费
    const FEE_GL = 'GL_'; // 管理费
    const FEE_QD = 'QD_'; // 渠道费
    const FEE_YQ = 'YQ_'; // 逾期罚息
    const FEE_UGL = 'UGL_'; // DTB 收取投资人的管理费
    const FEE_PRINCIPAL = 'BJ_'; //本金
    const FEE_INTEREST = 'LX_'; //利息
    const FEE_COMPEN = 'BCJ_';//提前还款补偿金


    const IDEMPOTENT_TYPE_BID    = 1;   // 投资
    const IDEMPOTENT_TYPE_GRANT  = 2;   // 放款
    const IDEMPOTENT_TYPE_REPAY  = 3;   // 还款
    const IDEMPOTENT_TYPE_CANCEL = 4;   // 流标
    const IDEMPOTENT_TYPE_YXT    = 5;   // 银信通还款通知提现
    const IDEMPOTENT_TYPE_WITHDRAW = 6; //放款后提现
    const IDEMPOTENT_TYPE_DTBID = 7;    // 智多鑫投资
    const IDEMPOTENT_TYPE_DTP2PBID = 8; // 智多鑫底层标的投资
    const IDEMPOTENT_TYPE_DTREPAY = 9;  //
    const IDEMPOTENT_TYPE_XH    = 10;   // 享花项目还款通知提现
    const IDEMPOTENT_TYPE_DK    = 11;   // 请求支付代扣
    const IDEMPOTENT_TYPE_TRANS    = 12;// 两个账户之间的资金转账
    const IDEMPOTENT_TYPE_REDEEM    = 13;// 智多鑫赎回到账
    const IDEMPOTENT_TYPE_NDREPAY   = 14;  //农担贷还款

    const CALLBACK_STATUS_SUCC = 'S'; // 回调状态 成功
    const CALLBACK_STATUS_FAIL = 'F'; // 回调状态 失败

    const MONEY_TRANSFER_BIZ_SUBTYPE = 11; // 对应MoneyOrderService中的bizSubtype
    const DT_TRANS_PAGESIZE = 100; // 智多新债转每次最大条数
}
