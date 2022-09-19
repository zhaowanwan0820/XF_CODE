<!-- Popup.vue -->
<template>
  <mt-popup class="mint-popup" v-model="isShowauth" v-bind:close-on-click-modal="false">
    <div class="popup-wrapper">
      <div class="wrapper">
        <img src="../../assets/image/hh-icon/icon-授权弹窗.svg" class="icon-header" />
        <p>积分换物需授权<br />审核预计1-3日 请提前完成</p>
        <gk-button type="primary" v-on:click="goAuthPath" class="button">了解授权相关问题</gk-button>
        <img src="../../assets/image/hh-icon/icon-授权关闭按钮.svg" class="icon-close" @click="closePopup" />
      </div>
    </div>
  </mt-popup>
</template>

<script>
const routerNameStatus = {
  product: '购买喜欢的商品',
  cart: '购买喜欢的商品',
  profile: '兑换积分'
}

// 3.18 调研结束关闭问卷星
// const wjx = 'https://www.wjx.top/jq/35078672.aspx'

import { mapMutations, mapState } from 'vuex'
import Button from './Button'
export default {
  components: {
    'gk-button': Button
  },
  props: {
    isShowauth: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    ...mapMutations({
      closeAuthPopup: 'closeAuthPopup'
    }),
    closePopup() {
      this.closeAuthPopup()
      // window.location.href = wjx
    },
    goAuthPath() {
      this.closeAuthPopup()
      this.$router.replace({ name: 'authPage' })
    }
  }
}
</script>

<style lang="scss" scoped>
.mint-popup {
  border-radius: 8px;
}
.popup-wrapper {
  width: 275px;
  position: relative;
}
.wrapper {
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  padding: 102px 20px 30px;
}
.icon-header {
  width: 154px;
  height: 154px;
  margin: 0 auto;
  position: absolute;
  left: 0;
  right: 0;
  top: -52px;
  z-index: -1;
}
.icon-close {
  width: 50px;
  height: 50px;
  margin: 0 auto;
  position: absolute;
  left: 0;
  right: 0;
  bottom: -62px;
}
p {
  color: $baseColor;
  font-size: 17px;
  line-height: 24px;
  text-align: center;
}
.button {
  @include button($radius: 20px, $margin: 30px 0 0);
  font-size: 15px;
}
</style>
