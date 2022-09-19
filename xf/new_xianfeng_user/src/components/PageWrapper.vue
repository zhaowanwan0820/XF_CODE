<template>
  <div class="page-wrapper">
    <section class="body">
      <slot />
    </section>
  </div>
</template>

<script>
export default {
  name: "PageWrapper",
}
</script>

<style lang="less" scoped>
.page-wrapper {
  width: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: column;
  .body {
    margin-top: 40px;
  }
}
</style>
