<template>
  <div class="container">
    <div class="header">
      <div class="wrap">
        <div class="arrow" @click="goBack"><img src="../../static/images/back-arrow.png" alt=""></div>
        <div class="title">{{title}}</div>
      </div>
    </div>
    <div class="resetPhone item" @click="goSetPhoneNum">
      <div class="label">手机号码</div>
      <div class="arrow"><img src="../../static/images/common-arrow.png" alt=""></div>
    </div>
    <div class="tradingPassword item" @click="goSetPassWord">
      <div class="label">密码设置</div>
      <div class="arrow"><img src="../../static/images/common-arrow.png" alt=""></div>
    </div>

<!--    <div class="item tradingPassword" @click="goVerifiedPage({isToast:true})">
      <div class="label label1 ">实名认证</div>
      <div class="arrow"><img src="../../static/images/common-arrow.png" alt=""></div>
    </div>-->
    <div class="item sc-xianFeng" @click="goRiskRating('evaluationResult')">
      <div class="label label1 ">风险评级</div>
      <div class="arrow"><img src="../../static/images/common-arrow.png" alt=""></div>
    </div>
    <div class="serviceContract">服务协议</div>
    <div style="background-color: #fff;">
      <div class="sc-xianFeng item" @click="goXianFeng">
        <div class="label">先锋服务协议</div>
        <div class="arrow"><img v-show="showArrow" src="../../static/images/common-arrow.png" alt=""></div>
      </div>
      <div class="tip" v-if="xfAgreement">电子协议生成中，稍后可查看</div>
    </div>
    <div class="sc-jiFen">
      <div class="con">
        <span>积分兑换协议</span>
        <div class="erroe-tip" v-if="!yjAgreement">没有签署兑换协议</div>
        <div class="youjie item" v-if="yjAgreement" @click="goYouJie">
          <div class="label">有解商城积分兑换协议</div>
          <div class="arrow"><img src="../../static/images/common-arrow.png" alt=""></div>
        </div>
        <!--  <div class="tiantian item"  v-if="yjAgreement">
          <div class="label">天天商城积分兑换协议</div>
          <div class="arrow"><img src="../../static/images/common-arrow.png" alt=""></div>
        </div> -->
      </div>
    </div>
  </div>
</template>

<script>
  import commonHeader from "@/components/CommonHeader.vue";
  import {
    getAgreementRequest
  } from '../../api/security.js'
  // import {
  //   Toast
  // } from 'vant'
  // import VerifiedMinix from "../../components/Verified/VerifiedMinix";
  export default {
    components: {
      commonHeader
    },
    data() {
      return {
        title: "安全设置",
        sign_agreement: 0, // 先锋服务协议状态：1-已签署，2-未签署
        set_password: 0, // 交易密码状态：1-已设置，2-未设置
        xfAgreement: false, //先锋电子协议生成中
        yjAgreement: false,
        xf_status: 0, //先锋协议状态
        xf_oss_download: '', //先锋协议地址
        yj_status: 0, //有解商城积分兑换协议状态
        yj_oss_download: '', //有解商城积分兑换协议下载地址
        showArrow : true, //显示协议跳转箭头
      };
    },
    // mixins: [VerifiedMinix],
    created() {
      this.init();
      this.getAgreementUrl();
    },
    mounted() {
      document.querySelector('body').setAttribute('style', 'background-color:#F4F4F4')
    },
    beforeDestroy() {
      document.querySelector('body').removeAttribute('style')
    },
    methods: {
      //初始化处理参数
      init() {
        let sign_agreement = this.$route.query.sign_agreement;
        let set_password = this.$route.query.set_password;
        this.sign_agreement = sign_agreement;
        this.set_password = set_password;
        if (sign_agreement == 1) { //已签署
          this.yjAgreement = true;
        } else if (sign_agreement == 2) { //未签署协议
          this.yjAgreement = false;
        }
      },
      goSetPhoneNum(){
        console.log(this.$route.query.currentPhone,'=====')
        this.$router.push({
          path: "/editPhoneIndex",
          query: {
            userInfo:this.$route.query.userInfoObj,
            currentPhone:this.$route.query.currentPhone
          }
        });
      },
      //设置交易密码
      goSetPassWord() {
        this.$router.push({
          path: "/setPassWord",
          query: {
            set_password: this.set_password,
            sign_agreement: this.sign_agreement,
            securityFlag : true
          }
        });
      },
      getAgreementUrl() {
        getAgreementRequest().then(res => {
          console.log(res);
          if (res.code === 0) {
            this.xf_status = res.data.xf_status
            this.xf_oss_download = res.data.xf_oss_download
            this.yj_status = res.data.yj_status
            this.yj_oss_download = res.data.yj_oss_download
            if (!this.xf_oss_download) {//没链接时不显示箭头
              this.showArrow = false
            }
            // if (this.xf_status != 1) { // 先锋服务协议状态：0未处理，1处理成功，2处理失败
            //   this.xfAgreement = true
            // }
            if (!this.yj_status && !this.yj_oss_download) {
              this.yjAgreement = false
            }

          }

        })
      },
      //有解积分兑换协议
      goYouJie() {
        window.location.href = this.yj_oss_download
      },
      //先锋积分兑换协议
      goXianFeng() {
        // if (this.xf_oss_download) {
        //   window.location.href = this.xf_oss_download
        // }
        this.$router.push({
          path:'/serviceAgreement',
          query:{
            showBtn:false
          }
        })
      },
      goBack() {
        this.$router.push({
          path: "/"
        })
      },
      //风险评级
      goRiskRating(name){
        window.location.href = `/debt/#/${name}`
      }
    }
  };
</script>

<style lang="less" scoped>
  .header {
    height: 36px;
    line-height: 36px;
    border-bottom: 1px solid #F4F4F4;
    background-color: #ffffff;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;

    .wrap {
      padding: 0 9px;
      display: flex;
      flex-direction: row;
      align-items: center;

      .arrow {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;

        img {
          width: 100%;
          height: 100%;
        }
      }

      .title {
        text-align: center;
        font-size: 18px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(51, 51, 51, 1);
        flex: 1;
        padding-right: 30px;
      }
    }

  }

  .item {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    background-color: #ffffff;

    .label {
      padding-left: 15px;
      font-size: 13px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
    }

    .arrow {
      padding-right: 15px;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      width: 30px;
      height: 30px;

      img {
        width: 6px;
        height: 12px;
      }
    }
  }

  .resetPhone{
    margin-top: 50px;
    height: 60px;
    line-height: 60px;
    .label {
      font-size: 15px;
    }
  }
  .tradingPassword {
    height: 60px;
    line-height: 60px;
    margin-top: 5px;
    margin-bottom: 5px;
    .label {
      font-size: 15px;
    }

  }

  .sc-xianFeng {
    height: 18px;
    line-height: 18px;
    padding: 18px 0;
    .label1{
      font-size: 15px;
    }
  }

  .tip {
    padding-left: 15px;
    font-size: 13px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    margin-top: 2px;
    margin-left: 27px;
    padding-bottom: 21px;
  }

  .serviceContract {
    height: 50px;
    line-height: 50px;
    padding-left: 15px;
    font-size: 15px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(153, 153, 153, 1);
  }

  .sc-jiFen {
    font-size: 13px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    background-color: #ffffff;
    margin-top: 5px;
    padding-top: 15px;
    padding-bottom: 21px;

    .erroe-tip {
      padding-left: 15px;
      font-size: 13px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
      margin-top: 20px;
      margin-left: 27px;
    }

    .con {
      span {
        display: inline-block;
        padding-left: 15px;
      }

      .item {
        padding-left: 27px;
        margin-top: 20px;
      }
    }

  }
</style>
