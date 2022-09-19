<template>
  <div class="container">
    <mt-header class="header" :title="`关于${utils.storeNameForShort}`">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="img-wrapepr">
      <img src="../../assets/image/hh-icon/f0-profile/logo-about@3x.png" alt="" />
      <p class="version">版本号： v{{ getVersion }}</p>
    </div>
    <div class="button-wrapper">
      <button @click="toScore">去评分</button>
      <button @click="appUpdate">软件更新</button>
    </div>
  </div>
</template>
<script>
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
export default {
  name: 'About',
  computed: {
    getVersion() {
      if (this.hhApp.getAppVersion()) {
        return this.hhApp.getAppVersion()
      } else {
        return '0.1.0'
      }
    }
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    toScore() {
      this.hhApp.toScore()
    },
    appUpdate() {
      this.hhApp.appUpdate()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  width: 100%;
  height: 100%;
  background-color: #fff;
  .img-wrapepr {
    width: 375px;
    height: 375px;
    background: url('../../assets/image/hh-icon/f0-profile/about-bg@3x.png') no-repeat 0 0;
    background-size: cover;
    text-align: center;

    img {
      width: 80px;
      margin-top: 98px;
    }
    .version {
      margin-top: -55px;
      color: #999;
      font-size: 13px;
      line-height: 18px;
    }
  }
  .button-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 46px;
    button {
      width: 140px;
      height: 36px;
      color: #552e20;
      font-size: 14px;
      line-height: 20px;
      background-color: #fff;
      border-radius: 2px;
      border: 1px solid #552e20;
      margin-bottom: 25px;
      outline: none;
    }
  }
}
</style>
