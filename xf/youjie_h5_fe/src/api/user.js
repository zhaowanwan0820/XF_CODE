import { fetchEndpoint } from '../server/network'
import { fetchJavaEndPoint } from './WorkorderMessage'

import store from '../store/index'

// 获取用户资料
export const userProfileGet = () => fetchEndpoint('/hh/hh.user.profile.get', 'POST')

// 【登录】发送验证码
export const getVelCode = (mobile, is_voice, wx_bind = 0) =>
  fetchEndpoint('/hh/hh.send.smscode', 'POST', {
    mobile: mobile,
    is_voice: is_voice, //验证码种类 0短信验证码 1语音验证码
    wx_bind: wx_bind // 是否三方登录 绑定手机号发送验证码
  })

// 登录login
export const getLogin = ({ mobile, valicode, invite_code, openid }) =>
  fetchEndpoint('/hh/hh.reg.login', 'POST', {
    mobile: mobile,
    valicode: valicode,
    invite_code: invite_code || store.getters.inviteCode, //邀请注册 小店SN
    openid: openid // 三方登录时 绑定手机号的 openid
  })

/**
 * 充值积分
 *
 * @param   {String}  params.cardSec1  卡密1
 * @param    {String}  params.cardSec2  卡密2
 */
export const rechargeHB = params =>
  fetchEndpoint('/hh/hh.user.card.change', 'POST', {
    cardSec1: params.cardSec1,
    cardSec2: params.cardSec2
  })

/**
 * 获取最近失败次数
 */
export const getRechargeFailedTime = () => fetchEndpoint('/hh/hh.user.card.info', 'POST')

/**
 * 保存用户 同意 债权兑换积分 协议
 */
export const saveAgreementForExchangeToken = () => fetchEndpoint('/hh/hh.save.agreement', 'POST')

//获取用户债权信息
export const subjectGet = () =>
  fetchJavaEndPoint('/confirmation/firstp2pDealLoad/subject/info', 'GET', true, {})

// 【设置交易密码】step1：获取短信验证码&校验手机号
export const apiGetVelCode = phone =>
  fetchEndpoint('/hh/hh.sms.send.logined', 'POST', {
    mobile: phone,
    sms_code: 'wx_vcode' // 短信模板
  })

// 【设置交易密码】step1：提交手机号&短信验证码
export const apiValidatePhoneCode = ({ mobile, valicode }) =>
  fetchEndpoint('/hh/hh.sms.validate', 'POST', {
    mobile: mobile,
    code: valicode
  })

/**
 * 【设置交易密码】step2：提交新设置的交易密码
 *
 * @param   {String}  params.password 密码
 * @param   {String}  params.token 上一步验证码校验接口成功后返回的token
 */
export const apiSetNewTransitionPwd = ({ password, token, mobile }) =>
  fetchJavaEndPoint('/confirmation/firstp2pUser/transaction/password', 'POST', false, {
    transactionPassword: password,
    token: token,
    mobile: mobile
  })

// 【修改交易密码】提交旧的交易密码和新设置的交易密码
export const apiChangeTransitionPwd = ({ oldPassword, newPassword }) =>
  fetchJavaEndPoint('/confirmation/firstp2pUser/transaction/password', 'PUT', false, {
    oldPassword: oldPassword,
    newPassword: newPassword
  })

// 获取是否设置交易密码
export const getIsSetPassword = () => fetchJavaEndPoint('/confirmation/firstp2pUser/pwd/presence', 'GET')
