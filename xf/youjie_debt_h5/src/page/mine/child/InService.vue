<template>
  <div class="container">
    <div class="content">
      <img src="../../../assets/image/confirmation/icon-success.png" alt="" />
      <p>客服已介入</p>
      <p class="desc">客服稍后会与您联系，请注意接听010-89929006号码来电<br />客服不会通过其他号码与您联系，请注意辨别</p>
      <button class="deep" @click="goBack">返回</button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmResult',
  methods: {
    goBack() {
      this.$router.push({name:'mytransfer'})
    }
  }
}
</script>
<style lang="less" scoped>
.container {
  height: 100%;
  background-color: #fff;
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  img {
    width: 62px;
    height: 62px;
    margin-top: 71px;
    margin-bottom: 25px;
  }
  p {
    font-size: 16px;
    font-weight: 400;
    color: #333;
    line-height: 24px;
    &.desc {
      padding: 20px 30px 40px;
      color: #666;
    }
  }
  label {
    margin-top: 4px;
    margin-bottom: 60px;
    font-size: 14px;
    font-weight: 400;
    color: #333;
    line-height: 24px;
    color: @themeColor;
  }
  button {
    width: 327px;
    height: 46px;
    border-radius: 2px;
    background-color: #fff;
    border: 1px solid @themeColor;
    margin-bottom: 20px;

    font-size: 18px;
    font-weight: 400;
    line-height: 1;
    color: @themeColor;
    &.deep {
      color: #fff;
      background-color: @themeColor;
    }
  }
}
</style>
