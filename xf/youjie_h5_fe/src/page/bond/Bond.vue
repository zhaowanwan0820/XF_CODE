<template>
  <div class="container">
    <mt-header class="header" title="我的积分">
      <header-item
        slot="left"
        class="left-icon"
        :isLeft="true"
        :icon="require('../../assets/image/hh-icon/h0-bond/icon-goback.png')"
        v-on:onclick="goBack"
      ></header-item>
      <header-item
        slot="right"
        calss="header-right"
        title="积分说明"
        titleColor="#ffffff"
        v-on:onclick="goBondIntro"
      ></header-item>
    </mt-header>
    <div class="top-wrapper">
      <div class="top-flex">
        <div class="left-flex">
          <div class="title">积分余额</div>
          <div class="count">{{ $accounting.formatNumber(currentBalance, 2) }}</div>
        </div>
        <!-- 隐藏【可兑换积分】 -->
        <!-- <template v-if="currentBond > 0">
          <img src="../../assets/image/hh-icon/h0-bond/icon-line.png" alt="" />
          <div class="right-flex">
            <div class="title">可兑换积分</div>
            <div class="count">{{ $accounting.formatNumber(currentBond, currentBondIsInteger ? 0 : 2) }}</div>
          </div>
        </template> -->
      </div>
      <div class="bottom-flex">
        <div class="left-flex" @click="goBalance">
          <img src="../../assets/image/hh-icon/h0-bond/icon-record.png" alt="" />
          <span>收支明细</span>
        </div>
        <div class="right-flex more-btn-wrapper" v-if="isShowExchangeMore">
          <div class="go-recharge" @click="changeMore">兑换更多</div>
          <!-- <router-link tag="div" class="go-recharge" :to="{ name: 'recharge' }">充值</router-link> -->
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import $cookie from 'js-cookie'
import { mapState, mapMutations, mapActions } from 'vuex'
import { HeaderItem, Button } from '../../components/common'
import { Header, Toast, MessageBox } from 'mint-ui'
import { bondGet } from '../../api/bond'
import { balanceGet } from '../../api/balance'
import { ENUM } from '../../const/enum'
export default {
  name: 'bond',
  beforeRouteEnter(to, from, next) {
    if (from['name']) {
      $cookie.set('bondForm', JSON.stringify({ path: from['path'], query: from['query'] }))
    }
    next()
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      currentBalance: state => state.balance.currentBalance,
      currentBond: state => state.bond.currentBond,
      currentBondIsInteger: state => state.bond.currentBondIsInteger,
      systemTime: state => state.app.systemTime
    }),
    isShowExchangeMore() {
      return false
      // if (this.systemTime >= ENUM.TIMESTAMP2019090712) {
      //   return false
      // }
      // return true
    }
  },
  data() {
    return {}
  },
  created: function() {
    if (this.isOnline) {
      this.getBond()
      balanceGet('userCenter').then(res => {
        this.saveCurrentBalanceState(res.surplus)
      })
    } else {
      // this.goBack()
      this.$router.push({ name: 'login' })
    }
  },
  methods: {
    ...mapMutations({
      saveCurrentBalanceState: 'saveCurrentBalanceState',
      saveExchangeBondState: 'saveExchangeBondState',
      saveCurrentBondState: 'saveCurrentBondState'
    }),
    getBond() {
      bondGet().then(res => {
        this.saveCurrentBondState(res)
      })
    },
    goBack() {
      this.$_goBack()
    },
    goRules() {
      this.$router.push('/bondRules')
    },
    goBondIntro() {
      this.$router.push({ name: 'hbDesc' })
    },
    goBalance() {
      if (this.isOnline) {
        this.$router.push({ name: 'balanceHistory' })
      } else {
        this.showLogin()
      }
    },
    changeMore() {
      MessageBox.prompt('请输入兑换数量', '兑换').then(({ value, action }) => {
        let msg = ''
        if (!value) {
          Toast('请输入要兑换的数量')
          return
        }
        let nVal = value
          .replace(/[^\d.]/g, '') //清除〝数字〞和〝.〞以外的字符
          .replace(/^\./g, '') //验证第一个字符是数字而不是〝.〞
          .replace(/\.{2,}/g, '.') //只保留第一个〝.〞
          .replace('.', '$#$')
          .replace(/\./g, '')
          .replace('$#$', '.')
          .replace(/^(\d+)\.(\d\d).*$/, '$1.$2') //只能输入两个小数
        /*console.log('nVal ', this)
        if (nVal !== value && nVal.substr(-1) !== '.') {
          this.val = nVal
          return
        }*/
        nVal = parseFloat(nVal)

        if (this.currentBond >= 100 && nVal < 100) {
          msg = '单次兑换数不小于100元'
          Toast(msg)
        } else if (nVal > this.currentBond) {
          msg = `超出累计兑换上限${this.$accounting.formatNumber(this.currentBond, 0)}元`
          Toast(msg)
        } else {
          msg = '兑换后我的积分' + this.$accounting.formatNumber(this.currentBalance + nVal, 2)
          Toast(msg)
          this.saveExchangeBondState({ bond: nVal })
          this.$router.push({ name: 'bondDebt' })
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  background-color: #ffffff;
  background-image: url('../../assets/image/hh-icon/h0-bond/bg-bond.png');
  background-repeat: no-repeat;
  background-size: 100% auto;
  background-position: top left;
  .header {
    @include header;
    background-color: transparent;
    /deep/ .mint-header-title {
      font-size: 18px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: #ffffff;
      line-height: 22px;
    }
    .left-icon /deep/ .icon {
      width: auto;
      height: 16px;
    }
  }
  .button {
    @include button($margin: 0 20px 28px, $radius: 23px);
  }
  .tip-wrapper {
    font-size: 14px;
    line-height: 40px;
    height: 40px;
    background-color: #fff9db;
    color: #f58319;
    text-align: center;
  }
  .top-wrapper {
    height: 154px;
    margin: 25px 15px 0;
    padding: 10px 24px 0;
    color: #883a14;
    display: flex;
    box-sizing: border-box;
    flex-direction: column;
    justify-content: space-between;
    align-items: stretch;
    position: relative;
    overflow: hidden;
    border-radius: 4px;
    background-color: #ffffff;
    box-shadow: 0px 2px 4px 0px rgba(10, 9, 9, 0.08);
    .top-flex {
      display: flex;
      justify-content: flex-start;
      align-items: baseline;
      & > div {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding-top: 15px;
        .title {
          font-size: 11px;
          font-family: PingFangSC-Light;
          font-weight: 300;
          color: rgba(102, 102, 102, 1);
          line-height: 16px;
        }
        .count {
          margin-top: 15px;
          font-size: 20px;
          font-family: DINPro-Medium;
          font-weight: 500;
          color: rgba(64, 64, 64, 1);
          line-height: 1;
        }
      }
      img {
        width: 7px;
        align-self: flex-end;
        margin: 0 10px;
      }
    }
    .bottom-flex {
      display: flex;
      height: 50px;
      justify-content: space-between;
      align-items: center;
      background-color: rgba(255, 239, 223, 1);
      margin: 0 -24px;
      padding: 0 24px;
      .left-flex {
        display: flex;
        align-items: center;
      }
      img {
        width: 20px;
      }
      span {
        font-size: 14px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: #fc7f0c;
        line-height: 1;
      }
      .go-recharge {
        width: 70px;
        height: 30px;
        border-radius: 2px;
        border: 1px solid rgba(138, 114, 72, 1);
        font-size: 16px;
        font-family: PingFangSC-Regular;
        text-align: center;
        font-weight: 400;
        color: rgba(138, 114, 72, 1);
        line-height: 30px;
      }
      .more-btn-wrapper {
        div {
          padding: 0 8px;
        }
      }
    }
  }
  .row-title {
    color: $baseColor;
    font-size: 15px;
    position: relative;
    .amount {
      font-size: 14px;
      font-weight: 600;
      float: right;
    }
  }
}
</style>
