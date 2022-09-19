import { ENUM } from '../../const/enum'

export const ORDEREFFRCTTIME = 1800

export const SECKILLORDEREFFRCTTIME = 300

// 14天后关闭售后订单
export const AFTERSALEDAYS = 14

export const ORDERSTATUS = [
  {
    name: '全部',
    id: ENUM.ORDER_STATUS.ALL
  },
  // {
  //   'name': '配货中',
  //   'id': ENUM.ORDER_STATUS.DISTRIBUTION
  // },
  {
    name: '已取消',
    id: ENUM.ORDER_STATUS.CANCELED
  },
  {
    name: '已完成',
    id: ENUM.ORDER_STATUS.FINISHED
  },
  {
    name: '待评价',
    id: ENUM.ORDER_STATUS.DELIVERIED
  },
  {
    name: '配送中',
    id: ENUM.ORDER_STATUS.DELIVERING
  },
  {
    name: '待发货',
    id: ENUM.ORDER_STATUS.PAID
  },
  {
    name: '待付款',
    id: ENUM.ORDER_STATUS.CREATED
  }
]

export const ORDERNAV = [
  {
    name: '全部',
    id: ENUM.ORDER_STATUS.ALL
  },
  {
    name: '待付款',
    id: ENUM.ORDER_STATUS.CREATED
  },
  {
    name: '待发货',
    id: ENUM.ORDER_STATUS.PAID
  },
  {
    name: '待收货',
    id: ENUM.ORDER_STATUS.DELIVERING
  },
  {
    name: '待评价',
    id: ENUM.ORDER_STATUS.DELIVERIED
  }
]

export const IMAGE = [
  {
    name: '好评',
    id: 5,
    img: require('../../assets/image/hh-icon/comment/icon-comment1-off.png'),
    activeImg: require('../../assets/image/hh-icon/comment/icon-comment1-on.png'),
    isActive: true
  },
  {
    name: '中评',
    id: 3,
    img: require('../../assets/image/hh-icon/comment/icon-comment2-off.png'),
    activeImg: require('../../assets/image/hh-icon/comment/icon-comment2-on.png'),
    isActive: false
  },
  {
    name: '差评',
    id: 1,
    img: require('../../assets/image/hh-icon/comment/icon-comment3-off.png'),
    activeImg: require('../../assets/image/hh-icon/comment/icon-comment3-on.png'),
    isActive: false
  }
]

export const INSTALMENTSTATUS = [
  { id: ENUM.INSTALMENT_STATUS.WAITING, name: '待支付' },
  { id: ENUM.INSTALMENT_STATUS.PAID, name: '已支付' },
  { id: ENUM.INSTALMENT_STATUS.CANCEL, name: '已取消' },
  { id: ENUM.INSTALMENT_STATUS.REFUND, name: '已退款' }
]

// 评价评分
export const SCORE = ['非常差', '差', '一般', '好', '非常好']
