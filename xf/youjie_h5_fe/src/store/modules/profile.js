import { orderSubtotal } from '../../api/order'

// profile.js
const state = {
  hasChange: false, //app更改个人信息 前端调接口更新个人信息
  orderCount: {} // 订单数量信息
}

// mutations
const mutations = {
  // app更改用户名活头像时 h5同步更新
  updateProfile(state, payload) {
    state.hasChange = !this.hasChange
  },
  saveOrderCount(state, payload) {
    state.orderCount = payload
  }
}

const actions = {
  fetchOrderSubtotal({ commit }) {
    // 获取订单数量
    return new Promise((resolve, reject) => {
      orderSubtotal().then(res => {
        commit('saveOrderCount', res)
        resolve()
      })
    })
  }
}

export default {
  state,
  mutations,
  actions
}
