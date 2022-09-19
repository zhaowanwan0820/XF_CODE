import { fetchPost } from "../server/network";

// 签署协议
export const seriveAgreementRequest = params =>
  fetchPost("/user/XFUser/signContract", {
    type: params.type
  });
