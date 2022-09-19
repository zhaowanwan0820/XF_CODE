import { cartGet, cartQuantity } from '../../api/cart'
import { orderPrice } from '../../api/order'

// initial state
const state = {
  goodsList: [], // 商品列表list，格式：{goods_id, price, property, attrs, attr_stock, amount, checked}
  mode: 1, // 1 结算模式，2 删除模式
  total_price: 0, // 结算商品（已选择）的总价，由服务器端计算
  promo_price: 0, // 活动优惠的金额，由服务器端计算
  isLoading: false // 数据是否正在更新
}

const getters = {
  cart_isEmpty(state, getters) {
    return state.goodsList.length ? false : true
  },
  cart_choosenGoods: function(state) {
    // 选中的商品list
    return state.goodsList.filter(ele => {
      return ele.checked ? true : false
    })
  },
  cart_choosen_amount: function(state, getters) {
    // 结算（已选择）商品的数量
    return getters.cart_choosenGoods.reduce((accumulator, currentValue) => {
      return accumulator + Number(currentValue.amount)
    }, 0)
  },
  cart_valid_goods: function(state, getters) {
    // 购物车中的有效商品
    return state.goodsList.filter(ele => {
      return ele.goods.good_stock > 0 && !isPreSale(ele.goods)
    })
  },
  cart_all_amount: function(state, getters) {
    // 购物车所有商品的数量
    return state.goodsList.reduce((accumulator, currentValue) => {
      return accumulator + Number(currentValue.amount)
    }, 0)
  },
  choosenGoodsInfoForOrderPrice(state, getters) {
    // 整理计算 orderprice需要的数据
    return getters.cart_choosenGoods.map(ele => {
      let obj = {
        goods_id: ele.goods.id,
        property: [],
        num: ele.amount
      }
      if (ele.attrs) {
        if (typeof ele.attrs !== 'number') {
          const attrs = ele.attrs.split(',')
          for (let i = 0; i <= attrs.length - 1; i++) {
            obj.property.push(attrs[i])
          }
        } else {
          obj.property.push(ele.attrs.toString())
        }
      }
      return obj
    })
  },
  cart_isAllChoosen(state, getters) {
    // 结算模式下，除失效商品外 其他商品都选中 也为全选中
    if (1 == state.mode) {
      return state.goodsList.length &&
        getters.cart_valid_goods.length &&
        getters.cart_valid_goods.length === getters.cart_choosenGoods.length
        ? true
        : false
    } else {
      return state.goodsList.length && state.goodsList.length === getters.cart_choosenGoods.length ? true : false
    }
  }
}

// mutations
const mutations = {
  saveGoodsList(state, list) {
    state.goodsList = list
  },
  saveTotalPrice(state, payload) {
    state.total_price = payload
  },
  savePromoPrice(state, payload) {
    state.promo_price = payload
  },
  changeChoosenStatus(state, good) {
    // 选择或者取消选择
    state.goodsList.find((ele, index) => {
      if (ele.id == good.id) {
        state.goodsList[index].checked = !state.goodsList[index].checked
        return true
      } else {
        return false
      }
    })
  },
  editGoodNumber(state, payload) {
    // 增加或者减少商品购买数量, action 1 增加，0 减少，2 直接修改
    const index = state.goodsList.findIndex(ele => {
      return ele.id == payload.item.id
    })
    switch (payload.action) {
      case 1:
        state.goodsList[index].amount++
        break
      case 0:
        state.goodsList[index].amount++
        break
      case 2:
        state.goodsList[index].amount = payload.amount
        break
    }
  },
  changeMode(state) {
    // 切换 结算或者删除 模式
    state.mode = state.mode === 1 ? 2 : 1
    // 切换后 重置所有商品的checked为false
    state.goodsList.forEach((ele, index) => {
      state.goodsList[index].checked = false
    })
    // 切换后 清空total_price promo_price
    state.total_price = 0
    state.promo_price = 0
  },
  removeGoods(state, rmGoods) {
    // 从购物车移除选中的商品
    rmGoods.forEach(ele => {
      state.goodsList.splice(state.goodsList.findIndex(ele1 => ele1.id == ele.id), 1)
    })
  },
  selectALL(state, isSelectAll) {
    // 全选 或 取消全选
    if (isSelectAll) {
      state.goodsList.forEach((ele, index) => {
        // 结算 模式下，失效商品不可选择
        if (1 == state.mode && (!state.goodsList[index].goods.good_stock || isPreSale(ele.goods))) {
          return
        }
        state.goodsList[index].checked = true
      })
    } else {
      state.goodsList.forEach((ele, index) => {
        state.goodsList[index].checked = false
      })
    }
  },
  setCartIsLoading(state, isLoading) {
    state.isLoading = isLoading
  }
}

// actions
const actions = {
  fetchCartList({ commit }) {
    // 获取购物车列表数据
    return new Promise((resolve, reject) => {
      cartGet().then(res => {
        let newGoodsList = []
        res.map(ele => {
          ele.list.map(item => {
            item.checked = false
            item.shop_name = ele.shop_name
            item.sn = ele.sn
          })
          newGoodsList = newGoodsList.concat(ele.list)
        })
        commit('saveGoodsList', Object.assign([], newGoodsList))
        resolve()
      })
    })
  },
  fetchOrderPrice({ commit, getters }) {
    // 根据当前选择的商品调用后端Api 计算相关价格数据
    return new Promise((resolve, reject) => {
      // 未选择商品
      if (!getters.choosenGoodsInfoForOrderPrice.length) {
        commit('saveTotalPrice', 0)
        commit('savePromoPrice', 0)
        resolve()
        return
      }
      orderPrice(getters.choosenGoodsInfoForOrderPrice).then(res => {
        // 已选商品总价
        commit('saveTotalPrice', res.total_price)
        // 活动优惠价格
        let promo_price = 0
        if (res.promos && res.promos.length > 0) {
          promo_price = res.promos[0].price || 0
        }
        commit('savePromoPrice', promo_price)
        resolve()
      })
    })
  },
  fetchCartNumber({ commit }) {
    // 获取购物车内商品总数
    cartQuantity().then(res => {
      commit('setCartNumber', res)
    })
  }
}

const isPreSale = good => {
  const now = parseInt(new Date().getTime() / 1000)
  return !!(good.is_pre_sale && good.sale_time > now)
}

export default {
  state,
  getters,
  mutations,
  actions
}
