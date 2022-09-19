// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './App'
import router from './router'
import store from './store/index'

import utils from './util/util'
import './util/filters'
import './assets/style/reset.less'
Vue.config.productionTip = false

Vue.prototype.utils = utils

import Vant from 'vant';
import 'vant/lib/index.css';
import commonHeader from './components/CommonHeader'
import nullData from './components/NullData'
Vue.use(Vant)
Vue.component('common-header',commonHeader)
Vue.component('null-data',nullData)
/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  store,
  components: { App },
  template: '<App/>'
})
