import { fetchEndpoint } from '../server/network'

// 余额信息
export const balanceGet = (from = '') =>
  fetchEndpoint('/hh/hh.balance.get', 'POST', {
    from: from
  })

// 我的余额列表
export const balanceList = (status, page, per_page) =>
  fetchEndpoint('/hh/hh.balance.history', 'POST', {
    status: status, // 状态   全部  收入  支出
    page: page, // 当前第几页
    per_page: per_page // 每页多少
  })
