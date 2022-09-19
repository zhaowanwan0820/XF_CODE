<template>
  <div class="header-switch-container">
    <div class="switch-tabs-wrapper">
      <van-icon name="arrow-left" @click="goBack" />
      <div class="switch-tabs">
        <span class="my-debt" :class="{ active: isMyDebt }" @click="goZX">尊享</span>
        <span class="my-subscription" :class="{ active: !isMyDebt }" @click="goPH">普惠</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: '',
  data() {
    return {
      currentProject: 1
    }
  },
  computed: {
    isMyDebt() {
      return this.currentProject == 1
    }
  },
  created() {},
  methods: {
    goBack() {
      this.$_goBack()
    },
    goPH() {
      this.currentProject = 2
      this.$emit('change-project', this.currentProject)
    },
    goZX() {
      this.currentProject = 1
      this.$emit('change-project', this.currentProject)
    }
  }
}
</script>

<style lang="less" scoped>
.header-switch-container {
  position: fixed;
  z-index: 9;
  width: 100%;
  .switch-tabs-wrapper {
    position: relative;
    border-bottom: 0.5px solid #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #fff;
    text-align: center;
    height: 46px;
    /deep/ .van-icon {
      font-size: 20px;
      position: absolute;
      left: 16px;
    }
    .switch-tabs {
      padding: 0 2px;
      width: 166px;
      height: 30px;
      border-radius: 15px;
      background: #f4f4f4;
      line-height: 30px;
      font-size: 12px;
      font-weight: 500;
      color: #999;
      span {
        display: inline-block;
        width: 50%;
        text-align: center;
      }
      .active {
        background: #fff;
        color: #404040;
        border-radius: 15px;
        height: 28px;
        line-height: 28px;
      }
    }
  }
}
</style>
