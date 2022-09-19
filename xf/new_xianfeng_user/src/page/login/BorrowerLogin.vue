<template>
  <div class="page-wrapper">
    <div class="header-container">
      <div class="header">
        <div class="wrap">
          <div class="title">借款人登录</div>
        </div>
      </div>
    </div>
    <div class="container-wrapper">
      <!-- <div class="logo"></div> -->
      <!-- 手机号 + 验证码提交表单 -->
      <form-submit-by-card-and-code
        v-on:submit-success="submitSuccess"
      ></form-submit-by-card-and-code>
    </div>
    <div class="xf-logo">
      <img src="../../static/images/xianfeng-logo.png" />
    </div>
  </div>
</template>
<script>
import { mapState, mapMutations, mapGetters } from "vuex";
import FormSubmitByCardAndCode from "./child/ComponentSubmitByCardAndCode";
import commonHeader from "@/components/CommonHeader.vue";
import { getPersonalInfo } from "../../api/user";

import { Toast } from "vant";
export default {
  name: "borrowerLogin",
  data() {
    return {
      from:''
    };
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      token: state => state.auth.token
    })
  },
  components: {
    commonHeader,
    FormSubmitByCardAndCode
  },
  mounted() {
    document
      .querySelector("body")
      .setAttribute("style", "background-color:#F4F4F4");
  },
  beforeDestroy() {
    document.querySelector("body").removeAttribute("style");
  },
  created() {
    this.from = this.$route.query.from;
    if (this.token) {
      this.$router.replace({
        name: "home"
      });
    }
  },
  methods: {
    ...mapMutations({
      saveToken: "saveToken"
    }),
    submitSuccess(res) {
      Toast({
          message: "登录成功"
        });
      
      this.saveToken(res.data.token);
      this.$router.replace({
            name: "borrowerVoucher"
          });
    },
    toEditPhone(){
      this.$router.push({
        path: "/editPhoneList",
        
      });
    },
  }
};
</script>
<style lang="less" scoped>
html {
  background: #f4f4f4;
  height: 100%;
}
.title{
  margin-top: 40px;
  font-size: 25px;
}

.edit-phone{
  text-align: center;
  margin-top: 20px;
  font-size: 13px;
  color: #3834de;
}
.page-wrapper {
  width: 100%;
  height: 100%;
  background: #f4f4f4;
  display: flex;
  flex-direction: column;
}
.header {
  height: 50px;
  color: #fff;
  font-size: 18px;
  text-align: center;
  line-height: 50px;
  // position: fixed;
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
}

.container-wrapper {
  padding-top: 166px;
  // padding: 160px 15px 0;
  // display: flex;
  // flex-direction: column;
  background: url("../../static/images/login-banner.png") left top no-repeat;
  background-size: 100%;
  // height: 298px;

  .agreement {
    height: 20px;
    margin-top: 15px;

    p {
      color: #ccc;
      font-size: 12px;
      line-height: 20px;

      span {
        color: #999;
        font-size: 12px;
      }
    }
  }

  .go-service {
    @include sc(12px, #fc7f0c);
    margin-top: 10px;
    text-align: center;
  }

  .tips {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding-bottom: 20px;
    font-size: 12px;
    text-align: center;
    color: #999999;
  }
}

.xf-logo {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  padding-top: 105px;
  background: #f4f4f4;
  img {
    width: 345px;
    height: 47px;
  }
}
</style>
