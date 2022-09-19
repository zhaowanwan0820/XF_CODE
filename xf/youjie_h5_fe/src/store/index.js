import Vue from 'vue'
import Vuex from 'vuex'

import createPersistedState from 'vuex-persistedstate'

import * as getters from './getter'
import mutations from './mutations'
import actions from './actions'

import address from './modules/address'
import app from './modules/app'
import auth from './modules/auth'
import mystore from './modules/mystore'
import balance from './modules/balance'
import bond from './modules/bond'
// import cardpage from './modules/cardpage'
import cart from './modules/cart'
import seckill from './modules/seckill'
import secCountdown from './modules/secCountdown'
import seckillList from './modules/seckillList'
// import cashgift from './modules/cashgift'
import category from './modules/category'
import checkout from './modules/checkout'
import config from './modules/config'
import coupon from './modules/coupon'
import delivery from './modules/delivery'
import detail from './modules/detail'
import home from './modules/home'
// import invoice from './modules/invoice'
import itouzi from './modules/itouzi'
// import message from './modules/message'
import order from './modules/order'
import payment from './modules/payment'
import mlm from './modules/mlm'
import paymentResult from './modules/paymentResult'
import product from './modules/product'
import profile from './modules/profile'
import region from './modules/region'
// import router from './modules/router'
// import score from './modules/score'
import search from './modules/search'
// import shipping from './modules/shipping'
import tabBar from './modules/tabbar'
import refund from './modules/refund'
import test from './modules/test'
import withdraw from './modules/withdraw'
import keepAlive from './modules/keepAlive'
import shop from './modules/shop'
import login from './modules/login'
import confirmation from './modules/confirmation'

Vue.use(Vuex)

export default new Vuex.Store({
    modules: {
        address,
        app,
        auth,
        mystore,
        balance,
        bond,
        // cardpage,
        cart,
        seckill,
        secCountdown,
        seckillList,
        // cashgift,
        category,
        checkout,
        config,
        coupon,
        delivery,
        detail,
        home,
        // invoice,
        itouzi,
        // message,
        order,
        mlm,
        payment,
        paymentResult,
        product,
        profile,
        region,
        // router,
        // score,
        search,
        // shipping,
        tabBar,
        test,
        withdraw,
        refund,
        keepAlive,
        shop,
        login,
        confirmation
    },
    getters: getters,
    actions,
    mutations,
    plugins: [
        createPersistedState({
            key: 'ecm',
            paths: ['auth', 'region', 'tabBar', 'category', 'home', 'mlm', 'keepAlive', 'checkout', 'coupon']
        })
    ]
})
