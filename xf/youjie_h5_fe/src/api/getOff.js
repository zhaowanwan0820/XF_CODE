import { fetchEndpoint } from '../server/network'

//获取活动商品列表
export const getGoodsList = () => fetchEndpoint('/activity/wx.gift.list', 'POST', {})

/**
 * 放弃礼包
 * @param type 1积分 2债权
 * */
export const setGiveUp = params => fetchEndpoint('/activity/wx.gift.waiver', 'POST', params)

/**
 * 商品详情
 * @param product 商品ID
 * */
export const getGoodsInfo = params => fetchEndpoint('/activity/wx.gift.get', 'POST', params)

/**
 * 添加行为接口
 *  @param event_code	{string}	事件code
 *  @param event_name	{string}	事件名称
 * */
export const postUserAction = params => fetchEndpoint('/hh/hh.user.action.add', 'POST', params)

/**
 * 同意活动 不在退回债权
 * */
export const postDebt = params => fetchEndpoint('/activity/wx.gift.agree', 'POST', params)