import { ENUM } from '../../../const/enum'
import utils from '../../../util/util'
// HIGH_HB: 1, // 高额积分专区
// SPECIAL_OFFER: 2, // 百元特价专区
// GIFT: 3, // 精选礼品
// SUMMER: 4, // 夏日爆款
// BUY_BACK: 5, // 好物回购
// FOOD_FRESH: 6, // 食品生鲜
// MAKEUP: 7, // 美妆护肤
// COSTUME: 8, // 服饰内衣
// ELECTRIC: 9, // 家用电器
// DAILY_NECESSITIES: 10, // 居家日用
// JEWELRY: 11, // 珠宝配饰
// BAGS_SHOES: 12, // 箱包鞋靴
// PAY_FOR_DEBT: 13, // 以资抵债
// NEWBIE: 14 // 新人

export const BANNER_ICON_CONFIG = [
  {
    name: '家居',
    type: 'clean',
    bg: require('../../../assets/image/hh-icon/new-home/nav-icon-1.png'),
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.DAILY_NECESSITIES,
      category: '810'
    }
  },
  // {
  //   name: '茶艺',
  //   type: 'tea',
  //   url: 'https://m.huanhuanyiwu.com/operation/index.php?url=https%3A%2F%2Fwww.itouzi.com%2Fe%2Fchayi_wap'
  // },
  {
    name: '食品',
    type: 'food-fresh',
    bg: require('../../../assets/image/hh-icon/new-home/nav-icon-2.png'),
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.FOOD_FRESH,
      category: '805'
    }
  },
  {
    name: '美妆',
    type: 'beautyMakeup',
    bg: require('../../../assets/image/hh-icon/new-home/nav-icon-3.png'),
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.MAKEUP,
      category: '808'
    }
  },
  {
    name: '服饰',
    type: 'clothing',
    bg: require('../../../assets/image/hh-icon/new-home/nav-icon-4.png'),
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.COSTUME,
      category: '807'
    }
  },
  {
    name: '家电',
    type: 'appliance',
    bg: require('../../../assets/image/hh-icon/new-home/nav-icon-5.png'),
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.ELECTRIC,
      category: '803'
    }
  }
]

export const RECOMMEND_CONFIG = [
  [
    {
      type: 'huanhuanke',
      layout: 'f-t-two',
      title: `${utils.storeNameForShort}优选`,
      subtitle: '大牌尖货&nbsp;&nbsp;4折起',
      subtitleDesc: '',
      url: 'https://m.huanhuanyiwu.com/operation/index.php?url=https://www.itouzi.com/e/youxuan_wap',
      bg: [
        require('../../../assets/image/hh-icon/b0-home/huanhuanke-bg-1.png'),
        require('../../../assets/image/hh-icon/b0-home/huanhuanke-bg-2.png')
      ],
      router: {
        // name: 'pickGoods'
      }
    },
    {
      type: 'quanyi',
      layout: 'f-t-two',
      title: '汽车生活',
      subtitle: '积分租购&nbsp;&nbsp;买车更轻松',
      subtitleDesc: '',
      url: 'https://m.huanhuanyiwu.com/operation/index.php?url=https://www.itouzi.com/e/chexilie_wap',
      bg: [require('../../../assets/image/hh-icon/b0-home/cars-bg.png')],
      router: {}
    }
  ],
  [
    {
      type: 'high-hb',
      layout: 'f-mdl-two',
      title: '高额积分专区',
      subtitle: '积分占比最高',
      subtitleDesc: '达90%以上',
      url: '',
      bg: [
        require('../../../assets/image/hh-icon/b0-home/high-hb-bg-1.png'),
        require('../../../assets/image/hh-icon/b0-home/high-hb-bg-2.png')
      ],
      router: {
        name: 'products',
        query: { tags_id: ENUM.TAGS_IDS.HIGH_HB }
      }
    },
    {
      type: 'special-offer',
      layout: 'f-mdl-two',
      title: '百元特价专区',
      subtitle: '低价享好物',
      subtitleDesc: '全场9.9元起',
      url: '',
      bg: [
        require('../../../assets/image/hh-icon/b0-home/special-offer-1.png'),
        require('../../../assets/image/hh-icon/b0-home/special-offer-2.png')
      ],
      router: {
        name: 'products',
        query: { tags_id: ENUM.TAGS_IDS.SPECIAL_OFFER }
      }
    }
  ],
  [
    {
      type: 'gift',
      layout: 'f-btm-four',
      title: '品牌嗨购',
      subtitle: '名品推荐',
      subtitleDesc: '',
      url: '',
      bg: [require('../../../assets/image/hh-icon/b0-home/gift-bg.png')],
      router: {
        name: 'products',
        query: { tags_id: ENUM.TAGS_IDS.GIFT }
      }
    },
    {
      type: 'summer',
      layout: 'f-btm-four',
      title: '童乐园',
      subtitle: '母婴一站购物',
      subtitleDesc: '',
      url: 'https://m.huanhuanyiwu.com/operation/index.php?url=https://www.itouzi.com/e/tongle_wap',
      bg: [require('../../../assets/image/hh-icon/b0-home/kidsPark-bg.png')],
      router: {
        // name: 'products',
        // query: { tags_id: ENUM.TAGS_IDS.SUMMER }
      }
    },
    {
      type: 'good-shop',
      layout: 'f-btm-four',
      title: '今日好店',
      subtitle: '好店好品质',
      subtitleDesc: '',
      url: 'https://m.huanhuanyiwu.com/operation/index.php?url=https://www.itouzi.com/e/haodian_wap',
      bg: [require('../../../assets/image/hh-icon/b0-home/good-shop-bg.png')],
      router: {}
    },
    {
      type: 'repo',
      layout: 'f-btm-four',
      title: '好物回购',
      subtitle: '人气口碑产品',
      subtitleDesc: '',
      url: '',
      bg: [require('../../../assets/image/hh-icon/b0-home/repo-bg.png')],
      router: {
        name: 'products',
        query: { tags_id: ENUM.TAGS_IDS.BUY_BACK }
      }
    }
  ]
]

export const HOT_TAGS = [
  require('../../../assets/image/hh-icon/b0-home/tag-pop.png'),
  require('../../../assets/image/hh-icon/b0-home/tag-hot.png'),
  require('../../../assets/image/hh-icon/b0-home/tag-celebrity.png'),
  require('../../../assets/image/hh-icon/b0-home/tag-reputably.png')
]

export const HOME_FLOOR = [
  {
    name: '食品酒水',
    type: 'food-fresh',
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.FOOD_FRESH,
      category: '805'
    },
    bg: require('../../../assets/image/hh-icon/new-home/banner-shipin.png')
  },
  {
    name: '家居日用',
    type: 'clean',
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.DAILY_NECESSITIES,
      category: '810'
    },
    bg: require('../../../assets/image/hh-icon/new-home/banner-jiaju.png')
  },
  {
    name: '美妆护肤',
    type: 'makeup',
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.MAKEUP,
      category: '808'
    },
    bg: require('../../../assets/image/hh-icon/new-home/banner-meizhuang.png')
  },
  {
    name: '服装箱包',
    type: 'costume',
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.COSTUME,
      category: '807'
    },
    bg: require('../../../assets/image/hh-icon/new-home/banner-fushi.png')
  },
  {
    name: '家电数码',
    type: 'electric',
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.ELECTRIC,
      category: '803'
    },
    bg: require('../../../assets/image/hh-icon/new-home/banner-jiadian.png')
  },
  {
    name: '珠宝首饰',
    type: 'jewelry',
    params: {
      admin_order: ENUM.ADMIN_ORDER.ORDERED,
      tags_id: ENUM.TAGS_IDS.JEWELRY,
      category: '804'
    },
    bg: require('../../../assets/image/hh-icon/new-home/banner-zhubao.png')
  }
]

// 首页 运营弹窗
export const HOME_POPUP = {
  meiJingOne2One: {
    src: require('../../../assets/image/home-popup/pop_meijing_p2p.png'),
    url: 'https://m.youjiemall.com/h5/#/products?is_appoint=1&sort_key=1'
  },
  respectToOldMan: {
    src: require('../../../assets/image/home-popup/pop_respect_to_oldMan.png'),
    url: 'https://m.youjiemall.com/h5/#/products?tags_id=22'
  },
  limitedSelection: {
    src: require('../../../assets/image/home-popup/pop_meijing_p2p.png'),
    url: 'https://m.youjiemall.com/h5/#/products?admin_order=1&tags_id=31'
  }
}

// 首页 运营弹窗
export const HOME_POPUP_2020 = {
  switch: false, // 开关
  online: {
    src: require('../../../assets/image/hh-icon/b0-home/pop-online.png'),
    url: 'https://m.huanhuanyiwu.com/h5/#/sharerDetail?id=7875'
  },
  offline: {
    src: require('../../../assets/image/home-popup/nian.png'),
    url: ''
  }
}

// 有解商城的特色推荐专区
export const Youjie_Feature = [
  {
    title: '优质房产',
    describe: '网罗优质房源 尽享都市繁华',
    imgUrl: require('../../../assets/image/hh-icon/new-home/feature-icon-1.png'),
    jumpUrl: {
      name: '',
      url: 'https://m.youjiemall.com/h5/#/products?category=1620'
    }
  },
  {
    title: '汽车生活',
    describe: '车行天下 引领品质生活',
    imgUrl: require('../../../assets/image/hh-icon/new-home/feature-icon-2.png'),
    jumpUrl: {
      name: '',
      url: 'https://m.youjiemall.com/h5/#/products?admin_order=1&tags_id=41'
    }
  }
]
