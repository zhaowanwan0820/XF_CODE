import { fetchEndpoint } from '../server/network'

export const FddApi = params => fetchEndpoint('/user/XFUser/FddApi', 'GET', params)
