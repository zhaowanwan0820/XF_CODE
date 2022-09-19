import { fetchEndpoint } from '../server/network'

// 个人中心优惠券数目
export const couponNumber = () => fetchEndpoint('/hh/hh.coupon.number', 'POST', {})

// 优惠券列表
export const couponList = status =>
  fetchEndpoint('/hh/hh.user.coupon.list', 'POST', {
    status: status
  })

/**
 * Gets the goods coupon list.
 *
 * @param      {Number}  id      goods id
 * @return     {Array}  The goods coupon list.
 */
export const getGoodsCouponList = id =>
  fetchEndpoint('/hh/hh.goods.coupon.list', 'POST', {
    goods_id: id
  })

// 获取单张优惠券info
export const couponInfo = coupon_id => fetchEndpoint('/hh/hh.coupon.single.get', 'POST', { coupon_id })

// 优惠券可用商品列表
export const couponGoodsList = params => fetchEndpoint('/hh/hh.coupon.goods.list', 'POST', { ...params })

/**
 * Gets a coupon.
 *
 * @param      {Number}  coupon_id  The coupon identifier
 * @return     {String}  A message.
 */
export const getACoupon = coupon_id => fetchEndpoint('/hh/hh.coupon.receive', 'POST', { coupon_id })
