<template>
  <div class="detail-target-container" @click="goDetail(info.debt_id, info.products)">
    <van-row class="row-first">
      <van-col :span="15" class="project-name">{{ info.name }}</van-col>
      <van-col :span="9" class="valid-time"
        >剩余有效期: {{ utils.getSurplusTime(info.endtime * 1e3) || '永久' }}</van-col
      >
    </van-row>
    <div class="main-msg">
      <div class="i-discount">
        <span class="zhe-val">{{ fmtDiscount }}</span
        ><span class="zhe">折</span>
      </div>
      <div class="i-plan-money">
        <span class="val">{{ utils.formatMoney(info.money) }}</span>
        <p class="desc">转让金额</p>
      </div>
      <div class="i-surplus-money">
        <span class="val">{{ utils.formatMoney(info.transferprice) }}</span>
        <p class="desc">转让价格</p>
      </div>
    </div>
  </div>
</template>

<script>
import verifiedMixin from "@/components/Verified/VerifiedMixin";

export default {
  name: 'targetItem',
  props: ['info'],
  mixins: [verifiedMixin],
  computed: {
    fmtDiscount() {
      return this.utils.formatFloat(this.info.discount.replace('折', ''))
    },
    fmtApr() {
      return this.utils.formatFloat(this.info.apr.replace('%', '')) + '%'
    }
  },
  data() {
    return {}
  },
  methods: {
    goDetail(id, products) {
      const isBreak = this.showVerifiedModal()
      if (isBreak) return;
      this.$router.push({ name: 'subjectDetails', query: { id: id, products: products } })
    }
  }
}
</script>

<style lang="less" scoped>
.detail-target-container {
  border-bottom: 1px solid rgba(233, 233, 233, 1);
  margin-top: 10px;
  padding: 16px 14px 18px 15px;
  box-sizing: border-box;
  height: 110px;
  background-color: #fff;
  font-weight: 400;
  .project-name {
    font-size: 14px;
    color: #333;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }
  .valid-time {
    font-size: 13px;
    color: @themeColor;
    text-align: right;
  }

  .main-msg {
    margin-top: 12px;
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
}
</style>
