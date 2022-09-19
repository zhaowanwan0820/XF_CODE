import Vue from 'vue'
// initial state
const state = {
  debtInfo: {}, //发起债转全部信息
  debtList: [], //发起债转列表
  transferBuyResult: {} //发起认购结果
}

// mutations
const mutations = {
  saveDebtInfo(state, payload) {
    state.debtInfo = payload
  },
  clearDebtInfo(state) {
    state.debtInfo = {}
  },
  saveDebtList(state, payload) {
    let list = payload || []
    list.length &&
      list.forEach(item => {
        Vue.set(item, 'checked', false)
        Vue.set(item, 'warning', false)
        Vue.set(item, 'money', Number(item.wait_capital))
      })
    state.debtList = [...state.debtList, ...list]
  },
  clearDebtList(state) {
    state.debtList = []
  },
  checkAll(state, payload) {
    state.debtList.forEach(item => {
      if (!Number(item.debt_status)) item.checked = payload
    })
  },
  saveTransferBuyResult(state, payload) {
    state.transferBuyResult = payload
  },
  clearTransferBuyResult(state) {
    state.transferBuyResult = {}
  }
}

const getters = {
  // 已选择列表
  checkedList(state, getters) {
    return state.debtList.filter(item => {
      return item.checked
    })
  },
  // 待还本金总和
  waitCapitalAll(state, getters) {
    let w_c = 0
    getters.checkedList.forEach(item => {
      w_c += Number(item.money)
    })
    return w_c
  },
  // 所选项 输入框内是否有违规输入
  isWarning(state, getters) {
    let i_w = getters.checkedList.some(item => {
      return item.warning
    })
    return i_w
  },
  // 可选列表（有债转的项目不能进行操作）
  effectiveList(state, getters) {
    return state.debtList.filter(item => {
      return !Number(item.debt_status)
    })
  },
  // 是否全选
  isCheckedAllByUser(state, getters) {
    let c_b_u = getters.effectiveList.every(item => {
      return item.checked
    })
    return c_b_u
  }
}

// actions
const actions = {}

export default {
  state,
  mutations,
  actions,
  getters
}
