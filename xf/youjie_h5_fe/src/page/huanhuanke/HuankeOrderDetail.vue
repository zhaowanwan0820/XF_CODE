<!--OrderDetail -->
<template>
  <div class="container">
    <mt-header class="header" title="订单详情">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBackPage"></header-item>
    </mt-header>
    <order-detail-body></order-detail-body>
  </div>
</template>

<script>
import { Header } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import OrderDetailBody from './child/OrderDetailBody'
import { ENUM } from '../../const/enum'
export default {
  components: {
    OrderDetailBody
  },
  methods: {
    goBackPage() {
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
    }
  }
}
</script>

<style lang="scss" scoped>
.header {
  @include header;
}
</style>
