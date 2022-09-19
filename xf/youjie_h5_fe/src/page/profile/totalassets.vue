<template>
  <div class="container">
    <mt-header class="header" fixed :title="title">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="assets">
      <div class="assets-box">
        <div class="assets-box-title">
          <h2>{{ total }}</h2>
          <img src="../../assets/image/hh-icon/f0-profile/cc-2.png" alt="" @click="eyeClick" v-if="isEye" />
          <img src="../../assets/image/hh-icon/f0-profile/cc-1.png" alt="" v-else @click="eyeClick" />
        </div>
        <div class="assets-box-text">总权益(元)</div>
        <div class="assets-box-foot">
          <div class="foot-box">
            <h1>￥{{ bx }}</h1>
            <p @click="isPupg(1)">所有权益 <img src="../../assets/image/hh-icon/f0-profile/tt-2.png" alt="" /></p>
          </div>
          <div class="foot-box foot-box-conter">
            <h1>￥{{ mon }}</h1>
            <p @click="isPupg(2)">原机构账户余额 <img src="../../assets/image/hh-icon/f0-profile/tt-2.png" alt="" /></p>
          </div>
          <div class="foot-box">
            <h1>{{ surpluss }}</h1>
            <p @click="isPupg(3)">有解积分 <img src="../../assets/image/hh-icon/f0-profile/tt-2.png" alt="" /></p>
          </div>
        </div>
      </div>
      <div class="assets-foot">
        <div class="assets-foot-title">
          <h1>机构</h1>
          <div class="assets-foot-title-conter" @click="goToClick">
            <span>网信</span>
            <div class="foot-title-conter-text">
              <span>¥</span>
              <span>{{ zs }}</span>
              <img src="../../assets/image/hh-icon/f0-profile/fanhui.png" alt="" />
            </div>
          </div>
        </div>
        <div class="assets-foot-bank" @click="goToBank">
          <h1>银行卡</h1>
          <img src="../../assets/image/hh-icon/f0-profile/fanhui.png" alt="" />
        </div>
        <!-- <div class="assets-foot-bank" @click="goToList">
          <h1>资产收支明细</h1>
          <img src="../../assets/image/hh-icon/f0-profile/fanhui.png" alt="" />
        </div> -->
      </div>
    </div>
    <mt-popup v-model="popupVisible" popup-transition="popup-fade">
      <h1>所有权益</h1>
      <p>已确认的所有权益</p>
    </mt-popup>
    <mt-popup v-model="popupVisible_t" popup-transition="popup-fade" class="t_view">
      <h1>原机构账户余额</h1>
      <p>已确认的全部机构的账户金额合计</p>
    </mt-popup>
    <mt-popup v-model="popupVisible_f" popup-transition="popup-fade" class="f_view">
      <h1>有解积分</h1>
      <p>全部机构的权益兑换的有解积分合计</p>
    </mt-popup>
  </div>
</template>

<script>
import { getTitleList, getIntegral } from '../../api/mineLoan'
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { Toast, MessageBox } from 'mint-ui'
export default {
  name: 'totalassets',
  data() {
    return {
      title: '账户中心',
      money: 0,
      isEye: true,
      phHasConfirmWaitCapital: 0,
      zxHasConfirmWaitCapital: 0,
      lockMoney: 0,
      surplus: 0,
      total: 0,
      totals: 0,
      bx: 0, //本息
      bxs: 0, //本息
      mon: 0, //余额
      mons: 0, //余额
      zs: 0,
      surpluss: 0,
      popupVisible: false,
      popupVisible_t: false,
      popupVisible_f: false
    }
  },
  created() {
    // console.log(this.$route)
    this.getData()
  },
  computed: {
    ...mapState({
      hasDebtAuthentication: state => state.auth.wxAuthCheckInfo.hasDebtAuthentication
    })
  },
  methods: {
    isPupg(t) {
      console.log(1)
      if (t == 1) {
        this.popupVisible = true
      } else if (t == 2) {
        this.popupVisible_t = true
      } else if (t == 3) {
        this.popupVisible_f = true
      }
    },
    goBack() {
      this.$_goBack()
    },
    bindBank() {
      this.$router.push({ name: 'bindbankcard' })
    },
    goToClick() {
      this.$router.push({ name: 'itemto' })
    },
    goToBank() {
      if (this.hasDebtAuthentication != 0) {
        this.$router.push({ name: 'sibank' })
      } else {
        Toast('您还未确权认证！')
      }
    },
    goToList() {
      this.$router.push({ name: 'AssetList' })
    },
    eyeClick() {
      this.isEye = !this.isEye
      if (this.isEye) {
        this.total = this.totals
        this.bx = this.bxs
        this.mon = this.mons
        this.surplus = this.surpluss
      } else {
        this.total = '******'
        this.bx = '****'
        this.mon = '****'
        this.surplus = '****'
      }
    },
    getData() {
      Promise.all([getIntegral(), getTitleList()]).then(r => {
        // console.log(r)
        this.total = this.NumberStr(
          r[1].phHasConfirmWaitCapital,
          r[1].zxHasConfirmWaitCapital,
          r[1].money,
          r[1].lockMoney,
          r[0].surplus
        )
        this.totals = this.NumberStr(
          r[1].phHasConfirmWaitCapital,
          r[1].zxHasConfirmWaitCapital,
          r[1].money,
          r[1].lockMoney,
          r[0].surplus
        )
        this.bx = this.NumberStr_two(r[1].phHasConfirmWaitCapital, r[1].zxHasConfirmWaitCapital)
        this.bxs = this.NumberStr_two(r[1].phHasConfirmWaitCapital, r[1].zxHasConfirmWaitCapital)
        this.mon = this.NumberStr_two(r[1].money, r[1].lockMoney)
        this.mons = this.NumberStr_two(r[1].money, r[1].lockMoney)
        // console.log(r[0].surplus)
        this.surplus = r[0].surplus
        this.surpluss = r[0].surplus
        this.zs = this.NumberStr_f(
          r[1].phHasConfirmWaitCapital,
          r[1].zxHasConfirmWaitCapital,
          r[1].money,
          r[1].lockMoney
        )
      })
    },
    NumberStr(a, b, c, d, e) {
      let num = 0
      if (
        typeof a === 'number' &&
        typeof b === 'number' &&
        typeof c === 'number' &&
        typeof d === 'number' &&
        typeof e === 'number'
      ) {
        num = a + b + c + d + e
        return this.toThousands(num, 2)
      } else {
        num = Number(a) + Number(b) + Number(c) + Number(d) + Number(e)
        return this.toThousands(num, 2)
      }
    },
    NumberStr_two(a, b) {
      let num = 0
      if (typeof a === 'number' && typeof b === 'number') {
        num = a + b
        return this.toThousands(num, 2)
      } else {
        num = Number(a) + Number(b)
        return this.toThousands(num, 2)
      }
    },
    NumberStr_f(a, b, c, d) {
      let num = 0
      if (typeof a === 'number' && typeof b === 'number' && typeof c === 'number' && typeof d === 'number') {
        num = a + b + c + d
        return this.toThousands(num, 2)
      } else {
        num = Number(a) + Number(b) + Number(c) + Number(d)
        return this.toThousands(num, 2)
      }
    },
    toThousands(num) {
      if (num) {
        let c =
          num.toString().indexOf('.') !== -1
            ? num.toLocaleString()
            : num.toString().replace(/(\d)(?=(?:\d{3})+$)/g, '$1,')
        return c
      } else {
        return 0
      }
    }
  }
}
</script>

<style lang="scss" scoped>
/deep/.mint-popup {
  position: absolute;
  left: 28%;
  top: 250px;
  width: 149px;
  padding: 12px 10px;
  border-radius: 5px;
  box-shadow: 0px 0px 10px 0px rgba(252, 127, 12, 0.12);
  h1 {
    font-size: 14px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    line-height: 20px;
    margin-bottom: 7px;
  }
  p {
    font-size: 12px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(153, 153, 153, 1);
    line-height: 20px;
  }
}
/deep/.mint-popup:before {
  display: inline-block;
  width: 0;
  height: 0;
  border: solid transparent;
  border-width: 10px;
  border-bottom-color: #fff;
  content: '';
  position: absolute;
  top: -20px;
  left: 76px;
}
.t_view {
  left: 52%;
  top: 250px;
  p {
    height: 40px;
  }
  &:before {
    content: '';
    left: 110px;
  }
}
.f_view {
  left: 72%;
  top: 250px;
  p {
    height: 40px;
  }
  &:before {
    content: '';
    left: 126px;
  }
}
header.mint-header.header.is-fixed {
  /*border-bottom: 1px solid #f4f4f4;*/
  background: linear-gradient(45deg, rgba(254, 131, 58, 1) 0%, rgba(255, 102, 88, 1) 100%);
  font-size: 18px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: rgba(255, 255, 255, 1);
}
.header:after {
  background-color: transparent;
}
.container {
  height: 100%;
  background-color: #fff;
  .header {
    @include header;
  }
  .assets {
    height: 89px;
    padding-top: 30px;
    background: linear-gradient(45deg, rgba(254, 131, 58, 1) 0%, rgba(255, 102, 88, 1) 100%);
    .assets-box {
      width: 90%;
      height: 160px;
      margin: 20px auto 0;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0px 0px 10px 0px rgba(252, 127, 12, 0.22);
      border-radius: 8px;
      .assets-box-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 21px 20px 4px 20px;
        h2 {
          min-width: 205px;
          font-size: 32px;
          font-family: DINAlternate-Bold, DINAlternate;
          font-weight: bold;
          color: rgba(64, 64, 64, 1);
        }
        img {
          width: 19px;
          height: 13px;
          margin-left: 17px;
        }
        .title-img-2 {
          margin-left: 11px;
        }
      }
      .assets-box-text {
        font-size: 14px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(153, 153, 153, 1);
        margin-bottom: 20px;
        padding: 0 20px;
      }
      .assets-box-foot {
        display: flex;
        align-items: center;
        padding: 0 20px;
        .foot-box {
          flex: 1;
          text-align: center;
          h1 {
            font-size: 16px;
            font-family: DINPro-Regular, DINPro;
            font-weight: 400;
            color: rgba(64, 64, 64, 1);
            margin-bottom: 2px;
          }
          p {
            font-size: 12px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(153, 153, 153, 1);
            img {
              width: 13px;
              height: 13px;
              vertical-align: middle;
            }
          }
        }
        .foot-box-conter {
          flex: auto;
          max-width: 120px;
          border-left: 1px solid rgba(153, 153, 153, 0.32);
          border-right: 1px solid rgba(153, 153, 153, 0.32);
          padding: 0;
        }
        .foot-box:nth-child(1) {
          padding-right: 6px;
        }
        .foot-box:nth-child(3) {
          padding-left: 6px;
          /*min-width: 70px;*/
        }
      }
    }
    .assets-foot {
      padding-top: 33px;
      margin: 0 21px 0 15px;
      .assets-foot-title {
        h1 {
          height: 22px;
          font-size: 16px;
          font-family: PingFangSC-Medium, PingFang SC;
          font-weight: 500;
          color: rgba(64, 64, 64, 1);
          line-height: 22px;
          border-bottom: 1px #f4f4f4 dashed;
          padding-bottom: 10px;
        }
        .assets-foot-title-conter {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 14px 3px 15px 6px;
          border-bottom: 1px #f4f4f4 dashed;
          span {
            font-size: 14px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(102, 102, 102, 1);
          }
          .foot-title-conter-text {
            span {
              margin-right: 10px;
              font-size: 16px;
              font-family: DINPro-Regular, DINPro;
              font-weight: 400;
              color: rgba(102, 102, 102, 1);
            }
            span:nth-child(1) {
              font-size: 14px;
              font-family: PingFangSC-Regular, PingFang SC;
              font-weight: 400;
              color: rgba(102, 102, 102, 1);
              margin-right: 3px;
            }
            img {
              width: 8px;
              height: 13px;
            }
          }
        }
      }
      .assets-foot-bank {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 3px 15px 0;
        border-bottom: 1px #f4f4f4 dashed;
        &:last-child {
          border: 0;
        }
        h1 {
          font-size: 16px;
          font-family: PingFangSC-Medium, PingFang SC;
          font-weight: 500;
          color: rgba(64, 64, 64, 1);
        }
        img {
          width: 8px;
          height: 13px;
        }
      }
    }
  }
}
</style>
