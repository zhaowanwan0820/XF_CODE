<!-- Popup.vue -->
<template>
  <div>
    <div class="mint-msgbox" style="">
      <div class="mint-msgbox-content">
        <div class="mint-msgbox-message">{{ txt }}</div>
        <div class="mint-msgbox-input" style="display: none;">
          <input placeholder="" type="text" />
          <div class="mint-msgbox-errormsg" style="visibility: hidden;"></div>
        </div>
      </div>
      <div class="mint-msgbox-btns">
        <button class="mint-msgbox-btn mint-msgbox-cancel" @click="checkOrder">查看订单</button>
        <button class="mint-msgbox-btn mint-msgbox-confirm" @click="payAgain">继续支付</button>
      </div>
    </div>
    <mt-popup class="mint-popup" v-model="isShowPayPop" v-bind:close-on-click-modal="false"> </mt-popup>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isShowPayPop: true,
      orderRestTime: 0,
      txt: '正在前往支付...'
    }
  },
  props: ['isNoSurplus'],
  created() {
    setTimeout(() => {
      if (this.isNoSurplus) {
        this.txt = '请完成支付'
      } else {
        this.txt = '积分已支付，请继续支付剩余部分'
      }
    }, 2000)
  },
  methods: {
    checkOrder() {
      this.$emit('checkOrder', false)
    },
    payAgain() {
      this.$emit('payAgain', false)
    }
  }
}
</script>

<style lang="scss" scoped>
.mint-msgbox {
  z-index: 9999;
}
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
