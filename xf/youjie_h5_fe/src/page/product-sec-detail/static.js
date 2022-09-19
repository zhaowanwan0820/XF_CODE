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
    0: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-0.png'),
    1: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-1.png'),
    2: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-2.png'),
    3: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-3.png'),
    4: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-normal-4.png')
  },
  active: {
    0: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-0.png'),
    1: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-1.png'),
    2: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-2.png'),
    3: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-3.png'),
    4: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-active-4.png')
  },
  checkout: {
    0: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-0.png'),
    1: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-1.png'),
    2: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-2.png'),
    3: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-3.png'),
    4: require('../../assets/image/hh-icon/c0-instalment/instalment-icon-checkout-4.png')
  }
}

export { header, evaluation, serveTags, instalmentIcon }
