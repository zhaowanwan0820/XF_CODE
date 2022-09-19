import utils from '../../util/util'

// initial state
const state = {
  banners: [],
  dataLast30: [],
  dataByDay: [],
  dataPlanList: [], // 求购计划
  dataDebtList: [] // 全部债权
}

// mutations
const mutations = {
  saveBanners: (state, banners) => {
    state.banners = banners
  },
  saveDataLast30: (state, data) => {
    state.dataLast30 = data
  },
  saveDataByDay: (state, data) => {
    state.dataByDay = data
  },
  saveDataPlanList: (state, data) => {
    state.dataPlanList = data
  },
  saveHomeDebtList: (state, data) => {
    state.dataDebtList = data
  }
}

// actions
const actions = {}

export default {
  state,
  mutations,
  actions
}
