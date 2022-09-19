import { fetchPost, fetchGet } from "../server/network";

// 新消息&新意见反馈回复状态
export const getNew = () => fetchGet("/user/XFUser/NewMessageFeedback");

// 消息列表
export const getMessageList = params =>
  fetchPost("/user/XFUser/MessageList", params);

// 消息详情
export const getMessageInfo = params =>
  fetchPost("/user/XFUser/MessageInfo", params);

// 意见反馈列表
export const getFeedback = params =>
  fetchPost("/user/XFUser/FeedbackList", params);

// 意见反馈详情
export const getFeedbackInfo = params =>
  fetchPost("/user/XFUser/FeedbackInfo", params);

// 公告列表
export const getNoticeList = params =>
  fetchPost("/user/XFUser/NoticeList ", params);

// 公告列表
export const getNoticeInfo = params =>
  fetchPost("/user/XFUser/NoticeInfo ", params);
