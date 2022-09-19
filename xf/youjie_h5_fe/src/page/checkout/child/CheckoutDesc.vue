<template>
  <div class="container">
    <label class="title">{{ title }}</label>
    <label class="subtitle">{{ subtitle }}</label>
  </div>
</template>

<script>
export default {
  props: {
    title: {
      type: String
    },
    subtitle: {
      type: String
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  background-color: #fff;
  @include thin-border(#f4f4f4, 15px);
  height: 50px;
  line-height: 50px;
  padding: 0 15px;
  color: #999999;
}
.title {
  width: 100px;
  font-size: 14px;
  font-weight: 300;
}
.subtitle {
  flex: 1;
  font-size: 15px;
  text-align: right;
}
</style>
