import { fetchEndpoint } from '../server/network'

export const categoryList = deep =>
  fetchEndpoint('/hh/hh.category.list', 'POST', {
    deep: deep // 深度
  })
