<template>
  <div class="container">
    <mt-header class="header" title="提现结果">
      <header-item
        slot="left"
        isLeft
        v-if="withdrawStatus == FAILED"
        :icon="require('../../assets/image/hh-icon/detail/icon-close@3x.png')"
        class="header-close"
        v-on:onclick="goBack"
      ></header-item>
      <header-item
        slot="right"
        v-if="withdrawStatus == SUCCEED"
        titleColor="#552E20"
        title="完成"
        v-on:onclick="goBack"
      ></header-item>
    </mt-header>
    <div class="content">
      <div class="result-top">
        <template v-if="withdrawStatus == SUCCEED">
          <img class="pay-result-icon" src="../../assets/image/hh-icon/b10-pay/pay-success@3x.png" />
          <div class="result-title">提现成功</div>
          <p>以支付宝实际到账时间为准</p>
        </template>
        <template v-if="withdrawStatus == FAILED">
          <img class="pay-result-icon" src="../../assets/image/hh-icon/b10-pay/pay-fail@3x.png" />
          <div class="result-title">提现失败</div>
          <p>{{ errorMsg }}</p>
        </template>
      </div>
      <div class="result-msg">
        <div class="msg-item">
          <span class="title">提现金额</span>
          <span class="sub-title">￥{{ utils.formatMoney(amount) }}</span>
        </div>
        <div class="msg-item">
          <span class="title">服务费</span>
          <span class="sub-title">￥{{ utils.formatMoney(service_fee) }}</span>
        </div>
        <div class="msg-item">
          <span class="title">到账支付宝账号</span>
          <span class="sub-title">{{ payee_account }}</span>
        </div>
      </div>
      <div class="button-wrapper" v-if="withdrawStatus == FAILED">
        <gk-button class="button" type="primary-secondary" v-on:click="reWithdraw">重新提交</gk-button>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { WITHDRAW_STATUS } from './static'
export default {
  data() {
    return {
      SUCCEED: WITHDRAW_STATUS.SUCCEED,
      FAILED: WITHDRAW_STATUS.FAILED
    }
  },

  created() {},

  computed: {
    ...mapState({
      payee_account: state => state.withdraw.payee_account,
      service_fee: state => state.withdraw.service_fee,
      amount: state => state.withdraw.amount,
      withdrawStatus: state => state.withdraw.withdrawStatus,
      errorMsg: state => state.withdraw.errorMsg
    })
  },

  methods: {
    goBack() {
      this.$_goBack()
    },

    reWithdraw() {
      this.$router.replace({ name: 'huankeWithdraw' })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.header {
  @include thin-border();
  .header-close /deep/ .icon {
    width: 16px;
    height: 16px;
    margin-left: 5px;
  }
}
.content {
  flex: 1;
  .result-top {
    text-align: center;
    padding: 0 30px 30px;
    background: #ffffff;
    .pay-result-icon {
      width: 60px;
      height: 60px;
      margin-top: 63px;
    }
    div {
      @include sc(24px, #333333);
      line-height: 33px;
      margin-top: 20px;
    }
    p {
      @include sc(14px, #b5b6b6);
      line-height: 20px;
      margin-top: 14px;
    }
  }
  .result-msg {
    margin-top: 10px;
    background-color: #ffffff;
    .msg-item {
      padding: 0 15px;
      height: 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .title {
      @include sc(14px, #707070);
    }
    .sub-title {
      @include sc(14px, #404040);
    }
  }
  .button-wrapper {
    text-align: center;
    margin-top: 50px;
    button {
      @include button($margin: 0, $radius: 2px, $spacing: 1px);
      width: 327px;
      font-size: 18px;
      color: #fff;
    }
  }
}
</style>
