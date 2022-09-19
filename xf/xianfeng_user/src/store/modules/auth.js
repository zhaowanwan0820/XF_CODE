  

// initial state
const state = {
  isOnline: false,
  token: null,  
}

// mutations
const mutations = {  
  saveToken(state, payload) {
    state.isOnline = true
    state.token = payload 
  }, 
  clearToken(state) {
    state.isOnline = false
    state.token = null 
  }, 
  
}
 

export default {
  state, 
  mutations
}
