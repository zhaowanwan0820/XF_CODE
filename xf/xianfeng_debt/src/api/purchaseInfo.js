import { fetchEndpoint } from '../server/network'

export const getPurchaseInfo = params => fetchEndpoint('/user/Purchase/PurchaseInfo', 'POST', params)
