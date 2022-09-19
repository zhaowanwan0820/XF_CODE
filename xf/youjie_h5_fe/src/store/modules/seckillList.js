import utils from '../../util/util'

const state = {
  secList: [], //status 0未开始 1进行中 2已结束
  secCurrentItem: {},
  secProducts: {},
  seckillStatus: -1 //商品详情页 当前秒杀商品状态 0暂未开始 1正在秒杀 2秒杀已结束
}

// mutations
const mutations = {
  setSeckillTabStatus(state, payload) {
    let now = payload.time / 1e3
    payload.list.forEach(val => {
      if (val.start_at > now) {
        val.status = 0 //即将开抢
      } else if (val.start_at <= now && now <= val.end_at) {
        val.status = 1 //抢购中
        state.secCurrentItem = val
      } else {
        val.status = 2 //已结束
      }

      let h = utils.formatDate('HH', val.start_at)
      let m = utils.formatDate('mm', val.start_at)
      val.title = h + ':' + m

      val.page = 1
    })

    state.secList = payload.list
    // 若无抢购中的场次，默认 current 为第一个
    if (!state.secCurrentItem.id && payload.list[0]) {
      state.secCurrentItem = payload.list[0]
    }
  },
  setSecItem(state, payload) {
    state.secCurrentItem = payload
  },
  changeItemStatus(state, payload) {
    state.secCurrentItem.status = payload
    state.secList.forEach(item => {
      if (item.id === state.secCurrentItem.id && item.status != payload) item.status = payload
      return
    })
  },
  changeItemPage(state, page) {
    state.secCurrentItem.page = page
    state.secList.forEach(item => {
      if (item.id === state.secCurrentItem.id) item.page = page
      return false
    })
  },
  changeItemTotal(state, total) {
    total = Math.ceil(total / 10)
    state.secCurrentItem.total = total
    state.secList.forEach(item => {
      if (item.id === state.secCurrentItem.id) item.total = total
      return false
    })
  },
  setSeckillProducts(state, payload) {
    state.secProducts = { ...payload }
  },
  clearSeckillProducts(state, payload) {
    state.secProducts = {}
  },
  setSeckillStatus(state, value) {
    state.seckillStatus = value
  },
  clearSeckillStatus(state, value) {
    state.seckillStatus = -1
  },
  clearSeckill(state, value) {
    state.seckillStatus = 0
    state.secList = []
    state.secCurrentItem = {}
  }
}

// actions
const actions = {}

export default {
  state,
  mutations,
  actions
}
