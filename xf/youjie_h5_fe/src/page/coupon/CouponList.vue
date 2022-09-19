<template>
  <div class="container">
    <mt-header class="header" title="我的优惠券">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="coupon-tabs-wrapper">
      <div
        class="coupon-tab"
        v-for="item in TAB"
        :key="item.id"
        :class="{ active: item.id === currentId }"
        @click="selectTabs(item.id)"
      >
        <label>
          <span>{{ item.name }}</span>
          <span v-if="isShowNum(item.id)">({{ unUsedNum }})</span>
        </label>
        <div class="line"></div>
      </div>
    </div>
    <template v-if="list.length">
      <div class="coupon-list-body-wrapper">
        <div class="coupon-list-body" v-for="(item, index) in list" :key="index">
          <coupon-item
            :item="item"
            btnTxt="去使用"
            :opacity="currentId !== 1"
            :isUsed="currentId === 2"
            v-on:onclick="goGoodsList(item)"
          ></coupon-item>
        </div>
      </div>
    </template>
    <template v-else>
      <div class="coupon-list-body-none">
        <img src="../../assets/image/hh-icon/l0-list-icon/coupon-list.png" alt="" />
        <p>暂无优惠券</p>
        <gk-button class="button" type="primary-secondary-white" v-on:click="goVisit">
          随便逛逛
        </gk-button>
      </div>
    </template>
  </div>
</template>

<script>
import { Header, Indicator } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { TAB } from './static'
import { couponList } from '../../api/coupon'
import CouponItem from './child/CouponItem'
export default {
  data() {
    return {
      TAB: TAB,
      currentId: 1
    }
  },
  created() {
    this.getCouponList()
  },
  components: {
    CouponItem
  },
  computed: {
    ...mapState({
      couponObj: state => state.coupon.couponObj,
      isOnline: state => state.auth.isOnline
    }),
    list() {
      if (this.couponObj && this.couponObj[this.currentId] && this.couponObj[this.currentId].length) {
        return this.couponObj[this.currentId]
      } else {
        return []
      }
    },
    unUsedNum() {
      return this.couponObj && this.couponObj[1] && this.couponObj[1].length ? this.couponObj[1].length : 0
    }
  },
  methods: {
    ...mapMutations({
      saveCoupon: 'saveCoupon',
      saveCouponSingleInfo: 'saveCouponSingleInfo'
    }),
    getCouponList() {
      Indicator.open()
      couponList(this.currentId)
        .then(
          res => {
            // 优惠券列表没有分页，暂不做分页考虑
            let obj = { ...this.couponObj }
            obj[this.currentId] = [...res]
            this.saveCoupon(obj)
          },
          error => {}
        )
        .finally(() => {
          Indicator.close()
        })
    },
    selectTabs(id) {
      this.currentId = id
      this.getCouponList()
    },
    goGoodsList(item) {
      this.saveCouponSingleInfo(item)
      this.$router.push({ name: 'couponProductList', query: { id: item.coupon_id } })
    },
    isShowNum(id) {
      return id === 1 && this.unUsedNum ? true : false
    },
    // 随便逛逛
    goVisit() {
      this.$router.push({ name: 'home' })
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
  background-color: #fff;

  display: flex;
  flex-direction: column;
}
.coupon-tabs-wrapper {
  width: 100%;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: space-around;
  .coupon-tab {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    label {
      height: 38px;
      font-size: 0;
      color: #666;
      font-weight: 400;
      span {
        line-height: 38px;
        font-size: 14px;
      }
    }
    .line {
      width: 48px;
      height: 2px;
    }
    &.active {
      label {
        color: $markColor;
      }
      .line {
        background-color: $markColor;
      }
    }
  }
}
.coupon-list-body-wrapper {
  overflow: auto;
  flex: 1;
}
.coupon-list-body {
  padding: 0 15px;
  &:nth-last-child(1) {
    margin-bottom: 25px;
  }
}
.coupon-list-body-none {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-bottom: 65px;
  img {
    @include wh(135px, 135px);
    margin-top: 68px;
  }
  p {
    margin-top: 15px;
    font-size: 17px;
    font-weight: 400;
    color: #666;
    line-height: 24px;
  }
  .button {
    @include button($radius: 2px);
    width: 140px;
    margin-top: 40px;
  }
}
</style>
