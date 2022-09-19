<template>
  <div class="p-item-wrapper" @click="goDetail" v-if="item.tender_id">
    <div class="checked" v-if="!status" :class="{ active: item.checked }" @click.stop="changeChecked"></div>
    <div class="content">
      <div class="p-content">
        <p class="van-ellipsis">{{ item.name }}</p>
        <span>{{ utils.formatFloat(item.surplus_capital) }}</span>
        <label>待还本金(元)</label>
      </div>
      <!-- <div class="p-content no-title">
        <p class="van-ellipsis"></p>
        <span>{{ utils.formatFloat(item.apr) }}%</span>
        <label>年利率</label>
      </div> -->
      <div class="p-content no-title">
        <p class="van-ellipsis"></p>
        <span>{{ utils.formatFloat(item.account_init) }}</span>
        <label>出借金额(元)</label>
      </div>
    </div>
    <img src="../../../assets/image/confirmation/icon-tip.png" alt="" />
  </div>
</template>

<script>
import { MAXLENGTH } from '../static.js'
import { mapState, mapGetters } from 'vuex'
export default {
  name: 'ProjectListItem',
  props: ['item', 'status'],
  computed: {
    ...mapGetters({
      hasCheckedLength: 'hasCheckedLength'
    })
  },
  methods: {
    goDetail() {
      // 标的详情页
      this.$router.push({
        name: 'confirmationDetail',
        params: { id: this.item.tender_id }
      })
    },
    changeChecked() {
      if (!this.item.checked && this.hasCheckedLength >= MAXLENGTH) {
        this.$toast('抱歉，暂仅支持选中50条记录')
      } else {
        this.item.checked = !this.item.checked
      }
    }
  }
}
</script>
<style lang="less" scoped>
.content {
  padding-left: 15px;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.p-item-wrapper {
  position: relative;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  padding: 12px 26px 12px 5px;
  background-color: #fff;
  overflow: hidden;
  img {
    width: 7px;
    height: 12px;
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
  }
  .checked {
    width: 22px;
    height: 22px;
    background: url('../../../assets/image/confirmation/icon-checked-off.png') no-repeat;
    background-size: 22px;
    margin-left: 5px;
    &.active {
      background-image: url('../../../assets/image/confirmation/icon-checked-on.png');
    }
  }
  .p-content {
    width: 126px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    p {
      font-size: 12px;
      font-weight: 400;
      line-height: 17px;
    }
    span {
      margin-top: 5px;
      display: block;
      font-size: 18px;
      font-weight: 500;
      color: #333;
      line-height: 25px;
    }
    label {
      .sc(11px, left center);
      color: #999;
      font-weight: 400;
      line-height: 16px;
    }
  }
  .no-title {
    p {
      height: 16px;
      opacity: 0;
    }
  }
}
</style>
