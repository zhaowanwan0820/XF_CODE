// initial state
const state = {
  payee_account: '',
  service_fee: 0.006,
  amount: 0,
  withdrawStatus: 0, // 提现成功/失败  0-无结果; 1-成功; 2-失败
  errorMsg: '' // 如果失败，失败的信息
}

// mutations
const mutations = {
  saveWithdrawInfo(state, payload) {
    state.payee_account = payload.payee_account
    state.service_fee = payload.service_fee
    state.amount = payload.amount
  },
  saveWithdrawStatus(state, payload) {
    state.withdrawStatus = payload.status
    state.errorMsg = payload.errorMsg
  }
}

// actions
const actions = {}

export default {
  state,
  mutations,
  actions
}
