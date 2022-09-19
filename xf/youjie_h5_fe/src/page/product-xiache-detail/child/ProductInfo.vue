<template>
  <!-- 商品 -->
  <div class="commodity-wrapper">
    <!-- 轮播图 -->
    <info-goods-swipe></info-goods-swipe>

    <!-- 预售信息 -->
    <info-pre-sale v-if="detailInfo && isPreSale"></info-pre-sale>

    <!-- 商品信息 -->
    <info-item></info-item>
    <!-- 促销信息 -->
    <!-- <info-promotions
      v-if="!this.isExchange && detailInfo && detailInfo.promos && detailInfo.promos.length > 0"
    ></info-promotions> -->
    <!-- 促销商品的活动结束时间 -->
    <!-- <info-promos-time></info-promos-time> -->

    <!-- 商家服务标签 -->
    <info-serve-tag></info-serve-tag>

    <!-- 购买 -->
    <info-buy v-if="detailInfo && detailInfo.properties && detailInfo.properties.length"></info-buy>

    <!-- 评论 -->
    <info-goods-review></info-goods-review>

    <!-- 商家信息 -->
    <info-supplier-msg></info-supplier-msg>

    <!-- 猜你喜欢商品  -->
    <recommend-item></recommend-item>
  </div>
</template>

<script>
import InfoGoodsSwipe from './InfoGoodsSwipe'
import InfoPreSale from './InfoPreSale'
import InfoItem from './InfoItem'
import InfoBuy from './InfoBuy'
import InfoSupplierMsg from './InfoSupplierMsg'
import InfoGoodsReview from './InfoGoodsReview'
import InfoServeTag from './InfoServeTag'
import RecommendItem from './RecommendItem'

import { mapState } from 'vuex'

export default {
  data() {
    return {}
  },
  components: {
    InfoGoodsSwipe,
    InfoPreSale,
    InfoItem,
    InfoBuy,
    InfoSupplierMsg,
    InfoGoodsReview,
    InfoServeTag,
    RecommendItem
  },

  computed: {
    ...mapState({
      // isExchange: state => state.score.isExchange,
      detailInfo: state => state.detail.detailInfo,
      isPreSale: state => state.detail.isPreSale
    })
  }
}
</script>

<style lang="scss" scoped>
.commodity-wrapper {
  // height: 100%;
  overflow-x: hidden;
}
.line {
  height: 10px;
  background-color: $mainbgColor;
}
</style>
