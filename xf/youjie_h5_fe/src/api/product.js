import { fetchEndpoint } from '../server/network'
import utils from '../util/util'

// 商品评价列表
export const getReviewList = (product, grade, show_reply, page, per_page) =>
  fetchEndpoint('/hh/hh.review.product.list', 'post', {
    product: product, // 商品ID
    grade: grade, // 返回类型 0:全 1:差评 2:中评 3:好评
    show_reply: show_reply, // 是否显示回复
    page: page,
    per_page: per_page
  })

export const getCommentList = params => fetchEndpoint('/hh/hh.product.comment.list', 'post', { ...params })

// 获取单个最新评价
export const getComment = goods_id => fetchEndpoint('/hh/hh.product.comment.first', 'post', { goods_id })

// 商品评价统计
export const getReviewsubtotal = product =>
  fetchEndpoint('/hh/hh.review.product.subtotal', 'post', { product: product })

// 商品列表
export const productList = params => {
  return new Promise((resolve, reject) => {
    if (params.category) {
      params.category = ('' + params.category).split(',')
    }
    fetchEndpoint('/hh/hh.product.list', 'POST', {
      brand_id: params.brand_id, // 品牌ID (选填)
      cat_id: params.category, // 分类ID (选填)
      // activity: params.activity, // 优惠活动id (选填) 2018.07.07 新增
      sort_key: params.sort_key, // 键
      sort_value: params.sort_value, // 值
      keyword: params.keyword, // 关键词
      page: params.page, // 当前第几页
      per_page: params.per_page, // 每页多少
      shop: params.shop, // 所属商户ID(选填)
      admin_order: params.admin_order, // 根据后台配置的推荐排序值由小到大排序：1:是 0:否(默认)
      tags_id: params.tags_id, // 查询结果增加设置为指定tags的商品，与 cat_id(分类ID) 同时存在时则展示 tags_id∪cat_id 的商品
      is_newbie: params.is_newbie, // 是否只查询新手限购的商品：1:是 0:否(默认)
      appoint: params.appoint // 是否指定商品
    }).then(
      res => {
        utils.splitMoneyLint(res.list)
        resolve(res)
      },
      error => {
        reject(error)
      }
    )
  })
}

// 商品详情
export const productGet = (product, modproductkey) => {
  return new Promise((resolve, reject) => {
    fetchEndpoint('/hh/hh.product.get', 'POST', {
      product: product, // 商品ID
      preview: modproductkey // preview
    }).then(
      res => {
        utils.splitMoneyLint(res)
        resolve(res)
      },
      error => {
        reject(error)
      }
    )
  })
}

// 分销 商品详情
export const productDistGet = (mlm_id, modproductkey) => {
  return new Promise((resolve, reject) => {
    fetchEndpoint('/hh/hh.product.get', 'POST', {
      mlm_id: mlm_id, // 商品分销id
      preview: modproductkey // preview
    }).then(
      res => {
        utils.splitMoneyLint(res)
        resolve(res)
      },
      error => {
        reject(error)
      }
    )
  })
}

// 收藏商品
export const productLike = product =>
  fetchEndpoint('/hh/hh.product.like', 'POST', {
    product: product // 商品ID
  })

// 取消收藏商品
export const productUnlike = product =>
  fetchEndpoint('/hh/hh.product.unlike', 'POST', {
    product: product // 商品ID
  })

// 已收藏的商品
export const productLikedList = (page, per_page) =>
  fetchEndpoint('/hh/hh.product.liked.list', 'POST', {
    page: page, // 当前第几页
    per_page: per_page // 每页多少
  })

// 立即购买（非购物车计算）
export const productPurchase = params => {
  return fetchEndpoint('/hh/hh.product.purchase', 'POST', {
    product: params.product,
    mlm_id: params.mlm_id,
    property: params.property, // 用户选择的属性ID
    amount: params.amount, // 数量
    consignee: params.consignee, // 收货人ID
    comment: params.comment, // 留言
    coupon_id: params.coupon_id, // 优惠券
    instalment_id: params.instalment_id, // 分期id
    secbuy_id: params.secbuy_id, // 秒杀商品id
    tiket: params.tiket, // 秒杀通行证
    train_sn: params.train_sn, //直通车商品sn
    reserved: params.reserved //兑换浣币总额，此参数大于0表示要生成临时订单
  })
}

// 临时订单 订单->提交接口
export const purchaseFromTmpOrder = ({ consignee, coupon_id, temp_order, reserved }) =>
  fetchEndpoint('/hh/hh.product.accept', 'POST', {
    consignee: consignee, // 收货地址
    coupon_id: coupon_id, // 临时订单 订单id 数组
    temp_order: temp_order, // 临时订单信息
    reserved: reserved // 兑换浣币总额，此参数大于0表示要生成临时订单
  })

// 临时订单 取消用户当前的临时订单
export const cancelTmpOrder = () => fetchEndpoint('/hh/hh.order.temp.cancel', 'POST', {})

// 购买须知-> 已知晓 不再提示
export const saveHasRead = (tag = 1) =>
  fetchEndpoint('/hh/hh.save.notification', 'POST', {
    notification_tag: tag // 已知晓 不再提示
  })

// 获取直通车优惠信息
export const trainBill = products =>
  fetchEndpoint('/hh/hh.train.bill', 'POST', {
    products: products // 商品信息数组
  })

// 合单分组金额计算
export const groupTotalPrice = ({ consignee, products, train_sn, isCart = false, temp_order }) =>
  fetchEndpoint('/hh/hh.product.group', 'POST', {
    consignee: consignee,
    temp_order: temp_order, // 临时订单 订单id 数组
    products: products,
    train_sn: train_sn, //直通车商品sn
    isCart: isCart // 是否是从购物车结算
  })

// 下车礼包详情
export const giftGet = (product, modproductkey) => {
  return new Promise((resolve, reject) => {
    fetchEndpoint('/activity/wx.gift.get', 'POST', {
      product: product // 商品ID
    }).then(
      res => {
        utils.splitMoneyLint(res)
        resolve(res)
      },
      error => {
        reject(error)
      }
    )
  })
}

// 下车 合单分组金额计算
export const groupXiacheTotalPrice = ({ consignee, products, train_sn, isCart = false, temp_order }) =>
  fetchEndpoint('/activity/wx.gift.checkout', 'POST', {
    ...products[0]
  })

// 下车 立即购买
export const productXiachePurchase = params => {
  return fetchEndpoint('/activity/wx.gift.purchase', 'POST', {
    product: params.product,
    property: params.property, // 用户选择的属性ID
    amount: params.amount, // 数量
    consignee: params.consignee, // 收货人ID
    comment: params.comment, // 留言
    instalment_id: params.instalment_id // 分期id
  })
}
