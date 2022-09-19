import { getHhkRetailCategory } from '../../api/huanhuanke'

const state = {
  addBySelf: false,
  hasProduct: true,
  // 获取分类和商品列表
  categorylists: [],
  // productlists: [],
  currentCate: null,
  shop_base_infos: {
    id: '', // 商家id 返回对象key为id，小店shop_sn
    is_supplier: '', // 判断是否是商家
    shop_icon: '',
    shop_name: '',
    shop_banner: '',
    shop_desc: '',
    fans_count: 0,
    pay_count: 0,
    money_all: 0,
    is_personal_supplier: '', // 个人商家的商家识别码
    personal_goods_count: 0 // 个人商家的商家数量
  } // 店铺基本信息，昵称，描述。。。
}

const mutations = {
  hideAddShelf(state) {
    state.addBySelf = !state.addBySelf
  },
  setCategoryLists(state, catelist) {
    state.categorylists = catelist
  },
  saveCurrentProductCate(state, item) {
    state.currentCate = item
  },
  setHasProduct(state, flag) {
    state.hasProduct = flag
  },
  setShopBaseInfos(state, infosload) {
    state.shop_base_infos = { ...infosload }
  }
}

const actions = {
  fetchMlmCategoryList({ commit, state }) {
    return new Promise((resolve, reject) => {
      getHhkRetailCategory().then(
        res => {
          commit('setCategoryLists', res)
          commit('saveCurrentProductCate', res[0])
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
