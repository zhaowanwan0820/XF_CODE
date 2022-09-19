<template>
  <div>
     <common-header :title="title" :showArrow='false'></common-header>
    <div class="content">
      <div class="top-img"><img src="../../static/images/noNet-tip.png" alt=""></div>
      <div class="middle-text">网络繁忙，连接失败。</div>
      <div class="bottom-btn">
        <span @click="goHome">返回首页</span>
      </div>
    </div>
  </div>
</template>

<script>
  export default{
    data(){
      return{
        title:'无网络'
      }
    },
    methods:{
      //返回首页
      goHome(){
        this.$router.push({
          path:'/'
        })
    }
  },
 }
</script>

<style lang="less" scoped>
  .content{
    margin-top: 36px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    .top-img{
      margin-top: 120px;
      img{
        width: 125px;
        height: 125px;
      }
    }
    .middle-text{
      margin: 25px 0;
      font-size:14px;
      font-family:PingFangSC-Light,PingFang SC;
      font-weight:300;
      color:#999999;
    }
    .bottom-btn{
      text-align: center;
      width: 100%;
      display: flex;
      flex-direction: row;
      justify-content: center;
      align-items: center;
      span{
        display: inline-block;
        height:30px;
        line-height: 30px;
        background:#3934DF;
        border-radius:50px;
        flex: 1;
        margin: 0 72px;
        font-size:14px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:400;
        color:rgba(255,255,255,1);
      }
    }
  }
</style>
