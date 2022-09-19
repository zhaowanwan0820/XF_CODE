<template>
  <div class="ui-product container">
    <div class="product-header">
      <!-- 商品头部 -->
      <product-header ref="header" :value="params.keyword" :isHb="isOnlyForHbUser"></product-header>

      <!-- 商品筛选 -->
      <product-filter
        ref="filter"
        :value="params.sort_key"
        :isHb="isOnlyForHbUser"
        v-if="!isOnlyForHbUser"
      ></product-filter>
      <!-- 积分专区隐藏商品筛选部分 -->
    </div>

    <!-- 商品列表 -->
    <div
      class="product-body"
      v-bind:class="{
        'scroll-container-keepAlive': true,
        'hide-product-list': isShowProductModel,
        'show-product-list': !isShowProductModel
      }"
      v-infinite-scroll="getMore"
      infinite-scroll-distance="10"
    >
      <!--  活动介绍    -->
<!--      <div class="toast-title" v-if="params.appoint == 1">-->
<!--        <p>XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX</p>-->
<!--        <div class="message-text">-->
<!--          <h4>注意事项：以下情况您可能无法继续参加0元购活动</h4>-->
<!--          <p>1、在兑换积分后，未提交订单的</p>-->
<!--          <p>2、购买了0元购商品又进行了退款的</p>-->
<!--        </div>-->
<!--      </div>-->
      <!-- 无限加载滚动列表 -->
      <div class="clearfix">
        <product-body
          :item="item"
          v-for="(item, index) in productList"
          v-bind:key="index"
          :productId="item.id"
          :requestparams="params"
        >
        </product-body>

        <!-- 表示是否还有更多数据的状态 -->
        <div class="loading-wrapper">
          <p v-if="!isMore && productList.length > 0">没有更多了</p>
          <mt-spinner type="triple-bounce" color="#FD9F21" v-if="isMore"></mt-spinner>
        </div>

        <div class="wrapper-list-empty" v-if="productList.length <= 0 && !isMore">
          <img src="../../assets/image/hh-icon/l0-list-icon/cart-list.png" />
          <p>暂无任何商品</p>
        </div>
        <div class="show-product-model" v-if="isShowProductModel" @click="closeProductModel"></div>
      </div>
    </div>
    <!-- 回到顶部 -->
    <v-back-top v-if="productList.length > 10" :target="target"></v-back-top>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import productHeader from './child/ProductListHeader'
import productBody from './child/ProductListBody'
import productFilter from './child/ProductListFilter'
import { productList } from '../../api/product'
import BackTop from '../../components/common/BackTop'
export default {
  name: 'products',
  components: {
    productHeader,
    productFilter,
    productBody,
    'v-back-top': BackTop
  },
  data() {
    return {
      params: {
        brand_id: this.$route.query.brand_id ? this.$route.query.brand_id : '',
        category: this.$route.query.category ? this.$route.query.category : '',
        shop: this.$route.query.shop ? this.$route.query.shop : '',
        sort_key: this.$route.query.sort_key ? this.$route.query.sort_key : 0,
        sort_value: 2,
        page: 0,
        per_page: 10,
        admin_order: this.$route.query.admin_order ? this.$route.query.admin_order : '', // 根据后台配置的推荐排序值由小到大排序：1:是 0:否(默认)
        keyword: this.$route.query.keywords ? this.$route.query.keywords : '',
        tags_id: this.$route.query.tags_id ? this.$route.query.tags_id : '',
        is_newbie: this.$route.query.is_newbie ? this.$route.query.is_newbie : '',
        appoint: this.$route.query.is_appoint ? this.$route.query.is_appoint : '' // 是否指定商品
      },
      productList: [], //商品列表
      loading: false, //是否正在加载数据 防止并发加载
      isMore: true, //是否有更多

      from: this.$route.query.from || '' // 来源，主要需要标识是否来自A类用户的用户中心积分专区入口
    }
  },

  computed: {
    ...mapState({
      isShowProductModel: state => state.product.isShowProductModel
    }),
    ...mapGetters({
      isHbUser: 'isHbUser'
    }),
    isOnlyForHbUser() {
      return this.isHbUser && this.from === 'ucenter' && this.params.sort_key == '6'
    }
  },

  created() {
    //todo
    // this.getUrlParams()

    this.$on('change-list', data => {
      document.activeElement.blur()
      let res = data
      this.params.page = 1
      this.productList = []
      this.loading = true
      this.isMore = true
      this.setParamsByData(res)
      this.getProductList()
    })

    this.$on('get-cart-quantity', () => {
      this.fetchCartNumber()
    })

    if (this.$route.query.keywords) {
      document.activeElement.blur()
    }
  },

  mounted() {
    // 计算内容高度
    this.target = document.querySelector('.product-body')
    // let totalHeight = 89
    // const target = this.target
    // this.utils.fillTheScreen({ target, totalHeight })
    // this.$nextTick(() => {
    //   // this.utils.fillTheScreen({target, totalHeight});
    // })
  },

  methods: {
    ...mapMutations({
      changeIsShowProductModel: 'changeIsShowProductModel'
    }),
    ...mapActions({
      fetchCartNumber: 'fetchCartNumber'
    }),

    /*
     * closeProductModel: 关闭筛选模态框
     */
    closeProductModel() {
      this.changeIsShowProductModel(false)
      this.$refs.filter.closeFiler()
    },

    /*
     * getMore: 无限滚动加载
     * TODO by dlj: 该方法存在被异常、重复 等触发的情况，先增加loading flag解决一下，待debug...
     */
    getMore() {
      if (this.loading) return

      // console.log('getMore Triggered...')

      if (this.isMore) {
        this.params.page = ++this.params.page
        this.loading = true

        this.getProductList(true)
      }
    },

    /*
     *  getUrlParams: 获取url上的参数
     *  @param： category
     *  @param: brand
     *  @param: shop
     *  @param: keywords
     */
    getUrlParams() {
      // console.log(this.$route.query);
      // this.params.brand = this.$route.query.brand ? this.$route.query.brand : '';
      // this.params.category = this.$route.query.category ? this.$route.query.category : '';
      // this.params.shop = this.$route.query.shop ? this.$route.query.shop : '';
      // this.params.keyword = this.$route.query.keywords ? this.$route.query.keywords : '';
    },

    /*
     * getProductList: 获取商品列表
     * @param：  ispush ？ true ：false 是否需要向商品列表追加数据
     */
    getProductList(ispush) {
      productList(this.params)
        .then(
          res => {
            this.buildData(ispush, res)
          },
          err => {
            err.errorCode == 400 && (this.isMore = false)
          }
        )
        .finally(() => {
          this.loading = false
        })
    },

    /*
     *  getList: 构建数据
     *  @param: ispush 是否改变向元数据追加数据
     *  @param: res 接口请求返回的数据
     */
    buildData(ispush, res) {
      if (ispush) {
        this.productList = [...this.productList, ...res.list]
      } else {
        this.productList = res.list
      }

      this.isMore = res.paged.more == 1 ? true : false
      if (res.list.length < 10) {
        this.isMore = false
      }
    },

    /*
        setLocal: 历史搜索
       */
    setLocal(key) {
      let current = this.utils.fetch('keyword')
      current.push('' + key + '')
      current = this.utils.arrayUnique(current)
      this.utils.save('keyword', current)
    },

    /*
     * setParamsByData 根据事件传递的值来对请求列表重新赋值
     * @param data 事件传递的参数
     */
    setParamsByData(data) {
      let params = this.params
      for (let item in params) {
        for (let list in data) {
          if (item == list) {
            params[item] = data[list]
          }
        }
      }
      return params
    }
  }
}
</script>

<style lang="scss" scoped>
  .toast-title{
    width: 100%;
    height: 278px;
    background: url("../../assets/image/yuan/yuan_0.png") no-repeat;
    background-size: contain;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-around;
    p{
      font-size:14px;
      font-family:PingFangSC-Regular,PingFang SC;
      font-weight:400;
      color:rgba(173,130,107,1);
      line-height:20px;
      margin: 20px 0;
    }
    .message-text{
      margin-top: 10px;
      h4{
        font-size:14px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:400;
        color:rgba(173,130,107,1);
        line-height:20px;
        margin-bottom: 8px;
      }
      p{
        font-size:14px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:400;
        color:rgba(173,130,107,1);
        line-height:20px;
        margin: 0;
      }
    }
  }
.ui-product {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  overflow: hidden;

  .product-header {
    height: 100px;
    width: 100%;
  }
  div.product-body {
    position: relative;
    width: 100%;
    background-color: #fff;
    flex-grow: 1;
    overflow: auto;
    .loading-wrapper {
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 44px;
      p {
        color: #7c7f88;
        font-size: 12px;
        font-weight: 'Regular';
        padding: 0;
        margin: 0;
      }
      span {
        display: inline-block;
      }
      /deep/ .mint-spinner-triple-bounce-bounce1,
      /deep/ .mint-spinner-triple-bounce-bounce2,
      /deep/ .mint-spinner-triple-bounce-bounce3 {
        background-color: #f0f0f0 !important;
      }
    }
    .wrapper-list-empty {
      background: #ffffff;
      padding: 30% 0 70px;
      text-align: center;
      img {
        width: 135px;
        height: 135px;
      }
      p {
        margin-top: 10px;
        font-size: 18px;
        font-family: PingFangSC-Medium;
        font-weight: 500;
        color: rgba(102, 102, 102, 1);
        line-height: 25px;
      }
    }
  }
}
// .hide-product-list {
//   overflow: hidden;
//   height: 80vh;
// }
// .show-product-list {
//   height: 100%;
//   overflow: auto;
// }
.show-product-model {
  background: rgba(0, 0, 0, 0.5);
  overflow: hidden;
  height: 100%;
  position: fixed;
  top: 100px;
  bottom: 0;
  left: 0;
  right: 0;
}
</style>
