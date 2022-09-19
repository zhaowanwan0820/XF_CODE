// initial state
const state = {
  openId: '', // 三方登录 openid
  popupBindPhoneShow: false // 绑定手机号弹窗
}

// mutations
const mutations = {
  saveOpenId: (state, openid) => {
    state.openId = openid
  },
  setPopupBindPhone: (state, status) => {
    state.popupBindPhoneShow = status
  }
}

export default {
  state,
  mutations
}
