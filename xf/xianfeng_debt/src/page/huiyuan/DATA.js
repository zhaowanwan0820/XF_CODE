// 近30笔债转折扣
// export const DATA_DISCOUNT_LAST_30 = [
//   {
//     xdata: '1',
//     ydata: 0.1 // 债转平均折扣
//   },
//   {
//     xdata: '2',
//     ydata: 0.13
//   },
//   {
//     xdata: '3',
//     ydata: 0.2
//   },
//   {
//     xdata: '4',
//     ydata: 0.5
//   },
//   {
//     xdata: '5',
//     ydata: 0.32
//   },
//   {
//     xdata: '6',
//     ydata: 0.55
//   },
//   {
//     xdata: '7',
//     ydata: 0.37
//   },
//   {
//     xdata: '8',
//     ydata: 0.1 // 债转平均折扣
//   },
//   {
//     xdata: '9',
//     ydata: 0.13
//   },
//   {
//     xdata: '10',
//     ydata: 0.2
//   },
//   {
//     xdata: '11',
//     ydata: 0.5
//   },
//   {
//     xdata: '12',
//     ydata: 0.32
//   },
//   {
//     xdata: '13',
//     ydata: 0.55
//   },
//   {
//     xdata: '14',
//     ydata: 0.37
//   },
//   {
//     xdata: '15',
//     ydata: 0.77
//   }
// ]

// export const DATA_DISCOUNT_BY_DAY = [
//   {
//     xdata: '2019-10-01', // 日期
//     ydata: 0.1 // 债转平均折扣
//   },
//   {
//     xdata: '2019-10-02',
//     ydata: 0.13
//   },
//   {
//     xdata: '2019-10-03',
//     ydata: 1.2
//   },
//   {
//     xdata: '2019-10-04',
//     ydata: 2.5
//   },
//   {
//     xdata: '2019-10-05',
//     ydata: 5.32
//   },
//   {
//     xdata: '2019-10-06',
//     ydata: 1.55
//   },
//   {
//     xdata: '2019-10-07',
//     ydata: 0.37
//   },
//   {
//     xdata: '2019-10-08',
//     ydata: 0.6
//   },
//   {
//     xdata: '2019-10-09',
//     ydata: 0.13
//   },
//   {
//     xdata: '2019-10-10',
//     ydata: 0.9
//   },
//   {
//     xdata: '2019-10-11',
//     ydata: 3.5
//   },
//   {
//     xdata: '2019-10-12',
//     ydata: 5.32
//   },
//   {
//     xdata: '2019-10-13',
//     ydata: 2.55
//   },
//   {
//     xdata: '2019-10-14',
//     ydata: 0.37
//   }
// ]

// banner
export const HUIYUAN_BANNERS = [
  {
    thumb: require('../../assets/image/huiyuan/banner-1.png'),
    // link: 'https://service.huanhuanyiwu.com/h5'
    routeName: 'huiYuanNoviceRaiders'
  }
]

export const DATA_PLAN_LIST = [
  {
    pur_id: '2', //求购计划id
    discount: '5', //求购折扣（折扣：0.01~10）
    expiry_time: '1572584706', // 返回的是时间戳
    acquired_money: '10000.00', // 待收金额
    money: '21000.00' // 求购金额
  },
  {
    pur_id: '3', //求购计划id
    discount: '0.35', //求购折扣（折扣：0.01~10）
    expiry_time: '1572509106', // 返回的是时间戳
    acquired_money: '4000.00', // 待收金额
    money: '10100.00' // 求购金额
  }
]

// export const DATA_DEBT_LIST = [
//   {
//     debt_id: '32', // 债转记录ID
//     amount: '150.00', // 转让金额
//     discount: '0.00折', // 折扣
//     serial_number: 'DEBT201910295DB7C6188C6D024260', // 债转编号
//     addtime: '2019-10-29 12:54:48', // 发布时间
//     end_time: '2019-11-07 23:59:59', // 结束时间
//     status: '1', // 债权转让状态 1-发布中，2-已成交，3-已取消，4-已过期，5-待确认
//     platform_name: '爱投资', // 平台名称
//     type: '2', // 项目类型ID（二期新增）
//     type_name: 'B项目', // 项目类型名称
//     name: '网贷项目01', // 项目名称
//     apr: '12.00%', // 年利率
//     bond_no: '2018DHEN01910', // 合同编号
//     real_name: '赵婉婉2', // 真实姓名
//     money: 158, // 需要支付的转让价格
//     success_time: '', // 成交时间
//     remaining_time: '9日8小时35分钟' // 剩余有效期
//   },
//   {
//     debt_id: '33', // 债转记录ID
//     amount: '61350.00', // 转让金额
//     discount: '6折', // 折扣
//     serial_number: 'DEBT201910295DB7C6188C6D024260', // 债转编号
//     addtime: '2019-10-29 12:54:48', // 发布时间
//     end_time: '2019-11-30 23:59:59', // 结束时间
//     status: '1', // 债权转让状态 1-发布中，2-已成交，3-已取消，4-已过期，5-待确认
//     platform_name: '爱投资', // 平台名称
//     type: '2', // 项目类型ID（二期新增）
//     type_name: 'B项目', // 项目类型名称
//     name: '网贷项目02', // 项目名称
//     apr: '11.00%', // 年利率
//     bond_no: '2018DHEN01910', // 合同编号
//     real_name: '赵婉婉1', // 真实姓名
//     money: 1800, // 需要支付的转让价格
//     success_time: '', // 成交时间
//     remaining_time: '1日8小时35分钟' // 剩余有效期
//   }
// ]

// 首页banner下方入口配置
export const ENTRANCE = [
  {
    text: '我要转让',
    image: require('../../assets/image/home/entrance/entrance-publish-debt@3x.png'),
    route: { name: 'projectList' }
  },
  {
    text: '我要认购',
    image: require('../../assets/image/home/entrance/entrance-all-debt@3x.png'),
    route: { name: 'targetList' }
  }
  // {
  //   text: '债权求购',
  //   image: require('../../assets/image/home/entrance/entrance-debt-plans@3x.png'),
  //   route: { name: 'purchaseList' }
  // },
  // {
  //   text: '我的债转',
  //   image: require('../../assets/image/home/entrance/entrance-my-debt@3x.png'),
  //   route: {},
  //   needLogin: true
  // },
  // {
  //   text: '我的认购',
  //   image: require('../../assets/image/home/entrance/entrance-debt-my-buy@3x.png'),
  //   route: {},
  //   needLogin: true
  // }
]
