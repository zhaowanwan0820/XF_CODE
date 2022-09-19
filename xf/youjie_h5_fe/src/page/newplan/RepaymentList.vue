<template>
  <div class="container">
    <mt-header class="header" title="还款兑付">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="list-wrapper" v-if="show">
      <div
        class="list"
        v-for="(item, index) in list"
        :key="index"
        @click="$router.push({ name: 'newPlanVote', query: { id: item.id, type: 1 } })"
      >
        <div class="time">
          <span>{{ utils.formatDate('MM-DD HH:mm:ss', item.crtTime) }}</span>
        </div>
        <div class="panel">
          <div class="title">{{ item.title }}</div>
          <mt-cell title="" label="查看详情" is-link></mt-cell>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getRepaymentlist } from '../../api/newplane'
export default {
  name: 'RepaymentList',
  data() {
    return {
      show: true,
      list: []
    }
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    getRepayment() {
      getRepaymentlist()
        .then(res => {
          this.list = res
        })
        .catch(err => {
          this.show = false
        })
    }
  },
  created() {
    this.getRepayment()
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  background-color: #fff;
  .list-wrapper {
    padding: 0 20px;
    .list {
      /deep/.mint-cell-wrapper .mint-cell-value {
        width: auto;
      }
      .time {
        text-align: center;
        color: #fff;
        font-size: 12px;
        padding: 15px 0;
        span {
          display: inline-block;
          width: 90px;
          height: 20px;
          line-height: 20px;
          background-color: #cbcbcb;
          border-radius: 10px;
        }
      }
      .panel {
        padding: 0 10px;
        border-radius: 5px;
        box-shadow: 0px 0px 6px 0px rgba(0, 0, 0, 0.1);
        .title {
          font-size: 16px;
          font-weight: 700;
          padding: 15px 5px;
        }
        .desc {
          padding: 0 5px;
          font-size: 12px;
          color: #666;
          overflow: hidden;
          text-overflow: ellipsis;
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
        }
      }
    }
  }
}
</style>
