import utils from '../../util/util'

const initState = {
  detailInfo: {}, //商品详情
  isShowcartInfo: false, //是否显示购物车
  isShowPromosPopup: false, //是否显示活动详情
  number: 0, //选择商品的数量
  chooseinfo: {
    specification: [],
    ids: []
  }, //选择该商品的ids和属性名称
  properties: [], //多属性商品的属性名称值
  index: 0, //当前点击的type值
  currentProductId: '', //当前商品的id
  isPreviewPicture: false, //当前是否是预览大图
  swipeId: 0, //当前滑动的swiperid值

  isShowHBRules: false, // 是否展示积分说明
  isShowServeTag: false, // 是否展示服务标签
  productSharer: [], //商品的分销客分享信息
  sharerTotal: 0, // 分销客获取
  isPreSale: false, // 是否预售
  showFromAct: 1, // 1-点击【商品规则选择】，2-点击加入购物车，3-点击立即购买
  instalment: null, // 汽车商品分期情况
  instalmentWay: undefined, // 选择的分期方式
  stackChoosedIds: [], // 按选择顺序存储选中的规格id（倒序，后选择的index靠前），为SKU图片服务

  isShowConfirmTrough: false, //是否展示直通车弹窗
  isConfirmTrough: false, //直通车商品是否确认下单
  troughInfo: {}, //立即下单返回 直通车商品信息

  // 商品的优惠券
  couponInfo: {
    id: undefined, // 商品id
    list: [] // 优惠券列表
  },
  isShowCouponPopup: false, // 是否显示优惠券弹窗
  isShowServicePopup: false // 是否显示联系信息弹窗
}

const getters = {
  getDetailPromos: function(state) {
    let arr = []
    if (!state.detailInfo.promos) {
      return []
    }
    state.detailInfo.promos.forEach((item, index) => {
      if (item.status == 2 && [2, 3].indexOf(item.type) > -1) {
        arr.push(item)
      }
    })
    return arr
  },
  getShoppingPrice: function(state) {
    // 根据 detailInfo.stock 和 chooseinfo 计算商品的价格
    let shoppingPrice = {
      money_line: 0,
      current_price: 0
    }

    if (state.detailInfo.id) {
      if (state.detailInfo.seller) {
        // 区分 分销商品
        shoppingPrice = {
          money_line: Number(state.detailInfo.money_line || 0),
          current_price: state.detailInfo.seller.current_price - state.detailInfo.money_line
        }
      } else if (state.detailInfo.secbuy) {
        // 秒杀商品
        shoppingPrice = {
          money_line: Number(state.detailInfo.secbuy.money_line || 0),
          current_price: state.detailInfo.secbuy.cash_price
        }
      } else {
        shoppingPrice = {
          money_line: Number(state.detailInfo.money_line || 0),
          current_price: state.detailInfo.current_price - state.detailInfo.money_line
        }
      }

      // 秒杀商品暂 不支持多属性商品设置单独价格
      if (!state.detailInfo.secbuy) {
        // 已选择 全部销售属性
        if (state.detailInfo.properties && state.chooseinfo.ids.length == state.detailInfo.properties.length) {
          let fromatIds = utils.fromatArray('|', state.chooseinfo.ids)
          let data = state.detailInfo.stock
          if (data) {
            for (let i = 0; i <= data.length - 1; i++) {
              if (data[i] && data[i].goods_attr.indexOf(fromatIds) >= 0) {
                shoppingPrice['current_price'] = data[i].goods_attr_price - state.detailInfo.money_line
              }
            }
          }
        }
      }
    }

    return shoppingPrice
  },
  hasSelectProperty: function(state) {
    // 是否已 选择全部商品属性
    let has = true

    if (state.detailInfo.id && state.detailInfo.properties.length) {
      if (state.chooseinfo.specification.length != 0 && state.chooseinfo.ids.length == 0) {
        has = false
      } else if (state.chooseinfo.ids.filter(item => item).length != state.detailInfo.properties.length) {
        has = false
      } else {
        for (let i = 0; i <= state.chooseinfo.ids.length - 1; i++) {
          if (state.chooseinfo.ids[i] == '' || state.chooseinfo.ids[i] == undefined) {
            has = false
          }
        }
      }
    }

    return has
  },
  isHasStock: function(state) {
    // 选择规格后的库存
    if (state.detailInfo.id) {
      let data = state.detailInfo.stock || []
      let id = utils.fromatArray('|', state.chooseinfo.ids)

      if (data.length > 0) {
        for (let i = 0, len = data.length; i <= len - 1; i++) {
          if (data[i].goods_attr == id) {
            return '' + data[i].stock_number + ''
          }
        }
      }
    }
  },
  hasEnoughStock: function(state, getters) {
    // 库存是否足够
    let has = true

    let good_stock = 0
    if (state.chooseinfo.ids.length <= 0) {
      good_stock = state.detailInfo.good_stock
    } else {
      let stock = getters.isHasStock
      good_stock = stock ? parseInt(stock) : state.detailInfo.good_stock
    }

    if (state.number > good_stock) {
      has = false
    }

    return has
  },
  getStockLimit: function(state, getters) {
    let res = 0
    if (state.detailInfo.id) {
      if (!getters.hasSelectProperty) {
        res = state.detailInfo.good_stock
      } else {
        let stock = getters.isHasStock
        res = stock ? parseInt(stock) : state.detailInfo.good_stock
      }
    }
    return res
  }
}

// initial state
const state = {
  ...initState,
  initState() {
    return initState
  }
}

// mutations
const mutations = {
  // 保存商品详情， 各个组件数据共享
  saveDetailInfo(state, value) {
    state.detailInfo = value
    state.detailInfo.supplier = { ...state.detailInfo.supplier }
    // state.instalment = utils.getInstalment(value.goods_brief)
  },

  // 根据点击时是否显示 选择商品规则 浮层
  saveCartState(state, value) {
    state.isShowcartInfo = value
  },

  // 根据点击时是否显示活动详情
  savePromosPopupState(state, value) {
    state.isShowPromosPopup = value
  },

  // 根据点击时是否显示活动详情
  saveHBRulesPopupState(state, value) {
    state.isShowHBRules = value
  },

  // 保存商品的分销客分享信息
  saveProductSharer(state, payload) {
    state.productSharer = payload.list || []
    state.sharerTotal = (payload.paged && payload.paged.total) || 0
  },

  // 根据点击时是否显示服务标签
  saveServeTagPopupState(state, value) {
    state.isShowServeTag = value
  },

  // 保存选择的商品的数量
  saveNumber(state, number) {
    state.number = number
  },

  //保存当前切换的tab值
  changeIndex(state, value) {
    state.index = value
  },

  // 设置当前商品的id值
  setCurrentProductId(state, value) {
    state.currentProductId = value
  },

  // 设置当前商品的属性值
  saveChooseInfo(state, info) {
    state.chooseinfo = info
  },

  // saveChooseInfo(state, info) {
  //   state.chooseinfo = info
  // },

  saveProperties(state, value) {
    state.properties = value
  },

  // 改变当前是否是预览大图的值
  setisPreviewPicture(state, value) {
    state.isPreviewPicture = value
  },

  setSwiperId(state, value) {
    state.swipeId = value
  },

  // 保存预售倒计时时间
  saveSaleFlag(state, value) {
    if (value === undefined) {
      state.isPreSale =
        state.detailInfo.is_pre_sale && state.detailInfo.sale_time > parseInt(new Date().getTime() / 1000)
    } else {
      state.isPreSale = value
    }
  },

  saveIsLike(state, value) {
    let info = { ...state.detailInfo }
    info.is_liked = value
    state.detailInfo = { ...info }
  },

  // 记录用户点击弹出 选择销售属性 浮层的来源（1-选择商品规格、2-加入购物车、3、立即购买）
  saveShowFromAct(state, value) {
    state.showFromAct = value
  },

  // 保存分期方式
  saveInstalmentWay(state, value) {
    state.instalmentWay = value
  },

  // stackChoosedIds变化
  editStackChoosedIds(state, { action = 'add', id }) {
    if ('add' === action) {
      // 选中
      state.stackChoosedIds.unshift(id)
    } else {
      //取消选中
      state.stackChoosedIds.splice(state.stackChoosedIds.findIndex(ele => ele == id), 1)
    }
  },

  changeShowConfirmTrough(state, value) {
    state.isShowConfirmTrough = value
  },
  setConfirmTrough(state, value) {
    state.isConfirmTrough = value
  },
  setTroughInfo(state, value) {
    state.troughInfo = value
  },

  // 保存优惠券信息
  saveDetailCouponInfo(state, payload) {
    state.couponInfo.id = payload.id || undefined
    state.couponInfo.list = payload.list
  },

  // 更新优惠券信息{ 领取优惠券后更新样式 }
  updateCouponInfo(state, index) {
    const has_got = 2
    state.couponInfo.list[index].is_rec = has_got
  },

  // 根据点击时是否显示优惠券
  saveCouponPopupState(state, value) {
    state.isShowCouponPopup = value
  },

  // 根据点击时是否显示联系方式
  saveServicePopupState(state, value) {
    state.isShowServicePopup = value
  }
}

export default {
  state,
  mutations,
  getters
}
