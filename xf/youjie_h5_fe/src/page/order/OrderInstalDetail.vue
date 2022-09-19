<template>
  <div class="order-instal-detail">
    <div class="h-container">
      <mt-header class="header" title="分期明细">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      </mt-header>
    </div>
    <div class="order-instal-list">
      <div class="instal-item-wrapper" v-for="(item, index) in instalmentList" :key="item.id">
        <div :class="{ 'index-not-pay': item.instalment_status === 0 }" class="draw">
          <template v-if="index === 0"
            >首期</template
          >
          <template v-else
            >第<span class="index">{{ index + 1 }}</span
            >期</template
          >
        </div>
        <p class="total-price-wrapper" :class="{ 'word-color': item.instalment_status === 0 }">
          <span class="price-unit">￥</span><span class="price-num">{{ utils.formatFloat(item.total) }}</span>
          <span class="due-day" :class="{ 'bg-img': item.instalment_status !== 0 }" v-if="item.due_day > 0"
            >逾期{{ item.due_day }}天</span
          >
          <button
            class="paying"
            v-if="item.instalment_status === 0 && item.need_pay_time === minPayTime"
            @click="payment(item)"
          >
            {{ fmtStatus(item) }}
          </button>
          <button class="waiting" v-else-if="item.instalment_status === 0" disabled>{{ fmtStatus(item) }}</button>
          <button class="cancel" v-else-if="item.instalment_status === 2">
            {{ fmtStatus(item) }}
          </button>
          <button
            class="paid-refund"
            v-else-if="[1, 3].indexOf(item.instalment_status) > -1"
            @click="goDetailInfo(item.id, index, item.instalment_status)"
          >
            {{ fmtStatus(item) }}
          </button>
          <img
            v-if="[1, 3].indexOf(item.instalment_status) > -1"
            src="../../assets/image/hh-icon/supplier/icon-tip.png"
            alt=""
          />
        </p>
        <p class="respective-price-wrapper">
          <span>现金支付&nbsp;&nbsp;{{ utils.formatFloat(item.money_paid) }}</span>
          <span>积分抵扣&nbsp;&nbsp;{{ utils.formatFloat(item.surplus) }}</span>
        </p>
        <p class="pay-time-wrapper" :class="{ 'not-pay': item.instalment_status === 0 }">
          应付时间：
          {{
            index === 0
              ? utils.formatDate('YYYY.MM.DD HH:mm:ss', item.need_pay_time)
              : utils.formatDate('YYYY.MM.DD', item.need_pay_time)
          }}
        </p>
      </div>
    </div>
    <div
      class="pay-all"
      @click="payAll"
      v-if="instalmentList.length && instalmentList[0].instalment_status == 1 && this.$route.query.pay_status != 2"
    >
      全部支付
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import { Header, Indicator } from 'mint-ui'
import { getOrderInstalmentList } from '../../api/order'
import { INSTALMENTSTATUS } from './static'
export default {
  name: 'orderInstalDetail',
  data() {
    return {
      instalmentList: [],
      minPayTime: 0
    }
  },
  created() {
    this.getOrderInstalment()
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    getOrderInstalment() {
      Indicator.open()
      getOrderInstalmentList(this.$route.query.id).then(res => {
        this.instalmentList = res
        Indicator.close()
        // 待支付状态中，时间最小的，置为支付
        this.minPayTime = this.instalmentList
          .map(item => {
            if (item.instalment_status === 0) {
              return item.need_pay_time
            }
          })
          .sort((a, b) => a - b)[0]
      })
    },
    goDetailInfo(id, index, status) {
      this.$router.push({ name: 'orderEveryInstalDetail', query: { id: id, index: index + 1, status: status } })
    },
    payment(item) {
      this.$router.push({
        name: 'paymentHuan',
        query: {
          order: item.id,
          parent_order: '',
          total: item.total,
          isInstalment: 1
        }
      })
    },
    payAll() {
      // 支付剩余全部
      this.$router.push({
        name: 'paymentHuan',
        query: {
          order: '',
          parent_order: this.$route.query.id,
          total: '',
          isInstalment: 1
        }
      })
    },
    fmtStatus(temp) {
      let s = ''
      INSTALMENTSTATUS.forEach(item => {
        if (item.id === temp.instalment_status) {
          s = item.name
        }
      })
      if (temp.need_pay_time === this.minPayTime) {
        s = '支付'
      }
      return s
    }
  }
}
</script>

<style lang="scss" scoped>
.order-instal-detail {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #fff;
}
.order-instal-list {
  border-top: 1px solid #f4f4f4;
  height: 100%;
  overflow: scroll;
  padding: 0 15px;
  .instal-item-wrapper {
    position: relative;
    width: 345px;
    height: 122px;
    margin: 18px 0;
    background: #fff;
    box-shadow: 0px 0px 8px 0px rgba(0, 0, 0, 0.07);
    border-radius: 2px;
    .draw {
      width: 44px;
      height: 0;
      position: absolute;
      top: -15px;
      left: -40px;
      border-bottom: 20px solid #e5e5e5;
      border-right: 20px solid transparent;
      border-left: 20px solid transparent;
      text-align: center;
      transform-origin: 100% 0%;
      transform: rotate(-45deg);
      font-size: 12px;
      font-weight: 400;
      line-height: 17px;
      color: #fff;
      .index {
        font-size: 16px;
      }
    }
    p {
      padding-left: 42px;
      width: 100%;
      box-sizing: border-box;
      color: #999;
      font-size: 12px;
    }
    .total-price-wrapper {
      display: flex;
      align-items: center;
      position: absolute;
      bottom: 77px;
      font-size: 14px;
      .price-unit {
        position: relative;
        top: 4px;
      }
      .price-num {
        font-size: 24px;
        font-weight: bold;
      }
      .due-day {
        background-image: url('../../assets/image/hh-icon/c0-instalment/due-day.png');
        background-size: 100% 100%;
        height: 22px;
        display: inline-block;
        padding: 3px 6px 0px 9px;
        position: relative;
        top: -3px;
        left: 5px;
        font-size: 12px;
        font-weight: 400;
        color: #fff;
      }
    }
    .respective-price-wrapper {
      position: absolute;
      bottom: 44px;
      height: 17px;
      font-weight: 400;
      line-height: 17px;
      & > span:last-child {
        margin-left: 14px;
      }
    }
    .pay-time-wrapper {
      position: absolute;
      bottom: 0;
      height: 35px;
      line-height: 35px;
      font-weight: 400;
    }
    .not-pay {
      color: #772508;
      background: rgba(119, 37, 8, 0.0247);
      border-radius: 0px 0px 2px 2px;
    }
  }
  button {
    position: absolute;
    right: 18px;
    width: 75px;
    height: 30px;
    font-size: 13px;
    border-radius: 2px;
    line-height: 30px;
  }
  img {
    position: absolute;
    right: 18px;
    width: 3px;
    height: 5px;
  }
  .paying {
    background: #772508;
    color: #fff;
  }
  .waiting {
    background: rgba(119, 37, 8, 0.3);
    color: #fff;
  }
  .cancel {
    background: none;
    color: #999;
  }
  .paid-refund {
    background: none;
    color: #552e20;
    text-indent: 20px;
  }
  .index-not-pay {
    border-bottom: 20px solid #f0ddb5 !important;
    color: rgba(64, 64, 64, 1) !important;
  }
  .word-color {
    color: #404040 !important;
  }
  .bg-img {
    background-image: url('../../assets/image/hh-icon/c0-instalment/due-day-over.png') !important;
    color: #999 !important;
  }
}
.pay-all {
  margin-top: 18px;
  width: 100%;
  height: 50px;
  line-height: 50px;
  text-align: center;
  background: #772508;
  color: #fff;
  font-size: 16px;
  font-weight: 400;
}
</style>
