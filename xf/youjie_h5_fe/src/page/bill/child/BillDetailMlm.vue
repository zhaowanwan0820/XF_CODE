<!-- 分销订单 -->
<template>
  <div class="detail-wrapper">
    <bill-item title="分销订单号" :subTitle="detail.order_sn"></bill-item>
    <bill-item title="分销商品名" :subTitle="detail.goods_name"></bill-item>
    <div class="line"></div>
    <bill-item title="分销购买人" :subTitle="detail.buyer_name"></bill-item>
    <bill-item title="分销物流状态" :subTitle="detail.shipping_status"></bill-item>
    <bill-item title="下单时间" :subTitle="detail.add_time"></bill-item>
    <bill-item title="支付时间" :subTitle="detail.pay_time"></bill-item>
  </div>
</template>

<script>
import BillItem from './BillItem'
export default {
  name: 'BillDetailMlm',
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
  .line {
    height: 10px;
    background-color: rgba(244, 244, 244, 1);
    margin: 20px 0;
  }
}
</style>
