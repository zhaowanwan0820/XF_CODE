<template>
  <div class="page-wrapper clearfix">
    <p class="title">好友代付订单</p>
    <div class="hb-acount">
      <div class="account-balance">
        <span class="txt">账户积分余额</span>
        <div class="money">
          <img class="hh-icon" src="../../assets/image/hh-icon/b0-home/money-icon.png" />
          <span class="num">{{ accountBalance }}</span>
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

    <div class="pay-detail">
      <div>
        <span class="txt">积分支付：</span>
        <img class="hh-icon" src="../../assets/image/hh-icon/b0-home/money-icon.png" />
        <span class="num">{{ needPayMoney }}</span>
      </div>
    </div>

    <div class="btn-pay">
      <p class="btn-desc" v-if="!authStatus && platform">请在{{ utils.storeName }}APP中，完成授权后再为好友支付积分</p>
      <button :class="btnClassName" @click="toPay">{{ btnTxt }}</button>
    </div>
  </div>
</template>
<script>
import { Toast, Indicator } from 'mint-ui'
import { mapState, mapMutations, mapActions } from 'vuex'
import { friendPayConfirmGet, friendPayConfirmPay } from '../../api/friendPay'

export default {
  data() {
    return {
      orderid: '',
      product: '',
      id: this.$route.params.id,
      status: 0, // 0 待支付 4 已完成 5 已取消（已过期）
      accountBalance: '-', // 账户积分余额

      needExchange: false, // 是否需要兑换更多积分
      needPayMoney: '-', // 需要支付的积分数

      not_enough_hb_for_special_debt: false // 是否为指定债权商品
    }
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      platform: state => state.auth.platform,
      authStatus: state => state.itouzi.authStatus
    }),
    btnTxt() {
      let btn = '立即支付'

      if (!this.isOnline) {
        btn = `登录${this.utils.storeNameForShort} 去支付`
      } else {
        if (!this.authStatus && this.platform) {
          btn = `下载${this.utils.storeName}APP`
        } else if (this.needExchange) {
          btn = '余额不足'
        }
      }

      return btn
    },
    btnClassName() {
      let className = 'btn'
      if (this.isOnline) {
        if (!this.authStatus && this.platform) {
        } else if (this.needExchange) {
          className += ' needExchange'
        }
      }

      return className
    },
    txt_account_not_enough() {
      // 账户积分余额不够 提示文案
      let txt = ''
      if (this.needExchange) {
        txt = '积分余额不足'

        if (this.not_enough_hb_for_special_debt) {
          txt = '需指定债权兑换支付'
        }
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
    async getOrderDetail() {
      Indicator.open()

      try {
        const res = await friendPayConfirmGet(this.id)

        this.orderid = res.id
        this.product = res.goods[0].id

        this.accountBalance = res.hhpay.need_surplus
        this.needExchange = res.order_amount - res.hhpay.need_surplus > 0 ? true : false
        this.needPayMoney = res.order_amount
        this.status = res.status

        // check status
        if (res.status != 0) {
          Indicator.close()
          Toast('该订单已' + (res.status == 4 ? '完成' : '取消'))
          return
        }
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
      if (!this.isOnline) {
        return this.$router.push({ name: 'login', query: { redirect: this.$route.fullPath } })
      }

      // 授权认证
      if (this.needAuth()) {
        setTimeout(() => {
          window.location.href = this.hhAppUrl
        }, 3000)
        Toast(`请先下载${this.utils.storeName}App完成授权认证`)
        return
      }

      // 去兑换更多积分
      let params = { need: this.needPayMoney - this.accountBalance, order: this.orderid }
      if (this.not_enough_hb_for_special_debt) {
        params['product'] = this.product
      }
      this.$router.push({
        name: 'bondDebt',
        params: params
      })
    },
    toPay() {
      if (!this.isOnline) {
        return this.$router.push({ name: 'login', query: { redirect: this.$route.fullPath } })
      }
      // 授权认证
      if (this.needAuth()) {
        setTimeout(() => {
          window.location.href = this.hhAppUrl
        }, 3000)
        Toast(`请先下载${this.utils.storeName}App完成授权认证`)
        return
      }

      if (this.needExchange || this.status != 0) {
        return
      }

      // 余额充足
      Indicator.open()
      friendPayConfirmPay(this.id)
        .then(
          res => {
            this.$router.replace({ name: 'friendPayResult', params: { orderId: this.orderid, isSuccess: 1 } })
          },
          error => {
            this.$router.replace({
              name: 'friendPayResult',
              params: { orderId: this.orderid, isSuccess: 0, msg: encodeURIComponent(error.errorMsg || '支付失败！') }
            })
          }
        )
        .finally(() => {
          Indicator.close()
        })
    }
  }
}
</script>
<style lang="scss" scoped>
.page-wrapper {
  min-height: 100%;
  background-color: #f4f4f4;
  padding: 0 15px;
}
.title {
  padding: 12px 0;
  font-size: 14px;
  font-weight: 400;
  color: rgba(85, 46, 32, 1);
  line-height: 20px;
}
.hh-icon {
  width: 12px;
}
.hb-acount {
  background: rgba(255, 255, 255, 1);
  border-radius: 2px;
  padding: 17px 16px 15px 14px;

  .account-balance {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .txt {
      font-size: 16px;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
      line-height: 22px;
    }
    .money {
      font-size: 0;
      display: flex;
      justify-content: center;
      align-items: center;

      .num {
        font-size: 16px;
        font-weight: bold;
        color: rgba(64, 64, 64, 1);
        line-height: 16px;
        margin-left: 3px;
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
