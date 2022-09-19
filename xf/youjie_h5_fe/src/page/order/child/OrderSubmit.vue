<!-- OrderSubmit.vue -->
<template>
  <div class="container">
    <!-- header -->
    <mt-header class="header" title="评价成功">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
    </mt-header>
    <!-- body -->
    <div class="order-trade-body">
      <img src="../../../assets/image/hh-icon/comment/icon-success.png" />
      <span>评价成功</span>
      <label>感谢您坚持写真实、内容丰富的评价</label>
      <button v-on:click="goOrder"><label>返回首页</label></button>
    </div>
    <recommend-list></recommend-list>
  </div>
</template>

<script>
import { HeaderItem } from '../../../components/common'
import { Header } from 'mint-ui'
import { ENUM } from '../../../const/enum'
import RecommendList from '../../recommend/RecommendList'
export default {
  components: {
    RecommendList
  },
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
  height: 100%;
  overflow-y: auto;
  .header {
    @include header;
  }
  .order-trade-body {
    display: flex;
    align-items: center;
    flex-direction: column;
    background-color: #fff;
    padding-bottom: 70px;
    img {
      @include wh(135px, 135px);
      margin-top: 32px;
    }
    span {
      font-size: 18px;
      font-weight: 500;
      color: #666;
      line-height: 25px;
      margin-top: 14px;
    }
    label {
      font-size: 14px;
      font-weight: 400;
      color: #b5b6b6;
      line-height: 20px;
      margin-top: 14px;
    }
    button {
      width: 140px;
      height: 36px;
      background-color: #fff;
      border-radius: 2px;
      border: 1px solid rgba(85, 46, 32, 1);
      margin-top: 40px;
      label {
        font-size: 14px;
        font-weight: 400;
        color: #552e20;
        line-height: 36px;
      }
    }
  }
}
</style>
