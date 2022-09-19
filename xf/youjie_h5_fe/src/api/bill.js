import { fetchEndpoint } from '../server/network'

/**
 * 获取账单列表
 */
export const getMoneyHistory = params =>
  fetchEndpoint('/hh/hh.money.history', 'POST', {
    status: params.status,
    page: params.page,
    per_page: params.per_page,
    time_from: params.time_from,
    time_to: params.time_to
  })

// 获取账单详情
export const getBillDetail = id =>
  fetchEndpoint('/hh/hh.money.get', 'POST', {
    id: id
  })
