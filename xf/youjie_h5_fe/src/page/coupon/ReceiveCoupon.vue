<template>
  <div class="container">
    <mt-header class="header" title="领取优惠券">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="coupon-wrapper" :class="`coupon-${status}`">
      <div class="coupon-info-wrapper">
        <template v-if="status === 0">
          <div class="coupon-info un-recieve">
            <label class="info">
              <span class="coupon-num">{{ info.price }}</span>
              <span class="coupon-word">元</span>
            </label>
            <label class="desc">{{ getUseTerm }}</label>
          </div>
          <div class="coupon-desc">
            <span class="left">有效期至</span>
            <span class="right">{{ info.period_time }}</span>
          </div>
          <div class="coupon-reason">
            <label>{{ reason }}</label>
          </div>
        </template>
        <template v-else-if="status === 1">
          <div class="coupon-info un-recieve">
            <label class="info">
              <span class="coupon-num">{{ info.price }}</span>
              <span class="coupon-word">元</span>
            </label>
          </div>
        </template>
        <template v-else-if="status === 2">
          <div class="coupon-info recieve">
            <label class="info">
              <span class="coupon-num">{{ info.price }}</span>
              <span class="coupon-word">元</span>
            </label>
            <label class="desc">{{ getUseTerm }}</label>
          </div>
          <div class="coupon-desc">
            <span class="left">有效期至</span>
            <span class="right">{{ info.period_time }}</span>
          </div>
        </template>
        <template v-else>
          <div class="coupon-info failed">
            <label class="info">
              <span class="coupon-num">{{ info.price }}</span>
              <span class="coupon-word">元</span>
            </label>
          </div>
        </template>
      </div>
      <!-- 立即领取 or 立即使用 -->
      <span class="btn" v-if="status === 1" @click="getACoupon">立即领取</span>
      <span class="btn" v-else-if="status === 2" @click="goGoodsList">立即使用</span>
      <span class="btn" v-else @click="goHome">去商城逛逛</span>
    </div>

    <!-- 使用说明 -->
    <div class="use-desc">
      <p>{{ info.coupon_desc }}</p>
    </div>
  </div>
</template>

<script>
import { Header, Indicator, Toast } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { couponInfo, getACoupon } from '../../api/coupon'
export default {
  data() {
    return {
      id: process.env.NODE_ENV === 'development' && !this.$route.query.id ? 'MDMwODA2MDk=' : this.$route.query.id,
      isGet: false, //是否进行领取动作
      info: ''
    }
  },
  created() {
    this.getCouponInfo()
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline
    }),
    status() {
      // 【info.status】 0不能领取（除过期、失效） 1可领取 2已领取 3已过期+已失效
      let status = 1
      if (this.isOnline && this.info.status > -1) {
        status = this.isGet ? 2 : this.info.status
      }
      return status
    },
    getUseTerm() {
      let str = ''
      if (this.info.use_term == -1) {
        str = '无门槛'
      } else {
        str = `门槛:满${this.info.use_term}元可用`
      }
      return str
    },
    reason() {
      return this.isOnline && this.info.reason ? this.info.reason : ''
    }
  },
  methods: {
    ...mapMutations({
      saveCouponSingleInfo: 'saveCouponSingleInfo'
    }),
    getCouponInfo() {
      Indicator.open()
      couponInfo(this.id)
        .then(
          res => {
            this.info = res
          },
          error => {}
        )
        .finally(() => {
          Indicator.close()
        })
    },
    getACoupon() {
      if (!this.isOnline) {
        this.goLogin()
        return
      }
      if (this.info.status !== 1) {
        // 不能领取 弹窗reason
        this.info.reason && Toast(this.info.reason)
        return
      }
      getACoupon(this.info.coupon_id).then(
        res => {
          Toast(res)
          this.isGet = true
        },
        error => {}
      )
    },
    goGoodsList(id) {
      this.saveCouponSingleInfo(this.info)
      this.$router.push({ name: 'couponProductList', query: { id: this.info.coupon_id } })
    },
    goHome() {
      this.$router.push({ name: 'home' })
    },
    goLogin() {
      this.$router.push({ name: 'login' })
    },
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.header {
  width: 100%;
}
.container {
  min-height: 100%;
  overflow: auto;
  display: flex;
  align-items: center;
  flex-direction: column;
  background-color: #e32a29;

  .coupon-wrapper {
    display: flex;
    align-items: center;
    flex-direction: column;

    width: 100%;
    height: 430px;
    background: #e32a29 no-repeat;
    background-size: 375px 430px;
    &.coupon-0 {
      background-image: url(../../assets/image/hh-icon/coupon/bg-receive-2.png);
      .coupon-info-wrapper {
        margin-top: 130px;
        .coupon-info {
          height: 85px;
        }
        .coupon-desc {
          margin-bottom: 4px;
        }
      }
    }
    &.coupon-1 {
      background-image: url(../../assets/image/hh-icon/coupon/bg-unreceive.png);
    }
    &.coupon-2 {
      background-image: url(../../assets/image/hh-icon/coupon/bg-receive.png);
    }
    &.coupon-3 {
      background-image: url(../../assets/image/hh-icon/coupon/bg-failed.png);
    }

    .coupon-info-wrapper {
      width: 100%;
      display: flex;
      align-items: center;
      flex-direction: column;
      margin-top: 149px;
      .coupon-info {
        padding-left: 50px;
        width: 210px;
        height: 77px;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        .info {
          font-size: 0;
          display: flex;
          align-items: baseline;
          .coupon-num {
            font-size: 40px;
            font-weight: normal;
            line-height: 49px;
          }
          .coupon-word {
            margin-left: 5px;
            font-size: 24px;
            font-weight: 500;
            line-height: 33px;
          }
        }
        .desc {
          margin-top: -3px;
          display: inline-block;
          @include sc(10px, rgba(169, 91, 28, 0.5));
          font-weight: 400;
          line-height: 14px;
        }

        &.un-recieve {
          color: #cc7d3c;
        }
        &.recieve {
          color: #cc7d3c;
        }
        &.failed {
          color: #d3d3d3;
        }
      }
      .coupon-desc {
        width: 250px;
        margin: 7px 0 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0;
        span {
          white-space: nowrap;
          color: #a95b1c;
        }
        .left {
          display: inline-block;
          @include sc(10px, #a95b1c);
          font-weight: 400;
          line-height: 17px;
        }
        .right {
          font-size: 12px;
          font-weight: 400;
          line-height: 17px;
        }
      }
      .coupon-reason {
        max-width: 250px;
        height: 30px;
        overflow: hidden;
        margin-bottom: 8px;

        display: flex;
        align-items: center;
        justify-content: center;
        label {
          @include sc(10px, #a95b1c);
          font-weight: 400;
          line-height: 15px;
        }
      }
    }
    .btn {
      margin-top: 48px;
      width: 198px;
      height: 50px;

      font-size: 20px;
      font-weight: 400;
      color: #a95b1c;
      text-align: center;
      line-height: 50px;
    }
  }

  .use-desc {
    width: 272px;
    background: rgba(235, 67, 66, 1);
    border-radius: 9px;
    border: 1px solid rgba(255, 110, 109, 1);

    padding: 41px 18px 26px;
    margin-bottom: 40px;
    background: url('../../assets/image/hh-icon/coupon/bg-desc.png') 30px 16px no-repeat;
    background-size: 247px 17px;
    p {
      word-break: break-all;
      font-size: 16px;
      font-weight: 400;
      color: rgba(253, 236, 206, 1);
      line-height: 23px;
    }
  }
}
</style>
