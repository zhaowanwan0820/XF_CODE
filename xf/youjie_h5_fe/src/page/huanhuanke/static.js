import { ENUM } from '../../const/enum'
import utils from '../../util/util'

// 7天后可提现
export const AFTERSALEDAYS = 7 * 24 * 3600

export const ORDERSTATUS0 = [
  {
    name: '全部',
    id: ENUM.ORDER_STATUS.ALL
  },
  {
    name: '已完成',
    id: ENUM.ORDER_STATUS.CREATED
  },
  {
    name: '已完成',
    id: ENUM.ORDER_STATUS.PAID
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

export const ORDERSTATUS1 = [
  {
    name: '全部',
    id: ENUM.ORDER_STATUS.ALL
  },
  {
    name: '未变现',
    id: ENUM.ORDER_STATUS.CREATED
  },
  {
    name: '已变现',
    id: ENUM.ORDER_STATUS.PAID
  },
  {
    name: '已变现',
    id: ENUM.ORDER_STATUS.FINISHED
  },
  {
    name: '已取消',
    id: ENUM.ORDER_STATUS.CANCELED
  }
]

export const ORDERNAV0 = [
  {
    name: '全部',
    id: ENUM.ORDER_STATUS.ALL
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

export const ORDERNAV1 = [
  {
    name: '全部',
    id: ENUM.ORDER_STATUS.ALL
  },
  {
    name: '未变现',
    id: ENUM.ORDER_STATUS.CREATED
  },
  {
    name: '已变现',
    id: ENUM.ORDER_STATUS.FINISHED
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

export const WITHDRAW_STATUS = {
  SUCCEED: 1,
  FAILED: 2
}

export const CHART_STATUS = [
  {
    id: 1,
    name: '昨日',
    e_name: 'yesterday'
  },
  {
    id: 2,
    name: '今日',
    e_name: 'today'
  },
  {
    id: 3,
    name: '累计',
    e_name: 'all'
  }
]

// 广告位链接
export const BANNERLINK =
  'https://m.huanhuanyiwu.com/operation/index.php?url=https://www.itouzi.com/e/zhuanqiangonglue_wap'

export const PLACEHOLDER = '新品茶叶 好茶好品味'

// 分销商品排序键
export const SORTKEY = [
  {
    key: 1,
    name: '推荐数',
    value: ENUM.SORT_VALUE.DESC,
    id: 0
  },
  {
    key: 2,
    name: '佣金数',
    value: ENUM.SORT_VALUE.DESC,
    id: 1
  },
  {
    key: 3,
    name: '销量',
    value: ENUM.SORT_VALUE.DESC,
    id: 2
  },
  {
    key: 4,
    name: '新品',
    value: ENUM.SORT_VALUE.DESC,
    id: 3
  }
]

// 我的小店 分享内容
export const MYSTORE_SHARE = {
  title: utils.storeName,
  desc: '最好的东西只想与你分享，分享生活的所有美好，动动手指，让我们的关系更亲密一点！',
  share_icon: 'https://static.itzcdn.com/app/hhmall_res/app_icon.png',
  platform: 'WechatSession,WechatTimeline',
  flag: 'hh-mystore-share'
}

export const BILL_TYPE = [
  {
    id: ENUM.BILLTYPES.ALL,
    name: '全部分类'
  },
  {
    id: ENUM.BILLTYPES.MLM,
    name: '分销订单'
  },
  {
    id: ENUM.BILLTYPES.RETURN_CASH,
    name: '订单返现'
  },
  {
    id: ENUM.BILLTYPES.WITHDRAW,
    name: '提现'
  },
  {
    id: ENUM.BILLTYPES.RECALL,
    name: '返现撤回'
  }
]
