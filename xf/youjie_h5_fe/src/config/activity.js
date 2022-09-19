// 节日主题
const activity_tabar = {
  switch: false, // 是否启用活动主题底部导航（底部图片尺寸变大）
  startTime: 1569427200000, // 主题开始时间
  endTime: 1570464000000, // 主题结束时间
  bannerBg: require('../assets/image/hh-icon/activity/national_day/banner-bg.png'), // 活动背景图
  tabColor: 'rgba(119, 37, 8, 1)', // 底部文字颜色 - 未激活
  tabActiveColor: 'rgba(196, 125, 29, 1)', // 底部文字颜色 - 激活
  data: [
    {
      name: '首页',
      link: 'home',
      key: 0,
      bgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-home.png'),
      activeBgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-home-active.png'),
      isActive: true
    },
    {
      name: '分类',
      link: 'category',
      key: 1,
      bgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-category.png'),
      activeBgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-category-active.png'),
      isActive: false
    },
    {
      name: '我的小店',
      link: 'myStore',
      key: 2,
      bgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-my-store.png'),
      activeBgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-my-store-active.png'),
      isActive: false
    },
    {
      name: '购物车',
      link: 'cart',
      params: { type: 1 },
      key: 3,
      bgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-cart.png'),
      activeBgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-cart-active.png'),
      isActive: false
    },
    {
      name: '我的',
      link: 'profile',
      key: 4,
      bgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-mine.png'),
      activeBgurl: require('../assets/image/hh-icon/activity/mid_autumn_festival/icon-mine-active.png'),
      isActive: false
    }
  ],
  barBg: require('../assets/image/hh-icon/activity/mid_autumn_festival/tab-bg.png'),
  BANNER_ICON_CONFIG: [
    {
      name: '腕表',
      type: 'watch',
      bg: require('../assets/image/hh-icon/activity/national_day/subbanner-watch.png'),
      url: 'https://m.huanhuanyiwu.com/operation/index.php?url=https%3A%2F%2Fwww.itouzi.com%2Fe%2Fbiaozhuanqu_wap'
    },
    {
      name: '美妆',
      type: 'beautyMakeup',
      bg: require('../assets/image/hh-icon/activity/national_day/subbanner-beautyMakeup.png'),
      url: 'https://m.huanhuanyiwu.com/operation/index.php?url=https%3A%2F%2Fwww.itouzi.com%2Fe%2Fmeizhuang_wap'
      // icon: 'car'
    },
    {
      name: '美食',
      type: 'food',
      bg: require('../assets/image/hh-icon/activity/national_day/subbanner-food.png'),
      url: 'https://m.huanhuanyiwu.com/operation/index.php?url=https%3A%2F%2Fwww.itouzi.com%2Fe%2Fshiping_wap'
    },
    {
      name: '唐卡',
      type: 'tangka',
      bg: require('../assets/image/hh-icon/activity/national_day/subbanner-tangka.png'),
      url: 'https://m.huanhuanyiwu.com/operation/index.php?url=https%3A%2F%2Fwww.itouzi.com%2Fe%2Ftangka_wap'
    }
  ]
}

export { activity_tabar }
