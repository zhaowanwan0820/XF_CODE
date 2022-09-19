<template>
  <div class="d-container">
    <div class="content">
      <div class="detail">
        <div class="detail-item">
          <span>项目名称</span><label>{{ info.name || '— —' }}</label>
        </div>
        <!-- <div class="detail-item">
          <span>年利率</span><label>{{ info.apr || '— —' }}%</label>
        </div> -->
        <div class="detail-item">
          <span>待还本金</span><label>{{ info.surplus_capital || '— —' }}</label>
        </div>
        <div class="detail-item">
          <span>还款方式</span><label>{{ info.style_cn || '— —' }}</label>
        </div>
        <div class="detail-item">
          <span>合同编号</span>
          <div class="label-container">
            <label>{{ info.contract_num || '— —' }}</label>
            <label v-if="info.contract_num" class="gray-desc">合同内容请前去机构平台查看</label>
          </div>
        </div>
      </div>

      <!--       <div class="plan-list">
        <van-tabs v-model="selected">
          <van-tab title="项目信息">
            <div class="tab-detail" v-html="info.intro || nullInfo"></div>
          </van-tab>
          <van-tab title="保障方信息">
            <div class="tab-detail protect">
              <span>保障类型：</span>
              <label>{{ PROTECTTYPS[info.type - 1] }}</label>
              <br />
              <span>保障方介绍：</span>
              <label>{{ info.companyBrief }}</label>
            </div>
          </van-tab>
        </van-tabs>
      </div> -->
    </div>
  </div>
</template>

<script>
import { Navbar, TabItem } from 'mint-ui'
import { RETURNTYPES, PROTECTTYPS } from './static.js'
import { getTitleDetail } from '../../api/confirmation.js'
export default {
  name: 'ConfirmationProjectDetail',
  data() {
    return {
      selected: '1',
      RETURNTYPES: RETURNTYPES,
      PROTECTTYPS: PROTECTTYPS,
      info: {}
    }
  },
  created() {
    this.getDetail()
  },
  computed: {
    nullInfo() {
      return `<span style="display: inline-block;width: 100%;text-align: center;">您还没有任何数据哦~</span>`
    },
    timeDw() {
      return this.info.loanType == 5 ? '天' : '个月'
    }
  },
  methods: {
    getDetail() {
      this.$loading.open()
      getTitleDetail(this.$route.params.id)
        .then(res => {
          this.info = res.data
        })
        .finally(() => {
          this.$loading.close()
        })
    }
  }
}
</script>

<style lang="less" scoped>
.d-container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.content {
  flex: 1;
  overflow: auto;
  display: flex;
  flex-direction: column;
}
.detail {
  padding: 20px 15px 15px;
  background-color: #fff;
  .detail-item {
    margin-bottom: 10px;
    display: flex;
    &:last-child {
      margin-bottom: 0;
    }
    .label-container {
      display: flex;
      flex-direction: column;
    }
    span {
      display: block;
      width: 58px;
      margin-right: 20px;
      font-size: 14px;
      font-weight: 400;
      color: #707070;
      line-height: 20px;
    }
    label {
      font-size: 14px;
      font-weight: 400;
      line-height: 20px;
      &.gray-desc {
        margin-top: 3px;
        .sc(10px, left center);
        font-weight: 400;
        line-height: 14px;
      }
    }
  }
}

.plan-list {
  flex: 1;
  overflow: auto;
  margin-top: 10px;
  background-color: #fff;
  display: flex;
  flex-direction: column;
  .item-wrapper {
    padding: 0 15px;
    border-bottom: 0.5px solid #e8e8e8;
    .item {
      padding: 15px 0 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      .left,
      .right {
        font-size: 0;
        display: flex;
        flex-direction: column;
        span {
          font-size: 14px;
          font-weight: 400;
          line-height: 20px;
        }
        label {
          font-size: 12px;
          font-weight: 400;
          color: #999;
          line-height: 17px;
        }
      }
      .right {
        align-items: flex-end;
      }
    }
  }
}
.protect {
  span {
    color: #999;
  }
}
.tab-detail {
  padding: 10px;
}
.van-tabs {
  flex: 1;
}
</style>
