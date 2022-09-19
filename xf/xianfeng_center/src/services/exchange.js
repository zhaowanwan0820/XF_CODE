/**
 * Exchange service
 */

import Resource from './resource'
import store from '@/store'
import { axios } from '@/utils'

const $env = store.getters.env

class Exchange extends Resource {
  constructor() {
    super('openApiV2/exchange')
  }

  // 获取商城名称接口
  getPlatformName() {
    // "data": {
    //   "platform": "测试商城"
    // },
    return axios.get(`/${this.endpoint}/platformName`, {
      params: {
        appid: $env.appid,
      },
    })
  }

  // 校验授权登录接口
  submitCheckAuth(phone, code) {
    // phone               string  是  手机号码
    // verification_code   int     是  验证码
    // appid               int     是  商城id
    // "data": {
    //     前端需要将这个字段拼接到商城传递的redirect_url 后面 形如：https://m.youjiemall.com/#/h5?code=asdfaddxxfefasdfasdfaadd 然后location到商城
    //     code:'asdfaddxxfefasdfasdfaadd'
    // },
    return axios.post(`/${this.endpoint}/checkAuth`, {
      phone,
      verification_code: code,
      appid: $env.appid,
    })
  }

  // 获取用户状态接口
  getUserStatus() {
    // appid          string  是 商城id
    // openid         string  是 用户openid
    // exchange_no    string  是 兑换单号
    // amount         float   是 兑换金额
    // goodsInfo      string  是 商品信息
    // goods_order_no string  是 商品订单号
    // redirect_url   string  是 回跳地址
    // notify_url     string  是 异步通知地址
    // timestamp      string  是 时间戳 用于超时校验
    // singnature     string  是 签名
    // "data": {
    //     "auth_status":1,//是否已经授权该商城
    //     "agreement_status":1,//兑换协议是否已经同意
    //     "pay_password_status":1,//支付密码是否应设置
    //     "debt_balance":10000,//债权余额  注意：当 auth_status=0 或者 agreement_status=0时   debt_balance 必然返回 0
    //     "debt_type":{ //用户可用债权类型 用于债权兑换页筛选
    //         1:"尊享",
    //         2:"普惠",
    //         3:"工厂微金",
    //         4:"智多新",
    //     }
    //     "token":xxxdddaaasswwww 用于后续流程的接口权限校验
    //  },

    return axios.post(`/${this.endpoint}/userStatus`, {
      appid: $env.appid,
      openid: $env.openid,
      exchange_no: $env.exchange_no,
      amount: $env.amount,
      goodsInfo: $env.goodsInfo,
      goods_order_no: $env.goods_order_no,
      redirect_url: $env.redirect_url,
      notify_url: $env.notify_url,
      timestamp: $env.timestamp,
      signature: $env.signature,
      area_code: $env.area_code,
    })
  }

  getUserPasswordStatus() {
    // "data": {
    //     "pay_password_status":1,//支付密码是否应设置

    //  },
    debugger

    return axios.post(`/${this.endpoint}/userPasswordStatus`, {
      appid: $env.appid,
      openid: $env.openid,
      token: $env.token,
    })
  }

  // 同意兑换协议接口
  submitAgreement() {
    // appid          string  是 商城id
    // openid         string  是 用户openid
    // token          string  是 接口权限校验令牌
    return axios.post(`/${this.endpoint}/doAgreement`, {
      appid: $env.appid,
      openid: $env.openid,
      token: $env.token,
    })
  }

  // 获取债权列表接口
  getDebtList(debtType, page, limit = 10) {
    // appid          string  是 商城id
    // openid         string  是 用户openid
    // debt_type      int     是 债权类型 1尊享 2 普惠
    // page           int     是 default 1
    // limit          int     是 default 10
    // token          string  是 接口权限校验令牌
    // "data": {
    //     "list": [
    //         {
    //             "id": "83475328",
    //             "account": "42.00",
    //             "name": "盈益A006204768",
    //             "borrow_id": "6204768",
    //             "addtime": "1578971163",
    //             "black_status": "1",
    //             "is_priority_use": 1,
    //             "is_black": 0,
    //             "type": 0,
    //             "is_exchanging": 0,
    //             "payment_lately": 0
    //         },
    //         {
    //             "id": "83472297",
    //             "account": "100.00",
    //             "name": "盈益A006202324",
    //             "borrow_id": "6202324",
    //             "addtime": "1577704082",
    //             "black_status": "1",
    //             "is_priority_use": 0,
    //             "is_black": 0,
    //             "type": 0,
    //             "is_exchanging": 0,
    //             "payment_lately": 0
    //         }
    //     ],
    //     "total": "2"
    // },
    return axios.post(`/${this.endpoint}/debtList`, {
      appid: $env.appid,
      openid: $env.openid,
      debt_type: debtType,
      page,
      limit,
      token: $env.token,
    })
  }

  // 债权兑换提交接口
  // eslint-disable-next-line camelcase
  submitDebt(debtType, ids, password, redirect_url) {
    // appid          string  是 商城id
    // openid         string  是 用户openid
    // ids            array   是 出借记录id
    // debt_type      int     是 债权类型 1尊享 2 普惠
    // amount         int     是 兑换金额
    // exchange_no    string  是 兑换单号
    // password       string  是 交易密码
    // notify_url     string  是 商城来时所传递的异步通知notify_url的地址
    // token          string  是 接口权限校验令牌
    return axios.post(`/${this.endpoint}/debtCommit`, {
      appid: $env.appid,
      openid: $env.openid,
      select_debt: ids,
      debt_type: debtType,
      amount: $env.ph_total_amount > 0 ? $env.ph_total_amount : $env.amount,
      exchange_no: $env.exchange_no,
      password,
      redirect_url,
      notify_url: $env.notify_url,
      token: $env.token,
      area_code: $env.area_code,
    })
  }
}

export default new Exchange()
