// search.js
// initial state
const state = {
  status: null
}

// mutations
const mutations = {
  setCountdownStatus(state, value) {
    state.status = value
  },
  clearCountdownStatus(state, value) {
    state.status = null
  }
}

export default {
  state,
  mutations
}
