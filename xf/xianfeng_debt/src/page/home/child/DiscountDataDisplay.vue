<template>
  <div class="data-display-wrapper">
    <van-tabs class="display-vant-tabs" v-model="selected" line-width="63" line-height="2">
      <van-tab title="近15笔转让折扣">
        <discount-last30></discount-last30>
      </van-tab>
      <van-tab title="日平均折扣趋势">
        <discount-data-by-day></discount-data-by-day>
      </van-tab>
    </van-tabs>
  </div>
</template>

<script>
import DiscountLast30 from './DiscountLast30'
import DiscountDataByDay from './DiscountDataByDay'

export default {
  name: 'DiscountDataDisplay',
  data() {
    return {
      selected: 0
    }
  },
  components: {
    DiscountLast30,
    'discount-data-by-day': DiscountDataByDay
  }
}
</script>

<style lang="less" scoped>
.data-display-wrapper {
  margin-top: 10px;
  background-color: #fff;
}
.display-vant-tabs {
  /deep/ .van-tabs__wrap {
    height: 40px;
  }
  /deep/ .van-hairline--top-bottom:after,
  /deep/ .van-hairline-unset--top-bottom:after {
    border-top-width: 0;
  }
}
</style>
