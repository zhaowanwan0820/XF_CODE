<!-- Popup.vue -->
<template>
  <div>
    <div class="mint-msgbox" style="">
      <div class="mint-msgbox-content">
        <div class="mint-msgbox-message">修改购买数量</div>
        <div class="mint-msgbox-input">
          <div class="reduce-number" @click="reduce"><div>-</div></div>
          <input type="text" v-model="gNumber" @input="correct(gNumber)" />
          <div class="add-number" @click="add"><div>+</div></div>
        </div>
      </div>
      <div class="mint-msgbox-btns">
        <button class="mint-msgbox-btn mint-msgbox-cancel" @click="closePopup">取消</button>
        <button class="mint-msgbox-btn mint-msgbox-confirm" @click="edit">确认</button>
      </div>
    </div>
    <mt-popup class="mint-popup" v-model="isShowauth" v-bind:close-on-click-modal="false"> </mt-popup>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isShowauth: true,
      gNumber: this.goodsNumber.amount
    }
  },
  props: {
    goodsNumber: {
      type: Object
    }
  },
  created: function() {},
  methods: {
    add() {
      this.gNumber++
    },
    reduce() {
      if (this.gNumber == 1) {
        return false
      }
      this.gNumber--
    },
    closePopup() {
      this.$emit('hidefooter', false)
      this.$emit('showFlag', false)
    },
    edit() {
      if (this.gNumber == '') {
        this.gNumber = 1
      }
      this.gNumber = Math.floor(this.gNumber)
      this.goodsNumber.amount = this.gNumber
      this.$emit('gNumber', this.goodsNumber)
      this.$emit('hidefooter', false)
      this.$emit('showFlag', false)
    },
    correct(num) {
      this.gNumber = num.replace(/[^0-9]/g, '')
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
.mint-msgbox-input {
  width: 100%;
  display: flex;
  justify-content: center;
  .reduce-number,
  .add-number {
    width: 48px;
    height: 48px;
    border: 1px solid #f2f3f4;
    display: flex;
    justify-content: center;
    align-items: center;
    div {
      width: 40px;
      height: 40px;
      font-size: 40px;
      color: #cfd0d1;
      margin-top: -5px;
      display: flex;
      justify-content: center;
      align-items: center;
    }
  }
  input {
    width: 95px;
    height: 48px;
    padding: 0;
    text-align: center;
    border-radius: 0;
    border: 0;
    border-top: 1px solid #f2f3f4;
    border-bottom: 1px solid #f2f3f4;
    font-size: 22px;
    color: #404040;
  }
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
.mint-msgbox-message {
  font-size: 18px;
  font-weight: 500;
  color: #404040;
  line-height: 25px;
}
.mint-msgbox-content {
  padding: 21px 20px 30px;
}
</style>
