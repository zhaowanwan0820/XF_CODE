<template>
  <div class="container">
    <div class="content">
      <img src="../../assets/image/confirmation/icon-success.png" alt="" />
      <p>确权成功</p>
      <label @click="goConfirmationDetail">查看明细</label>
      <button class="deep" @click="goMine">查看我的资产</button>
      <button @click="goConfirmation">继续确权</button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmResult',
  methods: {
    goMine() {
      this.$router.push({ name: 'mine' })
    },
    goConfirmation() {
      this.$router.push({ name: 'confirmation' })
    },
    goConfirmationDetail() {
      this.$router.push({ name: 'confirmationList', params: { type: this.$route.params.type, status: true } })
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
