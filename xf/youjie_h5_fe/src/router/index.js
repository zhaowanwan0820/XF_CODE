import Vue from 'vue'
import VueRouter from 'vue-router'
import store from '../store/index'

import routes from './router'
import utils from '../util/util'

import { tabBarRouteName, getQuery, getTabSchema } from './util'

import { sendBuryingPointInfo } from '../api/buryingPoint'

import { Toast } from 'mint-ui'

Vue.use(VueRouter)

const router = new VueRouter({
  routes,
  mode: 'hash',
  strict: process.env.NODE_ENV !== 'production'
})

router.beforeEach((to, from, next) => {
  if (to.name == 'payment' || to.name == 'login') {
    const code = utils.getUrlKey(location.href, 'code')
    if (code) {
      const reg = new RegExp(`code=${code}&?`)
      const newHref = location.href.replace(reg, '')
      utils.setCookie('wx_code', code)
      window.location.href = newHref
      return
    }
  }

  // 正常情况下根据meta.isshowtabbar 判断是否展示底部TabBar，but有些页面需要动态展示（像cart购物车页面 若是从商品详情进入购物车就没有底部Tabbar），
  // 则可以设置meta.setIsShowTabBar，并在下边根据params中的该参数的值来来重置meta.isshowtabbar（参考./router.js中cart），
  // !!! 在业务代码中只需根据 meta.isshowtabbar 来判断即可，不要在业务代码中使用 meta.setIsShowTabBar 对应的params参数
  let metaData = to.meta
  if (metaData.setIsShowTabBar) {
    for (const key in to.params) {
      if (key == metaData.setIsShowTabBar) {
        metaData.isshowtabbar = parseInt(to.params[metaData.setIsShowTabBar])
      }
    }
  }

  // App 进入新页面时内重置 isTop
  const rootApp = router.app
  if (rootApp.isHHApp) {
    store.commit('SET_IS_TOP', false)
  }

  // if (!to.meta.canShowWithoutAuth) {
  //   store.dispatch('fetchWxAuthCheck')
  // }

  // 是否一键下车用户 且未进入过下车活动页
  const isXiache = store.getters.isXiache
  if (to.name != 'promotion001' && to.name != 'debtinfo' && to.name != 'protocol' && isXiache && !store.state.auth.isHasEnterXiachePage) {
    next({ name: 'promotion001' })
    return
  }

  next()
})

router.afterEach((to, from) => {
  document.title = (to.meta.title ? to.meta.title + '-' : '') + utils.storeName

  // --- start 通过压栈方式 判断是前进还是后退
  const routerStack = store.getters.routerStack
  if (to.fullPath === routerStack[routerStack.length - 1]) {
    console.log('后退')
    // 后退
    store.commit('popRouterStack')
    store.commit('popInclude', from.name)
  } else {
    console.log('前进')
    // 前进
    if ('/' !== from.fullPath) {
      store.commit('pushRouterStack', from.fullPath)
    }
    store.commit('pushInclude', to.name)
  }
  // --- end
  // --- tabBar切换时(或者tabBar进入登录页时) 强制清除from的keepAlive缓存
  if (tabBarRouteName.includes(from.name) && (tabBarRouteName.includes(to.name) || 'login' === to.name)) {
    store.commit('popInclude', from.name)
  }
})

export default router
