<template>
  <div class="title-wrapper">
    <div class="title">
      <img :src="icon" alt="" />
      <span>{{ title }}</span>
    </div>
    <div class="subtitle" @click="goPage">
      <span>{{ subtitle }}</span>
      <img v-if="path" src="../../../assets/image/hh-icon/mlm/icon-tip@3x.png" alt="" />
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {}
  },
  props: ['icon', 'title', 'subtitle', 'path'],

  methods: {
    goPage() {
      if (!this.path || !this.path.name) {
        return
      }
      this.$router.push(this.path)
    }
  }
}
</script>

<style lang="scss" scoped>
.title-wrapper {
  height: 50px;
  display: flex;
  padding: 0 15px 0 20px;
  justify-content: space-between;
  align-items: center;
  @include thin-border(rgba(85, 46, 32, 0.2), 0, 0, true);
  div {
    display: flex;
    align-items: center;
    &.title {
      font-size: 0;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: #404040;
      line-height: 18px;
      img {
        width: 16px;
        margin-right: 5px;
      }
      span {
        font-size: 14px;
        line-height: 1;
      }
    }
    &.subtitle {
      span {
        display: inline-block;
        @include sc(11px, #999999, right center);
      }
      img {
        width: 6px;
        margin-left: 1px;
      }
    }
  }
}
</style>
