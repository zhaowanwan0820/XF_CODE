<template>
  <div id="editPhone">
    <div class="header">
      <div class="wrap">
        <div class="arrow">
          <img @click="back" src="../../static/images/back-arrow.png" alt />
        </div>
        <div class="title">交易所用户绑定手机号</div>
        <!-- <div class="arrow help" @click="searchHelp">
          <img src="../../assets/editPhone/help.png" alt />
        </div> -->
      </div>
    </div>
    <van-form @submit="onSubmit" class="form">
      <van-field v-model="form.name" name="用户名" label="姓名/企业名" placeholder="请输入姓名/企业名" clearable />
      <van-field v-model="form.id_number" name="证件号" label="证件号" placeholder="请输入证件号" clearable />
      
      <div class="upload_box">
        <div class="inline common_id_pic_front">
          <van-uploader v-model="id_pic_front" :max-count="1" />
        </div>
        <div class="inline common_id_pic_back">
          <van-uploader v-model="id_pic_back" :max-count="1" />
        </div>
        <div class="inline common_user_pic_front">
          <van-uploader v-model="user_pic_front" :max-count="1" />
        </div>
        <div class="inline common_user_pic_back">
          <van-uploader v-model="user_pic_back" :max-count="1" />
        </div>

        <div class="inline contract_pic">
          <van-uploader v-model="contract_pic" :max-count="1" />
        </div>
        <div class="inline evidence_pic">
          <van-uploader v-model="evidence_pic" :max-count="1" />
        </div>

      </div>
      <van-field
        v-model="form.new_mobile"
        clearable
        name="手机号"
        type="tel"
        maxlength="11"
        label="手机号"
        placeholder="请输入手机号"
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
        <p class="tips_text">1.合同文件及打款凭证用于确认您是否为原交易所投资用户。</p>
        <p class="tips_text">2.合同文件请拍摄签署页，确保姓名、日期清晰可见。</p>
        <p class="tips_text">3.请在光线较好的环境下拍摄，确保照片清晰可见。</p>
        <p class="tips_text">4.需更换的新手机号码，必须为本人实名认证手机号码，如非本人实名认证手机号码，引起的任何账户信息安全隐患，由申请人本人负责。</p>
        <p class="tips_text">5.申请提交后，在审核结束前不能重复提交申请。</p>
        <p class="tips_text">6.一般审核周期为10个工作日。</p>
      </div>
      <div style="margin: 16px;">
        <van-button color="#4d58f9"  round block type="info" native-type="submit">提交</van-button>
      </div>
    </van-form>
  </div>
</template>

<script>
import { Toast, Dialog } from "vant";
// import commonHeader from "@/components/CommonHeader.vue";
import { checkJYSUserInfo, createJYSUser, getJYSNewPhoneCode } from "../../api/user";
export default {
  // components: {
  //   commonHeader,
  // },
  data() {
    return {
      countDownNum: 60,
      canClick: true,
      id_pic_front: [],
      id_pic_back: [],
      user_pic_front: [],
      user_pic_back: [],
      contract_pic:[],
      evidence_pic:[],
      form: {
        name: "",
        id_number: "",
        new_mobile: "",
        new_mobile_code: "",
        id_pic_front: "",
        id_pic_back: "",
        user_pic_front: "",
        user_pic_back: "",
        contract_pic:"",
        evidence_pic:""
      },
    };
  },
  created() {
    document.getElementsByTagName("html")[0].style.backgroundColor = "#ebebeb";
  },
  methods: {
    back() {
      Dialog.confirm({
        title: "提示",
        message: "确定要放弃吗？",
      })
        .then(() => {
          // on confirm
          window.history.go(-1);
        })
        .catch(() => {
          // on cancel
        });
    },
    checkSame(){
      if(this.form.old_mobile==this.form.new_mobile){
        Toast('新手机号与旧手机号一致')
      }
    },
    checkSubmit(){
      let params={name:this.form.name,id_number:this.form.id_number}
      checkJYSUserInfo(params)
        .then(
          (res) => {
            if (res.code === 0) {
            
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
    searchHelp() {
      this.$router.push({
        name: "help",
      });
    },
    onSubmit(values) {
       
      this.form.id_pic_front = this.id_pic_front[0]
        ? this.id_pic_front[0].content
        : "";
      this.form.id_pic_back = this.id_pic_back[0]
        ? this.id_pic_back[0].content
        : "";
      this.form.user_pic_front = this.user_pic_front[0]
        ? this.user_pic_front[0].content
        : "";
      this.form.user_pic_back = this.user_pic_back[0]
        ? this.user_pic_back[0].content
        : "";
      this.form.contract_pic = this.contract_pic[0]
      ? this.contract_pic[0].content
      : "";
      this.form.evidence_pic = this.evidence_pic[0]
      ? this.evidence_pic[0].content
      : "";
      const index_void = Object.values(this.form).indexOf("");
    
      if (index_void >= 0) {
        Toast("请完善信息！");
        return;
      }
      createJYSUser(this.form)
        .then(
          (res) => {
            
            if (res.code === 0) {
              this.$router.push({
                name: "editSuccess",
              });
            } else {
              Toast(res.info);
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
        getJYSNewPhoneCode(params)
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
    // 验证手机验证码
    checkVel() {
      if (this.form.new_mobile_code.length > 6) {
        this.form.new_mobile_code = this.form.new_mobile_code.slice(0, 6);
      }
      let velrule = /^\d{6}$/;
      if (velrule.test(this.form.new_mobile_code)) {
        return true;
      } else {
        Toast("请输入6位数字验证码");
        return false;
      }
    },
  },
};
</script>

<style lang="less" scoped>
.header {
  height: 36px;
  line-height: 36px;
  background-color: #ffffff;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1;
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
.inline {
  display: inline-block;
  margin-bottom: 17px;
}
.id_pic_back,
.user_pic_back {
  margin-left: 17px;
}
.upload_box {
  padding-left: 4.267vw;
  padding-top: 17px;
  //   padding-bottom: 17px;
  background: #fff;
  margin-bottom: 10px;
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
</style>