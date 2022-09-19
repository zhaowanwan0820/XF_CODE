import { fetchEndpoint } from '../server/network'

// 近30笔转让折扣
export const getLast30DiscountList = (limit = 15) =>
  fetchEndpoint('/Debt/Wx/RecentDebtData', 'POST', {
    limit: limit // 最近N笔
  })
// 近7天日平均折扣趋势
export const getLast7dayDiscountList = (days = 7) =>
  fetchEndpoint('/Debt/Wx/Index', 'POST', {
    days: days // 最近N天
  })
// 近5笔折扣最高求购计划
// export const getDiscountPlanList = (limit = 5) =>
//   fetchEndpoint('/Launch/Index/purchaselist', 'POST', {
//     limit: limit, // N笔
//     field: 2, // 排序字段 1：发布时间 2：折扣
//     order: 1 // 1：倒序 2：升序
//   })
