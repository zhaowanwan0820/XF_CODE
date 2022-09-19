<!-- 充值 -->
<template>
  <div class="withdrawal-container">
    <div class="select-tip">请选择需要提现的机构</div>
    <!-- 机构列表 -->
    <div class="mechanism-list">
      <div class="mechanism-item">
        <span class="name">爱投资</span>
        <span class="tip">您可以去该机构官方网站进行提现</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'withdrawal',
  data() {
    return {
      value: ''
    }
  },
  computed: {},
  created() {},
  methods: {}
}
</script>

<style lang="less" scoped>
.withdrawal-container {
  background: #fff;
  .select-tip {
    padding-left: 15px;
    height: 47px;
    font-size: 12px;
    font-weight: 400;
    color: #404040;
    line-height: 47px;
    background: #f4f4f4;
  }
  .mechanism-list {
    padding: 0 15px;
    .mechanism-item {
      height: 55px;
      line-height: 55px;
      display: flex;
      justify-content: space-between;
      font-weight: 400;
      border-bottom: 0.5px solid #e9e9e9;
      .name {
        font-size: 16px;
        color: #404040;
      }
      .tip {
        font-size: 12px;
        color: #999;
      }
    }
  }
}
</style>
