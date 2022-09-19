<!-- 提现 -->
<template>
  <div class="detail-wrapper">
    <bill-item title="时间" :subTitle="detail.created_at"></bill-item>
    <bill-item title="交易单号" :subTitle="detail.order_sn"></bill-item>
  </div>
</template>

<script>
import BillItem from './BillItem'
export default {
  name: 'BillDetailWithdraw',
  data() {
    return {}
  },

  props: ['detail'],

  components: {
    BillItem
  }
}
</script>

<style lang="scss" scoped>
.detail-wrapper {
}
</style>
