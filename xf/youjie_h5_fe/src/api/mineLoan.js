import { fetchJavaEndPoint } from '../api/WorkorderMessage.js'

import { fetchEndpoint } from '../server/network'

/**
 * 获取账单列表
 */
export const getIntegral = params => fetchEndpoint('/hh/hh.user.profile.get', 'POST', {})

// 获取标题列表信息
export const getTitleList = params => fetchJavaEndPoint('/confirmation/firstp2pDealLoad/userCenter', 'GET', params)

// 获取普惠，尊享项目列表信息
export const getDateList = params => fetchJavaEndPoint('/confirmation/firstp2pDeal/title', 'GET', false, params)

// 获取普惠，尊享项目列表信息
export const getbankCard = params => fetchJavaEndPoint('/confirmation/firstp2pUser/bankCard', 'GET', false, params)

// 获取资产收支详情
export const getAssetList = params =>
  fetchJavaEndPoint('/confirmation/firstp2pUser/assets/detail', 'GET', false, params)
