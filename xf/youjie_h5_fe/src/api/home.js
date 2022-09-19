import { fetchEndpoint } from '../server/network'

// 首页推荐商品列表
export const homeProductList = params =>
  fetchEndpoint('/hh/hh.home.product.list', 'POST', {
    show: params.show // 请求数据的数组 1-hotProducts ; 2-recentlyProducts ; 3-goodProducts
  })
