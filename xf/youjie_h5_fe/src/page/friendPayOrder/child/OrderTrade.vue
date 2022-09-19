<!-- OrderTrade.vue-->
<template>
  <div class="container">
    <!-- header -->
    <mt-header class="header" title="交易成功">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <!-- body -->
    <div class="order-trade-body">
      <img src="../../../assets/image/change-icon/g1_icon_pay_ok@2x.png" />
      <p>交易成功，卖家将收到您的货款！</p>
      <div>
        <button v-on:click="goOrderComment">立即评价</button>
        <button v-on:click="goOrderDetail">订单详情</button>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../../components/common'
import { Header } from 'mint-ui'
import { ENUM } from '../../../const/enum'
export default {
  methods: {
    goBack() {
      this.$router.push({ name: 'order', params: { order: ENUM.ORDER_STATUS.DELIVERIED } })
    },
    // 去订单详情
    goOrderDetail() {
      this.$router.push({ name: 'orderDetail', query: { id: this.$route.query.id, isSuccess: true } })
    },
    // 去评价订单
    goOrderComment() {
      this.$router.push({ name: 'orderComment', query: { order: this.$route.query.id } })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  height: 100%;
  .header {
    @include header;
  }
  .order-trade-body {
    vertical-align: middle;
    text-align: center;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    img {
      width: 75px;
      height: 75px;
    }
    p {
      height: 16px;
      font-size: 16px;
      font-family: 'PingFangSC-Regular';
      color: rgba(124, 127, 136, 1);
      line-height: 16px;
      text-align: center;
      padding-bottom: 28px;
    }
    button {
      width: 120px;
      height: 44px;
      background: #772508;
      border-radius: 4px;
      margin: 0 10px;
      border: none;
      font-size: 16px;
      font-family: 'PingFangSC-Regular';
      color: rgba(255, 255, 255, 1);
      line-height: 22px;
    }
  }
}
</style>
