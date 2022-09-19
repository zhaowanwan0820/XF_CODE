<template>
  <div>
    <van-nav-bar :title="title" left-arrow  left-text="返回首页"  @click-left="onClickLeft" />
    <div class="success">
    <img :src="img" alt="" />
    <div class="succ-text">
      <p class="p-title">出售成功</p>
  
    </div>
    <van-button type="info" @click="$router.push({name:'debtMarket'})">继续出售</van-button>
    <div class="btn-gto" @click="$router.push({name:'myAreaDebt'})">查看我的债转</div>
  </div>
  </div>
  
</template>

<script>
export default {
  name: 'SellSuccess',
  data() {
    return {
      img: require('../../assets/image/success/success-1.png'),
      number_text: '--',
      title:"出售成功"
    }
  },
  created() {
    this.number_text = this.$route.params.id
    this.code = this.$route.query.code !=0?this.$route.query.code:false
  },
  methods: {
    onClickLeft(){
      this.$router.push({
        path:'/huiYuanArea'
      })
    }
  }
}
</script>
<style lang="less" scoped>
/deep/ .van-nav-bar__text{
  font-size: 16px;
  font-family: PingFangSC-Light;
  color: gray;
}
.success {
  display: flex;
  flex-direction: column;
  align-items: center;
  img {
    width: 61px;
    height: 61px;
    margin-top: 67px;
  }
  .succ-text {
    text-align: center;
    margin: 22px 0 66px;
    .p-title {
      font-size:16px;
      font-family:PingFangSC-Regular,PingFang SC;
      font-weight:400;
      color:rgba(51,51,51,1);
      margin-bottom: 10px;
    }
    .p-text {
      font-size:12px;
      font-family:PingFangSC-Regular,PingFang SC;
      font-weight:400;
      color:rgba(153,153,153,1);
      margin-bottom: 10px;
    }
  }
  .van-button {
    width: 309px;
    height: 46px;
    background: rgba(252, 127, 12, 1);
    border-radius: 2px;
    border: 0;
    font-size:18px;
    font-family:PingFangSC-Regular,PingFang SC;
    font-weight:400;
    color: rgba(255, 255, 255, 1);
    letter-spacing: 1px;
    margin-bottom: 20px;
  }
  .btn-gto{
    width: 309px;
    height:46px;
    border-radius:2px;
    border:1px solid rgba(252,127,12,1);
    line-height: 46px;
    text-align: center;
    color: rgba(252, 127, 12, 1);
    font-size:18px;
    font-family:PingFangSC-Regular,PingFang SC;
    font-weight:400;
  }
}
</style>
