<template>
  <div class="container">
    <mt-header class="header" title="修改交易密码">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="change-pwd-wrapper">
      <!-- 输入旧密码 and 输入&再次输入新密码 -->
      <form-for-change-pwd v-on:submit-success="success"></form-for-change-pwd>
    </div>
    <div class="forget-pwd-wrapper"><a @click="toResetPwd">忘记原密码</a></div>
  </div>
</template>

<script>
import FormForChangePwd from './child/ComponentChangePwd'
export default {
  name: 'TransitionPwdChange',
  data() {
    return {}
  },
  components: {
    FormForChangePwd
  },
  methods: {
    success(res) {
      this.$toast({ message: '密码修改成功' })
      setTimeout(() => {
        this.goBack()
      }, 1000)
    },
    goBack() {
      this.$_goBack()
    },
    toResetPwd() {
      this.$router.replace({ name: 'transPwdSet' })
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
.forget-pwd-wrapper {
  margin-top: 30px;
  text-align: center;
  a {
    color: $primaryColor;
    font-size: 16px;
    line-height: 22px;
  }
}
</style>
