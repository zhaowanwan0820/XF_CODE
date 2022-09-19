import { fetchPost } from "../server/network";

// 设置交易密码
export const setPassWordRequest = params =>
  fetchPost("/user/XFUser/SetPassWord", {
    new_password: params.password1,
    token: params.token
  });

// 修改交易密码
export const changePWRequest = params =>
  fetchPost("/user/XFUser/EditPassWord", {
    old_password: params.old_password,
    new_password: params.new_password
  });

//找回/重置交易密码
export const findPWRequest = params =>
  fetchPost("/user/XFUser/ResetPassword", {
    new_password: params.new_password,
    code: Number(params.code)
  });

//获取短信验证码
export const getMsgRequest = params =>
  fetchPost("/user/XFUser/GetSMSFromResetPassword", {
    number: params.number
  });

//校验外部设置交易密码地址有效性
export const checkSetPwdUrlRequest = params =>
  fetchPost("/user/XFUser/SetPassWordPage", {
    token: params.token
  });

//校验外部校验交易密码地址有效性
export const checkTradersPwdUrlRequest = params =>
  fetchPost("/user/XFUser/CheckPassWordPage", {
    token: params.token
  });

//外部校验交易密码
export const checkTradersPwdRequest = params =>
  fetchPost("/user/XFUser/CheckPassWord", {
    token: params.token,
    password: params.password
  });
