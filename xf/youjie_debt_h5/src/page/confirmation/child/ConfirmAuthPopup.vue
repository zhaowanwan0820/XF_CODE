<template>
  <mt-popup v-model="popupVisible" popup-transition="popup-fade">
    <div class="popup-container">
      <h4>确权协议</h4>
      <img src="../../../assets/image/hh-icon/confirmation/icon-close.png" class="close" @click="close" alt="" />
      <div class="content" @scroll="scrollEvent" ref="content">
        <div class="content-item gray">
          <p>协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权</p>
        </div>
        <div class="content-item">
          <p>
            1. 我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议。
            <br />2.
            我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议。<br />
            3.
            我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议。<br />
            1. 我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议。
            <br />2.
            我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议。<br />
            3.
            我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议我是确权协议。<br />
          </p>
        </div>
      </div>
      <div class="btn" :class="{ opacity: !hasRead }">
        <button @click="agree">同意并继续</button>
      </div>
      <p class="desc">请认真阅读《XXX确权协议》确权协议，滑动滑块到 底部进行确认。</p>
    </div>
  </mt-popup>
</template>
<script>
import { mapState, mapGetters, mapMutations } from 'vuex'
export default {
  data() {
    return {
      popupVisible: false,
      hasRead: false
    }
  },
  watch: {
    popupVisible(val) {
      if (!val) this.setShowConfirmPopup(val)
      this.$refs.content.scrollTop = 0
      this.hasRead = false
    },
    isShowConfirmPopup(val) {
      if (val) this.popupVisible = val
    }
  },
  computed: {
    ...mapState({
      isShowConfirmPopup: state => state.confirmation.isShowConfirmPopup
    })
  },
  methods: {
    ...mapMutations({
      setHasConfirm: 'setHasConfirm',
      setShowConfirmPopup: 'setShowConfirmPopup'
    }),
    close() {
      this.popupVisible = false
    },
    agree() {
      if (!this.hasRead) return
      // 暂时注释保存确权状态代码
      // this.setHasConfirm(true)
      this.close()
      this.$emit('confirm')
    },
    scrollEvent(e) {
      let target = e.target
      if (target.scrollTop + target.clientHeight - target.scrollHeight <= 10) this.hasRead = true
    }
  }
}
</script>

<style lang="scss" scoped>
.mint-popup {
  border-radius: 8px;
}
.popup-container {
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  width: 315px;
  height: 500px;
  background: rgba(255, 255, 255, 1);
  border-radius: 8px;
  padding: 15px 27px 10px;
  h4 {
    font-size: 18px;
    font-weight: 500;
    color: #333;
    line-height: 25px;
  }
  .close {
    position: absolute;
    width: 12px;
    height: 12px;
    top: 15px;
    right: 15px;
  }
  .content {
    flex: 1;
    overflow: auto;
    margin-top: 15px;
    .content-item {
      margin-bottom: 10px;
      &.gray p {
        color: #666;
      }
      p {
        font-size: 14px;
        font-weight: 400;
        color: #333;
        line-height: 24px;
      }
    }
  }
  .btn {
    margin-top: 17px;
    &.opacity {
      opacity: 0.3;
    }
    button {
      width: 270px;
      height: 44px;
      background: $primaryColor;
      border-radius: 4px;

      font-size: 17px;
      font-weight: 400;
      color: #fff;
      line-height: 44px;
    }
  }
  .desc {
    margin-top: 11px;
    font-size: 12px;
    font-weight: 400;
    color: #999;
    line-height: 17px;
  }
}
</style>
