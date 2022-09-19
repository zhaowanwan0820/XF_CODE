import { bannerList } from '../../api/banner'
import { homeProductList } from '../../api/home'
import { seckillRecommend } from '../../api/seckill'
import utils from '../../util/util'

// initial state
const state = {
  banners: null,
  hotProducts: null,
  recentlyProducts: null,
  goodProducts: null,
  seckill: {}
}

// mutations
const mutations = {
  saveHomeBanners(state, items) {
    state.banners = items
  },
  saveHomeProducts(state, data) {
    state.hotProducts = utils.splitMoneyLint(data.hot_products)
    state.recentlyProducts = utils.splitMoneyLint(data.recently_products)
    state.goodProducts = utils.splitMoneyLint(data.good_products)
  },
  saveHomeSeckill(state, data) {
    state.seckill = data
  },
  clearHomeSeckill(state, data) {
    state.seckill = {}
  }
}

// actions
const actions = {
  fetchHomeBanner({ commit, state }) {
    return new Promise((resolve, reject) => {
      bannerList().then(
        res => {
          commit('saveHomeBanners', res)
          resolve(res)
        },
        error => {
          reject(error)
        }
      )
    })
  },

  fetchHomeProduct({ commit, state }) {
    return new Promise((resolve, reject) => {
      const params = {
        show: [1] // 请求数据的数组 1-hotProducts ; 2-recentlyProducts ; 3-goodProducts
      }
      homeProductList(params).then(
        res => {
          commit('saveHomeProducts', res)
          resolve(res)
        },
        error => {
          reject(error)
        }
      )
    })
  },
  fetchHomeSeckillProducts({ commit, state }) {
    return new Promise((resolve, reject) => {
      seckillRecommend()
        .then(
          res => {
            commit('saveHomeSeckill', res)
            resolve(res)
          },
          error => {
            reject(error)
          }
        )
        .finally(() => {})
    })
  }
}

export default {
  state,
  mutations,
  actions
}
