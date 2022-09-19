// refund.js
// initial state
const state = {
  status: 0 // 退款提交申请 1-成功; 2-失败
}

// mutations
const mutations = {
  //
  saveRefundStatus(state, value) {
    state.status = value
  }
}

export default {
  state,
  mutations
}
