import { fetchEndpoint } from '../server/network'

// 提交发布转让
export const projectInfo = params =>
  fetchEndpoint('/Launch/XfDebtGarden/DebtDetails', 'POST', params)

// 提交发布转让
export const addRelease = params =>
  fetchEndpoint('/Debt/Index/ProjectTransfer', 'POST', params)
