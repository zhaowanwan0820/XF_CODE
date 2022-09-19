import Vue from 'vue'

import 'babel-polyfill'

import Mint from 'mint-ui'

import App from './App.vue' // app 容器
import router from './router/index'

import store from '../../store/index'

import utils from '../../util/util'
import '../../util/mixin_handleGoBack'

// App bridge相关处理
import '../../util/appBridgeReady'

import $cookie from 'js-cookie'

import 'mint-ui/lib/style.css'
import '../../assets/style/reset.scss'
import '../../assets/style/common.scss'
import '../../assets/style/my-mint.scss'

Vue.use(Mint)

Vue.prototype.utils = utils
Vue.prototype.$cookie = $cookie

Vue.config.productionTip = false

new Vue({
  store,
  router,
  render: h => h(App)
}).$mount('#appOperation')

