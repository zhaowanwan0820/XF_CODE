<?php

namespace NCFGroup\Protos\O2O\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class GameEnum extends AbstractEnum {
    const ALLOWANCE_TYPE_COUPON = 1;            // 礼券
    const ALLOWANCE_TYPE_DISCOUNT = 2;          // 投资券

    const EVENT_STATUS_DO = 0;                  // 未开始
    const EVENT_STATUS_DOGING = 1;              // 进行中
    const EVENT_STATUS_DONE = 2;                // 已结束

    const EVENT_LIMIT_NONE = 0;                 // 没有限制
    const EVENT_LIMIT_WITH_ACTIVITY = 1;        // 次/活动
    const EVENT_LIMIT_WITH_DAY = 2;             // 次/每日

    const SOURCR_TYPE_INVEST = 1;               // 投资增加
    const SOURCR_TYPE_GAMING = 2;               // 游戏消耗减少
    const SOURCR_TYPE_HONGBAO = 3;              // 红包充值增加
    const SOURCE_TYPE_AR_BONUS = 4;             // AR扫码红包
    const SOURCE_TYPE_MATCH_GUESS = 5;          // 比赛竞猜
    const SOURCE_TYPE_GUESS_SUCCESS = 6;        // 竞猜成功
    const SOURCE_TYPE_MATCH_GIVE = 7;           // 比赛赠送
    const SOURCE_TYPE_INIT_GIVE = 8;            // 初始赠送
    const SOURCE_TYPE_CHECKIN = 9;              // 签到
    const SOURCE_TYPE_INVITE = 10;              // 邀请用户
    const SOURCE_TYPE_PAUL_MEDAL = 11;          // 保罗勋章
    const SOURCE_TYPE_ADMIN_INCR = 12;          // 管理员增加
    const SOURCE_TYPE_ADMIN_DECR = 13;          // 管理员减少
    const SOURCE_TYPE_SCORE_CONVERT = 14;       // 积分折算

    const TEMPLATE_ROULETTE = 1;                // 大转盘
    const TEMPLATE_SCRATCH_CARD = 2;            // 刮刮卡
    const TEMPLATE_AR_BONUS = 3;                // AR红包
    const TEMPLATE_MATCH = 4;                   // 比赛竞猜

    const STATISTICS_TYPE_SHARE = 1;            // 分享
    const STATISTICS_TYPE_PV = 2;               // 浏览

    const PRIZE_ACTION_ADD    = 1;              // 添加奖品
    const PRIZE_ACTION_UPDATE = 2;              // 更新奖品
    const PRIZE_ACTION_DELETE = 3;              // 删除奖品

    // 比赛的状态列表
    const MATCH_STATUS_DO = 1;                  // 未开始
    const MATCH_STATUS_GUESSING = 2;            // 竞猜中
    const MATCH_STATUS_DOING = 3;               // 比赛中
    const MATCH_STATUS_DONE = 4;                // 已结束
    const MATCH_STATUS_WAIT = 5;                // 比赛未开始，等待中

    const MATCH_STATUS_FLAG_DISABLE = 1;        // 停用
    const MATCH_STATUS_FLAG_ENABLE = 2;         // 启用

    // 比赛模式
    const MATCH_MODE_MORE_EQ_LESS = 1;          // 正负平
    const MATCH_MODE_MORE_LESS = 2;             // 正负
    const MATCH_MODE_ONE_TWO = 3;               // 冠亚军

    // 竞猜结果
    const GUESS_STATUS_NO = -1;                 // 未竞猜
    const GUESS_STATUS_INIT = 0;                // 待确认
    const GUESS_STATUS_SUCCESS = 1;             // 成功
    const GUESS_STATUS_FAILED = 2;              // 失败

    // 竞猜选择
    const GUESS_CHOICE_A = 'A';                 // A队获胜
    const GUESS_CHOICE_B = 'B';                 // B队获胜
    const GUESS_CHOICE_DRAW = 'DRAW';           // 平
    // 积分token前缀
    const GUESS_SOURCE_BID = 'bid_';            //投资
    const GUESS_SOURCE_INVITE = 'invite_';      //邀请首投
    const GUESS_SOURCE_CHECKIN = 'checkin_';    //签到
    const GUESS_SOURCE_GUESS = 'guess_';        //竞猜
    const GUESS_SOURCE_INIT_GIVE = 'initgive_'; //初始赠送
    const GUESS_SOURCE_WIN = 'win_';            //竞猜成功瓜分奖励
    const GUESS_SOURCE_SUCCESS_REWARD = 'successreward_'; //竞猜成功额外奖励
    const GUESS_SOURCE_FAILED_REWARD = 'failedreward_'; //竞猜失败额外奖励
    const GUESS_SOURCE_PAUL_MEDAL = 'paulmedal_'; // 保罗勋章
    const GUESS_SOURCE_ADMIN_INCR = 'adminincr_'; // 管理员增加
    const GUESS_SOURCE_ADMIN_DECR = 'admindecr_'; // 管理员减少
    const GUESS_SOURCE_SCORE_CONVERT = 'scoreconvert_'; // 世界杯积分折算

    // 活动积分规则
    const GUESS_CHECKIN_START_TIME = '2018-06-13';
    const GUESS_POINTS_START_TIME = '2018-06-13';
    const GUESS_POINTS_END_TIME = '2018-07-16';
    const GUESS_PEAK_NIGHT_START_TIME = '2018-07-12 09:00';
    const GUESS_PEAK_NIGHT_END_TIME = '2018-07-15 23:00';

    const GUESS_INIT_GIVEN_POINTS = 10;         // 每名老用户直接领取，初始竞猜10积分，仅可领取一次
    const GUESS_INVEST_3000_6000_POINTS = 10;   // 3000≤单笔投资金额<6000，用户此笔投资可获得10积分；
    const GUESS_INVEST_6000_10000_POINTS = 25;  // 6000≤单笔投资金额<10000，用户此笔投资可获得25积分；
    const GUESS_INVEST_10000_30000_POINTS = 45; // 10000≤单笔投资金额<30000，用户此笔投资可获得45积分；
    const GUESS_INVEST_30000_50000_POINTS = 150;// 30000≤单笔投资金额<50000，用户此笔投资可获得150积分；
    const GUESS_INVEST_50000_100000_POINTS = 280;// 50000≤单笔投资金额<100000，用户此笔投资可获得280积分；
    const GUESS_INVEST_100000_POINTS = 600;     // 100000≤单笔投资金额<无上限，用户此笔投资可获得600积分；
    const GUESS_INVITER_POINTS = 150;           // 用户邀新且首投，可获取150积分值，无上限
    const GUESS_PAUL_MDEAL_POINTS = 100;        // 在活动时间内，用户竞猜成功次数大于等于5次，则获得“保罗称号”与100积分，全程活动仅一次
    const GUESS_CHECKIN_POINTS = 5;             // 用户签到可获得5积分

    /**
     * 排行榜类型
     */
    const RANK_INVEST_AMOUNT             = 1;        // 投资金额
    const RANK_INVEST_ANNUALIZED_AMOUNT  = 2;        // 投资年化金额
    const RANK_INVITE_COUNT              = 3;        // 拉新数目
    const RANK_INVITE_AMOUNT             = 4;        // 拉新金额
    const RANK_INVITE_ANNUALIZED_AMOUNT  = 5;        // 拉新年化金额

    /**
     * 排行榜统计的业务类型
     */
    const RANK_DEAL_TYPE_P2P            = 1;
    const RANK_DEAL_TYPE_EXCLUSIVE      = 2;


    // 默认的活动详情
    public static $DEFAULT_EVENT_DETAIL = array(
        'nonStr' => '',
        'timestamp' => '',
        'sign' => '',
        'prizeSettings' => '[]',
        'eventDesc' => '',
        'gameTemplate' => 'roulette',
        'shareIcon' => '',
        'shareDesc' => '游戏活动',
        'shareTitle' => '游戏活动',
        'userLeftTimes' => 0,
        'isDisable' => 1,
        'status' => self::EVENT_STATUS_DO
    );

    // 世界杯活动屏蔽不参加
    public static $WORLDCUP_BLACKLIST_USERGROUP = array(
        392,//主站_平台_企业投资户
        391,//主站_平台_结算户机构
        31,//主站_平台_交易机构（中新小贷）
        3,//主站_平台_交易机构
        396,//粤港贷_资金渠道_机构
        344,//盈华财富_资金渠道_机构（门店）
        427,//盈华财富_资金渠道_机构（僵尸户）
        253,//网信金服_资金渠道_机构（一分）
        438,//网信金服_资金渠道_机构（金服）
        254,//网信金服_资金渠道_机构（二分）
        424,//联合财富_资金渠道_机构（僵尸户）
        428,//弘达财富_资金渠道_机构（僵尸户）
        475,//弘达财富_资金渠道_机构
        328,//弘达北方_租上租_资金渠道_机构
        330,//典当联盟_资金渠道_机构
        397,//第一房贷_资金渠道_机构
        269,//第一房贷_上海贷_资金渠道_机构
        306,//产融贷（汇源金控）_资金渠道_机构
        111,//财益通（哈哈农庄）_资金渠道_机构
    );

    //世界杯屏蔽的会员组(不允许获得邀请奖励积分)
    public static $WORLDCUP_FORBIDEN_USERGROUP = array(
        309,//网信白泽_资金渠道_外部渠道（大连）
        312,//网信白泽_资金渠道_外部渠道（沈阳）
        313,//网信白泽_资金渠道_外部渠道（宁波）
        359,//网信白泽_资金渠道_外部渠道（西安）
        360,//网信白泽_资金渠道_外部渠道（济南）
        362,//网信白泽_资金渠道_外部渠道（武汉）
        435,//网信白泽_资金渠道_外部渠道（网推）
        376,//网信白泽_资金渠道_外部渠道（网推-返利平台）
        453,//网信白泽_开放平台_CPA
        462,//网信白泽_开放平台_CPA+CPS
        472,//网信理财_开放平台_卓越恒信
        380,//网信白泽_开放平台_CPA+CPS（二部2）
        379,//网信白泽_开放平台_CPA+CPS（二部）
        270,//网信理财_开放平台_卓越恒信理财师（270）
        477,//网信理财_开放平台_卓越恒信（推荐CPA机构）
        446,//网信理财_开放平台_正堂方略
        445,//网信理财_开放平台_正堂方略（推荐合作机构）
    );

    // 比赛状态
    public static $MATCH_STATUS = array(
        self::MATCH_STATUS_DO => '未开始',
        self::MATCH_STATUS_GUESSING => '竞猜中',
        self::MATCH_STATUS_WAIT => '比赛未开始',
        self::MATCH_STATUS_DOING => '比赛中',
        self::MATCH_STATUS_DONE => '已结束'
    );

    public static $MATCH_STATUS_FLAG = array(
        self::MATCH_STATUS_FLAG_DISABLE => '停用',
        self::MATCH_STATUS_FLAG_ENABLE => '启用'
    );

    // 比赛模式
    public static $MATCH_MODES = array(
        self::MATCH_MODE_MORE_EQ_LESS => '正负平',
        self::MATCH_MODE_MORE_LESS => '正负',
        self::MATCH_MODE_ONE_TWO => '冠亚军'
    );

    // 竞猜结果
    public static $GUESS_STATUS = array(
        self::GUESS_STATUS_NO => '未竞猜',
        self::GUESS_STATUS_INIT => '已竞猜',
        self::GUESS_STATUS_SUCCESS => '竞猜成功',
        self::GUESS_STATUS_FAILED => '竞猜失败'
    );

    // 竞猜选择
    public static $GUESS_CHOICES = array(
        self::GUESS_CHOICE_A => 'A队胜',
        self::GUESS_CHOICE_B => 'B队胜',
        self::GUESS_CHOICE_DRAW => '平',
    );

    // 队伍键值映射
    public static $TEAMS_MAP = array(
        'teamA' => 'A',
        'teamB' => 'B',
        'teamC' => 'C',
        'teamD' => 'D',
        'teamE' => 'E',
        'teamF' => 'F',
        'teamG' => 'G',
        'teamH' => 'H',
    );

    // 活动状态
    public static $EVENT_STATUS = array(
        self::EVENT_STATUS_DO => '未开始',
        self::EVENT_STATUS_DOGING => '进行中',
        self::EVENT_STATUS_DONE => '已结束'
    );

    // 排行榜活动屏蔽会员ID不参加
    public static $RANK_BLACKLIST_USERID = array(
        5676915,//光大云付互联网股份有限公司
        9778175,//深圳市贸通达供应链有限公司
        11169360,//书铭信息科技（上海）有限公司
        11204846,//北京经讯时代科技有限公司
        11287811,//北京铭人伟业体育文化发展有限公司
        11339763,//杭州淘游科技有限公司
        11373735,//致联新能源产业（深圳）有限公司
        11472652,//昌顺达集团有限公司
        11513709,//北京网信白泽投资服务有限公司
        11618726,//广州市元旭汽车租赁有限责任公司
        11673501,//北京网信白泽投资服务有限公司
        11706839,//悠融资产管理（上海）有限公司
        11758994,//广州开拓网络科技有限公司
        11759032,//东莞市旭升广告有限公司
        11769032,//北京昆仑新一房财富投资管理有限公司
        11769035,//北京昆仑新一房财富投资管理有限公司
        4032506,//陕西汇宏商务信息咨询有限公司
        5561172,//刘萍
        9859413,//吉林省祥满商贸有限公司
        10984778,//北京世捷宏业商务服务有限公司
        11201446,//长泰西海建设工程有限公司
        11347848,//北京网信白泽投资服务有限公司
        11719908,//湖南点点通信息科技有限公司
        11725299,//北京德绣科技有限公司
        11758354,//宜昌龙之梦信息科技有限公司
        11758847,//宜昌祥元商贸有限公司
        11769020,//北京昆仑新一房财富投资管理有限公司
        11769034,//北京昆仑新一房财富投资管理有限公司
        170674,//北京东方联合科技有限公司
        6143159,//北京财益通投资有限公司
        6355863,//西安皮个布企业管理咨询有限公司
        6390852,//陕西汇泰企业管理咨询有限公司
        9858341,//大连嘉得商贸有限公司
        10223054,//店商互联（重庆）科技发展有限公司
        10947648,//广州市会荣汽车租赁有限责任公司
        11200234,//鹰潭市茂源财务咨询有限公司
        11200784,//鹰潭市鑫汇企业管理咨询有限公司
        11241864,//成都投利宝信息技术有限公司
        11339739,//杭州淘游科技有限公司
        11392294,//深圳市铛铛出行科技有限公司
        11427352,//北京网信白泽投资服务有限公司
        11673485,//北京网信白泽投资服务有限公司
        11673494,//北京网信白泽投资服务有限公司
        11689764,//山东欧派得采购代理有限公司
        11769039,//北京昆仑新一房财富投资管理有限公司
        4169,//北京汇源先锋资本控股有限公司
        5616,//唐永贵
        14203,//金戴文
        777261,//陕西荣投信息科技有限公司
        1456787,//北京嘉德利雅投资管理有限公司
        5102609,//
        10216718,//武汉盈华商务汽车租赁有限公司
        10222501,//电商互联（苏州）科技发展有限公司
        11287845,//北京铭人伟业体育文化发展有限公司
        11347942,//北京网信白泽投资服务有限公司
        11347953,//北京网信白泽投资服务有限公司
        11618111,//广州市曙帆汽车租赁有限责任公司
        6398506,//益融通商业保理有限公司
        7579576,//郑欢
        11113682,//无效
        11169353,//书铭信息科技（上海）有限公司
        11287858,//北京铭人伟业体育文化发展有限公司
        11319403,//深圳东方网信互联网金融信息服务有限公司
        11504376,//北京轻盈付数据科技有限公司
        11580576,//中宏信融融资租赁（辽宁）有限公司
        11604166,//深圳市皮洛施商贸有限公司
        11607493,//深圳市优万贸易有限公司
        11673477,//北京网信白泽投资服务有限公司
        11758383,//深圳市兆丰泰达贸易有限公司
        11769028,//北京昆仑新一房财富投资管理有限公司
    );
    // 排行榜活动屏蔽会员组不参加
    public static $RANK_BLACKLIST_USERGROUP = array(
        453,//网信白泽_开放平台_CPA
        462,//网信白泽_开放平台_CPA+CPS
        381,//网信白泽_开放平台_CPA+CPS（大连）
        379,//网信白泽_开放平台_CPA+CPS（二部）
        380,//网信白泽_开放平台_CPA+CPS（二部2）
        377,//网信白泽_开放平台_CPA+CPS（济南）
        363,//网信白泽_开放平台_CPA+CPS（宁波）
        434,//网信白泽_开放平台_CPA+CPS（沈阳）
        419,//网信白泽_开放平台_CPA+CPS（武汉）
        311,//网信白泽_开放平台_CPA+CPS（西安）
        446,//网信白泽_开放平台_正堂方略
        445,//网信白泽_开放平台_正堂方略（推荐合作机构）
        472,//网信白泽_开放平台_卓越恒信
        327,//网信白泽_开放平台_卓越恒信（机构）
        477,//网信白泽_开放平台_卓越恒信（推荐CPA机构）
        400,//网信白泽_开放平台_卓越恒信（无返利）
        270,//网信白泽_开放平台_卓越恒信理财师
        309,//网信白泽_资金渠道_外部渠道（大连）
        313,//网信白泽_资金渠道_外部渠道（济南）
        312,//网信白泽_资金渠道_外部渠道（宁波）
        310,//网信白泽_资金渠道_外部渠道（沈阳）
        435,//网信白泽_资金渠道_外部渠道（天津）
        359,//网信白泽_资金渠道_外部渠道（西安）
        362,//网信白泽_资金渠道_外部渠道（武汉）
        376,//网信白泽_资金渠道_外部渠道（网推）
        360,//网信白泽_资金渠道_外部渠道（网推-返利平台）
        481,//联合财富_资金渠道_机构（用户分配）
        480,//盈华财富_资金渠道_机构（用户分配）
        475,//弘达财富_资金渠道_机构 
        438,//网信金服_资金渠道_机构（金服）
        428,//弘达财富_资金渠道_机构（僵尸户）
        427,//盈华财富_资金渠道_机构（僵尸户）
        424,//联合财富_资金渠道_机构（僵尸户）
        392,//主站_平台_企业投资户
        391,//主站_平台_结算户机构
        372,//先锋国盛_资金渠道_机构
        352,//中申保理_资金渠道_机构（用户分配）
        344,//盈华财富_资金渠道_机构（门店）
        334,//深圳华启_资金渠道_机构（用户分配）
        333,//佳禾财富_资金渠道_机构（用户分配）
        324,//中发鼎盛_资金渠道_机构（用户分配）
        322,//佳泓_资金渠道_机构（用户分配）
        318,//杭州名泽_资金渠道_机构（用户分配）
        290,//先锋国盛_资金渠道_机构（用户分配）
        286,//弘达财富_资金渠道_机构（用户分配）
        265,//上海迈麟_资金渠道_机构（用户分配）
        261,//开元信和_资金渠道_机构（用户分配）
        255,//开元_张也团队_资金渠道_机构（用户分配）
        254,//网信金服_资金渠道_机构（二分）
        253,//网信金服_资金渠道_机构（一分）
        252,//至朴财富_资金渠道_机构
        239,//泓实资产_资金渠道_机构（用户分配）
        233,//元亨利至_资金渠道_机构（用户分配）
        220,//开元投资_资金渠道_机构（用户分配）
        31,//主站_平台_交易机构（中新小贷）
        3,//主站_平台_交易机构
    );

    // 游戏模板
    public static $TEMPLATES = array(
        self::TEMPLATE_ROULETTE => '大转盘',
        self::TEMPLATE_SCRATCH_CARD => '刮刮卡',
        self::TEMPLATE_AR_BONUS => 'AR红包',
        self::TEMPLATE_MATCH => '比赛竞猜'
    );

    public static $TEMPLATES_FILES = array(
        self::TEMPLATE_ROULETTE => 'roulette',
        self::TEMPLATE_SCRATCH_CARD => 'scratch',
        self::TEMPLATE_AR_BONUS => 'arbonus',
        self::TEMPLATE_MATCH => 'match'
    );

    // 返利类型
    public static $ALLOWANCE_TYPE = array(
        self::ALLOWANCE_TYPE_COUPON => '礼券',
        self::ALLOWANCE_TYPE_DISCOUNT => '投资券'
    );

    // 活动次数限制类型
    public static $EVENT_LIMIT_TYPE = array(
        self::EVENT_LIMIT_NONE => '无',
        self::EVENT_LIMIT_WITH_ACTIVITY => '次/活动',
        self::EVENT_LIMIT_WITH_DAY => '次/每日'
    );

    // 来源类型
    public static $SOURCE_TYPE = array(
        self::SOURCR_TYPE_INVEST => '投资增加',
        self::SOURCR_TYPE_GAMING => '游戏消耗减少',
        self::SOURCR_TYPE_HONGBAO => '红包增加',
        self::SOURCE_TYPE_AR_BONUS => 'AR扫码红包',
        self::SOURCE_TYPE_MATCH_GUESS => '比赛竞猜',
        self::SOURCE_TYPE_GUESS_SUCCESS => '竞猜成功',
        self::SOURCE_TYPE_MATCH_GIVE => '比赛赠送',
        self::SOURCE_TYPE_INIT_GIVE => '初始赠送',
        self::SOURCE_TYPE_CHECKIN => '签到',
        self::SOURCE_TYPE_INVITE => '邀请用户',
        self::SOURCE_TYPE_PAUL_MEDAL => '保罗勋章',
        self::SOURCE_TYPE_ADMIN_INCR => '系统补偿',
        self::SOURCE_TYPE_ADMIN_DECR => '系统扣减',
        self::SOURCE_TYPE_SCORE_CONVERT => '积分折算'
    );

    // 统计动作类型
    public static $STATISTICS_TYPES = array(
        self::STATISTICS_TYPE_SHARE => '分享',
        self::STATISTICS_TYPE_PV => '浏览'
    );

    // 排行榜类型
    public static $RANK_TYPES = array(
        self::RANK_INVEST_AMOUNT             => '投资金额',
        self::RANK_INVEST_ANNUALIZED_AMOUNT  => '投资年化',
        self::RANK_INVITE_COUNT              => '拉新数目',
        self::RANK_INVITE_AMOUNT             => '拉新金额',
        self::RANK_INVITE_ANNUALIZED_AMOUNT  => '拉新年化',
    );

    // 排行榜统计的业务类型
    public static $DEAL_TYPES = array(
        self::RANK_DEAL_TYPE_P2P            => 'p2p',
        self::RANK_DEAL_TYPE_EXCLUSIVE     => '尊享',
    );
}
