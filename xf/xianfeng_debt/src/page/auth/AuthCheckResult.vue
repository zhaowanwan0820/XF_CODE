<template>
  <div class="auth-check-result">
    <div class="result-container">
      <div class="result-wrapper">
        <img src="../../assets/image/login/icon-suc.png" alt />
        <p class="result-title">身份验证成功</p>
        <p class="result-con">已获取到您的债权信息，<br />{{ second }}秒后开始确权</p>
      </div>
    </div>
    <!-- <div class="auth-content">
      <div class="title">确权说明</div>
      <p>这是文本内容这是文本内容这是文本内容这是文本内容这是文本内容这是文本内容这是文本内容。</p>
      <p>这是文本内容这是文本内容这是文本内容这是文本内容这是文本内容这是文本内容这是文本内容。</p>
      <p>这是文本内容这是文本内容这是文本内容这是文本内容这是文本内容这是文本内容这是文本内容。</p>
    </div> -->
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
    // if ('development' === process.env.NODE_ENV) return
    this.timer = setInterval(_ => {
      this.second -= 1
      if (this.second <= 0) {
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

<style lang="less" scoped>
.auth-check-result {
  width: 100%;
  background: #f4f4f4;
  display: flex;
  flex-direction: column;
  background-color: #fff;
  .result-container {
    background-color: #fff;
    display: flex;
    flex-direction: column;
    & > div {
      background: #ffffff;
    }
    .result-wrapper {
      text-align: center;
      flex: 1;
      padding: 60px 0 27px;
      img {
        width: 62px;
        height: 62px;
      }
      p.result-title {
        font-size: 16px;
        color: #333;
        font-weight: 500;
        margin-top: 24px;
      }
      p.result-con {
        font-size: 14px;
        color: #666;
        margin-top: 13px;
        line-height: 24px;
      }
    }
  }
  .auth-content {
    margin-top: 10px;
    padding: 25px 0 10px;
    text-align: center;
    flex: 1;
    background-color: #fff;
    .title {
      margin: 0 auto;
      width: 329px;
      margin-bottom: 9px;
      font-size: 16px;
      color: #333;
      line-height: 24px;
      background: url('../../assets/image/login/bg.png') no-repeat center;
      background-size: 249px auto;
    }
    p {
      font-size: 14px;
      color: #666;
      margin-top: 13px;
      line-height: 22px;
      text-align: left;
      padding: 0 24px;
    }
  }
}
</style>
