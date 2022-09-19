<?php
namespace NCFGroup\Protos\Medal\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class MedalEnum extends AbstractEnum {
    // medal相关
    const MEDAL_SHOW_TIME_FOREVER = 1;     // 持续型，一直有效
    const MEDAL_SHOW_TIME_ZONE = 2;     // 时间段型，有固定上下线时间
    const MEDAL_EFFECTTIVE = 1; // 有效
    const MEDAL_INEFFECTTIVE = 2; // 无效
    const MEDAL_HAS_AWARD = 1;
    const MEDAL_NO_AWARD = 2;
    const MEDAL_RULE_RELATION_AND = 1;
    const MEDAL_RULE_RELATION_OR = 2;
    const MEDAL_LIMITED = 1;
    const MEDAL_UNLIMITED = 2;

    // medal rule 相关
    const RULE_STAT_TIME_FOREVER = 1; // 一直统计
    const RULE_STAT_TIME_ZONE = 2; // 按时间段统计
    const RULE_EVENT_DEAL = 1; //投资
    const RULE_EVENT_CHARITY = 2; //公益标
    const RULE_EVENT_BONUS_USE = 3; //使用红包
    const RULE_EVENT_INVITE_DEAL = 4; //邀请投资
    const RULE_EVENT_INVITE_FIRST_DEAL = 5; //邀请首投
    const RULE_EVENT_INVEST_FROM_APP = 6; //APP投资
    const RULE_EVENT_DEPOSIT = 7;         //充值

    const RULE_STAT_ACCUMULATE_COUNT = 1; //累计次数
    const RULE_STAT_ACCUMULATE_MONEY = 2; //累计金额
    const RULE_STAT_ACCUMULATE_HEAD_COUNT = 4; //累计人数
    const RULE_STAT_SERIAL_DAYS = 8; //连续天数

    // medal user 相关
    const AWARD_ACQUIRED = 1; //已领取
    const AWARD_UNACQUIRE = 2; //未领取

    // medal progress相关
    const PROGRESS_COMPLETED = 1; //已完成
    const PROGRESS_UNCOMPLETE = 2; //未完成

    //TAG相关的配置的key, hash数据结构
    const BEGINNER_MEDAL_TAG_KEY = "/hash/beginner_medal_tag_key";
    //文案相关的配置的key, zset数据结构
    const BEGINNER_MEDAL_PIC_KEY = "/zset/beginner_medal_pic_key";
   
    const MEDAL_GROUP_TYPE_BEGINNER = "beginner";
    const MEDAL_GROUP_TYPE_NORMAL = "normal";

    public static $medalShowTypes = array(
        self::MEDAL_SHOW_TIME_FOREVER => '持续性',
        self::MEDAL_SHOW_TIME_ZONE => '阶段性'
    );

    public static $medalStatus = array(
        self::MEDAL_EFFECTTIVE => '有效',
        self::MEDAL_INEFFECTTIVE => '无效'
    );

    public static $medalRuleRelation = array(
        self::MEDAL_RULE_RELATION_AND => '且',
        self::MEDAL_RULE_RELATION_OR => '或'
    );

    public static $ruleStatTimeTypes = array(
        self::RULE_STAT_TIME_FOREVER => '持续性',
        self::RULE_STAT_TIME_ZONE => '阶段性',
    );

    public static $ruleStatTypes = array(
        self::RULE_STAT_ACCUMULATE_COUNT => '累计次数',
        self::RULE_STAT_ACCUMULATE_MONEY => '累计金额',
        self::RULE_STAT_ACCUMULATE_HEAD_COUNT => '累计人数',
        self::RULE_STAT_SERIAL_DAYS => '连续天数',
    );

    public static $ruleEvents = array(
        self::RULE_EVENT_DEAL => '投资',
        self::RULE_EVENT_CHARITY => '公益标',
        self::RULE_EVENT_BONUS_USE => '使用红包',
        //self::RULE_EVENT_INVITE_DEAL => '邀请投资',
        self::RULE_EVENT_INVITE_FIRST_DEAL => '邀请首投',
        self::RULE_EVENT_INVEST_FROM_APP => 'APP投资',
        self::RULE_EVENT_DEPOSIT => '充值',
    );

    public static $awardAcquireStatus = array(
        self::AWARD_ACQUIRED => '已领取',
        self::AWARD_UNACQUIRE => '未领取'
    );
}
