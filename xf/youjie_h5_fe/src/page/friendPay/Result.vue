<template>
  <div class="page-wrapper clearfix">
    <template>
      <div class="result">
        <img v-if="success" class="result-icon" src="../../assets/image/hh-icon/b10-pay/pay-success@3x.png" />
        <img v-if="!success" class="result-icon" src="../../assets/image/hh-icon/b10-pay/pay-fail@3x.png" />
        <p class="title">{{ resultTxt }}</p>
        <p class="result-desc">{{ resultDesc }}</p>
      </div>
      <div class="btn">
        <button @click="goWhere">{{ btnTxt }}</button>
      </div>
      <div class="pay-notice">
        <dl>
          <dt>代付订单提示</dt>
          <dd>
            1.如果该笔订单因申请人取消、支付超时取消或发生退货/售后服务，订单已支付的各个部分将会按照原支付方式返回。
          </dd>
          <dd>
            2.您可以在{{ utils.storeName }}APP，【我的】-【{{
              utils.mlmUserName
            }}】-【好友代付】列表中，查看全部帮助好友代付的订单。未参与代付的订单不会展示。
          </dd>
        </dl>
      </div>
    </template>
  </div>
</template>
<script>
import { Toast, Indicator } from 'mint-ui'
import { mapState, mapMutations, mapActions } from 'vuex'
import { friendPayResultGet } from '../../api/friendPay'

export default {
  data() {
    return {
      isSuccess: this.$route.params.isSuccess,
      msg: this.$route.params.msg || ''
    }
  },
  computed: {
    success() {
      return this.isSuccess == 1 ? true : false
    },
    resultTxt() {
      return this.success ? '支付成功' : '支付失败'
    },
    resultDesc() {
      return this.success ? '让好友确认订单状态吧' : decodeURIComponent(this.msg)
    },
    btnTxt() {
      return this.success ? '查看代付订单' : '更多商品推荐'
    }
  },
  methods: {
    goWhere() {
      let path = this.success
        ? { path: '/friendPayOrderDetail', query: { id: this.$route.params.orderId } }
        : { path: '/' }
      this.$router.replace(path)
    }
  }
}
</script>
<style lang="scss" scoped>
.page-wrapper {
  min-height: 100%;
  background: rgba(255, 255, 255, 1);
}

.result {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-direction: column;
  padding-top: 63px;

  img {
    width: 60px;
  }
  .title {
    font-size: 24px;
    font-weight: 400;
    color: rgba(51, 51, 51, 1);
    line-height: 33px;
    margin-top: 20px;
  }
  .result-desc {
    font-size: 14px;
    font-weight: 400;
    color: rgba(181, 182, 182, 1);
    line-height: 20px;
    margin-top: 14px;
  }
}

.btn {
  margin: 50px 28px 0;

  button {
    display: block;
    height: 46px;
    border-radius: 2px;
    border: 1px solid rgba(85, 46, 32, 1);
    width: 100%;
    background: rgba(255, 255, 255, 1);
    line-height: 46px;
    font-size: 18px;
    font-weight: 400;
    color: rgba(85, 46, 32, 1);
  }
}

.pay-notice {
  padding: 40px 28px 0;
  font-size: 12px;
  font-weight: 300;
  color: rgba(153, 153, 153, 1);
  line-height: 17px;

  dt {
    font-size: 13px;
    font-weight: 400;
    color: rgba(102, 102, 102, 1);
    line-height: 18px;
  }
  dd {
    margin-left: 0;
    margin-top: 9px;
  }
}
</style>
