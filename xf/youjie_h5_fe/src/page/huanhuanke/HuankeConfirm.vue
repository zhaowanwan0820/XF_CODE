<template>
  <div class="container">
    <mt-header class="header" title="确认付款">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBackPage"></header-item>
    </mt-header>
    <div class="page-wrapper clearfix">
      <p class="title">积分变现</p>
      <p class="title-msg">支付积分后可变现</p>
      <div class="hb-acount">
        <div class="account-balance">
          <span class="txt">需支付积分</span>
          <div class="money">
            <img class="hh-icon" src="../../assets/image/hh-icon/mlm/icon-surplus.png" />
            <span class="num">{{ surplus }}</span>
          </div>
        </div>

        <div v-if="txt_account_not_enough" class="account-exchange">
          <span class="txt">{{ txt_account_not_enough }}</span>
          <div class="exchange" @click="toExchange">
            <span class="txt">兑换更多积分</span>
            <img src="../../assets/image/hh-icon/b10-pay/icon-right-triangle.png" />
          </div>
        </div>
      </div>

      <div class="btn-pay">
        <p class="btn-desc" v-if="!authStatus && platform">请在{{ utils.storeName }}APP中，完成授权后再支付积分</p>
        <button class="btn" @click="toPay">立即付款</button>
      </div>
    </div>
  </div>
</template>
<script>
import { Header } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import { Toast, Indicator } from 'mint-ui'
import { mapState, mapMutations } from 'vuex'
import { balanceGet } from '../../api/balance'
import { huanPay, huanPayInfo } from '../../api/huanhuanke'

export default {
  name: 'HuankeConfirm',
  data() {
    return {
      id: this.$route.params.id,
      code: 'balance', //积分支付
      accountBalance: '-', // 账户积分余额

      needExchange: false, // 是否需要兑换更多积分

      surplus: 0, //需要支付积分数
      huan_order_id: 0 //兑换积分 订单id
    }
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      platform: state => state.auth.platform,
      authStatus: state => state.itouzi.authStatus,
      currentBalance: state => state.balance.currentBalance, //积分余额
      currentBond: state => state.bond.currentBond //当前用户拥有债权
    }),

    txt_account_not_enough() {
      // 账户积分余额不够 提示文案
      let txt = ''
      if (this.needExchange) {
        txt = '积分余额不足'
      }

      return txt
    }
  },
  created() {
    // 获取订单详情
    if (!this.id) {
      Toast('订单Id异常')
      return
    }

    this.getOrderDetail()
  },
  methods: {
    ...mapMutations({
      saveCurrentBalanceState: 'saveCurrentBalanceState',
      itzAuthGuide: 'itzAuthGuide'
    }),
    async getOrderDetail() {
      Indicator.open()

      balanceGet().then(
        res => {
          this.saveCurrentBalanceState(res.surplus)
          this.accountBalance = res.surplus
        },
        error => {
          Toast('检查用户账户的积分余额失败，错误信息：' + error.errorMsg)
        }
      )

      try {
        const res = await huanPayInfo(this.id)

        this.needExchange = res.surplus - this.accountBalance > 0 ? true : false
        this.surplus = res.surplus
        this.huan_order_id = res.list[0].order_id
      } catch (error) {
        Indicator.close()
        Toast('获取订单详情异常，错误信息：' + error.errorMsg)
        return
      }
    },
    needAuth() {
      return !this.authStatus && this.platform
    },
    toExchange() {
      // 授权认证
      // if (this.needAuth()) {
      //   return this.itzAuthGuide({ isHHApp: this.isHHApp })
      // }

      // 去兑换更多积分
      let params = { need: this.surplus - this.accountBalance, order: this.huan_order_id }
      this.$router.push({
        name: 'bondDebt',
        params: params
      })
    },
    toPay() {
      // 授权认证
      // if (this.needAuth()) {
      //   return this.itzAuthGuide({ isHHApp: this.isHHApp })
      // }

      if (this.needExchange) {
        return
      }

      // 余额充足
      Indicator.open()
      let ids = []
      ids.push(this.id)
      huanPay(ids, this.code)
        .then(
          res => {
            this.$router.replace({ name: 'HuanKeResult', params: { id: this.id } })
          },
          error => {
            // error.errorCode  901余额不足  902债权不足
            Toast(error.errorMsg)
          }
        )
        .finally(() => {
          Indicator.close()
        })
    },
    goBackPage() {
      this.$_goBack()
    }
  }
}
</script>
<style lang="scss" scoped>
.container {
  min-height: 100%;
  background-color: #fff;
}
.page-wrapper {
  background: url('../../assets/image/hh-icon/mlm/bg-order.png') #fff no-repeat;
  background-size: 375px 108px;
  padding: 15px;
}
.title {
  font-size: 18px;
  font-weight: 500;
  color: #552e20;
  line-height: 25px;
}
.title-msg {
  @include sc(11px, #552e20, left);
  font-weight: 400;
  line-height: 16px;
  margin: 3px 0 14px;
  opacity: 0.5;
}
.hh-icon {
  width: 12px;
}
.hb-acount {
  background: rgba(255, 255, 255, 1);
  box-shadow: 0px 2px 4px 0px rgba(219, 204, 204, 0.5);
  border-radius: 2px;
  padding: 15px;

  .account-balance {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .txt {
      font-size: 18px;
      font-weight: 400;
      color: #404040;
      line-height: 25px;
    }
    .money {
      font-size: 0;
      display: flex;
      justify-content: center;
      align-items: center;

      .num {
        font-size: 20px;
        font-weight: 500;
        color: #404040;
        line-height: 28px;
      }
      img {
        width: 16px;
        height: 16px;
        margin-right: 5px;
      }
    }
  }
  .account-exchange {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;

    .txt {
      font-size: 13px;
      font-weight: 400;
      color: rgba(153, 153, 153, 1);
      line-height: 18px;
    }
    .exchange {
      font-size: 0;
      display: flex;
      justify-content: center;
      align-items: center;

      .txt {
        font-size: 13px;
        font-weight: 300;
        color: rgba(86, 47, 33, 1);
        line-height: 18px;
      }
      img {
        width: 6px;
        margin-left: 3px;
      }
    }
  }
}

.pay-detail {
  background: rgba(255, 255, 255, 1);
  border-radius: 2px;
  margin-top: 10px;
  display: flex;
  justify-content: flex-end;
  align-items: center;

  & > div {
    padding: 13px 14px 13px;
    font-size: 0;

    * {
      display: inline-block;
      vertical-align: middle;
    }
    .txt {
      font-size: 12px;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
      line-height: 17px;
      vertical-align: middle;
    }
    .num {
      font-size: 20px;
      font-weight: bold;
      color: rgba(119, 37, 8, 1);
      line-height: 24px;
      margin-left: 3px;
    }
  }
}

.btn-pay {
  margin: 60px 9px;

  .btn {
    height: 46px;
    background: rgba(119, 37, 8, 1);
    border-radius: 2px;
    width: 100%;
    background: rgba(119, 37, 8, 1);
    font-size: 18px;
    font-weight: 400;
    color: rgba(255, 255, 255, 1);

    &.needExchange {
      background: rgba(119, 37, 8, 1);
      opacity: 0.3;
    }
    &.disabled {
      background: rgba(192, 192, 192, 1);
    }
  }
}
.btn-desc {
  font-size: 12px;
  text-align: center;
  margin-bottom: 10px;
}
</style>
