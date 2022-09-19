import { fetchEndpoint } from '../server/network'
import store from '../store/index'
let current_plat = {
  platform_id: store.state.auth.platInfo.platform_id,
  user_id: store.state.auth.platInfo.user_id,
  platform_user_id: store.state.auth.platInfo.platform_user_id
}

// 获取用户确权信息
export const getConfirmInfo = () =>
  fetchEndpoint('/debtConfirm/Index/getTenderDebtConfirmCount', 'POST', { ...current_plat })

// 获取标题列表信息
export const getTitleList = params =>
  fetchEndpoint('/debtConfirm/Index/getTenderConfirmList', 'POST', { ...current_plat, ...params })

// 标题确权
export const confirmTitle = tender_ids =>
  fetchEndpoint('/debtConfirm/index/confirmDebt', 'POST', { ...current_plat, tender_ids })

// 详情
export const getTitleDetail = tender_id =>
  fetchEndpoint('/debtConfirm/index/getTenderDetail', 'POST', { ...current_plat, tender_id })
