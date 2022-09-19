import { fetchEndpoint } from '../server/network'

// 平台列表
export const getPlatformList = platform_name =>
  fetchEndpoint('/assetGarden/platform/getList', 'POST', { platform_name })

// 选择平台提交
export const choosePlatform = platform_id => fetchEndpoint('/assetGarden/user/choosePlatform', 'POST', { platform_id })

// 提交用户信息
export const confirmAuth = params => fetchEndpoint('/assetGarden/user/bindPlatform', 'POST', params)

// 授权平台-提交
export const authPlatform = platform_id => fetchEndpoint('/assetGarden/user/authPlatform', 'POST', { platform_id })

// 获取用户确权信息
export const getAuthStatus = _ => fetchEndpoint('/confirmation/firstp2pUser/authentication', 'GET', {})

// 获取用户确权信息
export const getConfirmInfo = params => fetchEndpoint('/debtConfirm/Index/getTenderDebtConfirmCount', 'POST', params)
