<template>
  <div class="profile-service">
    <div class="service-head">账户中心</div>
    <div class="service-wrapper" @click="fundClick">
      <div class="account-title">
        <div class="account-title-left">
          <span class="title-left-icon">¥</span>
          <span class="title-left-money">{{ total }}</span>
        </div>
        <div class="account-title-right">
          <img src="../../../assets/image/hh-icon/f0-profile/fanhui.png" alt="" />
        </div>
      </div>
      <div class="account-count">
        <p>总权益<img src="../../../assets/image/hh-icon/f0-profile/tt-1.png" alt="" @click.stop="isPopup" /></p>
      </div>
      <!-- <div class="account-foot" v-if="!isAllConfirm">您还有资产暂未确权，确权后将计入您的总资产</div> -->
    </div>
  </div>
</template>

<script>
// import { Popup } from 'mint-ui';
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { getTitleList, getIntegral } from '../../../api/mineLoan'
export default {
  name: 'ProfileAsset',
  data() {
    return {
      /**
       * 普惠已确认所有权益金额
       */
      phHasConfirmWaitCapital: 0,
      /**
       * 尊享已确认所有权益金额
       */
      zxHasConfirmWaitCapital: 0,
      /**
       * 尊享已确认的金额
       */
      money: 0,
      /**
       * 尊享已确认的冻结金额
       */
      lockMoney: 0,
      surplus: '', //有解积分
      total: 0,
      isAllConfirm: false,
      popupVisible: true
    }
  },

  computed: {},
  created() {
    this.getData()
  },
  methods: {
    getData() {
      Promise.all([getIntegral(), getTitleList()]).then(r => {
        console.log(r)
        this.total = this.NumberStr(
          r[1].phHasConfirmWaitCapital,
          r[1].zxHasConfirmWaitCapital,
          r[1].money,
          r[1].lockMoney,
          r[0].surplus
        )
        this.isAllConfirm = r[1].isAllConfirm
      })
    },
    fundClick() {
      this.$router.push({ name: 'totalassets', params: { title: 1 } })
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
        return this.toThousands(num)
      } else {
        num = Number(a) + Number(b) + Number(c) + Number(d) + Number(e)
        return this.toThousands(num)
      }
    },
    toThousands(num) {
      let c =
        num.toString().indexOf('.') !== -1
          ? num.toLocaleString()
          : num.toString().replace(/(\d)(?=(?:\d{3})+$)/g, '$1,')
      return c
    },
    isPopup() {
      let flag = false
      this.$emit('massage', flag)
    }
  }
}
</script>

<style lang="scss" scoped>
.profile-service {
  background-color: #ffffff;
  .service-head {
    font-size: 18px;
    font-weight: bold;
    color: #333;
    line-height: 25px;
    padding: 15px 15px 20px;
  }
  .service-wrapper {
    width: 90%;
    margin: 0 auto 14px;
    padding-bottom: 14px;
    background: rgba(255, 255, 255, 1);
    box-shadow: 0px 0px 6px 0px rgba(0, 0, 0, 0.1);
    .account-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 23px 12px 27px;
      .account-title-left {
        .title-left-icon {
          font-size: 14px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(64, 64, 64, 1);
          margin-right: 4px;
        }
        .title-left-money {
          font-size: 24px;
          font-family: DINAlternate-Bold, DINAlternate;
          font-weight: bold;
          color: rgba(64, 64, 64, 1);
        }
      }
      .account-title-right {
        img {
          width: 8px;
          height: 13px;
        }
      }
    }
    .account-count {
      padding: 0 23px 0 27px;
      p {
        font-size: 14px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(153, 153, 153, 1);
        img {
          width: 14px;
          height: 14px;
          vertical-align: middle;
          margin-left: 6px;
        }
      }
    }
    .account-foot {
      margin-top: 13px;
      font-size: 12px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(252, 127, 12, 1);
      padding: 0 23px 0 27px;
    }
  }
}
</style>
