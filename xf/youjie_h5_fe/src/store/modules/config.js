// import { configGet } from '../../api/config'
// import XXTEA from '../../assets/js/xxtea'

const ENCRYPT_KEY = 'getprogname()'

// initial state
const state = {
  config: null,
  feature: null,
  platform: null
}

// mutations
const mutations = {
  saveConfig(state, payload) {
    state.config = payload.config
    state.feature = payload.feature
    state.platform = payload.platform
  },
  clearConfig(state) {
    state.config = null
    state.feature = null
    state.platform = null
  }
}

// actions
// const actions = {
//   fetchConfig({ commit, state }) {
//     return new Promise((resolve, reject) => {
//       configGet().then(
//         (response) => {
//           if (response.data) {
//             let raw = XXTEA.decryptFromBase64(response.data, ENCRYPT_KEY);
//             let json = JSON.parse(raw);
//             commit('saveConfig', json)
//             resolve(json)
//           }
//         }, (error) => {
//           reject(error)
//         })
//     })
//   }
// }

export default {
  state,
  mutations
  // actions
}
