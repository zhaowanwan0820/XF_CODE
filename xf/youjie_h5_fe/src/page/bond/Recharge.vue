<template>
  <div class="container">
    <mt-header class="header" title="积分充值">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="form-wrapper">
      <p class="tips">充值积分后，可在商城购买商品时，抵扣部分现金</p>
      <input
        type="text"
        maxLength="16"
        class="input"
        @input="inputCardSec1"
        v-model="cardSec1"
        placeholder="请输入积分充值卡密"
      />
      <input
        type="number"
        class="input"
        pattern="[0-9]*"
        @input="inputCardSec2"
        v-model="cardSec2"
        placeholder="请输入4位校验码"
        v-if="timer >= 3"
      />
      <p class="err-msg">{{ errMsg }}</p>
      <p class="err-msg">
        {{ isShowErr && errMsg && timer && timer < 3 ? '连续错误3次，需同时输入卡密+校验码' : '' }}
      </p>
      <div class="btn-wrapper">
        <gk-button class="button" v-if="canRecharge" type="primary-secondary" v-on:click="recharge">立即充值</gk-button>
        <gk-button class="button disable" v-else type="primary-secondary">立即充值</gk-button>
      </div>
      <router-link tag="p" class="help" :to="{ name: 'rechargeTips' }">积分充值卡密说明</router-link>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { rechargeHB, getRechargeFailedTime } from '../../api/user'
import { HeaderItem, Button } from '../../components/common'
import { Header, MessageBox } from 'mint-ui'
export default {
  data() {
    return {
      isShowErr: false,
      isTap: false,
      timer: 0,
      cardSec1: '',
      cardSec2: '',
      errMsg: '',
      errCount: 0
    }
  },

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline
    }),
    canRecharge() {
      if (this.timer >= 3) {
        return this.cardSec1.length == 16 && this.cardSec2.length == 4 && this.isTap
      } else {
        return this.cardSec1.length == 16 && this.isTap
      }
    }
  },

  created() {
    if (!this.isOnline) {
      this.goBack()
      return
    }
    this.getTimer()
  },

  methods: {
    goBack() {
      this.$_goBack()
    },

    recharge() {
      const params = {
        cardSec1: this.cardSec1,
        cardSec2: this.cardSec2
      }
      this.isTap = false
      rechargeHB(params).then(
        res => {
          let msg = `<p class="recharge-success-alert">您的编号为<span>${res.huanCardId}</span>的卡密已经充值成功！本次充值积分<span>${res.suplus}</span>个</p><p class="recharge-success-alert">当前账户积分余额<span>${res.now_surplus}</span>个</p>`
          MessageBox({
            title: '充值成功',
            message: msg,
            closeOnClickModal: false,
            confirmButtonText: '去商城逛逛'
          }).then(action => {
            this.$router.push({ name: 'home' })
          })
        },
        err => {
          console.log(err)
          if (err.data && err.data.timer == 3) {
            this.errMsg = '连续输错3次密码后，需同时输入卡密+校验码'
          } else if (err.data.timer >= 100) {
            this.errMsg = '我怀疑您是机器人，请停止这重复且无用的请求'
            let msg = '我怀疑您是机器人，请停止这重复且无用的请求'
            MessageBox({
              title: '警告！',
              message: msg,
              closeOnClickModal: false,
              confirmButtonText: this.errCount == 1 ? '再见' : '再给我一次机会'
            }).then(action => {
              this.errCount += 1
              if (this.errCount == 2) {
                this.$router.push({ name: 'home' })
              }
            })
          } else {
            this.errMsg = err.errorMsg
          }
          this.timer = err.data.timer ? err.data.timer : this.timer
          this.isShowErr = err.data.timer ? true : false
        }
      )
    },

    getTimer() {
      getRechargeFailedTime().then(res => {
        this.timer = res.timer
      })
    },

    inputCardSec1() {
      this.isTap = true
      this.cardSec1 = this.cardSec1.replace(/[^0-9A-Z]/gi, '').toUpperCase()
    },
    inputCardSec2() {
      this.isTap = true
      this.cardSec2 = this.cardSec2.replace(/ /g, '').slice(0, 4)
    }
  }
}
</script>

<style>
.mint-msgbox .recharge-success-alert {
  font-size: 15px;
  font-family: PingFangSC-Regular;
  font-weight: 400;
  color: #333333;
  line-height: 28px;
}
.mint-msgbox .recharge-success-alert + .recharge-success-alert {
  margin-top: 40px;
}
.mint-msgbox .recharge-success-alert span {
  color: #772508;
}
</style>
<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
  .header {
    flex-shrink: 0;
  }
  .form-wrapper {
    flex-grow: 1;
    padding: 10px 15px;
    .tips {
      padding-left: 6px;
      font-size: 14px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: rgba(136, 136, 136, 1);
      line-height: 20px;
    }
    .input {
      box-sizing: border-box;
      padding: 0 15px;
      margin-top: 12px;
      box-shadow: none;
      font-family: PingFangSC-Medium;
      font-weight: 500;
      border: none;
      width: 345px;
      height: 50px;
      line-height: 50px;
      background: rgba(255, 255, 255, 1);
      border-radius: 2px;
      font-size: 20px;
      color: #404040;
      & + .input {
        margin-top: 15px;
      }
      &::placeholder {
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: #999999;
        font-size: 14px;
      }
    }
    .err-msg {
      margin-top: 12px;
      padding-left: 6px;
      font-size: 14px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: rgba(155, 33, 11, 1);
      line-height: 20px;
      height: 20px;
      & + .err-msg {
        margin-top: 0;
      }
    }
  }
  .btn-wrapper {
    padding: 0 10px;
    margin-top: 50px;
  }
  .button {
    width: 100%;
    @include button($margin: 0, $radius: 2px);
    background-color: #772508;
    font-size: 18px;
  }
  .help {
    text-align: center;
    font-size: 16px;
    font-family: PingFangSC-Regular;
    font-weight: 400;
    color: rgba(85, 46, 32, 1);
    line-height: 22px;
    margin-top: 30px;
  }
}
</style>
