<template>
  <div id="editPhone_canUse">
    <div class="header">
      <div class="wrap">
        <div class="arrow">
          <img @click="back" src="../../static/images/back-arrow.png" alt />
        </div>
        <div class="title">修改绑定手机</div>
        <div class="arrow help" @click="searchHelp">
          <img src="../../assets/editPhone/help.png" alt />
        </div>
      </div>
    </div>

    <van-form class="form">
      <van-field
        v-model="form.new_mobile"
        clearable
        name="新手机号"
        type="tel"
        maxlength="11"
        label="新手机号"
        placeholder="请输入新手机号"
        @blur="checkSame"
      />
      <van-field
        v-model="form.new_mobile_code"
        center
        clearable
        maxlength="6"
        label="验证码"
        placeholder="请输入验证码"
      >
        <template #button>
          <van-button size="small" type="default" round v-if="canClick" @click="getCode">发送验证码</van-button>
          <van-button size="small" type="default" round v-else disabled>{{countDownNum}}s</van-button>
        </template>
      </van-field>
      <div class="tips">
        <p class="tips_title">温馨提示</p>
        <p class="tips_text">1.需更换的新手机号码，必须为本人实名认证手机号码，如非本人实名认证手机号码，引起的任何账户信息安全隐患，由申请人本人负责。</p>
      </div>
    </van-form>

    <div style="margin: 16px;">
        <van-button color="#4d58f9" round block type="info" @click="onSubmit" native-type="submit">提交</van-button>
      </div>
  </div>
</template>

<script>
import { Toast, Dialog } from "vant";
import { getNewPhoneCode, MobileChange } from "../../api/user";
export default {
  data() {
    return {
        canClick: true,
        countDownNum: 60,
      form: {
        new_mobile: "",
        new_mobile_code: "",
        
      },
      userInfoObj:{},
    };
  },
  created() {
        document.getElementsByTagName("html")[0].style.backgroundColor = "#ebebeb";
    },
    mounted(){
        this.userInfoObj = JSON.parse(this.$route.query.userInfo);
    },
  methods: {
    onSubmit() {
        if(this.form.new_mobile_code==''||this.form.new_mobile==''){
            Toast('请完善信息!');
            return
        }
        let params={new_mobile_code:this.form.new_mobile_code,new_mobile:this.form.new_mobile}
      MobileChange(params)
        .then(
          (res) => {
            if (res.code === 0) {
                this.$router.push({
                    path: "/editPhoneSuccess",
                    query: {
                        userInfo:this.$route.query.userInfo,
                        currentPhone:this.form.new_mobile
                    }
                });
            } else if(res.code === 1098) {
              Dialog.alert({
                title: '提醒',
                message: '您已提交过申请请耐心等待',
              }).then(() => {
                // on close
              });
              return
            }
          },
          (error) => {
            console.log(error);
          }
        )
        .finally(() => {});
    },
    getCode() {
      if (this.checkTel()) {
        let params = { number: this.form.new_mobile };
        getNewPhoneCode(params)
          .then(
            (res) => {
              if (res.code === 0) {
                Toast("验证码已发送！");
                this.canClick = false;
                this.getCountDown(res.data.ttl);
              } else {
                Toast(res.info);
              }
            },
            (error) => {
              console.log(error);
            }
          )
          .finally(() => {});
      }
    },
    //获取验证码倒计时
    getCountDown(num) {
      if (!this.canClick) {
        let timer = setInterval(() => {
          this.countDownNum--;
          if (this.countDownNum <= 0) {
            // if (this.isOnce) this.isOnce = false;
            this.canClick = true;
            this.countDownNum = num;
            clearInterval(timer);
          }
          // this.msgText = "重发验证码";
        }, 1000);
      }
    },
    // 验证手机号
    checkTel() {
      if (this.form.new_mobile.length > 11) {
        this.form.new_mobile = this.form.new_mobile.slice(0, 11);
      }
      let telrule = /^1\d{10}$/;
      let twrule = /^09\d{8}$/;
      if (
        !telrule.test(this.form.new_mobile) &&
        !twrule.test(this.form.new_mobile)
      ) {
        Toast("请输入11位手机号");
        return false;
      } else {
        return true;
      }
    },
    checkSame(){
      if(this.userInfoObj.mobile_o==this.form.new_mobile){
        Toast('新手机号与旧手机号一致')
      }
    },
    back() {
      Dialog.confirm({
        title: "提示",
        message: "确定要放弃修改绑定手机吗？",
      })
        .then(() => {
          // on confirm
          window.history.go(-1);
        })
        .catch(() => {
          // on cancel
        });
    },
    searchHelp() {
      this.$router.push({
        name: "help",
      });
    },
  },
};
</script>

<style lang="less" scoped>
#editPhone_canUse{
    // background: #fff;
    padding-bottom: 17px;
}
.tips {
  padding: 17px;
  color: #bbb;
  font-size: 13px;
  background: #fff;
}
.tips_title {
  color: #555;
  margin-bottom: 5px;
}
.tips_text {
  line-height: 23px;
  text-align: justify;
}
.header:after {
    position: absolute;
    box-sizing: border-box;
    content: " ";
    pointer-events: none;
    right: 0;
    bottom: 0;
    left: 0;
    border-bottom: 1px solid #ebedf0;
    -webkit-transform: scaleY(.5);
    transform: scaleY(.5);
}
.header {
  height: 36px;
  line-height: 36px;
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
    .help {
      img {
        width: 70%;
        height: 70%;
      }
    }

    .title {
      text-align: center;
      font-size: 18px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(51, 51, 51, 1);
      flex: 1;
      // padding-right: 30px;
    }
  }
}
</style>