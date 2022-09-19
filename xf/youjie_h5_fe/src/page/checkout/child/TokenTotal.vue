<template>
  <div class="token-wrapper" v-show="ifShowExchange">
    <div class="token-item">
      <div class="left">
        <span class="token-name">权益：</span>
        <span class="token">积分</span>
      </div>
      <label>{{ utils.formatFloat(currentAvailabel) }}可用</label>
    </div>
    <div class="change" v-if="ifShowExchange" @click="goChange">
      <span>兑换积分</span>
      <img src="../../../assets/image/hh-icon/c10-checkout/icon-right.png" alt="" />
    </div>
  </div>
</template>

<script>
export default {
  name: 'TokenTotal',
  data() {
    return {}
  },
  props: {
    currentAvailabel: Number,
    ifShowExchange: Boolean
  },
  methods: {
    goChange() {
      this.$emit('goExchange')
    }
  }
}
</script>

<style lang="scss" scoped>
.token-wrapper {
  margin-top: 10px;
  padding: 14px 15px;
  background-color: #fff;
  @include thin-border(#f4f4f4, 15px);
  .token-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    .left {
      font-size: 0;
      span {
        font-size: 13px;
        font-weight: 300;
        color: #404040;
        line-height: 18px;
      }
      .token {
        margin-left: 5px;
        color: #666;
      }
    }
    label {
      font-size: 13px;
      font-weight: 300;
      color: #404040;
      line-height: 18px;
    }
  }
  .change {
    margin-top: 9px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    span {
      font-size: 12px;
      font-weight: 300;
      color: #772508;
      line-height: 17px;
    }
    img {
      margin-left: 7px;
      @include wh(7px, 11px);
    }
  }
}
</style>
