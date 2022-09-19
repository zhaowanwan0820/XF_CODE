<template>
  <div class="start-a-new-debt-wrapper" @click="toStartNewDebt" v-move>
    <img src="../../../assets/image/home/icon-start-a-new-debt@2x.png" />
    <span class="txt">发布债转</span>
  </div>
</template>

<script>
export default {
  name: 'PlanItem',
  data() {
    return {}
  },
  methods: {
    toStartNewDebt() {
      this.$router.push({ name: 'projectList' })
    }
  }
}
</script>

<style lang="less" scoped>
.start-a-new-debt-wrapper {
  position: fixed;
  z-index: 10;
  width: 83px;
  height: 83px;
  overflow: hidden;
  right: 12px;
  bottom: 9px;

  img {
    width: 100%;
  }
  .txt {
    position: absolute;
    width: 100%;
    text-align: center;
    top: 43px;
    left: 0;
    font-size: 12px;
    font-weight: 400;
    color: rgba(255, 255, 255, 1);
    line-height: 17px;
  }
}
</style>
