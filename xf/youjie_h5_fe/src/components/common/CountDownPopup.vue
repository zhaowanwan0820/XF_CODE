<template>
  <mt-popup class="mint-popup" v-model="showPopup" position="center" v-bind:close-on-click-modal="true">
    <div class="content">
      <div class="mint-msgbox-content">
        <div class="mint-msgbox-message">{{ timeTxt }}</div>
        <div class="mint-msgbox-input" style="display: none;">
          <input placeholder="" type="text" />
          <div class="mint-msgbox-errormsg" style="visibility: hidden;"></div>
        </div>
      </div>
      <div class="mint-msgbox-btns">
        <button class="mint-msgbox-btn mint-msgbox-cancel" @click="goLeave">确认离开</button>
        <button class="mint-msgbox-btn mint-msgbox-confirm" @click="closePopup">继续支付</button>
      </div>
    </div>
  </mt-popup>
</template>
<script>
export default {
  data() {
    return {
      showPopup: true,
      timeTxt: '',
      timer: null
    }
  },
  props: {
    canceledTime: {
      type: Number
    }
  },
  created: function() {
    this.countTime()
  },
  methods: {
    countTime() {
      this.timer = setTimeout(() => {
        this.countTime()
      }, 1000)
      this.exportTime()
    },
    exportTime() {
      const restTime = this.canceledTime - Math.floor(new Date().getTime() / 1000)

      if (restTime > 0) {
        const minite = Math.floor(restTime / 60)
        const sec = restTime % 60
        this.timeTxt = '您的订单在' + minite + '分' + sec + '秒内未完成支付将被取消，请尽快完成支付！'
      } else {
        clearTimeout(this.timer)
        this.timeTxt = '该订单已失效'
      }
    },
    closePopup() {
      this.$emit('showFlag', false)
    },
    goLeave() {
      this.$emit('leavePage', false)
    }
  }
}
</script>

<style lang="scss" scoped>
.mint-popup {
  border-radius: 3px;
  width: 80%;
  overflow: hidden;
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
