import { regionList } from '../../api/region'

// initial state
const state = {
  items: [],
  last_at: 0,
  last_act: 0
}

// mutations
const mutations = {
  saveRegionItems(state, items) {
    state.items = items
  },
  saveRegionAt(state, at) {
    state.last_at = at || 1
    state.last_act = Date.now()
  }
  // clearRegionItems(state) {
  //   state.item = []
  // }
}

// actions
const actions = {
  fetchRegions({ commit, state }) {
    if (Date.now() - state.last_act > 18e5) {
      // 30分钟内如果请求成功过就不可以再请求
      regionList(state.last_at).then(
        res => {
          if (res.regions) {
            commit('saveRegionItems', res.regions)
          }
          if (res.last_at) {
            commit('saveRegionAt', res.last_at)
          }
        },
        error => {}
      )
    }
  }
}

export default {
  state,
  mutations,
  actions
}
