<!-- Detailinfo.vue -->
<template>
  <div class="ui-detail-info" v-if="detailInfo">
    <div class="info-header ui-flex">
      <template v-if="seckillStatus === 0">
        <div class="price info-price">
          <span class="price-unit">￥</span>
          <span>{{ utils.formatFloat(detailInfo.current_price) }}</span>
        </div>
      </template>
      <template v-else>
        <div class="price info-price">
          <span class="price-unit">￥</span>
          <span>{{ utils.formatFloat(detailInfo.secbuy.cash_price) }}</span>
        </div>
        <label class="huan" v-if="detailInfo.secbuy.money_line > 0"
          ><span>积分抵扣￥{{ utils.formatFloat(detailInfo.secbuy.money_line) }}</span></label
        >
        <div class="discount-1" v-if="detailInfo.secbuy.discount < 10">
          <span>{{ utils.formatFloat(detailInfo.secbuy.discount) }}折</span>
        </div>
      </template>
    </div>

    <!-- 积分抵扣部分 -->
    <div class="hb-iscount" v-if="detailInfo.id">
      <div class="hb-discount-title">
        <template v-if="seckillStatus === 0">
          <span class="hb-price-desc">
            <span class="hb-price-desc-txt">
              {{ `￥${utils.formatFloat(detailInfo.cash_price)}` }}
            </span>
            <div class="quanyi-wrapper">
              <img src="../../../assets/image/hh-icon/l0-list-icon/debt-left-angle.png" />
              <div>
                <label class="debt-style">{{ `积分抵扣￥${utils.formatFloat(detailInfo.money_line)}` }}</label>
              </div>
            </div>
          </span>
        </template>

        <template v-else>
          <span class="un-line">￥{{ utils.formatFloat(detailInfo.price) }}</span>
        </template>
      </div>
      <div></div>
    </div>

    <!-- 商品名称 -->
    <div class="prod-name">{{ detailInfo.name }}</div>
    <!-- 商家 -->
    <info-suppliers></info-suppliers>

    <!-- 首单返现 -->
    <info-return-cash
      v-if="detailInfo.red_cash_back && (detailInfo.red_cash_back.name || detailInfo.red_cash_back.max_num > 0)"
      :returnCash="detailInfo.red_cash_back.max_num"
    ></info-return-cash>
  </div>
</template>

<script>
import { mapState, mapMutations, mapGetters } from 'vuex'
import InfoSuppliers from './InfoSuppliers'
import InfoReturnCash from '../../product-detail/child/InfoReturnCash'
import { ENUM } from '../../../const/enum'

export default {
  data() {
    return {
      s2: ENUM.SUPPLIERS_TYPE.COLLABORATOR,
      orderTime: '', //下单时间
      arrivalsTime: '', //到达时间
      arrivalsTitle: '', // 到达时间的标题
      arrivalsRange: '', //到达时间区间,
      isShowDesc: false // 商品简介是否显示更多
    }
  },

  components: {
    InfoSuppliers,
    InfoReturnCash
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo,
      currentProductId: state => state.detail.currentProductId,
      user: state => state.auth.user,
      seckillStatus: state => state.seckillList.seckillStatus
    })
  },

  created() {
    this.getCurrentDate()
  },

  methods: {
    ...mapMutations({
      changeIndex: 'changeIndex'
    }),

    /*
     * getCommentStatus： 去到评论页面
     */
    getCommentStatus() {
      this.changeIndex(2)
    },

    /*
        getCurrentDate: 获取当前时间
      */
    getCurrentDate() {
      let date = new Date()
      let month = date.getMonth() + 1,
        data = date.getDate(),
        hour = date.getHours() < 10 ? '0' + date.getHours() : date.getHours(),
        minute = date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes(),
        second = date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds()
      this.getTimeRange(hour, minute, month, data)
    },

    /*
        getTimeRange： 获取送货时间
        @params： hour：小时  minute： 分钟  month： 月份  data： 日期
      */
    getTimeRange(hour, minute, month, data) {
      let time = hour + '' + minute
      // 24:00 - 9:30
      if ((time >= 2400) & (time <= 930)) {
        this.orderTime = '9:30'
        this.arrivalsTitle = '当天'
        this.arrivalsTime = month + '月' + data + '日'
        this.arrivalsRange = '10:00-14:30'
      } else if ((time > 930) & (time <= 1430)) {
        // 9:30 - 14；30
        this.orderTime = '14:30'
        this.arrivalsTitle = '当天'
        this.arrivalsTime = month + '月' + data + '日'
        this.arrivalsRange = '15:00-20:00'
      } else if ((time > 1430) & (time < 1830)) {
        // 14: 30 - 18:30
        this.orderTime = '18:30'
        this.arrivalsTitle = '当天'
        this.arrivalsTime = month + '月' + data + '日'
        this.arrivalsRange = '19:00-23:00'
      } else if ((time >= 1830) & (time < 2400)) {
        // 18:30 - 24:00
        this.orderTime = '09:30'
        this.arrivalsTitle = '次日'
        this.arrivalsTime = month + '月' + (data + 1) + '日'
        this.arrivalsRange = '10:00-14:30'
      }
    },

    /*
        productLike： 收藏商品
      */
    productLike() {
      if (this.user) {
        let id = this.detailInfo.id
        productLike(id).then(res => {
          this.detailInfo.is_liked = res
          this.getDetail()
        })
      } else {
        this.$router.push({ name: 'login' })
      }
    },
    /*
        showDesc: 是否显示商品简介更多
       */
    showDesc() {
      this.isShowDesc = !this.isShowDesc
    },

    /*
        productUnlike： 取消收藏
      */
    productUnlike() {
      if (this.user) {
        let id = this.detailInfo.id
        productUnlike(id).then(res => {
          this.detailInfo.is_liked = res
          this.getDetail()
        })
      } else {
        this.$router.push({ name: 'login' })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-detail-info {
  padding-top: 18px;
  background: #fff;
  // height: 120px;
  .ui-flex {
    display: flex;
    justify-content: space-between;
    align-content: center;
    align-items: center;
    flex-basis: 100%;
    width: auto;
  }
  .info-header {
    padding: 0 15px;
    justify-content: flex-start;
    .price {
      position: relative;
      font-weight: bold;
      font-size: 0;
      &.info-price {
        display: flex;
        align-items: baseline;
        span {
          font-weight: 600;
          line-height: 1;
        }
      }
      span {
        @include sc(23px, #772508);
      }
      .price-unit {
        @include sc(15px, #772508);
      }
    }
    .huan {
      height: 18px;
      padding: 0 6px;
      background-color: #ef7e2f;
      border-radius: 9px 9px 9px 0px;
      margin-left: 5px;

      display: flex;
      align-items: center;
      justify-content: center;
      span {
        font-size: 12px;
        font-weight: 400;
        color: #fff;
        line-height: 16px;
      }
    }
    .discount-1 {
      margin-left: 5px;
      width: 62px;
      height: 18px;
      background: linear-gradient(90deg, rgba(247, 61, 231, 1) 0%, rgba(165, 66, 240, 1) 100%);
      border-radius: 9px;

      display: flex;
      align-items: center;
      justify-content: center;
      span {
        font-size: 14px;
        font-weight: 500;
        color: #fff;
        line-height: 1;
      }
    }
  }
  .hb-iscount {
    padding: 0 15px;
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    align-items: center;
    .hb-discount-title {
      color: #666666;
      font-size: 14px;
      display: flex;
      align-items: center;
      .ltr-spc {
        letter-spacing: 1px;
      }
      .unit {
        font-size: 12px;
        margin-right: -2px;
      }
      .hn-price {
        min-width: 32px;
        color: #707070;
        margin-right: 2px;
        margin-left: -1px;
        line-height: 1;
        .price-unit {
          letter-spacing: 0;
          display: inline-block;
          @include sc(11px, #707070);
          margin-right: -1px;
          line-height: 1;
        }
        .price-count {
          font-size: 16px;
          color: #707070;
        }
      }
      .hb-price-desc {
        display: flex;
        align-items: center;
        line-height: 1;
        .sur-icon {
          width: 13px;
        }
        .sur-img {
          width: 29px;
          height: 10px;
        }
        .hb-price-desc-txt {
          display: inline-block;
          font-size: 12px;
          color: #999;
          font-weight: bold;
          margin-right: 5px;
        }
        .quanyi-wrapper {
          @include quanyi-wrapper();
        }
      }
      .un-line {
        font-size: 12px;
        font-weight: 400;
        color: #999;
        line-height: 16px;
        text-decoration: line-through;
      }
    }
    & > img {
      width: 19px;
    }
  }

  .money-coupon {
    padding: 0 15px;
    height: 50px;
    display: flex;
    align-items: center;
    @include thin-border(#dbdbdb, 15px, auto, true);
    .money-coupon-title {
      color: #999999;
      text-align: left;
      font-size: 14px;
    }
    span.prod-orig-price {
      color: #999999;
      font-size: 16px;
      position: relative;
      &:before {
        content: '';
        position: absolute;
        display: block;
        height: 1px;
        width: 100%;
        background-color: #999999;
        top: 50%;
      }
    }
    span.prod-couponed-price {
      font-size: 16px;
      color: #772508;
      margin-left: 5px;
    }
    span.RMB-unit {
      font-size: 12px;
      margin-right: -2px;
    }
    .discount-coupon {
      flex: 1;
      word-break: break-word;
      overflow: hidden;
      text-overflow: ellipsis;
      font-weight: normal;
      display: -webkit-box;
      /*! autoprefixer: ignore next */
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 1;
      margin-left: 10px;
      span {
        display: inline-block;
        height: 20px;
        line-height: 20px;
        padding: 0 8px;
        background-color: #c9b594;
        position: relative;
        border-radius: 2px;
        @include sc(11px, #ffffff);
        & + span {
          margin-left: 5px;
        }
        &:before {
          position: absolute;
          border-radius: 6px;
          display: block;
          content: '';
          width: 6px;
          height: 6px;
          background-color: #ffffff;
          left: 0;
          top: 50%;
          transform: translate(-50%, -50%);
        }
        &:after {
          position: absolute;
          border-radius: 6px;
          display: block;
          content: '';
          width: 6px;
          height: 6px;
          background-color: #ffffff;
          right: 0;
          top: 50%;
          transform: translate(50%, -50%);
        }
      }
    }
    .money-coupon-rules {
      display: inline-block;
      @include sc(11px, #999999);
      line-height: 1;
      margin-right: 3px;
    }
    & > img {
      width: 19px;
    }
  }

  .prod-name {
    color: #707070;
    font-size: 14px;
    padding: 0 15px 20px;
    margin-top: 18px;
    font-weight: 600;
    word-break: break-all;
    line-height: 1.5;
    .tags-box {
      display: inline-block;
      background-color: #d5b4be;
      border-radius: 10px;
      height: 18px;
      line-height: 18px;
      padding: 0 6px;
      font-size: 0;
      margin-right: 5px;
      span {
        display: inline-block;
        @include sc(10px, #ffffff);
      }
    }
  }
  .price {
    display: flex;
    span {
      display: block;
      font-weight: normal;
    }
    .old-price {
      font-size: 13px;
      font-weight: normal;
      line-height: 18px;
      color: #979797;
      line-height: 16px;
      text-decoration: line-through;
      margin-left: 5px;
      margin-bottom: -7px;
    }
  }
  .detailinfo-sub {
    font-size: 12px;
    color: #808080;
    margin-top: 10px;
    padding: 0 15px;
    background: #fff;
    height: 30px;
  }
  .info-sub {
    border-bottom: 0.5px solid #e8eaed;
    padding-bottom: 15px;
    p {
      padding: 0;
      margin: 0;
      color: $primaryColor;
      font-size: 12px;
      &.ui-clip {
        display: -webkit-box;
        overflow: hidden;
      }
    }
    img {
      width: 8px;
      height: 4px;
      margin-left: 11px;
    }
  }
  .info-promotions {
    display: flex;
    justify-content: flex-start;
    align-content: center;
    align-items: center;
    padding: 15px;
    span {
      margin-left: 15px;
      font-size: 12px;
      font-family: 'PingFangSC-Regular';
      color: rgba(143, 142, 148, 1);
    }
    img {
      width: 38px;
    }
  }
}
</style>
