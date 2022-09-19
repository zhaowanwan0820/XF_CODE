import { fetchEndpoint } from '../server/network'

// 【好友代付】获取代付订单详情
export const friendPayOrderGet = order =>
  fetchEndpoint('/hh/sharepay/hh.order.get/guest', 'POST', {
    sn: order // 代付订单ID
  })

// 【好友代付】确认页 获取页面信息
export const friendPayConfirmGet = order =>
  fetchEndpoint('/hh/sharepay/hh.order.get', 'POST', {
    sn: order // 代付订单ID
  })

// 【好友代付】确认页 确认支付接口
export const friendPayConfirmPay = order =>
  fetchEndpoint('/hh/sharepay/hh.payment.pay', 'POST', {
    sn: order, // 代付订单ID
    code: 'balance'
  })

// 【好友代付】结果页 获取代付结果
// export const friendPayResultGet = order =>
//   fetchEndpoint('/hh/hh.friendPay.resultGet', 'POST', {
//     order: order // 代付订单ID
//   })
