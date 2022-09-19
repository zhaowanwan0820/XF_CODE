<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MsgBoxEnum extends AbstractEnum {

    // 未读
    const MSG_STATUS_UNREAD = 0;
    // 已读
    const MSG_STATUS_READ = 1;

    //注意
    //类型不能超过127
    const TYPE_SYSTEM = 1; // 系统消息
    const TYPE_INFORMATION_PASS = 2; // 材料通过
    const TYPE_AUDIT_FAILED = 3; // 审核失败
    const TYPE_RATING_UPDATE = 4; // 额度更新
    const TYPE_CARRY_APPLY = 5; // 提现申请
    const TYPE_CARRY_SUCCESS = 6; // 提现成功
    const TYPE_CARRY_FAILED = 7; // 提现失败
    const TYPE_REPAY_SUCCESS = 8; // 还款成功
    const TYPE_MONEY_BACK = 9; // 回款成功
    const TYPE_BORROWER_FAILED = 10; // 借款人流标
    const TYPE_INVESTOR_FAILED = 11; // 投资人流标
    const TYPE_DEAL_FULL = 16; // 满标提示
    const TYPE_AHEAD_REPAY_AUDIT_FALIED = 17; // 提前还款未通过审核
    const TYPE_DEAL_SUCCESS_TIPS = 18; // 投标完成提示
    const TYPE_DEAL_LOAN_TIPS = 19; // 投标放款提示
    const TYPE_BONUS = 30; // 红包
    const TYPE_REBATE = 31; // 返利
    const TYPE_CONTRACT_SEND = 32; // 合同下发
    const TYPE_MONTHLY_BILL_CHECK = 33; // 月对账单
    const TYPE_CHARGE_SUCCESS = 34; // 充值成功
    const TYPE_O2O_COUPON = 35; // 礼券
    const TYPE_MEDAL = 36; // 勋章
    const TYPE_DUOTOU = 37; // 智多鑫
    const TYPE_DONATE = 38;
    const TYPE_DISCOUNT = 39; // 投资劵
    const TYPE_NOTICE = 40; // 个人通知
    const TYPE_RESERVATION = 41; // 随心约
    const TYPE_RESERVATION_EXPIRE = 61; // 随心约预约到期
    const TYPE_RESERVATION_SUCCESS = 62; // 随心约预约交易成功
    const TYPE_VIP_UPGRADE = 42;           // 会员服务升级
    const TYPE_VIP_BIRTHDAY = 43;          // 会员生日礼包
    const TYPE_VIP_ANNIVERSARY = 44;       // 会员周年礼包
    const TYPE_VIP_RELEGATED = 45;         // 进入保级
    const TYPE_VIP_WILL_DEGRADE = 46;      // 即将降级
    const TYPE_VIP_DEGRADE = 47;           // 会员降级
    const TYPE_DUOTOU_INTEREST = 51; // 智多鑫结息
    const TYPE_DUOTOU_BID_DONE = 52; // 智多鑫投标完成提示
    const TYPE_DUOTOU_TRANSFER_APPLY = 53; // 智多鑫转让申请
    const TYPE_DUOTOU_TRANSFER_SUCCESS = 54; // 智多鑫转让成功
    const TYPE_DUOTOU_LOAN_USER_CHANGED = 55; // 债权转让通知

    //移动端消息跳转类型
    const TURN_TYPE_CONTINUE_INVEST = 1;//继续投资
    const TURN_TYPE_REPAY_CALENDAR  = 2;//回款日历
    const TURN_TYPE_REBATE_DETAIL   = 3;//返利明细
    const TURN_TYPE_MONEY_LOG       = 4;//资金记录
    const TURN_TYPE_MEDAL           = 5;//勋章
    const TURN_TYPE_VIP             = 6;//会员
    const TURN_TYPE_DISCOUNT        = 7;//优惠券
    const TURN_TYPE_URL             = 8;// 连接

    /**
     * 所有消息类型
     */
    public static $allType = array(
        self::TYPE_SYSTEM => '系统消息',
        self::TYPE_INFORMATION_PASS => '材料通过',
        self::TYPE_AUDIT_FAILED => '审核失败',
        self::TYPE_RATING_UPDATE => '额度更新',
        self::TYPE_CARRY_APPLY => '提现申请',
        self::TYPE_CARRY_SUCCESS => '提现成功',
        self::TYPE_CARRY_FAILED => '提现失败',
        self::TYPE_REPAY_SUCCESS => '还款成功',
        self::TYPE_MONEY_BACK => '回款成功',
        self::TYPE_BORROWER_FAILED => '借款人流标',
        self::TYPE_INVESTOR_FAILED => '投资人流标',
        self::TYPE_DEAL_FULL => '满标提示',
        self::TYPE_AHEAD_REPAY_AUDIT_FALIED => '提前还款未通过审核',
        self::TYPE_DEAL_SUCCESS_TIPS => '投标完成提示',
        self::TYPE_DEAL_LOAN_TIPS => '投标放款提示',
        self::TYPE_BONUS => '红包',
        //31 => '返利',
        self::TYPE_REBATE => '邀请奖励',
        self::TYPE_CONTRACT_SEND => '合同下发',
        self::TYPE_MONTHLY_BILL_CHECK => '月对账单',
        self::TYPE_CHARGE_SUCCESS => '充值成功',
        self::TYPE_O2O_COUPON => '礼券',
        self::TYPE_MEDAL => '勋章',
        self::TYPE_DUOTOU => '智多鑫',
        self::TYPE_DONATE => '捐赠',
        self::TYPE_DISCOUNT => '投资劵',
        self::TYPE_NOTICE => '通知',
        self::TYPE_RESERVATION => '随心约',
        self::TYPE_VIP_UPGRADE => '会员升级',
        self::TYPE_VIP_BIRTHDAY => '生日福利',
        self::TYPE_VIP_ANNIVERSARY => '周年福利',
        self::TYPE_VIP_RELEGATED => '进入保级',
        self::TYPE_VIP_WILL_DEGRADE => '即将降级',
        self::TYPE_VIP_DEGRADE => '会员降级',
    );

    /**
     * 移动端展示的消息类型
     */
    public static $appType = array(
        self::TYPE_MONEY_BACK => '项目回款',
        //31 => '返利',
        self::TYPE_REBATE => '邀请奖励',
        self::TYPE_CARRY_SUCCESS => '提现',
        self::TYPE_RESERVATION => '随心约',
        self::TYPE_DUOTOU => '智多鑫',
        self::TYPE_O2O_COUPON => '礼券',
        self::TYPE_MEDAL => '勋章',
        self::TYPE_DISCOUNT => '投资劵',
        self::TYPE_BONUS => '红包',
        self::TYPE_DEAL_LOAN_TIPS => '投标放款',
        self::TYPE_DEAL_FULL => '满标',
        self::TYPE_CONTRACT_SEND => '合同下发',
        self::TYPE_NOTICE => '通知',
        self::TYPE_VIP_UPGRADE => '会员升级',
        self::TYPE_VIP_BIRTHDAY => '生日福利',
        self::TYPE_VIP_ANNIVERSARY => '周年福利',
        self::TYPE_VIP_RELEGATED => '进入保级',
        self::TYPE_VIP_WILL_DEGRADE => '即将降级',
        self::TYPE_VIP_DEGRADE => '会员降级',
    );

    /**
     * 移动端消息类型分组(设置界面中)
     */
    public static $appTypeGroup = array(
        array(self::TYPE_CONTRACT_SEND, self::TYPE_DEAL_FULL, self::TYPE_DEAL_LOAN_TIPS, self::TYPE_MONEY_BACK, self::TYPE_CARRY_SUCCESS),
        array(self::TYPE_BONUS, self::TYPE_REBATE, self::TYPE_O2O_COUPON, self::TYPE_MEDAL, self::TYPE_DUOTOU, self::TYPE_DISCOUNT, self::TYPE_VIP_UPGRADE),
    );

    /**
     * 移动端展示的消息类型(仅做界面展示)
     */
    public static $structAppType = array(
        '投资进度' => array(self::TYPE_MONEY_BACK => '回款', self::TYPE_DEAL_LOAN_TIPS => '放款计息',self::TYPE_DEAL_FULL => '满标',self::TYPE_CONTRACT_SEND => '合同下发'),
        '智多鑫' => array(self::TYPE_DUOTOU_INTEREST => '结息', self::TYPE_DUOTOU_BID_DONE => '投标完成', self::TYPE_DUOTOU_TRANSFER_APPLY => '转让申请', self::TYPE_DUOTOU_TRANSFER_SUCCESS => '转让成功'),
        '随心约' => array(self::TYPE_RESERVATION_EXPIRE => '预约到期', self::TYPE_RESERVATION_SUCCESS => '预约交易成功'),
        '福利' => array(self::TYPE_REBATE => '邀请红包奖励', self::TYPE_MEDAL => '获得勋章', self::TYPE_O2O_COUPON => '礼券', self::TYPE_DISCOUNT => '投资劵'),
        '会员' => array(self::TYPE_VIP_UPGRADE => '会员升级', self::TYPE_VIP_BIRTHDAY => '生日福利', self::TYPE_VIP_ANNIVERSARY => '周年福利', self::TYPE_VIP_RELEGATED => '进入保级', self::TYPE_VIP_WILL_DEGRADE => '即将降级', self::TYPE_VIP_DEGRADE => '会员降级'),
        '其他' => array(self::TYPE_CARRY_SUCCESS => '提现', self::TYPE_NOTICE => '通知'),
    );

    /**
     * 移动端展示的消息类型-智多鑫/随心约type相等（title是老数据）
    */
    public static $sameAppType = array(
            self::TYPE_DUOTOU_INTEREST => '智多鑫结息',
            self::TYPE_DUOTOU_BID_DONE => '投标完成提示',
            self::TYPE_DUOTOU_TRANSFER_APPLY => '转让申请',
            self::TYPE_DUOTOU_TRANSFER_SUCCESS => '转让成功',
            self::TYPE_RESERVATION_EXPIRE => '预约到期',
            self::TYPE_RESERVATION_SUCCESS => '预约交易成功'
    );
}
