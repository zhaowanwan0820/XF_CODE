<template>
  <div class="container">
    <mt-header class="header" title="购买成功">
      <div class="complete-header-item" slot="right" @click="goBack">
        完成
      </div>
    </mt-header>
    <div class="wrapper">
      <div class="content">
        <!-- <img class="icon-success" v-if="this.isExchange" src="../../assets/image/change-icon/h2_icon_gift@2x.png" /> -->
        <img class="icon-success" src="../../assets/image/hh-icon/b10-pay/pay-success@3x.png" />
        <!-- <label class="title" v-if="this.isExchange">恭喜您，兑换成功</label> -->
        <label class="title">购买成功</label>
      </div>
      <div class="btns">
        <gk-button class="button" type="primary-secondary-white" v-on:click="goPaid">查看订单</gk-button>
        <gk-button class="button" type="primary-secondary-white" v-on:click="goShopping">继续购物</gk-button>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem, Button } from '../../components/common'
import { mapState, mapMutations, mapActions } from 'vuex'
import { Header, MessageBox } from 'mint-ui'
import { ENUM } from '../../const/enum'
export default {
  data() {
    return {
      // isExchange: this.$route.query.isExchange ? this.$route.query.isExchange : false,
      order_status_paid: ENUM.ORDER_STATUS.PAID
    }
  },
  created() {
    // 下车礼包支付后更新状态
    this.fetchUserInfos()
  },
  methods: {
    ...mapMutations({
      changeStatus: 'changeStatus'
    }),
    ...mapActions({
      fetchUserInfos: 'fetchUserInfos'
    }),
    goBack() {
      this.changeStatus(this.order_status_paid)
      this.$router.push({ name: 'order', params: { order: this.order_status_paid } })
    },
    // goDetail() {
    //   let order = this.$cookie.get('orderid');
    //   let orderId = order ? order : null;
    //   this.$router.push({ name: 'orderDetail', query: {id: orderId, isFromPay: true }})
    // },
    goPaid() {
      this.changeStatus(this.order_status_paid)
      this.$router.push({ name: 'order', params: { order: this.order_status_paid } })
    },
    goShopping() {
      this.$router.push('/home')
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  background-color: #fff;
}
.header {
  @include header;
  border-bottom: 1px solid $lineColor;
  .complete-header-item {
    color: #552e20;
    font-size: 16px;
  }
}
.wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.content {
  display: flex;
  flex-direction: column;
  text-align: justify;
  justify-content: center;
  align-items: center;
  padding: 60px 25px 0;
  .icon-success {
    width: 62px;
    height: 62px;
  }
  .title {
    color: #404040;
    font-size: 18px;
    margin-top: 20px;
    line-height: 1.5;
  }
}
.btns {
  margin-top: 50px;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 25px;
  .button {
    width: 100%;
    font-size: 18px;
    @include button($margin: 0, $radius: 2px, $spacing: 2px);
    & + .button {
      margin-top: 25px;
    }
  }
}
</style>
