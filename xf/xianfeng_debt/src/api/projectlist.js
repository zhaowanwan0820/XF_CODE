import { fetchEndpoint } from '../server/network'

export const getProjectList = params => fetchEndpoint('/Launch/XfDebtGarden/TransferableDebtList', 'POST', params)

export const getTypeList = () => fetchEndpoint('/Debt/Index/ProjectType', 'POST', {})
