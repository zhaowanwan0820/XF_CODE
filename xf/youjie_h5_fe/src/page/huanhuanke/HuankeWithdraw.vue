<template>
  <div class="container">
    <template v-if="currentIndex == 1">
      <mt-header class="header" title="提现到支付宝">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      </mt-header>
      <div class="content">
        <div class="form">
          <div class="withdraw-account">
            <div class="w-a-icon">
              <img src="../../assets/image/hh-icon/ali-icon.png" alt="" />
            </div>
            <div class="w-a-content">
              <div class="w-a-input name">
                <label for="aliName">姓名</label>
                <input type="text" v-model="aliName" id="aliName" placeholder="支付宝实名" />
              </div>
              <div class="w-a-input account">
                <label for="aliAccount">支付宝</label>
                <input type="text" v-model="aliAccount" id="aliAccount" placeholder="支付宝账号" />
              </div>
            </div>
          </div>
          <div class="withdraw-money">
            <div class="w-m-title">
              <span class="title">提现金额</span>
              <span class="w-m-all" @click="withdrawAll"><span>全部</span></span>
            </div>
            <div class="w-m-input">
              <div>
                <span>￥</span>
                <input
                  type="text"
                  @input="inputMoney"
                  v-model="withdrawMoney"
                  :placeholder="`本次最高可提现${utils.formatMoney(accountInfo.user_money)}元`"
                />
              </div>
            </div>
            <template v-if="isLegalMoney">
              <p class="tips">{{ inputTips }}</p>
            </template>
            <template v-else>
              <p class="tips error">{{ errorMsg }}</p>
            </template>
          </div>
        </div>
        <div class="botton-wrapper">
          <gk-button
            class="button"
            type="primary-secondary"
            v-if="aliName && aliAccount && withdrawMoney && isLegalMoney"
            v-on:click="submitWithdraw"
            >立即提现</gk-button
          >
          <gk-button class="button  disable" type="primary-secondary" v-else>立即提现</gk-button>
        </div>
        <p class="warn"><span @click="changeCurrent(2)">注意事项</span></p>
      </div>
    </template>
    <template v-if="currentIndex == 2">
      <tips v-on:click="changeCurrent(1)"></tips>
    </template>
  </div>
</template>

<script>
import { Toast } from 'mint-ui'
import { mapMutations } from 'vuex'
import { getWithdrawAccount, withdrawMoneySubmit } from '../../api/mlm'
import Tips from './child/Tips'
import { WITHDRAW_STATUS } from './static'
export default {
  data() {
    return {
      currentIndex: 1, // 展示内容; 1-提现页面 2-注意事项

      aliName: '',
      aliAccount: '',
      withdrawMoney: '',

      isLegalMoney: false, // 输入的金额是否错误
      errorMsg: '',

      WITHDRAW_STATUS,

      accountInfo: {
        user_money: 0, //可用余额
        rate: 0.006, //费率
        payee_real_name: '', //收款人真实姓名
        payee_account: '' //收款账户
      },

      isWithdrawing: false // 是否在提交提现申请
    }
  },

  components: {
    Tips
  },

  created() {
    this.getInfo()
  },

  computed: {
    inputTips() {
      return `扣除￥${this.getServiceCharge}服务费（费率${this.accountInfo.rate * 100}%），试运行期间暂不收取`
    },
    /**
     * 计算手续费
     */
    getServiceCharge() {
      let money = 0
      try {
        money = parseFloat(this.withdrawMoney)
      } catch (e) {
        money = 0
      }
      const serviceCharge = (money * this.accountInfo.rate).toFixed(2)
      return serviceCharge > 0.1 ? serviceCharge : '0.10'
    }
  },

  methods: {
    ...mapMutations({
      saveWithdrawInfo: 'saveWithdrawInfo',
      saveWithdrawStatus: 'saveWithdrawStatus'
    }),
    getInfo() {
      getWithdrawAccount().then(res => {
        // if (res.accountInfo.user_money < 0.1) {
        //   this.$_goBack()
        // }
        this.accountInfo = res.accountInfo
        this.aliName = res.accountInfo.payee_real_name
        this.aliAccount = res.accountInfo.payee_account
      })
    },
    goBack() {
      this.$_goBack()
    },
    changeCurrent(index) {
      this.currentIndex = index || 1
    },
    withdrawAll() {
      this.withdrawMoney = parseFloat(this.accountInfo.user_money)
    },

    inputMoney(e) {
      this.withdrawMoney = e.target.value
        .replace(/[^\d\.]/g, '')
        .replace(/^\./, '0.')
        .replace(/(\.\d{0,2}).*/g, '$1')
    },

    /**
     * 提现
     */
    submitWithdraw() {
      if (this.isWithdrawing) {
        return
      }
      if (!this.aliName) {
        Toast('请输入姓名')
      }
      if (!this.aliAccount) {
        Toast('请输入支付宝账号')
      }
      if (!this.withdrawMoney) {
        Toast('请输入提现金额')
      }

      const params = {
        money: this.withdrawMoney,
        payee_real_name: this.aliName,
        payee_account: this.aliAccount
      }
      this.isWithdrawing = true

      withdrawMoneySubmit(params).then(
        res => {
          this.saveWithdrawInfo(res)
          this.saveWithdrawStatus({
            status: this.WITHDRAW_STATUS.SUCCEED
          })
          this.$router.replace({ name: 'withdrawResult' })
          this.isWithdrawing = false
        },
        error => {
          this.saveWithdrawInfo(error.data)
          this.saveWithdrawStatus({
            status: this.WITHDRAW_STATUS.FAILED,
            errorMsg: error.errorMsg
          })
          this.$router.replace({ name: 'withdrawResult' })
          this.isWithdrawing = false
        }
      )
    }
  },

  watch: {
    withdrawMoney(newValue, oldValue) {
      let money = 0
      try {
        money = parseFloat(newValue || 0)
      } catch (e) {
        money = 0
      }
      if (money == 0) {
        this.isLegalMoney = false
        this.errorMsg = ''
      } else if (money > this.accountInfo.user_money) {
        this.isLegalMoney = false
        this.errorMsg = '输入金额超过可提现金额'
      } else {
        this.isLegalMoney = true
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  position: relative;
  flex-direction: column;
  justify-content: flex-start;
  background: #f4f4f4;
}
.header {
  @include header;
  @include thin-border();
  flex-basis: 44px;
}
.content {
  display: flex;
  flex-direction: column;
  flex: 1;
  position: relative;
  .form {
    background-color: #ffffff;
  }
  .withdraw-account {
    padding: 15px 15px 0;
    background-color: #2c2e46;
  }
  .w-a-icon {
    font-size: 0;
    img {
      width: 86px;
    }
  }
  .w-a-content {
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    background-color: #ffffff;
    margin-top: 15px;
    box-shadow: 0 2px 4px 0 rgba(191, 191, 191, 0.1);
  }
  .w-a-input {
    display: block;
    height: 54px;
    display: flex;
    align-items: center;
    padding: 0 15px;
    &.name {
      @include thin-border(rgba(85, 46, 32, 0.2), 0, auto, true);
    }
    label {
      width: 58px;
      @include sc(14px, #707070);
      font-family: PingFangSC-Regular;
    }
    input {
      flex: 1;
      border: none;
      @include sc(14px, #404040);
      font-family: PingFangSC-Medium;
      &::-webkit-input-placeholder {
        color: #cccccc;
      }
    }
  }
  .withdraw-money {
    padding: 20px 15px 0;
    .w-m-title {
      display: flex;
      justify-content: space-between;
      .title {
        @include sc(12px, #404040);
      }
      .w-m-all {
        width: 40px;
        height: 18px;
        border: 1px solid #552e20;
        border-radius: 2px;
        display: flex;
        align-items: center;
        justify-content: center;
        span {
          @include sc(11px, #552e20);
        }
      }
    }
    .w-m-input {
      display: flex;
      align-items: center;
      height: 50px;
      margin-top: 15px;
      @include thin-border(#f4f4f4);
      div {
        flex: 1;
        display: flex;
        align-items: baseline;
      }
      span {
        line-height: 1;
        @include sc(23px, #404040);
        font-weight: 600;
        display: inline-block;
        width: 26px;
      }
      input {
        border: none;
        @include sc(24px, #404040);
        flex: 1;
        font-weight: 500;
        &::-webkit-input-placeholder {
          @include sc(18px, #cccccc);
        }
      }
    }
    .tips {
      height: 28px;
      line-height: 28px;
      @include sc(11px, #999999, left center);
      &.error {
        color: #9b210b;
      }
    }
  }
  .botton-wrapper {
    text-align: center;
    margin-top: 50px;
    button {
      @include button($margin: 0, $radius: 2px, $spacing: 1px);
      width: 327px;
      font-size: 18px;
      color: #fff;
    }
  }
  .warn {
    margin-top: 140px;
    text-align: center;
    @include sc(14px, $deleteColor);
  }
}
</style>
