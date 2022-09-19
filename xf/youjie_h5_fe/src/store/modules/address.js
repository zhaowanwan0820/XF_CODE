import * as api from '../../api/consignee'
import { getItemById, getIndexById } from '../util/paginate'

const setMap = function(arr) {
  let o = {}
  for (var i = 0; i < arr.length; i++) {
    o[arr[i].id] = arr[i].name
    if (arr[i].more == 1) {
      o = { ...o, ...setMap(arr[i].regions) }
    }
  }
  return o
}

// initial state
const state = {
  defaultItem: null, // 默认收货地址
  selectedItem: null, // 选中的收货地址
  items: [],
  regionsMap: {} // 地址信息MAP
}

// getters
const getters = {
  // TODO:
}

// 遍历获取默认item
const getDefaultItem = items => {
  let item = null
  for (let i = 0; i < items.length; i++) {
    const element = items[i]
    if (element.is_default) {
      item = element
      break
    }
  }
  return item
}

// mutations
const mutations = {
  setDefaultAddress(state, item) {
    state.defaultItem = item

    // 当前选中的地址和要设置为默认的地址不同时，更新当前选中的地址值
    let selectedItem = state.selectedItem
    if (selectedItem && selectedItem.id) {
      if (selectedItem.id !== item.id) {
        selectedItem.is_default = false
      } else {
        selectedItem.is_default = true
      }
      this.commit('selectAddressItem', selectedItem)
    }
  },
  selectAddressItem(state, item) {
    state.selectedItem = item
  },
  unselectAddressItem(state) {
    state.selectedItem = null
  },
  traverseAddressItems(state) {
    const { items } = state
    if (items && items.length) {
      let defaultItem = getDefaultItem(items)
      if (defaultItem) {
        this.commit('setDefaultAddress', defaultItem)
      }

      if (state.selectedItem === null || state.selectedItem === undefined) {
        let item = defaultItem ? defaultItem : items[0]
        this.commit('selectAddressItem', item)
      }
    }
  },
  addAddressItem(state, item) {
    state.items.push(item)
    this.commit('traverseAddressItems')
  },
  removeAddressItem(state, id) {
    const { items } = state
    let index = getIndexById(items, id)
    state.items.splice(index, 1)

    // 当前选中的地址和要删除的地址相同时，当前选中的地址置为空
    let selectedItem = state.selectedItem
    if (selectedItem && selectedItem.id && selectedItem.id === id) {
      this.commit('unselectAddressItem')
    }
    this.commit('traverseAddressItems')
  },
  modifyAddressItem(state, item) {
    const { items } = state
    let index = getIndexById(items, item.id)
    state.items.splice(index, 1, item)

    // 当前选中的地址和要修改的地址相同时，更新当前选中的地址
    let selectedItem = state.selectedItem
    if (selectedItem && selectedItem.id && selectedItem.id === item.id) {
      this.commit('selectAddressItem', item)
    }
    this.commit('traverseAddressItems')
  },
  saveAddressItems(state, items) {
    state.items = items
    this.commit('traverseAddressItems')
  },
  saveRegionsMap(state, items) {
    state.regionsMap = setMap(items)
  }
}

// actions
// const actions = {
//   fetchItems({ commit, state }) {
//   return api.consigneeList().then(
//       (response) => {
//         const { consignees } = response
//         commit('saveAddressItems', consignees)
//       }, (error) => {
//         // TODO:
//       })
//   },
//   addAddressItem({ commit, state }, {name, mobile, tel, zip_code, region, address}) {
//     return api.consigneeAdd(name, mobile, tel, zip_code, region, address).then(
//       (response) => {
//         const { consignee } = response
//         commit('addAddressItem', consignee)
//       }, (error) => {
//         // TODO:
//       })
//   },
//   modifyAddressItem({ commit, state }, id, name, mobile, tel, zip_code, region, address) {
//     return api.consigneeUpdate(id, name, mobile, tel, zip_code, region, address).then(
//       (response) => {
//         const { consignee } = response
//         commit('modifyAddressItem', consignee)
//       }, (error) => {
//         // TODO:
//       })
//   },
//   removeAddressItem({ commit, state }, id) {
//     return api.consigneeDelete(id).then(
//       (response) => {
//         commit('removeAddressItem', id)
//       }, (error) => {
//         // TODO:
//       })
//   },
//   setDefaultAddressItem({ commit, state }, id) {
//     return api.consigneeSetdefaultAddress(id).then(
//       (response) => {
//         const { items } = state
//         let item = getItemById(items, id)
//         commit('setDefaultAddress', item)
//       }, (error) => {
//         // TODO:
//       })
//   }
// }

export default {
  state,
  getters,
  mutations
  // actions
}
