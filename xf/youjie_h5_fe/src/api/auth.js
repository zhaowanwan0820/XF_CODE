import { fetchJavaEndPoint } from './WorkorderMessage'

// 获取用户确权信息
export const getAuthStatus = _ => fetchJavaEndPoint('/confirmation/firstp2pUser/authentication', 'GET', false, {})

// 用户认证
export const confirmAuth = params => fetchJavaEndPoint('/confirmation/firstp2pUser/card', 'GET', false, params)

// 查看登录用户是个人用户还是企业用户
export const identity = _ => fetchJavaEndPoint('/confirmation/firstp2pUser/identity', 'GET', false, {})
