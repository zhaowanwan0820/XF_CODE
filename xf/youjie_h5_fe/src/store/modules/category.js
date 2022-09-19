import { categoryList } from '../../api/category'

// initial state
const state = {
  list: [],
  items: [],
  currentItem: null
}

// mutations
const mutations = {
  saveCategoryList(state, items) {
    state.list = items
  },
  saveCategoryItems(state, items) {
    state.items = items
  },
  clearCategoryItems(state) {
    state.items = null
  },
  saveCurrentCategoryItem(state, item) {
    state.currentItem = item
  },
  resetCurrentCategoryItem(state) {
    if (state.items && state.items.length) {
      state.currentItem = state.items[0]
    }
  }
}

// actions
const actions = {
  fetchCategoryList({ commit, state }) {
    return new Promise((resolve, reject) => {
      categoryList(3).then(
        res => {
          commit('saveCategoryItems', res)
          if (!state.currentItem) {
            commit('saveCurrentCategoryItem', res[0])
          }
          resolve(res)
        },
        error => {
          reject(error)
        }
      )
    })
  }
}

export default {
  state,
  mutations,
  actions
}
