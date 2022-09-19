// 满减活动

// initial state
const state = {
  seckillItems: [], // 选中的item
  selectedItem: {},
  prodPrice: 0,
  discountMoney: 0
}

// getters
const getters = {
  getUsableList: state => {
    let arr = []
    let obj = {}
    state.seckillItems.forEach((item, index) => {
      if (
        (item.status == 2 && item.detail.limit <= state.prodPrice && !item.is_over) ||
        item.id == state.selectedItem.id
      ) {
        obj[item.detail.reduce] = item
        arr.push(item)
      }
    })
    let keys = Object.keys(obj)
    keys.sort(function(a, b) {
      return a - b < 0
    })
    keys.forEach((item, index) => {
      arr[index] = obj[item]
    })
    return arr
  },

  getDetailUsableList: state => {
    let arr = []
    let obj = {}
    let n = []
    state.seckillItems.forEach((item, index) => {
      if (item.status == 2 && !item.is_over) {
        obj[item.detail.reduce] = item
        arr.push(item)
      }
    })
    let keys = Object.keys(obj)
    keys.sort(function(a, b) {
      return a - b < 0
    })
    keys.forEach((item, index) => {
      n[index] = obj[item]
    })
    return n
  }
}

// mutations
const mutations = {
  saveSeckillItems(state, promos) {
    let arr = []
    promos.forEach((item, index) => {
      if (item.type == 1) {
        arr.push(item)
      }
    })
    state.seckillItems = arr
  },
  unsaveSeckillItems(state, promos) {
    state.seckillItems = []
  },
  removeSeckillItem(state, seckillItem) {
    state.seckillItems.forEach((item, index) => {
      if (seckillItem.id == item.id) {
        state.seckillItems.splice(index, 1)
      }
      if (seckillItem.id == state.selectedItem.id) {
        state.selectedItem = {}
      }
    })
  },
  savePrice(state, price) {
    // product.current_price
    state.prodPrice = price
  },
  saveSelectedItem(state, item) {
    state.selectedItem = item || {}
  },
  unsaveSelectedItem(state) {
    state.selectedItem = {}
  }
}

export default {
  state,
  mutations,
  getters
}
