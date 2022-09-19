import { fetchEndpoint } from '../server/network'
import utils from '../util/util'

/**
 * Gets the mlm list.
 *
 * @param      {Object}  params  请求参数{page|per_page}
 */
export const getMlmProductList = params => fetchEndpoint('/hh/mlm/hh.product.list', 'post', params)

// 获取分销返佣商品详情
export const mlmProductGet = product => {
  return new Promise((resolve, reject) => {
    fetchEndpoint('/hh/mlm/hh.product.get', 'POST', {
      product: product // 商品id
    }).then(
      res => {
        if (res.origin_price) {
          utils.splitMoneyLint(res.origin_price)
        }
        resolve(res)
      },
      error => {
        reject(error)
      }
    )
  })
}

/**
 * 获取分销客分享详情
 *
 * @param      {product}  商品ID
 * @param      {page}     页码
 * @param      {per_page} 每页数量
 */
export const productLiveGet = params => fetchEndpoint('/hh/mlm/hh.product.live', 'POST', params)

/**
 * 保存并分享分销商品
 *
 * @params      {goods_id}    商品ID
 * @params      {remark}      好友说
 * @params      {shop_price}  分销客分销价
 * @params      {is_shop}     是否是店铺商品
 */
export const shareMlmProduct = params => fetchEndpoint('/hh/mlm/hh.product.share', 'POST', params)

/**
 * 分销客提现信息
 *
 */
export const getWithdrawAccount = () => fetchEndpoint('/hh/mlm/hh.withdraw.accountInfo', 'POST')

/**
 * 分销客提现提交
 *
 * @param      {money}            params  提现金额
 * @param      {payee_real_name}  params  姓名
 * @param      {payee_account}    params  支付宝账号
 */
export const withdrawMoneySubmit = params => fetchEndpoint('/hh/mlm/hh.withdraw.do', 'POST', params)

/**
 * 分销商品分销价格列表
 *
 * @param      {goods_id}   params  商品id
 */
export const getExclusivePrice = params => fetchEndpoint('/hh/mlm/hh.exclusiveprice.list', 'POST', params)

/**
 * 获取小店数据看板
 *
 */
export const getShopDashboard = () => fetchEndpoint('/hh/mlm/hh.shop.board', 'POST')
