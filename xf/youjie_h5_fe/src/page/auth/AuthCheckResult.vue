<template>
  <div class="auth-check-result">
    <div class="header-container">
      <mt-header class="header" title="身份验证">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      </mt-header>
    </div>
    <div class="result-container">
      <div class="result-wrapper">
        <img src="../../assets/image/hh-icon/auth/icon-suc.png" alt />
        <p class="result-title">身份验证成功</p>
        <p class="result-con">已获取到您的债权信息，<br />{{ second }}秒后开始确权</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AuthCheckResult',
  data() {
    return {
      second: 3,
      timer: null
    }
  },

  mounted() {
    this.timer = setInterval(_ => {
      this.second -= 1
      if (this.second == 0) {
        clearInterval(this.timer)
        return this.$router.push({ name: 'confirmation' })
      }
    }, 1000)
  },

  methods: {
    goBack() {
      this.$router.push({ name: 'home' })
    }
  },

  beforeDestroy() {
    clearInterval(this.timer)
  }
}
</script>

<style lang="scss" scoped>
.auth-check-result {
  width: 100%;
  min-height: 100%;
  background: #fff;
  display: flex;
  flex-direction: column;
}
.header {
  @include thin-border();
}
.result-container {
  background: #f4f4f4;
  flex: 1;
  display: flex;
  flex-direction: column;
  & > div {
    background: #ffffff;
  }
  .result-wrapper {
    text-align: center;
    flex: 1;
    padding: 60px 0 25px;
    img {
      width: 62px;
      height: 62px;
    }
    p.result-title {
      @include sc(16px, #333333);
      font-weight: 500;
      margin-top: 25px;
      font-family: PingFangSC-Medium, PingFang SC;
    }
    p.result-con {
      @include sc(14px, #666);
      margin-top: 25px;
      line-height: 24px;
    }
  }
  .auth-content {
    margin-top: 10px;
    padding: 25px 0;
    text-align: center;
    flex: 1;
    .title {
      margin: 0 auto;
      width: 249px;
      margin-bottom: 10px;
      @include sc(16px, #333333);
      line-height: 25px;
      background-image: url('../../assets/image/hh-icon/auth/bg.png');
      background-repeat: no-repeat;
      background-size: 249px auto;
      background-position: center;
    }
    p {
      @include sc(14px, #666666);
      margin-top: 15px;
      line-height: 1.5;
      text-align: left;
      padding: 0 25px;
    }
    button {
      width: 327px;
      height: 46px;
      border: 0;
      color: #fff;
      background-color: $primaryColor;
      border-radius: 2px;
      font-size: 16px;
      margin-top: 27px;
      outline: none;
    }
  }
}
</style>
