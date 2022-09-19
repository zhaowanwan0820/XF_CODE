import { ENUM } from '../const/enum'

export const isOnline = state => {
  return  state.auth.isOnline
}

export const keepAlive = state => {
  return state.keepAlive.include
}

export const routerStack = state => {
  return state.keepAlive.routerStack
}

export const token = state => {
  return state.auth.token
}

export const isSetPassword = state => {
  return !!state.auth.user.is_set_pay_password
}

