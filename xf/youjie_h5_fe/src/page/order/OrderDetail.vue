<!--OrderDetail -->
<template>
  <div class="container">
    <mt-header class="header" title="订单详情">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBackPage"></header-item>
    </mt-header>
    <order-detail-body @onloaded="getOrde"></order-detail-body>
    <count-down-popup
      @showFlag="getShowFlag"
      @leavePage="leavePay"
      :canceledTime="canceledTime"
      v-if="showflag"
    ></count-down-popup>
  </div>
</template>

<script>
import { Header } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import OrderDetailBody from './child/OrderDetailBody'
import CountDownPopup from '../../components/common/CountDownPopup'
import { ENUM } from '../../const/enum'
import { ORDEREFFRCTTIME, SECKILLORDEREFFRCTTIME } from './static'
export default {
  data() {
    return {
      showflag: false,
      status: -1,
      canceledTime: 0,
      order: {}
    }
  },
  beforeRouteLeave(to, from, next) {
    if (to.name == 'profile') {
      next()
    }

    if (to.name == 'payResult') {
      next({ name: 'profile' })
    } else {
      next()
    }
  },
  components: {
    OrderDetailBody,
    CountDownPopup
  },
  methods: {
    goBackPage() {
      const order_restTime = this.canceledTime - Math.floor(new Date().getTime() / 1000)
      // 分期
      if (this.order && this.order.instalment && this.order.instalment.length) {
        this.goBack()
        return
      }

      if (this.status == 0 && order_restTime > 0) {
        this.showflag = true
      } else {
        this.goBack()
      }
    },
    getShowFlag(value) {
      this.showflag = value
    },
    leavePay(value) {
      this.showflag = value
      this.goBack()
    },
    goBack() {
      let isFromPay = this.$route.query.isFromPay
      let isSuccess = this.$route.query.isSuccess
      let id = this.$route.query.id ? this.$route.query.id : ''
      if (isFromPay) {
        this.$router.push({ name: 'order', params: { order: ENUM.ORDER_STATUS.PAID } })
      } else if (isSuccess) {
        this.$router.push({ name: 'orderTrade', query: { id: id } })
      } else {
        this.$_goBack()
      }
    },
    getOrde(res) {
      this.order = res
      this.status = res.status
      this.canceledTime = res.canceled_at
    }
  }
}
</script>

<style lang="scss" scoped>
.header {
  @include header;
  @include thin-border();
  // top: 0;
  // right: 0;
  // left: 0;
  // position: fixed;
  // z-index: 1;
  // border-bottom: 1px solid #e8eaed;
  // background: #fff;
  // border: 0;
  // color: #fff;
}
</style>
