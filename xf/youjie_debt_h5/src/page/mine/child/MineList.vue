<template>
  <div class="list">
    <!-- 暂无 -->
    <!-- <div class="list-box">
      <span>资产明细</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div> -->
    <div class="list-box" @click="goRecharge">
      <span>充值</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div>
    <div class="list-box" @click="goWithdrawal">
      <span>提现</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div>
    <div class="list-box" @click="goTransfer">
      <span>我的转让</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div>
    <div class="list-box" @click="goSub">
      <span>我的认购</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div>
  </div>
</template>

<script>
export default {
  name: 'MineList',
  methods: {
    goRecharge() {
      this.$router.push({ name: 'recharge' })
    },
    goWithdrawal() {
      this.$router.push({ name: 'withdrawal' })
    },
    goTransfer() {
      this.$router.push({ name: 'mytransfer' })
    },
    goSub() {
      this.$router.push({ name: 'mysubscription' })
    }
  }
}
</script>

<style lang="less" scoped>
.list {
  padding: 51px 15px 0 15px;
  .list-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 19px;
    margin-top: 20px;
    border-bottom: 1px solid rgba(233, 233, 233, 1);
    span {
      height: 20px;
      font-size: 16px;
      font-family: PingFangSC-Medium, PingFang SC;
      font-weight: 500;
      color: rgba(64, 64, 64, 1);
      line-height: 20px;
    }
    img {
      width: 7px;
      height: 13px;
    }
  }
}
</style>
