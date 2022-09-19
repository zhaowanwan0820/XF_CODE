<template>
  <div class="success-item">
    <div class="head" @click="itemClick">
      <span>{{ title }}</span>
      <div :class="{ active }">
        <label>{{ result.length }}</label>
        <img src="../../../assets/image/choose/icon-tip.png" alt="" />
      </div>
    </div>
    <div class="detail-list" v-if="result.length && active">
      <div class="list-item" v-if="result.length" v-for="item in result" :key="item.tender_id">
        <span>{{ item.projectName }}</span>
        <span>{{ detail(item) }}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      active: false
    }
  },
  props: {
    title: {
      type: String
    },
    result: {
      type: Array,
      default: []
    },
    type: {
      type: String,
      default: 'success'
    }
  },
  methods: {
    detail(item) {
      let detail = ''
      switch (this.type) {
        case 'success':
          detail = this.utils.formatFloat(item.return_capital)
          break
        case 'fail':
          detail = item.reason
          break
      }
      return detail
    },
    itemClick() {
      this.active = !this.active
    }
  }
}
</script>
<style lang="less" scoped>
.success-item {
  margin-top: 10px;
}
.head {
  height: 50px;
  padding: 0 14px 0 20px;
  background-color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  span {
    font-size: 15px;
    color: #404040;
    line-height: 21px;
  }
  div {
    font-size: 0;
    label {
      font-size: 15px;
      color: #404040;
      line-height: 21px;
    }
    img {
      margin-top: 5px;
      margin-left: 6px;
      width: 10px;
      height: 10px;
      transform: rotateZ(-90deg);
      transition: 0.3s;
    }
    &.active {
      img {
        transform: rotateZ(0);
      }
      // transition: 0.3s;
    }
  }
}
.detail-list {
  padding: 20px 14px 20px 20px;
  background-color: #f9f9f9;
  .list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
    &:last-child {
      margin-bottom: 0;
    }
    span {
      font-size: 14px;
      color: #666;
      line-height: 20px;
    }
  }
}
</style>
