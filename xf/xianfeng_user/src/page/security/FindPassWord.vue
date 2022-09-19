<template>
  <div>
    <common-header :title="title" style="border-bottom: 1px solid #F4F4F4;"></common-header>
    <div class="input-box">
      <div class="item">
        <div class="label">手机号</div>
        <input type="number" maxlength="11" v-model="params.number" @input="inputChange">
      </div>
      <div class="error-tip" v-if="showError">{{errorInfo}}</div>
      <div class="item msg-item">
        <div class="label">验证码</div>
        <input type="number" v-model="params.code" maxlength="6"  @input="inputChange">
        <div class="getMsg" @click="getMsg"><span>{{smgText}}</span></div>
      </div>
    </div>
    <div class="password-box">
      <div class="item">
        <div class="label">新密码</div>
        <input type="password"  v-model="params.new_password"  maxlength="6" @input="inputChange">
      </div>
      <div class="item">
        <div class="label">确认密码</div>
        <input type="password"   v-model="params.password2"  maxlength="6" @input="inputChange">
      </div>
    </div>
    <div class="send-btn" @click="findPassWord">
      <span>发送</span>
    </div>
  </div>
</template>

<script>
  import commonHeader from "@/components/CommonHeader.vue";
  import {
    findPWRequest,
    getMsgRequest
  } from '../../api/SetPassWord.js'
  import {
    Toast
  } from 'vant';
  export default {
    components: {
      commonHeader
    },
    data() {
      return {
        title: '找回密码',
        set_password: 0,
        params: {
          new_password: '', //新交易密码
          code: '', //短信验证码
          number: '', //手机号
          password2:''//确认密码
        },
        showError: false,
        errorInfo: '',
        isSend: false,
        smgText:'获取验证码'
      }
    },
    methods: {
      //输入框的焦点事件
      inputChange() {
        if(this.params.number.length>11){
          this.params.number=this.params.number.slice(0,11);
        }
        if(this.params.code.length>6){
          this.params.code=this.params.code.slice(0,6);
        }
      },
      //获取验证码
      getMsg() {
        let that = this
        if(this.params.number.length != 11){
          Toast("请输入正确的手机号");
          return;
        }
        if (this.isSend) {
            return;
        }
        that.isSend = true;
        console.log(this.params.number);
        getMsgRequest(this.params).then(res => {
           console.log(res);
          if (res.code === 0) {
              that.countDown(res.data.ttl);
          }else{
            Toast(res.info);
            this.isSend = false;
          }
        })
      },
      countDown(second) {
        let that = this;
        if (second <= 0 || !this.isSend) {
          this.isSend = false;
          this.smgText= '重发验证码'
          return;
        }
        this.smgText= second+'s'
        setTimeout(function() {
          // 放在最后--
          second -= 1;
          that.countDown(second);
        }, 1000);
      },
      findPassWord(){
        let number = this.params.number;
        let code = this.params.code;
        if(number.length === 0){
          Toast('请输入手机号');
            return ;
        }
        if(code.length === 0){
          Toast('请点击【获得验证码】，输入短信发送的验证码！');
            return ;
        }
        let new_password = this.params.new_password;
        let password2 = this.params.password2;
        if (new_password != password2) {
          Toast('两次密码不一致');
          return;
        }else if(new_password.length !=6){
          Toast('请输入6位数密码');
          return;
        }
        console.log(typeof (new_password));
        findPWRequest(this.params).then(res=>{
          console.log(res);
          if(res.code === 0){
            Toast.success('密码重置成功');
            window.history.go(-1);
          }else{
            Toast(res.info);
          }
        })
      }

    }
  }
</script>

<style lang="less" scoped>
  .error-tip {
    font-size: 12px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(231, 29, 54, 1);
    margin: 8px 48px 0 48px;
  }
  .input-box{
    margin-top: 36px;
  }
  .item {
    display: flex;
    flex-direction: row;
    align-items: center;
    margin: 0 48px;
    padding-bottom: 15px;
    border-bottom: 1px dashed rgba(56, 52, 222, 0.5);
    margin-top: 60px;

    .label {
      font-size: 16px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(112, 112, 112, 1);
    }

    input {
      font-size: 16px;
      font-family: PingFangSC-Medium, PingFang SC;
      font-weight: 500;
      color: rgba(64, 64, 64, 1);
      line-height: 22px;
      height: 22px;
      margin-left: 15px;
      width: 125px;
      border: none;
      outline: none;
    }
  }
.password-box{
    .item{
      margin-top: 30px;

    }
  }
  .msg-item {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    margin-top: 55px;
    .label{
      // width: 65px;
    }
    input {
      flex: 1;
      width: 70px;
    }

    .getMsg {
      text-align: center;
      height: 28px;
      line-height: 28px;
      background: rgba(57, 52, 223, 1);
      border-radius: 14px;
      display: inline-block;
      font-size: 13px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(255, 255, 255, 1);
      margin-left: 10px;
      span{
        padding: 0 15px;
        display: inline-block;
      }
    }
  }

  .send-btn {
    margin: 60px 55px 0 41px;
    text-align: center;

    span {
      width: 100%;
      display: inline-block;
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
</style>
