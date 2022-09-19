<template>
  <div class="login-wrapper">
    <div class="bg" />
    <main class="main">
      <div class="logo" />
      <div class="form">
        <van-field v-model="tel" type="number" left-icon="xf-tel" placeholder="请输入手机号" maxlength="11" />
        <van-field v-model="sms" type="number" center left-icon="xf-sms" placeholder="请输入短信验证码" maxlength="6">
          <template #button>
            <div class="divider--vertical"></div>
            <van-button :disabled="tel.length != 11 || smsCD > 0" @click="onSMS">
              {{ smsCD > 0 ? `等待 ${smsCD} 秒` : '获取验证码' }}
            </van-button>
          </template>
        </van-field>
        <van-checkbox v-model="checked" shape="square">
          <label>
            我已阅读并同意
            <span @click="$router.push({ name: 'agreement' })">《注册协议及隐私保护政策》</span>
          </label>
        </van-checkbox>
        <van-button class="submit" :disabled="tel.length != 11 || sms.length != 6 || once || !checked" @click="onLogin"
          >授权登录</van-button
        >
      </div>
    </main>
    <footer class="footer">Copyright © China UCF Group Co., Limited.</footer>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'
import { parseParams } from '@/utils'

export default {
  name: 'Login',
  beforeRouteEnter(_, from, next) {
    // 若是通过返回跳过来的，就在往前跳
    if (process.env.NODE_ENV !== 'development' && !from.name) {
      next(vm => {
        vm.__goBack()
      })
    } else {
      next()
    }
  },
  data() {
    return {
      once: false,
      tel: process.env.NODE_ENV === 'development' ? '13716970622' : '',
      sms: process.env.NODE_ENV === 'development' ? '123456' : '',
      smsCD: 0,
      checked: false,
    }
  },
  watch: {
    ticket: {
      handler() {
        this.once = false
      },
      deep: true,
    },
  },
  computed: {
    ...mapGetters({
      redirectUrl: 'redirectUrl',
    }),
    ticket() {
      const { tel, sms } = this
      return {
        tel,
        sms,
      }
    },
  },
  mounted() {
    this.creatInterval()
  },
  methods: {
    ...mapActions({
      changeEnv: 'changeEnv',
    }),
    creatInterval() {
      const timer = setInterval(() => {
        if (this.smsCD) {
          this.smsCD--
        }
      }, 1e3)
      this.$once('hook:destroyed', () => {
        clearInterval(timer)
      })
    },
    onSMS() {
      this.smsCD = 60
      this.sms = ''
      this.$services.phone.getSmsVcode(this.tel).then(res => {
        const { data } = res
        if (data.code == 0) {
          this.$toast.success('发送成功')
        } else if (data.info) {
          this.smsCD = 0
          this.$toast.fail(data.info)
        }
      })
    },
    onLogin(res) {
      this.once = true
      this.$services.exchange.submitCheckAuth(this.tel, this.sms).then(res => {
        const { data } = res
        if (data.code == 0) {
          this.$toast.success({
            message: '登入成功',
            onClose: () => {
              location.href = parseParams(this.redirectUrl, data.data)
            },
          })
        } else if (data.info) {
          this.$toast.fail(data.info)
        }
      })
    },
    reset() {
      this.tel = ''
      this.sms = ''
      this.smsCD = 0
    },
  },
}
</script>

<style lang="scss">
@import 'assets/scss/mixin';

@mixin inner {
  width: 95%;
  max-width: 330px;
  margin: 0 auto;
}

#app .van-nav-bar {
  &.header-navbar--login {
    background-color: #3834df;
    margin-bottom: -1px;
    .van-ellipsis {
      color: #fff;
    }
  }
}

.login {
  &-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    .bg {
      width: 100%;
      height: 300px;
      background: center center no-repeat url('~images/login/bg.svg');
      background-size: 100% 300px;
      position: absolute;
      z-index: 1;
    }
    .main {
      @include inner;
      flex-grow: 1;
      z-index: 2;

      .logo {
        height: 31px;
        background: center center no-repeat url('~images/login/先峰logo@3x.png');
        background-size: 214px 31px;
        margin: 63px 0 25px;
      }
    }
    .form {
      height: 300px;
      background: #fff;
      box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.05);
      border-radius: 10px;
      padding: 24px 15px 0;
      .van-field {
        height: 40px;
        background: #f9f9f9;
        border-radius: 100px;
        margin-top: 20px;
        input::placeholder {
          color: #9b9b9b;
        }
        .divider--vertical {
          background-color: #ccc;
          width: 1px;
          float: left;
          margin: 1px 0 1px -8px;
          height: 40px;
          transform: scale(0.5);
          transform-origin: left;
        }
        .van-button {
          color: #3834df;
          background: transparent;
          border-color: transparent;
          margin-right: -7px;
          &:hover {
            background: #f9f9f9;
          }
        }
        .van-icon {
          &-xf-tel {
            width: 14px;
            height: 21px;
            background: center center no-repeat url('~images/login/手机@3x.png');
            background-size: 14px 21px;
          }
          &-xf-sms {
            width: 15px;
            height: 17px;
            background: center center no-repeat url('~images/login/验证码@3x.png');
            background-size: 15px 17px;
          }
        }
      }
      .van-checkbox {
        margin-top: 20px;
        label {
          font-size: 10px;
          color: slategray;
        }
        span {
          color: rgb(56, 53, 223);
        }
        margin-left: 15px;
      }
      .van-checkbox__icon--checked .van-icon-success {
        color: #fff;
        background-color: #3834df;
        border-color: #3834df;
      }
      .submit {
        width: 100%;
        font-size: 20px;
        color: #fff;
        background: #3834df;
        border-radius: 100px;
        margin-top: 20px;
      }
    }
    .footer {
      @include inner;
      @include sc(9);
      height: 50px;
      color: #9b9b9b;
      text-align: center;
      font-family: ArialMT;
    }
  }
}
</style>
