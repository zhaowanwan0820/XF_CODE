/**
 * Vuex docs
 * https://vuex.vuejs.org/zh-cn
 * https://github.com/vuejs/vue-hackernews-2.0
 */

import Vue from 'vue'
import Vuex from 'vuex'

// // Make sure state writeable
// import * as state from './state' // prop readonly
import state from './state'
import getters from './getters'
import mutations from './mutations'
import actions from './actions'
import modules from './modules'

Vue.use(Vuex)

const strict = process.env.NODE_ENV !== 'production'

/**
 * @type {import('vuex/types').Store<typeof import('./state').default>}
 */
const store = new Vuex.Store({ state, getters, mutations, actions, modules, strict })

export default store

// Hot module replacement
if (module.hot) {
  module.hot.accept(['./getters', './mutations', './actions', './modules/env'], () => {
    store.hotUpdate({
      getters: require('./getters'),
      mutations: require('./mutations'),
      actions: require('./actions'),
      modules: {
        env: require('./modules/env'),
      },
    })
  })
}
