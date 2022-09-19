import { fetchJavaEndPoint } from './WorkorderMessage'
import { fetchEndpoint } from '../server/network'

// 获取用户还款计划信息
export const getDetail = params =>
  fetchJavaEndPoint('/confirmation/firstp2pRepaymentPlan/repaymentPlan', 'GET', true, params)

// 验证交易密码是否有设置
// export const confirmAuth = params => fetchJavaEndPoint('/confirmation/firstp2pUser/card', 'GET', false, params)
export const getCode = _ => fetchJavaEndPoint('/confirmation/firstp2pUser/pwd/presence', 'GET', false, {})

// 发送验证码短信
export const getVelCode = params =>
  fetchEndpoint('/hh/hh.sms.send.logined', 'POST', {
    mobile: params.mobile,
    sms_code: params.sms_code
    // is_validate: params.is_validate,
    // is_vcode: params.is_vcode
  })
// 校验验证码
export const checkCode = params =>
  fetchEndpoint('/hh/hh.sms.validate', 'POST', {
    mobile: params.mobile,
    code: params.code
  })

// 验证交易密码
// export const checkPass = params =>
//   fetchJavaEndPoint('/confirmation/firstp2pDealLoad/transaction/password?transactionPassword=' + params, 'POST')

// 提交还款计划
export const submitPlan = params => fetchJavaEndPoint('/confirmation/firstp2pDealLoad/v1', 'POST', true, params)

// 首页是否弹框
// export const homeProp = _ => fetchJavaEndPoint('/confirmation/firstp2pRepaymentPlan/pop-ups', 'GET', false, {})

//首页弹框判断
export const getDialogStatus = () => fetchJavaEndPoint('/confirmation/firstp2pRepaymentPlan/pop-ups', 'GET')
// 还款计划详情
export const getRepaymentInfo = () => fetchJavaEndPoint('/confirmation/firstp2pRepaymentPlan/repaymentPlan', 'GET')
// 还款兑付入口判断
export const getRepaymenBtn = () => fetchJavaEndPoint('/confirmation/firstp2pDealLoad/repay/way', 'GET')

//个人中心-还款计划列表
export const getRepaymentlist = () => fetchJavaEndPoint('/confirmation/firstp2pRepaymentPlan/repaymentPlan/list', 'GET')
