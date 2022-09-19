<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class JobsEnum extends AbstractEnum {

    const JOBS_STATUS_WAITING = 0;
    const JOBS_STATUS_PROCESS = 1;
    const JOBS_STATUS_SUCCESS = 2;
    const JOBS_STATUS_FAILED = 3;


    public static $statusCn = array(
        self::JOBS_STATUS_WAITING => '等待执行',
        self::JOBS_STATUS_PROCESS => '执行中',
        self::JOBS_STATUS_SUCCESS => '执行成功',
        self::JOBS_STATUS_FAILED => '执行失败',
    );

    const JOBS_RETRY_COUNT = 100; // jobs最大重试次数
    const GET_JOBS_COUNT = 500;

    const ERRORCODE_NEEDDELAY = 1005;
    const ERRORMSG_NEEDDELAY = "Jobs Need To Be Back";

    /**
     * jobs 优先级定义到此处
     */
    const JOBS_PRIORITY_MSGBUS = 100;
    const JOBS_PRIORITY_ADMIN_DEAL_UPDATE = 101;
    const JOBS_PRIORITY_DEAL_FAIL = 102;
    const JOBS_PRIORITY_DEAL_TRUNCATE = 103;
    const JOBS_PRIORITY_DEAL_LOAD_FULL_CHECK = 104;

    /**********************消息队列优先级**********************/
    const PRIORITY_MESSAGE_QUEUE                = 2000;  //通用消息队列优先级
    const PRIORITY_MESSAGE_QUEUE_LOAN           = 2001;  //放款消息队列优先级
    const PRIORITY_MESSAGE_QUEUE_REPAY          = 2002;  //还款消息队列优先级
    const PRIORITY_MESSAGE_QUEUE_PREPAY         = 2003;  //提前还款消息队列优先级
    const PRIORITY_MESSAGE_QUEUE_PART_PREPAY    = 2004;  //部分提前还款消息队列优先级

    //还款日历收集
    const JOBS_PRIORITY_REPAY_CALENDAR_COLLECT = 69;

    // 存管订单
    const PRIORITY_SUPERVISION_ORDER = 0;

    // 享花标的还款通知
    const PRIORITY_XH_REPAY_NOTIFY = 201;
    // 订单拆分服务-请求存管
    const PRIORITY_ORDERSPLIT_REQUEST = 202;

    // 标的放款
    const PRIORITY_DEAL_GRANT = 99;
    // 放款提现
    const PRIORITY_DEAL_GRANT_WITHDRAW = 50;
    // 放款收尾处理
    const PRIORITY_FINISH_DEALLOANS = 50;
    // 放款创建回款计划
    const PRIORITY_DEALLOANS_REPAY_CREATE = 50;

    const PRIORITY_PUSH_DEAL_LOAD = 56; // 协会上报投资记录

    //智多新邀请码优先级
    const PRIORITY_DTB_COUPON = 87;

    // 投资取消通知银行
    const BID_CANCEL_REQUEST = 98;
    // 投资成功回调
    const PRIORITY_BID_SUCCESS_CALLBACK = 150;
    // 多投投资成功回调
    const PRIORITY_DT_BID_SUCCESS = 140;

    const PRIORITY_REPAY_DEAL_LOAN = 85;
    const PRIORITY_REPAY_FINISH = 86;

    // 标的正常还款
    const PRIORITY_DEAL_REPAY = 90;
    // 立即执行还款
    const PRIORITY_ADD_BATH_RIGHT = 92;

    // 标的提前还款
    const PRIORITY_DEAL_PREPAY = 80;

    // 智多鑫还款资金记录
    const PRIORITY_DTB_REPAY_MONEY = 55;

    // 投资人 投资时 生成正式合同
    const BID_SEND_CONTRACT = 123;
    // 投资人检查满标
    const BID_CHECK_FULL_CONTRACT = 122;

    // 下发合同（用户全部签署后给借款人和投资人发送站内信和邮件）
    const SEND_CONTRACT_MSG = 124;

    // 随心约投资券
    const PRIORITY_RESERVE_DISCOUNT = 130;

    //随心约预约协议
    const PRIORITY_RESERVE_PROTOCOL = 131;

    // 转移临时合同到正式合同记录
    const PRIORITY_CONTRACT = 176;

    // 代扣回调成功后的处理
    const PRIORITY_P2P_DK_CALLBACK = 200;

    // 报备标的还款通知
    const PRIORITY_P2P_REPAY_REQUEST = 199;

    const DEAL_REPAY_CREDIT_LOAN = 152;

    // 农担贷还款信息试算
    const PRIORITY_ND_REPAY_CALC = 203;

    // 农担贷还款-请求存管
    const PRIORITY_ND_REPAY_REQUEST = 204;

    // 农担贷还款-存管回调后处理
    const PRIORITY_ND_REPAY_CALLBACK = 205;

    const REPAY_FREEZE_NOTIFY_SUDAI = 151;  // 回款冻结通知速贷

    const REPAY_CREDIT_LOAN = 152;

    //  自动签署合同
    const SIGN_CONTRACT = 133;

    //  合同打时间戳
    const CONTRACT_TSA = 175;
    //  智多新合同打时间戳
    const CONTRACT_TSA_DT = 177;
    // 生成智多新打戳jobs
    const CONTRACT_JOBS_TSA_DT = 178;
    // 随心约合同打戳;
    const CONTRACT_JOBS_TSA_RESERVATION = 179;

    // 智多鑫匹配完成拉取债转数据
    const PRIORITY_DTB_GET_TRANSDATA = 333;

    // 智多鑫投资
    const PRIORITY_DTB_CALLBACK_BID = 88;
    // 智多鑫还款通知银行
    const PRIORITY_DTB_REPAY_BANK = 77;
    //智多鑫合同
    const PRIORITY_DTB_CONTRACT = 206;

    //上报数据(中互金标的数据)
    const PRIORITY_DATA_REPORT_IFA_DEAL = 400;

    //上报数据(中互金标的状态)
    const PRIORITY_DATA_REPORT_IFA_DEAL_STATUS = 401;

    //放款后收费
    const PRIORITY_FEE_AFTER_LOAN = 207;

    //通知功夫贷扣费结果
    const PRIORITY_NOTIFY_GFD = 208;

    //上报数据（百行非循环贷账户数据--放款）
    const PRIORITY_DATA_REPORT_BAIHANG_LOAN = 402;

    //上报数据（百行非循环贷贷后数据--还款）
    const PRIORITY_DATA_REPORT_BAIHANG_REPAY = 403;
}
