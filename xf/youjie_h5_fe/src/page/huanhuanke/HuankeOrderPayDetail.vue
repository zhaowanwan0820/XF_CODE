<template>
  <div class="container">
    <mt-header class="header" title="支付明细">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="detail-container">
      <div class="detail-top-wrapper">
        <div class="detail-top">
          <p class="title">总支付</p>
          <div class="total">
            <div class="surplus">
              <img src="../../assets/image/hh-icon/mlm/icon-surplus.png" alt />
              <label>{{ do_surplus }}</label>
            </div>
            <!-- <div class="cash">
              <label class="add">+</label>
              <label class="cash-icon">￥</label>
              <label class="cash-num">{{ utils.formatFloat(order.total) }}</label>
            </div> -->
          </div>
        </div>
        <div class="pay-detail">
          <div class="head bt-line">
            <p class="title">支付明细</p>
          </div>
          <div class="pay-info">
            <!-- <div class="pay-item bt-line">
              <div class="left">
                <div class="title">买家支付现金</div>
                <div class="nickname">{{ order.nickname }}</div>
              </div>
              <div class="right">
                <div class="amount">￥ {{ rebate }}</div>
              </div>
            </div> -->
            <div class="pay-item">
              <div class="title">积分支付</div>
              <div class="amount">
                <img src="../../assets/image/hh-icon/mlm/icon-surplus.png" />
                {{ do_surplus }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="rules-wrappers">
        <p class="title">规则明细</p>
        <div>
          <p class="rule-item">
            1.订单代付完成后，您可以通过【我的】-【{{ utils.mlmUserName }}】查询订单、佣金、变现金额等相关信息。
          </p>
          <p class="rule-item">
            2.{{ utils.storeName }}提醒各位{{
              utils.mlmUserName
            }}和买家，为了保证各位买家的利益，请买家(收货人)在收到货物时务必验货，请拆开包装箱，确认货品完好无损、准确无误后再签收；
          </p>
          <p class="rule-item">
            &nbsp;&nbsp;&nbsp;&nbsp;如果发现货物在运输途中有包装破损，或验货后发现商品质量、数量有问题的，买家(收货人)有权拒收。请拍照取证，并在快递单上写明拒收原因，再请快递人员退回；
          </p>
          <p class="rule-item">
            &nbsp;&nbsp;&nbsp;&nbsp;同时请买家与商家进行联系，提供证据、说明原因来完成退货/换货。如果商家不配合，买家可致电商城服务热线：{{
              service_tel
            }}
            进行咨询或投诉。
          </p>
          <p class="rule-item">
            3.如果买家申请(部分)退货/款，订单中买家支付的现金会(按比例)以买家原支付方式返回，{{
              utils.mlmUserName
            }}账户中冻结的佣金、积分变现金额也将会(按比例)扣除，{{
              utils.mlmUserName
            }}代付的积分部分会(按比例)返回至积分余额中。
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
import { huanOrderGet } from '../../api/huanhuanke'
import { ENUM } from '../../const/enum'
export default {
  data() {
    return {
      order: {},
      service_tel: ENUM.SERVICE.MASTER_TEL
    }
  },
  created() {
    let id = this.$route.query.id ? this.$route.query.id : null
    this.orderInfo(id)
  },
  computed: {
    //好友支付(佣金)
    rebate() {
      return this.utils.formatFloat(this.order.total)
    },
    do_surplus() {
      return this.utils.formatFloat(this.order.do_surplus)
    }
  },
  methods: {
    // 获取订单详情数据
    orderInfo(id) {
      huanOrderGet(id).then(
        res => {
          this.order = res
        },
        error => {
          console.log(error)
        }
      )
    },
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
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
  padding-top: 50px;
  display: flex;
  flex-direction: column;
  align-items: center;
  .detail-top-wrapper {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    background: url('../../assets/image/hh-icon/mlm/bg-order.png') #fff no-repeat;
    background-size: 375px 108px;
    padding: 16px 0 7px;
  }
  .detail-top {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 345px;
    height: 132px;
    background: url('../../assets/image/hh-icon/mlm/bg-paydetail.png') no-repeat;
    background-size: 345px 132px;
    padding-bottom: 34px;
    .title {
      font-size: 18px;
      line-height: 25px;
      color: #404040;
      margin: 20px 0 18px;
    }
    .total {
      height: 26px;
      color: $formInputColor;
      font-weight: 500;
      margin-bottom: 29px;
      display: flex;
      .surplus {
        display: flex;
        align-items: center;
        margin-right: 6px;
        img {
          width: 20px;
          height: 20px;
          margin-right: 6px;
        }
        label {
          font-size: 28px;
          line-height: 40px;
        }
      }
      .cash {
        display: flex;
        justify-content: center;
        align-items: center;
        .add {
          font-size: 23px;
          line-height: 32px;
          font-weight: 600;
          margin-right: 5px;
        }
        .cash-icon {
          font-size: 26px;
          line-height: 32px;
          padding-top: 3px;
          font-weight: 600;
        }
        .cash-num {
          font-size: 28px;
          line-height: 40px;
        }
      }
    }
  }
  .pay-detail {
    width: 100%;
    margin-bottom: 10px;
    .bt-line {
      @include thin-border(#f4f4f4, 15px);
    }
    .head {
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
      padding-left: 15px;
      margin-bottom: 9px;
      .title {
        font-size: 16px;
        color: #404040;
        line-height: 22px;
        padding-bottom: 9px;
        margin: 0;
      }
    }
    .pay-info {
      padding: 0 15px;
    }
    .pay-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      color: #888;
      .title {
        font-size: 14px;
        line-height: 20px;
      }
      .nickname {
        @include sc(11px, #888, left);
        margin-top: 3px;
        line-height: 16px;
        opacity: 0.5;
      }
      .amount {
        font-size: 14px;
        line-height: 20px;
        img {
          width: 11px;
          height: 11px;
        }
      }
    }
  }
  .rules-wrappers {
    background-color: #fff;
    margin-top: 10px;
    padding: 15px;
    .title {
      font-size: 16px;
      font-weight: 400;
      color: #404040;
      line-height: 22px;
      padding-bottom: 14px;
    }
    .rule-item {
      font-size: 12px;
      font-weight: 300;
      color: #666;
      line-height: 17px;
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
