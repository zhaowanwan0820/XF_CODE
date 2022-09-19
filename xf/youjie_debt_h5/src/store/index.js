import Vue from 'vue'
import Vuex from 'vuex'

import createPersistedState from 'vuex-persistedstate'

import * as getters from './getter'
import mutations from './mutations'
import actions from './actions'

import app from './modules/app'
import tabBar from './modules/tabbar'
import auth from './modules/auth'
import home from './modules/home'
import keepAlive from './modules/keepAlive'
import transfer from './modules/transfer'
import confirmation from './modules/confirmation'
Vue.use(Vuex)

export default new Vuex.Store({
  modules: {
    app,
    tabBar,
    auth,
    home,
    keepAlive,
    transfer,
    confirmation
  },
  getters: getters,
  actions,
  mutations,
  plugins: [
    createPersistedState({
      key: 'm_assets_garden',
      paths: ['auth']
    })
  ]
})
