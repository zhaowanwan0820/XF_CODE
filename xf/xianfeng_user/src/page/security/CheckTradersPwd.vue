<template>
  <div class="container">
    <common-header :title="title" style="border-bottom: 1px solid #F4F4F4;"></common-header>
    <div class="label-tip" v-if="checkUrl">请输入6位数字交易密码</div>
    <!-- 验证交易密码-->
    <div class="password-set" v-if="checkUrl">
      <div class="item">
        <div class="label">交易密码</div>
        <input type="password" maxlength="6" v-model="params.password" @input="inputChange">
      </div>
      <div class="error-tip" v-if="showError">{{errorInfo}}</div>
      <div class="confirm-btn" @click="checkPwdBtn">
        <span>确定</span>
      </div>
    </div>
  </div>
</template>

<script>
  import {
    checkTradersPwdUrlRequest,
    checkTradersPwdRequest
  } from '../../api/SetPassWord.js'
  import {
    Toast
  } from 'vant';
  export default {
    data() {
      return {
        title: '验证交易密码',
        showError: false,
        errorInfo: '',
        params: {
          token: '',
          password: ''
        },
        checkUrl:true

      }
    },
    created() {
      this.init()
    },
    methods: {
      //初始化
      init() {
        //外部交易密码
        let href = window.location.href
        this.params.token = this.utils.getUrlKey(href, 'token');
        if(this.params.token){
          checkTradersPwdUrlRequest(this.params).then(res => {
            if(res.code === 0){

            }else{
              this.checkUrl = false
              Toast(res.info)
            }
          })
        }
      },
      checkPwdBtn() {
        if (this.params.password.length != 6) {
          this.showError = true
          this.errorInfo = '请输入6位数字交易密码'
          return
        }
        checkTradersPwdRequest(this.params).then(res=>{
          if(res.code === 0){
            Toast("验证成功")
            window.location.href = res.data.url
          }else{
            Toast(res.info)
          }
        })
      },
      inputChange() {
        if (this.params.password.length == 6) {
          this.showError = false
          this.errorInfo = ''
        }
      }

    },
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
