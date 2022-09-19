<template>
  <div class="container">
    <mt-header class="header" title="设置交易密码">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div v-if="step === 1" class="phone-check-wrapper">
      <!-- 手机号 + 验证码提交表单 -->
      <form-submit-by-phone-and-code
        fromClass="transtionPwdSet"
        :getVelApi="getVelApi"
        :submitApi="submitApi"
        subBtnTxt="下一步"
        v-on:submit-success="step1Success"
      ></form-submit-by-phone-and-code>
    </div>
    <div v-if="step === 2" class="set-and-confirm-pwd-wrapper">
      <!-- 输入&再次输入 密码 -->
      <form-for-set-and-confirm-pwd v-on:submit-success="step2Success"></form-for-set-and-confirm-pwd>
    </div>
  </div>
</template>

<script>
import FormSubmitByPhoneAndCode from '../login/child/ComponentSubmitByPhoneAndCode'
import FormForSetAndConfirmPwd from './child/ComponentSetAndConfirmPwd'
import { apiGetVelCode, apiValidatePhoneCode } from '../../api/user'
export default {
  name: 'TransitionPwdSet',
  data() {
    return {
      getVelApi: apiGetVelCode,
      submitApi: apiValidatePhoneCode,
      step: 1,
      token: '', // 第一步校验成功后接口返回的token，给第二步设置接口以校验接口调用的合法性
      pas_agree: this.$route.query.pas_agree ? this.$route.query.pas_agree : ''
    }
  },
  components: {
    FormSubmitByPhoneAndCode,
    FormForSetAndConfirmPwd
  },
  methods: {
    step1Success(res) {
      this.$toast({ message: '验证成功' })
      // 保存验证成功的信息（a token)至本地，在第二步设置密码时给到接口以进行校验
      this.token = res.token
      setTimeout(() => {
        this.step = 2
      }, 1000)
    },
    step2Success(res) {
      this.$toast({ message: '密码设置成功' })
      setTimeout(() => {
        this.goBack()
      }, 1000)
    },
    goBack() {
      // if (this.pas_agree) {
      //   this.$router.replace({ name: 'newPlanVote', query: { pas_agree: this.pas_agree, pwd: '1' } })
      //   return
      // }
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  width: 100%;
  min-height: 100%;
  background: #fff;
}
.phone-check-wrapper {
  padding: 19px 24px 0;
}
</style>
