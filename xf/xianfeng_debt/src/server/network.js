import { router } from '../main'
import axios from 'axios'
import qs from 'qs'
import XXTEA from '../assets/js/xxtea'
import CryptoJS from 'crypto-js'
import utils from '../util/util'
import { Toast } from 'vant'

import store from '../store/index'
import { ENUM } from '../const/enum'

const SIGN_KEY = 'arc4random()'
const ENCRYPT_KEY = 'getprogname()'

// 使用线上还是开发环境的后端接口
const origin =
  location.host === process.env.VUE_APP_H5_HOST
    ? process.env.VUE_APP_APPSERVER_ORIGIN_PROD
    : process.env.VUE_APP_APPSERVER_ORIGIN_DEV

function getUserAgent() {
  let userAgent = ''
  let platform = 'Mozilla'
  const { width, height } = window.screen

  var lang = navigator.systemLanguage ? navigator.systemLanguage : navigator.language
  userAgent =
    'Platform/' +
    platform +
    ', Device/' +
    utils.getDeviceID() +
    ', Lang/' +
    lang +
    ', ScreenWidth/' +
    width +
    ', ScreenHeight/' +
    height +
    ', GID/' +
    utils.getCookie('grwng_uid')
  return userAgent
}

// 超时时间
axios.defaults.timeout = 12e3

// 请求
axios.interceptors.request.use(
  config => {
    // loading + 1
    // store.dispatch('SetLoading', true)

    let isAPIRequest = config.url.indexOf(origin) == 0 ? true : false
    if (isAPIRequest) {
      // if (config.method === 'get') {
      config.withCredentials = true
      // }

      let token = ''
      if (store.getters.token) {
        token = store.getters.token
      }
      //config.headers['X-HH-AUTHORIZATION'] = token
      config.headers['X-HH-AUTHORIZATION'] = token

      //config.headers['X-HH-AUTHORIZATION'] ="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoiNDQ4NTM5NSIsImlzX29ubGluZSI6IjEiLCJzaWduX2FncmVlbWVudCI6MSwiZXhwIjoxNjMyOTI0NTI2fQ.ow0hWIHqKDvyUJos5YnLYAo9tlGGdQQXBGxozMlktnE"

      // config.headers['X-HH-UserAgent'] = getUserAgent()
      // config.headers['X-HH-Ver'] = process.env.VUE_APP_APPSERVER_VERSION

      if (config.method !== 'get') {
        let params = config.data || {}
        for (let key in params) {
          if (params[key] === null || params[key] === undefined) {
            delete params[key]
          }
        }
        let post_body = qs.stringify(params)
        config.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8'

        if (process.env.VUE_APP_ENCRYPTED) {
          // timestamp: 客户端秒级时间戳
          let timestamp = Date.parse(new Date()) / 1000 + ''
          // sign: HMAC-SHA256( timestamp + post_body, SIGN_KEY )
          let sign = CryptoJS.HmacSHA256(timestamp + post_body, SIGN_KEY)
          // xSign格式: sign,timestamp
          let xSign = sign + ',' + timestamp
          // config.headers['X-HH-Sign'] = xSign

          let encry_post_body = ''
          let body = null
          if (post_body && post_body.length) {
            encry_post_body = XXTEA.encryptToBase64(post_body, ENCRYPT_KEY)
            body = qs.stringify({ x: encry_post_body })
          }
          config.data = body
        } else {
          // config.headers['X-HH-Sign'] = null
          config.data = post_body
        }
      }
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应
axios.interceptors.response.use(
  response => {
    if (response) {
      let isAPIRequest = response.config.url.indexOf(origin) == 0 ? true : false

      if (isAPIRequest) {
        let newToken = response.headers && response.headers['x-hh-new-authorization']
        if (newToken) {
          store.commit('saveToken', newToken)
        }

        if (process.env.VUE_APP_ENCRYPTED) {
          // 加密
          if (response.data && response.data.data) {
            var raw = XXTEA.decryptFromBase64(response.data.data, ENCRYPT_KEY)
            var json = JSON.parse(raw)
            if (json) {
              delete response.data.data
              for (var key in json) {
                response.data[key] = json[key]
              }
            }
            logResponseParams(response)
            return response.data
          } else if (response.data && response) {
            let errorMessage = response.data.message
            let errorCode = response.data.code
            if (response.data.error) {
              // return response.data;
              logErrorInfo(response.config.url, errorCode, errorMessage)
              onAuthInvaild(errorCode, response.data)
              return Promise.reject({ errorCode: errorCode, errorMsg: errorMessage })
            }
          }
        } else {
          if (response) {
            if (response.data) {
              logResponseParams(response)
              return response.data
            } else {
              let errorMessage = response.data.info
              let errorCode = response.data.code
              let data = response.data.data || {}
              logErrorInfo(response.config.url, errorCode, errorMessage)
              onAuthInvaild(errorCode, response.data)
              return Promise.reject({ errorCode: errorCode, errorMsg: errorMessage, data: data })
            }
          }
        }
      } else if (response.config.newapi == 2 || response.newapi == null) {
        if (response.data) {
          return response.data
        }
      } else {
        console.log('请求地址错误!')
      }
    } else {
      console.log('网络错误')
    }
  },
  error => {
    // loading - 1
    // store.dispatch('SetLoading', false)
    if (error.response) {
      error = error.response
      if (error.data) {
        error = error.data
      }
    }
    return Promise.reject(error)
  }
)

function logResponseParams(response) {
  if (process.env.NODE_ENV === 'development') {
    let str = JSON.stringify(response.data)
    console.groupCollapsed(
      '%c' + utils.formatDate('HH:mm:ss.SSS') + '%c请求' + response.config.url + '\n',
      'font-weight:normal;color:#fff;background:#35495e;padding:2px 7px;border-radius:3px 0 0 3px;',
      'font-weight:normal;color:#fff;background:#41b883;padding:2px 7px;border-radius:0 3px 3px 0;',
      str.length < 80 ? str : str.substring(0, 80) + '...'
    )
    console.count('SN')
    // console.info(response);
    response.config.data && console.log('%c参数\n', 'color:#aaa', response.config.data)
    console.log('%c响应\n', 'color:#aaa', response.data)
    console.groupEnd()
  }
}

function logErrorInfo(url, errorCode, errorMessage) {
  if (process.env.NODE_ENV === 'development') {
    console.log('request url is: ', url)
    console.log('网络错误, 错误代码:=' + errorCode + '错误信息:=' + errorMessage)
  }
}

function onAuthInvaild(errorCode, data) {
  if (errorCode == ENUM.ERROR_CODE.TOKEN_INVALID || errorCode == ENUM.ERROR_CODE.TOKEN_EXPIRED) {
    // 前端登录状态 退出
    store.commit('kickout')
    if (router.app.isHHApp) {
      router.app.hhApp.logout()
    }

    let name = router.currentRoute.name
    if (name && (name !== 'home' && name !== 'category')) {
      router.app.$router.replace({ name: 'login' })
    }
  }
}

// 发起请求(不加host)
export function fetchAlone(reqUrl, type = 'GET', data = {}) {
  return fetchEndpoint(reqUrl, type, data, 2)
}

// 发起请求
export function fetchEndpoint(reqUrl, type = 'POST', data = {}, newapi = 0) {
  if (!reqUrl) {
    return
  }
  type = type.toUpperCase()

  if (newapi != 3 && newapi != 2) {
    reqUrl = origin + reqUrl
  }

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
    axios({
      newapi: newapi,
      method: type,
      url: reqUrl,
      data: data
    }).then(
      res => {
        resolve(res)
      },
      error => {
        console.log('error', error)
        if (error.errorMsg) {
          Toast(error.errorMsg)
        } else if (/^\<\!DOCTYPE/.test(error)) {
          // 接口代码报错
          Toast('网络繁忙，请稍后重试')
        } else {
          Toast('网络繁忙-2，请稍后重试')
        }

        // 不把服务端的错误reject给业务层
        resolve({ code: -1, data: {} })
      }
    )
  })
}
