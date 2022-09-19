<template>
  <!-- 商品 -->
  <div class="commodity-wrapper">
    <!-- 分销客 说 -->
    <seller-say></seller-say>

    <!-- 轮播图 -->
    <info-goods-swipe></info-goods-swipe>
    <!-- 商品信息 -->
    <info-item></info-item>

    <!-- 商家服务标签 -->
    <!-- <info-serve-tag></info-serve-tag> -->

    <!-- 首单返现 -->
    <info-return-cash
      v-if="detailInfo.red_cash_back && (detailInfo.red_cash_back.name || detailInfo.red_cash_back.max_num > 0)"
      :returnCash="detailInfo.red_cash_back.max_num"
    ></info-return-cash>

    <!-- 商品销售属性（属性&数量） -->
    <info-buy v-if="detailInfo && detailInfo.properties && detailInfo.properties.length"></info-buy>

    <!-- 商品售罄时 显示app下载入口 -->
    <to-app-store></to-app-store>
  </div>
</template>

<script>
import SellerSay from './SellerSay'
import InfoGoodsSwipe from '../../product-detail/child/InfoGoodsSwipe'
import InfoReturnCash from '../../product-detail/child/InfoReturnCash'
import InfoItem from './InfoItem'
import InfoServeTag from '../../product-detail/child/InfoServeTag'
import InfoBuy from '../../product-detail/child/InfoBuy'
import ToAppStore from './ToAppStore'

import { mapState, mapMutations } from 'vuex'

export default {
  data() {
    return {}
  },
  components: {
    SellerSay,
    InfoGoodsSwipe,
    InfoReturnCash,
    InfoItem,
    InfoBuy,
    InfoServeTag,
    ToAppStore
  },
  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
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
