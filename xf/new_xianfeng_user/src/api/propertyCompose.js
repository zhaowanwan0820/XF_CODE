import { fetchGet, fetchPost, fetchGetBlob } from "../server/network";

// 获取资产构成尊享
export const getPropertyRequest = () => fetchGet("/user/XFUser/UserInfo");

// 获取资产构成投资记录
export const getPropertyDetailRequest = params =>
  fetchPost("/user/XFUser/loanList", {
    type: params.type,
    status: params.status,
    limit: params.limit,
    page: params.page
  });

//出借详情
export const getLoanDetailRequest = params =>
  fetchPost("/user/XFUser/DealLoadInfo", {
    id: params.id,
    platform_id: params.platform_id
  });

//查看合同列表和详情
export const getAgreementRequest = params =>
  fetchPost("/user/userContract/ContractList", {
    deal_load_id: params.id,
    p: params.platform_id
    // deal_load_id: 83480805,
    // p: 4
  });

//打开合同
export const getAgreementDetailRequest = (url, params) =>
  fetchGetBlob(url, {
    // deal_load_id: params.id,s
    // p: params.platform_id
    // deal_load_id: 83480937,
    // p: 4,
    // order: 0
  });
  //投资记录审核 - 详情
export const getDealLoadAuditInfoRequest = ( params) =>
  fetchPost('/user/XFUser/DealLoadAuditInfo', {
    platform_id:params.platform_id,
    id:params.id
  });
//投资记录审核 - 上传
export const getDealLoadAuditUploadRequest = ( params) =>
  fetchPost('/user/XFUser/DealLoadAuditUpload', {
    platform_id:params.platform_id,
    id:params.id,
    number:params.number,
    picture:params.picture
  });

//还款凭证 - 详情
export const getBorrowerVoucherInfoRequest = () =>fetchGet('/user/Borrower/RepayVoucherInfo');
//还款凭证 - 上传
export const borrowerVoucherUploadRequest = ( params) =>
fetchPost('/user/Borrower/RepayVoucherUpload', {
  picture:params.picture
});
