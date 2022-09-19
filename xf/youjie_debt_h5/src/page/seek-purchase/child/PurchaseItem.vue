<template>
  <div class="detail-purchase-container">
    <!-- body -->
    <van-row>
      <van-col :span="12" class="discount">
        <span class="discount-num">{{ utils.formatFloat(info.discount) }}</span
        ><span class="discount-unit">折</span>
      </van-col>
      <van-col :span="12" class="btn-box">
        <van-button type="primary" v-if="info.operable" @click="goTransfer(info.pur_id)">发起转让</van-button>
      </van-col>
    </van-row>
    <van-row class="money-num"> 计划求购金额: ￥{{ utils.formatMoney(info.money) }} </van-row>
    <van-row class="money-num"> 剩余求购金额: ￥{{ utils.formatMoney(info.money - info.acquired_money) }} </van-row>
    <van-row class="valid-time"> 剩余有效期: {{ utils.getSurplusTime(info.expiry_time * 1e3) }} </van-row>
  </div>
</template>

<script>
import { mapState } from 'vuex'
export default {
  name: 'purchaseItem',
  props: ['info'],
  computed: {
    ...mapState({
      authAgreement: state => state.auth.authAgreement,
      platInfo: state => state.auth.platInfo
    })
  },
  methods: {
    goTransfer(id) {
      // 是否确权 > 是否完成风险评级 > 是否同意债转协议, 本期2019.11.27不做风险评级
      if (!this.platInfo.confirm_status) {
        this.$dialog
          .confirm({
            message: '您的债权需要在有解债转信息平台进行确权后，方可发起转让，赶紧去确权吧！',
            confirmButtonText: '去确权',
            cancelButtonText: '稍后'
          })
          .then(() => {
            this.$router.push({ name: 'confirmation' })
          })
        return
      }
      if (!this.authAgreement) {
        this.$router.push({ name: 'debtAgreement' })
        return
      }
      this.$router.push({ name: 'transferDebt', params: { id: id } })
    }
  }
}
</script>

<style lang="less" scoped>
.detail-purchase-container {
  margin-bottom: 10px;
  padding: 0 15px 16px;
  height: 157px;
  background-color: #fff;
  box-sizing: border-box;
  .discount {
    font-weight: bold;
    color: @themeColor;
    span {
      display: inline-block;
    }
    .discount-num {
      height: 56px;
      font-size: 40px;
      line-height: 56px;
    }
    .discount-unit {
      margin-left: 2px;
      height: 28px;
      font-size: 20px;
      line-height: 28px;
      position: relative;
      top: -2px;
    }
  }
  .btn-box {
    margin-top: 13px;
    text-align: right;
    /deep/ .van-button {
      height: 30px;
      width: 80px;
      padding: 0;
      line-height: 30px;
      border-radius: 2px;
    }
  }
  .money-num {
    margin-top: 5px;
    height: 20px;
    font-size: 14px;
    font-weight: 400;
    line-height: 20px;
    margin-bottom: 10px;
    color: #404040;
  }
  .valid-time {
    font-size: 13px;
    font-weight: 400;
    color: #29af73;
    margin-top: 12px;
    height: 18px;
    line-height: 18px;
  }
}
</style>
