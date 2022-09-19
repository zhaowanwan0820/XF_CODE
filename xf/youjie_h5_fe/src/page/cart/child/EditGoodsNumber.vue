<!-- Popup.vue -->
<template>
  <mt-popup class="mint-popup" v-model="showPopup" position="center" v-bind:close-on-click-modal="false">
    <div class="content">
      <div class="mint-msgbox-content">
        <div class="mint-msgbox-message">修改购买数量</div>
        <div class="mint-msgbox-input">
          <div class="reduce-number" @click="reduce"><div>-</div></div>
          <input type="text" v-model="gNumber" @input="correct" />
          <div class="add-number" @click="add"><div>+</div></div>
        </div>
      </div>
      <div class="mint-msgbox-btns">
        <button class="mint-msgbox-btn mint-msgbox-cancel" @click="closePopup">取消</button>
        <button class="mint-msgbox-btn mint-msgbox-confirm" @click="edit">确认</button>
      </div>
    </div>
  </mt-popup>
</template>

<script>
export default {
  data() {
    return {
      showPopup: true,
      gNumber: this.goods.amount
    }
  },
  props: {
    goods: Object
  },
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
      this.showPopup = false
      this.$emit('close')
    },
    edit() {
      if (this.gNumber == '' || this.gNumber < 1) {
        this.gNumber = 1
      }
      this.gNumber = Math.floor(this.gNumber)
      this.$emit('commit', this.gNumber)
      this.$emit('close')
    },
    correct(event) {
      let val = parseInt(event.target.value, 10)
      if (isNaN(val)) {
        val = ''
      }
      this.gNumber = val
    }
  }
}
</script>

<style lang="scss" scoped>
.mint-popup {
  width: 80%;
  border-radius: 8px;
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
.mint-msgbox-confirm {
  background-color: #772508;
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
