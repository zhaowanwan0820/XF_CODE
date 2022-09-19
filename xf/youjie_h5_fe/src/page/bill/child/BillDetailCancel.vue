<!-- 返现撤回 -->
<template>
  <div class="detail-wrapper">
    <bill-item title="商品名称" :subTitle="detail.goods_name"></bill-item>
    <bill-item title="支付金额" :subTitle="getPayMoney"></bill-item>
    <bill-item title="付款方式" :subTitle="getPayType"></bill-item>
    <div class="line"></div>
    <bill-item title="订单编号" :subTitle="detail.order_sn"></bill-item>
    <bill-item title="物流状态" :subTitle="detail.shipping_status"></bill-item>
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

  computed: {
    getPayMoney() {
      let str = ''
      if (this.detail.goods_amount == this.detail.money_paid) {
        str = this.detail.pay_name
      } else if (this.detail.token_type == 1) {
        str = `￥${this.detail.goods_amount} (￥${this.detail.money_paid}+积分抵扣${this.detail.surplus})`
      } else if (this.detail.token_type == 2) {
        str = `￥${this.detail.goods_amount} (￥${this.detail.money_paid}+H${this.detail.surplus})`
      }
      return str
    },

    getPayType() {
      let str = ''
      if (this.detail.goods_amount > 0) {
        if (this.detail.goods_amount == this.detail.money_paid) {
          str = `${this.detail.pay_name}`
        } else if (this.detail.token_type == 1) {
          str = `${this.detail.pay_name}+积分抵扣`
        } else {
          str = `${this.detail.pay_name}+积分`
        }
      }
      return str
    }
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
