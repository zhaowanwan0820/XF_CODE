import { fetchEndpoint } from '../server/network'

export const getTargetList = params => fetchEndpoint('', 'POST', params)
