import { fetchJavaEndPoint } from './WorkorderMessage'

//用户银行卡状态
export const BankRecordGet = () => fetchJavaEndPoint('/confirmation/bankRecord/status', 'GET', false, {})

//查询银行信息
export const BankGet = params => fetchJavaEndPoint('/confirmation/bank/list', 'GET', false, params)

//银行查看支行信息
export const BranchGet = params => fetchJavaEndPoint('/confirmation/branch/list', 'GET', false, params)

//提交重置银行卡信息
export const SubmitPost = params => fetchJavaEndPoint('/confirmation/bankRecord/card', 'POST', false, params)

//解绑银行卡
export const UnbindPost = params => fetchJavaEndPoint('/confirmation/bankcard/unbind', 'GET', true, params)

//绑定银行卡
export const UpDataPost = params => fetchJavaEndPoint('/confirmation/bankcard/bind', 'POST', false, params)

//银行卡信息
export const UserBankGet = () => fetchJavaEndPoint('/confirmation/bankcard', 'GET', true, {})

//验密
export const UserPawPost = params => fetchJavaEndPoint('/confirmation/firstp2pUser/verify/pwd', 'GET', true, params)
