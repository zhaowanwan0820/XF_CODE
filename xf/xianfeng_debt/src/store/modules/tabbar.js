// tabbar.js
const state = {
  currentTabBar: 'debtMarket'
}
// mutations
const mutations = {
  changeTabBar(state, value) {
    state.currentTabBar = value
  }
}

export default {
  state,
  mutations
}
