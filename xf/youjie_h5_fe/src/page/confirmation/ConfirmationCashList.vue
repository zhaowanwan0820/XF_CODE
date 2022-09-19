<template>
  <div class="container">
    <mt-header class="header" :title="title">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <p class="title">账户权益确认</p>
      <!-- 账户余额 -->
      <div class="cash-item">
        <div class="left">
          <div class="head">
            <span>{{ info.money }}</span>
            <label>现金</label>
          </div>
          <div class="title">现金账户余额(元)</div>
        </div>
        <div class="right" v-if="info.money > 0">
          <button v-if="!isMoneyConfirm" @click="confirmCash(2)"><span>确认</span></button>
          <label v-else>已确认</label>
        </div>
      </div>
      <!-- 冻结金额 -->
      <div class="cash-item">
        <div class="left">
          <div class="head">
            <span>{{ info.lockMoney }}</span>
            <label class="freez">冻结</label>
          </div>
          <div class="title">现金账户余额(元)</div>
        </div>
        <div class="right" v-if="info.lockMoney > 0">
          <button v-if="!isLockConfirm" @click="confirmCash(1)"><span>确认</span></button>
          <label v-else>已确认</label>
        </div>
      </div>
    </div>
    <confirm-auth-popup @confirm="confirm"></confirm-auth-popup>
  </div>
</template>
<script>
import { getMoneyList, confirmMoney } from '../../api/confirmation.js'
import ConfirmAuthPopup from './child/ConfirmAuthPopup'
import { mapMutations } from 'vuex'

export default {
  name: 'ConfirmationCashList',
  data() {
    return {
      title: this.$route.params.type == 1 ? '尊享' : '网信普惠',
      status: 0,
      info: {}
    }
  },
  created() {
    this.getCashInfo()
  },
  components: {
    ConfirmAuthPopup
  },
  computed: {
    isMoneyConfirm() {
      let status = false
      if ([2, 3].indexOf(this.info.hasDebtConfirm) !== -1) status = true
      return status
    },
    isLockConfirm() {
      let status = false
      if ([1, 3].indexOf(this.info.hasDebtConfirm) !== -1) status = true
      return status
    }
  },
  methods: {
    ...mapMutations({
      setShowConfirmPopup: 'setShowConfirmPopup',
      hasCheckProjectTotal: 'hasCheckProjectTotal'
    }),
    getCashInfo() {
      let data = {
        type: this.$route.params.type
      }
      this.$indicator.open()
      getMoneyList(data)
        .then(res => {
          this.info = res
        })
        .finally(() => {
          this.$indicator.close()
        })
    },
    confirmCash(status) {
      this.status = status
      this.confirm()
      // 取消授权弹窗
      // this.setShowConfirmPopup(true)
    },
    confirm() {
      // status: 1冻结 2账户余额
      let data = {
        type: this.$route.params.type,
        debtConfirm: Number(this.status) + Number(this.info.hasDebtConfirm)
      }
      this.$indicator.open()
      confirmMoney(data)
        .then(res => {
          console.log(res)
          this.hasCheckProjectTotal(1)
        })
        .finally(() => {
          this.$indicator.close()
          this.getCashInfo()
        })
    },
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.content {
  padding: 0 15px;
}
p.title {
  font-size: 13px;
  font-weight: 500;
  color: #666;
  line-height: 18px;
  padding: 15px 0 10px 0;
}
.cash-item {
  width: 345px;
  height: 87px;
  margin-bottom: 15px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #fff;
  .left {
    .head {
      font-size: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      span {
        font-size: 20px;
        font-weight: 500;
        line-height: 28px;
      }
      label {
        font-size: 12px;
        font-weight: 400;
        color: #fff;
        line-height: 16px;

        margin-left: 5px;
        padding: 0 3px;
        background: rgba(253, 180, 114, 1);
        border-radius: 1px;
        &.freez {
          background-color: #988dff;
        }
      }
    }
    .title {
      margin-top: 4px;
      @include sc(11px, #999, left center);
      font-weight: 400;
      line-height: 16px;
    }
  }
  .right {
    position: absolute;
    right: 10px;
    bottom: 10px;
    button {
      width: 58px;
      height: 24px;
      background: $primaryColor;
      border-radius: 2px;
      span {
        font-size: 13px;
        color: #fff;
        line-height: 1;
      }
    }
    label {
      display: block;
      padding: 0 8px 3px 0;
      font-size: 13px;
      font-weight: 400;
      color: #999;
      line-height: 18px;
    }
  }
}
</style>
