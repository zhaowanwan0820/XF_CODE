import { MAXLENGTH } from '../../page/confirmation/static.js'
const state = {
  projectInfo: null,
  projectList: [], //未确权列表
  hasConfirm: false, //是否已同意确权协议
  isShowConfirmPopup: false //展示授权协议弹窗
}

// mutations
const mutations = {
  saveProjectList(state, payload) {
    payload.forEach(item => {
      item.checked = false
    })
    state.projectList = [...state.projectList, ...payload]
  },
  clearProjectList(state, payload) {
    state.projectList = []
  },
  saveProjectInfo(state, payload) {
    state.projectInfo = payload
  },
  clearProjectInfo(state, payload) {
    state.projectInfo = null
  },
  // 批量 选中or取消
  setAllChecked(state, payload) {
    if (state.projectList < MAXLENGTH || !payload) {
      // 小于50条或批量取消，直接操作整个数组
      state.projectList.forEach(item => {
        item.checked = payload
      })
    } else {
      // 大于50条且选中
      state.projectList.forEach((item, index) => {
        if (index >= MAXLENGTH) return false
        item.checked = payload
      })
    }
  },
  setHasConfirm(state, payload) {
    state.hasConfirm = payload
  },
  setShowConfirmPopup(state, payload) {
    state.isShowConfirmPopup = payload
  }
}

const getters = {
  // 已选中列表
  hasCheckedList(state, getters) {
    return state.projectList.filter(item => {
      return item.checked
    })
  },
  titleListLength(state, getters) {
    return state.projectList.length || 0
  },
  hasCheckedLength(state, getters) {
    return getters.hasCheckedList.length || 0
  },
  // 当前是否已经全选
  isCheckedAll(state, getters) {
    return (
      getters.hasCheckedList.length &&
      (getters.hasCheckedList.length === state.projectList.length || getters.hasCheckedList.length === MAXLENGTH)
    )
  },
  // 已选中债权金额
  hasCheckProjectMoney(state, getters) {
    return getters.hasCheckedList.reduce((total, current) => total + Number(current.surplus_capital), 0)
  }
}

export default {
  state,
  getters,
  mutations
}
