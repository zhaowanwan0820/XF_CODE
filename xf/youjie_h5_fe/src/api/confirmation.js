import { fetchJavaEndPoint } from '../api/WorkorderMessage.js'

// 获取用户确权信息
export const getConfirmInfo = () => fetchJavaEndPoint('/confirmation/firstp2pDealLoad/info', 'GET', false, {})

// 获取标题列表信息
export const getTitleList = params => fetchJavaEndPoint('/confirmation/firstp2pDeal/title', 'GET', false, params)

// 获取金额列表信息
export const getMoneyList = params => fetchJavaEndPoint('/confirmation/firstp2pDeal/money', 'GET', false, params)

// 标题确权
export const confirmTitle = params =>
  fetchJavaEndPoint('/confirmation/firstp2pDeal/confirmation/title', 'PUT', false, params)

// 金额确权
export const confirmMoney = params =>
  fetchJavaEndPoint('/confirmation/firstp2pDeal/confirmation/money', 'PUT', false, params)

// 金额确权
export const getTitleDetail = params => fetchJavaEndPoint('/confirmation/firstp2pDeal/detail', 'GET', false, params)

// 勾选‘不再提示’
export const changeReads = params => fetchJavaEndPoint('/confirmation/firstp2pUser/read/' + params, 'PUT', false)
