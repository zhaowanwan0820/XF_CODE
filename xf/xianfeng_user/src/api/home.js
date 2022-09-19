import { fetchPost, fetchGet } from "../server/network";

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
