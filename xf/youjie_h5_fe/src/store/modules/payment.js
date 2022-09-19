const state = {
  order: {},
  hhpay: {
    money_paid: 0,
    surplus_paid: 0,
    need_surplus: 0,
    user_debt: 0,
    need_money: 0,
    friend_high_surplus: 0,
    support_friend_pay: false
  },
  isFriendPay: 0 // 是否是好友代付
}

const getters = {
  orderNeedSurplus: function(state) {
    let price = 0
    if (state.hhpay.need_money > 0) {
      if (state.hhpay.surplus_paid > 0) {
        price = 0
      } else if (state.isFriendPay) {
        price = 0
      } else {
        price = state.hhpay.need_surplus
      }
    } else {
      if (state.hhpay.friend_high_surplus == -1) {
        price = state.order.total
      } else {
        if (state.isFriendPay) {
          price = 0
        } else {
          price = state.hhpay.need_surplus
        }
      }
    }
    return Number(price)
  },
  orderNeedMoney: function(state) {
    let price = 0
    price = state.hhpay.need_money
    return Number(price)
  },
  companyList(state) {
    let company = state.hhpay.user_partner_info ? state.hhpay.user_partner_info : {}
    if (company instanceof Array) {
      company = {}
    }
    let list = []
    if (company) {
      list = [...Object.values(company)]
    }
    return list
  }
}

// mutations
const mutations = {
  initPaymentSate(state) {
    state.order = {}
    state.hhpay = {
      money_paid: 0,
      surplus_paid: 0,
      need_surplus: 0,
      user_debt: 0,
      need_money: 0,
      friend_high_surplus: 0,
      support_friend_pay: false
    }
    state.isFriendPay = 0
  },
  saveOrder(state, order) {
    state.order = order
  },
  saveHhpay(state, payload) {
    state.hhpay = { ...payload }
  },
  saveFriendPayFlag(state, value) {
    state.isFriendPay = value
  }
}

export default {
  state,
  getters,
  mutations
}
