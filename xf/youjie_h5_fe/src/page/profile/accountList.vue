<template>
  <div class="container">
    <mt-header class="header" fixed :title="title">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="account">
      <div class="account-box">
        <div class="box-foot">
          <div class="foot-box">
            <p class="foot-box-p host-i">40000.00</p>
            <p class="foot-box-text">现金账户余额(元)</p>
          </div>
          <div class="foot-box">
            <p class="foot-box-pt"><span>确权时间</span>2019-09-09</p>
          </div>
        </div>
      </div>
      <div class="account-box">
        <div class="box-foot">
          <div class="foot-box">
            <p class="foot-box-p host-i host-i-t">40000.00</p>
            <p class="foot-box-text">现金账户余额(元)</p>
          </div>
          <div class="foot-box">
            <p class="foot-box-pt"><span>确权时间</span>2019-09-09</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'accountList',
  data() {
    return {
      title: '网信普惠'
    }
  },
  created() {
    console.log(this.$route)
    this.title = this.$route.params.title == 1 ? '网信普惠' : '尊享'
  },
  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  padding-top: 54px;
  .header {
    @include header;
  }
  .account {
    .account-box {
      background-color: #fff;
      padding: 20px 0;
      margin: 0 15px 15px 15px;
      border-radius: 6px;
      .box-foot {
        /*display: flex;*/
        /*flex-direction: column;*/
        /*align-items: center;*/
        .foot-box {
          width: 100%;
          text-align: center;
          .foot-box-p {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-family: PingFangSC-Medium, PingFang SC;
            font-weight: 500;
            color: rgba(64, 64, 64, 1);
            position: relative;
          }
          .host-i::after {
            content: '现金';
            border-radius: 1px;
            font-size: 12px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(255, 255, 255, 1);
            padding: 1px 3px;
            background: rgba(253, 180, 114, 1);
            position: absolute;
            top: 4px;
            right: 89px;
          }
          .host-i-t::after {
            content: '冻结';
            background: rgba(152, 141, 255, 1);
          }
          .foot-box-text {
            height: 16px;
            line-height: 16px;
            font-size: 11px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(153, 153, 153, 1);
          }
          .foot-box-pt {
            height: 17px;
            line-height: 17px;
            font-size: 12px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(153, 153, 153, 1);
            margin-top: 14px;
            span {
              font-size: 11px;
              font-family: PingFangSC-Regular, PingFang SC;
              font-weight: 400;
              color: rgba(153, 153, 153, 1);
            }
          }
        }
      }
    }
  }
}
</style>
