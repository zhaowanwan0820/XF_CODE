import { fetchGet } from "../server/network";

// 获取协议跳转链接
export const getAgreementRequest = () =>
  fetchGet("/user/XFUser/ContractAddress");
