<template>
  <div class="item" @click="goLend()">
    <div class="top">
      <div class="state state-ing" v-if="listValue.status==1">还款中</div>
      <div class="state state-ed" v-if="listValue.status!=1">已结清</div>
      <div class="deal-name">{{listValue.deal_name}}</div>
      <div class="arrow-box"><img src="../static/images/common-arrow.png" alt=""></div>
    </div>
    <div class="group">
      <div class="label">原始出借金额</div>
      <div class="number">{{listValue.money | formatMoney}}元</div>
    </div>
    <div class="group">
      <div class="label">年利率</div>
      <div class="number">{{listValue.rate | formatMoney}}%</div>
    </div>
    <div class="group" v-show="listValue.status==1  && type != 4">
      <div class="label">在途本金/利息</div>
      <div class="number">{{listValue.wait_capital | formatMoney}}元/{{listValue.wait_interest | formatMoney}}元</div>
    </div>
    <div class="group" v-show="listValue.status==1 && type == 4">
      <div class="label">在途本金</div>
      <div class="number">{{listValue.wait_capital | formatMoney}}元</div>
    </div>
    <div class="group" style="border: none;">
      <div class="label">出借时间</div>
      <div class="number">{{listValue.create_time | convertTime2}}</div>
    </div>
  </div>
</template>

<script>
  export default {
    props: {
      listValue: {
        type: Object,
        default: function() {
          return {}
        }
      },
      type: {
        type: Number,
        default: function() {
          return 0
        }
      },
    },
    methods:{
      goLend(){
        this.$emit('goLend')
      }
    }
  }
</script>

<style lang="less" scoped>
  .item{
    margin-bottom: 10px;
    background: #fff;
    .top{
      padding-left: 15px;
      display: flex;
      flex-direction: row;
      align-items: center;
      border-bottom: 1px solid rgba(230,230,230,0.3);
      .state{
        text-align: center;
        margin: 12px 10px 12px 0;
        width:63px;
        height:22px;
        line-height: 22px;
        border-radius:16px;
        font-size:13px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:400;
        color:rgba(255,255,255,1);
      }
      .state-ing{
        background:rgba(44,196,65,1);
      }
      .state-ed{
        background:rgba(254,128,13,1)
      }
      .deal-name{
        font-size:15px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:500;
        color:rgba(64,64,64,1);
        flex: 1;
      }
      .arrow-box{
        display: flex;
        align-items: center;
        width: 6px;
        height: 11px;
        margin: 0 15px;
        img{
          width: 100%;
          height: 100%;
        }
      }
    }
    .group{
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
      height:43px;
      line-height:43px;
      font-size:15px;
      font-family:PingFangSC-Light,PingFang SC;
      font-weight:400;
      color:rgba(112,112,112,1);
      margin: 0 15px;
      border-bottom: 1px solid rgba(230,230,230,0.3);
      .number{
        color: #404040;
        font-family:PingFangSC-Light,PingFang SC;
      }
    }
  }
</style>
