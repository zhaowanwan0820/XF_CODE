const state = {
  exchangeBond: 0, // 要兑换的债权数量
  canExchangePartially: false, // 是否可以部分兑换
  exchangeBondOrderId: '', // 兑换时的订单号
  exchangeBondProductId: '', // 兑换时的商品id
  currentBond: 0, // 当前用户拥有债权
  currentBondIsInteger: false, // 当前用户拥有债权是否为整数
  debt_id: 0 // 兑换债权前生成的临时订单的关联id
}

// mutations
const mutations = {
  saveExchangeBondState(state, payload) {
    state.exchangeBond = parseFloat(payload.bond)
    state.canExchangePartially = !!payload.canPartial
    state.exchangeBondOrderId = payload.order || ''
    state.exchangeBondProductId = payload.product || ''
    state.debt_id = payload.debt_id
  },

  saveCurrentBondState(state, value) {
    state.currentBond = parseFloat(value)
    state.currentBondIsInteger = Number.isInteger(state.currentBond)
  }
}

export default {
  state,
  mutations
}
