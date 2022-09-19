<template>
  <section class="error">
    <footer>← 回到<router-link to="/">首页</router-link></footer>
  </section>
</template>

<script>
export default {
  name: 'error',
  title: 'Not Found',
}
</script>

<style lang="scss">
.error {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  letter-spacing: 2px;

  footer {
    margin-top: 80px;
    font-size: 12px;
  }
}
</style>
