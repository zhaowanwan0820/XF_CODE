import { fetchEndpoint } from '../server/network'

// 广告列表
export const bannerList = () => fetchEndpoint('/hh/hh.banner.list', 'POST')
