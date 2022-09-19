import { fetchEndpoint } from '../server/network'

// 我的认购列表
export const getSubscriptionList = params => fetchEndpoint('/Launch/index/Subscription', 'POST', params)
