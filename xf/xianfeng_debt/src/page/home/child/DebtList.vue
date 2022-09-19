<template>
  <div class="plan-wrapper">
    <div class="head">
      <span class="title"></span>
      <div class="more" @click="toDebtList">
        <span>查看更多</span>
        <img src="../../../assets/image/home/arrow-right@2x.png" />
      </div>
    </div>
    <debt-item v-for="(item, index) in dataDebtList" :info="item" :key="index"></debt-item>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import DebtItem from '../../target/child/TargetItem'

export default {
  data() {
    return {}
  },
  inject: ['verifiedModal'],
  computed: {
    ...mapState({
      dataDebtList: state => state.home.dataDebtList
    })
  },
  components: {
    DebtItem
  },
  methods: {
    toDebtList() {
      const isBreak = this.verifiedModal.showVerifiedModal()
      if (isBreak) return;
      this.$router.push({ name: 'targetList' })
    }
  }
}
</script>

<style lang="less" scoped>
.plan-wrapper {
  margin-top: 10px;
  margin-bottom: 10px;

  .item-wrapper:nth-child(2) {
    margin-top: 0;
  }
}
.head {
  background: rgba(255, 255, 255, 1);
  height: 30px;
  padding: 0 12px 1px 11px;
  margin-bottom: -10px;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;

  .title {
    font-size: 16px;
    font-weight: bold;
    color: rgba(102, 102, 102, 1);
    line-height: 22px;
  }
  .more {
    span {
      display: inline-block;
      font-size: 13px;
      font-weight: 400;
      color: rgba(153, 153, 153, 1);
      line-height: 38px;
      margin-right: 4px;
    }
    img {
      width: 5px;
    }
  }
}
</style>
