import { fetchEndpoint } from '../server/network'

export const getPurchaseList = params => fetchEndpoint('/user/Purchase/PurchaseDebtList', 'POST', params)
