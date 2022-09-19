<template>
  <div class="container">
    <mt-header class="header" title="支付明细">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="detail-container">
      <p class="title">总支付</p>
      <div class="total">
        <!-- <div class="surplus">
          <img src="../../assets/image/hh-icon/b0-home/money-icon.png" alt />
          <label>580</label>
        </div>-->
        <div class="cash">
          <!-- <label class="add">+</label> -->
          <label class="cash-icon">￥</label>
          <label class="cash-num">{{ total }}</label>
        </div>
      </div>
      <div class="pay-detail">
        <div class="head">
          <div class="line"></div>
          <p class="title">支付明细</p>
          <div class="line"></div>
        </div>
        <div class="pay-info">
          <div class="cash" v-if="money_paid != 0">
            <div class="cash-title">现金支付</div>
            <div class="cash-amount">￥ {{ money_paid }}</div>
          </div>
          <div class="surplus" v-if="surplus_paid != 0">
            <div class="surplus-title" v-if="orderDetail.share_sn">好友支付积分{{ nickname }}</div>
            <div class="surplus-title" v-else>积分支付</div>
            <div class="surplus-amount">
              <img v-if="token_type == 1" src="../../assets/image/hh-icon/b0-home/money-icon.png" alt />
              <img v-if="token_type == 2" src="../../assets/image/hh-icon/b0-home/money-icon-hb.png" alt />
              {{ surplus_paid }}
            </div>
          </div>
          <div class="payment" v-if="money_paid != 0">
            <div class="payment-title">{{ payment }}</div>
            <div class="payment-number">{{ transaction_id }}</div>
          </div>
        </div>
      </div>

      <div class="rules-wrappers">
        <div class="head">
          <div class="line"></div>
          <p class="title">规则明细</p>
          <div class="line"></div>
        </div>
        <div>
          <p class="rule-item bottom10">1.订单支付完成后，您可以通过【我的】—【我的订单】查询订单的相关信息。</p>
          <p class="rule-item">
            2.{{
              utils.storeName
            }}提醒您，为了保证您的利益，请您在收到货物时务必验货，请拆开包装箱，确认货品完好无损、准确无误后再签收；
          </p>
          <p class="rule-item">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;如果发现货物在运输途中有包装破损，或验货后发现商品质量、数量有问题的，您有权拒收。请拍照取证，并在快递单上写明拒收原因，再请快递人员退回；
          </p>
          <p class="rule-item bottom10">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;同时请您与商家进行联系，提供证据、说明原因来完成退货/换货。如果商家不配合，您可以致电商城进行咨询或投诉。
          </p>

          <p class="rule-item">
            3. 如果您申请退货，订单中的积分支付部分会返回至您的积分余额中，现金支付部分会按原支付方式返回。
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
import { orderGet } from '../../api/order'
import { ENUM } from '../../const/enum'
export default {
  data() {
    return {
      orderDetail: {},
      total: '',
      transaction_id: '',
      token_type: 0,
      surplus_paid: 0,
      payment: '',
      money_paid: 0,
      pay_detail: {}, //好友代付详情
      service_tel: ENUM.SERVICE.MASTER_TEL
    }
  },
  created() {
    let id = this.$route.query.id ? this.$route.query.id : null
    this.orderInfo(id)
  },
  computed: {
    nickname() {
      return this.orderDetail.share_detail[0].nickname
    }
  },
  methods: {
    // 获取订单详情数据
    orderInfo(id) {
      orderGet(id).then(res => {
        // 只取 share_sn、total、payment、hhpay、share_detail
        this.orderDetail = res
        this.total = res.total
        this.transaction_id = res.payment.transaction_id
        this.payment = res.payment ? res.payment.name : ''
        this.addDingDan(this.payment)
        this.token_type = res.hhpay.token_type
        this.surplus_paid = res.hhpay.surplus_paid ? res.hhpay.surplus_paid : 0
        this.money_paid = res.hhpay.money_paid ? res.hhpay.money_paid : 0
      })
    },
    goBack() {
      this.$_goBack()
    },
    // 添加订单
    addDingDan(value) {
      if (value == '支付宝' || value == '微信') {
        this.payment = this.payment + '订单'
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  background: #fff;
}
.header {
  @include header;
  position: fixed;
  border-bottom: 1px solid #d3d3d3;
  position: fixed;
  width: 100%;
  height: 50px;
  top: 0;
  z-index: 101;
}
.detail-container {
  padding: 73px 11px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  .title {
    font-size: 14px;
    line-height: 20px;
    color: #999;
    margin-bottom: 11px;
  }
  .total {
    height: 26px;
    color: $formInputColor;
    font-weight: bold;
    margin-bottom: 29px;
    display: flex;
    .surplus {
      display: flex;
      align-items: center;
      img {
        width: 19px;
        height: 19px;
        margin-right: 2px;
      }
      label {
        font-size: 28px;
        line-height: 32px;
      }
    }
    .cash {
      display: flex;
      justify-content: center;
      align-items: center;
      .add {
        font-size: 18px;
        line-height: 21px;
      }
      .cash-icon {
        font-size: 17px;
        line-height: 24px;
        padding-top: 7px;
      }
      .cash-num {
        font-size: 28px;
        line-height: 32px;
      }
    }
  }
  .pay-detail,
  .rules-wrappers {
    width: 100%;
    padding-bottom: 25px;
    .head {
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      .line {
        width: 132px;
        height: 1px;
        background: $lineColor;
      }
      .title {
        font-size: 14px;
        color: #999;
        line-height: 20px;
        margin: 0;
      }
    }
    .pay-info,
    .rule-item {
      font-size: 13px;
      color: #666;
      line-height: 18px;
      padding: 0 5px;
      & > div {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 9px;
        margin-bottom: 12px;
      }
    }
    .bottom10 {
      margin-bottom: 10px;
    }
  }
  .surplus-amount {
    display: flex;
    align-items: center;
    img {
      width: 16px;
      height: 16px;
      margin-right: 4px;
    }
  }
}
</style>
<style>
.icon {
  vertical-align: middle;
}
</style>
