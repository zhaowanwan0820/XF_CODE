import { fetchJavaEndPoint } from './WorkorderMessage'

// 未读消息
export const unReadMsg = () => fetchJavaEndPoint('workOrderMessage/unRead', 'GET', false, {})

// 获取未读消息列表
export const unReadMsgList = () => fetchJavaEndPoint('workOrderMessage/list', 'GET', false, {})

// 查询工单详情
export const workorderDetail = number => fetchJavaEndPoint('workOrder', 'GET', true, number)

// 未读消息设置为已读
export const setReadMsg = id => fetchJavaEndPoint('workOrderMessage/', 'PUT', true, id)
