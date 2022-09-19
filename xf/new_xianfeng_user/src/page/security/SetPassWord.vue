<template>
  <div class="container">
    <common-header :title="title" style="border-bottom: 1px solid #F4F4F4;"></common-header>
    <div class="label-tip" v-if="errorToken">请设置6位数字交易密码</div>
    <!-- 设置密码-->
    <div class="password-set" v-if="!isSetPassWord && errorToken">
      <div class="item">
        <div class="label">交易密码</div>
        <input type="password" v-model="params.password1" maxlength="6">
      </div>
      <div class="error-tip"><span v-show="showError">{{errorInfo}}</span></div>
      <div class="item">
        <div class="label">确认密码</div>
        <input type="password" v-model="params.password2" maxlength="6">
      </div>
      <div class="confirm-btn" @click="setPassword">
        <span>确认</span>
      </div>
    </div>
    <!-- 修改密码-->
    <div class="password-modify" v-if="isSetPassWord">
      <div class="item">
        <div class="label">旧密码</div>
        <input type="password" v-model="params.old_password" maxlength="6">
      </div>
      <div class="tip-box">
        <div class="error-tip"><span v-show="showError">{{errorInfo}}</span></div>
        <div class="find-password" @click="findPassWord">找回密码</div>
      </div>
      <div class="item">
        <div class="label">新密码</div>
        <input type="password" v-model="params.new_password" maxlength="6">
      </div>
      <div class="item">
        <div class="label">确认密码</div>
        <input type="password" v-model="params.password2" maxlength="6">
      </div>
      <div class="confirm-btn" @click="changPassWord">
        <span>确认</span>
      </div>
    </div>
  </div>
</template>

<script>
  import commonHeader from "@/components/CommonHeader.vue";
  import {
    setPassWordRequest,
    changePWRequest,
    checkSetPwdUrlRequest
  } from '../../api/SetPassWord.js'
  import {
    Toast
  } from 'vant';
  export default {
    components: {
      commonHeader,
    },
    data() {
      return {
        title: '交易密码设置',
        set_password: 0, // 交易密码状态：1-已设置，2-未设置
        sign_agreement: 0, // 先锋服务协议状态：1-已签署，2-未签署
        isSetPassWord: false,
        showError: false, //是否显示错误信息
        errorInfo: '',
        params: {
          password1: '', //交易密码
          password2: '', //交易密码确认
          old_password: '', //旧密码
          new_password: '', //新密码
          token:'' //外部进来时的token
        },
        errorToken:true,
        securityFlag : false
      }
    },
    created() {
      this.init();
    },
    methods: {
      //初始化处理参数
      init() {
        //内部交易密码
        console.log(this.$route.query);
        // 交易密码状态：1-已设置，2-未设置
        this.set_password = this.$route.query.set_password;
        this.sign_agreement = this.$route.query.sign_agreement;
        this.securityFlag = this.$route.query.securityFlag;
        if (this.set_password == 1) { //已设置
          this.isSetPassWord = true;
          this.title = "交易密码修改";
        } else if (this.set_password == 2) { //未设置
          this.isSetPassWord = false;
          this.title = "交易密码设置"
        }
        //校验外部设置交易密码地址有效性
        let href = window.location.href
        this.params.token = this.utils.getUrlKey(href, 'token');
        if(this.params.token){
          checkSetPwdUrlRequest(this.params).then(res=>{
            console.log(res);
            if(res.code===0){

            }else{
              this.errorToken = false
              Toast(res.info);
            }
          })
        }
      },
      findPassWord() {
        this.$router.push({
          path: '/findPassWord',
        })
      },
      //设置交易密码
      setPassword() {
        let password1 = this.params.password1;
        let password2 = this.params.password2;
        console.log(password1 + '=' + password2)
        if (password1 != password2) {

          Toast("两次密码不一致");
          return;
        } else if (password1.length != 6) {

          Toast("请输入6位数密码");
          return;
        }
        setPassWordRequest(this.params).then(
          res => {
            console.log(res);
            if (res.code === 0) {
             if(!this.params.token){
               //内部调用
               Toast.success('交易密码已成功设置');
               //"set_password": 1 // 交易密码状态：1-已设置，2-未设置
               this.set_password = 1; //已设置
               if(this.securityFlag){
                 this.$router.push({
                   path: "/security",
                   query: {
                     set_password: this.set_password, // 交易密码状态：1-已设置，2-未设置
                     sign_agreement: this.sign_agreement // 先锋服务协议状态：1-已签署，2-未签署
                   }
                 });
               }else{
                 localStorage.setItem('is_set_pay_password',true);
                 window.history.go(-1);
               }

             }else{
               //外部商城调用
              Toast.success('交易密码已成功设置');
              window.location.href = res.data.url
             }
            } else {
              Toast(res.info)
            }
          },
          error => {
            console.log(error);

          }
        )
      },

      //交易密码修改
      changPassWord() {
        let old_password = this.params.old_password;
        let new_password = this.params.new_password;
        let password2 = this.params.password2;
        if (old_password.length != 6) {
          Toast("请输入6位数旧密码")
        } else if (new_password != password2) {
           Toast("两次密码不一致")
          return;
        } else if (new_password.length != 6) {
           Toast("请输入6位数新密码")
          return;
        }
        changePWRequest(this.params).then(res => {
          console.log(res);
          if (res.code === 0) {
            Toast.success('交易密码已修改成功');
            window.history.go(-1);
          } else {
            Toast(res.info);
          }
        })
      }

    }

  }
</script>

<style lang="less" scoped>
  .label-tip {
    font-size: 12px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(112, 112, 112, 1);
    margin: 56px 0 40px 15px;
  }

  .error-tip {
    font-size: 12px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(231, 29, 54, 1);
    margin: 8px 30px 0 30px;

  }

  .item {
    display: flex;
    flex-direction: row;
    margin: 15px 30px 0 30px;
    padding-bottom: 15px;
    border-bottom: 1px dashed rgba(56, 52, 222, 0.5);

    .label {
      margin-right: 15px;
      font-size: 16px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(112, 112, 112, 1);
    }

    input {
      border: none;
      outline: none;
      flex: 1;
    }
  }

  .confirm-btn {
    margin: 50px 55px 0 41px;
    text-align: center;

    span {
      display: inline-block;
      width: 100%;
      height: 50px;
      line-height: 50px;
      background: rgba(57, 52, 223, 1);
      border-radius: 25px;
      font-size: 18px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(255, 255, 255, 1);
    }
  }

  .tip-box {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    padding-bottom: 24px;

    .error-tip {
      flex: 1;
      font-size: 12px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(231, 29, 54, 1);
      margin-top: 0;
    }

    .find-password {
      margin-right: 30px;
      width: 74px;
      height: 25px;
      line-height: 25px;
      text-align: center;
      background: rgba(57, 52, 223, 0.06);
      border-radius: 13px;
      font-size: 12px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(57, 52, 223, 1);
    }
  }
</style>
