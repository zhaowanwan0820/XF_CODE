<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MsgbusEnum extends AbstractEnum {

    const TOPIC_DEAL_CREATE = 'deal_create'; // 标的创建后消息
    const TOPIC_DEAL_PROGRESSING = 'deal_progressing'; // 标的进行中消息
    const TOPIC_DEAL_IFA_DEAL_PROGRESSING = 'deal_ifa_progressing'; // 标的进行中消息
    const TOPIC_DEAL_REPORT_DEAL_PROGRESSING = 'deal_report_progressing'; //标的进行中消息
    const TOPIC_DEAL_FULL = 'deal_full'; // 标的满标消息
    const TOPIC_DEAL_MAKE_LOANS = 'deal_make_loans'; // 标的放款消息
    const TOPIC_DEAL_FAIL = 'deal_fail'; // 标的流标消息

    const TOPIC_USER_REGISTER_SUCCESS = 'user_register_success';

    const TOPIC_DEAL_BID_SUCESS = 'deal_bid_sucess'; // 投资成功

    const TOPIC_DEAL_REPAY_FINISH = 'deal_repay'; // 标的正常还款完成消息
    const TOPIC_DEAL_REPAY_OVER = 'deal_repay_over'; // 标的已还清

    const TOPIC_DEAL_PREPAY_FINISH = 'deal_prepay'; // 标的提前还款完成消息

    const TOPIC_ACCOUNT_LOG_SYNC = 'account_log_sync'; //资金记录同步

    const TOPIC_DEAL_BID_TRIGGER_O2O = 'deal_trigger_o2o'; //投资成功触发o2o

    const TOPIC_RESERVE_MERGE_SEND_MSG = 'reserve_merge_send_msg'; //随鑫约合并发送短信

    const TOPIC_CONTRACT_MSG = 'contract_msg'; // 合同全部签署完成，发送下发合同的邮件和短信

    const TOPIC_HANDLE_DEAL_TRIGGER_FINANCE= 'deal_handle_notify_finance'; // 放还款等操作通知业财

    const TOPIC_SUPERVISION_WITHDRAW_DELAY_MSG = 'supervision_withdraw_delay_msg'; // 延迟提现命中之后的异步通知

    const TOPIC_DT_TRANSFER = 'dt_transfer'; // 智多新债转

    const TOPIC_USER_ASSET_CHANGE = 'ph_user_asset_change'; // 用户资产变更

}
