import Vue from 'vue'
import store from '../store/index'
import hhApp from '../assets/js/mallApp-bridge'
import utils from './util'

// for Test
// import { Toast } from 'mint-ui'

// 商城App js-bridge
Vue.prototype.hhApp = hhApp
// 商城App 特腾讯应用宝下载地址
Vue.prototype.hhAppUrl = 'https://a.app.qq.com/o/simple.jsp?pkgname=com.huanhuanyiwu.mall&fromcase=40003'
// 是否 商城App
Vue.prototype.isHHApp = window.navigator.userAgent.indexOf('_YOUJIEMALL_') > -1 ? true : false

if (Vue.prototype.isHHApp) {
  const version = (Vue.prototype.AppVersion = utils.getHhAppVersion())
  // 0.4.0版本开始可以【同步】从app存储中读取数据
  if (version >= 40) {
    let user_info = hhApp.getData('user_info')
    if (user_info) {
      user_info = JSON.parse(user_info)

      // for Test
      // Toast('get user_info sucess')

      store.commit('signin', { token: user_info.token, user: user_info.user })
      store.dispatch('helperItzAuthCheck')
    }
  }
}
