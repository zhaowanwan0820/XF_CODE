<template>
  <div class="order-item-container" @click="goDetail">
    <div class="title">
      <van-row>
        <van-col :span="12" class="project-name text-show">{{ info.name }}</van-col>
        <van-col :span="12" class="order-status">{{ orderStatus }}</van-col>
      </van-row>
    </div>
    <div class="info">
      <!-- 待还本金 -->
      <div class="wait-money">
        <span class="num">{{ utils.formatMoney(info.wait_capital) }}</span>
        <p class="name">待还本金</p>
      </div>
      <!-- 转让折扣 -->
      <div class="transfer-discount">
        <span class="num">{{ info.discount }}</span>
        <p class="name">转让折扣</p>
      </div>
      <!-- 转让价格 -->
      <div class="transfer-price">
        <span class="num">{{ utils.formatMoney(info.transferprice) }}</span>
        <p class="name">转让价格</p>
      </div>
    </div>
    <div class="time-operate" v-if="s_waitpay || s_waitconfirm || s_success">
      <div class="expired-time" v-if="s_waitpay || s_waitconfirm">
        剩余 <van-count-down :time="info.endtime * 1e3" format="HH时mm分ss秒" />
      </div>
      <div class="empty"><!-- 占位 --></div>
      <div class="btn-box">
        <van-button size="small" plain type="primary" @click.stop="cancelOrder" v-if="s_waitpay">取消订单</van-button>
        <van-button size="small" type="primary" @click.stop="goPay" v-if="s_waitpay">转账付款</van-button>
        <van-button
          class="big-btn"
          size="small"
          plain
          type="primary"
          @click.stop="viewDebt"
          v-if="s_success"
          :disabled="contract_failed"
          >{{ contract_btn_text }}</van-button
        >
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
        name: 'subscribeDetail',
        params: { id: 1 },
        query: { products: this.products, debt_id: this.info.debt_tender_id }
      })
    },
    cancelOrder() {
      this.$dialog
        .confirm({
          message: '是否取消该订单？',
          confirmButtonText: '是',
          cancelButtonText: '否'
        })
        .then(() => {
          cancelOrder(this.params).then(res => {
            if (res.code == 0) {
              this.$toast('操作成功')
              this.$emit('refresh')
            } else {
              this.$toast(res.info)
            }
          })
        })
        .catch(() => {})
    },
    goPay() {
      this.$router.push({ name: 'transferpayments', query: { products: this.products, id: this.info.debt_tender_id } })
    },
    viewDebt() {
      if (this.info.remark_status == 2) {
        if (this.yjApp.getAppVersion() == undefined) {
          window.open(this.info.oss_download, '_blank')
        } else {
          this.yjApp.openAppPage(this.info.oss_download)
        }
        // this.$emit('func', this.info.oss_download)
      } else {
        this.$dialog.alert({
          message:
            '合同生成失败，请联系客服\n<a style="margin-top: 10px;display: inline-block;" href="tel:010-89929006">010-89929006</a>',
          confirmButtonText: '我知道了'
        })
      }
    }
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
