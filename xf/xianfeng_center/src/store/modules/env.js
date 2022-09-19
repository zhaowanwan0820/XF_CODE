import Vue from 'vue'
import { Toast } from 'vant'
/**
 * Initial state
 */
const state = {
  /**
   * 配置选项
   */
  env: {
    // 这边留了默认字段是为了让首屏页面数据绑定，不需要绑定的字段可以不列出来
    appid: 1,
    area_code: '',
    platform: '',
    amount: 0,
    agreement_status: 0,
    exchange_no: '',
    goodsInfo: '',
    goods_order_no: '',
    openid: '',
    redirect_url: '',
    debt_type: {},
    ph_total_amount: 0,

    // ...
  },
}

/**
 * Getters
 * @type {import('vuex/types').GetterTree<typeof state}
 */
const getters = {
  /**
   * 获取配置选项
   */
  env: state => state.env,
  isAgreement: state => state.env.agreement_status,
  redirectUrl: state => decodeURIComponent(state.env.redirect_url),
  platformName: state => state.env.platform,
  needDebt: state => (state.env.ph_total_amount ? state.env.ph_total_amount : state.env.amount),
  setPassword: state => state.env.pay_password_status,
  debtType: state => state.env.debt_type,
  exchangeNo: state => state.env.exchange_no,
  ph_total_amount: state => state.env.ph_total_amount,
}

/**
 * Mutations
 * @type {import('vuex/types').MutationTree<typeof state}
 */
const mutations = {
  /**
   * 设置配置选项
   */
  CHANGE_ENV: (state, env) => {
    Object.assign(state.env, env)
  },
}

/**
 * Actions
 * @type {import('vuex/types').ActionTree<typeof state}
 */
const actions = {
  async getPlatform({ commit }) {
    await Vue.services.exchange.getPlatformName().then(res => {
      const { data } = res
      if (data.code == 0) {
        commit('CHANGE_ENV', { platform: data.data.platform })
      } else if (data.info) {
        Toast.fail(data.info)
      }
    })
  },
  async changeEnv({ commit, state, dispatch }, env) {
    const oldAppid = state.env.appid
    commit('CHANGE_ENV', env)
    if (env.appid && env.appid !== oldAppid) {
      await dispatch('getPlatform')
    }
  },
}

// Export module
export default { state, getters, mutations, actions }
