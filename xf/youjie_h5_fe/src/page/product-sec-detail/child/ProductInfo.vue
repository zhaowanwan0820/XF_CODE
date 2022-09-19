<template>
  <!-- 商品 -->
  <div class="commodity-wrapper">
    <!-- 轮播图 -->
    <info-goods-swipe></info-goods-swipe>
    <!-- 秒杀倒计时 -->
    <seckill-countdown></seckill-countdown>
    <!-- 商品信息 -->
    <info-item></info-item>
    <!-- 商家服务标签 -->
    <!-- <info-serve-tag></info-serve-tag> -->
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
import SeckillCountdown from './SeckillCountdown'
import InfoItem from './InfoItem'
import InfoServeTag from './InfoServeTag'
import InfoBuy from './InfoBuy'
import InfoGoodsReview from './InfoGoodsReview'
import InfoSupplierMsg from './InfoSupplierMsg'
import RecommendItem from './RecommendItem'

import { mapState, mapMutations } from 'vuex'

export default {
  data() {
    return {}
  },

  created() {},

  components: {
    InfoGoodsSwipe,
    InfoItem,
    InfoBuy,
    InfoSupplierMsg,
    InfoGoodsReview,
    InfoServeTag,
    RecommendItem,
    SeckillCountdown
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    })
  },

  mounted() {},

  methods: {}
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
