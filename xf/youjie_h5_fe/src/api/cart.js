import { fetchEndpoint } from '../server/network'

// 获取购物车商品分组列表
export const cartGet = () => fetchEndpoint('/hh/hh.cart.get', 'POST')

// 获取购物车商品数量
export const cartQuantity = () => fetchEndpoint('/hh/hh.cart.quantity', 'POST')

// 添加商品到购物车
export const cartAdd = (product, property, amount) =>
  fetchEndpoint('/hh/hh.cart.add', 'POST', {
    product: product, // 在售商品id
    property: property, // 用户选择的属性ID
    amount: amount // 数量
  })

// 从购物车删除删除
export const cartDelete = goods =>
  fetchEndpoint('/hh/hh.cart.delete', 'POST', {
    good: goods // 购物车商品 id 数组
  })

// 修改购物车商品数量
export const cartUpdate = (good, amount) =>
  fetchEndpoint('/hh/hh.cart.update', 'POST', {
    good: good, // 购物车商品 id
    amount: amount // 数量
  })

// 从购物车结算
export const cartCheckout = params =>
  fetchEndpoint('/hh/hh.cart.checkout', 'POST', {
    consignee: params.consignee, // 收货人ID
    products: params.products,
    coupon_id: params.coupon_id, // 优惠券
    reserved: params.reserved //兑换浣币总额，此参数大于0表示要生成临时订单
  })
