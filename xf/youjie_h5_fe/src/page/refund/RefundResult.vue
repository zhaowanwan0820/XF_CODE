<template>
  <div class="container">
    <mt-header class="header" title="退款结果">
      <header-item
        v-if="refundStatus == failedStatus"
        slot="left"
        isLeft
        :icon="require('../../assets/image/hh-icon/detail/icon-close@3x.png')"
        v-on:onclick="goBack"
      ></header-item>
      <div class="complete-header-item" slot="right" v-if="refundStatus == succeedStatus" @click="goBack">
        完成
      </div>
    </mt-header>
    <div class="wrapper">
      <div class="content">
        <template v-if="refundStatus == succeedStatus">
          <img class="icon" src="../../assets/image/hh-icon/b10-pay/pay-success@3x.png" />
          <label class="title">退款成功</label>
          <label class="subtitle">预计退款金额将在3个工作日内到账</label>
        </template>
        <template v-else-if="refundStatus == failedStatus">
          <img class="icon" src="../../assets/image/hh-icon/b10-pay/pay-fail@3x.png" />
          <label class="title">退款失败</label>
        </template>
      </div>
      <gk-button class="button left-button" type="primary-secondary-white" v-on:click="goBack">查看订单</gk-button>
    </div>
  </div>
</template>

<script>
import { HeaderItem, Button, PopupShareFriendPay } from '../../components/common'
import { REFUND_STATUS } from './static'
import { mapState, mapMutations } from 'vuex'
export default {
  data() {
    return {
      succeedStatus: REFUND_STATUS.SUCCEED, // 退款成功
      failedStatus: REFUND_STATUS.FAILED // 退款失败
    }
  },

  computed: {
    ...mapState({
      refundStatus: state => state.refund.status
    })
  },

  created() {
    if (this.refundStatus === null) {
      this.$_goBack()
    }
  },

  methods: {
    ...mapMutations({
      saveRefundStatus: 'saveRefundStatus'
    }),

    goBack() {
      this.$_goBack()
    }
  },

  destroyed() {
    this.saveRefundStatus(null)
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
  @include header;
  border-bottom: 1px solid $lineColor;
  .complete-header-item {
    color: #552e20;
    font-size: 16px;
  }
  /deep/ .icon {
    width: 16px;
    height: 16px;
  }
}
.wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  background-color: #ffffff;
  .content {
    display: flex;
    flex-direction: column;
    text-align: justify;
    justify-content: center;
    align-items: center;
    margin-top: 60px;
  }
  .icon {
    width: 60px;
    height: 60px;
  }
  .title {
    color: $baseColor;
    font-size: 18px;
    line-height: 1.4;
    margin-top: 20px;
  }
  .subtitle {
    color: #b5b6b6;
    font-size: 14px;
    margin-top: 10px;
  }
  .button {
    @include button($margin: 23px 20px 28px, $radius: 2px, $spacing: 12px);
  }
}
</style>
