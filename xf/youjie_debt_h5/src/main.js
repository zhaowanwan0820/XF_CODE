import Vue from 'vue'

import 'babel-polyfill'

import App from './App.vue' // app 容器
import router from './router/index'
import store from './store/index'
import utils from './util/util'
import validator from './util/validator'
import $cookie from 'js-cookie'

import './assets/style/reset.less'

import './util/mixin_handleGoBack'
import './util/mixin_handleScrollTopForKeepAlive'

import './util/filters'
import './util/directives'

// import yjmall from './util/mall_bridge'
// Vue.prototype.$yjmall = yjmall

// 测试
if (process.env.NODE_ENV === 'test') {
  require('./util/vconsole.js')
}

// 注册全局公用组件
// import './components/common'

import Vant from 'vant'
import 'vant/lib/index.less'
Vue.use(Vant)

Vue.prototype.utils = utils
Vue.prototype.validator = validator
Vue.prototype.$cookie = $cookie

// 加一个简洁的loading
import { $loading } from './util/vantComponents.js'
Vue.prototype.$loading = $loading

// 系统时间
import { getSysTime } from './util/getSysTime'
Vue.prototype.getSysTime = getSysTime
// 自动修正系统 时钟偏移
import './util/autoFixSysTime'

// 页面跳转至外链时 定义的公用方法
import { pageJump } from './util/pageJump'
Vue.prototype.Jump = pageJump

import yjApp from './util/mall_bridge'
Vue.prototype.yjApp = yjApp

Vue.config.productionTip = false

new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app')

export { router, store }
