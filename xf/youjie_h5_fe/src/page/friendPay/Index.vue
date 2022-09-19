<template>
  <div class="page-wrapper clearfix">
    <p class="originator">申请人：{{ maskNickName }}</p>
    <div class="pay-detail" v-if="status == 0">
      <span class="label">代付积分</span>
      <div class="money-area border-bottom">
        <div class="money">
          <img src="../../assets/image/hh-icon/b0-home/money-icon.png" />
          <span class="num txt">{{ productHb }}</span>
        </div>
        <div class="pay-countDown">
          <span class="txt">剩余支付时间</span>
          <div class="countDown">
            <span class="num hour">{{ hour }}</span>
            <span class="mh">:</span>
            <span class="num minute">{{ minute }}</span>
            <span class="mh">:</span>
            <span class="num second">{{ second }}</span>
          </div>
        </div>
      </div>
      <div class="product-area border-bottom">
        <div class="img-wrapper">
          <img src="../../assets/image/change-icon/default_image_02@2x.png" v-if="!productImg" />
          <img :src="productImg" v-if="productImg" />
        </div>
        <div class="desc-wrapper">
          <div class="name">
            <p>{{ productName }}</p>
          </div>
          <div class="sizeAndNum">
            <span class="size">{{ productSize }}</span>
            <span class="num">x{{ productNum }}</span>
          </div>
          <div class="price">
            <span class="unit">￥</span>
            <span class="num">{{ productRmb }}</span>
            <span class="plus">+</span>
            <img class="hb-icon" src="../../assets/image/hh-icon/b0-home/money-icon.png" />
            <span class="num">{{ productHb }}</span>
          </div>
        </div>
      </div>
      <div class="btn-pay">
        <button @click="toConfirm">{{ btnTxt }}</button>
      </div>
    </div>

    <div class="pay-detail expire" v-if="status == 5">
      <span class="label">代付积分</span>
      <div class="money-area border-bottom">
        <div class="money">
          <span class="txt">【订单已失效】</span>
        </div>
        <div class="pay-countDown">
          <span class="txt">剩余支付时间</span>
          <div class="countDown">
            <span class="num hour">00</span>
            <span class="mh">:</span>
            <span class="num minute">00</span>
            <span class="mh">:</span>
            <span class="num second">00</span>
          </div>
        </div>
      </div>
      <div class="to-notify-friend">
        <span>通知好友重新发一次吧</span>
      </div>
    </div>

    <div class="pay-detail expire" v-if="status == 4">
      <span class="label">代付积分</span>
      <div class="money-area border-bottom">
        <div class="money">
          <span class="txt">【订单已完成】</span>
        </div>
        <div class="pay-countDown">
          <span class="txt">剩余支付时间</span>
          <div class="countDown">
            <span class="num hour">00</span>
            <span class="mh">:</span>
            <span class="num minute">00</span>
            <span class="mh">:</span>
            <span class="num second">00</span>
          </div>
        </div>
      </div>
      <div class="to-notify-friend">
        <span>通知好友再发一单吧</span>
      </div>
    </div>

    <div class="pay-notice">
      <dl>
        <dt>代付注意事项</dt>
        <dd>1.付款前务必和好友再次确认订单及账号信息，谨防上当受骗而导致的财产损失；</dd>
        <dd>
          2.如果该笔订单因申请人取消、支付超时取消或发生退货/售后服务，订单已支付的各个部分将会按照原支付方式返回；
        </dd>
        <dd>
          3.您可以在{{ utils.storeName }}APP，【我的】-【{{
            utils.mlmUserName
          }}】-【好友代付】列表中，查看全部帮助好友代付的订单。未参与代付的订单不会展示。
        </dd>
      </dl>
    </div>

    <!-- 状态为待支付 -->
    <count-down
      v-if="status == 0 && timeEnd > 0"
      :endTime="timeEnd"
      v-on:time-change="timeChange"
      v-on:time-end="timeEndEvent"
    ></count-down>
  </div>
</template>
<script>
import CountDown from '../../components/common/CountDown'

import { Toast, Indicator } from 'mint-ui'
import { mapState, mapMutations, mapActions } from 'vuex'
import { friendPayOrderGet } from '../../api/friendPay'

export default {
  data() {
    return {
      id: this.$route.params.id,
      nickName: '-',

      timeEnd: 0,

      hour: '--',
      minute: '--',
      second: '--',

      productName: '',
      productImg: '',

      productSize: '',
      productNum: 0,
      productRmb: 0,
      productHb: 0,

      btnTxt: `登录${this.utils.storeNameForShort} 豪爽支付`,

      status: 0 // 0 待支付 4 已完成 5 已取消（已过期）
    }
  },
  components: {
    CountDown
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline
    }),
    maskNickName() {
      return this.nickName
    }
  },
  created() {
    // 获取订单详情
    if (!this.id) {
      Toast('订单Id异常')
      return
    }

    this.getOrderDetail()

    if (this.isOnline) {
      this.btnTxt = '豪爽支付'
    }
  },
  methods: {
    getOrderDetail() {
      Indicator.open()

      friendPayOrderGet(this.id)
        .then(
          res => {
            this.status = res.status || 0
            this.nickName = res.nickname
            this.timeEnd = res.canceled_at * 1000
            this.productName = res.goods[0].name
            this.productImg = res.goods[0].thumb
            this.productSize = res.goods[0].property
            this.productNum = res.goods[0].total_amount
            this.productRmb = res.hhpay.money_paid
            this.productHb = res.order_amount
          },
          error => {
            Toast('获取订单详情异常，错误信息：' + error)
          }
        )
        .finally(() => {
          Indicator.close()
        })
    },
    toConfirm() {
      if (this.isOnline) {
        this.$router.push({ name: 'friendPayConfirm', params: { id: this.id } })
      } else {
        this.$router.push({ name: 'login', query: { redirect: `/friendPayConfirm/${this.id}` } })
      }
    },
    timeChange(time) {
      this.hour = time.h
      this.minute = time.m
      this.second = time.s
    },
    timeEndEvent() {
      this.status = 5
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
.pay-detail {
  position: relative;
  background: rgba(255, 255, 255, 1);
  border-radius: 8px;

  .label {
    position: absolute;
    top: 12px;
    left: 13px;
  }

  .border-bottom {
    position: relative;

    &:before {
      content: '';
      position: absolute;
      border-bottom: 1px dotted rgba(85, 46, 32, 0.3);
      width: 100%;
      height: 1px;
      bottom: 0;
      left: 0;
      transform: scaleY(0.5);
    }
  }

  .money-area {
    margin: 0 15px;
    padding-top: 41px;
    padding-bottom: 21px;

    .money {
      display: flex;
      justify-content: center;
      align-items: center;

      img {
        width: 18px;
      }
      .num {
        font-size: 38px;
        font-weight: bold;
        color: rgba(119, 37, 8, 1);
        line-height: 38px;
        margin-left: 6px;
      }
    }
    .pay-countDown {
      margin-top: 17px;
      text-align: center;
      font-size: 0;

      .txt {
        font-size: 12px;
        font-weight: 400;
        color: rgba(119, 37, 8, 1);
        line-height: 17px;
        margin-right: 7px;
        display: inline-block;
      }
      .countDown {
        display: inline-block;
        font-size: 0;

        span {
          display: inline-block;
          line-height: 16px;
          font-size: 12px;
          text-align: center;
        }
        .num {
          background: rgba(119, 37, 8, 1);
          border-radius: 2px;
          color: #fff;
          width: 18px;
          text-align: center;
        }
        .mh {
          padding: 0 2px;
          font-weight: bold;
        }
      }
    }
  }

  .product-area {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 15px;

    .img-wrapper {
      width: 85px;
      height: 85px;

      img {
        width: 100%;
      }
    }
    .desc-wrapper {
      flex-grow: 1;
      width: 1px;
      padding: 15px 0 15px 12px;

      .name {
        max-height: 32px;
        overflow: hidden;

        p {
          max-height: 48px;
          -webkit-line-clamp: 2;
          font-size: 13px;
          color: rgba(64, 64, 64, 1);
          line-height: 16px;
        }
      }

      .sizeAndNum {
        font-size: 12px;
        font-weight: 300;
        line-height: 16px;
        margin-top: 5px;

        .num {
          margin-left: 4px;
        }
      }

      .price {
        font-size: 0;
        margin-top: 14px;

        .unit {
          font-size: 9px;
          font-weight: 600;
          color: rgba(64, 64, 64, 1);
        }
        .num {
          font-size: 14px;
          font-weight: bold;
          color: rgba(64, 64, 64, 1);
        }
        .plus {
          font-size: 10px;
          font-weight: bold;
          color: rgba(64, 64, 64, 1);
          margin: 0 3px;
        }
        .hb-icon {
          width: 12px;
          vertical-align: -1px;
        }
      }
    }
  }
  .btn-pay {
    padding: 25px 9px;

    button {
      display: block;
      width: 100%;
      height: 46px;
      line-height: 46px;
      background: rgba(119, 37, 8, 1);
      border-radius: 2px;
      font-size: 18px;
      font-weight: 400;
      color: rgba(255, 255, 255, 1);
    }
  }
}
.pay-notice {
  padding: 20px 2px 49px 8px;
  font-size: 12px;
  font-weight: 300;
  color: rgba(153, 153, 153, 1);
  line-height: 17px;

  dt {
    font-size: 13px;
    font-weight: 400;
    color: rgba(102, 102, 102, 1);
    line-height: 18px;
  }
  dd {
    margin-left: 0;
    margin-top: 9px;
  }
}
.originator {
  font-size: 13px;
  font-weight: 400;
  color: rgba(64, 64, 64, 1);
  line-height: 18px;
  padding: 17px 0 16px 5px;
}

.pay-detail.expire {
  .money-area {
    padding-top: 46px;

    .money {
      color: rgba(155, 33, 11, 1);
    }
  }
  .pay-countDown {
    .txt {
      color: rgba(198, 198, 198, 1);
    }
    .countDown {
      .num {
        background: rgba(64, 64, 64, 1);
        opacity: 0.1966;
      }
      .mh {
        color: rgba(218, 218, 218, 1);
      }
    }
  }
  .to-notify-friend {
    display: flex;
    align-items: center;
    justify-content: center;

    padding: 40px 0;

    span {
      font-size: 16px;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
      line-height: 22px;
    }
  }
}
</style>
