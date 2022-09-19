import {fetchPost, fetchGet} from "../server/network";
import {phoneModel} from "../util/device";

// 获取个人基础信息
export const getUserInfoRequest = params =>
  fetchPost("/user/XFUser/UserInfo", {
    type: params.type
  });

//提交意见反馈
export const feedbackRequest = params =>
  fetchPost("/user/XFUser/AddFeedback", {
    content: params.content
  });

// 公告列表
export const getNoticeList = params =>
  fetchPost("/user/XFUser/NoticeList ", {
    limit: params.limit,
    page: params.page
  });

//是否有新消息&新意见反馈回复状态
export const getNewsRequest = () => fetchGet("/user/XFUser/NewMessageFeedback");

//去专区的code
export const getUserAuthCode = () => fetchGet("/user/XFUser/GetUserAuthCode");


// 获取个人实名认证信息
export const getVerifiedInfoRequest = ({return_url = location.href}) =>
  fetchPost("/user/XFUser/UserInfo", {
    return_url,
    add_device: phoneModel(),
    add_browser: window.__user_browser
  });
/**
 * 置换接口 接口文档：http://39.106.189.58:8090/pages/viewpage.action?pageId=101941365
 * @param displace_type
 * 置换方式：2-用户点击确认签约，3-用户点击其他签约
 * @returns {Promise<unknown>}
 */
export const fetchDisplace = ({displace_type}) => fetchPost(`/user/XFUser/displace`, {
  displace_type,
  add_device: phoneModel(),
  add_browser: window.__user_browser
})
/**
 * 证件照上传 接口文档：http://39.106.189.58:8090/pages/viewpage.action?pageId=101941365#id-%E4%B8%87%E5%B3%BB%E7%BD%AE%E6%8D%A2-%E5%85%88%E9%94%8B%E7%94%A8%E6%88%B7%E4%B8%AD%E5%BF%83%E6%8E%A5%E5%8F%A3%E6%96%87%E6%A1%A3-1%E3%80%81%E7%94%A8%E6%88%B7%E4%B8%AD%E5%BF%83%E9%A6%96%E9%A1%B5-%E6%9F%A5%E8%AF%A2%E7%94%A8%E6%88%B7%E7%BD%AE%E6%8D%A2%E4%BF%A1%E6%81%AF(%E5%8E%9F%E6%9C%89%E6%8E%A5%E5%8F%A3%E6%96%B0%E5%A2%9E%E4%BC%A0%E5%8F%82%E3%80%81%E8%BF%94%E5%9B%9E%E9%A1%B9)
 * @param params
 * @returns {Promise<unknown>}
 */
export const fetchUploadIdCard = params => fetchPost(`/user/XFUser/uploadIdPhoto`, params)
