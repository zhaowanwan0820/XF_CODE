<template>
  <div class="balance-container">
    <div class="balance-header">
      <div class="balance-header-wrapper">
        <label class="title">积分</label>
        <img src="../../../assets/image/hh-icon/icon-积分.svg" />
      </div>
      <label class="balance" v-if="this.balance">{{ this.balance }}</label>
      <label class="balance" v-else>无可用积分</label>
    </div>
  </div>
</template>

<script>
import { mapMutations } from 'vuex'
import { balanceGet } from '../../../api/balance'

export default {
  data() {
    return {
      balance: null
    }
  },
  methods: {
    ...mapMutations({
      saveBalanceInfo: 'saveBalanceInfo'
    })
  },
  created() {
    balanceGet().then(res => {
      this.balance = parseFloat(res.surplus)
      this.saveBalanceInfo(this.balance)
    })
  }
}
</script>

<style lang="scss" scoped>
.balance-container {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  background-color: #fff;
  height: 50px;
  .bottom-wrapper {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: stretch;
    margin: 15px;
    input {
      flex: 1;
      font-size: 14px;
      color: #4e545d;
      background-color: #f7f8fa;
      padding: 10px;
      border: none;
      border-radius: 2px;
      &:focus {
        outline-style: none;
      }
    }
  }
}
.balance-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 28px 0 20px;
  .balance-header-wrapper {
    .title {
      font-size: 15px;
      color: $baseColor;
      line-height: 50px;
    }
    img {
      widows: 12px;
      height: 12px;
    }
  }
  .balance {
    font-size: 15px;
    color: $markColor;
  }
}
</style>
