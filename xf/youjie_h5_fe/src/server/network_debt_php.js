/**
 * PHP债转服务 api请求处理
 * 专用
 */
import router from '../router/index'
import axios from 'axios'
import qs from 'qs'
import utils from '../util/util'
import { Toast } from 'mint-ui'

import store from '../store/index'
import { ENUM } from '../const/enum'

// 新建一个axios实例，不受全局的其他axios影响
const instance = axios.create({
  baseURL: root_limit,
  timeout: 6000
})

// 发起请求
export function fetchEndpointDebt(reqUrl, type = 'POST', data = {}) {
  type = type.toUpperCase()

  reqUrl = process.env.VUE_APP_APPSERVER_HOST + reqUrl
  if (type == 'GET') {
    let dataStr = '' //数据拼接字符串
    Object.keys(data).forEach(key => {
      dataStr += key + '=' + data[key] + '&'
    })

    if (dataStr !== '') {
      dataStr = dataStr.substr(0, dataStr.lastIndexOf('&'))
      reqUrl = reqUrl + '?' + dataStr
    }
  }

  return new Promise((resolve, reject) => {
    instance
      .request({
        url: reqUrl,
        method: type,
        data: data
      })
      .then(
        res => {
          if (Object.prototype.toString.call(res) === '[object Object]') {
            // 若可以把对象转回简单的类型就转
            let keys = Object.keys(res)
            if (keys.length == 1 && 'scalar' in res) {
              // 后端返回非数组、对象的类型时，会自动转为对象并给予键值 scalar，影响的类型有: 布林、数字、字符串
              res = res['scalar']
            } else if (
              Object.keys(res).every(function(value, index) {
                return value == index
              })
            ) {
              // 后端返回非数组类型时，会自动转为对象
              res = Object.values(res)
            }
          }
          resolve(res)
        },
        error => {
          error.toastObj = Toast(
            /^\<\!DOCTYPE/.test(error)
              ? '网络繁忙，请稍后重试' // 接口代码报错
              : error.errorMsg
              ? error.errorMsg
              : '网络繁忙-2，请稍后重试'
          )

          reject(error)
        }
      )
  })
}

// 请求拦截器
instance.interceptors.request.use(
  config => {
    let isAPIRequest = config.url.indexOf(process.env.VUE_APP_DEBTSERVER_HOST) == 0 ? true : false
    if (!isAPIRequest) {
      return config
    }

    // config.withCredentials = true

    let token = null
    if (store.getters.token) {
      token = store.getters.token
    }
    config.headers['X-HH-Authorization'] = token

    if (config.method !== 'get') {
      let params = config.data || {}
      for (let key in params) {
        if (params[key] === null || params[key] === undefined) {
          delete params[key]
        }
      }
      let post_body = qs.stringify(params)
      config.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8'
      config.data = post_body
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应拦截器
instance.interceptors.response.use(
  response => {
    if (!response) {
      console.error('返回内容为空，请确认程序是否有问题！')
      return {}
    }

    const isAPIRequest = response.config.url.indexOf(process.env.VUE_APP_DEBTSERVER_HOST) == 0 ? true : false

    // 非指定的api请求
    if (!isAPIRequest) {
      return response
    }

    // Token自动延长有效期
    let newToken = response.headers && response.headers['x-hh-new-authorization']
    if (newToken) {
      store.commit('saveToken', newToken)
    }

    if (response.data && (response.data.code === ENUM.ERROR_CODE.OK || !response.data.code)) {
      if (response.data.info) {
        Toast(response.data.info)
      }
      return response.data.data
    } else {
      let errorMessage = response.data.info
      let errorCode = response.data.code
      let data = response.data.data || {}
      logErrorInfo(response.config.url, errorCode, errorMessage)
      onAuthInvaild(errorCode, response.data)
      return Promise.reject({ errorCode: errorCode, errorMsg: errorMessage, data: data })
    }
  },
  error => {
    if (error.response) {
      error = error.response
      if (error.data) {
        error = error.data
      }
    }
    return Promise.reject(error)
  }
)

function logErrorInfo(url, errorCode, errorMessage) {
  if (process.env.NODE_ENV === 'development') {
    console.log('request url is: ', url)
    console.log('网络错误, 错误代码:=' + errorCode + '错误信息:=' + errorMessage)
  }
}
