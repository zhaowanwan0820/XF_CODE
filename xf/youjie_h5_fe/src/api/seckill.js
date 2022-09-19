import { fetchEndpoint } from '../server/network'

// 首页秒杀商品推荐
export const seckillRecommend = () => fetchEndpoint('/hh/hh.secbuy.recommend', 'POST', {})

// 秒杀专场场次清单
export const seckillTabs = () => fetchEndpoint('/hh/hh.secbuy.tabs', 'POST', {})

// 秒杀专场场次商品列表接口
export const seckillList = (id, page, per_page = 10) =>
  fetchEndpoint('/hh/hh.secbuy.list', 'POST', {
    id: id,
    page: page,
    per_page: per_page
  })

export const secPfoductDetail = id =>
  fetchEndpoint('/hh/hh.secbuy.goods.info', 'POST', {
    id: id
  })
