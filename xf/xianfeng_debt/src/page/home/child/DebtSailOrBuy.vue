<template>
  <div class="data-display-wrapper">
    <van-tabs class="display-vant-tabs" v-model="selected" line-width="63" line-height="2">
      <van-tab title="债权转让">
        <debt-list></debt-list>
      </van-tab>
      <van-tab title="债权求购">
        <purchase-list></purchase-list>
      </van-tab>
    </van-tabs>
  </div>
</template>

<script>
import DebtList from './DebtList'
import PurchaseList from './PurchaseList'

export default {
  name: 'DebtSailOrBuy',
  data() {
    return {
      selected: 0
    }
  },
  components: {
    DebtList,
    PurchaseList,
  },
  methods: {
    isBreak() {
      return this.$emit('verified-modal')
    }
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
