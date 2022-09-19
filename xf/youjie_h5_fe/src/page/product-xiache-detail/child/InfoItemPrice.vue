<template>
  <div class="price-contianer">
    <div class="info-header ui-flex">
      <div class="price info-price">
        <!-- <span class="price-unit">￥</span> -->
        <!-- <span>{{ utils.formatFloat(detailInfo.current_price) }}</span> -->
        <!-- <label class="old-price" v-if="isShowMarketPrice">￥{{ utils.formatFloat(detailInfo.market_price) }}</label> -->
        <!-- <price-item 
          :cash="detailInfo.MONEY_SHOW" 
          :surplus="detailInfo.HB_SHOW" 
          class="product-price-wrapper"
          classType="price-style-two"
         ></price-item> -->
      </div>
    </div>

    <div class="line-price-wrap" @click="showHBRules">
      <div class="old-price">
        <span class="line-text">市场价￥</span>
        <span class="line-money">{{ utils.formatFloat(detailInfo.market_price) }}</span>
      </div>
      <!-- <img src="../../../assets/image/change-icon/icon_more.png" /> -->
    </div>

    <!-- 积分抵扣部分 -->
    <!-- 后期可能会区分用户显示（爱投资/其他） -->
    <!-- <div class="hb-iscount" v-if="detailInfo.id && detailInfo.HB_SHOW > 0" @click="showHBRules">
      <div class="hb-discount-title">
        <span class="hb-price-desc">
          <span class="hb-price-desc-txt">
            {{ `￥${utils.formatFloat(detailInfo.MONEY_SHOW)}` }}
          </span>
          <div class="quanyi-wrapper">
            <img src="../../../assets/image/hh-icon/l0-list-icon/debt-left-angle.png" />
            <div>
              <label class="debt-style">{{ `积分抵扣￥${utils.formatFloat(detailInfo.HB_SHOW)}` }}</label>
            </div>
          </div>
        </span>
      </div>
    </div> -->
  </div>
</template>

<script>
import { mapState, mapMutations, mapGetters } from 'vuex'
import PriceItem from '../../../components/common/ListItemPrice'
export default {
  name: '',
  data() {
    return {}
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    }),
    isShowMarketPrice() {
      let market_price = this.detailInfo.market_price * 1
      let current_price = this.detailInfo.current_price * 1
      let val = false
      if (market_price > current_price) {
        val = true
      }
      return val
    }
  },

  methods: {
    ...mapMutations({
      saveHBRulesPopupState: 'saveHBRulesPopupState'
    }),

    /*
        showHBRules: 显示积分说明
       */
    showHBRules() {
      return
      this.saveHBRulesPopupState(true)
    }
  },
  components: {
    PriceItem
  }
}
</script>

<style lang="scss" scoped="scoped">
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
  div {
    display: flex;
    justify-content: space-between;
    align-content: center;
    align-items: center;
    // div{
    //   width: 21px;
    //   height: 21px;
    //   vertical-align: middle;
    //   margin-left: 15px;
    //   flex-shrink: 0;

    // }
  }

  .price {
    position: relative;
    font-weight: bold;
    font-size: 0;
    &.info-price {
      display: flex;
      align-items: baseline;
      span {
        font-weight: 600;
      }
    }
    span {
      @include sc(23px, #b75800);
    }
    .price-unit {
      @include sc(15px, #b75800);
    }
  }
  .price {
    display: flex;
    span {
      display: block;
      font-weight: normal;
    }
  }
}
// .hb-iscount {
//   padding: 0 15px;
//   display: flex;
//   justify-content: space-between;
//   margin-top: 10px;
//   align-items: center;
//   .hb-discount-title {
//     color: #666666;
//     font-size: 14px;
//     display: flex;
//     align-items: center;
//     .ltr-spc {
//       letter-spacing: 1px;
//     }
//     .unit {
//       font-size: 12px;
//       margin-right: -2px;
//     }
//     .hn-price {
//       min-width: 32px;
//       color: #707070;
//       margin-right: 2px;
//       margin-left: -1px;
//       line-height: 1;
//       .price-unit {
//         letter-spacing: 0;
//         display: inline-block;
//         @include sc(11px, #707070);
//         margin-right: -1px;
//         line-height: 1;
//       }
//       .price-count {
//         font-size: 16px;
//         color: #707070;
//       }
//     }
//     .hb-price-desc {
//       display: flex;
//       align-items: center;
//       line-height: 1;
//       .sur-icon {
//         width: 13px;
//       }
//       .sur-img {
//         width: 29px;
//         height: 10px;
//       }
//       .hb-price-desc-txt {
//         margin-left: -2px;
//         display: inline-block;
//         @include sc(11px, #999999);
//       }
//       .quanyi-wrapper {
//         @include quanyi-wrapper();
//       }
//     }
//   }
//   & > img {
//     width: 19px;
//   }
// }
.line-price-wrap {
  padding: 0 15px;
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
  align-items: center;
  .old-price {
    font-weight: normal;
    color: #979797;
    // margin-left: 5px;
    // margin-bottom: -7px;
    position: relative;
    .line-text {
      font-size: 11px;
      line-height: 16px;
    }
    .line-money {
      font-size: 16px;
      line-height: 22px;
    }
    &::after {
      position: absolute;
      top: 55%;
      left: 0;
      content: '';
      width: 105%;
      height: 1px;
      background: #979797;
    }
  }
  & > img {
    width: 19px;
  }
}
</style>
