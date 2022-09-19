import { fetchEndpoint } from '../server/network'

// 发起转让列表
export const getDebtList = params =>
  fetchEndpoint('/Launch/Index/transferInlist', 'POST', {
    pur_id: params //求购计划id
  })

// 发起认购
export const transferbuy = params => fetchEndpoint('/Launch/Index/transferbuy', 'POST', params)

//yiu新增
/** 项目详情接口(发布转让)
 * deal_load_id 否	int 投资记录ID（status=1必传）
 * products	是	int	所属产品：1尊享债转 2普惠供应链
 * debt_id	否	int	债权记录ID（status=2必传）
 * status	否	int	状态1:发布 2:重新发布
 * */
export const getProInfo = params => fetchEndpoint('/Launch/DebtGarden/DebtDetails', 'POST', params)

/**
 * 债权发布接口
 * @param deal_load_id	{int}	投资记录ID
 * @param products	{int}	所属产品：1尊享债转 2普惠供应链
 * @param money	{float}	转让金额
 * @param discount	{int}	转让折扣(取值范围0.01至10)
 * @param effect_days	{int}	有效期(10，20，30)
 * @param bankcard_id	{int}	银行卡ID
 * @param transaction_password	{string}	支付密码
 * @param is_orient	{int}	定向转让(1是 2不是) //非必传默认2
 * */
export const setRelease = params => fetchEndpoint('/Launch/DebtGarden/ProjectTransfer', 'POST', params)

/**
 * 个人信息接口
 * @param debt_tender_id	认购债权记录ID
 * @param products		所属产品：1尊享债转 2普惠供应链
 * */
export const getUserInfo = params => fetchEndpoint('/Launch/DebtGarden/PayeeInfo', 'POST', params)

/**
 * 转账付款接口
 * @param debt_tender_id	{int} 认购债权记录ID
 * @param products		{int}	所属产品：1尊享债转 2普惠供应链
 * @param payer_name		{string}	付款人姓名
 * @param account		{string}	付款金额
 * @param payer_bankzone		{string}	付款人开户行
 * @param payer_bankcard		{string}	付款人银行卡号
 * @param pay_voucher	{string}	付款凭证
 * */
export const setCertificate = params => fetchEndpoint('/Launch/DebtGarden/TransferPayment', 'POST', params)

/**
 * 重新发布
 * debt_id	是	int	债转投资记录ID
 * products	是	int	所属产品：1尊享债转 2普惠供应链
 * transaction_password	是	string	支付密码
 * */
export const setRepublish = params => fetchEndpoint('/Launch/DebtGarden/AgainProjectTransfer', 'POST', params)