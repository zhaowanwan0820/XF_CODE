export const ENUM = {
  SERVICE: {
    ITOUZI_H5: 'https://www.sobot.com/chat/pc_new/index.html?sysNum=382c37caa562417090a9b8ff5604167f',
    MASTER_H5: 'https://chat.icsoc.net/user-iframe.html?channel_key=c1b018edd2b33c909b6ed90999591101&init=1',
    MASTER_TEL: '4006960099',
    MASTER_TEL_ARR: ['4006960099', '010-89929967', '010-89929968']
  },

  // 错误代码
  ERROR_CODE: {
    OK: 0, //  正常
    UNKNOWN_ERROR: 10000, //  内部错误
    TOKEN_INVALID: 10001, //  Token 无效
    TOKEN_EXPIRED: 10002, //  Token 过期
    SIGN_INVALID: 10003, //  Sign 无效
    SIGN_EXPIRED: 10004, //  Sign 过期
    AUTO_INVALID: 10005, //  尚未完成ITZ授权
    NETWORK_ERROR: 10006 //  网络异常
  },

  // 主机类型
  HOST_TYPE: {
    COMMON: 0, //  通用
    ONLY_WLAN: 1, //  只允许WIFI网络访问
    ONLY_MOBILE: 2 //  只允许移动网络访问
  },

  // 帐号类型
  AUTH_VENDOR: {
    UNKNOWN: 0, //  未知
    WEIXIN: 1, //  微信
    WEIBO: 2, //  微博
    QQ: 3, //  QQ
    TAOBAO: 4 //  淘宝
  },

  // 帐号类型
  SOCIAL_VENDOR: {
    UNKNOWN: 0, //  未知
    WEIXIN: 1, //  微信
    WEIBO: 2, //  微博
    QQ: 3, //  QQ
    TAOBAO: 4 //  淘宝
  },

  // 余额状态
  BALANCE_STATUS: {
    ALL: 0, //全部
    INCOME: 1, //  收入
    EXPENDITURE: 2 //  支出
  },

  // 排序键
  SORT_KEY: {
    DEFAULT: 0, //  默认
    PRICE: 1, //  价格从低到高
    // POPULAR: 2, //  人气
    // CREDIT: 3, //  好评
    // SALE: 4, //  销量
    DATE: 5, //  上架时间
    SUPPLIERS_IN_DEBT: 6, //  专区商品
    SUPPLIERS: 8, // 总销量
    SUPPLIERS_MONTH: 81, // 第三方 (月销量)
    SUPPLIERS_DAY: 82 // 第三方 (日销量)
    // ACT_61601: 102, // 616 年中新人专享
    // ACT_61602: 103 // 616 年中精品特价
  },

  // 排序值
  SORT_VALUE: {
    DEFAULT: 0, //  默认排序
    ASC: 1, //  升序
    DESC: 2 //  降序
  },

  ADMIN_ORDER: {
    ORDERED: 1,
    NOT_ORDER: 0
  },

  // 卡片组类型
  CARDGROUP_LAYOUT: {
    A1H: 'A1H', //  通栏（高）
    A1S: 'A1S', //  通栏（矮）
    A2H: 'A2H', //  垂直二等分（高）
    A2S: 'A2S', //  垂直二等分（矮）
    A2XS: 'A2XS', //  垂直二等分（更矮）
    A2XH: 'A2XH', //  垂直二等分（更高）
    A2XXH: 'A2XXH', //  垂直二等分（更更高）
    A3XH: 'A3XH', //  垂直三等分（更高）
    A3XXH: 'A3XXH', //  垂直三等分（更更高）
    A3H: 'A3H', //  垂直三等分（高）
    A3S: 'A3S', //  垂直三等分（矮）
    A4H: 'A4H', //  垂直四等分（高）
    A4S: 'A4S', //  垂直四等分（矮）
    A5H: 'A5H', //  垂直五等分（高）
    A5S: 'A5S', //  垂直五等分（矮）
    B1L: 'B1L', //  左一右二
    B1R: 'B1R', //  左二右一（镜像）
    B2L: 'B2L', //  左一右三
    B2R: 'B2R', //  左三右一（镜像）
    B3L: 'B3L', //  左一右四
    B3R: 'B3R', //  左四右一（镜像）
    B4L: 'B4L', //  左一右一
    B4R: 'B4R', //  左一右一（镜像）
    B5L: 'B5L', //  左一右二
    B5R: 'B5R', //  左二右一（镜像）
    C1H: 'C1H', //  滚动横幅（高）
    C1S: 'C1S', //  滚动横幅（矮）
    C2: 'C2', //  宫格横幅
    C3S: 'C3S', //  公告横幅
    C4: 'C4', //  标题卡片组 (新增)
    C5: 'C5', //  产品卡片组 (新增)
    N1: 'N1', //  横幅（6:5） (新增)
    N2: 'N2', //  横幅2张（1:1） (新增)
    N3: 'N3', //  三格横幅（1:1） (新增)
    N4: 'N4' //  八宫格横幅（1:1) (新增)
  },

  // 卡片类型
  CARD_STYLE: {
    V1T: 'V1T', //  垂直样式1（从上到下）
    V1B: 'V1B', //  垂直样式1（从下到上）
    V2T: 'V2T', //  垂直样式2（从上到下）
    V2B: 'V2B', //  垂直样式2（从下到上）
    H1L: 'H1L', //  水平样式1（从左到右）
    H1R: 'H1R', //  水平样式1（从右到左）
    H2L: 'H2L', //  水平样式1（从左到右）
    H2R: 'H2R', //  水平样式1（从右到左）
    Z1: 'Z1', //  其他样式1
    Z2: 'Z2', //  其他样式2（大图）
    Z3: 'Z3', //  其他样式3
    Z3S: 'Z3S', //  文字展示
    V3T: 'V3T', //  垂直样式3（从上到下）
    V3B: 'V3B', //  垂直样式3（从下到上）
    V4T: 'V4T', //  垂直样式4（从上到下）
    V4B: 'V4B', //  垂直样式4（从下到上）
    V5T: 'V5T', //  垂直样式5（从上到下）有售罄图片 有购物车按钮
    V5B: 'V5B', //  垂直样式5（从下到上）有售罄图片 有购物车按钮
    V6T: 'V6T', //  垂直样式6（从上到下）有售罄图片
    V6B: 'V6B', //  垂直样式6（从下到上）有售罄图片
    Z2L: 'Z2L', //  其他样式2L（大图）左下有价格
    Z2P: 'Z2P', //  其他样式2P（大图）图片上有文字遮罩
    Z4: 'Z4' //  其他样式1 （倒计时卡片）   温超二期   2016.8.22
  },

  // 红包状态
  CASHGIFT_STATUS: {
    AVAILABLE: 0, //  未过期
    EXPIRED: 1, //  过期
    USED: 2 //  已使用
  },

  // 平台类型，ECN 1.2.3
  PLATFORM_TYPE: {
    B2C: 0, //  单店
    B2B2C: 1 //  多店
  },

  // 平台厂商，ECN 1.2.3
  PLATFORM_VENDOR: {
    UNKNOWN: 0, //  未知
    ECSHOP: 1, //
    ECSTORE: 2, //
    ECMALL: 3, //
    MAGENTO: 4 //
  },

  // 优惠券状态
  COUPON_STATUS: {
    AVAILABLE: 0, //  未过期
    EXPIRED: 1, //  过期
    USED: 2 //  已使用
  },

  // 积分类型
  MESSAGE_TYPE: {
    SYSTEM: 1, //  系统
    ORDER: 2 //  订单
  },

  // 订单状态
  ORDER_STATUS: {
    CREATED: 0, //  待付款
    PAID: 1, //  待发货
    DELIVERING: 2, //  发货中
    DELIVERIED: 3, //  已收货，待评价
    FINISHED: 4, //  已完成
    CANCELED: 5, //  已取消
    // DISTRIBUTION: 6,  //  配货中
    ALL: 10 //  全部 // TODO: 去除状态ALL
  },

  SUPPLIERS_TYPE: {
    DEFAUTL: 1, // 积分商品：商品归属欠款企业 ---- 专区商品
    COLLABORATOR: 2, // 三方商家：商品归属非欠款企业 ---- 三方商家
    AGENT: 3 // 代销商品：商品归属保理公司 ---- 积分购物
  },

  // 订单评价
  ORDER_GRADE: {
    BAD: 1, //  差评
    MEDIUM: 2, //  中评
    GOOD: 3 //  好评
  },

  // 促销状态
  PRODUCT_ACTIVITY_STATUS: {
    PREPARING: 0, //  未开始
    ONGOING: 1, //  已开始
    FINISHED: 2 //  已结束
  },

  // 促销活动类型
  PROMOTION_TYPE: {
    GROUPBUY: 0, //  团购
    PACKAGE: 1 //  礼包
  },

  // ECShop 二期 客户端不改
  AFFILIATE_STSTUA: {
    SIGNUP: 0, //   注册分成
    ORDER: 1 //   订单分成
  },

  // ECShop 二期 客户端不改
  AFFILIATE_TYPE: {
    WAIT: 0, //   等待
    CANCEL: 1, //   取消分成
    FINISH: 2 //   已分成
  },

  // 评价类型
  REVIEW_GRADE: {
    ALL: 0, //  全部
    BAD: 1, //  差评
    MEDIUM: 2, //  中评
    GOOD: 3 //  好评
  },

  // 积分状态
  SCORE_STATUS: {
    OPTAIN: 1, //  收入
    DEDUCT: 2 //  支出
  },

  // 关键词
  KEYWORD_TYPE: {
    product: 1, //  商品
    shop: 2 //  店铺
  },

  // 用户性别
  // PROFILE_GENDER: {
  //   UNKNOWN: 0, //  保密
  //   MALE: 1, //  男
  //   FEMALE: 2 //  女
  // },

  BONUS_STATUS: {
    WAIT: 0, // 等待处理
    FINISH: 1, // 已分成
    CANCEL: 2, // 已取消
    REVOKE: 3 // 已撤销
  },

  BONUS_TYPE: {
    SIGNUP: 0, //  注册分成
    ORDER: 1 //  订单分成
  },

  // 提现
  WITHDRAW_STATUS: {
    WAIT: 0, //  待处理     待处理的时候  可以做取消操作
    FINISH: 1, //  已完成
    CANCEL: 2, //  已取消    (目前ecshop后台没有已取消的状态)
    FAILED: 3 //  已失败
  },

  // 拓展类型代码
  ORDER_EXTENSION_CODE: {
    EXCHANGE_GOODS: 'exchange_goods', //  积分商城兑换
    GROUP_BUY: 'group_buy', //  团购
    AUCTION: 'auction', //  拍卖
    SNATCH: 'snatch' //  夺宝奇兵
  },

  // 现金支付方式
  CASH_PAY_TYPE: {
    ALIPAY_H5: 'alipay.wap', // 支付宝H5
    ALIPAY_APP: 'alipay.app', // 支付宝App
    WXPAY_H5: 'wxpay.h5', // 微信H5
    WXPAY_APP: 'wxpay.app', // 微信App
    WXPAY_JS: 'wxpay.jsapi', // 微信jsapi支付
    RONG_BAO_H5: 'rong.h5' // 融宝支付
  },

  // 好友代付的方式 （4.19 目前仅支持纯积分代付）
  SHARE_TYPE: {
    ONLY_HB: 1, // 纯积分
    ONLY_MONEY: 2, // 纯现金
    HB_AND_MONEY: 3 // 混合
  },

  // 订单支付结果
  ORDER_PAY_STATUS: {
    WAITTING: -1, // 等待结果
    SUCCEED: 1, // 支付成功（无代付时）
    FAILED: 2, // 支付失败
    SUCCEED_FRIEND: 3, // 支付成功（需要好友代付）
    SUCCEED_MLM: 4 // 支付成功（分销订单购买成功）
  },

  // 查看分销客个人信息
  HUANKE_STATUS: {
    UNPAY: 0, //提示供个人中心调用未代付的数值
    ALL: 9 //全部
  },

  // 登录验证码种类
  LOGON_CODE: {
    SMS: 0, //短信验证码
    VOICE: 1 //语音验证码
  },

  // 商品tags, 用于查询同类标签的商品
  TAGS_IDS: {
    HIGH_HB: 1, // 高额积分专区
    SPECIAL_OFFER: 2, // 百元特价专区
    GIFT: 3, // 精选礼品
    SUMMER: 4, // 夏日爆款
    BUY_BACK: 5, // 好物回购
    FOOD_FRESH: 6, // 食品生鲜
    MAKEUP: 7, // 美妆护肤
    COSTUME: 8, // 服饰内衣
    ELECTRIC: 9, // 家用电器
    DAILY_NECESSITIES: 10, // 居家日用
    JEWELRY: 11, // 珠宝配饰
    BAGS_SHOES: 12, // 箱包鞋靴
    PAY_FOR_DEBT: 13, // 以资抵债
    NEWBIE: 14 // 新人
  },

  // 积分支付方式
  HUANTYPE: {
    COIN: 1,
    BEAN: 2
  },

  // 积分种类
  BEANTYPE: {
    MY: 0,
    COM: 1
  },

  // 针对一些功能的阅读须知
  HAS_READ_CONFIG: {
    MLM_RULE: 5 // 分销返佣规则
  },

  // 2019-08-07 12:00 时间戳 ms
  TIMESTAMP2019090712: 1565150400000,

  // 账单类型
  BILLTYPES: {
    ALL: 0, // 全部
    MLM: 1, // 分销
    RETURN_CASH: 2, // 订单返现
    WITHDRAW: 3, // 提现
    RECALL: 4 // 返现撤回
  },

  // 订单类型 0:普通,1:分期订单,2:好友代付订单,3:分销订单,4:秒杀订单,5:直通车订单
  ORDERTYPE: {
    DEFAULT: 0,
    INSTAL: 1,
    FRIEND: 2,
    MLM: 3,
    SECKILL: 4,
    TRAIN: 5
  },

  // 分期订单状态： 0：待支付，1：已支付， 2：已取消，3：已退款
  INSTALMENT_STATUS: {
    WAITING: 0,
    PAID: 1,
    CANCEL: 2,
    REFUND: 3
  }
}
