import { fetchEndpoint } from '../server/network'

// 分销客个人信息
export const huanAccount = detail =>
  fetchEndpoint('/hh/mlm/hh.dashboard.info', 'POST', {
    detail: detail // 0个人中心 9全部
  })

// 佣金收支明细
export const huanMoneyHistory = (status, page, per_page) =>
  fetchEndpoint('/hh/mlm/hh.money.history', 'POST', {
    status: status,
    page: page,
    per_page: per_page
  })

// 分销订单列表
export const huanOrderList = (status, page, per_page) =>
  fetchEndpoint('/hh/mlm/hh.order.list', 'POST', {
    status: status,
    page: page,
    per_page: per_page
  })

// 分销订单详情
export const huanOrderGet = order =>
  fetchEndpoint('/hh/mlm/hh.order.get', 'POST', {
    order: order //订单id
  })

// 分销订单代付信息
export const huanPayInfo = order =>
  fetchEndpoint('/hh/mlm/hh.payment.info', 'POST', {
    order: order //订单id
  })

// 分销订单确认代付
export const huanPay = (order, code) =>
  fetchEndpoint('/hh/mlm/hh.payment.pay', 'POST', {
    order: order, //订单id 数组
    code: code //支付方式
  })

// 获取店铺基本信息 - 买家侧视角的分销客店铺
export const getShopInfo = ({ sn = '' }) => fetchEndpoint('/hh/mlm/hh.shop.info.get', 'POST', { shop_sn: sn })

// 获取店铺信息 - 卖家侧视角(需要验证身份)
export const getMyShopInfo = () => fetchEndpoint('/hh/mlm/hh.shop.myinfo.get', 'POST')

/**
 * 分销 & 自营分类不同，所以请求不同的分类接口, 两个分类接口，不能因为参数的不同而写成一个
 */
// 分销返佣分类
export const getHhkRetailCategory = () => fetchEndpoint('/hh/mlm/hh.myshare.category.list', 'POST')

// 获取商品列表 - 卖家侧，需要type区分分销 || 自营
export const getStoreProductList = params =>
  fetchEndpoint('/hh/mlm/hh.share.hhk.list', 'POST', {
    cat_id: params.cat_id,
    type: params.type,
    page: params.page,
    per_page: params.per_page
  })

// 移除小店
export const removeFromShop = goods_id =>
  fetchEndpoint('/hh/mlm/hh.shop.shiftout', 'POST', {
    goods_id: goods_id
  })

// 获取商品分类 - 买家侧视角的分销客店铺
// export const getHhkShopCategory = sn => fetchEndpoint('/hh/mlm/hh.share.category.list', 'POST', { shop_sn: sn })

// 商品列表 - 买家侧视角的分销客店铺
export const getHhkProductList = ({ sn = '' }) =>
  fetchEndpoint('/hh/mlm/hh.share.list', 'POST', {
    shop_sn: sn
  })

// 热销商品列表 - 卖家小店推荐热销商品
export const lotsOnShelf = goods_arr => fetchEndpoint('/hh/mlm/hh.product.lotonshelf', 'POST', { goods_arr: goods_arr })

// 推荐商品列表
export const recommendProduct = params =>
  fetchEndpoint('/hh/hh.product.foryou', 'POST', {
    mlm: 1,
    page: 1,
    per_page: 10
  })

// 获取池子商品分类
export const getCategory = () => fetchEndpoint('/hh/mlm/hh.platform.category.list', 'POST')

/**
 * @params:{
 *   cat_id: 商品分类
 *   sort_key: 1推荐数 2佣金数 3销量 4新品
 *   sort_value: 升、降序
 *   keyword: 搜索关键词
 *   page
 *   per_page
 * }
 */
// 分销商品列表
export const getPickProductList = params =>
  fetchEndpoint('/hh/mlm/hh.pick.product.list ', 'POST', {
    ...params
  })

// 设置小店信息
export const setShopBaseInfo = ({ shop_icon = '', shop_name = '', shop_desc = '', shop_banner = '' }) =>
  fetchEndpoint('/hh/mlm/hh.shop.info.set', 'POST', {
    shop_icon: shop_icon,
    shop_name: shop_name,
    shop_desc: shop_desc,
    shop_banner: shop_banner
  })

// 商品置顶 && 取消置顶
export const setFirstInList = goods_id =>
  fetchEndpoint('/hh/mlm/hh.mlmgoodscategory.hot', 'POST', {
    goods_id: goods_id
  })

// 分销客小店商品取消置顶
export const cancelFirstList = goods_id =>
  fetchEndpoint('/hh/mlm/hh.mlmgoodscategory.cancelhot', 'POST', {
    goods_id: goods_id
  })

// 分销客小店分类修改
export const manageCategoryList = params =>
  fetchEndpoint('/hh/mlm/hh.category.change', 'POST', {
    shop_sn: params.shop_sn,
    category: params.category
  })

// 分销客个人分销商品列表
export const refreshProduct = params =>
  fetchEndpoint('/hh/mlm/hh.share.hhk.list', 'POST', {
    product: params.productID,
    cat_id: params.cat_id,
    type: params.type,
    page: 1,
    per_page: params.per_page
  })

// 获取小店海报
export const getStoreSharePhoto = params =>
  fetchEndpoint('/hh/mlm/hh.share.shop.poster.get', 'POST', {
    shop_sn: params.shop_sn
  })

// 获取分销商品海报
export const getProdSharePhoto = params =>
  fetchEndpoint('/hh/mlm/hh.share.goods.poster.get', 'POST', {
    mlm_id: params.mlm_id
  })
