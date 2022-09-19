<template>
  <div class="bond-item" :class="{ 'section-disabled': sectionDisabled }">
    <div class="row row-1">
      <span class="name">{{ item.name }}</span>
      <span class="company-sign" v-if="item.companySign">【{{ item.companySign }}】</span>
      <span class="payment-lately" v-if="item.payment_lately"></span>
    </div>
    <div class="row row-2">
      <div class="column column-left">
        <label class="val">{{ $accounting.formatNumber(item.account, 2) }}</label>
        <label class="title">债权金额(元)</label>
      </div>
      <!-- <div class="column">
        <label class="val">{{ getBondStatusBy(item.type) }}</label>
        <label class="title">还款状态</label>
      </div> -->
      <div class="column column-right">
        <input
          type="checkbox"
          :id="'id' + item.id"
          class="sel-debt"
          :disabled="sectionDisabled"
          v-model="checkStatus"
        />
        <label class="sel-radius" placeholder="v" :for="'id' + item.id"></label>
      </div>
    </div>
    <div class="row row-3">
      <span> {{ utils.formatDate('YYYY-MM-DD', item.addtime) }}出借 </span>
      <span class="disable-txt" v-if="showDisableTxt">{{ disableTxt }}</span>
    </div>
  </div>
</template>

<script>
import { BONDSTATUS } from '../static'
export default {
  name: 'BondDebtItem',
  props: {
    index: {
      type: Number
    },
    item: {
      type: Object
    },
    checkId: {
      type: Boolean
    },
    disabledCheckbox: {
      type: Boolean
    }
  },
  data: function() {
    return {
      checkStatus: this.checkId,
      BONDSTATUS: BONDSTATUS
    }
  },
  computed: {
    showDisableTxt() {
      return this.item.is_exchanging || this.item.is_black
    },
    disableTxt() {
      return this.item.is_exchanging ? '兑换处理中' : this.item.is_black ? '已被冻结，不能兑换' : ''
    },
    sectionDisabled() {
      return (!this.checkId && this.disabledCheckbox) || !!this.item.is_exchanging || !!this.item.is_black
    }
  },
  watch: {
    checkId: function(value) {
      this.checkStatus = value
    },
    checkStatus: function() {
      this.$emit('clickBondId', this.index)
    }
  },
  methods: {
    // 根据债权状态值获取对应的状态
    getBondStatusBy(status) {
      let data = this.BONDSTATUS
      for (let i = 0, len = data.length; i <= len - 1; i++) {
        if (data[i].id == status) {
          return data[i].name
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.bond-item {
  height: auto;
  color: $baseColor;
  background-color: #fff;
  margin-top: 10px;
  padding: 17px 20px 13px 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
  &.section-disabled {
    color: #7e7e7e;
    background: #fafafa;
    .row-2 .column .title {
      color: #adadad;
    }
  }
  .row {
    display: flex;
  }
  .row-1 {
    align-items: center;
    flex-wrap: wrap;

    font-size: 12px;
    line-height: 20px;
    .name {
      text-overflow: ellipsis;
      overflow: hidden;
    }
    .payment-lately {
      display: block;
      width: 76px;
      height: 16px;
      background: url(../../../assets/image/hh-icon/h0-bond/payment-lately@2x.png) no-repeat 0 center;
      background-size: contain;

      margin-left: 6px;
    }
  }
  .row-2 {
    margin-top: 5px;

    .column {
      flex: 1;
      label {
        display: block;
      }
      .val {
        font-size: 15px;
        line-height: 36px;
      }
      .title {
        font-size: 11px;
        color: $subbaseColor;
      }
    }
    .column-left .val {
      font-size: 25px;
      font-weight: 600;
    }
    .column-right {
      flex: 0;
    }
  }
  .row-3 {
    justify-content: space-between;
    margin-top: 12px;
    font-size: 11px;
    height: 12px;
    line-height: 12px;
    color: #b5b6b6;
  }
  .disable-txt {
    color: #999;
    font-weight: bold;
  }
  .sel-debt {
    display: none;
    &:checked + .sel-radius {
      @include wh(20px, 20px);
      background: #a3c6db url('../../../assets/image/hh-icon/icon-checkbox-勾.svg') center no-repeat;
      background-size: 12px 12px;
      border: 0;
    }
    &:disabled + .sel-radius {
      visibility: hidden;
    }
  }
  .sel-radius {
    @include wh(16px, 16px);
    @include borderRadius(50%);
    margin-top: 7px;
    border: 2px solid #cfd0d1;
  }
}
</style>
