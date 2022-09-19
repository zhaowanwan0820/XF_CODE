<template>
  <div class="service-wrapper">
    <div class="service-content">
      <div class="card">
        <img src="../../assets/image/confirmation/icon-serve.png" />
        <div class="time-wrpper">
          <div class="name">客服电话：400 0088 100</div>
          <!-- <div class="phone"></div> -->
          <div class="time">工作时间：工作日9:00-18:00</div>
        </div>
        <a :href="`tel:${isIos ? '//' : ''}4000088100`">立即拨打</a>
      </div>
      <!-- <div class="wchat-wrapper">
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
      </div> -->
      <!-- <div class="q-a">
        <div class="title">常见问题</div>
        <q-a-item v-for="(item, index) in qa" :key="index" :item="item"></q-a-item>
      </div> -->
    </div>
  </div>
</template>

<script>
// import QAItem from './child/QAItem'
import { Q_A } from './static'
export default {
  data() {
    return {
      isIos: 1 == this.utils.getOpenBrowser() ? true : false
      // qa: Q_A
    }
  },

  // components: {
  //   QAItem
  // },

  methods: {
    // goBack() {
    //   this.$_goBack()
    // }
  }
}
</script>

<style lang="less" scoped>
.service-wrapper {
  height: 100%;
  width: 100%;
  display: flex;
  flex-direction: column;
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
    background-image: url('../../assets/image/confirmation/service-bg.png');
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
      .name {
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
      // background: rgba(255, 150, 54, 1);
      background-color: @themeColor;
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
}
</style>
