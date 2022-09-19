<template>
  <div class="hb-iscount" @click="showHBRules">
    <div class="hb-discount-title">
      <span class="hb-price-desc">
        <img src="../../../assets/image/hh-icon/b0-home/money-icon.png" class="sur-icon" />
        <span class="hb-price-desc-txt">
          {{
            `${utils.formatFloat(
              detailInfo.instalment[detailInfo.instalment.length - 1].surplus
            )}积分+￥${utils.formatFloat(detailInfo.instalment[detailInfo.instalment.length - 1].cash)}`
          }}
        </span>
        <span class="fq-length"><span>首期</span></span>
      </span>
    </div>
    <img src="../../../assets/image/change-icon/icon_more.png" />
  </div>
</template>
<script>
import { mapState, mapMutations, mapGetters } from 'vuex'
import InfoSuppliers from './InfoSuppliers'
import InfoPromotions from './InfoPromotions'
import { ENUM } from '../../../const/enum'
import { PRODUCT_SHOW_SHOUQI } from '../static.js'

export default {
  data() {
    return {}
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    })
  },

  methods: {
    ...mapMutations({
      saveHBRulesPopupState: 'saveHBRulesPopupState'
    }),
    showHBRules() {
      this.saveHBRulesPopupState(true)
    }
  }
}
</script>
<style lang="scss" scoped>
.hb-iscount {
  padding: 0 15px;
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
  align-items: center;
  .hb-discount-title {
    color: #666666;
    font-size: 14px;
    display: flex;
    align-items: center;

    .hb-price-desc {
      display: flex;
      align-items: center;
      line-height: 1;
      .sur-icon {
        width: 20px;
      }
      .hb-price-desc-txt {
        font-size: 20px;
        font-weight: 500;
        color: rgba(119, 37, 8, 1);
        line-height: 20px;
        margin-left: 0;
        transform: none;
        margin-left: 4px;
      }
      .fq-length {
        background: rgba(99, 73, 3, 1);
        border-radius: 7px;
        font-weight: 500;
        line-height: 16px;
        padding: 0 4px;
        margin-left: 2px;
        display: inline-block;

        span {
          display: inline-block;
          @include sc(11px, #fff);
        }
      }
    }
  }
  & > img {
    width: 19px;
  }
}
</style>
