<template>
  <div class="item-wrapper" @click="goDetail">
    <div class="img-wrapper">
      <img src="../../../assets/image/hh-icon/mlm/icon-add@2x.png" alt="" v-if="item.amount_type === 1" />
      <img src="../../../assets/image/hh-icon/mlm/icon-sub@2x.png" alt="" v-else />
    </div>
    <div class="content">
      <div class="left">
        <p class="item-title">{{ getTitle }}</p>
        <p class="item-name">{{ item.name }}</p>
        <p class="item-time">{{ utils.formatDate('YYYY-MM-DD HH:mm', item.created_at) }}</p>
      </div>
      <div class="right" :class="{ gray: item.order_cancel == 1 }">
        <p v-if="item.amount_type === 1">
          <span>+</span><span>{{ item.amount }}</span>
        </p>
        <p class="bla" v-else>
          <span>-</span><span>{{ item.amount }}</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { ENUM } from '../../../const/enum'
import { BILL_TYPE } from '../static'

export default {
  name: 'BalanceItem',
  data() {
    return {
      BILL_TYPE
    }
  },
  props: {
    item: {
      type: Object
    }
  },
  computed: {
    getTitle() {
      let str = this.item.name
      this.BILL_TYPE.forEach(item => {
        if (item.id == this.item.type) {
          str = item.name
        }
      })
      return str
    }
  },
  methods: {
    goDetail() {
      this.$router.push({ name: 'billDetail', query: { id: this.item.id } })
    }
  }
}
</script>

<style lang="scss" scoped>
.item-wrapper {
  display: flex;
  .img-wrapper {
    padding: 20px 20px 36px 15px;
    img {
      width: 30px;
      height: 30px;
    }
  }
  .content {
    padding: 15px 0 9px 0;
    margin-right: 20px;
    flex: 1;
    display: flex;
    justify-content: space-between;
    @include thin-border(rgba(85, 46, 32, 0.2), 0, auto, true);
    .left {
      .item-title {
        font-size: 16px;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
        line-height: 22px;
      }
      .item-name {
        font-size: 13px;
        font-weight: 400;
        color: rgba(102, 102, 102, 1);
        line-height: 18px;
        white-space: nowrap;
        width: 210px;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 3px;
      }
      .item-time {
        font-size: 13px;
        font-weight: 400;
        color: rgba(153, 153, 153, 1);
        line-height: 18px;
        margin-top: 5px;
      }
    }
    .right {
      &.gray p {
        color: rgba(204, 204, 204, 1) !important;
      }
      p {
        font-size: 18px;
        color: rgba(119, 37, 8, 1);
        line-height: 1;
        font-weight: 600;
        &.bla {
          color: rgba(64, 64, 64, 1);
        }
        span {
          font-family: DINAlternate;
        }
      }
    }
  }
}
</style>
