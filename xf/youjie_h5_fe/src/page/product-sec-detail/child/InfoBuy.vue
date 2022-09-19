<!-- Buy.vue -->
<template>
  <div class="ui-buy-wrapper ui-detail-common" v-if="detailInfo">
    <div class="buy-wrapper header" @click="changeCartState" v-if="this.activeBuy()">
      <div class="buy-wrapper-header">选择</div>
      <p v-if="number <= 0 && chooseinfo.ids.length <= 0" class="no-choosed">
        请选择购买{{ choosedInfo.join(' / ') }}数量分类
      </p>
      <p v-if="number <= 0 && chooseinfo.ids.length > 0">
        <span>{{ number + 1 }}件 {{ choosedInfo.join(' / ') }}</span>
      </p>
      <p v-if="number > 0 && chooseinfo.ids.length <= 0">数量{{ number }}</p>
      <p v-if="number > 0 && chooseinfo.ids.length > 0">
        <span>{{ number }}件 {{ choosedInfo.join(' / ') }}</span>
      </p>
      <img src="../../../assets/image/change-icon/icon_more.png" />
    </div>
    <div class="buy-wrapper header isopacity" v-if="!this.activeBuy()">
      <div class="buy-wrapper-header">选择</div>
      <p v-if="number <= 0 && chooseinfo.ids.length <= 0" class="no-choosed">
        请选择购买{{ choosedInfo.join(' / ') }}数量分类
      </p>
      <p v-if="number <= 0 && chooseinfo.ids.length > 0">
        <span>{{ number + 1 }}件 {{ choosedInfo.join(' / ') }}</span>
      </p>
      <p v-if="number > 0 && chooseinfo.ids.length <= 0">数量{{ number }}</p>
      <p v-if="number > 0 && chooseinfo.ids.length > 0">
        <span>{{ number }}件 {{ choosedInfo.join(' / ') }}</span>
      </p>
      <img src="../../../assets/image/change-icon/icon_more.png" />
    </div>
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
export default {
  data() {
    return {}
  },

  computed: {
    ...mapState({
      number: state => state.detail.number,
      detailInfo: state => state.detail.detailInfo,
      chooseinfo: state => state.detail.chooseinfo
      // isExchange: state => state.score.isExchange,
      // exchangeScore: state => state.score.exchangeScore,
      // currentScore: state => state.score.currentScore
    }),
    choosedInfo() {
      let arr = []
      if (this.chooseinfo.ids.length > 0) {
        arr = this.chooseinfo.specification.filter((item, index) => {
          return !(index % 2)
        })
      }
      return arr
    }
  },

  created() {},

  watch: {
    detailInfo: function(value) {
      this.setSpecification()
    }
  },

  methods: {
    ...mapMutations({
      saveCartState: 'saveCartState',
      saveChooseInfo: 'saveChooseInfo',
      changeType: 'changeType',
      saveShowFromAct: 'saveShowFromAct'
    }),

    activeBuy: function() {
      if (this.detailInfo.secbuy && this.detailInfo.secbuy.secbuy_sale < this.detailInfo.secbuy.secbuy_quantity) {
        // if (this.isExchange) {
        //   if (this.currentScore >= this.exchangeScore) {
        //     return true
        //   } else {
        //     return false
        //   }
        // } else {
        return true
        // }
      } else {
        return false
      }
    },

    changeCartState() {
      this.saveCartState(true)
      this.saveShowFromAct(1)
    },

    setSpecification() {
      if (this.detailInfo && this.detailInfo.properties) {
        let data = this.detailInfo.properties
        let arrays = []
        for (let i = 0; i <= data.length - 1; i++) {
          arrays.push(data[i].name)
        }
        this.saveChooseInfo({ specification: arrays, ids: [] })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-buy-wrapper {
  margin-top: 0;
  .buy-wrapper {
    .buy-wrapper-header {
      font-size: 14px;
      color: #999999;
    }
    &.header {
      padding: 0;
      height: 50px;
    }
    &.isopacity {
      opacity: 0.5;
    }
    span {
      color: #552e20;
      font-size: 14px;
    }
    p {
      flex: 1;
      font-size: 14px;
      color: #888;
      line-height: 20px;
      padding: 0;
      margin: 0;
      font-weight: 600;
      margin-left: 10px;
      &.no-choosed {
        color: #552e20;
        letter-spacing: 1px;
      }
      i {
        font-weight: normal;
        font-style: normal;
      }
    }
  }
  img {
    width: 19px;
  }
}
</style>
