// initial state
const state = {
  items: [],
  selectedDate: null,
  selectedTime: null
}

// getters
const getters = {
  getTimeItems: state => {
    let selectedDate = state.selectedDate
    let items = state.items
    return getItemByKey(selectedDate, items)
  }
}

const getItemByKey = (key, items) => {
  for (let i = 0; i < items.length; i++) {
    const element = items[i]
    if (key && key === element.date) {
      return element.time
    }
  }
  return null
}

// mutations
const mutations = {
  saveDeliveryItems(state, items) {
    state.items = items
  },
  clearDeliveryItems(state) {
    state.items = []
  },
  selectDeliveryDate(state, date) {
    const { selectedDate } = state
    if (selectedDate) {
      if (selectedDate !== date) {
        state.selectedDate = date
      }
    } else {
      state.selectedDate = date
    }
    let items = state.items
    const times = getItemByKey(selectedDate, items)
    if (times && times.length) {
      this.commit('selectDeliveryTime', times[0])
    }
  },
  unselectDeliveryDate(state) {
    state.selectedDate = null
  },
  selectDeliveryTime(state, time) {
    const { selectedTime } = time
    if (selectedTime) {
      if (selectedTime !== time) {
        state.selectedTime = time
      }
    } else {
      state.selectedTime = time
    }
  },
  unselectDeliveryTime(state) {
    state.selectedTime = null
  },
  unselectDelivery(state) {
    this.commit('unselectDeliveryDate')
    this.commit('unselectDeliveryTime')
  }
}

export default {
  state,
  getters,
  mutations
}
