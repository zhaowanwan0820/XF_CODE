import { fetchEndpoint } from '../server/network'
import { fetchJavaEndPoint } from '../server/network_java'

// 保存 债转服务协议 已阅读标识
export const saveDebtAgreement = () => fetchEndpoint('/Launch/DebtGarden/TransAgree', 'POST', {})

// 发送验证码
export const getVelCode = phone =>
  fetchEndpoint('/apiService/phone/getSmsVcode', 'POST', {
    phone
  })

// 登录login
export const getLogin = params => fetchEndpoint('/assetGarden/login/regLogin', 'POST', params)

// 获取用户风险问卷结果
export const getRiskTestResult = () => fetchEndpoint('/Launch/DebtGarden/TransLook', 'POST')

// 获取用户设置支付密码状态
export const getIsSetPassword = () => fetchJavaEndPoint('/confirmation/firstp2pUser/pwd/presence', 'GET')
