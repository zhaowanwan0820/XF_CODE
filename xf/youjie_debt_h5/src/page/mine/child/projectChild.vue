<template>
  <div class="container">
    <!--        <div class="message" v-if="childList.length==0">暂无数据</div>-->
    <van-list v-model="loading" :finished="finished" :finished-text="finishedText" @load="onLoad">
      <div class="box" v-for="(item, index) in childList" :key="index">
        <div class="title">{{ item.name ? item.name : '--' }}</div>
        <div class="content">
          <div class="content-box">
            <p>{{ item.surplus_capital ? utils.toThousands(item.surplus_capital) : '--' }}</p>
            <span>待还本金(元)</span>
          </div>
          <!--          <div class="content-box">-->
          <!--            <p>{{ item.apr ? parseFloat(item.apr) : '&#45;&#45;' }}%</p>-->
          <!--            <span>年化率</span>-->
          <!--          </div>-->
          <div class="content-box">
            <p>{{ item.account_init ? utils.toThousands(item.account_init) : '--' }}</p>
            <span>出借金额(元)</span>
          </div>
        </div>
      </div>
    </van-list>
  </div>
</template>

<script>
import { getConfirmed } from '../../../api/mine'
import { Toast } from 'vant'
export default {
  name: 'projectChild',
  props: ['listArr'],
  data() {
    return {
      loading: false,
      finished: false,
      childList: [],
      params: {}
    }
  },
  computed: {
    finishedText() {
      return this.childList.length ? '没有更多了' : '暂无数据'
    }
  },
  created() {
    this.params = { ...this.params, ...this.listArr }
  },
  methods: {
    onLoad() {
      this.params.page += 1
      this.getData()
    },
    getData() {
      console.log(this.params)
      getConfirmed(this.params)
        .then(res => {
          if (res.code == 0) {
            this.childList = [...this.childList, ...res.data.list]
            this.loading = false // 加载状态结束
            if (res.data.list.length < this.params.size) {
              this.finished = true // 数据全部加载完成
            }
          }
        })
        .catch(err => {})
    }
  }
}
</script>

<style lang="less" scoped>
.container {
  background-color: #fff;
  padding: 0 18px;
  .message {
    text-align: center;
    padding-top: 50px;
  }
  .box {
    padding-bottom: 19px;
    padding-top: 15px;
    border-bottom: 1px solid rgba(233, 233, 233, 1);
    .title {
      height: 17px;
      font-size: 12px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
      line-height: 17px;
      margin-bottom: 9px;
    }
    .content {
      display: flex;
      .content-box {
        p {
          height: 22px;
          font-size: 16px;
          font-family: PingFangSC-Medium, PingFang SC;
          font-weight: 500;
          color: rgba(51, 51, 51, 1);
          line-height: 22px;
        }
        span {
          height: 16px;
          font-size: 11px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(153, 153, 153, 1);
          line-height: 16px;
        }
      }
      .content-box:nth-child(1) {
        flex: 1;
        margin-left: 17px;
      }
      .content-box:nth-child(2) {
        flex: 1;
      }
      .content-box:nth-child(3) {
        flex: 1;
        text-align: right;
      }
    }
  }
}
</style>
