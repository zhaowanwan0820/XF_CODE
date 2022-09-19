<template>
  <div class="container">
    <mt-header class="header" fixed title="网信">
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
            <p>所有权益</p>
          </div>
          <div class="foot-box foot-box-conter">
            <h1>￥{{ mon }}</h1>
            <p>原机构账户余额</p>
          </div>
        </div>
      </div>
      <div class="assets-foot">
        <div class="assets-foot-title">
          <h1>所有权益</h1>
          <div class="assets-foot-title-conter" @click="goToClick(1)">
            <span>网信普惠</span>
            <div class="foot-title-conter-text">
              <span>¥</span>
              <span>{{ phHasConfirmWaitCapital }}</span>
              <img src="../../assets/image/hh-icon/f0-profile/fanhui.png" alt="" />
            </div>
          </div>
          <div class="assets-foot-title-conter" @click="goToClick(0)">
            <span>尊享</span>
            <div class="foot-title-conter-text">
              <span>¥</span>
              <span>{{ zxHasConfirmWaitCapital }}</span>
              <img src="../../assets/image/hh-icon/f0-profile/fanhui.png" alt="" />
            </div>
          </div>
        </div>
        <div class="assets-foot-title" style="margin-top: 16px;">
          <h1>原机构账户余额</h1>
          <div class="assets-foot-title-conter">
            <span>现金</span>
            <div class="foot-title-conter-text">
              <span>¥</span>
              <span>{{ money }}</span>
            </div>
          </div>
          <div class="assets-foot-title-conter">
            <span>冻结</span>
            <div class="foot-title-conter-text">
              <span>¥</span>
              <span>{{ lockMoney }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getTitleList, getIntegral } from '../../api/mineLoan'
export default {
  name: 'itemto',
  data() {
    return {
      isEye: true,
      money: 0,
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
      zs: 0
    }
  },
  created() {
    // console.log(this.$route)
    this.getData()
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    bindBank() {
      this.$router.push({ name: 'bindbankcard' })
    },
    eyeClick() {
      this.isEye = !this.isEye
      if (this.isEye) {
        this.total = this.totals
        this.bx = this.bxs
        this.mon = this.mons
      } else {
        this.total = '******'
        this.bx = '****'
        this.mon = '****'
      }
    },
    goToClick(type) {
      if (type) {
        this.$router.push({ name: 'fund', params: { type: 2 } })
      } else {
        this.$router.push({ name: 'fund', params: { type: 1 } })
      }
    },
    getData() {
      Promise.all([getIntegral(), getTitleList()]).then(r => {
        console.log(r)
        this.total = this.NumberStr(
          r[1].phHasConfirmWaitCapital,
          r[1].zxHasConfirmWaitCapital,
          r[1].money,
          r[1].lockMoney,
          0
          // r[0].surplus
        )
        this.totals = this.NumberStr(
          r[1].phHasConfirmWaitCapital,
          r[1].zxHasConfirmWaitCapital,
          r[1].money,
          r[1].lockMoney,
          0
          // r[0].surplus
        )
        this.bx = this.NumberStr_two(r[1].phHasConfirmWaitCapital, r[1].zxHasConfirmWaitCapital)
        this.bxs = this.NumberStr_two(r[1].phHasConfirmWaitCapital, r[1].zxHasConfirmWaitCapital)
        this.mon = this.NumberStr_two(r[1].money, r[1].lockMoney)
        this.mons = this.NumberStr_two(r[1].money, r[1].lockMoney)
        this.surplus = this.NumberStr_two(r[0].surplus)
        this.money = r[1].money
        this.phHasConfirmWaitCapital = r[1].phHasConfirmWaitCapital
        this.zxHasConfirmWaitCapital = r[1].zxHasConfirmWaitCapital
        this.lockMoney = r[1].lockMoney
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
          // text-align: center;
          h1 {
            font-size: 16px;
            font-family: PingFangSC-Medium, PingFang SC;
            font-weight: 500;
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
          border-bottom: 1px solid rgba(244, 244, 244, 1);
          padding-bottom: 10px;
        }
        .assets-foot-title-conter {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 14px 3px 15px 6px;
          border-bottom: 1px solid rgba(244, 244, 244, 1);
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
    }
  }
}
</style>
