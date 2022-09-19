// initial state
const state = {
  comment: '',
  balance: null,
  cartGoods: [], // 当前要结算的商品 list
  tmpOrder: {} // 临时订单信息
}

// mutations
const mutations = {
  saveCommentInfo(state, payload) {
    state.comment = payload.comment
  },
  clearCommentInfo(state) {
    state.comment = ''
  },
  saveBalanceInfo(state, value) {
    state.balance = value
  },
  clearBalanceInfo(state) {
    state.balance = null
  },
  saveSelectedCartGoods(state, payload) {
    state.cartGoods = payload.cartGoods
    // 生成新的结算时 清空之前的临时订单信息
    this.commit('clearTmpOrder')
  },
  clearSelectedCartGoods(state) {
    state.cartGoods = []
  },
  saveTmpOrder(state, order) {
    state.tmpOrder = order
  },
  clearTmpOrder(state) {
    state.tmpOrder = {}
  }
}

export default {
  state,
  mutations
}
