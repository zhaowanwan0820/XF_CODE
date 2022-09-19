// initial state
const initState = {
  isShowProductModel: false, // 筛选时商品列表是否出现浮层
  // isSearch: false //判断是否调用搜索接口
  imagePopupType: null, //展示大图来源 默认为detail.photos商品大图
  comments: {
    //评价大图 当前点击的评价信息
    comment_id: null,
    img_arr: []
  }
}

const state = {
  ...initState,
  initState() {
    return initState
  }
}

// mutations
const mutations = {
  // 切换筛选时商品列表是否出现浮层
  changeIsShowProductModel(state, value) {
    state.isShowProductModel = value
  },
  // changeSearch(state, value) {
  //   state.isSearch = value
  // }
  setComments(state, value) {
    // 如果是同一评论的图片，不需要重新赋值
    if (value.comment_id === state.comments.comment_id) return

    state.comments = { ...value }
  },
  resetComments(state) {
    state.comments = { ...initState.comments }
  },
  saveImageType(state, value) {
    state.imagePopupType = value
  }
}

export default {
  state,
  mutations
}
