<template>
  <div class="container">
    <mt-header class="header" title="确权结果">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <img src="../../assets/image/hh-icon/confirmation/icon-success.png" alt="" />
      <p>确权成功</p>
      <label @click="goConfirmationDetail">查看明细</label>
      <button class="deep" @click="goHomePage">进入有解</button>
      <button @click="goConfirmation">继续确权</button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmResult',
  methods: {
    goHomePage() {
      this.$router.push({ name: 'profile' })
    },
    goConfirmation() {
      this.$router.push({ name: 'confirmation' })
    },
    goConfirmationDetail() {
      this.$router.push({ name: 'confirmationList', params: { type: this.$route.params.type, status: true } })
    },
    goBack() {
      this.$_goBack()
    }
  }
}
</script>
<style lang="scss" scoped>
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
    color: #988dff;
  }
  button {
    width: 327px;
    height: 46px;
    border-radius: 2px;
    background-color: #fff;
    border: 1px solid $primaryColor;
    margin-bottom: 20px;

    font-size: 18px;
    font-weight: 400;
    line-height: 1;
    color: $primaryColor;
    &.deep {
      color: #fff;
      background-color: $primaryColor;
    }
  }
}
</style>
