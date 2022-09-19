import { fetchEndpoint } from '../server/network'

// 地区列表（三级地址）
export const regionList = last_at =>
  fetchEndpoint('/hh/hh.region.list', 'POST', {
    last_at: last_at
  })
