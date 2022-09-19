<template>
  <div class="error-page-container">
    <div class="txt">404 页面不存在！！！</div>
    <div class="btn" @click="$router.push({ name: 'mall' })">进入商城</div>
  </div>
</template>

<script>
export default {
  name: 'Error',
  data() {
    return {}
  }
}
</script>

<style lang="scss" scoped>
.error-page-container {
  height: 100%;
  background-color: #fff;
  text-align: center;

  .txt {
    color: #000;
    font-size: 20px;
    padding: 50px 0 30px;
  }
  .btn {
    font-size: 18px;
  }
}
</style>
