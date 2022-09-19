<template>
  <div class="wrapper" :class="fromClass">
    <div class="tel-input">
      <div class="tel-wrapper">
        <input type="number" v-model="tel" @input="checkTel" placeholder="请输入手机号" />
      </div>
      <div class="vel-wrapper">
        <div class="input-wrp">
          <input type="number" v-model="vel" @input="checkVel" placeholder="请输入验证码" />
        </div>
        <div
          class="vel"
          v-if="!isCountDown || code_type"
          :class="{ deep: telIsOk && (!code_type || !isCountDown) }"
          @click="getSmsCode"
        >
          获取验证码
        </div>
        <div class="vel" v-if="isCountDown && !code_type">{{ countDown }}s</div>
      </div>
    </div>
    <div class="login">
      <button @click="submit" :class="{ disabled: !telIsOk || !velIsOk }">{{ subBtnTxt }}</button>
    </div>
  </div>
</template>
<script>
import { mapState, mapMutations } from 'vuex'
import { getVelCode, getLogin } from '../../../api/user'
import { ENUM } from '../../../const/enum'

export default {
  name: 'componentSubmitByPhoneAndCode',
  data() {
    return {
      tel: process.env.NODE_ENV === 'production' ? '' : '10002065571',
      vel: process.env.NODE_ENV === 'production' ? '' : '111111', // 这个值是多少就登录UID为多少的用户
      isCountDown: false, //是否展示倒计时
      countDown: 60,
      telIsOk: process.env.NODE_ENV !== 'production', //是否为11位手机号码
      velIsOk: process.env.NODE_ENV !== 'production',
      code_type: 0, //验证码类型 0短信验证码 1语音验证码
      isOnce: true, //是否第一次获取短信验证码
      isVoiceOnce: true, //是否第一次获取语音验证码
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
      default: '登　录'
    },
    fromClass: String
  },
  computed: {
    ...mapState({
      openId: state => state.login.openId
    })
  },
  methods: {
    ...mapMutations({
      saveTmpPhone: 'saveTmpPhone'
    }),
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
      this.code_type = ENUM.LOGON_CODE.SMS
      this.getVel(this.code_type)
    },
    // 获取语音验证码
    getVoiceCode() {
      this.$messageBox({
        title: '发送语音验证码',
        message: '验证码将以电话形式通知您，请留意您的电话',
        showCancelButton: true,
        confirmButtonText: '获取',
        cancelButtonText: '取消'
      }).then(action => {
        if (action == 'confirm') {
          if (this.isVoiceOnce) this.isVoiceOnce = false
          this.code_type = ENUM.LOGON_CODE.VOICE
          this.getVel(this.code_type)
        }
      })
    },
    // 获取验证码
    getVel(is_voice) {
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
      this.getVelApi(this.tel, is_voice, this.openId ? 1 : 0)
        .then(
          res => {
            if (res.code === 0) {
              this.isCountDown = true
              this.getCountDown()
            }
            if (res.code === 2033 || res.code === 2034) {
              this.$messageBox({
                title: '',
                message: res.info,
                confirmButtonText: '知道了'
              }).then(action => {
                if (this.isOnce) {
                  this.isOnce = false //短信验证码超过次数后重新进入登录页面获取验证码 打开语音验证码入口
                }
              })
            } else {
              this.$toast({
                message: res.info
              })
            }
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
            if (this.isOnce) this.isOnce = false
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
      this.login(this.tel, this.vel)
    },
    login(tel, val) {
      this.$indicator.open('提交中...')
      this.submitApi({ mobile: tel, valicode: val, openid: this.openId })
        .then(
          res => {
            if (res.code === 2405) {
              this.isCountDown = false
              this.isVelGeting = false
            }
            if (res.code && res.code !== 0) {
              this.$toast({
                message: res.info
              })
              return
            }
            this.saveTmpPhone(tel)
            this.$emit('submit-success', res)
          },
          error => {
            this.$toast({
              message: error.errorMsg
            })
          }
        )
        .finally(() => {
          this.$indicator.close()
        })
    }
  }
}
</script>
<style lang="scss" scoped>
// 不同功能的手机号+验证码的UI样式可能会有差异，可以在prop传入自定义的fromClass，然后在下边的css文件中自定义相应的样式
@import './ComponentSubmitByPhoneAndCode.scss';
</style>
