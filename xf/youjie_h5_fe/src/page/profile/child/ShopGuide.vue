<template>
  <div class="container">
    <mt-header class="header" title="购物指南">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
    </mt-header>
    <div class="body-wrapper">
      <div class="guide-header">
        <label>步骤</label>
        <label>具体操作</label>
      </div>
      <div class="guide-body section-header">
        <label>第1步</label>
        <h5>登录</h5>
        <p>使用爱投资注册手机登录，同意协议，完成登录</p>
      </div>
      <div class="guide-body">
        <label>第2步</label>
        <h5>授权</h5>
        <p>签署债权转让协议并上传手持身份证照片，通过审核</p>
      </div>
      <div class="guide-body">
        <label>第3步</label>
        <h5>兑换积分</h5>
        <p>选择债权兑换成商城积分</p>
      </div>
      <div class="guide-body">
        <label>第4步</label>
        <h5>选择商品</h5>
        <p>选择您想要购买的商品</p>
      </div>
      <div class="guide-body">
        <label>第5步</label>
        <h5>提交订单</h5>
        <p>选择配送方式和收货地址，用积分支付订单</p>
      </div>
      <div class="guide-body">
        <label>第6步</label>
        <h5>查看订单状态</h5>
        <p>准备收货</p>
      </div>
      <div class="guide-body section-footer">
        <label>第7步</label>
        <h5>收货后评价</h5>
        <p>收货确认后可以评价商品</p>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../../components/common'
import { Header } from 'mint-ui'
export default {
  name: 'shopGuide',
  methods: {
    goBack() {
      this.$_goBack()
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
  .header {
    @include header;
  }
  .body-wrapper {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: stretch;
    font-size: 14px;
    background-color: #fff;
    margin: 10px;
    border-radius: 4px;
    overflow: hidden;
  }
  .guide-header {
    background-color: #a3c6db;
    color: #fff;
    height: 40px;
    line-height: 40px;
    label {
      padding-right: 53px;
      padding-left: 26px;
    }
  }
  .guide-body {
    background-color: #fff;
    border-radius: 4px;
    border: 1px solid rgba(163, 198, 219, 1);
    margin-top: 10px;
    margin-left: 28px;
    margin-right: 9px;
    padding: 10px 10px 10px 28px;
    position: relative;
    label {
      line-height: 18px;
      color: #fff;
      font-size: 11px;
      background-color: #a3c6db;
      border-radius: 11px;
      position: absolute;
      top: 9px;
      left: -18px;
      padding: 1px 6px;
    }
    h5 {
      font-size: 14px;
      color: $baseColor;
      line-height: 20px;
    }
    p {
      font-size: 13px;
      color: $subbaseColor;
      line-height: 19px;
      margin-top: 4px;
    }
  }
  .section-header {
    margin-top: 27px;
  }
  .section-footer {
    margin-bottom: 21px;
  }
}
</style>
