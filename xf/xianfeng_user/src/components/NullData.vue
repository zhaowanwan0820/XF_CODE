<template>
  <div>
    <div class="content">
      <div class="top-img"><img src="../static/images/kong-tip.png" alt=""></div>
      <div class="middle-text">暂无相关内容</div>
      <div class="bottom-text">主人，暂未查到相关的内容哦~</div>
    </div>
  </div>
</template>

<script>
  export default{
    data(){
      return{

      }
    },
    methods:{

    }
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
      font-size:15px;
      font-family:PingFangSC-Medium,PingFang SC;
      font-weight:bold;
      color:#666666;
      margin: 25px 0 10px 0;
    }
    .bottom-text{
      font-size:14px;
      font-family:PingFangSC-Light,PingFang SC;
      font-weight:300;
      color:#999999;
    }
  }
</style>
