// 订单接口，包含我的转让 && 我的认购
import { fetchEndpoint } from '../server/network'

/******************************************** 我的认购 *******************************************/

// 我的认购列表
export const getSubscriptionList = params => fetchEndpoint('/Launch/DebtGarden/SubscriptionOwn', 'POST', params)

/**
 * @name  认购详情
 * @param products 必传 查询类型：1-尊享，2-普惠供应链
 * @param debt_tender_id 必传 债转记录ID
 */
export const getTenderDetail = params => fetchEndpoint('/Launch/DebtGarden/SubscriptionDetails', 'POST', params)

// 认购-取消订单
export const cancelOrder = params => fetchEndpoint('/Launch/DebtGarden/CancelTenderDebt', 'POST', params)

// 认购-收款人信息
export const PayeeInfo = params => fetchEndpoint('/Launch/DebtGarden/PayeeInfo', 'POST', params)

/******************************************** 我的转让 *******************************************/

// 我的转让列表
export const getTransferDebt = params => fetchEndpoint('/Debt/Wx/DebtList', 'POST', params)

/**
 * @name 债权转让详情
 * @param products 必传 查询类型：1-尊享，2-普惠供应链
 * @param debt_id 必传 债转记录ID
 */
export const getSubDetail = params => fetchEndpoint('/Debt/Wx/DebtInfo', 'POST', params)

// 转让-撤销转让
export const CancelDebt = params => fetchEndpoint('/Launch/DebtGarden/CancelDebt', 'POST', params)

// 确认收款
export const ConfirmGetMoney = params => fetchEndpoint('/Launch/DebtGarden/ConfirmReceipt', 'POST', params)

// 资金未到账
export const NotGetMoney = params => fetchEndpoint('/Debt/Wx/DebtCustomerService', 'POST', params)
