import { fetchEndpoint } from '../server/network'

// 第三方同步登录
export const authWeb = (vendor, scope, code, invite_code, is_app) =>
  fetchEndpoint('/hh/hh.auth.web', 'POST', {
    vendor: vendor, // 第三方平台类型
    scope: scope, // 第三方平台作用域(例如微信的snsapi_userinfo)
    code: code, // code参数(例如微信网页授权的第一步)
    invite_code: invite_code, // 推荐人ID（选填）
    is_app: is_app // 是否是App内微信授权登录
  })

// 获取微信的config
export const getWxConfig = () => fetchEndpoint('/hh/hh.config.wechat', 'POST')
