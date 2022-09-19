// import * as api from '../../api/cashgift'
// initial state
// const state = {
//   selectedItem: null, // 选中的item
//   items: [],
//   total: 0,
//   page: 1, // 当前页码
//   isMore: false // 是否有更多
// }

// getters
// const getters = {}

// mutations
// const mutations = {
//   selectCashgiftItem(state, item) {
//     const { selectedItem } = state
//     if (selectedItem) {
//       if (selectedItem.id !== item.id) {
//         state.selectedItem = item
//       }
//     } else {
//       if (item) {
//         state.selectedItem = item
//       }
//     }
//   },
//   unselectCashgiftItem(state) {
//     state.selectedItem = null
//   },
//   saveCashgiftItems(state, items) {
//     state.items = items
//   },
//   saveCashgiftPaged(state, payload) {
//     state.total = payload.total
//     state.page = payload.page
//     state.isMore = payload.isMore
//   }
// }

// actions
// const actions = {
//   fetchCashgiftUsable({ commit, state }, params) {
//     let page = params.isFirstPage ? 1 : state.page
//     let per_page = 10
//     api.cashgiftAvailable(page, per_page, params.shop, params.total_price).then(
//       response => {
//         if (response && response.cashgifts) {
//           let items = state.items
//           if (params.isFirstPage) {
//             page = 1
//             items = response.cashgifts
//           } else {
//             items = [...items, ...response.cashgifts]
//           }
//           page = page + 1
//           let total = response.paged.total
//           let isMore = response.paged.more
//           commit('saveCashgiftItems', items)
//           commit('saveCashgiftPaged', { page, total, isMore })
//         }
//       },
//       error => {}
//     )
//   }
// }

// export default {
//   state,
//   mutations,
//   actions
// }
