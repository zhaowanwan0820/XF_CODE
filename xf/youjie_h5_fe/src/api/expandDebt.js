import { fetchEndpoint } from '../server/network'

// hashid登录
export const hashLogin = hashid =>
  fetchEndpoint('/activity/hh.out.login', 'POST', {
    hashid: hashid
  })

// 获取推荐礼包list
export const outGoods = () => fetchEndpoint('/activity/hh.out.outGoods', 'POST')

// 获取选中礼包信息
export const giftDetail = product =>
  fetchEndpoint('/activity/hh.product.outGet', 'POST', {
    product: product
  })

// 获取推荐商品信息
export const recoList = () => fetchEndpoint('/activity/hh.product.endPay', 'POST')

// 确认兑换
export const confirmExchange = (product, consignee, mobile, address, country, province, city, district) =>
  fetchEndpoint('/activity/hh.product.outPurchase', 'POST', {
    product: product, //商品id
    consignee: consignee, //收货人
    mobile: mobile, //手机
    address: address, //详细地址
    country: country, //国家
    province: province, //省份
    city: city, //城市
    district: district //区域
  })
