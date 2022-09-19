// initial state
const state = {
  couponObj: {},
  couponInfo: {},
  couponSingleInfo: {}
}

// mutations
const mutations = {
  saveCoupon(state, payload) {
    state.couponObj = payload
  },
  clearCoupon(state, payload) {
    state.couponObj = {}
  },
  saveCouponInfo(state, payload) {
    state.couponInfo = payload
  },
  saveCouponSingleInfo(state, payload) {
    state.couponSingleInfo = payload
  }
}

export default {
  state,
  mutations
}
