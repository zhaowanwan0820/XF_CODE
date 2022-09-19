<template>
  <div class="order-trade-recommend" v-infinite-scroll="getMore" infinite-scroll-distance="10" v-if="list.length">
    <div class="title">猜你喜欢</div>
    <!-- 无限加载滚动列表 -->
    <div class="clearfix">
      <div class="product-group" v-for="(items, index) in showData">
        <template v-for="(item, itemIndex) in items">
          <recommend-list-body
            :item="item"
            v-bind:key="index * 2 + itemIndex"
            v-stat="{ id: `shopcart_recommend_${index}_${itemIndex}` }"
          >
          </recommend-list-body>
        </template>
      </div>

      <!-- 表示是否还有更多数据的状态 -->
      <div class="loading-wrapper">
        <p v-if="!isMore && showData.length > 0">没有更多了</p>
        <mt-spinner type="triple-bounce" color="#FD9F21" v-if="isMore"></mt-spinner>
      </div>

      <div class="wrapper-list-empty" v-if="showData.length <= 0 && !isMore">
        <div>
          <img src="../../assets/image/change-icon/empty_goods@2x.png" />
          <p>暂无任何商品</p>
        </div>
      </div>
    </div>
    <!-- 回到顶部 -->
    <v-back-top v-if="list.length > 0 && scrollDom" :target="target" :bottom="bottom"></v-back-top>
  </div>
</template>

<script>
import RecommendListBody from './child/RecommendListBody'
import { getRecommendList } from '../../api/recommend'
import BackTop from '../../components/common/BackTop'
export default {
  name: 'RecommendList',
  data() {
    return {
      list: [],
      param: {
        page: 0,
        per_page: 10
      },
      isMore: true,
      isLoadding: false,
      target: null,
      show: false
    }
  },
  props: ['params', 'scrollDom'],
  computed: {
    showData() {
      let arr = []
      const forArr = new Array(Math.ceil(this.list.length / 2))
      for (var i = 0; i < forArr.length; i++) {
        let subArr = []
        subArr.push(this.list[i * 2])
        if (this.list[i * 2 + 1]) {
          subArr.push(this.list[i * 2 + 1])
        }
        arr.push(subArr)
      }
      return arr
    },
    bottom() {
      let bottom = 20
      if (this.$route.name === 'cart') {
        bottom += 50
      }
      if (this.$route.meta.isshowtabbar) {
        bottom += 50
      }
      return bottom
    }
  },
  created() {
    this.getMore()
  },
  mounted() {
    this.$nextTick(() => {
      this.target = document.querySelector(`.${this.scrollDom}`)
    })
  },
  components: {
    RecommendListBody,
    'v-back-top': BackTop
  },
  methods: {
    getMore() {
      if (this.loading) return

      // console.log('getMore Triggered...')

      if (this.isMore) {
        this.param.page = ++this.param.page
        this.loading = true

        this.getProductList(true)
      }
    },

    /*
     * getProductList: 获取商品列表
     * @param：  ispush ？ true ：false 是否需要向商品列表追加数据
     */
    getProductList(ispush) {
      getRecommendList(this.param).then(res => {
        this.loading = false

        if (ispush) {
          this.list = [...this.list, ...res.list]
        } else {
          this.list = res.list
        }

        this.isMore = res.paged.more == 1 ? true : false
        if (res.list.length < this.param.per_page) {
          this.isMore = false
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.order-trade-recommend {
  margin-top: 20px;
  background-color: #ffffff;
  border-radius: 10px;
  padding: 15px 25.5px 0;
  position: relative;
  .title {
    font-size: 14px;
    margin-bottom: 15px;
    font-family: PingFangSC-Medium;
    font-weight: 500;
    color: #fc7f0c;
    text-align: center;
  }
  .product-group {
    display: flex;
    justify-content: flex-start;
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
    padding-bottom: 20px;
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
</style>
