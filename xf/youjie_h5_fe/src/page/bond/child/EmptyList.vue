<template>
  <div class="no-data">
    <img class="img-none" src="../../../assets/image/hh-icon/empty-list@2x.png" />
    <p class="desc">{{ emptyDesc }}</p>
    <gk-button type="primary" class="button" v-if="isHasDebt" @click="toConfirmDebt">开始资产确权</gk-button>
  </div>
</template>

<script>
export default {
  data: function() {
    return {}
  },
  props: {
    isHasDebt: Boolean,
    type: {
      type: String,
      default: '1'
    }
  },
  computed: {
    emptyDesc() {
      return this.isHasDebt ? '暂无已确认的债权，快去做资产确权吧' : '暂无数据'
    }
  },
  methods: {
    // 去确权
    toConfirmDebt() {
      this.$router.push({ name: 'confirmationList', params: { type: this.type } })
    }
  }
}
</script>

<style lang="scss" scoped>
.no-data {
  box-sizing: border-box;
  height: 100%;
  background-color: #fff;
  padding-top: 60px;
  text-align: center;

  .img-none {
    width: 135px;
    height: 135px;
  }
  .desc {
    margin-top: 14px;
    margin-bottom: 40px;
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    color: rgba(102, 102, 102, 1);
    line-height: 20px;
  }
}
.button {
  height: 36px;
  width: 140px;
  font-size: 14px;
}
</style>
