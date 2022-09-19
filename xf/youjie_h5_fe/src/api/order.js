import { fetchEndpoint } from '../server/network'

// 订单详情
export const orderGet = (order, isInstalment, parent_order) => {
  if (isInstalment == 1) {
    return fetchEndpoint('/hh/hh.order.instalment.get', 'POST', {
      order: order, // 小订单ID
      parent_order: parent_order
    })
  } else {
    return fetchEndpoint('/hh/hh.order.get', 'POST', {
      order: order // 订单ID
    })
  }
}

// 代付方 代付订单详情
export const orderFriendPayGet = order =>
  fetchEndpoint('/hh/sharepay/hh.order.get', 'POST', {
    order: order // 订单ID
  })

// 订单列表
export const orderList = (page, per_page, status) =>
  fetchEndpoint('/hh/hh.order.list', 'POST', {
    page: page, // 当前第几页
    per_page: per_page, // 每页多少
    status: status // 按订单状态筛选（可选，不填则全部）
  })

// 好友代付订单列表
export const orderFriendPayList = (page, per_page, status) =>
  fetchEndpoint('/hh/sharepay/hh.order.list', 'POST', {
    page: page, // 当前第几页
    per_page: per_page, // 每页多少
    status: status // 按订单状态筛选（可选，不填则全部）
  })

// 确认收货
export const orderConfirm = order =>
  fetchEndpoint('/hh/hh.order.confirm', 'POST', {
    order: order // 订单ID
  })

// 获取退货原因
export const orderReasonList = () => fetchEndpoint('/hh/hh.order.reason.list', 'POST')

// 取消订单
export const orderCancel = (order, reason) =>
  fetchEndpoint('/hh/hh.order.cancel', 'POST', {
    order: order,
    reason: reason // 取消理由
  })

// 订单评价
export const commentSave = params => fetchEndpoint('/hh/hh.product.comment.save', 'POST', { ...params })

// 订单追评
export const commentAppendSave = params => fetchEndpoint('/hh/hh.product.comment.appendsave', 'POST', { ...params })

// 订单不同状态的数量统计
export const orderSubtotal = () => fetchEndpoint('/hh/hh.order.subtotal', 'POST')

// 订单价格计算
export const orderPrice = (products, consignee, activity, train_sn) =>
  fetchEndpoint('/hh/hh.order.price', 'POST', {
    products: products, // 商品信息数组
    consignee: consignee, // 收货人ID
    activity: activity, // 优惠券
    train_sn: train_sn //直通车商品sn
  })

// 用户当日 未支付订单数（待支付订单和已取消订单）
export const getUnfinishedOrderCount = () => fetchEndpoint('/hh/hh.order.unpaid', 'POST')

// 分期明细
export const getOrderInstalmentList = order =>
  fetchEndpoint('/hh/hh.order.instalment.list', 'POST', {
    order: order
  })

// 每期详细信息
export const getOrderEveryInstalmentInfo = order =>
  fetchEndpoint('/hh/hh.order.instalment.get', 'POST', {
    order: order
  })

// 生成临时订单
export const createTmpOrder = product =>
  fetchEndpoint('/hh/hh.order.createTmpOrder', 'POST', {
    product: product // 商品id
  })
// 取消临时订单（置为失效）
export const cancelTmpOrder = orderId =>
  fetchEndpoint('/hh/hh.order.cancelTmpOrder', 'POST', {
    product: orderId // 商品id
  })
