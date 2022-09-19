<?php
namespace NCFGroup\Protos\Ptp\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class VipEnum extends AbstractEnum {
    //vip服务端接口路径
    const VIP_SERVICE_DIR = 'vip';
    //vip业务方标识：token的前缀。token规则:业务前缀_sourceId_
    const VIP_SOURCE_INIT = 'init';            //初始化
    const VIP_SOURCE_ADMIN = 'admin';          //后台补发
    const VIP_SOURCE_P2P = 'p2p';              //p2p
    const VIP_SOURCE_ZHUANXIANG = 'zhuanxiang';//专享
    const VIP_SOURCE_GOLD = 'gold';            //黄金定期
    const VIP_SOURCE_CHECKIN = 'checkin';      //签到
    const VIP_SOURCE_INVITE = 'invite';        //邀请首投
    const VIP_SOURCE_EXPIRE = 'expire';        //过期扣除
    const VIP_SOURCE_DT = 'dt';                //智多鑫
    const VIP_SOURCE_YJB = 'yjb';              //优金宝
    //vip业务方来源值
    const VIP_SOURCE_VALUE_P2P = 1;
    const VIP_SOURCE_VALUE_ZHUANXIANG = 2;
    const VIP_SOURCE_VALUE_GOLD = 3;
    const VIP_SOURCE_VALUE_INVITE = 4;
    const VIP_SOURCE_VALUE_CHECKIN = 5;
    const VIP_SOURCE_VALUE_ADMIN = 6;
    const VIP_SOURCE_VALUE_INIT = 7;
    const VIP_SOURCE_VALUE_EXPIRE = 8;
    const VIP_SOURCE_VALUE_DT = 9;
    const VIP_SOURCE_VALUE_YJB = 10;
    //vip记录类型
    const VIP_ACTION_INIT = 0;                  //创建账户
    const VIP_ACTION_UPGRADE = 1;               //升级
    const VIP_ACTION_SAFEGUARDGRADE = 2;        //保级
    const VIP_ACTION_REMOVE_SAFEGUARDGRADE = 3; //解除保级
    const VIP_ACTION_DEGRADE = 4;               //降级

    //VIP经验值类型
    const VIP_POINT_ADD = 1;                    //经验增加
    const VIP_POINT_EXPIRE = 2;                 //经验过期

    const VIP_GRADE_PT = 0; //普通用户
    const VIP_GRADE_QT = 1; //青铜会员
    const VIP_GRADE_BY = 2; //白银会员
    const VIP_GRADE_HJ = 3; //黄金会员
    const VIP_GRADE_BJ = 4; //铂金会员
    const VIP_GRADE_ZS = 5; //钻石会员
    const VIP_GRADE_HG = 6; //皇冠会员

    const VIP_GRADE_ALIAS_PT = 'pt'; //普通用户
    const VIP_GRADE_ALIAS_QT = 'qt'; //青铜会员
    const VIP_GRADE_ALIAS_BY = 'by'; //白银会员
    const VIP_GRADE_ALIAS_HJ = 'hj'; //黄金会员
    const VIP_GRADE_ALIAS_BJ = 'bj'; //铂金会员
    const VIP_GRADE_ALIAS_ZS = 'zs'; //钻石会员
    const VIP_GRADE_ALIAS_HG = 'hg'; //皇冠会员

    const PRIVILEGE_INTEREST = 1; //投资加息
    const PRIVILEGE_GIFT = 2; //升级礼包
    const PRIVILEGE_BIRTHDAY_DISCOUNT = 3; //生日福利券
    const PRIVILEGE_ANNIVER_DISCOUNT = 4; //周年福利券
    const PRIVILEGE_OFFLINE_SALON = 5; //线下沙龙
    const PRIVILEGE_ASSET_CUSTOMIZE = 6; //资产定制服务
    const PRIVILEGE_CUSTOMER_SERVICE = 7; //400优先进线
    const PRIVILEGE_SENIOR_CONSULTANT = 8; //高级咨询服务顾问
    const PRIVILEGE_CONSULTANT = 9; //咨询服务顾问



    // VIP业务来源值alias=>value
    public static $vipSourceMap = array(
        self::VIP_SOURCE_P2P                => self::VIP_SOURCE_VALUE_P2P,
        self::VIP_SOURCE_ZHUANXIANG         => self::VIP_SOURCE_VALUE_ZHUANXIANG,
        self::VIP_SOURCE_GOLD               => self::VIP_SOURCE_VALUE_GOLD,
        self::VIP_SOURCE_INVITE             => self::VIP_SOURCE_VALUE_INVITE,
        self::VIP_SOURCE_CHECKIN            => self::VIP_SOURCE_VALUE_CHECKIN,
        self::VIP_SOURCE_ADMIN              => self::VIP_SOURCE_VALUE_ADMIN,
        self::VIP_SOURCE_INIT               => self::VIP_SOURCE_VALUE_INIT,
        self::VIP_SOURCE_EXPIRE             => self::VIP_SOURCE_VALUE_EXPIRE,
        self::VIP_SOURCE_DT                 => self::VIP_SOURCE_VALUE_DT,
        self::VIP_SOURCE_YJB                => self::VIP_SOURCE_VALUE_YJB,
    );

    // VIP业务来源值value=>alias
    public static $vipSourceMapToAlias = array(
        self::VIP_SOURCE_VALUE_P2P          => self::VIP_SOURCE_P2P,
        self::VIP_SOURCE_VALUE_ZHUANXIANG   => self::VIP_SOURCE_ZHUANXIANG,
        self::VIP_SOURCE_VALUE_GOLD         => self::VIP_SOURCE_GOLD,
        self::VIP_SOURCE_VALUE_INVITE       => self::VIP_SOURCE_INVITE,
        self::VIP_SOURCE_VALUE_CHECKIN      => self::VIP_SOURCE_CHECKIN,
        self::VIP_SOURCE_VALUE_ADMIN        => self::VIP_SOURCE_ADMIN,
        self::VIP_SOURCE_VALUE_INIT         => self::VIP_SOURCE_INIT,
        self::VIP_SOURCE_VALUE_EXPIRE       => self::VIP_SOURCE_EXPIRE,
        self::VIP_SOURCE_VALUE_DT           => self::VIP_SOURCE_DT,
        self::VIP_SOURCE_VALUE_YJB          => self::VIP_SOURCE_YJB,
    );

    // VIP业务来源描述
    public static $vipSourceDesc = array(
        self::VIP_SOURCE_P2P => 'P2P出借',
        self::VIP_SOURCE_ZHUANXIANG => '投资专享',
        self::VIP_SOURCE_GOLD => '购买优长金',
        self::VIP_SOURCE_INVITE => '邀请用户',
        self::VIP_SOURCE_CHECKIN => '签到',
        self::VIP_SOURCE_ADMIN => '后台补发',
        self::VIP_SOURCE_INIT => '初始化',
        self::VIP_SOURCE_EXPIRE => '过期扣除',
        self::VIP_SOURCE_DT => '加入智多新',
        self::VIP_SOURCE_YJB => '购买优金宝',
    );

    // VIP会员等级对照表
    public static $vipGradeAliasToNo = array(
        self::VIP_GRADE_ALIAS_PT => self::VIP_GRADE_PT,
        self::VIP_GRADE_ALIAS_QT => self::VIP_GRADE_QT,
        self::VIP_GRADE_ALIAS_BY => self::VIP_GRADE_BY,
        self::VIP_GRADE_ALIAS_HJ => self::VIP_GRADE_HJ,
        self::VIP_GRADE_ALIAS_BJ => self::VIP_GRADE_BJ,
        self::VIP_GRADE_ALIAS_ZS => self::VIP_GRADE_ZS,
        self::VIP_GRADE_ALIAS_HG => self::VIP_GRADE_HG
    );

    public static $vipGradeNoToAlias = array(
        self::VIP_GRADE_PT => self::VIP_GRADE_ALIAS_PT,
        self::VIP_GRADE_QT => self::VIP_GRADE_ALIAS_QT,
        self::VIP_GRADE_BY => self::VIP_GRADE_ALIAS_BY,
        self::VIP_GRADE_HJ => self::VIP_GRADE_ALIAS_HJ,
        self::VIP_GRADE_BJ => self::VIP_GRADE_ALIAS_BJ,
        self::VIP_GRADE_ZS => self::VIP_GRADE_ALIAS_ZS,
        self::VIP_GRADE_HG => self::VIP_GRADE_ALIAS_HG
    );

    // VIP记录类型
    public static $vipLogTypes = array(
        self::VIP_ACTION_INIT => '初始化',
        self::VIP_ACTION_UPGRADE => '升级',
        self::VIP_ACTION_SAFEGUARDGRADE => '保级',
        self::VIP_ACTION_REMOVE_SAFEGUARDGRADE => '解除保级',
        self::VIP_ACTION_DEGRADE => '降级'
    );

    // VIP经验值类型
    public static $vipPointTypes = array(
        self::VIP_POINT_ADD => '经验增加',
        self::VIP_POINT_EXPIRE => '经验过期'
    );

    //VIP会员等级(minInvest 最小投资额 万为单位)
    public static $vipGrade = array(
            self::VIP_GRADE_PT => array('vipGrade' => 0, 'name' => '普通用户', 'alias' => 'pt'),
            self::VIP_GRADE_QT => array('vipGrade' => 1, 'name' => '青铜会员', 'alias' => 'qt', 'minInvest' => 5, 'raiseInterest' => 0, 'giftValue' => 288, 'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/6d7f7d2f5445c7369cfb7a625cd155c9/index.jpg', 'privilege' => array(self::PRIVILEGE_GIFT,self::PRIVILEGE_BIRTHDAY_DISCOUNT, self::PRIVILEGE_ANNIVER_DISCOUNT, self::PRIVILEGE_ASSET_CUSTOMIZE,self::PRIVILEGE_CUSTOMER_SERVICE,self::PRIVILEGE_CONSULTANT)),
            self::VIP_GRADE_BY => array('vipGrade' => 2, 'name' => '白银会员', 'alias' => 'by', 'minInvest' => 20, 'raiseInterest' => 0, 'giftValue' => 518, 'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/17/f1030f8c604ee7380ab1a648b7e4a4bc/index.jpg', 'privilege' => array(self::PRIVILEGE_GIFT,self::PRIVILEGE_BIRTHDAY_DISCOUNT,self::PRIVILEGE_ANNIVER_DISCOUNT,self::PRIVILEGE_ASSET_CUSTOMIZE,self::PRIVILEGE_CUSTOMER_SERVICE,self::PRIVILEGE_CONSULTANT)),
            self::VIP_GRADE_HJ => array('vipGrade' => 3, 'name' => '黄金会员', 'alias' => 'hj', 'minInvest' => 50, 'raiseInterest' => 0.1, 'giftValue' => 888, 'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/9b64a4ae8f3fa2b63ac41ec7479f2519/index.jpg', 'privilege' => array(self::PRIVILEGE_INTEREST, self::PRIVILEGE_GIFT,self::PRIVILEGE_BIRTHDAY_DISCOUNT, self::PRIVILEGE_ANNIVER_DISCOUNT, self::PRIVILEGE_OFFLINE_SALON, self::PRIVILEGE_ASSET_CUSTOMIZE,self::PRIVILEGE_CUSTOMER_SERVICE,self::PRIVILEGE_SENIOR_CONSULTANT)),
            self::VIP_GRADE_BJ => array('vipGrade' => 4, 'name' => '铂金会员', 'alias' => 'bj', 'minInvest' => 100, 'raiseInterest' => 0.11, 'giftValue' => 1318, 'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/7857952d7bb8ae4c2c3561f8d9b4b711/index.jpg', 'privilege' => array(self::PRIVILEGE_INTEREST, self::PRIVILEGE_GIFT,self::PRIVILEGE_BIRTHDAY_DISCOUNT, self::PRIVILEGE_ANNIVER_DISCOUNT, self::PRIVILEGE_OFFLINE_SALON, self::PRIVILEGE_ASSET_CUSTOMIZE,self::PRIVILEGE_CUSTOMER_SERVICE,self::PRIVILEGE_SENIOR_CONSULTANT)),
            self::VIP_GRADE_ZS => array('vipGrade' => 5, 'name' => '钻石会员', 'alias' => 'zs', 'minInvest' => 300, 'raiseInterest' => 0.12, 'giftValue' => 2288, 'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/56496af28ac2a931c68dc8cc987f039e/index.jpg', 'privilege' => array(self::PRIVILEGE_INTEREST, self::PRIVILEGE_GIFT,self::PRIVILEGE_BIRTHDAY_DISCOUNT, self::PRIVILEGE_ANNIVER_DISCOUNT, self::PRIVILEGE_OFFLINE_SALON, self::PRIVILEGE_ASSET_CUSTOMIZE,self::PRIVILEGE_CUSTOMER_SERVICE,self::PRIVILEGE_SENIOR_CONSULTANT)),
            self::VIP_GRADE_HG => array('vipGrade' => 6, 'name' => '皇冠会员', 'alias' => 'hg', 'minInvest' => 1000, 'raiseInterest' => 0.15, 'giftValue' => 11888, 'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/90ea5c6aef43457fc5cb6b0a12fda60f/index.jpg', 'privilege' => array(self::PRIVILEGE_INTEREST, self::PRIVILEGE_GIFT,self::PRIVILEGE_BIRTHDAY_DISCOUNT, self::PRIVILEGE_ANNIVER_DISCOUNT, self::PRIVILEGE_OFFLINE_SALON, self::PRIVILEGE_ASSET_CUSTOMIZE,self::PRIVILEGE_CUSTOMER_SERVICE,self::PRIVILEGE_SENIOR_CONSULTANT)),
    );

    //VIP等级特权
    public static $vipPrivilege = array(
            self::PRIVILEGE_INTEREST => array('privilegeId' => 1, 'name' => '投资加息红包', 'describe' => '{$n}%投资加息' ,'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/4720f5a01b64d009daccea80f274d663/index.jpg', 'detail' => '投资加息红包按照单笔投资的年化投资额计算，规则如下：</br> 1、黄金VIP(含)以上等级的会员投资P2P、专享和优长金产品可获得投资加息红包；</br> 2、不同等级的VIP会员，享受不同比例的投资加息红包。</br>将在所投项目放款后的次日凌晨，根据当前等级发放加息红包。'),
            self::PRIVILEGE_GIFT => array('privilegeId' => 2, 'name' => '升级大礼包', 'describe' => '价值{$m}元' ,'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/49f54a027ada06bdcd1f3ac122fd5d72/index.jpg','detail' => '每当您升级到不同VIP等级，将为您送上升级大礼包：</br>1、同一等级升级礼包，每个用户只可享受一次。</br>2、若用户投资后发生用户等级需上升两个档位或两个档位以上时，则用户享受升级对应的全部礼包。</br>不满足以上条件的VIP会员不发放升级大礼包。'),
            self::PRIVILEGE_BIRTHDAY_DISCOUNT => array('privilegeId' => 3, 'name' => '生日福利', 'describe' => '惊喜小礼物' ,'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/267e3709feb2a0f877617f2e07a13a94/index.jpg', 'detail' => '网信会为生日的VIP用户发放生日专属投资券；不同等级的VIP会员，享受不同的投资券。'),
            self::PRIVILEGE_ANNIVER_DISCOUNT => array('privilegeId' => 4, 'name' => '周年福利', 'describe' => '周年纪念奖' ,'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/8b190f1b99cd391c671505abf669f26a/index.jpg','detail' => '网信会根据VIP用户的注册时间，在周年纪念日时发放感恩回馈礼；</br>不同等级的VIP会员，享受不同的周年福利。'),
            self::PRIVILEGE_OFFLINE_SALON => array('privilegeId' => 5, 'name' => '线下沙龙', 'describe' => 'VIP欢聚一堂' ,'imgUrl' => 'http://static.firstp2p.com/attachment/201709/12/14/cc4d05180b46c6b1a47f2a78c46d4985/index.jpg','detail' => '黄金VIP（含）以上等级的会员将有机会参加网信为VIP会员举办的线下沙龙活动，如海外医疗、海外投资、珠宝鉴赏等；</br> 线下沙龙活动目前仅限北京、上海。'),
            self::PRIVILEGE_ASSET_CUSTOMIZE => array('privilegeId' => 6, 'name' => '资产定制服务', 'describe' => '量身打造' ,'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/fee7ca77fcfe6bb8e5fcc0a08176aa40/index.jpg','detail' => '黄金VIP(含)以上等级的会员可通过网信客服热线或专属咨询服务顾问,申请资产定制,以便提前预留资产,让您的投资“先”人一步。'),
            self::PRIVILEGE_CUSTOMER_SERVICE => array('privilegeId' => 7, 'name' => '客服专线', 'describe' => '电话优先进线' ,'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/2aac8bd2871c0a2d0108953f6cb24f32/index.jpg','detail' => '黄金VIP(含)以上等级的会员用注册手机拨打网信客服热线,系统将自动识别VIP身份,匹配 VIP专属客服,优先被接听,尽享尊贵体验。'),
            self::PRIVILEGE_SENIOR_CONSULTANT => array('privilegeId' => 8, 'name' => '高级咨询服务顾问', 'describe' => '提供投资建议' ,'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/e4dde44aeb2a16801e580fc48d96b221/index.jpg','detail' => '升级为黄金VIP后,高级专属咨询服务顾问将通过专属微信号或网信客服热线与VIP会员取得联系,高级专属咨询服务顾问会依照VIP会员需求,提供产品咨询,投资建议等咨询服务。'),
            self::PRIVILEGE_CONSULTANT => array('privilegeId' => 9, 'name' => '咨询服务顾问', 'describe' => '提供投资建议' ,'imgUrl' => 'http://static.firstp2p.com/attachment/201707/21/18/8c56535e293ebf6d5cc6a3ffe9676df3/index.jpg','detail' => '获得VIP身份后,专属咨询服务顾问将通过专属微信号或网信客服热线与VIP会员取得联系,专属咨询服务顾问会依照VIP会员需求,提供产品咨询,投资建议等咨询服务。'),
    );

    //特权免责声明
    public static $privilegeDisclaimer = '注：权益的最终解释权归网信所有。网信有权根据市场情况、会员需求等因素调整权益内容。';

    //VIP特权图片地址-点亮图片地址
    public static $privilegeImgUrl = array(
            self::PRIVILEGE_INTEREST => array(
                    self::VIP_GRADE_HJ => "http://static.firstp2p.com/attachment/201707/21/18/2b3fc88d0d2a76087693a853ad5e67a5/index.jpg",
                    self::VIP_GRADE_BJ => "http://static.firstp2p.com/attachment/201707/21/18/a4e1a8c22e1846ee0d48b25381c68f8f/index.jpg",
                    self::VIP_GRADE_ZS => "http://static.firstp2p.com/attachment/201707/21/18/465003cbebf00eeff77b9e1d6b3b9845/index.jpg",
                    self::VIP_GRADE_HG => "http://static.firstp2p.com/attachment/201707/21/18/6577a018598845d76bd56bb4605e3ecb/index.jpg"),
            self::PRIVILEGE_GIFT => array(
                    self::VIP_GRADE_QT => "http://static.firstp2p.com/attachment/201707/21/18/dbc3a0a31b7f51ad1ed44fab8e89a5b3/index.jpg",
                    self::VIP_GRADE_BY => "http://static.firstp2p.com/attachment/201707/21/17/a5aa2761c15e847ba1c99f3bc2e3c424/index.jpg",
                    self::VIP_GRADE_HJ => "http://static.firstp2p.com/attachment/201707/21/18/08f4dbabdf6ddca111ea09b086bda01f/index.jpg",
                    self::VIP_GRADE_BJ => "http://static.firstp2p.com/attachment/201707/21/18/165faea85d86c066f32dfed24dfd892a/index.jpg",
                    self::VIP_GRADE_ZS => "http://static.firstp2p.com/attachment/201707/21/18/110581fa7e2121b43c69e1a52f220f7b/index.jpg",
                    self::VIP_GRADE_HG => "http://static.firstp2p.com/attachment/201707/21/18/b8ec05fefadbbd4af4dbb13e487ad9c4/index.jpg"),
            self::PRIVILEGE_BIRTHDAY_DISCOUNT => array(
                    self::VIP_GRADE_QT => "http://static.firstp2p.com/attachment/201707/21/18/25bce20888a64c1bedccb39f942b9745/index.jpg",
                    self::VIP_GRADE_BY => "http://static.firstp2p.com/attachment/201707/21/17/9f3974b4db41bc244349c9f0696bd497/index.jpg",
                    self::VIP_GRADE_HJ => "http://static.firstp2p.com/attachment/201707/21/18/c3dbb67d180bbd38bf453a406db8a057/index.jpg",
                    self::VIP_GRADE_BJ => "http://static.firstp2p.com/attachment/201707/21/18/54f4ecef513ed7a9c67f59532466b7c0/index.jpg",
                    self::VIP_GRADE_ZS => "http://static.firstp2p.com/attachment/201707/21/18/4da9816099c332ce63859c042e570536/index.jpg",
                    self::VIP_GRADE_HG => "http://static.firstp2p.com/attachment/201707/21/18/fa14a132384973aabec3ddbecafc4380/index.jpg"),
            self::PRIVILEGE_ANNIVER_DISCOUNT => array(
                    self::VIP_GRADE_QT => "http://static.firstp2p.com/attachment/201707/21/18/a57fbecce3a91490dca92679d0a9d2d3/index.jpg",
                    self::VIP_GRADE_BY => "http://static.firstp2p.com/attachment/201707/21/17/e703d8cef80d29c2f4b4342fa3d65fc4/index.jpg",
                    self::VIP_GRADE_HJ => "http://static.firstp2p.com/attachment/201707/21/18/1bce0e032453fd2e2fe35ae28f86e1e9/index.jpg",
                    self::VIP_GRADE_BJ => "http://static.firstp2p.com/attachment/201707/21/18/20886aac5812244af8465faedf8e49dd/index.jpg",
                    self::VIP_GRADE_ZS => "http://static.firstp2p.com/attachment/201707/21/18/4aab25948e0fff8c6c12fa8c535ab532/index.jpg",
                    self::VIP_GRADE_HG => "http://static.firstp2p.com/attachment/201707/21/18/c920f19c94c9f9ff51a647541eb27d03/index.jpg"),
            self::PRIVILEGE_ASSET_CUSTOMIZE => array(
                    self::VIP_GRADE_QT => "http://static.firstp2p.com/attachment/201707/21/18/13971ed336e49650bb5a5ea31707402b/index.jpg",
                    self::VIP_GRADE_BY => "http://static.firstp2p.com/attachment/201707/21/18/f597e8f6a0cb18961f2cec715565b24d/index.jpg",
                    self::VIP_GRADE_HJ => "http://static.firstp2p.com/attachment/201707/21/18/48a4fc0b3ed1b152bee4b2a9c9ba0265/index.jpg",
                    self::VIP_GRADE_BJ => "http://static.firstp2p.com/attachment/201707/21/18/037ff4a295b16e39bd6df2c7810ff8ed/index.jpg",
                    self::VIP_GRADE_ZS => "http://static.firstp2p.com/attachment/201707/21/19/d235bcd3937930a090324ac21764d53a/index.jpg",
                    self::VIP_GRADE_HG => "http://static.firstp2p.com/attachment/201707/21/18/b37ee99eaa4d05f0b3329868abecda92/index.jpg"),
            self::PRIVILEGE_CUSTOMER_SERVICE => array(
                    self::VIP_GRADE_QT => "http://static.firstp2p.com/attachment/201707/21/18/6dc941213a1a2867632d362ad2ec68e0/index.jpg",
                    self::VIP_GRADE_BY => "http://static.firstp2p.com/attachment/201707/21/17/c2853db2ae79d9e5f68d501812b89fd8/index.jpg",
                    self::VIP_GRADE_HJ => "http://static.firstp2p.com/attachment/201707/21/18/8042f928cc3dcdd9173e36e8c11e252b/index.jpg",
                    self::VIP_GRADE_BJ => "http://static.firstp2p.com/attachment/201707/21/18/27c38b7e5030599303277f157c59053e/index.jpg",
                    self::VIP_GRADE_ZS => "http://static.firstp2p.com/attachment/201707/21/18/f42f2d3bc57165e8b6b28c5c65a0860a/index.jpg",
                    self::VIP_GRADE_HG => "http://static.firstp2p.com/attachment/201707/21/18/e5cee8ddbb640be961f6ea165be3f637/index.jpg"),
            self::PRIVILEGE_SENIOR_CONSULTANT => array(
                    self::VIP_GRADE_HJ => "http://static.firstp2p.com/attachment/201707/21/18/005b8f38d56d57a3d9dbcc11ef0e759e/index.jpg",
                    self::VIP_GRADE_BJ => "http://static.firstp2p.com/attachment/201707/21/18/66f084135070482a27c6d4b2523466d7/index.jpg",
                    self::VIP_GRADE_ZS => "http://static.firstp2p.com/attachment/201707/21/18/63ab86ecee4fffe6c7919459cc5281a9/index.jpg",
                    self::VIP_GRADE_HG => "http://static.firstp2p.com/attachment/201707/21/18/0b50b322eae86fc458284f2887350235/index.jpg"),
            self::PRIVILEGE_CONSULTANT => array(
                    self::VIP_GRADE_QT => "http://static.firstp2p.com/attachment/201707/21/18/88af4e4e5af5a54eb850523646d2b6d3/index.jpg",
                    self::VIP_GRADE_BY => "http://static.firstp2p.com/attachment/201707/21/17/05a5b301e527d3a0cf72c43bff2fa4bd/index.jpg"),
            self::PRIVILEGE_OFFLINE_SALON => array(
                    self::VIP_GRADE_HJ => "http://static.firstp2p.com/attachment/201707/25/11/0e1489e27fd00cd2eb62523b75798c30/index.jpg",
                    self::VIP_GRADE_BJ => "http://static.firstp2p.com/attachment/201707/25/11/cf15683c11486132f32459e2791030f8/index.jpg",
                    self::VIP_GRADE_ZS => "http://static.firstp2p.com/attachment/201707/25/11/be835101d636df49afc3b6403b500bc6/index.jpg",
                    self::VIP_GRADE_HG => "http://static.firstp2p.com/attachment/201707/25/11/1e7603b44129ee86a6846e16d8ab9fb3/index.jpg"),
    );
}

