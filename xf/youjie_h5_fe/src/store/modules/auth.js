import router from '../../router/index'
import utils from '../../util/util'
import { balanceGet } from '../../api/balance'
import { bondGet } from '../../api/bond'
import { getAuthStatus } from '../../api/auth'
import { userProfileGet } from '../../api/user'
import { getConfirmInfo } from '../../api/confirmation'

import { Indicator, MessageBox } from 'mint-ui'

let timer = null

// initial state
const state = {
  isOnline: false,
  token: null,
  user: null,
  isTokenInvalid: false,
  flag: false,
  platform: 0,
  openId: '',
  inviteCode: '', // 邀请注册 小店SN
  userBondInfo: {
    balance: 0,
    amount: 0,
    errorCode: -1 // 用户是否应该重新请求查询
  },
  wxAuthCheckInfo: {
    count: 0,
    hasDebtAuthentication: 0
  },
  seckillToken: null, //购买秒杀商品 通过限流凭证
  tmpPhone: '', // 手机号 + 验证码校验成功后记录的临时phone
  isHasEnterXiachePage: false // 是否进入过一键下车页
}

// mutations
const mutations = {
  signin(state, payload) {
    this.commit('saveUser', payload.user)
    this.commit('saveToken', payload.token)

    // 登录时读取购物车状态
    this.dispatch('fetchCartNumber')
    state.isTokenInvalid = false
  },
  signout(state) {
    this.commit('clearToken')
    this.commit('clearUser')

    // 登出时清空购物车状态
    this.commit('setCartNumber', 0)
    this.commit('unselectAddressItem')

    // 登出时清除授权状态
    this.commit('clearAuthStatus')

    // 登出时清空余额及债权信息
    this.commit('clearSharer')
    // 登出时清除openid
    this.commit('clearOpenid')
    // 登出时清楚我的小店信息
    // this.commit('clearShopInfos')
  },
  kickout(state) {
    // 登录信息失效时
    this.commit('clearToken')
    this.commit('clearUser')

    state.isTokenInvalid = true
  },
  saveUser(state, payload) {
    state.user = Object.assign({}, payload)

    state.platform = state.user.user_platform
    if (!state.platform) {
      // 非爱投资用户不需要授权
      this.commit('saveAuthData', { data: { status: true } })
    }
  },
  saveToken(state, payload) {
    state.isOnline = true
    state.token = payload
    this.commit('setCookieFromToken')
  },
  setCookieFromToken(state) {
    utils.setCookie('auth_token', state.token, { domain: utils.getDomainA.domain })
  },
  clearUser(state) {
    state.user = null
    state.platform = 0
  },
  clearToken(state) {
    state.isOnline = false
    state.token = null
    utils.removeCookie('auth_token', { domain: utils.getDomainA.domain })
  },
  changeFlag(state, value) {
    state.flag = value
  },
  saveOpenid(state, payload) {
    state.openId = payload
  },
  clearOpenid(state) {
    state.openId = ''
  },
  saveSharer(state, payload) {
    state.userBondInfo = { ...state.userBondInfo, ...payload }
  },
  clearSharer(state) {
    state.userBondInfo = {
      balance: 0,
      amount: 0,
      errorCode: -1 // 用户是否应该重新请求查询
    }
  },
  // 保存 特定商品(或店铺) 的阅读购买须知-》已知晓不再显示
  // saveCheckShouldKnowBeforeBy(state, payload) {
  //   const read_marker = state.user.read_marker || []
  //   if (read_marker.indexOf(payload) == -1) {
  //     state.user.read_marker = [...read_marker, payload]
  //   }
  // },
  saveInviteCode(state, payload) {
    state.inviteCode = payload
  },
  // 保存秒杀商品 限流凭证
  saveSeckillToken(state, value) {
    state.seckillToken = value
  },
  clearSeckillToken(state, value) {
    state.seckillToken = null
  },
  // 债权兑换积分协议 签署
  saveAgreementForExchangeToken(state, isAgree) {
    state.user.is_allow_exchange = isAgree
  },
  saveWxAuthCheckInfo(state, payload) {
    state.wxAuthCheckInfo = { ...state.wxAuthCheckInfo, ...payload }
  },
  saveWxAuthCheckCount(state, num) {
    num = parseInt(num)
    state.wxAuthCheckInfo.count += num
  },
  saveTmpPhone(state, phone) {
    state.tmpPhone = phone
  },
  saveHasEnterXiache(state, has) {
    state.isHasEnterXiachePage = has
  }
}

const actions = {
  // 同步用户信息
  fetchUserInfos({ commit, dispatch, state }, payload) {
    return new Promise((resolve, reject) => {
      userProfileGet().then(
        res => {
          commit('saveUser', res)
          resolve(res)
        },
        error => {
          reject('getUserInfos error')
        }
      )
    })
  },
  // 判断是否为分销客（有无积分余额且有无债权）
  fetchItzBondAuthCheck({ commit, dispatch, state }, payload) {
    return new Promise((resolve, reject) => {
      if (!state.isOnline) {
        commit('clearSharer')
        resolve({ userBondInfo: state.userBondInfo })
      }
      if (state.platform && (state.userBondInfo.errorCode || payload)) {
        Indicator.open()
        const pBalance = balanceGet()
        const pBond = bondGet()
        Promise.all([pBalance, pBond])
          .then(posts => {
            const balance = Number(posts[0].surplus)
            const amount = Number(posts[1])
            if (balance > 0 || amount > 0) {
              commit('saveSharer', { balance, amount, errorCode: 0 })
              resolve({ userBondInfo: { balance, amount, errorCode: 0 } })
              clearTimeout(timer)
              timer = setTimeout(() => {
                // 每过2分钟秒自动重新获取余额及债权
                commit('saveSharer', { errorCode: -1 })
              }, 120e3)
            } else {
              commit('clearSharer')
              resolve({ userBondInfo: state.userBondInfo })
            }
          })
          .catch(error => {
            resolve({ userBondInfo: state.userBondInfo })
            console.log(error)
          })
          .finally(() => {
            Indicator.close()
          })
      } else {
        resolve({ userBondInfo: state.userBondInfo })
      }
    })
  },

  fetchWxAuthCheck({ commit, dispatch, state }, payload = false) {
    if (!state.isOnline || state.wxAuthCheckInfo.count > 0) {
      return
    }
    return new Promise((resolve, reject) => {
      let p0 = getConfirmInfo()
      let p1 = getAuthStatus()
      Promise.all([p0, p1]).then(
        res => {
          let p = res[0]
          let o = res[1]
          if (o) {
            commit('saveWxAuthCheckInfo', o)
          }

          var notNeedConfirmation
          if (p) {
            notNeedConfirmation =
              Number(p.phHasConfirmWaitCapital) == Number(p.phAllConfirmWaitCapital) &&
              Number(p.zxHasConfirmWaitCapital) == Number(p.zxAllConfirmWaitCapital) &&
              Number(p.zxHasConfirmAccount) == Number(p.zxAllConfirmAccount)
          }

          if (state.wxAuthCheckInfo.hasDebtAuthentication == 0) {
            router.push({ name: 'AuthChooseOgnztion' })
            reject()
          } else if (state.wxAuthCheckInfo.count == 0 && !notNeedConfirmation && !payload) {
            router.push({ name: 'confirmation' })
            reject()
          } else {
            resolve()
          }
        },
        err => {
          reject(err)
        }
      )
    })
  }
}

export default {
  state,
  actions,
  mutations
}
