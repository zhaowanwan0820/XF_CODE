import { fetchPost ,fetchGet} from "../server/network";
import {phoneModel} from "../util/device";

// 【登录】发送验证码
export const getVelCode = params =>
  fetchPost("/user/XFUser/GetSMSFromLogin", params);

// 登录login
export const getLogin = params => {
  return fetchPost("/user/XFUser/Login", {
    ...params,
    add_device: phoneModel(),
    add_browser: window.__user_browser
  });
}

// 个人信息
export const getPersonalInfo = params =>
  fetchPost("/user/XFUser/UserInfo", params);

// 积分兑换
export const getExchangeList = params =>
  fetchPost("/user/XFUser/exchangeList", params);

//修改手机号 新手机号获取验证码
export const getNewPhoneCode = params =>
  fetchPost("/user/XFUser/GetSMSFromMobile",params);

//提交手机号修改申请（旧手机号不可用）
export const mobileChangeApply = params =>
  fetchPost("/user/XFUser/MobileChangeApply",params);

//提交手机号修改申请（旧手机号可用）
export const MobileChange = params =>
  fetchPost("/user/XFUser/MobileChange",params);

//判断手机号是否在审核中
export const checkUserInfo = params =>
fetchPost("/user/XFUser/CheckUserInfo",params)
//判断用户是否提交交易所注册申请
export const checkJYSUserInfo = params =>
fetchPost("/user/XFJYS/CheckUserInfo",params)
//交易所注册申请 新手机号获取验证码
export const getJYSNewPhoneCode = params =>
  fetchPost("/user/XFUser/AddJYSUserInfoGetSMS",params);
//提交交易所注册申请
export const createJYSUser = params =>
  fetchPost("/user/XFJYS/AddJYSUserInfo",params);
// 【借款人登录】获取图形验证码
export const getCaptcha = params =>
fetchGet("/apiService/Captcha/Login", params);
// 借款人登录login
export const cardLogin = params => fetchPost("/user/Borrower/CardLogin", params);
