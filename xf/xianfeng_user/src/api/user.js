import { fetchPost } from "../server/network";

// 【登录】发送验证码
export const getVelCode = params =>
  fetchPost("/user/XFUser/GetSMSFromLogin", params);

// 登录login
export const getLogin = params => fetchPost("/user/XFUser/Login", params);

// 个人信息
export const getPersonalInfo = params =>
  fetchPost("/user/XFUser/UserInfo", params);

// 积分兑换
export const getExchangeList = params =>
  fetchPost("/user/XFUser/exchangeList", params);
