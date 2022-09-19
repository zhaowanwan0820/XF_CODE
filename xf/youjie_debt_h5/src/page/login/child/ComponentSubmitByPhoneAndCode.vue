<template>
  <div class="wrapper">
    <div class="tel-input">
      <div class="tel-wrapper">
        <input type="number" v-model="tel" @input="checkTel" placeholder="请输入手机号" />
      </div>
      <div class="vel-wrapper">
        <div class="input-wrp">
          <input type="number" v-model="vel" @input="checkVel" placeholder="请输入验证码" />
        </div>
        <div class="vel" v-if="!isCountDown" :class="{ deep: telIsOk && !isCountDown }" @click="getSmsCode">
          获取验证码
        </div>
        <div class="vel" v-else>{{ countDown }}s</div>
      </div>
    </div>
    <div class="login">
      <button @click="submit" :class="{ disabled: !telIsOk || !velIsOk }">{{ subBtnTxt }}</button>
    </div>
  </div>
</template>
<script>
import { getVelCode, getLogin } from '../../../api/user'

export default {
  name: 'componentSubmitByPhoneAndCode',
  data() {
    return {
      tel: process.env.NODE_ENV === 'production' ? '' : '18920820651',
      vel: process.env.NODE_ENV === 'production' ? '' : '111111',
      isCountDown: false, //是否展示倒计时
      countDown: 60,
      telIsOk: process.env.NODE_ENV !== 'production', //是否为11位手机号码
      velIsOk: process.env.NODE_ENV !== 'production',
      isVelGeting: false //是否正在请求获取验证码接口，防止重复点击
    }
  },
  props: {
    getVelApi: {
      // 获取短信验证码接口
      type: Function,
      default: getVelCode
    },
    submitApi: {
      // 表单提交接口
      type: Function,
      default: getLogin
    },
    subBtnTxt: {
      type: String,
      default: '登录/注册'
    }
  },
  methods: {
    // 验证手机号
    checkTel() {
      if (this.tel.length > 11) {
        this.tel = this.tel.slice(0, 11)
      }
      let telrule = /^1\d{10}$/
      let twrule = /^09\d{8}$/
      if (!telrule.test(this.tel) && !twrule.test(this.tel)) {
        this.telIsOk = false
      } else {
        this.telIsOk = true
      }
    },
    // 验证手机验证码
    checkVel() {
      if (this.vel.length > 6) {
        this.vel = this.vel.slice(0, 6)
      }
      let velrule = /^\d{6}$/
      if (velrule.test(this.vel)) {
        this.velIsOk = true
      } else {
        this.velIsOk = false
        return
      }
    },
    // 获取短信验证码
    getSmsCode() {
      this.getVel()
    },
    // 获取验证码
    getVel() {
      if (!this.telIsOk) {
        return
      }

      if (this.isCountDown) {
        return
      }

      if (this.isVelGeting) {
        return
      }

      this.isVelGeting = true
      getVelCode(this.tel)
        .then(
          res => {
            if (res.code === 0) {
              this.isCountDown = true
              this.getCountDown()
            } else {
              this.$toast({
                message: res.info || res.error_data.info
              })
            }
            // if (res.code === 2033 || res.code === 2034) {
            //   this.$dialog.confirm({
            //     title: '',
            //     message: res.info,
            //     confirmButtonText: '知道了'
            //   })
            // } else {

            // }
          },
          error => {
            console.log(error)
          }
        )
        .finally(() => {
          this.isVelGeting = false
        })
    },
    //获取验证码倒计时
    getCountDown() {
      if (this.isCountDown) {
        let timer = setInterval(() => {
          this.countDown--
          if (this.countDown <= 0) {
            this.isCountDown = false
            this.countDown = 60
            clearInterval(timer)
          }
        }, 1000)
      }
    },
    submit() {
      if (!this.telIsOk) {
        this.$toast({
          message: '请输入11位手机号'
        })
        return
      }
      if (!this.velIsOk) {
        this.$toast({
          message: '请输入6位数字验证码'
        })
        return
      }
      this.login()
    },
    login() {
      this.$loading.open()
      let responce = null
      getLogin({ phone: this.tel, verification_code: this.vel })
        .then(
          res => {
            responce = res
          },
          error => {
            this.$toast({
              message: error.errorMsg
            })
          }
        )
        .finally(() => {
          this.$loading.close()
          this.$emit('submit-success', responce)
        })
    }
  }
}
</script>
<style lang="less" scoped>
.wrapper {
  margin-top: 4px;
  .tel-input {
    width: 100%;
    input {
      width: 100%;
      line-height: 21px;
      text-indent: 24px;
      border: 0;
      padding: 0;
      outline: none;
      font-size: 15px;
      color: #333;
      background-repeat: no-repeat;
      background-size: 20px 20px;
      background-position: 0 50%;
    }
    input::-webkit-input-placeholder {
      color: #ccc;
      font-size: 15px;
    }
    .tel-wrapper {
      position: relative;
      padding: 24px 0 10px;
      border-bottom: dotted 0.5px rgba(85, 46, 32, 0.2);
      input {
        background-image: url('../../../assets/image/login/icon-tel.png');
      }
    }
    .vel-wrapper {
      position: relative;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 24px 0 10px;
      border-bottom: dotted 0.5px rgba(85, 46, 32, 0.2);
      .input-wrp {
        flex: 1 0 0;
        input {
          width: 100%;
          background-image: url('../../../assets/image/login/icon-vel.png');
        }
      }
      .vel {
        flex: 0 0 95px;
        width: 95px;
        height: 16px;
        text-align: center;
        border-left: dotted 0.5px rgba(85, 46, 32, 0.2);
        color: @themeColor;
        font-size: 15px;
        line-height: 15px;
        pointer-events: none;
      }
      .deep {
        color: @themeColor;
        pointer-events: auto;
      }
    }
  }
  .voice_code {
    display: flex;
    justify-content: flex-end;
    margin-top: 15px;
    .vcd1 {
      font-size: 13px;
      font-weight: 300;
      color: #999;
      line-height: 20px;
      &.deep {
        color: #666;
      }
    }

    .vcd2 {
      font-size: 13px;
      font-weight: 400;
      color: rgba(119, 37, 8, 0.3);
      line-height: 20px;
      pointer-events: none;
      &.deep {
        color: #772508;
        pointer-events: auto;
      }
      span {
        color: #772508;
      }
    }
  }
  .login {
    width: 100%;
    text-align: center;
    button {
      width: 327px;
      height: 46px;
      border: 0;
      color: #fff;
      background-color: @themeColor;
      border-radius: 2px;
      outline: none;

      margin-top: 82px;
      font-size: 18px;
      font-weight: 300;
    }
    .disabled {
      opacity: 0.5;
      pointer-events: none;
    }
  }
}
</style>
