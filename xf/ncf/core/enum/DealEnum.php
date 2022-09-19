<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use core\enum\DealAgencyEnum;

class DealEnum extends AbstractEnum {

    const DEAL_MOVED_DB_NAME = 'firstp2p_moved';

    const DEAL_NORMAL_DB_NAME = 'firstp2p';

    const DEAL_REPORT_STATUS_YES = 1; // 报备状态 0--未报备
    const DEAL_REPORT_STATUS_NO = 0; // 报备状态  1--已报备

    const DEAL_PUBLISH_WAIT_NO = 0; // 完成审核
    const DEAL_PUBLISH_WAIT_YES = 1; // 等待审核

    const DEAL_EFFECT_YES = 1; // 有效
    const DEAL_EFFECT_NO = 0; // 无效

    const DEAL_IS_DOING_YES = 1; // 执行中
    const DEAL_IS_DOING_NO = 0; // 未执行中

    const DEAL_IS_HAS_LOANS_NO  = 0; // 未放款
    const DEAL_IS_HAS_LOANS_YES = 1; // 已放款
    const DEAL_IS_HAS_LOANS_ING = 2; // 放款中

    const DEAL_DURING_REPAY = 1; // 是否正在还款中 是
    const DEAL_NOT_DURING_REPAY = 0;// 是否正在还款中 否


    const DEAL_STATS_WAITING = 0;  // 标的状态 等待确认
    const DEAL_STATUS_PROCESSING = 1; // 标的状态 进行中
    const DEAL_STATUS_FULL = 2; // 标的状态 满标
    const DEAL_STATUS_FAIL = 3; // 标的状态 流标
    const DEAL_STATUS_REPAY = 4; // 标的状态 还款中
    const DEAL_STATUS_REPAID = 5;// 标的状态 已还清
    const DEAL_STATUS_RESERVING = 6;// 标的状态 预约投标中

    const DEAL_NAME_PREFIX = '100起，'; // 默认标的名前缀


    //投资人群
    const DEAL_CROWD_ALL = 0; //全部用户
    const DEAL_CROWD_NEW = 1; //新手专享
    const DEAL_CROWD_SPEC = 2; //专享
    const DEAL_CROWD_MOBILE = 4; //手机专享
    const DEAL_CROWD_MOBILE_NEW = 8; //手机新手标
    const DEAL_CROWD_SPECIFY_USER = 16; // 指定用户可投
    const DEAL_CROWD_OLD_USER = 32; // 老用户专享
    const DEAL_CROWD_VIP = 33; // vip用户专享
    const DEAL_CROWD_CUSTORM = 34; // 批量导入用户定制

    // 标类型
    const DEAL_TYPE_GENERAL = 0; //普通标
    const DEAL_TYPE_COMPOUND = 1;  //通知贷
    const DEAL_TYPE_EXCHANGE = 2;  //交易所
    const DEAL_TYPE_EXCLUSIVE = 3;  //专享
    const DEAL_TYPE_ALL_P2P = 0;  //所有p2p包含通知贷和普通标

    // 此类型为虚拟类型，deal表中不存在类型为4的记录
    const DEAL_TYPE_SUPERVISION = 4; // 走存管标的类型不含通知贷
    const DEAL_TYPE_PETTYLOAN = 5;//小贷


    const DAY = "天";
    const HOUR = "时";
    const MINUTE = "分";

    const DAY_OF_YEAR = 360;    //金融计算通常将一年作为360天计算
    const DAY_OF_MONTH = 30;    //一月作为30天计算
    const MONTH_OF_YEAR = 12;   //一年中的月数
    const RATE_DIGIT = 5; //利率位数

    // 可以使用红包
    const CAN_USE_BONUS = true;

    const HOLIDAY_REPAY_TYPE_BEFORE = 1;  //节假日提前还款
    const HOLIDAY_REPAY_TYPE_NORMAL = 2;  //节假日当日还款
    const HOLIDAY_REPAY_TYPE_AFTER  = 3;  //节假日顺延还款

    // dealagency与deal表中的机构类型对照表
    static $agencyKey = array(
        DealAgencyEnum::TYPE_GUARANTEE => 'agency_id', // 担保
        DealAgencyEnum::TYPE_CONSULT   => 'advisory_id', // 咨询
        // DealAgencyModel::TYPE_PLATFORM  => 'site_id', // 平台机构在deal_site表中与deal关联
        DealAgencyEnum::TYPE_PAYMENT   => 'pay_agency_id', // 支付
        DealAgencyEnum::TYPE_MANAGEMENT=> 'management_agency_id',
        DealAgencyEnum::TYPE_ADVANCE   => 'advance_agency_id', // 垫付
    );

    /**
     * 节假日还款方式
     *
     * @var string
     **/
    public static $HOLIDAY_REPAY_TYPES = array(
        self::HOLIDAY_REPAY_TYPE_BEFORE => '节假日提前', //节假日提前
        self::HOLIDAY_REPAY_TYPE_NORMAL => '节假日当日', //节假日当日
        self::HOLIDAY_REPAY_TYPE_AFTER  => '节假日顺延', //节假日顺延
    );

    /**
     * 节假日还款方式-在合同中显示的文本
     *
     * @var string
     **/
    public static $HOLIDAY_REPAY_TYPE_CONTRACT = array(
        self::HOLIDAY_REPAY_TYPE_BEFORE => 'A',
        self::HOLIDAY_REPAY_TYPE_NORMAL => 'B',
        self::HOLIDAY_REPAY_TYPE_AFTER => 'C',
    );


    /**
     * 借款状态
     *
     * @var string
     **/
    public static $DEAL_STATUS = array(
        'waiting'     => 0, //等待材料
        'progressing' => 1, //进行中
        'full'        => 2, //满标
        'failed'      => 3, //流标
        'repaying'    => 4, //还款中
        'repaid'      => 5, //已还清
        'reserving'   => 6, //预约投标中
    );





}
