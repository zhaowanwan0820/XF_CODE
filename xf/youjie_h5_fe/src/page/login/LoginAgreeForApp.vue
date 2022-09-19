<template>
  <div>
    <div class="header-container">
      <mt-header class="header" fixed>
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      </mt-header>
    </div>
    <!-- 登录/注册 协议 -->
    <login-agreement v-if="true"></login-agreement>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import LoginAgreement from './LoginAgreement'
import { Header } from 'mint-ui'
export default {
  components: {
    LoginAgreement
  },
  methods: {
    goBack() {
      if (this.isHHApp) {
        this.hhApp.close()
      } else {
        this.$_goBack()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.header {
  background: rgba(255, 255, 255, 0);
}
</style>
