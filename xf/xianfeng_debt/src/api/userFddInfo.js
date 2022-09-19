import { fetchEndpoint } from '../server/network'

export const userFddInfo = params => fetchEndpoint('/user/Purchase/UserFddInfo', 'POST', params)
