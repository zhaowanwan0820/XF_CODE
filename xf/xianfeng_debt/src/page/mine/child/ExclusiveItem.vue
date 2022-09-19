<template>
  <div class="order-item-container" @click="goDetail">
    <div class="title">
      <van-row>
        <van-col :span="12" class="project-name text-show">普惠</van-col>
        <van-col :span="12" class="order-status">{{ info.purchase_status }}</van-col>
      </van-row>
    </div>
    <div class="info">
      <!-- 待还本金 -->
      <div class="wait-money">
        <span class="num">{{ utils.formatMoney(info.wait_capital) }}</span>
        <p class="name">在途债权合计</p>
      </div>
      <!-- 转让折扣 -->
      <div class="transfer-discount">
        <span class="num">{{ info.discount }}</span>
        <p class="name">转让折扣</p>
      </div>
      <!-- 转让价格 -->
      <div class="transfer-price">
        <span class="num">{{ utils.formatMoney(info.purchase_amount) }}</span>
        <p class="name">收购金额</p>
      </div>
    </div>
 
  </div>
</template>

<script>
import { cancelOrder } from '../../../api/order'
import { SUBSCRIPTION_STATUS } from '../const'
export default {
  name: 'orderItem',
  props: ['info', 'products'],
  data() {
    return {
      SUBSCRIPTION_STATUS,
      params: {
        products: this.products,
        debt_tender_id: this.info.debt_tender_id
      },
      PDFshow: false,
      embedSrc: ''
    }
  },
  computed: {
    // 格式化状态
    orderStatus() {
      let _s = ''
      this.SUBSCRIPTION_STATUS.forEach(item => {
        if (item.id == this.info.status) {
          _s = item.name
        }
      })
      return _s
    },
    // 待付款
    s_waitpay() {
      return this.info.status == 1
    },
    // 待卖方确认
    s_waitconfirm() {
      return this.info.status == 6
    },
    // 交易成功
    s_success() {
      return this.info.status == 2
    },
    // 生成合同中
    contract_failed() {
      return this.info.remark_status == 1 || this.info.remark_status == ''
    },
    // 合同按钮文案
    contract_btn_text() {
      let s_name = ''
      if (this.contract_failed) {
        s_name = '合同生成中'
      } else {
        s_name = '债转合同'
      }
      return s_name
    }
  },
  created() {},
  methods: {
    goDetail() {
      // /subscribeDetail/:id?products=products&debt_id=debt_id  1->认购，2->转让
      this.$router.push({
        name: 'exclusiveDetails',
        query: { id: this.info.id }
      })
    },
    
  }
}
</script>

<style lang="less" scoped>
.order-item-container {
  a {
    color: #075db3;
    margin-top: 10px;
    display: block;
  }
  background: #fff;
  margin-top: 10px;
  padding-left: 15px;
  .title {
    padding: 10px 15px 9px 0;
    height: 20px;
    font-size: 14px;
    font-weight: bold;
    color: #888;
    line-height: 20px;
    border-bottom: 1px solid #f4f4f4;
    .order-status {
      text-align: right;
      color: @themeColor;
      font-weight: 400;
    }
  }
  .info {
    margin-top: 14px;
    padding: 0 15px 10px 0;
    display: flex;
    align-items: stretch;

    .num {
      display: block;
      font-size: 18px;
      font-weight: bold;
      color: rgba(64, 64, 64, 1);
      line-height: 25px;
    }
    .name {
      font-size: 13px;
      font-weight: 400;
      color: rgba(153, 153, 153, 1);
      line-height: 18px;
      margin-top: 2px;
    }
    .wait-money {
      width: 133px;
      flex-basis: 133px;
    }
    .transfer-discount {
      width: 77px;
      flex-basis: 77px;
      text-align: center;
    }
    .transfer-price {
      flex: 1 0 0;
      text-align: right;
    }
  }
  .time-operate {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 60px;
    padding-right: 15px;
    font-size: 14px;
    font-weight: 300;
    color: #404040;
    border-top: 1px solid #f4f4f4;
    .expired-time {
      display: flex;
      align-items: center;
    }
    /deep/ .van-count-down {
      font-size: 14px;
      margin-left: 10px;
    }
    .btn-box {
      /deep/ .van-button {
        margin-left: 15px;
        width: 70px;
      }
      .big-btn {
        width: 84px;
      }
    }
  }
  .text-show {
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    word-break: break-all;
  }
}
</style>
