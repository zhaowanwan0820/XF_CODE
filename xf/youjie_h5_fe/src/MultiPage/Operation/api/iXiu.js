import { fetchEndpoint } from '../../../server/network'

// 获取活动页面内容（Html串）
export const getEventContent = (name, token) =>
  fetchEndpoint('/hh/hh.static.act', 'POST', {
    name: name,
    token: token
  })
