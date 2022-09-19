<template>
  <div class="detail-head">
    <div class="bill-type">
      <img src="../../../assets/image/hh-icon/mlm/icon-add@2x.png" alt="" v-if="detail.amount_type == 1" />
      <img src="../../../assets/image/hh-icon/mlm/icon-sub@2x.png" alt="" v-else />
      <div class="type-title">{{ getTitle }}</div>
    </div>
    <div class="bill-amount" :class="{ gray: detail.order_cancel == 1 }">
      {{ detail.amount_type == 1 ? '+' : '-' }}{{ detail.amount }}
      <span class="bill-sub-title" v-if="detail.type != ENUM.BILLTYPES.WITHDRAW">{{ fmtFrozen }}</span>
    </div>
  </div>
</template>

<script>
import { ENUM } from '../../../const/enum'
import { BILL_TYPE } from '../../huanhuanke/static'
export default {
  name: 'BillDetailHead',
  data() {
    return {
      BILL_TYPE,
      ENUM
    }
  },

  computed: {
    getTitle() {
      let str = this.detail.name
      this.BILL_TYPE.forEach(item => {
        if (item.id == this.detail.type) {
          str = item.name
        }
      })
      return str
    },
    fmtFrozen() {
      let frozen_status = ''
      if (this.detail.order_cancel == 1) {
        frozen_status = '已撤回'
      } else {
        frozen_status = this.detail.is_frozen == 1 ? '冻结中' : '可提现'
      }
      return frozen_status
    }
  },

  props: ['detail']
}
</script>

<style lang="scss" scoped>
.detail-head {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px 0;
  .bill-type {
    display: flex;
    align-items: center;
    justify-content: center;
    img {
      width: 30px;
    }
    .type-title {
      margin-left: 10px;
      font-size: 18px;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
      line-height: 25px;
    }
  }
  .bill-amount {
    margin-top: 15px;
    font-size: 32px;
    font-weight: 500;
    color: rgba(64, 64, 64, 1);
    line-height: 1;
    letter-spacing: 1px;
    position: relative;
    padding-right: 5px;
    &.gray {
      color: rgba(204, 204, 204, 1);
    }
    span {
      position: absolute;
      left: 100%;
      bottom: 3px;
      white-space: nowrap;
      font-size: 13px;
      font-weight: 400;
      color: rgba(155, 33, 11, 1);
      line-height: 1;
    }
  }
}
</style>
