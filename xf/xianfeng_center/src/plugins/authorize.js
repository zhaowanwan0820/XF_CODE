/**
 * Check login state
 * Some middleware to help us ensure the user is authenticated.
 * https://github.com/vuejs/vue-router/issues/1048
 * https://jsfiddle.net/yezr0jjt/
 */

// import store from '@/store'
import router from '@/router'
import { axios } from '@/utils'

export default Vue => {
  // Authorize (Make sure that is the first hook.)
  router.beforeHooks.unshift((to, from, next) => {
    // don't need authorize
    if (!to.meta.requireAuth) return next()
    // check login state
    // store.dispatch('checkToken').then(valid => {
    //   // authorized
    //   if (valid) return next()
    //   // unauthorized
    //   console.log('Unauthorized')
    //   next({ name: 'login', query: { redirect: to.fullPath } })
    // })
  })

  // login page visiable
  router.beforeEach((to, from, next) => {
    next()
    // check login state
    // store.dispatch('checkToken').then(valid => {
    //   if (!valid) return next()
    //   // when logged in
    //   console.log('Authorized')
    //   next({ path: to.query.redirect || '/' })
    // })
  })

  // 请求拦截器
  axios.interceptors.request.use(
    config => {
      // 每次发送请求之前判断vuex中是否存在token
      // 如果存在，则统一在http请求的header都加上token，这样后台根据token判断你的登录情况
      // 即使本地存在token，也有可能token是过期的，所以在响应拦截器中要对返回状态进行判断
      // const token = store.state.token
      // token && (config.headers.Authorization = token)
      // config.headers.Authorizationa = 'aaaaaaaaaaa'
      return config
    },
    error => {
      return Promise.error(error)
    },
  )
}
