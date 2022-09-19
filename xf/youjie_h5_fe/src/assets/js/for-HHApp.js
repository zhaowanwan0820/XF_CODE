import store from '../../store/index'
import router from '../../router/index'

window.getUserTokenForApp = function() {
  return decodeURI(store.getters.token)
}

// app登录
window.loginFromApp = function(data) {
  data = JSON.parse(data)
  store.commit('signin', { token: data.token, user: data.user })
  store.dispatch('helperItzAuthCheck')
}
// app登出
window.logoutFromApp = function(user) {
  store.commit('signout')
  router.push({ name: 'home' })
}
// app更新头像+昵称
window.updateProfile = function() {
  store.commit('updateProfile')
}

// 点击 App 底部导航栏时，刷新对应 tab 栏中的 H5 页面
window.refreshTab = tabIndex => {
  store.commit('UPDATE_TAB_COUNTER', tabIndex)
}
