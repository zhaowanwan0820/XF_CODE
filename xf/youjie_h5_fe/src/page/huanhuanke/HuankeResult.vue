<template>
  <div class="page-wrapper clearfix">
    <mt-header class="header" title="支付结果">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goHuanOrderList"></header-item>
      <header-item slot="right" titleColor="#552E20" title="完成" v-on:onclick="goHuanOrderList"></header-item>
    </mt-header>
    <template>
      <div class="result">
        <img class="result-icon" src="../../assets/image/hh-icon/b10-pay/pay-success@3x.png" />
        <p class="title">支付成功</p>
        <p class="result-desc">佣金已经即时到账</p>
      </div>
      <div class="btn">
        <button @click="goOrderDetail">查看分销订单</button>
      </div>
      <div class="pay-notice">
        <dl>
          <dt>提示</dt>
          <dd>
            1.买家【确认收货】7天后，该笔订单的代付佣金将可提现；
          </dd>
          <dd>
            2.如果买家在上述时间点前，发生订单取消、退款、退货等情况，则代付积分、代付佣金等需原路退回。
          </dd>
        </dl>
      </div>
    </template>
  </div>
</template>
<script>
import { Header } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import { mapState } from 'vuex'

export default {
  name: 'HuanKeResult',
  data() {
    return {
      id: this.$route.params.id ? this.$route.params.id : null
    }
  },
  computed: {
    ...mapState({
      orderStatus: state => state.order.orderStatus
    })
  },
  methods: {
    goOrderDetail() {
      this.$router.replace({ name: 'HuankeOrderDetail', query: { id: this.id } })
    },
    goHuanOrderList() {
      this.$router.replace({ name: 'HuankeOrder', params: { order: this.orderStatus } })
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
