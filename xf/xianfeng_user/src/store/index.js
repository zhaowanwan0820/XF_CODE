import Vue from 'vue'
import Vuex from 'vuex' 

import * as getters from './getter'
import auth from './modules/auth' 
import login from './modules/login' 
import createPersistedState from 'vuex-persistedstate'

Vue.use(Vuex)

export default new Vuex.Store({
    modules: { 
        auth, 
        login 
    }, 
    getters: getters, 
    plugins: [
        createPersistedState({
        key: 'auth',
        paths: ['auth']
        })
    ]
})
