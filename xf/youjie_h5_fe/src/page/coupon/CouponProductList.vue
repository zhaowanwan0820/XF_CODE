<template>
  <div class="container">
    <coupon-search v-show="isSearch"></coupon-search>
    <div class="seach-wrapper" v-show="!isSearch">
      <mt-header class="header" title="可用商品">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        <header-item slot="right" :searchIcon="true" v-on:onclick="changeSearch"></header-item>
      </mt-header>

      <!-- 当前优惠券详情 -->
      <div class="coupon-info-wrapper">
        <div class="coupon-info">
          <p class="name">{{ coupon_info.coupon_name }}</p>
          <p class="desc">{{ coupon_info.period_time }}</p>
        </div>
      </div>

      <!-- 商品筛选 -->
      <product-list-filter ref="filter" :value="params.sort_key"></product-list-filter>

      <!-- 商品列表 -->
      <div class="product-body" v-infinite-scroll="getMore" infinite-scroll-distance="10">
        <!-- 无限加载滚动列表 -->
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
          <div>
            <img src="../../assets/image/change-icon/empty_goods@2x.png" />
            <p>暂无任何商品</p>
          </div>
        </div>
        <div class="show-product-model" v-if="isShowProductModel" @click="closeProductModel"></div>
      </div>
    </div>
  </div>
</template>

<script>
import { Header, Indicator } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import ProductListFilter from '../product-list/child/ProductListFilter'
import ProductBody from '../product-list/child/ProductListBody'
import CouponSearch from './child/CouponSearch'
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { couponGoodsList } from '../../api/coupon'
export default {
  data() {
    return {
      isSearch: false,
      params: {
        coupon_id: this.$route.query.id || '',
        keyword: '',
        sort_key: 0,
        sort_value: 2,
        page: 0,
        per_page: 10
      },
      productList: [],
      loading: false,
      isMore: true
    }
  },
  components: {
    ProductListFilter,
    ProductBody,
    CouponSearch
  },
  created() {
    this.clearCoupon()
    this.$on('change-list', data => {
      document.activeElement.blur()
      let res = data
      this.params.page = 1
      this.productList = []
      this.loading = true
      this.isMore = true
      this.setParamsByData(res)
      this.getList()
    })
  },
  computed: {
    ...mapState({
      isShowProductModel: state => state.product.isShowProductModel,
      coupon_info: state => state.coupon.couponSingleInfo
    })
  },
  methods: {
    ...mapMutations({
      changeIsShowProductModel: 'changeIsShowProductModel',
      clearCoupon: 'clearCoupon'
    }),
    getList(isPush) {
      Indicator.open()
      couponGoodsList(this.params)
        .then(
          res => {
            this.loading = false

            if (isPush) {
              this.productList = [...this.productList, ...res.list]
            } else {
              this.productList = res.list
            }

            this.isMore = res.paged.more == 1 ? true : false
            if (res.list.length < 10) {
              this.isMore = false
            }
          },
          error => {}
        )
        .finally(() => {
          Indicator.close()
        })
    },
    getMore() {
      if (this.loading) return

      if (this.isMore) {
        this.params.page = ++this.params.page
        this.loading = true

        this.getList(true)
      }
    },
    /*
     * closeProductModel: 关闭筛选模态框
     */
    closeProductModel() {
      this.changeIsShowProductModel(false)
      this.$refs.filter.closeFiler()
    },
    changeSearch() {
      this.isSearch = !this.isSearch
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
    },
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.seach-wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.header {
  flex-basis: 44px;
}
.coupon-info-wrapper {
  position: relative;
  width: 100%;
  flex-basis: 115px;
  background: url('../../assets/image/hh-icon/coupon/bg-coupon.png') #fff 0 15px no-repeat;
  background-size: 375px 100px;
  .coupon-info {
    position: absolute;
    top: 51px;
    left: 67px;
    width: 216px;

    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .name {
    color: #552e20;
    font-size: 13px;
    font-weight: 400;
    line-height: 18px;
  }
  .desc {
    font-size: 10px;
    @include sc(10px, rgba(85, 46, 32, 0.6));
    font-weight: 400;
    line-height: 14px;
    white-space: nowrap;
  }
}

.ui-product-filter {
  flex-basis: 50px;
}

.product-body {
  position: relative;
  width: 100%;
  background-color: #fff;
  flex: 1;
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
.show-product-model {
  background: rgba(0, 0, 0, 0.5);
  overflow: hidden;
  height: 100%;
  position: fixed;
  top: 209px;
  bottom: 0;
  left: 0;
  right: 0;
}
</style>
