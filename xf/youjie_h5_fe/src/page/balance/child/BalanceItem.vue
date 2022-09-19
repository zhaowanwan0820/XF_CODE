<template>
  <div class="item-wrapper">
    <div class="item" @click="clickDetail">
      <div class="item-info">
        <div class="column flex">
          <label class="title" v-html="getReason"></label>
          <label class="subtitle">{{ getDate }}</label>
        </div>
        <div class="column">
          <label class="price" v-bind:class="{ income: isIncome, expend: !isIncome }">
            {{ item.change > 0 ? '+' : '' }}{{ utils.formatMoney(item.change) }}
          </label>
        </div>
        <div class="column on-detail" v-if="item.change_type == 98">
          <img
            src="../../../assets/image/hh-icon/icon-enter-列表箭头.svg"
            :class="{ handstand: showDetail && item.detail.length }"
          />
        </div>
      </div>
      <div class="detail-wrapper" v-if="showDetail && item.detail.length">
        <div v-for="(item, index) in item.detail" :key="index" class="detail-item">
          <label class="item-name">{{ item.name }}</label>
          <label class="item-account">债权金额：{{ utils.formatMoney(item.account) }}</label>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ENUM } from '../../../const/enum'
export default {
  name: 'BalanceItem',
  props: {
    item: {
      type: Object
    }
  },
  data() {
    return {
      showDetail: false
    }
  },
  computed: {
    getReason() {
      return this.item.reason.replace(/\n/g, '<br />')
    },
    getDate() {
      let date = ''
      let item = this.item
      if (item && item.created_at) {
        date = this.utils.formatDate('YYYY-MM-DD HH:mm', item.created_at)
      }
      return date
    },
    isIncome() {
      return this.item && this.item.status === ENUM.BALANCE_STATUS.INCOME ? true : false
    }
  },
  methods: {
    clickDetail() {
      if (this.item.change_type == 98) {
        if (this.showDetail && this.item.detail.length) {
          this.showDetail = false
        } else {
          if (!this.item.detail.length) {
            this.$emit('showItemDetail', this.item)
          }
          this.showDetail = true
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.item-wrapper {
  padding-left: 15px;
  padding-right: 15px;
  background-color: #fff;
  &.section-footer .item {
    border-bottom: 0;
  }
}
.income {
  color: $primaryColor;
}
.expend {
  color: $baseColor;
}
.item {
  min-height: 69px;
  display: flex;
  position: relative;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  padding-left: 5px;
  border-bottom: 1px solid #eaebec;
  .flex {
    flex: 1;
  }
  .item-info {
    display: flex;
    flex-direction: row;
    flex: 1;
  }
  .column {
    display: flex;
    flex-direction: column;
  }
  .title {
    color: $baseColor;
    font-size: 14px;
    margin-top: 14px;
  }
  .subtitle {
    font-size: 12px;
    color: #b5b6b6;
    margin-top: 6px;
    margin-bottom: 12px;
  }
  .price {
    line-height: 69px;
    font-size: 16px;
    font-weight: 500;
  }
  .on-detail {
    width: 13px;
    margin-top: 27px;
    margin-left: 21px;
    img {
      @include wh(13px, 13px);
      transform: rotate(90deg);
      &.handstand {
        transform: rotate(270deg);
      }
    }
  }
  .detail-wrapper {
    color: #888;
    font-size: 12px;
    padding-bottom: 15px;
    display: flex;
    flex-direction: column;
    .detail-item {
      width: 100%;
      line-height: 17px;
      align-items: center;
      padding-bottom: 5px;
      .item-name {
        padding-right: 20px;
      }
    }
  }
}
</style>
