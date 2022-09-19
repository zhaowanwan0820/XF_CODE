import Vue from 'vue'
import VueRouter from 'vue-router'
import store from '../store/index'

import routes from './router'

// https://github.com/vuejs/vue-router/issues/2881
const originalPush = VueRouter.prototype.push
VueRouter.prototype.push = function push(location, onResolve, onReject) {
  if (onResolve || onReject) return originalPush.call(this, location, onResolve, onReject)
  return originalPush.call(this, location).catch(err => err)
}

Vue.use(VueRouter)

const router = new VueRouter({
  routes,
  mode: 'hash',
  strict: process.env.NODE_ENV !== 'production'
})

router.beforeEach((to, from, next) => {
  const routeNotAllow = [
    'purchaseList',
    'AuthChooseOgnztion',
    'AuthCheck',
    'AuthCheckResult',
    'confirmation',
    'confirmationList',
    'confirmatResult',
    'confirmationDetail',
    'choosePlatForConfirm',
    // 'mine',
    'huiYuanArea',
    'login',
    'settphone',
    'settpass',
    'editpass',
    'assets',
    'mechanism',
    'project',
    'recharge',
    'withdrawal'
  ]
  if (routeNotAllow.includes(to.name)) {
    return next({ name: 'debtMarket' })
  }

  // 风险测评跳转
  // const ignoreRisk = ['evaluation', 'mytransfer', 'mysubscription']
  const ignoreRisk = ['projectList', 'targetList','purchaseDetails','exclusiveDetails','subjectDetails']
  if (ignoreRisk.indexOf(to.name) >= 0  && (!store.getters.isDoneRiskTest || !store.state.auth.authAgreement)) {
    store.dispatch('fetchRiskResult').then(res => {
      if (!res.code && !res.data.risk_level) {
        return next({ name: 'evaluation', params: { type: 1 } })
      } else {
        return next()
      }
    })
  } else {
    next()
  }
 next()
})

router.afterEach((to, from) => {
  document.title = (to.meta.title ? to.meta.title + '-' : '') + '债权市场'

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
})

export default router
