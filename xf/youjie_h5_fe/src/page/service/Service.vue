<template>
  <div class="service-wrapper">
    <mt-header class="header" title="联系客服">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="service-content">
      <div class="card">
        <img src="../../assets/image/hh-icon/s9-service/icon-service.png" />
        <div class="time-wrpper">
          <div class="name">网信相关产品确权客服电话</div>
          <div class="phone">010-8992-0001</div>
          <div class="time">工作时间:工作日9:00-18:00</div>
        </div>
        <a :href="`tel:${isIos ? '//' : ''}01089920001`">立即拨打</a>
      </div>
      <div class="wchat-wrapper">
        <div>
          <div class="top">
            <img src="../../assets/image/hh-icon/s9-service/icon-scan.png" />
            <span>扫码进入有解公众号</span>
          </div>
          <div class="bottom">
            <img src="../../assets/image/hh-icon/s9-service/icon-wchat.png" />
            <span>保存图片中的二维码，使用微信“扫一扫”功能进行扫描，进入有解官方公众号</span>
          </div>
        </div>
      </div>
      <div class="q-a">
        <div class="title">常见问题</div>
        <q-a-item v-for="(item, index) in qa" :key="index" :item="item"></q-a-item>
      </div>
    </div>
  </div>
</template>

<script>
import QAItem from './child/QAItem'
import { Q_A } from './static'
export default {
  data() {
    return {
      isIos: 1 == this.utils.getOpenBrowser() ? true : false,
      qa: Q_A
    }
  },

  components: {
    QAItem
  },

  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.service-wrapper {
  height: 100%;
  width: 100%;
  display: flex;
  flex-direction: column;
}
.header {
  @include thin-border();
}
.service-content {
  flex: 1;
  overflow-y: auto;
  padding: 15px;
  box-sizing: border-box;
  background-color: #ffffff;
  .card {
    width: 345px;
    height: 85px;
    background-image: url('../../assets/image/hh-icon/s9-service/service-bg.png');
    background-size: 100%;
    background-repeat: no-repeat;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: space-between;
    img {
      width: 46px;
      height: 47px;
      margin-left: 22px;
    }
    .time-wrpper {
      margin-left: 13px;
      color: #ffffff;
      flex: 1;
      .name{
        font-size: 12px;
        line-height: 1.5;
      }
    }
    .phone {
      font-size: 14px;
      line-height: 1.5;
    }
    .time {
      font-size: 12px;
      line-height: 1.5;
      margin-top: 8px;
    }
    a {
      margin-right: 20px;
      border-radius: 14px;
      height: 27px;
      background: rgba(255, 150, 54, 1);
      box-shadow: 0px 0px 9px 0px rgba(255, 28, 0, 0.37);
      font-size: 12px;
      line-height: 27px;
      letter-spacing: 1px;
      font-weight: 400;
      padding: 0 10px;
      color: #ffffff;
      &:hover,
      &:active,
      &:visited {
        color: #ffffff;
      }
    }
  }
  .wchat-wrapper {
    width: 345px;
    background-color: #fffdf9;
    border-radius: 8px;
    position: relative;
    margin-top: 29px;
    min-height: 169px;
    &:before {
      content: '';
      display: block;
      position: absolute;
      width: 200%;
      height: 200%;
      top: 50%;
      left: 50%;
      border-radius: 16px;
      transform: translate(-50%, -50%) scale(0.5);
      border: 1px solid rgba(255, 166, 84, 0.15);
      z-index: 1;
    }
    & > div {
      box-sizing: border-box;
      position: absolute;
      width: 100%;
      height: 100%;
      left: 0;
      top: 0;
      z-index: 2;
      padding: 0 17px;
    }
    .top {
      display: flex;
      align-items: center;
      @include thin-border(rgba(255, 150, 54, 0.25), 0, 0, true) img {
        width: 76px;
        margin-left: 9px;
      }
      span {
        @include sc(18px, #404040);
        margin-left: 39px;
      }
    }
    .bottom {
      display: flex;
      align-items: center;
      margin-top: 8px;
      img {
        width: 91px;
        margin-left: 2px;
      }
      span {
        @include sc(12px, #999999);
        line-height: 2;
        margin-left: 31px;
        width: 162px;
        text-align: justify;
      }
    }
  }
  .q-a {
    margin-top: 35px;
    .title {
      @include sc(14px, #999999);
      text-align: center;
      padding-bottom: 23px;
      line-height: 1.5;
      @include thin-border(rgba(85, 46, 32, 0.2), 0, 0, true);
    }
  }
}
</style>
