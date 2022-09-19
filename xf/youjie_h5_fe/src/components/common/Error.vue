<template>
  <div class="container">
    <div class="content">
      <p class="title">页面不存在</p>
      <router-link :to="this.$route.query.redirect || '/'">
        <gk-button class="button" type="primary">返回</gk-button>
      </router-link>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.container {
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  background-color: #fff;
}
.content {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  margin-top: 150px;
}
.title {
  color: $baseColor;
  font-size: 20px;
}
.button {
  @include button($margin: 60px 0 0, $spacing: 12px);
}
</style>
