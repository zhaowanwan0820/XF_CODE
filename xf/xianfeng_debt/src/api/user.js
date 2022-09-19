import { fetchEndpoint } from '../server/network'

// 保存 债转服务协议 已阅读标识
export const saveDebtAgreement = () => fetchEndpoint('/Launch/XfDebtGarden/TransAgree', 'POST', {})

// 发送验证码
export const getVelCode = phone =>
  fetchEndpoint('/apiService/phone/getSmsVcode', 'POST', {
    phone
  })

// 登录login
export const getLogin = params => fetchEndpoint('/assetGarden/login/regLogin', 'POST', params)

// 获取用户风险问卷结果
export const getRiskTestResult = () => fetchEndpoint('/Launch/XfDebtGarden/TransLook', 'POST')

// 获取用户信息
export const getIsSetPassword = () => fetchEndpoint('/user/XFUser/UserInfo', 'POST', { type: 0 })
//获取用户专区银行卡
export const getUserBankCard = () => fetchEndpoint('/Debt/Xf/ZqUserBankCard', 'POST')

