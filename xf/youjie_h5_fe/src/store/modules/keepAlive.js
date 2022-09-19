// 该数组中的name 不缓存，与下面state中的include相反的意思
import exclude from '../util/keepAlive_exclude'

// initial state
const state = {
  include: [], // 该数组初始值不用管；实际应用中主要配置下边的exclude值
  routerStack: [] // 记录访问路径 以 标识 前进或者后退
}

// mutations
const mutations = {
  pushInclude(state, item) {
    // 开发环境若因为keepAlive 影响开发过程中的hotReload，可以将以下注释打开
    // if (process.env.NODE_ENV === 'development') {
    //   state.include = []
    //   return
    // }

    if (!state.include.includes(item) && !exclude.includes(item)) {
      state.include.push(item)
    }
  },
  popInclude(state, item) {
    // if ('home' === item) return
    const index = state.include.indexOf(item)
    index !== -1 && state.include.splice(index, 1)
  },
  pushRouterStack(state, item) {
    state.routerStack.push(item)
  },
  popRouterStack(state, item) {
    state.routerStack.pop()
  },
  resetRouterStack(state, item) {
    state.routerStack = []
  }
}

export default {
  state,
  mutations
}
