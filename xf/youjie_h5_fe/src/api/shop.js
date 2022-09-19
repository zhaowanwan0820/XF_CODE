import { fetchEndpoint } from '../server/network'

// 店铺详情
export const shopGet = shop =>
  fetchEndpoint('/hh/hh.shop.get', 'POST', {
    shop: shop // 店铺id
  })
