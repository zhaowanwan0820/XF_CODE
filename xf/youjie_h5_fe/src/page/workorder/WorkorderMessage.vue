<!--workOrderList -->
<template>
  <div class="container">
    <mt-header class="header" title="申请详情">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack()"></header-item>
      <header-item v-if="showcolse" slot="right" title="关闭申请" v-on:onclick="closeOrder()"></header-item>
    </mt-header>
    <work-message-body ref="orderSn"></work-message-body>
  </div>
</template>

<script>
import { Header, MessageBox, Toast } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import WorkMessageBody from './MessageBody'

export default {
  data() {
    return {
      wor_id: this.$route.params.id ? this.$route.params.id : '',
      status: this.$route.params.status ? this.$route.params.status : '',
      showcolse: false
    }
  },
  components: {
    WorkMessageBody
  },
  created() {
    this.as()
  },
  methods: {
    as() {
      this.status == 2 ? (this.showcolse = false) : (this.showcolse = true)
    },
    goBack() {
      let orderSn = this.$refs.orderSn.list.workOrder.orderSn
      let back = this.$route.params.from ? true : false
      if (back) {
        this.$_goBack()
      } else {
        this.$router.replace({ name: 'WorkorderList', params: { id: orderSn } })
      }
    },
    closeOrder() {
      let orderSn = this.$refs.orderSn.list.workOrder.orderSn
      MessageBox.confirm('', {
        message: '是否已处理完您的反馈，若未处理完请勿关闭申请',
        title: '是否关闭此申请',
        confirmButtonText: '确定',
        cancelButtonText: '取消'
      }).then(action => {
        if (action == 'confirm') {
          //确认的回调
          this.$api.put('workOrder/' + this.wor_id, null, r => {
            // console.log(r)
            if (r.code == 1) {
              this.list = r.data
              this.$router.replace({ name: 'WorkorderList', params: { id: orderSn } })
            } else {
              Toast(r.msg)
            }
          })
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.header {
  @include header;
  border-bottom: 1px solid #ddd;
}
.mint-msgbox {
  font-size: 14px;
}
.mint-msgbox-message {
  font-size: 12px;
}
.title {
  font-size: 16px;
  color: #552e20;
}
</style>
