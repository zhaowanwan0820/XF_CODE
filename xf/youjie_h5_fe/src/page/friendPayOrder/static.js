import { ENUM } from '../../const/enum'

export const ORDEREFFRCTTIME = 1800

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
    id: ''
  },
  {
    name: '已完成',
    id: ENUM.ORDER_STATUS.FINISHED
  },
  {
    name: '已取消',
    id: ENUM.ORDER_STATUS.CANCELED
  }
]

export const IMAGE = [
  {
    name: '好评',
    id: 3,
    img: require('../../assets/image/change-icon/g1_icon_comments1_nor@2x.png'),
    activeImg: require('../../assets/image/change-icon/g1_icon_comments1_sel@2x.png'),
    isActive: true
  },
  {
    name: '中评',
    id: 2,
    img: require('../../assets/image/change-icon/g1_icon_comments2_nor@2x.png'),
    activeImg: require('../../assets/image/change-icon/g1_icon_comments2_sel@2x.png'),
    isActive: false
  },
  {
    name: '差评',
    id: 1,
    img: require('../../assets/image/change-icon/g1_icon_comments3_nor@2x.png'),
    activeImg: require('../../assets/image/change-icon/g1_icon_comments3_sel@2x.png'),
    isActive: false
  }
]
