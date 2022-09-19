import { fetchEndpoint } from '../server/network'

// 可兑换债权金额
export const bondGet = (productIds = []) =>
  fetchEndpoint('/hh/hh.bond.get', 'POST', {
    product: productIds
  })

/**
 * 返回债券列表
 *
 * @params                {Object}  params  参数
 * @params.page           {Number}  params  页数
 * @params.per_page       {Array}   params  每页数据量
 * @params.product        {Number}  params  商品ID
 * @params.debt_type      {Number}  params  债权类型： 1-省心 2-智选
 * @param.debt_id         {Number}  params  兑换债权前生成的临时订单的关联id
 */
export const bondList = params =>
  fetchEndpoint('/hh/hh.bond.list', 'POST', {
    product: params.product,
    debt_type: params.debt_type,
    page: params.page || 1,
    per_page: params.per_page || 10,
    debt_id: params.debt_id
  })

/**
 * 兑换积分接口
 *
 * @param                {Object}  params  参数
 * @param.account        {Number}  params  兑换金额
 * @param.bond_ids       {Array}   params  省心计划兑换列表
 * @param.bond_wise_ids  {Array}   params  智选系列兑换列表
 * @param.order          {Number}  params  兑换时的订单
 * @param.product        {Array}  params   兑换时的商品
 * @param.debt_id        {Number}  params  兑换债权前生成的临时订单的关联id
 */
export const bondChange = params =>
  fetchEndpoint('/hh/hh.bond.change', 'POST', {
    account: params.account,
    bond_ids: params.bond_ids,
    bond_wise_ids: params.bond_wise_ids,
    order: params.order,
    product: params.product,
    debt_id: params.debt_id
  })

// 返回订单状态(cookie取得 || 最后一笔)
export const bondResult = order =>
  fetchEndpoint('/hh/hh.bond.result', 'POST', {
    order: order || ''
  })

// 返回订单明细
export const bondDetail = log_id =>
  fetchEndpoint('/hh/hh.bond.detail', 'POST', {
    log_id: log_id
  })

// 查询用户持有的债权类型
export const getDebtType = () => fetchEndpoint('/hh/hh.bond.debtType', 'POST')
