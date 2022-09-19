<!-- OrderSubmit.vue -->
<template>
  <div class="container">
    <!-- header -->
    <mt-header class="header" title="评价成功">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
    </mt-header>
    <!-- body -->
    <div class="order-trade-body">
      <img src="../../../assets/image/change-icon/g1_comments_ok@2x.png" />
      <p>商品评价成功，感谢您给予的评价。</p>
      <div>
        <button v-on:click="goOrder">再去逛逛</button>
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
    goOrder() {
      this.$router.push('home')
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
      margin-top: 27px;
      margin-bottom: 24px;
    }
    button {
      width: 200px;
      height: 44px;
      background: $primaryColor;
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
