// initial state
const state = {
  // mainContainer's scrollTop
  containerScrollTop: 0
}

// mutations
const mutations = {
  changeContainerScrollTop(state, payload) {
    state.containerScrollTop = payload
  }
}

export default {
  state,
  mutations
}
