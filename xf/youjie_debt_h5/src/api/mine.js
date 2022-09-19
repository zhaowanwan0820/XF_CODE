import { fetchEndpoint } from '../server/network'

// 个人信息
export const getUser = () => fetchEndpoint('/assetGarden/user/userInfo', 'POST', {})

//总资产
export const getTotal = () => fetchEndpoint('/assetGarden/userCenter/userTotalAccount', 'POST', {})

//获取手机号
export const getCode = params => fetchEndpoint('/apiService/phone/getSmsVcode', 'POST', params)

//设置交易密码
export const getPassword = params => fetchEndpoint('/assetGarden/user/setPayPassword', 'POST', params)

//修改交易密码
export const editPassword = params => fetchEndpoint('/assetGarden/user/modifyPayPassword', 'POST', params)

/**
 * @name 机构资产明细
 * @param platfom_id 必传 平台id
 * @param platform_user_id 必传 平台user_id
 */
export const getAccount = params => fetchEndpoint('/assetGarden/userCenter/platformUserAccount', 'POST', params)

/**
 * @name 已确权列表
 * @param platfom_id 必传 平台id
 * @param platform_user_id 必传 平台user_id
 * @param status 必传 0:全部 1:还款中 15:已结清
 * @param page 必传 页数
 * @param size 必传 条数
 */
export const getConfirmed = params => fetchEndpoint('/assetGarden/userCenter/confirmedList', 'POST', params)
// 获取用户确权机构列表
export const getPlatformConfirm = () => fetchEndpoint('/assetGarden/userCenter/UserPlatformConfirm', 'POST', {})
