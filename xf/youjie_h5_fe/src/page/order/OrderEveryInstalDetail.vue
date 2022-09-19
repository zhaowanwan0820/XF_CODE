<template>
  <div class="order-every-instal-detail">
    <div class="h-container">
      <mt-header class="header" title="详细信息">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      </mt-header>
    </div>
    <div class="infos-wrapper">
      <div class="up-header"></div>
      <div class="infos-content">
        <div class="infos-title-shadow"></div>
        <div class="infos-title">
          <label
            >第<span class="index">{{ $route.query.index }}</span
            >期</label
          >
          <span class="pay-status">{{ isPaid ? '已支付' : '已退款' }}</span>
        </div>
        <div class="infos-header">
          <div class="total-pay-infos">
            <span class="price-unit">￥</span>
            <span class="price-total">{{ utils.formatFloat(instalDetailInfo.total) }}</span>
            <span class="due-day" v-if="instalDetailInfo.due_day > 0">逾期{{ instalDetailInfo.due_day }}天</span>
          </div>
          <div class="respective-pay-infos">
            <span>现金支付: {{ utils.formatFloat(instalDetailInfo.real_money_paid) }}</span>
            <span>积分支付: {{ utils.formatFloat(instalDetailInfo.real_surplus) }}</span>
          </div>
        </div>
        <div class="infos-item">
          <v-instal
            title="应付时间："
            :subTitle="utils.formatDate('YYYY-MM-DD HH:mm:ss', instalDetailInfo.need_pay_time)"
          ></v-instal>
          <v-instal
            title="支付时间："
            :subTitle="utils.formatDate('YYYY-MM-DD HH:mm:ss', instalDetailInfo.real_pay_time)"
          ></v-instal>
          <v-instal title="支付方式：" :subTitle="instalDetailInfo.pay_name"></v-instal>
          <v-instal title="订单编号：" :subTitle="instalDetailInfo.sn"></v-instal>
        </div>
        <div class="infos-item" v-if="!isPaid">
          <div class="line"></div>
          <v-instal
            title="退款金额："
            :subTitle="
              `现金: ￥${utils.formatFloat(instalDetailInfo.cash_back)}\xa0\xa0\xa0\xa0
							积分抵扣: ${utils.formatFloat(instalDetailInfo.surplus_back)}`
            "
          ></v-instal>
          <v-instal
            title="退款时间："
            :subTitle="utils.formatDate('YYYY-MM-DD HH:mm:ss', instalDetailInfo.refund_time)"
          ></v-instal>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getOrderEveryInstalmentInfo } from '../../api/order'
import OrderInstalmentItem from './child/OrderInstalmentItem'
import { Indicator } from 'mint-ui'
export default {
  name: 'orderEveryInstalDetail',
  data() {
    return {
      instalDetailInfo: {}
    }
  },
  components: {
    'v-instal': OrderInstalmentItem
  },
  created() {
    Indicator.open()
    getOrderEveryInstalmentInfo(this.$route.query.id).then(res => {
      this.instalDetailInfo = res
      Indicator.close()
    })
  },
  computed: {
    isPaid() {
      return this.$route.query.status === 1
    }
  },
  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.h-container {
  width: 375px;
  height: 114px;
  background: rgba(57, 62, 80, 1);
}
.infos-wrapper {
  display: flex;
  align-items: center;
  flex-direction: column;
  .up-header {
    width: 345px;
    height: 10px;
    background: #1c1c2f;
    border-radius: 5px;
    position: relative;
    top: -35px;
  }
  .infos-content {
    position: relative;
    top: -40px;
    width: 335px;
    background: #fff;
    box-shadow: 0px 0px 9px 0px rgba(57, 62, 80, 0.18);
    border-radius: 0px 0px 5px 5px;
    .infos-title-shadow {
      position: absolute;
      z-index: 10;
      width: 100%;
      height: 10px;
      background: linear-gradient(rgba(57, 62, 80, 0.56), rgba(247, 247, 247, 0));
    }
    .infos-title {
      height: 56px;
      background: #f7f7f7;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 400;
      label {
        height: 33px;
        font-size: 16px;
        color: rgba(57, 62, 80, 1);
        line-height: 22px;
        letter-spacing: 6px;
        .index {
          font-size: 24px;
        }
      }
      .pay-status {
        position: absolute;
        right: 23px;
        height: 20px;
        font-size: 14px;
        color: rgba(153, 153, 153, 1);
        line-height: 20px;
      }
    }
    .infos-header {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      padding: 27px 0 23px;
      box-sizing: border-box;
      height: 140px;
      border-bottom: 1px dotted rgba(85, 46, 32, 0.2);
      .total-pay-infos {
        color: rgba(64, 64, 64, 1);
        font-weight: bold;
        .price-unit {
          height: 28px;
          font-size: 24px;
          line-height: 28px;
        }
        .price-total {
          height: 51px;
          font-size: 44px;
          line-height: 51px;
        }
        .due-day {
          background-image: url('../../assets/image/hh-icon/c0-instalment/due-day.png');
          background-size: 100% 100%;
          height: 22px;
          display: inline-block;
          padding: 3px 6px 0px 9px;
          position: relative;
          top: -24px;
          left: 2px;
          font-size: 12px;
          font-weight: 400;
          color: #fff;
        }
      }
      .respective-pay-infos {
        font-size: 16px;
        font-weight: 400;
        color: #999;
        line-height: 22px;
        & > span:last-child {
          margin-left: 24px;
        }
      }
    }
    .infos-item {
      padding: 5px 0 26px;
      .line {
        width: 295px;
        margin-left: 20px;
        border-bottom: 1px dotted rgba(85, 46, 32, 0.2);
      }
    }
  }
}
</style>
