// 我的认购tab
export const SUBSCIPTION_TITLE_LIST = ['全部', '待付款', '待卖方收款', '交易成功', '交易取消']

// 我的认购tab对应状态码 -> 认购状态：默认10-全部 1-待付款 2-交易成功 3-交易取消 4-待卖方收款
export const SUBSCIPTION_TITLE_CODE = [10, 1, 4, 2, 3]

// 我的转出tab
export const TRANSFER_TITLE_LIST = ['转让中', '待买方付款', '待收款', '交易成功', '交易取消']

// 我的转出tab对应状态码 -> 1-转让中，2-交易成功，3-交易取消，5-待买方付款，6-待收款
export const TRANSFER_TITLE_CODE = [1, 5, 6, 2, 3]

// 我的认购订单状态
export const SUBSCRIPTION_STATUS = [
  { id: 1, name: '待付款' },
  { id: 2, name: '交易成功' },
  { id: 3, name: '交易取消' },
  { id: 4, name: '交易取消' },
  { id: 5, name: '交易取消' },
  { id: 6, name: '待卖方确认' },
  { id: 7, name: '待卖方确认(客服介入)' }
]

// 我的转让订单状态
export const TRANSFER_STATUS = [
  { id: 1, name: '转让中' },
  { id: 2, name: '交易成功' },
  { id: 3, name: '交易取消' },
  { id: 4, name: '交易取消' },
  { id: 5, name: '待买方付款' },
  { id: 6, name: '待收款' }
]
