<template>
    <div class="container">
        <mt-header class="header" fixed :title="title">
            <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        </mt-header>
        <div class="bankcard">
            <div class="bankcard-title">
                <p class="bank-name">持卡人: <span>李婷婷大王呀</span></p>
                <p class="bank-name">证件号: <span>152**********4098</span></p>
            </div>
            <div class="bankcard-box">
                <div class="bank-item">
                    <input class="bank-input" v-model.Number="form.card" type="number" placeholder="请输入您本人的储蓄卡卡号" @input="cardInput($event,'card')">
                </div>
                <div class="bank-item">
                    <input class="bank-input" v-model="form.phone" type="number" placeholder="请输入银行预留手机号" @input="cardInput($event,'phone')">
                </div>
                <div class="bank-item code">
                    <input class="bank-input bank-input-code" type="text" v-model="form.code" placeholder="请输入短信验证码" @input="cardInput($event,'code')">
                    <div class="btn-code" :class="{ disabled: isDis }" @click="sendCode" :disabled="isDis">{{auth_time}}</div>
                </div>
            </div>
            <div class="bankcard-info">
                <p>查看 <span>支持的银行</span></p>
            </div>
            <div class="bankcard-btn">
                <div class="bank-btn" :class="{ disabled: isBtn }" @click="submit" :disabled="isBtn">立即验证</div>
            </div>
        </div>
    </div>
</template>

<script>
  import { Header, Toast, Popup, MessageBox,Indicator } from "mint-ui";
  export default {
    name: 'BankCardAdd',
    data () {
      return {
        title: '绑定银行卡',
        form:{
          card:'',
          phone:'',
          code:'',
        },
        isDis:true,
        isBtn:true,
        sendAuthCode:true,
        auth_time: '点击获取',
        auth_str:null,
      }
    },
    created() {
      console.log(this.$route)
      this.title = this.$route.params.title?'绑定银行卡':'银行卡快捷验证'
    },
    methods: {
      goBack() {
        this.$_goBack()
      },
      sendCode(){
        let that = this;
        let telrule = /^1[3456789]\d{9}$/;
        if (!telrule.test(that.form.phone)) {
          Toast("请输入正确手机号");
          return;
        }
        if(that.sendAuthCode){
          that.getCode()
          that.sendAuthCode = false;
          that.auth_time = 60;
          that.auth_str =  setInterval(()=>{
            that.auth_time--;
            if(that.auth_time<=0){
              that.sendAuthCode = true;
              clearInterval(that.auth_str);
              that.auth_time = '重新获取'
            }
          }, 1000);
        }
      },
      cardInput(e,type){
        // console.log(e.target.value)
        let str = e.target.value;
        if(type == 'card'){
          if(str.length <= 20){
            this.form.card = str;
          }else {
            this.form.card = str.substr(0,20);
          }
        }else if(type == 'phone'){
          if(str.length <= 11){
            this.form.phone = str;
          }else {
            this.form.phone = str.substr(0,11);
          }
          if(this.form.phone.length == 11){
            this.isDis = false;
          }else {
            this.isDis = true;
          }
        }else if(type == 'code'){
          if(str.length>0){
            this.isBtn = false;
          }else {
            this.isBtn = true;
          }
        }
      },
      getCode(){
        console.log(111)
      },
      submit(){
        let telrule = /^1[3456789]\d{9}$/;
        if (!telrule.test(this.form.phone)) {
          Toast("请输入正确手机号");
          return false;
        }
        console.log(22)
      }
    }
  }
</script>

<style lang="scss" scoped>
    .container {
        height: calc(100% - 54px);
        padding-top: 54px;
        background-color: #fff;
        .header {
            @include header;
        }
        .bankcard{
            .bankcard-title{
                margin: 33px 40px 21px;
                .bank-name{
                    font-size:16px;
                    font-family:PingFangSC-Regular,PingFang SC;
                    font-weight:400;
                    color:rgba(153,153,153,1);
                    span{
                        font-size:16px;
                        font-family:PingFangSC-Regular,PingFang SC;
                        font-weight:400;
                        color:rgba(51,51,51,1);
                    }
                    &:nth-child(1){
                        margin-bottom: 15px;
                    }
                }
            }
        }
        .bankcard-box{
            background:rgba(255,255,255,1);
            /*box-shadow:0px 0px 8px 0px rgba(0,0,0,0.15);*/
            box-shadow:0px -5px 8px 0px rgba(0,0,0,0.1);
            border-radius:16px 16px 8px 8px;
            padding: 31px 20px 0;
            .bank-item{
                border:1px solid rgba(221,221,221,1);
                .bank-input{
                    width: 90%;
                    height:40px;
                    line-height: 40px;
                    margin-left: 10px;
                    font-size:15px;
                    font-family:PingFangSC-Regular,PingFang SC;
                    font-weight:400;
                    color:rgba(64,64,64,1);
                }
                margin-bottom: 20px;
            }
            .code{
                display: flex;
                align-items: center;
                .bank-input-code{
                    width: calc( 100% - 116px);
                }
            }
        }
        .bankcard-info{
            margin: 20px;
            p{
                font-size:15px;
                font-family:PingFangSC-Light,PingFang SC;
                font-weight:300;
                color:#5E5E5E;
                span{
                    color: #FC7F0C;
                }
            }
        }
        .bankcard-btn{
            border-radius:2px;
            width: 90%;
            margin: 127px auto 0;
        }
    }
    .bank-btn{
         height:46px;
         line-height: 46px;
         font-size:18px;
         background:rgba(252,127,12,1);
         font-family:PingFangSC-Regular,PingFang SC;
         font-weight:400;
         color:rgba(255,255,255,1);
         text-align: center;
         text-indent: 2px;
     }
    .btn-code{
        width: 96px;
        height:42px;
        line-height: 42px;
        background:rgba(252,127,12,1);
        font-size:15px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:400;
        color:rgba(255,255,255,1);
        text-align: center;
        margin-left: 10px;
    }
    .disabled{
        background:rgba(252,127,12,0.3);
        color:rgba(255,255,255,.5);
        pointer-events: none;
    }
    input{
        background:none;
        outline:none;
        border:none;
    }
    input:focus{
        border: none;
    }
    input::-webkit-input-placeholder {
        color: rgba(204,204,204,1);
        font-size:15px;
        font-family:PingFangSC-Light,PingFang SC;
        font-weight:300;
    }
</style>