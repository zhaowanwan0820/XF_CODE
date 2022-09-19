/**
 * Phone service
 */

import Resource from './resource'
import store from '@/store'
import { axios } from '@/utils'

class Phone extends Resource {
  constructor() {
    super('apiService/phone')
  }

  // 获取验证码接口
  getSmsVcode(phone) {
    // phone  string  是  手机号码
    // type   int     是  1:授权登录 0:普通登录，此处传 type=1
    // appid  int     是  商城id
    // "data": {
    // },
    return axios.post(`/${this.endpoint}/GetSmsVcode`, {
      phone,
      type: 1,
      appid: store.getters.env.appid,
    })
  }
}

export default new Phone()
