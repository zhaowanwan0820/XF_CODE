/**
 * Custom axios instance
 * > - [Axios的配置](https://blog.ygxdxx.com/2017/01/29/Axios-Config/)
 * > - [Vuex2和Axios的开发](https://blog.ygxdxx.com/2017/02/01/Vuex2&Axios-Develop/)
 * > - [Axios全攻略](https://blog.ygxdxx.com/2017/02/27/Axios-Strategy/)
 * > - [Vue 全家桶 + axios 前端实现登录拦截、登出、拦截器等功能](https://github.com/superman66/vue-axios-github)
 * > - [axios和网络传输相关知识的学习实践](http://www.jianshu.com/p/8e5fb763c3d7)
 * > - [Vue.js REST API Consumption with Axios](https://alligator.io/vuejs/rest-api-axios/)
 */

import axios from 'axios'
import qs from 'qs'

const instance = axios.create({
  baseURL: process.env.VUE_APP_API_BASE,
  timeout: 10 * 1000, // 10s
  headers: {
    // 'X-Custom-Header': 'foobar',
    // 'Authorization': true,
    'Content-Type': 'application/x-www-form-urlencoded',
  },
})

// 请求拦截器
instance.interceptors.request.use(
  config => {
    if (config.data) {
      config.data = qs.stringify(config.data) // 转为 formdata 数据格式
    }
    return config
  },
  error => Promise.error(error),
)

export default instance
