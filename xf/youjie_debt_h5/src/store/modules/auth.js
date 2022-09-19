import utils from '../../util/util'
import { getConfirmInfo } from '../../api/auth'
import { getRiskTestResult, getIsSetPassword } from '../../api/user'

let timer = null

// initial state
const state = {
  isOnline: false,
  token: null,
  user: {},
  authAgreement: false, // 是否已同意 债权市场服务协议
  authPlatInfo: {}, //身份验证选择的的平台
  platInfo: null, // 平台信息
  confirmList: [], //确权项目列表
  risk_level: null // 风险测评结果
}

// mutations
const mutations = {
  signin(state, payload) {
    this.commit('removeuser')
    if (payload.user) this.commit('saveUser', payload.user)
    if (payload.token) this.commit('saveToken', payload.token)
  },
  signout(state) {
    this.commit('clearToken')
    this.commit('clearUser')
  },
  kickout(state) {
    // 登录信息失效时
    this.commit('clearToken')
    this.commit('clearUser')
  },
  saveUser(state, payload) {
    state.user = Object.assign({}, payload)
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
  },
  clearToken(state) {
    state.isOnline = false
    state.token = null
    utils.removeCookie('auth_token', { domain: utils.getDomainA.domain })
  },
  saveDebtAgreement(state, payload) {
    state.authAgreement = payload
  },
  saveAuthPlateInfo(state, payload) {
    state.authPlatInfo = payload
  },
  savePlateInfo(state, payload) {
    payload.confirm_status = !!Number(payload.confirm_status)
    payload.agree_status = !!Number(payload.agree_status)
    state.platInfo = payload
  },
  removeuser(state) {
    state.isOnline = false
    state.token = null
    state.user = {}
    state.platInfo = null
    state.authPlatInfo = {}
    state.confirmList = null
  },
  saveConfirmList(state, payload) {
    state.confirmList = payload
  },
  updatedUserPwdStatus(state, payload) {
    state.user.is_set_pay_password = payload
  },
  saveRisk(state, payload) {
    state.risk_level = payload
  }
}

const getters = {
  hasAuth(state, getters) {
    // true 已通过身份认证
    return state.platInfo && !!state.platInfo.authorization_status
  },
  unNeedConfirm(state, getters) {
    // true 已全部确权 或 用户原本无债权（即债权总额为0）=> 无需再去确权页面
    return state.confirmList && state.confirmList.length && state.confirmList.every(item => item.confirm == item.total)
  },
  isDoneRiskTest(state, getters) {
    // 是否已完成风险测评
    return state.risk_level && state.risk_level.level_name
  }
}

const actions = {
  fetchHasConfirmList({ commit, dispatch, state }) {
    let data = {
      platform_id: state.platInfo.platform_id,
      user_id: state.platInfo.user_id,
      platform_user_id: state.platInfo.platform_user_id
    }
    getConfirmInfo(data).then(res => {
      commit('saveConfirmList', res.data.project)
    })
  },
  fetchRiskResult({ commit, dispatch, state }) {
    // 更新风险测评结果
    return new Promise((resolve, reject) => {
      getRiskTestResult()
        .then(res => {
          commit('saveRisk', {
            level_name: res.data.risk_level,
            level_id: res.data.level_risk_id
          })
          // 保存债权协议签署状态
          commit('saveDebtAgreement', res.data.agree_status)
          resolve(res)
        })
        .catch(err => {
          reject(err)
        })
    })
  },
  updatePwdStatus({ commit, dispatch, state }) {
    // 更新交易密码设置状态
    return new Promise((resolve, reject) => {
      getIsSetPassword()
        .then(res => {
          commit('updatedUserPwdStatus', !res.transactionPassword || '0' == res.transactionPassword ? false : true)
          resolve(res)
        })
        .catch(err => {
          reject(err)
        })
    })
  }
}

export default {
  state,
  actions,
  getters,
  mutations
}
