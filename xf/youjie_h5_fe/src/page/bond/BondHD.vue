<template>
  <div class="container">
    <mt-header class="header" :title="`我的积分`">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      <header-item
        slot="right"
        calss="header-right"
        title="积分说明"
        titleColor="#000000"
        v-on:onclick="goBondIntro"
      ></header-item>
    </mt-header>
    <div class="top-wrapper">
      <div class="top-flex">
        <div class="left-flex">
          <div class="title">积分余额</div>
          <div class="count">{{ $accounting.formatNumber(currentTokenHD, 2) }}</div>
        </div>
      </div>
    </div>
    <div class="bottom-wrapper">
      <label class="service">
        客服电话：
        <a :href="'tel:' + service_tel">{{ service_tel }}</a>
      </label>
      <label class="service">
        周一至周日9:30~18:30
      </label>
    </div>
    <div class="service-wrapper">
      <img src="../../assets/image/hh-icon/h0-bond/icon-在线客服.svg" @click="goBot" />
    </div>
  </div>
</template>

<script>
import $cookie from 'js-cookie'
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { HeaderItem, Button } from '../../components/common'
import { Header, Toast } from 'mint-ui'
import { bondGet } from '../../api/bond'
import { balanceGet } from '../../api/balance'
import { ENUM } from '../../const/enum'
export default {
  name: 'BondHD',
  beforeRouteEnter(to, from, next) {
    if (from['name']) {
      $cookie.set('bondForm', JSON.stringify({ path: from['path'], query: from['query'] }))
    }
    next()
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      currentTokenHD: state => state.balance.currentTokenHD
      // currentBond: state => state.bond.currentBond,
      // currentBondIsInteger: state => state.bond.currentBondIsInteger
    })
  },
  data() {
    return {
      service_tel: ENUM.SERVICE.MASTER_TEL
    }
  },
  created: function() {
    if (this.isOnline) {
      // this.getBond()
      balanceGet('userCenter').then(res => {
        this.saveCurrentTokenHDState(res.token_all)
      })
    } else {
      // this.goBack()
      this.$router.push({ name: 'login' })
    }
  },
  methods: {
    ...mapMutations({
      saveCurrentTokenHDState: 'saveCurrentTokenHDState'
      // saveExchangeBondState: 'saveExchangeBondState',
      // saveCurrentBondState: 'saveCurrentBondState'
    }),
    /*getBond() {
      bondGet().then(res => {
        this.saveCurrentBondState(res)
      })
    },*/
    goBack() {
      this.$_goBack()
    },
    goRules() {
      this.$router.push('/bondRules')
    },
    goBot() {
      this.$router.push({ name: 'webPage', query: { url: ENUM.SERVICE.ITOUZI_H5, title: '客服中心' } })
    },
    goBondIntro() {
      this.$router.push({ name: 'BondHDRules' })
    },
    goBalance() {
      if (this.isOnline) {
        this.$router.push({ name: 'balanceHistory' })
      } else {
        this.showLogin()
      }
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
      color: rgba(0, 0, 0, 1);
      line-height: 22px;
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
    height: 103px;
    margin: 17px 15px 0;
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
    @include bis('../../assets/image/hh-icon/h0-bond/bg-bond-hd.png');
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
        color: rgba(172, 144, 94, 1);
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
  .bottom-wrapper {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    margin-bottom: 43px;
    display: flex;
    flex-direction: column;
    margin-top: 81px;
    .service {
      text-align: center;
      font-size: 13px;
      color: $subbaseColor;
      line-height: 18px;
      margin-bottom: 10px;
      a {
        color: #552e20;
      }
    }
  }
}
.service-wrapper {
  @include wh(50px, 50px);
  background-color: #fff;
  border-radius: 50%;
  box-shadow: 0px 0px 8px 0px rgba(14, 3, 36, 0.07);
  position: absolute;
  right: 13px;
  bottom: 48px;
  img {
    @include wh(31px, 31px);
    margin: 9px auto;
    display: block;
  }
}
.in {
  font-size: 34px;
  width: 100%;
  height: 48px;
  border: 0;
  background: #fff;
  resize: none;
  outline: none;
  box-sizing: border-box;
  &:focus {
    border-color: #5caaf8;
    & + .ilb:before {
      color: #5caaf8;
    }
    & + .ilb:after {
      width: 0;
    }
  }
  &:focus + .ilb:before,
  &:valid + .ilb:before {
    transition-duration: 0.2s;
    transform: translate(-0.8rem, -2.2rem) scale(0.9, 0.9);
  }
  &:valid + .ilb:after {
    width: 0;
  }
  &:invalid + .ilb[alt]:before {
    content: attr(alt);
  }
  & + .ilb {
    margin-top: -32px;
    margin-right: 15px;
    display: block;
    position: relative;
    pointer-events: none;
    &:before {
      content: attr(placeholder);
      font-size: 17px;
      line-height: 24px;
      display: inline-block;
      margin-left: 10px;
      color: #b5b6b6;
      white-space: nowrap;
      transition: 0.3s ease-in-out;
      background-image: linear-gradient(to bottom, #ffffff, #ffffff);
      background-size: 100% 5px;
      background-repeat: no-repeat;
      background-position: center;
    }
    &:after {
      content: '';
      width: 3px;
      height: 100%;
      background-color: #5caaf8;
      border-radius: 3px;
      position: absolute;
      left: 0;
      bottom: -1px;
    }
  }
}
</style>
