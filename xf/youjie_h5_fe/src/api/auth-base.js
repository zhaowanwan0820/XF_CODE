import { fetchEndpoint } from '../server/network'

// 去授权设置页
export const authSettingURL = () => fetchEndpoint('/hh/hh.auth.setting', 'POST')

// 授权状态检查
export const authCheck = (clearCache = 0) =>
  fetchEndpoint('/hh/hh.auth.check', 'POST', {
    refresh: clearCache
  })

// app内签署协议
export const saveAgreement = () => fetchEndpoint('/hh/hh.save.agreement', 'POST')
