import store from '../../store/index'
import { Indicator, MessageBox } from 'mint-ui'
import router from '../../router/index'
import { productPurchase } from '../../api/product'
import { cartCheckout } from '../../api/cart'

// 要兑换的浣币数
let local_need = 0
// 生成临时订单的api
let local_api = ''
// 生成临时订单接口需要的数据
let local_postData = {}

export const goExchange = async (need, api, postData, goods_ids) => {
  // 是否已同意 债权兑换积分协议
  const isAllowExchange = store.state.auth.user.is_allow_exchange

  if (!isAllowExchange) {
    return router.app.$router.push({ name: 'agreementPage' })
  }
  const isExchangeAndPay = !!need
  const needbond = isExchangeAndPay ? need : 0
  const params = { need: needbond }
  if (!isExchangeAndPay) {
    params.canPartial = true
  }

  if (goods_ids) params.product = goods_ids

  local_need = needbond
  local_api = api
  local_postData = postData
  // 兑换债权前 先生成临时订单
  let canExhange = true
  try {
    await createTmpOrderBefore()
  } catch (e) {
    canExhange = false
  }
  if (!canExhange) return

  params['debt_id'] = store.state.checkout.tmpOrder.debt_id
  router.app.$router.push({ name: 'bondDebt', params: params })
}

// 生成临时订单
const createTmpOrderBefore = () => {
  const data = { ...local_postData, reserved: local_need }
  return new Promise((resolve, reject) => {
    Indicator.open()
    local_api(data)
      .then(res => {
        store.commit('saveTmpOrder', res)
        resolve()
      })
      .catch(err => {
        reject(err)
      })
      .finally(() => {
        Indicator.close()
      })
  })
}
