<!-- OrderTrade.vue-->
<template>
  <div class="container">
    <!-- header -->
    <mt-header class="header" fixed title="交易成功"> </mt-header>
    <!-- body -->
    <div class="order-trade-body" ref="scrollTop">
      <div class="order-trade-result">
        <img class="odr-trd-rslt-icon" src="../../../assets/image/hh-icon/b10-pay/pay-success@3x.png" />
        <p class="title">交易成功</p>
        <p class="subtitle">交易成功卖家将收到您的货款</p>
        <div class="btns">
          <gk-button class="button" type="primary-secondary-white" v-on:click="goHome">返回首页</gk-button>
          <gk-button class="button" type="primary-secondary-white" v-on:click="goOrderComment">立即评价</gk-button>
        </div>
      </div>
      <recommend-list :params="params" :scrollDom="'order-trade-body'"></recommend-list>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../../components/common'
import { Header } from 'mint-ui'
import { ENUM } from '../../../const/enum'
import RecommendList from '../../recommend/RecommendList'
export default {
  data() {
    return {
      params: {}
    }
  },
  components: {
    RecommendList
  },
  methods: {
    // 返回首页
    goHome() {
      this.$router.push({ name: 'home' })
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
    @include thin-border();
  }
  .order-trade-body {
    vertical-align: middle;
    flex-grow: 1;
    position: relative;
    width: 100%;
    overflow: auto;
  }
  .order-trade-result {
    background-color: #ffffff;
    padding: 60px 0 70px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    img {
      width: 60px;
      height: 60px;
    }
    p {
      font-family: PingFangSC-Regular;
      font-weight: 400;
      &.title {
        margin-top: 20px;
        font-size: 24px;
        color: rgba(51, 51, 51, 1);
        line-height: 33px;
      }
      &.subtitle {
        font-size: 14px;
        color: rgba(181, 182, 182, 1);
        line-height: 20px;
        margin-top: 15px;
      }
    }
    .btns {
      margin-top: 50px;
      font-size: 0;
      button {
        @include button($margin: 0 15px, $radius: 2px, $spacing: 2px);
        width: 140px;
        height: 36px;
        font-size: 16px;
        font-family: 'PingFangSC-Regular';
      }
    }
  }
}
</style>
