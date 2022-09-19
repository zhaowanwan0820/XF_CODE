<template>
  <div class="box">
    <p class='fb'>{{info.exchange_time|convertTime}}</p>
    <div class="list"> 
      <van-row>
        <van-col span="8">兑换金额</van-col>
        <van-col span="16">{{utils.formatFloat(info.debt_account,0)}} 元</van-col> 
      </van-row>
      <van-row>
        <van-col span="8">兑换渠道</van-col>
        <van-col span="16">{{platform(info.platform_no)}}</van-col> 
      </van-row>
      <van-row>
        <van-col span="8">标的编号</van-col>
        <van-col span="16">{{info.deal_name}}</van-col> 
      </van-row>
    </div>
  </div>
</template>

<script> 
export default {
  name: 'projectItem',
  props: ['info'], 
  methods: {
    platform(n){
      let plat = [
          {
            value:'1',
            name:'有解商城'
          },
          {
            value:'2',
            name:'天天'
          }]
      let find=plat.find(val=>{
        if(n == val.value){
          return val
        }
      })
      return find ? find.name : ''
    }, 
  }
}
</script>

<style lang="less" scoped>
.box {
  font-size:13px; 
  color:#404040;
  background-color: #fff;
  margin: 10px 15px;
  box-sizing: border-box;  
  line-height:42px; 
  background:rgba(255,255,255,1);
  border-radius:1px;
  .fb{
    font-size:14px;
    font-weight:500; 
    padding:0 15px;  
    font-family:PingFangSC-Medium,PingFang SC;
    height:42px;
    box-sizing:border-box;
    border-bottom: 1px solid rgba(212, 212, 212, 0.2);
     
  }
  .list{  
    padding:0 15px;
  }
  .van-row{ 
    border-bottom: 1px solid rgba(203, 203, 203, 0.2);
    &:last-child{
      border:0;
    }
  }
  .van-col{  
    font-family:PingFangSC-Regular,PingFang SC;
    overflow:hidden;
    &:last-child{
      text-align:right;
    }
  }
}
</style>
