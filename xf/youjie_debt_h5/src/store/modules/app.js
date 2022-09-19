import utils from '../../util/util'
import { MessageBox } from 'mint-ui'

// initial state
const state = {
  requestLoading: 0,
  isHHAppTop: false, // 在换换商城app中是否到达页面顶部
  localTimeClockOffset: 0, // 本地时间相对服务器时间的时钟偏移（例如本地时间比服务器时间慢5000ms 那么该值为5000）；

  isHHAppBridgeReady: false, // HHApp bridge 是否已经ready for H5
  counterForTabRefresh: 1 // App 调用 javascript 内定义的定义的refresh方法 更新H5页面的数据
}

// mutations
const mutations = {
  SET_LOADING: (state, status) => {
    // error 的时候直接重置
    if (status === 0) {
      state.requestLoading = 0
      return
    }
    state.requestLoading = status ? ++state.requestLoading : --state.requestLoading
  },
  SET_IS_TOP: (state, isTop) => {
    state.isHHAppTop = isTop
  },
  SET_LOCALTIME_CLOCK_OFFSET: (state, offsetTime) => {
    state.localTimeClockOffset = offsetTime
  },
  SET_HHAPP_IS_READY: (state, isReady) => {
    state.isHHAppBridgeReady = isReady
  },
  // 在对象上添加新属性时，使用 Vue.set(obj, 'newProp', 123) or 进行对象替换 state.obj = { ...state.obj, newProp: 123 }
  // 见：https://vuex.vuejs.org/zh/guide/mutations.html
  UPDATE_TAB_COUNTER: (state, tabIndex) => {
    ++state.counterForTabRefresh
  }
}

// actions
const actions = {
  SetLoading({ commit }, status) {
    commit('SET_LOADING', status)
  },
  updateClockOffset({ commit, state }) {
    // 更新本地时间相对服务器时间的时钟偏移
    return new Promise((resolve, reject) => {
      utils.getNowTime().then(({ time }) => {
        const local_time = new Date().getTime()
        commit('SET_LOCALTIME_CLOCK_OFFSET', time - local_time)
        resolve()
      })
    })
  }
}

export default {
  state,
  mutations,
  actions
}
