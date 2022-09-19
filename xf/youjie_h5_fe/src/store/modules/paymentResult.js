const state = {
  status: -1, // 支付状态 -1-未出结果； 1-成功； 2-失败
  message: '', // 结果信息
  order: undefined, // 订单sn
  order_id: undefined, // 订单id
  created_at: 0, // 订单创建时间
  share_pay: {
    sn: '',
    need_money: 0,
    need_surplus: 0,
    thumb: ''
  }
}

// mutations
const mutations = {
  initPaymentResultState(state) {
    state.status = -1
    state.created_at = ''
    state.order = undefined
    state.order_id = undefined
    state.message = 0
    state.share_pay = {
      sn: '',
      need_money: 0,
      need_surplus: 0,
      thumb: ''
    }
  },
  savePaymentState(state, payResult) {
    state.status = payResult.status || state.status
    state.created_at = payResult.created_at || state.created_at
    state.order = payResult.order || state.order
    state.order_id = payResult.order_id || state.order_id
    state.message = payResult.message || state.message
    if (payResult.share_pay) {
      state.share_pay = { ...payResult.share_pay }
    }
  }
}

export default {
  state,
  mutations
}
