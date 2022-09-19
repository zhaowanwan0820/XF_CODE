<template>
  <div class="debt-item-wrapper" v-if="item.tender_id">
    <div class="circular" :class="{ check: item.checked, opacity: isDebt }" @click="checkItem"></div>
    <div class="debt-content">
      <div class="item-title">
        <div>{{ item.projectName }}</div>
        <div>合同号：{{ item.bond_no }}</div>
      </div>
      <div class="balance">
        <span> 待还本金：{{ this.utils.formatMoney(item.wait_capital) }} </span>
        <label v-if="isDebt">有债转</label>
      </div>
      <div class="change-account">
        <span>转让金额：</span>
        <input
          type="text"
          :disabled="isDebt || isDisabled"
          v-model="item.money"
          @blur="checkMoney(item.money)"
          @input="handleInput"
        />
      </div>
      <p class="warning" v-if="warningTxt">{{ warningTxt }}</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      warningTxt: ''
    }
  },
  props: {
    item: {
      type: Object
    }
  },
  watch: {
    warningTxt(val) {
      this.item.warning = !!val
    }
  },
  computed: {
    isDisabled() {
      let status = false
      if (this.item.wait_capital < 200) status = true
      return status
    },
    isDebt() {
      return Number(this.item.debt_status)
    }
  },
  methods: {
    handleInput(e) {
      this.item.money = e.target.value.replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3')
    },
    checkMoney(val) {
      let bal = this.item.wait_capital - val
      if (val < 100) {
        this.warningTxt = '请填写大于100的金额'
      } else if (bal > 0 && bal < 100) {
        this.warningTxt = '剩余待还本金不足￥100，请全部转让吧'
      } else if (val - this.item.wait_capital > 0) {
        this.warningTxt = '您可转让的金额为￥' + 100 + ' -￥' + Number(this.item.wait_capital)
      } else {
        this.warningTxt = ''
      }
    },
    checkItem() {
      this.item.checked = !this.item.checked
    }
  }
}
</script>

<style lang="less" scoped>
.debt-item-wrapper {
  display: flex;
  align-items: center;
  margin-top: 15px;
  padding: 0 15px 0 10px;

  .circular {
    width: 30px;
    height: 30px;
    flex: 30px 0 0;
    margin-right: 10px;
    border-radius: 50%;
    border: 1px solid #9f9f9f;
    &.check {
      width: 32px;
      height: 32px;
      background: url('../../../assets/image/choose/checked.png') no-repeat;
      background-size: 32px 32px;
      border: 0;
    }
    &.opacity {
      opacity: 0;
      pointer-events: none;
    }
  }
  .debt-content {
    flex: 1;
    background-color: #fff;
    border-radius: 6px;

    box-sizing: border-box;
    padding: 13px 15px 12px 20px;
    .item-title {
      font-size: 15px;
      line-height: 30px;
      color: #666;
    }
    .balance {
      margin-top: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      span {
        font-size: 15px;
        color: #666;
        line-height: 21px;
      }
      label {
        font-size: 12px;
        color: @themeColor;
        line-height: 1;
      }
    }
    .change-account {
      margin-top: 10px;
      display: flex;
      align-items: center;
      span {
        font-size: 15px;
        color: #666;
        line-height: 21px;
        white-space: nowrap;
      }
      input {
        flex: 1;
        height: 39px;
        border: 1px solid #e7e7e7;

        font-size: 15px;
        color: #999;
        line-height: 21px;
        text-indent: 11px;
        &::-webkit-input-placeholder {
          font-size: 15px;
          color: #999;
          line-height: 21px;
        }
      }
    }
    .warning {
      margin-top: 5px;
      margin-left: 75px;
      font-size: 12px;
      font-weight: 400;
      color: @markColor;
      line-height: 17px;
    }
  }
}
</style>
