const state = {
  mlmProduct: {},
  isInternal: false, //是否为内部分销客
  selectId: 0, //离开商品池子页面 记录选择的商品
  popupStatus: false //展示提示框
}

const mutations = {
  saveMlmProduct(state, payload) {
    state.mlmProduct = payload
  },
  saveInternal(state, payload) {
    state.isInternal = payload
  },
  clearMlmProduct(state) {
    state.mlmProduct = {}
  },
  saveId(state, payload) {
    state.selectId = payload
  },
  clearId(state, payload) {
    state.selectId = 0
  },
  setPopup(state, payload) {
    state.popupStatus = payload
  }
}

export default {
  state,
  mutations
}
