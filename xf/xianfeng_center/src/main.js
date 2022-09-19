import Vue from 'vue'
// import { sync } from 'vuex-router-sync'
import App from './app'
import store from './store'
import router from './router'
import plugins from './plugins'
import mixin from './mixin'
import './filter'

// global styles
import 'assets/libs/rem.js'
import 'assets/scss/style.scss'

// global config
Vue.config.debug = process.env.NODE_ENV === 'development'
Vue.config.silent = process.env.NODE_ENV === 'production'
Vue.config.productionTip = false

// plugins
Vue.use(plugins)
// mixin
Vue.mixin(mixin)
// sync(store, router, { moduleName: 'route' })

// Create root app
const app = new Vue({
  name: 'root',
  store: store,
  router: router,
  render: h => h(App),
})

// Mount to `#app` element
app.$mount('#app')
