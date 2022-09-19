<template>
  <div class="choose-footer-wrapper">
    <div class="choose-all">
      <div class="circular" :class="{ checked: isCheck_all }" @click="ckeckAll"></div>
      <span>全选</span>
    </div>
    <div class="confirm-wrapper">
      <div class="total-wrapper">
        <span class="t-title">合计：</span>
        <div class="total">
          <label>待还本金：-￥{{ waitCapitalAll }}</label>
          <label>回本：+￥{{ returnCappital }}</label>
        </div>
      </div>
      <button :class="{ disabled: isDisabled }" @click="confirm">
        <span class="btn">确认转让</span>
        <span class="fee">手续费：￥{{ serviceChange }}</span>
      </button>
    </div>
    <van-popup
      v-model="show"
      closeable
      position="bottom"
      :close-on-click-overlay="false"
      :style="{ height: 'auto' }"
    >
      <div class="pop-title">请输入交易密码</div>
      <!-- 密码输入框 -->
      <van-password-input :value="value" :focused="showKeyboard" @focus="showKeyboard = true" />
      <div class="forget">
        <span class="reset" @click="forget">忘记密码?</span>
      </div>
      <!-- 数字键盘 -->
      <van-number-keyboard
        :hide-on-click-outside="false"
        :show="showKeyboard"
        @input="onInput"
        @delete="onDelete"
        @blur="showKeyboard = false"
      />
    </van-popup>
  </div>
</template>
<script>
import { mapState, mapMutations, mapGetters } from 'vuex'
import { transferbuy } from '../../api/transfer.js'
export default {
  data() {
    return {
      show: false,
      value: '',
      showKeyboard: true,
      isCheck_all: false
    }
  },
  watch: {
    isCheckedAllByUser(val) {
      if (this.isCheck_all !== val) this.isCheck_all = val
    }
  },
  computed: {
    ...mapState({
      debtInfo: state => state.transfer.debtInfo
    }),
    ...mapGetters({
      checkedList: 'checkedList',
      waitCapitalAll: 'waitCapitalAll',
      isWarning: 'isWarning',
      isCheckedAllByUser: 'isCheckedAllByUser'
    }),
    isDisabled() {
      return !this.checkedList.length || this.isWarning
    },
    hasChecked() {
      let h_c = []
      this.checkedList.length &&
        this.checkedList.forEach(item => {
          h_c.push({ tender_id: item.tender_id, money: item.money })
        })
      return h_c
    },
    returnCappital() {
      return this.utils.formatFloat((this.waitCapitalAll * this.debtInfo.discount) / 10 || 0)
    },
    serviceChange() {
      // return this.utils.formatFloat(this.returnCappital * x)
      return 0 //暂时写死0
    }
  },
  methods: {
    ...mapMutations({
      checkAll: 'checkAll',
      saveResult: 'saveTransferBuyResult'
    }),
    ckeckAll() {
      this.isCheck_all = !this.isCheck_all
      this.checkAll(this.isCheck_all)
    },
    onInput(key) {
      this.value = (this.value + key).slice(0, 6)
      if (this.value.length == 6) {
        this.show = false
        this.submit()
      }
    },
    onDelete() {
      this.value = this.value.slice(0, this.value.length - 1)
    },
    confirm() {
      if (this.waitCapitalAll > this.debtInfo.sum_wait_capital) {
        this.$dialog.alert({
          message: '所选转让金融已超出求购计划'
        })
      } else {
        let is_set_pay_password = JSON.parse(localStorage.getItem('m_assets_garden')).auth.user.is_set_pay_password
        let setPass = localStorage.getItem('is_set_pay_password')
        if (!setPass) {
          if (!is_set_pay_password) {
            this.$dialog.confirm({ message: '您还没有设置交易密码,请前往设置' }).then(() => {
              // this.$router.push({ name: 'settpass' })
              window.location.href = '/#/setPassWord'
            })
            return
          }
        }
        // if (!this.$store.getters.isSetPassword || !setPass) {
        //   this.$dialog.confirm({ message: '您还没有设置交易密码,请前往设置' }).then(() => {
        //     // this.$router.push({ name: 'settpass' })
        //     window.location.href = '/#/setPassWord'
        //   })
        //   return
        // }

        this.value = ''
        this.showKeyboard = true
        this.show = true
      }
    },
    submit() {
      let data = {}
      data.pur_id = this.$route.params.id
      data.tenderArr = JSON.stringify(this.hasChecked)
      data.payPassword = this.value
      transferbuy(data).then(res => {
        if (res.code == 0) {
          this.saveResult(res)
          this.$router.push({ name: 'transferSuccess' })
        } else {
          this.$toast(res.info)
        }
      })
    },
    forget() {
      window.location.href = '/#/findPassWord'
    }
  }
}
</script>
<style lang="less" scoped>
.choose-footer-wrapper {
  width: 100%;
  height: 50px;
  background-color: #fff;

  display: flex;
  align-items: center;
  justify-content: space-between;
  .choose-all {
    display: flex;
    align-items: center;
    margin-left: 10px;
    .circular {
      width: 30px;
      height: 30px;
      margin-right: 5px;
      border-radius: 50%;
      border: 1px solid rgba(159, 159, 159, 0.7);
      &.checked {
        width: 32px;
        height: 32px;
        background: url('../../assets/image/choose/checked.png') no-repeat;
        background-size: 32px 32px;
        border: 0;
      }
    }
    span {
      font-size: 12px;
      line-height: 17px;
    }
  }
  .confirm-wrapper {
    height: 100%;
    display: flex;
    align-items: center;
    .total-wrapper {
      display: flex;
      align-items: center;
      .t-title {
        font-size: 12px;
        color: #999;
        line-height: 17px;
      }
      .total {
        margin-right: 14px;
        display: flex;
        align-items: flex-end;
        flex-direction: column;
        label {
          font-size: 12px;
          line-height: 17px;
          & + label {
            margin-top: 3px;
          }
        }
      }
    }
    button {
      width: 117px;
      height: 50px;
      background-color: @themeColor;
      color: #fff;

      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      .btn {
        font-size: 16px;
        line-height: 22px;
      }
      .fee {
        color: #fff;
        .sc(11);
        margin-top: 3px;
        line-height: 16px;
      }
      &.disabled {
        pointer-events: none;
      }
    }
  }
  .pop-title {
    line-height: 50px;
    text-align: center;
  }
  .van-password-input__security:after {
    content: '';
  }
  .forget {
    padding: 10px 10px 20px;
    text-align: center;
    color: #fc810c;
  }
}
</style>
