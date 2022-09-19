<template>
  <div class="detail-project-container">
    <van-row>
      <van-col :span="20" class="bond-name">{{ info.name }}</van-col>
      <van-col :span="4">
        <span v-if="info.status==2" class="has-debt" type="text">债转中</span>
      </van-col>
    </van-row>
<!--    <van-row class="row-second">-->
<!--      <van-col :span="6" class="project-name">合同编号：</van-col>-->
<!--      <van-col class="project-num">{{ info.bond_no }}</van-col>-->
<!--    </van-row>-->
    <van-row class="row-second" :gutter="10">
      <van-col class="project-name" :span="6">待还本金：</van-col>
      <van-col class="project-num" :span="10">{{ info.wait_capital }}</van-col>
      <!-- <van-col class="btn-box" :span="8">
        <van-button class="no-debt" v-if="info.status==1" type="primary" @click="debtTransfer(info.deal_load_id,info.products)">发布转让</van-button>
        <van-button class="disable-debt" v-else disabled type="default">发布转让</van-button>
      </van-col> -->
    </van-row>
  </div>
</template>

<script>
import { mapState } from 'vuex'
export default {
  name: 'projectItem',
  props: ['info'],
  computed: {
    ...mapState({
      authAgreement: state => state.auth.authAgreement
    })
  },
  methods: {
    debtTransfer(id,products) {
      // 只展示已确权的项目，2019.11.27, 该版本中不做风险评级，所以只判断是否同意债转协议
      if (!this.authAgreement) {
        this.$dialog
          .confirm({
            message: '请先阅读并同意“债转服务协议”',
            confirmButtonText: '去阅读',
            cancelButtonText: '稍后'
          })
          .then(() => {
            this.$router.push({ name: 'debtAgreement' })
          })
        return
      }
      this.$router.push({ name: 'release', query: { id: id ,products:products,status:'1'} })
    }
  }
}
</script>

<style lang="less" scoped>
.detail-project-container {
  padding: 15px;
  background-color: #fff;
  margin-top: 10px;
  box-sizing: border-box;
  /deep/ .van-row {
    height: 21px;
    line-height: 21px;
  }
  .bond-name {
    color: #333;
  }
  .project-name,
  .project-num {
    color: #404040;
  }
  .project-name {
    font-size: 14px;
    font-weight: 300;
  }
  .project-num {
    font-size: 15px;
    font-weight: bold;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }
  .row-second {
    margin-top: 10px;
  }
  .btn-box {
    text-align: right;
  }
  .has-debt {
    color: @themeColor;
    display: inline-block;
    text-align: right;
    width: 100%;
  }
  /deep/ .van-button {
    height: 30px;
    width: 80px;
    padding: 0;
    line-height: 30px;
    border-radius: 2px;
  }
  .disable-debt{
    background-color: #D7D7D7;
    color: #fff;
  }
}
</style>
