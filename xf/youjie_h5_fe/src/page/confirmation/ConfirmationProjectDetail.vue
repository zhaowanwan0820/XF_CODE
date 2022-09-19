<template>
  <div class="d-container">
    <mt-header class="header" title="标的详情">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <div class="detail">
        <div class="detail-item">
          <span>项目名称</span><label>{{ info.titleName || '— —' }}</label>
        </div>
        <div class="detail-item">
          <span>年利率</span><label>{{ info.rate || '— —' }}%</label>
        </div>
        <div class="detail-item">
          <span>所有权益</span><label>{{ info.money || '— —' }}</label>
        </div>
        <!-- <div class="detail-item">
          <span>合同编号</span>
          <div class="label-container">
            <label>{{ info.contractNum || '— —' }}</label>
            <label v-if="info.contractNum" class="gray-desc">合同内容请前去机构平台查看</label>
          </div>
        </div> -->
        <div class="detail-item">
          <span>期限</span><label>{{ info.repayTime || '— —' }}{{ timeDw }}</label>
        </div>
        <div class="detail-item">
          <span>还款方式</span><label>{{ RETURNTYPES[info.loanType - 1] || '— —' }}</label>
        </div>
        <div class="detail-item">
          <span>借款人</span><label>{{ info.borrowName || '— —' }}</label>
        </div>
      </div>

      <div class="plan-list">
        <mt-navbar v-model="selected">
          <!-- <mt-tab-item id="1">资产收支明细</mt-tab-item> -->
          <mt-tab-item id="2">项目信息</mt-tab-item>
          <mt-tab-item id="3">保障方信息</mt-tab-item>
        </mt-navbar>

        <!-- tab-container -->
        <mt-tab-container v-model="selected">
          <!-- <mt-tab-container-item id="1">
            <div class="tab-detail" v-html="info.list"></div>
          </mt-tab-container-item> -->
          <mt-tab-container-item id="2">
            <div class="tab-detail" v-html="info.intro || nullInfo"></div>
          </mt-tab-container-item>
          <mt-tab-container-item id="3">
            <div class="tab-detail protect">
              <span>保障类型：</span>
              <label>{{ PROTECTTYPS[info.type - 1] }}</label>
              <br />
              <span>保障方介绍：</span>
              <label>{{ info.companyBrief }}</label>
            </div>
          </mt-tab-container-item>
        </mt-tab-container>
      </div>
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
      let data = {
        id: this.$route.params.id,
        type: this.$route.params.type
      }
      this.$indicator.open()
      getTitleDetail(data)
        .then(res => {
          this.info = res
        })
        .finally(() => {
          this.$indicator.close()
        })
    },
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
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
        @include sc(10px, #848a95, left center);
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
  // tab样式开始
  .mint-tab-container {
    flex: 1;
    overflow: auto;
  }
  .mint-navbar {
    margin-bottom: 10px;
    justify-content: space-around;
  }
  .mint-tab-item {
    flex-grow: 0;
    flex-basis: auto;
    padding: 11px 0;
    &.is-selected {
      color: $markColor;
      border-bottom: $markColor 2px solid;
    }
    .mint-tab-item-label {
      font-size: 12px;
      font-weight: 400;
      color: #666;
      line-height: 17px;
    }
  }
  // tab样式结束
  .item-wrapper {
    padding: 0 15px;
    @include thin-border(#e8e8e8);
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
  @include sc(14px, #666);
  line-height: 2em;
}
</style>
