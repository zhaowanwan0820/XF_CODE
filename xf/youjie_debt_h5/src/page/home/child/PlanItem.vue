<template>
  <div class="item-wrapper" @click="toDetail">
    <div class="surplus-time" v-if="surplusTime">
      <p>剩余有效期：{{ surplusTime }}</p>
    </div>
    <div class="main-msg">
      <div class="i-discount">
        <span class="zhe-val">{{ utils.formatFloat(item.discount) }}</span
        ><span class="zhe">折</span>
      </div>
      <div class="i-plan-money">
        <span class="val">{{ item.money | formatMoney }}</span>
        <p class="desc">计划求购金额</p>
      </div>
      <div class="i-surplus-money">
        <span class="val">{{ (item.money - item.acquired_money) | formatMoney }}</span>
        <p class="desc">剩余求购金额</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PlanItem',
  data() {
    return {}
  },
  props: {
    item: Object
  },
  computed: {
    surplusTime() {
      return this.utils.getSurplusTime(this.item.expiry_time * 1000)
    }
  },
  methods: {
    toDetail() {
      this.$router.push({
        name: 'transferDebt',
        params: { id: this.item.pur_id },
        query: { discount: this.item.discount }
      })
    }
  }
}
</script>

<style lang="less" scoped>
.item-wrapper {
  margin-top: 10px;
  padding: 10px 0;
  background: rgba(255, 255, 255, 1);
}
.main-msg {
  padding: 0 14px 0 15px;
  display: flex;
  align-items: stretch;

  .val {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: rgba(64, 64, 64, 1);
    line-height: 25px;
  }
  .desc {
    font-size: 13px;
    font-weight: 400;
    color: rgba(153, 153, 153, 1);
    line-height: 18px;
    margin-top: 2px;
  }
  .i-discount {
    width: 127px;
    flex-basis: 127px;
    font-size: 0;

    .zhe-val {
      font-size: 40px;
      font-weight: bold;
      color: @themeColor;
      display: inline-block;
      line-height: 1;
    }
    .zhe {
      display: inline-block;
      font-size: 20px;
      font-weight: bold;
      color: @themeColor;
      line-height: 1;
      margin-left: 2px;
      vertical-align: 4px;
    }
  }
  .i-plan-money {
    width: 138px;
    flex-basis: 138px;
  }
  .i-surplus-money {
    flex: 1 0 0;
    text-align: right;
  }
}
.surplus-time {
  font-size: 13px;
  font-weight: 400;
  color: #29af73;
  line-height: 18px;
  padding-right: 12px;
  margin-bottom: 10px;
  text-align: right;
}
</style>
