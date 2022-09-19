<template>
  <div class="mlm-prod-list">
    <span class="back-icon" @click="goBack"></span>
    <!-- 商品列表 -->
    <div
      class="product-body scroll-container-keepAlive"
      @scroll="handleScroll"
      v-infinite-scroll="getMore"
      infinite-scroll-distance="10"
    >
      <!-- 无限加载滚动列表 -->
      <div class="clearfix">
        <div class="product-group" v-for="(item, index) in dataListArr">
          <product-body :item="productList[index * 2]" v-bind:key="index * 2" :productId="productList[index * 2].id">
          </product-body>
          <template v-if="productList[index * 2 + 1]">
            <product-body
              :item="productList[index * 2 + 1]"
              v-bind:key="index * 2 + 1"
              :productId="productList[index * 2 + 1].id"
            >
            </product-body>
          </template>
        </div>

        <!-- 表示是否还有更多数据的状态 -->
        <div class="loading-wrapper">
          <p v-if="!isMore && productList.length > 0">没有更多了</p>
          <mt-spinner type="triple-bounce" color="#FD9F21" v-if="isMore"></mt-spinner>
        </div>

        <div class="wrapper-list-empty" v-if="productList.length <= 0 && !isMore">
          <div>
            <img src="../../assets/image/change-icon/empty_goods@2x.png" />
            <p>暂无任何商品</p>
          </div>
        </div>
      </div>
    </div>
    <!-- 回到顶部 -->
    <v-back-top v-if="productList.length > 10" :target="target"></v-back-top>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import productBody from './child/ProductListBody'
import { getMlmProductList } from '../../api/mlm'
import BackTop from '../../components/common/BackTop'
export default {
  name: 'mlmProducts',
  components: {
    productBody,
    'v-back-top': BackTop
  },
  data() {
    return {
      params: {
        page: 0,
        per_page: 10
      },
      productList: [], //商品列表
      loading: false, //是否正在加载数据 防止并发加载
      isMore: true //是否有更多
    }
  },

  // keepAlive 被唤醒时
  activated() {
    this.handleScroll()
  },

  computed: {
    dataListArr() {
      let count = Math.ceil(this.productList.length / 2)
      return new Array(count)
    }
  },

  created() {
    this.fetchItzBondAuthCheck()
  },

  mounted() {
    this.handleScroll()
    // 计算内容高度
    this.target = document.querySelector('.product-body')
  },

  methods: {
    ...mapMutations({
      setIsTop: 'SET_IS_TOP'
    }),
    ...mapActions({
      fetchItzBondAuthCheck: 'fetchItzBondAuthCheck'
    }),
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
     * getProductList: 获取商品列表
     * @param：  ispush ？ true ：false 是否需要向商品列表追加数据
     */
    getProductList(ispush) {
      getMlmProductList(this.params).then(res => {
        this.buildData(ispush, res)
      })
    },

    /*
     *  getList: 构建数据
     *  @param: ispush 是否改变向元数据追加数据
     *  @param: res 接口请求返回的数据
     */
    buildData(ispush, res) {
      this.loading = false

      if (res) {
        if (ispush) {
          this.productList = [...this.productList, ...res.list]
        } else {
          this.productList = res.list
        }

        this.isMore = res.paged.more == 1 ? true : false
        if (res.list.length < 10) {
          this.isMore = false
        }
      }
    },

    goBack() {
      // history.length <= 1 ? this.$router.push({ name: 'home' }) : this.$_goBack()
      this.$router.push({ name: 'home' })
    },

    handleScroll(event) {
      const dom = (event && event.target) || document.querySelector('.product-body')

      // 告知原生App是否isTop
      if (this.isHHApp) {
        if (dom.scrollTop <= 0) {
          this.setIsTop(true)
        } else {
          this.setIsTop(false)
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.mlm-prod-list {
  height: 100%;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  overflow: hidden;

  span.back-icon {
    position: absolute;
    z-index: 1;
    left: 10px;
    top: 10px;
    width: 31px;
    height: 31px;
    background-image: url('../../assets/image/change-icon/back_bg@3x.png');
    background-size: 100%;
    background-repeat: no-repeat;
  }

  div.product-body {
    position: relative;
    width: 100%;
    flex-grow: 1;
    background-color: #ffffff;
    overflow: auto;
    .clearfix {
      padding-top: 150px;
      background-color: #ffffff;
      background-image: url('../../assets/image/hh-icon/c0-goods/mlm-list-bg.png');
      background-repeat: no-repeat;
      background-size: 100%;
      background-position: top center;
    }
    .product-group {
      display: flex;
      justify-content: space-between;
      padding: 0 15px;
    }
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
      display: flex;
      justify-content: center;
      align-content: center;
      align-items: center;
      padding-top: 45%;
      div {
        display: flex;
        flex-direction: column;
        align-items: center;
        img {
          width: 75px;
          height: 75px;
        }
        p {
          text-align: center;
          margin-top: 27px;
          color: #a4aab3;
        }
      }
    }
  }
}
</style>
