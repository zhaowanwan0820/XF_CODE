import { fetchEndpoint } from '../server/network'

// 获取债权详情
export const getDebtdetails = params => fetchEndpoint('/Launch/DebtGarden/TransferDetails', 'POST', params)
// 债权认购
export const debtSubscription = params => fetchEndpoint('/Launch/DebtGarden/TransferBuy', 'POST', params)
// 获取认购结果
export const getSubscribeResult = params => fetchEndpoint('/Launch/index/AmcTrView', 'POST', params)
// 校验认购码
export const checkCode = params => fetchEndpoint('/Launch/DebtGarden/CheckBuyCode', 'POST', params)
// 债转列表
export const getDebtMarketList = ({ type, limit, page, order, products, name, field }) =>
  fetchEndpoint('/Launch/DebtGarden/DebtList', 'POST', {
    type, // 查询类型 1:表示债转市场 2:认购转让中的债权
    limit, // 返回条数限制（默认10 ，最大传入100）
    page, // 当前页码（默认1）
    order, // 转让折扣排序 1：正序2：倒序（默认2）
    products, // 1：尊享 2：普惠供应链（type=2时必传、type=1时非必传）
    name, // 项目名称
    field // 1：综合排序 2：转让折扣 （默认1）
  })
