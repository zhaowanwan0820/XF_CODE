<template>
  <div class="success-comtainer">
    <div class="head">
      <img src="../../assets/image/choose/success.png" alt="" />
      <span>操作成功</span>
    </div>
<!--    <transfer-item :title="'债转成功'" :result="transferBuyResult.success" type="success"></transfer-item>-->
<!--    <transfer-item :title="'债转失败'" :result="transferBuyResult.data.fail" type="fail"> </transfer-item>-->
    <div class="btn">
      <van-button type="primary" class="primary" @click="$router.push({name:'mydebt'})">查看我的债转</van-button>
      <van-button @click="goBack">返回首页</van-button>
    </div>
  </div>
</template>
<script>
import { mapState, mapMutations } from 'vuex'
import TransferItem from './child/TransferSuccessItem'
export default {
  data() {
    return {
      pur_id: this.$route.params.id
    }
  },
  computed: {
    ...mapState({
      transferBuyResult: state => state.transfer.transferBuyResult
    })
  },
  components: {
    TransferItem
  },
  methods: {
    ...mapMutations({
      // clearResult: 'clearTransferBuyResult'//暂时先不清空
    }),
    goBack() {
      this.$router.push('/')
    }
  }
}
</script>

<style lang="less" scoped>
.success-comtainer {
  background-color: #f4f4f4;
}
.head {
  font-size: 0;
  height: 227px;
  background-color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  img {
    width: 61px;
    height: 61px;
    padding-top: 61px;
  }
  span {
    margin-top: 20px;
    font-size: 24px;
    color: #333;
    line-height: 33px;
  }
}
.btn {
  display: flex;
  align-items: center;
  flex-direction: column;
  padding-bottom: 25px;
  button {
    width: 280px;
    height: 45px;
    border-radius: 6px;
    margin-top: 20px;
    &.primary {
      margin-top: 26px;
    }
  }
}
</style>
