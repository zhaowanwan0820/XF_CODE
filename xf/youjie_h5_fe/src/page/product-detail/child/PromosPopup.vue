<!-- Shopping.vue -->
<template>
  <mt-popup v-model="isShowPromosPopup" position="bottom" v-bind:close-on-click-modal="false" style="height: 77%;">
    <div class="favorable-container" v-if="detailInfo && detailInfo.promos && detailInfo.promos.length > 0">
      <div class="favorable-header">
        <span>优惠</span>
        <img src="../../../assets/image/hh-icon/icon-关闭.png" v-on:click="closePromoPop(false)" />
      </div>
      <div class="favorable-body">
        <div class="promo body-item" v-for="(item, index) in getDetailPromos" v-if="getDetailPromos.length > 0">
          <div class="body-title">
            促销
          </div>
          <div class="body-list" v-if="item.status == 2" :key="index" @click="goPromoDetail(item)">
            <span class="promo-title">{{ utils.activityNameTmp }}</span>
            <!-- <img src="../../../assets/image/change-icon/c0_sale@2x.png" /> -->
            <span class="promo-desc">{{ item.desc }}</span>
            <img src="../../../assets/image/hh-icon/b0-home/icon-more.png" alt="" />
          </div>
        </div>
        <div class="coupon body-item" v-if="getDetailUsableList.length > 0">
          <div class="body-title">
            优惠券
          </div>
          <!-- <div class="tips">已为您选择最佳优惠券，可优惠20元</div> -->
          <template v-for="(item, index) in getDetailUsableList">
            <promos-item :item="item" v-if="item.status == 2" :key="index"></promos-item>
          </template>
        </div>
      </div>
    </div>
  </mt-popup>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { Toast, MessageBox, Button } from 'mint-ui'
import PromosItem from './PromosItem'

export default {
  data() {
    return {}
  },

  props: {
    isShowPromosPopup: {
      type: Boolean,
      default: false
    }
  },

  components: {
    PromosItem
  },

  created() {},

  computed: {
    ...mapGetters({
      getDetailUsableList: 'getDetailUsableList',
      getDetailPromos: 'getDetailPromos'
    }),
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    })
  },

  mounted() {},

  methods: {
    ...mapMutations({
      savePromosPopupState: 'savePromosPopupState'
    }),

    // 关闭购物车浮层
    closePromoPop(value) {
      this.savePromosPopupState(value)
    },

    goPromoDetail(item) {}
  }
}
</script>
<style lang="scss" scoped>
.favorable-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  .favorable-header {
    display: flex;
    justify-content: space-between;
    height: 50px;
    align-items: center;
    padding: 0 15px;
    @include thin-border();
    span {
      color: #404040;
      font-size: 14px;
    }
    img {
      width: 12px;
    }
  }
  .favorable-body {
    flex: 1;
    overflow: auto;
    padding: 15px;
    .body-item {
      &.coupon {
        margin-top: 15px;
        & > .tips {
          font-size: 13px;
          color: #666666;
          margin-top: 13px;
        }
        .body-list {
          margin-top: 10px;
          border: 1px solid #d0b482;
          border-radius: 2px;
          display: flex;
          flex-direction: column;
          height: 105px;
          width: 100%;
          .coupon-body {
            flex: 1;
            background: rgba(208, 180, 130, 0.1);
            display: flex;
            .coupon-reduce {
              text-align: center;
              width: 100px;
              .number {
                font-size: 0;
                color: #404040;
                font-weight: 600;
                span {
                  font-size: 34px;
                }
                .number-unit {
                  font-size: 16px;
                  margin-right: -2px;
                }
              }
              .tips {
                @include sc(11px, #404040);
              }
            }
            .coupon-rules {
              font-size: 12px;
              color: #404040;
              line-height: 1.5;
              flex: 1;
              display: flex;
              flex-direction: column;
              justify-content: center;
            }
            .coupon-status {
              width: 85px;
              background: rgba(208, 180, 130, 0.2);
              display: flex;
              font-size: 12px;
              color: #404040;
              align-items: center;
              justify-content: center;
              text-align: center;
            }
          }
          .coupon-tips {
            line-height: 30px;
            height: 30px;
            @include sc(11px, #666666);
          }
        }
      }
      &.promo {
        .body-list {
          display: flex;
          height: 50px;
          align-items: center;
          justify-content: space-between;
          @include thin-border();
          .promo-title {
            @include sc(11px, #ffffff);
            margin-left: 0;
            padding: 0 8px;
            line-height: 18px;
            border-radius: 20px;
            border: 1px solid #e97c74;
            background: #e97c74;
          }
          .promo-desc {
            margin-left: 7px;
            flex: 1;
            font-size: 13px;
            color: #666666;
          }
          & > img {
            width: 6px;
            height: 6px;
          }
        }
      }
    }
    .body-title {
      color: #999999;
      font-size: 14px;
    }
  }
}
</style>
