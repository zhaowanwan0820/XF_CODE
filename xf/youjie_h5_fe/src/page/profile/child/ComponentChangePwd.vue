<template>
  <div class="wrapper">
    <div class="items-wrapper">
      <div class="item">
        <span class="label">原密码</span>
        <div class="input-wrp">
          <input
            type="password"
            v-model="pwd0"
            @input="checkPwd"
            @blur="checkOldPwdOnBlur"
            placeholder="请输入原6位数字密码"
          />
        </div>
      </div>
      <div class="item">
        <span class="label">新密码</span>
        <div class="input-wrp">
          <input type="password" v-model="pwd1" @input="checkPwd" placeholder="请输入6位数字密码" />
        </div>
      </div>
      <div class="item">
        <span class="label">确认密码</span>
        <div class="input-wrp">
          <input type="password" v-model="pwd2" @input="checkPwd" placeholder="请再次输入6位数字密码" />
        </div>
      </div>
    </div>
    <div :class="{ 'input-err-wrapper': true, visible: inputErrVisible }">
      <p class="error-msg">{{ inputErrMsg }}</p>
    </div>
    <div class="btn-wrapper">
      <button @click="submit" :class="{ enable: inputIsOk }">确认</button>
    </div>
  </div>
</template>
<script>
import { apiChangeTransitionPwd } from '../../../api/user'
const ruleAccept = /^\d{6}$/
const ruleExcept = /[^\d]/g

export default {
  name: 'ComponentSetAndConfirmPwd',
  data() {
    return {
      pwd0: '',
      pwd1: '',
      pwd2: '',
      inputErrMsg: '',
      inputIsOk: false
    }
  },
  computed: {
    inputErrVisible() {
      return this.inputErrMsg ? true : false
    }
  },
  methods: {
    checkPwd() {
      this.inputIsOk = false
      this.inputErrMsg = ''

      if (this.pwd0.length > 6) {
        this.pwd0 = this.pwd0.slice(0, 6)
      }
      if (this.pwd1.length > 6) {
        this.pwd1 = this.pwd1.slice(0, 6)
      }
      if (this.pwd2.length > 6) {
        this.pwd2 = this.pwd2.slice(0, 6)
      }

      if (ruleExcept.test(this.pwd0) || ruleExcept.test(this.pwd1) || ruleExcept.test(this.pwd2)) {
        this.inputErrMsg = '密码只能输入数字'
        return
      }

      // 原密码
      if (ruleAccept.test(this.pwd0)) {
        // 新密码 输入完成后的校验
        if (ruleAccept.test(this.pwd1) && ruleAccept.test(this.pwd2)) {
          if (this.pwd1 !== this.pwd2) {
            this.inputErrMsg = '两次输入的新密码不一致'
            return
          }
          this.inputIsOk = true
        }
      }
    },
    checkOldPwdOnBlur() {
      if (this.pwd0.length < 6) {
        this.inputErrMsg = '原密码长度应为6位数字'
      }
    },
    submit() {
      if (!this.inputIsOk) {
        return
      }
      this.post()
    },
    post() {
      this.$indicator.open('提交中...')
      apiChangeTransitionPwd({ oldPassword: this.pwd0, newPassword: this.pwd1 })
        .then(
          res => {
            if (res.status != 200) {
              this.pwd0 = ''
              return this.$toast({
                message: res.message
              })
            }
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
.desc {
  font-size: 12px;
  font-weight: 400;
  color: rgba(51, 51, 51, 1);
  line-height: 17px;
  padding: 11px 0 0 24px;
}
.items-wrapper {
  padding: 33px 18px 0 0;
  .item {
    display: flex;
    align-items: center;
    margin-top: 20px;

    .label {
      font-size: 15px;
      font-weight: 300;
      color: rgba(51, 51, 51, 1);
      line-height: 21px;
      width: 78px;
      text-align: right;
    }
    .input-wrp {
      flex: 1 0 0;
      padding-left: 9px;
    }
    input {
      width: 100%;
      line-height: 22px;
      text-indent: 11px;
      border: 0;
      padding: 0;
      outline: none;
      font-size: 16px;
      color: #333;
      border: 1px solid rgba(221, 221, 221, 1);
      height: 40px;
      box-sizing: border-box;
    }
    input::-webkit-input-placeholder {
      color: rgba(204, 204, 204, 1);
      font-size: 15px;
    }
  }
}
.input-err-wrapper {
  visibility: hidden;
  &.visible {
    visibility: visible;
  }
  padding: 12px 18px 0 87px;
  .error-msg {
    font-size: 15px;
    color: rgba(224, 32, 32, 1);
    height: 21px;
    line-height: 21px;
  }
}
.btn-wrapper {
  width: 100%;
  margin-top: 50px;
  text-align: center;
  button {
    width: 327px;
    height: 46px;
    border: 0;
    color: #fff;
    background-color: $primaryColorDisable;
    border-radius: 2px;
    font-size: 16px;
    outline: none;
    pointer-events: none;

    &.enable {
      background: $primaryColor;
      pointer-events: auto;
    }
  }
}
</style>
