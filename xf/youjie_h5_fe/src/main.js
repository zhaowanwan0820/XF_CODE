import Vue from 'vue'

import 'babel-polyfill'

import Mint from 'mint-ui'

import App from './App.vue' // app 容器
import router from './router/index'
import store from './store/index'
import utils from './util/util'
import validator from './util/validator'
import wxApi from './util/wxapi'
import Accounting from 'accounting' // 金钱相关
import $cookie from 'js-cookie'

import 'mint-ui/lib/style.css'
import './assets/style/reset.scss'
import './assets/style/common.scss'
import './assets/style/my-mint.scss'

import './util/mixin_handleGoBack'
import './util/mixin_handleScrollTopForKeepAlive'

import VueAwesomeSwiper from 'vue-awesome-swiper'
import 'swiper/dist/css/swiper.css'
Vue.use(VueAwesomeSwiper)

// 将时间戳转成日期
Vue.filter('convertTime', function(timeStr) {
  return utils.formatDate('YYYY-MM-DD HH:mm:ss', timeStr)
})

// 测试
if (process.env.NODE_ENV === 'test') {
  require('./util/vconsole.js')
}

// 注册全局公用组件
import './components/common'

Vue.use(Mint)

Vue.prototype.utils = utils
Vue.prototype.validator = validator
Vue.prototype.wxApi = wxApi
Vue.prototype.$accounting = Accounting
Vue.prototype.$cookie = $cookie

// 系统时间
import { getSysTime } from './util/getSysTime'
Vue.prototype.getSysTime = getSysTime
// 自动修正系统 时钟偏移
import './util/autoFixSysTime'

// 页面跳转至外链时 定义的公用方法
import { pageJump } from './util/pageJump'
Vue.prototype.Jump = pageJump

import api from './api/WorkorderMessage.js'

// 将API方法绑定到全局
Vue.prototype.$api = api
Vue.config.productionTip = false

//埋点
import './util/directive_buryPoint'

// hhApp bridge相关处理
import './util/appBridgeReady'
// 提供给app调用的 定义在window上面的方法
import './assets/js/for-HHApp'

new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#appMall')
