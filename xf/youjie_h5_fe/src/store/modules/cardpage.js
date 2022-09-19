// import { cardpageGet } from '../../api/cardpage'

// initial state
// const state = {
//   item: null
// }

// // mutations
// const mutations = {
//   saveIndexCardpage(state, item) {
//     state.item = item
//   },
//   clearIndexCardpage(state) {
//     state.item = null
//   }
// }

// actions
// const actions = {
//   fetchCardpage({ commit, state }, params) {
//     return new Promise((resolve, reject) => {
//       cardpageGet(params.name).then(
//         response => {
//           if (response && response.cardpage) {
//             commit('saveIndexCardpage', response.cardpage)
//           }
//           resolve(response)
//         },
//         error => {
//           reject(error)
//         }
//       )
//     })
//   }
// }

// export default {
//   state,
//   mutations,
//   actions
// }
