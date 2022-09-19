const header = [
  {
    name: '商品',
    id: 0,
    isActive: false
  },
  {
    name: '详情',
    id: 1,
    isActive: false
  }
]

const evaluation = [
  {
    name: '全部',
    id: 1,
    isActive: false,
    value: 'total',
    grade: 0
  },
  {
    name: '好评',
    id: 2,
    isActive: false,
    value: 'good',
    grade: 3
  },
  {
    name: '中评',
    id: 3,
    isActive: false,
    value: 'medium',
    grade: 2
  },
  {
    name: '差评',
    id: 4,
    isActive: false,
    value: 'bad',
    grade: 1
  }
]

const COMMENTSTATUS = [
  {
    name: '全部',
    id: 1,
    value: 'count_total'
  },
  {
    name: '有图',
    id: 2,
    value: 'count_img'
  },
  {
    name: '好评',
    id: 3,
    value: 'count_good'
  },
  {
    name: '中评',
    id: 4,
    value: 'count_medium'
  },
  {
    name: '差评',
    id: 5,
    value: 'count_bad'
  },
  {
    name: '追评',
    id: 6,
    value: 'count_append'
  },
  {
    name: '最新',
    id: 7,
    value: 'count_total'
  }
]

const serveTags = [
  /*{
    id: 1,
    type: 1,
    banner: '包邮',
    title: '全场包邮',
    desc: '所有商品均无条件包邮'
  },
  {
    id: 2,
    type: 2,
    banner: '假一罚十',
    title: '假一罚十',
    desc: '若收到商品是假冒品牌，可获十倍赔偿'
  },
  {
    id: 3,
    type: 3,
    banner: '极速退款',
    title: '极速退款',
    desc: '订单处于待发货状态，申请退款立即退款'
  }*/
]

const instalmentIcon = {
  normal: {
    5: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-0.png'),
    1: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-1.png'),
    2: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-2.png'),
    3: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-3.png'),
    4: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-4.png')
  },
  active: {
    5: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-0.png'),
    1: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-1.png'),
    2: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-2.png'),
    3: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-3.png'),
    4: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-4.png')
  },
  checkout: {
    5: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-0.png'),
    1: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-1.png'),
    2: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-2.png'),
    3: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-3.png'),
    4: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-4.png')
  }
}

// 指定商品 分期 展示 首期 else 期
const PRODUCT_SHOW_SHOUQI = [9338, 9431, 9606, 9610, 9626]

export { header, evaluation, serveTags, instalmentIcon, PRODUCT_SHOW_SHOUQI, COMMENTSTATUS }
