<template>
  <div class="list">
  
  
    <div class="list-box" @click="goTransfer">
      <span>我的转让</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div>
     <div class="list-box" @click="goExclusive">
      <span>专属收购记录</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div>
    <!-- <div class="list-box" @click="goDebt">
      <span>可用债权</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div> -->
    <div class="list-box" @click="goSubscription">
      <span>我的认购</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div>
     <div class="list-box" @click="goBankCard">
      <span>银行卡信息</span>
      <img src="../../../assets/image/mine/right-3.png" alt="" />
    </div>
   

  </div>

  
</template>

<script>
export default {
  name: 'MineList',
  methods: {
   
    goTransfer() {
      this.$router.push({ name: 'mytransfer'})
    },
    goExclusive() {
      this.$router.push({ name: 'myexclusive'})
    },
    goSubscription() {
      this.$router.push({ name: 'mysubscription' })
    },
     goBankCard() {
      this.$router.push({ name: 'bankCard' })
    },
  }
}
</script>

<style lang="less" scoped>
.list {
  background-color: #fff;
  padding: 1px 15px 0 15px;
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
      color: #5B5B5B	;
      line-height: 20px;
    }
    img {
      width: 7px;
      height: 13px;
    }
  }
}
</style>
