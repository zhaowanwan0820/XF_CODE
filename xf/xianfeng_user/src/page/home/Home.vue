<template>
  <div class="container">
    <!-- 头部信息-->
    <div class="top-banner">
      <div class="wrap">
        <div class="userInfo">
          <div class="tx" @click="goUser">
            <img
              :src="
                userInfoObj.head_portrait ? userInfoObj.head_portrait : txSrc
              "
              alt
            />
          </div>
          <div class="phoneNumber">{{ userInfoObj.mobile }}</div>
        </div>
        <div class="messageIcon" @click="goMessage">
          <img src="../../static/images/xiaoxi-icon.png" alt />
          <span
            class="circle-red"
            v-if="new_message == 1 || new_feedback == 1"
          ></span>
        </div>
      </div>
    </div>
    <!-- 账户总权益-->
    <div class="account-info-box">
      <div class="wrap">
        <!-- 总权益-->
        <div class="total-equity">
          <div class="top">
            <div class="label">账户总权益(元)</div>
            <div class="encryption-btn" @click="addSecrecy">
              <img
                v-show="!isSecrecy"
                src="../../static/images/eye-open.png"
                alt
              />
              <img
                v-show="isSecrecy"
                src="../../static/images/eye-close.png"
                alt
              />
            </div>
          </div>
          <div class="bottom bottom1" v-show="!isSecrecy">
            {{ userInfoObj.all_Money | formatMoney }}
          </div>
          <div class="bottom" v-show="isSecrecy">****</div>
          <div class="collapse-img" @click="collapseOpen" v-show="!cllapseFlag">
            <img src="../../static/images/collapse-open.png" alt />
          </div>
        </div>
        <!-- 权益详情-->
        <div class="detail-equity" v-show="cllapseFlag">
          <div class="item-box">
            <div class="item">
              <div class="label">在途本金(元)</div>
              <div class="amount" v-show="!isSecrecy">
                {{ userInfoObj.wait_capital | formatMoney }}
              </div>
              <div class="amount" v-show="isSecrecy">****</div>
            </div>
            <div class="item">
              <div class="label">在途利息(元)</div>
              <div class="amount" v-show="!isSecrecy">
                {{ userInfoObj.wait_interest | formatMoney }}
              </div>
              <div class="amount" v-show="isSecrecy">****</div>
            </div>
          </div>
          <div class="item-box">
            <div class="item">
              <div class="label">账户余额(元)</div>
              <div class="amount" v-show="!isSecrecy">
                {{ userInfoObj.money | formatMoney }}
              </div>
              <div class="amount" v-show="isSecrecy">****</div>
            </div>
            <div class="item">
              <div class="label">冻结金额(元)</div>
              <div class="amount" v-show="!isSecrecy">
                {{ userInfoObj.lock_money | formatMoney }}
              </div>
              <div class="amount" v-show="isSecrecy">****</div>
            </div>
          </div>
          <div class="collapse-img" v-show="cllapseFlag" @click="collapseClose">
            <img src="../../static/images/collapse-close.png" alt />
          </div>
        </div>
      </div>
    </div>
    <!-- 消息轮播-->
    <div class="swiper-box">
      <div class="wrap" v-if="isShowNotice">
        <div class="horn-icon">
          <img src="../../static/images/horn-icon.png" alt />
        </div>
        <div class="swiper-info">
          <van-swipe
            class="mySwiper"
            :autoplay="3000"
            loop
            vertical
            :show-indicators="false"
          >
            <van-swipe-item
              v-for="(val, index) in list"
              :key="index"
              @click="messageDetail(val.id)"
              >{{ val.title }}</van-swipe-item
            >
          </van-swipe>
        </div>
        <div class="more-btn" @click="goNotic">查看更多</div>
        <div class="arrow-box">
          <img src="../../static/images/little-arrow.png" alt />
        </div>
      </div>
      <div class="wrap wrap-block" v-if="isShowNotice"></div>
    </div>
    <!-- -->
    <div class="item-box">
      <div class="wrap">
        <!-- 消息通知-->
        <div class="item mb10" @click="goMessage">
          <div class="left">
            <div class="icon-box">
              <img src="../../static/images/xx-icon.png" alt />
            </div>
            <div class="item-name">消息通知</div>
            <div class="new-news" v-if="new_message == 1 || new_feedback == 1">
              <img src="../../static/images/new-icon.png" alt />
            </div>
          </div>
          <div class="arrow-box">
            <img src="../../static/images/common-arrow.png" alt />
          </div>
        </div>
        <!-- 债转系统-->
        <div class="item" @click="goDebt('debtMarket')">
          <div class="left">
            <div class="icon-box">
              <img src="../../static/images/zhaizhuan-icon.png" alt />
            </div>
            <div class="item-name">债转市场</div>
          </div>
          <div class="arrow-box">
            <img src="../../static/images/common-arrow.png" alt />
          </div>
        </div>

        <!-- 资产中心-->
        <div class="item" @click="goPropertyCompose">
          <div class="left">
            <div class="icon-box">
              <img src="../../static/images/zc-icon.png" alt />
            </div>
            <div class="item-name">资产中心</div>
          </div>
          <div class="arrow-box">
            <img src="../../static/images/common-arrow.png" alt />
          </div>
        </div>
        <!-- 积分中心-->
        <div class="item" @click="goExchange">
          <div class="left">
            <div class="icon-box">
              <img src="../../static/images/jf-icon.png" alt />
            </div>
            <div class="item-name">积分中心</div>
          </div>
          <div class="arrow-box">
            <img src="../../static/images/common-arrow.png" alt />
          </div>
        </div>
        <!-- 安全设置-->
        <div class="item mb10" @click="goSecurity">
          <div class="left">
            <div class="icon-box">
              <img src="../../static/images/aq-icon.png" alt />
            </div>
            <div class="item-name">安全设置</div>
          </div>
          <div class="arrow-box">
            <img src="../../static/images/common-arrow.png" alt />
          </div>
        </div>

        <!-- 意见反馈-->
        <!-- <div class="item" style="border: none;">
          <div class="left">
            <div class="icon-box">
              <img src="../../static/images/yj-icon.png" alt />
            </div>
            <div class="item-name">意见反馈</div>
          </div>
        </div> -->
        <!-- 反馈提交框-->
        <!-- <div class="feedback-box">
          <div class="wrap">
            <div class="label">问题描述</div>
            <div class="textarea-box">
              <textarea v-model="params.content" placeholder="告诉我们您遇到的问题，我们会第一时间处理" />
            </div>
            <div class="feedback-btn">
              <span @click="feedback">提交反馈</span>
            </div>
          </div>
        </div> -->
        <!-- 联系客服-->
        <div class="item" @click="goKefu">
          <div class="left">
            <div class="icon-box">
              <img src="../../static/images/kefu-icon.png" alt />
            </div>
            <div class="item-name">联系客服</div>
          </div>
          <div class="arrow-box">
            <img src="../../static/images/common-arrow.png" alt />
          </div>
        </div>
        <div style="background: #fff;padding-left: 9px;">
          <submitfeedback></submitfeedback>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapMutations, mapGetters } from "vuex";
import submitfeedback from "../../components/SubmitFeedBack.vue";
import {
  getUserInfoRequest,
  feedbackRequest,
  getNoticeList,
  getNewsRequest
} from "../../api/home.js";
import { Toast, Swipe, SwipeItem } from "vant";
export default {
  components: {
    submitfeedback
  },
  data() {
    return {
      userInfoObj: {},
      userInfoObj_S: {}, //保存正常显示信息的对象
      isSecrecy: false,
      params: {
        type: 0, //查询债权和账户的范围：0-全部，1-尊享，2-普惠
        content: "",
        limit: 10,
        page: 1
      },
      cllapseFlag: false,
      list: [],
      txSrc: require("../../static/images/tx-icon.png"), //默认头像
      new_message: 0, // 是否有新消息：1-是，2-否
      new_feedback: 0, // 是否有新意见反馈回复：1-是，2-否
      isShowNotice: true
    };
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      token: state => state.auth.token
    })
  },
  created() {
    this.userInfo();
    this.noticeList();
    this.isNewsMessage();
    if (!this.token) {
      this.$router.replace({
        name: "login"
      });
    }
    localStorage.setItem("xianfeng", true);
  },
  mounted() {
    document
      .querySelector("body")
      .setAttribute("style", "background-color:#F4F4F4");
  },
  beforeDestroy() {
    document.querySelector("body").removeAttribute("style");
  },
  methods: {
    goDebt(name) {
      window.location.href = `/debt/#/${name}`;
    },
    //个人信息
    userInfo() {
      getUserInfoRequest(this.params)
        .then(res => {
          if (res.code === 0) {
            let data = res.data;
            if (data.sign_agreement != 1) {
              // 未签署协议
              this.$router.push({
                path: "/serviceAgreement",
                query: {
                  showBtn: true
                }
              });
            } else {
              this.userInfoObj = data;
              this.userInfoObj_S = data;
              this.userInfoObj.all_Money =
                data.wait_capital +
                data.wait_interest +
                data.money +
                data.lock_money;
              if (this.userInfoObj.mobile) {
                this.userInfoObj.mobile =
                  data.mobile.substr(0, 3) + "****" + data.mobile.substr(7);
              }
            }
          }
        })
        .finally(() => {});
    },
    //脱敏
    addSecrecy() {
      this.isSecrecy = !this.isSecrecy;
      // if (this.isSecrecy === false) {
      //   //未脱敏
      //   this.userInfoObj = this.userInfoObj_S;
      // } else {
      //   //显示脱敏
      //   this.userInfoObj = {
      //     wait_capital: "****",
      //     wait_interest: "****",
      //     money: "****",
      //     lock_money: "****",
      //     all_Money: "****",
      //     mobile: this.userInfoObj.mobile
      //   };
      // }
    },
    //公告别表
    noticeList() {
      getNoticeList(this.params).then(res => {
        console.log(res);
        if (res.code === 0) {
          if (res.data.length === 0) {
            this.isShowNotice = false;
          }
          this.list = res.data.slice(0, 3);
        }
      });
    },

    //公告详情
    messageDetail(id) {
      this.$router.push({
        name: "messageDetail",
        query: {
          id: id
        }
      });
    },
    //跳转去个人中心
    goUser() {
      this.$router.push({
        path: "/user",
        query: {
          userInfoObj: JSON.stringify(this.userInfoObj_S)
        }
        // path:'/user/${id}'
      });
    },
    //跳转去安全设置
    goSecurity() {
      this.$router.push({
        path: "/security",
        query: {
          set_password: this.userInfoObj_S.set_password, // 交易密码状态：1-已设置，2-未设置
          sign_agreement: this.userInfoObj_S.sign_agreement // 先锋服务协议状态：1-已签署，2-未签署
        }
      });
    },
    //资产构成
    goPropertyCompose() {
      this.$router.push({
        path: "/propertyCompose"
      });
    },
    //展开权益详情
    collapseOpen() {
      this.cllapseFlag = true;
    },
    //收起权益详情
    collapseClose() {
      this.cllapseFlag = false;
    },
    // 消息列表
    goMessage() {
      this.$router.push({
        name: "message"
      });
    },
    // 消息通知
    goMessageList() {
      this.$router.push({
        name: "messageList",
        query: {
          sq: "1"
        }
      });
    },
    // 积分中心
    goExchange() {
      this.$router.push({
        path: "/ExchangeList"
      });
    },
    // 公告
    goNotic() {
      this.$router.push({
        path: "/messageList",
        query: {
          sq: "3"
        }
      });
    },
    //是否有新消息
    isNewsMessage() {
      getNewsRequest().then(res => {
        console.log(res);
        this.new_message = res.data.new_message;
        this.new_feedback = res.data.new_feedback;
      });
    },
    //联系客服页面
    goKefu() {
      this.$router.push({
        name: "service"
      });
    }
  }
};
</script>

<style lang="less" scoped>
.mb10 {
  margin-bottom: 10px;
  border-bottom: none !important;
}
.container {
  width: 100%;

  .collapse-img {
    display: flex;
    flex-direction: column;

    img {
      width: 50px;
      height: 12px;
    }
  }

  .top-banner {
    padding-top: 16px;
    width: 100%;
    height: 175px;
    background: url(../../static/images/top-banner.png) center center no-repeat;
    background-size: 100% 100%;

    .wrap {
      margin: 0 17px;
      display: flex;
      flex-direction: row;
      justify-content: space-between;

      .userInfo {
        display: flex;
        flex-direction: row;

        // justify-content: center;
        .tx {
          margin-right: 12px;
          background: rgba(216, 216, 216, 0.3);
          width: 34px;
          height: 34px;
          border-radius: 8px;
          border: 2px solid rgba(255, 255, 255, 0.2);
          overflow: hidden;

          img {
            width: 100%;
            height: 100%;
          }
        }

        .phoneNumber {
          width: 110px;
          height: 34px;
          font-size: 16px;
          font-family: PingFangSC-Medium, PingFang SC;
          font-weight: 500;
          color: rgba(255, 255, 255, 1);
          line-height: 34px;
        }
      }

      .messageIcon {
        height: 34px;
        width: 21px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;

        .circle-red {
          display: inline-block;
          width: 7px;
          height: 7px;
          background: rgba(255, 49, 34, 1);
          border-radius: 50%;
          position: absolute;
          top: 0;
          right: -23%;
        }

        img {
          width: 21px;
          height: 19px;
        }
      }
    }
  }

  .account-info-box {
    width: 100%;
    margin-top: -96px;

    .wrap {
      margin: 0 15px;
      background: rgba(255, 255, 255, 1);
      border-radius: 4px;
      text-align: center;

      .total-equity {
        display: flex;
        flex-direction: column;
        align-items: center;

        .top {
          display: flex;
          flex-direction: row;
          justify-content: center;
          align-items: center;
          font-size: 14px;
          font-family: PingFangSC-Light, PingFang SC;
          color: rgba(53, 52, 92, 1);
          line-height: 20px;
          width: 100%;

          .label {
            flex: 1;
            height: 20px;
            font-size: 14px;
            font-family: PingFangSC-Light, PingFang SC;
            color: rgba(53, 52, 92, 0.5);
            line-height: 20px;
            margin: 16px 0 7px 37px;
          }

          .encryption-btn {
            padding-right: 18px;
            margin-top: 16px;

            img {
              width: 19px;
              height: 15px;
            }
          }
        }

        .bottom {
          height: 33px;
          font-size: 24px;
          font-family: PingFangSC-Medium, PingFang SC;
          color: rgba(53, 52, 92, 1);
          line-height: 33px;
          letter-spacing: 1px;
          padding-bottom: 22px;
          font-weight: bold;
        }
      }

      .detail-equity {
        padding-top: 17px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        background: rgba(249, 250, 255, 1);
        border-radius: 0px 0px 4px 4px;

        .collapse-img {
          padding-top: 16px;
        }

        .item-box {
          display: flex;
          flex-direction: row;
          align-items: center;
          justify-content: space-between;
          width: 100%;
          margin-bottom: 15px;

          .item:first-child {
            text-align: left;
            padding-left: 20px;
          }

          .item:last-child {
            text-align: right;
            padding-right: 20px;
          }

          .item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;

            .label {
              height: 17px;
              font-size: 10px;
              font-family: PingFangSC-Light, PingFang SC;
              color: rgba(53, 52, 92, 1);
              line-height: 17px;
              width: 100%;
            }

            .amount {
              height: 17px;
              line-height: 17px;
              font-size: 15px;
              font-family: PingFangSC-Medium, PingFang SC;
              color: rgba(53, 52, 92, 1);
              line-height: 21px;
              margin-top: 5px;
              width: 100%;
            }
          }
        }
      }
    }
  }

  .swiper-box {
    margin-top: 15px;

    .wrap {
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
      height: 33px;
      background: rgba(237, 236, 249, 1);
      border-radius: 4px;
      margin: 0 15px;

      .horn-icon {
        height: 33px;
        background: rgba(237, 236, 249, 1);
        border-radius: 4px;
        margin-left: 9px;
        margin-right: 15px;
        display: flex;
        align-items: center;

        img {
          width: 19px;
          height: 16px;
        }
      }

      .swiper-info {
        font-size: 13px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
        flex: 1;

        .mySwiper {
          height: 33px;
          line-height: 33px;
          overflow: hidden;

          .van-swipe-item {
            height: 33px;
          }
        }
      }

      .more-btn {
        font-size: 13px;
        font-family: PingFangSC-Light, PingFang SC;
        font-weight: 300;
        color: rgba(64, 64, 64, 1);
        margin-left: 5px;
      }

      .arrow-box {
        margin-left: 4px;
        display: flex;
        align-items: center;
        width: 30px;
        height: 30px;

        img {
          width: 4px;
          height: 7px;
        }
      }
    }

    .wrap-block {
      height: 9px;
      background: #fff;
      border-radius: 0;
    }
  }

  .item-box {
    .wrap {
      margin: 0 15px;
      // background: #fff;

      .item {
        background: #fff;
        display: flex;
        flex-direction: row;
        height: 55px;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px dashed rgba(198, 198, 198, 0.3);
        padding-left: 9px;
        padding-right: 15px;

        .left {
          display: flex;
          flex-direction: row;
          align-items: center;

          .icon-box {
            width: 20px;
            height: 20px;

            img {
              width: 100%;
              height: 100%;
            }
          }

          .item-name {
            font-size: 15px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(64, 64, 64, 1);
            margin-left: 10px;
            margin-right: 5px;
          }

          .new-news {
            width: 33px;
            height: 14px;

            img {
              width: 100%;
              height: 100%;
            }
          }
        }

        .arrow-box {
          width: 30px;
          height: 30px;
          display: flex;
          justify-content: center;
          align-items: center;

          img {
            width: 6px;
            height: 11px;
          }
        }
      }

      .item:last-child {
        border: none;
      }
    }
  }

  .feedback-box {
    padding-bottom: 20px;

    .wrap {
      margin: 0 10px 17px 24px;
      background: rgba(249, 249, 249, 1);
      border-radius: 1px;
      min-height: 118px;

      .label {
        font-size: 12px;
        font-family: PingFangSC-Medium, PingFang SC;
        font-weight: 500;
        color: rgba(64, 64, 64, 1);
        padding: 10px 0 4px 0;
        margin: 0 12px;
        height: 17px;
        line-height: 17px;
        border-bottom: 1px dashed #dbdbdb;
      }

      .textarea-box {
        margin: 9px 12px 0 12px;

        textarea {
          border: none;
          width: 100%;
          min-height: 45px;
          background: rgba(249, 249, 249, 1);
          outline: none;
          font-size: 12px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: #000;
          resize: none;
        }
      }

      .feedback-btn {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        margin-right: 5px;
        margin-top: 4px;

        span {
          text-align: center;
          width: 57px;
          height: 24px;
          line-height: 24px;
          border-radius: 2px;
          border: 1px solid rgba(59, 45, 233, 1);
          font-size: 10px;
          font-family: PingFangSC-Medium, PingFang SC;
          font-weight: 500;
          color: rgba(77, 88, 249, 1);
          margin-bottom: 4px;
        }
      }
    }
  }
}
</style>
