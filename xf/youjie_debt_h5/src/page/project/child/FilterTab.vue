<template>
  <div class="filter-container">
    <p class="show-tip">以下展示您已确权的项目</p>
    <div class="select-content">
      <van-dropdown-menu>
        <van-dropdown-item v-model="currentType" :options="option" @change="confirmType" />
      </van-dropdown-menu>
      <!-- <img src="../../../assets/image/filter/select-down.png" alt="" /> -->
    </div>

    <!-- 类型选择框 -->
  </div>
</template>

<script>
export default {
  name: 'filterTab',
  props: ['list'],
  data() {
    return {
      currentType: 1,
      option: this.list
    }
  },
  methods: {
    confirmType(val) {
      this.$emit('choose-type', val)
    }
  }
}
</script>

<style lang="less" scoped>
.filter-container {
  height: 90px;
  background-color: #fff;
  padding: 10px 20px 15px;
  box-sizing: border-box;
  .show-tip {
    margin-bottom: 5px;
    height: 20px;
    font-size: 14px;
    font-weight: 400;
    color: #999;
    line-height: 20px;
  }
  .select-content {
    width: 335px;
    height: 40px;
    border: 1px solid #e7e7e7;
    /* display: flex;
    align-items: center;
    justify-content: space-between;
    padding-left: 13px;
    padding-right: 20px;
    box-sizing: border-box; */
    /deep/ .van-dropdown-menu {
      width: 100%;
      height: 100%;
    }
    /deep/ .van-hairline--top-bottom:after,
    /deep/ .van-hairline-unset--top-bottom:after {
      border-width: 0;
    }
    img {
      width: 14px;
      height: 10px;
    }
  }
}
</style>
