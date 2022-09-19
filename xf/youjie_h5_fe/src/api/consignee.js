import { fetchEndpoint } from '../server/network'

/**
 * 收货人列表
 *
 * @param      {is_default}  params  是否光请求默认地址
 */
export const consigneeList = is_default =>
  fetchEndpoint('/hh/hh.consignee.list', 'POST', {
    is_default: is_default
  })

// 添加收货人
export const consigneeAdd = (name, mobile, tel, region, address) =>
  fetchEndpoint('/hh/hh.consignee.add', 'POST', {
    name: name, // 姓名
    mobile: mobile, // 移动电话
    tel: tel, // 座机
    region: region, // 区域id
    address: address // 详细地址
  })

// 删除收货人
export const consigneeDelete = consignee =>
  fetchEndpoint('/hh/hh.consignee.delete', 'POST', {
    consignee: consignee
  })

// 修改收货人
export const consigneeUpdate = (consignee, name, mobile, tel, region, address) =>
  fetchEndpoint('/hh/hh.consignee.update', 'POST', {
    consignee: consignee,
    name: name, // 姓名
    mobile: mobile, // 移动电话
    tel: tel, // 座机
    region: region, // 区域id
    address: address // 详细地址
  })

// 设置默认地址
export const consigneeSetdefault = consignee =>
  fetchEndpoint('/hh/hh.consignee.default', 'POST', {
    consignee: consignee
  })
