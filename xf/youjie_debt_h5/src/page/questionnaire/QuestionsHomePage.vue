<template>
  <div class="q-home-page">
    <p>
      尊敬的投资者：<br />
      <br />为了便于您了解自身的风险承受能力，请您填写以下风险承受能力评估问卷。下列问题可协助评估本公司向您提供的服务将以您的风险承受能力等级为基础，请您根据自身情况认真选择。评估结果仅供参考，不构成投资建议。为了及时了解您的风险承受能力，我们建议您持续做好动态评估。我们承诺对您的所有个人资料保密，未经授权，不向任何第三方透露。
    </p>
    <div class="btn-wrapper">
      <button @click="start">开始</button>
    </div>
  </div>
</template>

<script>
export default {
  methods: {
    start() {
      this.$emit('start')
    }
  }
}
</script>

<style lang="less" scoped>
.q-home-page {
  p {
    font-size: 16px;
    padding: 18px 18px 0 24px;
    line-height: 24px;
  }
  .btn-wrapper {
    margin-top: 72px;
    text-align: center;
  }
  button {
    width: 280px;
    height: 45px;
    background: @themeColor;
    border-radius: 6px;

    color: #fff;
    font-size: 16px;
    line-height: 22px;
  }
}
</style>
