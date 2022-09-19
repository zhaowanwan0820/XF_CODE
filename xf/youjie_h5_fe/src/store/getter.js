import { ENUM } from '../const/enum'

export const isOnline = state => {
  return state.auth.isOnline
}

export const token = state => {
  return state.auth.token
}

export const getUser = state => {
  return state.auth.user
}

export const authStatus = state => {
  return state.itouzi.authStatus
}

export const isAgree = state => {
  return state.itouzi.auth_agreement
}

export const authStep = state => {
  return state.itouzi.authStep
}

export const inviteCode = state => {
  return state.auth.inviteCode
}

export const keepAlive = state => {
  return state.keepAlive.include
}

export const routerStack = state => {
  return state.keepAlive.routerStack
}

export const isXiache = state => {
  return state.auth.user && state.auth.user.service_list.length && state.auth.user.service_list.indexOf(4) != -1
}

// A类用户：账户有积分余额或者有爱投资在投债权
// 当前用户是否为A类用户
export const isHbUser = state => {
  if (!state.auth.isOnline) {
    return false
  }
  // 账户积分余额
  if (Number(state.auth.user.surplus) > 0) {
    return true
  }
  // 拥有积分的商家用户
  if (state.auth.user.suppliers_id != 0) {
    return true
  }
  // 1565150400000：2019-08-07 12:00:00 开始无积分用户被视为非A类用户
  // if (state.app.systemTime >= ENUM.TIMESTAMP2019090712) {
  //   return false
  // }
  // 是itz平台用户且itz可兑换债权大于0
  if (state.auth.platform == 1 && Number(state.bond.currentBond) > 0) {
    return true
  }
  return false
}
