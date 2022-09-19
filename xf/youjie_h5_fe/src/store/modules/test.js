// test.js

let readyStatus = false

// initial state
const state = {
  isTesMode: false
}

// mutations
const mutations = {
  //
  changeTest(state, value) {
    if (!value || readyStatus) {
      state.isTesMode = value
    }
  },

  readyTest(state) {
    if (!readyStatus) {
      setTimeout(() => {
        readyStatus = false
      }, 2e3)
    }
    readyStatus = true
  }
}

export default {
  state,
  mutations
}
