<template>
  <div class="container" id="content">
    <div class="top" v-if="showBtn">
      <div class="close-icon" @click="excitLogin"><img src="../../static/images/close-btn.png" alt=""></div>
      <div class="title">先锋服务协议</div>
    </div>
    <common-header :title="title" v-if="!showBtn"></common-header>
    <div style="height: 36px;" v-if="!showBtn"></div>
    <div class="content">
      <agreement-detail></agreement-detail>
    </div>
    <div class="gray" v-if="showBtn"></div>
    <div class="bottom" v-if="showBtn">
      <van-checkbox class="checkbox" v-model="checked" icon-size="15px" shape="square" checked-color="#ffffff">协议已阅读</van-checkbox>
      <div class="btn-box">
        <div class="cancel-btn" @click="excitLogin">
          <span>取消</span>
        </div>
        <div :class="checked===false?'agree-btn  agree-btn-opcity':'agree-btn'" @click="agree">
          <span>同意</span>
        </div>
      </div>
    </div>

    <div class="backTop" @click="backTop">
      <img src="../../static/images/backTop.png" alt="返回顶部">
    </div>
  </div>
</template>

<script>
  import AgreementDetail from '../../components/AgreementDetail.vue'
  import {
    mapMutations
  } from 'vuex'
  import {
    Checkbox,
    CheckboxGroup,
    Toast
  } from 'vant';
  import {
    seriveAgreementRequest
  } from '../../api/serviceAgreement.js'
  export default {
    components: {
      AgreementDetail
    },
    data() {
      return {
        title: '先锋服务协议',
        checked: false,
        params: {
          type: 1
        },
        showBtn: true,
        isShowimg: false,
        gotop: false
      }
    },
    mounted() {
      this.showBtn = this.$route.query.showBtn;
    },
    methods: {
      ...mapMutations({
        clearToken: 'clearToken'
      }),
      excitLogin() {
        Toast("不签署协议无法使用服务")
        this.clearToken();
        this.$router.push({
          path: "/login"
        });
      },
      agree() {
        if (this.checked === false) {
          Toast("请勾选确认协议已阅读")
          return;
        } else {
          seriveAgreementRequest(this.params).then(res => {
            if (res.code === 0) {
              this.$router.push({
                path: "/"
              });
            }
          })
        }
      },
      backTop(x) {
         content.scrollIntoView()

      },
    }
  }
</script>

<style lang="less" scoped>
  .container {
    width: 100%;

    .backTop {
      width: 70px;
      height: 70px;
      position: fixed;
      right: 0;
      top: 560px;
      z-index: 100;

      img {
        width: 100%;
        height: 100%;
      }
    }

    .top {
      margin: 10px 15px 0 15px;
      display: flex;
      flex-direction: row;
      justify-content: center;
      height: 30px;
      line-height: 30px;

      .close-icon {
        display: flex;
        flex-direction: row;
        justify-content: start;
        align-items: center;

        img {
          width: 30px;
          height: 30px;
        }
      }

      .title {
        margin-right: 30px;
        text-align: center;
        font-size: 18px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
        letter-spacing: 1px;
        flex: 1;
      }
    }

    .content {
      margin: 12x 0;
      padding-bottom: 5px;
      // height: 457px;
      font-size: 15px;
      font-family: PingFangSC-Light, PingFang SC;
      font-weight: 300;
      color: rgba(102, 102, 102, 1);
      line-height: 24px;
      letter-spacing: 1px;
    }

    .gray {
      width: 100%;
      height: 10px;
      background: rgba(244, 244, 244, 1);
    }

    .bottom {
      margin: 0 26px;

      .checkbox {
        margin-top: 21px;

        /deep/ .van-checkbox__icon--checked .van-icon {
          color: #999999;
          border: 1px solid rgba(153, 153, 153, 1) !important;
          border-radius: 1px;
        }

        /deep/ .van-checkbox__label {
          font-size: 12px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(64, 64, 64, 1);
        }
      }


      .btn-box {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        text-align: center;
        padding: 20px 0;

        .cancel-btn {
          flex: 1;
          margin-right: 33px;
          height: 44px;
          line-height: 44px;
          background: rgba(255, 255, 255, 1);
          box-shadow: 0px 2px 6px 0px rgba(195, 195, 195, 0.5);
          border-radius: 25px;
          border: 1px solid rgba(112, 112, 112, 1);
          font-size: 15px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(64, 64, 64, 1);
        }

        .agree-btn {
          flex: 1;
          height: 44px;
          line-height: 44px;
          background: rgba(57, 52, 223, 1);
          box-shadow: 0px 2px 6px 0px rgba(43, 39, 164, 0.5);
          border-radius: 25px;
          font-size: 15px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(255, 255, 255, 1);
        }

        .agree-btn-opcity {
          background: rgba(57, 52, 223, 0.3);
        }
      }
    }
  }
</style>
