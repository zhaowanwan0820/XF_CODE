<template>
  <div class="bill-container">
    <mt-header class="header" title="账单详情">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="bill-body" v-if="detail.id">
      <bill-detail-head :detail="detail"></bill-detail-head>

      <!-- 分销返佣 -->
      <bill-detail-mlm :detail="detail" v-if="detail.type == ENUM.BILLTYPES.MLM"></bill-detail-mlm>

      <!-- 订单返现 -->
      <bill-detail-return-cash
        :detail="detail"
        v-if="detail.type == ENUM.BILLTYPES.RETURN_CASH"
      ></bill-detail-return-cash>

      <!-- 提现 -->
      <bill-detail-withdraw :detail="detail" v-if="detail.type == ENUM.BILLTYPES.WITHDRAW"></bill-detail-withdraw>

      <!-- 返现撤回 -->
      <bill-detail-cancel :detail="detail" v-if="detail.type == ENUM.BILLTYPES.RECALL"></bill-detail-cancel>
    </div>
  </div>
</template>

<script>
import { ENUM } from '../../const/enum'
import { getBillDetail } from '../../api/bill'
import BillDetailHead from './child/BillDetailHead'
import BillDetailCancel from './child/BillDetailCancel'
import BillDetailMlm from './child/BillDetailMlm'
import BillDetailReturnCash from './child/BillDetailReturnCash'
import BillDetailWithdraw from './child/BillDetailWithdraw'
export default {
  name: 'BillDetail',
  data() {
    return {
      ENUM,
      id: this.$route.query.id,
      detail: {}
    }
  },

  created() {
    this.getDetail()
  },

  components: {
    BillDetailHead,
    BillDetailCancel,
    BillDetailMlm,
    BillDetailReturnCash,
    BillDetailWithdraw
  },

  methods: {
    goBack() {
      this.$_goBack()
    },
    getDetail() {
      getBillDetail(this.id).then(res => {
        this.detail = { ...res }
        console.log(this.detail)
        console.log(this.ENUM.BILLTYPES)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.bill-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  .header {
    @include thin-border();
  }
  .bill-body {
    flex: 1;
    background-color: #ffffff;
  }
}
</style>
