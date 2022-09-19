import { fetchEndpoint } from '../server/network'

export const getPurchaseList = params => fetchEndpoint('/Launch/Index/purchaselist', 'POST', params)
