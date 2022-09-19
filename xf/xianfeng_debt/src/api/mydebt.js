import { fetchEndpoint } from '../server/network'

// 获取当前项目
export const getDebtList = params => fetchEndpoint('/Launch/XfDebtGarden/DebtList', 'POST', params)
//取消发布
export const cancelDebt = params => fetchEndpoint('/Debt/User/Cancel', 'POST', params)
//重新发布
export const republish = params => fetchEndpoint('/Debt/User/Again', 'POST', params)
//查看合同
export const viewPDF = params => fetchEndpoint('/Debt/User/Contract', 'POST', params)
