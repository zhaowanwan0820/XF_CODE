<!-- Popup.vue -->
<template>
  <div>
    <div class="mint-msgbox" style="">
      <div class="mint-msgbox-content">
        <div class="mint-msgbox-message">{{ phone }}</div>
        <div class="mint-msgbox-input" style="display: none;">
          <input placeholder="" type="text" />
          <div class="mint-msgbox-errormsg" style="visibility: hidden;"></div>
        </div>
      </div>
      <div class="mint-msgbox-btns">
        <button class="mint-msgbox-btn mint-msgbox-cancel" @click="cancelPhone">取消</button>
        <button class="mint-msgbox-btn mint-msgbox-confirm" @click="cancelPhone">
          <a class="tel-link" :href="'tel:' + phone">呼叫</a>
        </button>
      </div>
    </div>
    <mt-popup class="mint-popup" v-model="test" v-bind:close-on-click-modal="false"> </mt-popup>
  </div>
</template>

<script>
export default {
  name: 'ContactSupplier',
  data() {
    return {
      test: true,
      orderRestTime: 0
    }
  },
  props: {
    phone: String
  },
  created() {},
  methods: {
    cancelPhone() {
      this.$emit('cancelPhone', false)
    }
  }
}
</script>

<style lang="scss" scoped>
.mint-msgbox {
  z-index: 9999;
}
.mint-msgbox-message {
  font-size: 20px;
  color: $baseColor;
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
.tel-link {
  color: #ffffff;
  display: block;
  line-height: 50px;
}
.mint-msgbox-btn:active .tel-link {
  color: #fd9f21;
}
</style>
