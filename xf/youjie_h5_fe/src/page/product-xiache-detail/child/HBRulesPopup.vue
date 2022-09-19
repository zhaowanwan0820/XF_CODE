<!-- Shopping.vue -->
<template>
  <mt-popup v-model="ShowHBRules" position="bottom" v-bind:close-on-click-modal="true" style="height: 78%;">
    <div class="h-b-container">
      <div class="h-b-header">
        <span>积分</span>
        <img src="../../../assets/image/hh-icon/icon-关闭.png" v-on:click="closeHBPopup(false)" />
      </div>
      <div class="h-b-body">
        积分是第三方公司委托{{ utils.storeName }}平台发行的购物抵现券，持有积分的用户可在购物时用积分抵减商品现金价格。
      </div>
    </div>
  </mt-popup>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import { Toast, MessageBox, Button } from 'mint-ui'

export default {
  data() {
    return {
      ShowHBRules: false
    }
  },

  props: {
    isShowHBRules: {
      type: Boolean,
      default: false
    }
  },

  created() {
    this.ShowHBRules = this.isShowHBRules
  },

  watch: {
    ShowHBRules(value) {
      this.saveHBRulesPopupState(value)
    }
  },

  methods: {
    ...mapMutations({
      saveHBRulesPopupState: 'saveHBRulesPopupState'
    }),

    // 关闭购物车浮层
    closeHBPopup(value) {
      this.saveHBRulesPopupState(value)
    }
  }
}
</script>
<style lang="scss" scoped>
.h-b-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  .h-b-header {
    display: flex;
    justify-content: space-between;
    height: 50px;
    align-items: center;
    padding: 0 15px;
    @include thin-border();
    span {
      color: #404040;
      font-size: 14px;
    }
    img {
      width: 12px;
    }
  }
  .h-b-body {
    flex: 1;
    padding: 20px 15px;
    color: #999999;
    font-size: 13px;
    overflow-y: auto;
  }
}
</style>
