<template>
  <div>
    <switch-tab></switch-tab>
    <van-tabs v-model="active" @change="tabChange" swipeable>
      <van-tab v-for="(title, index) in titleList" :title="title" :key="index">
        <van-list v-model="loading" :finished="finished" :finished-text="finishedText" @load="onLoad">
          <div class="item-list" v-for="i in list" :key="i.debt_id">
            <van-cell :value="i.name" />
            <div class="panel">
              <van-row>
                <van-col span="24"
                  ><span class="title">合同号：</span> <span class="item-value">{{ i.bond_no }}</span></van-col
                >
              </van-row>
              <van-row>
                <van-col span="24"
                  ><span class="title">债转编号：</span> <span class="item-value">{{ i.serial_number }}</span></van-col
                >
              </van-row>
              <van-row>
                <van-col span="24"
                  ><span class="title">转让金额：</span> <span class="item-value">{{ i.amount }}</span></van-col
                >
                <van-col span="24"
                  ><span class="title">转让价格：</span
                  ><span class="item-value">{{ i.money }}({{ i.discount }})</span></van-col
                >
              </van-row>
              <!-- <van-row>
                <van-col span="24"
                  ><span class="title">年利率：</span> <span class="item-value">{{ i.apr }}</span></van-col
                >
              </van-row> -->
            </div>
            <div class="opt-btn" v-show="active != 2">
              <van-row>
                <van-col span="16">
                  <div v-show="active == 0">
                    <span class="period">剩余有效期： </span>
                    <span class="item-value">{{ i.remaining_time.replace('日', '天') }}</span>
                  </div>
                  <div v-show="active == 1">
                    <span class="period">成交时间： </span> <span class="item-value">{{ i.success_time }}</span>
                  </div>
                </van-col>
                <van-col span="8" style="text-align: right;padding-right: 20px;">
                  <van-button type="primary" size="small" v-show="active == 0" @click="cancel(i.debt_id)"
                    >取消</van-button
                  >
                  <!--                  <van-button type="primary" size="small" v-show="active == 1" @click="viewPDF(i.debt_id)">债转合同</van-button>-->
                  <van-button type="primary" size="small" v-show="active == 3" @click="republish(i.debt_id)"
                    >重新发布</van-button
                  >
                </van-col>
              </van-row>
            </div>
          </div>
        </van-list>
      </van-tab>
    </van-tabs>
    <van-popup v-model="show" closeable position="left" :style="{ width: '100%', height: '100%' }">
      <iframe :src="src" frameborder="0" style="width: 100%;height: 100%;"></iframe>
    </van-popup>
  </div>
</template>

<script>
import { getDebtList, cancelDebt, republish, viewPDF } from '../../api/mydebt'
import SwitchTab from '../../components/common/SwitchTab'
export default {
  name: 'MyDebt',
  data() {
    return {
      show: false,
      src: '',
      active: 0,
      titleList: ['发布中', '已成交', '已取消', '已过期'],
      list: [],
      loading: false,
      finished: false,
      page: 0, //onload会加1所以这里必须是0
      limit: 10
    }
  },
  components: { SwitchTab },
  computed: {
    finishedText() {
      return this.list.length ? '没有更多了' : '暂无数据'
    }
  },
  methods: {
    tabChange() {
      this.page = 1
      this.list = []
      this.getList()
    },
    cancel(id) {
      this.$dialog
        .confirm({
          title: '取消',
          message: '确定取消吗?'
        })
        .then(() => {
          cancelDebt({ debt_id: id }).then(res => {
            if (res.code == 0) {
              this.page = 1
              this.list = []
              this.getList()
            }
            this.$toast(res.info)
          })
        })
        .catch(() => {
          // on cancel
        })
    },
    republish(id) {
      this.$dialog
        .confirm({
          title: '重新发布',
          message: '确定重新发布吗?'
        })
        .then(() => {
          republish({ debt_id: id }).then(res => {
            if (res.code == 0) {
              this.$toast(res.info)
              this.page = 1
              this.list = []
              this.getList()
            } else {
              this.$toast(res.info)
            }
          })
        })
        .catch(() => {
          // on cancel
        })
    },
    viewPDF(id) {
      viewPDF({ debt_id: id }).then(res => {
        if (res.code == 0) {
          if (res.data.c_viewpdf_url) {
            this.show = true
            this.src = res.data.c_viewpdf_url
          } else {
            this.$toast('没有债转合同!')
          }
        } else {
          this.$toast(res.info)
        }
      })
    },
    getList() {
      getDebtList({
        type: 1,
        status: this.active + 1,
        page: this.page,
        limit: this.limit
      }).then(res => {
        if (res.code !=0 ) {
          this.list = []
          this.loading = false
          this.finished = true
        } else {
          this.list = [...this.list, ...res.data.data]
          this.loading = false // 加载状态结束
          if (res.data.data.length < this.limit) {
            this.finished = true // 数据全部加载完成
          }
        }
      })
    },
    onLoad() {
      this.page += 1
      this.getList()
    }
  },
  created() {}
}
</script>

<style lang="less" scoped>
.item-list {
  margin-top: 10px;
  .item-value {
    font-weight: 700;
  }
  .panel {
    background-color: #fff;
    padding: 15px;
    .title {
      display: inline-block;
      line-height: 32px;
      width: 75px;
      text-align: right;
    }
  }
  .opt-btn {
    background-color: #fff;
    padding-left: 16px;
    line-height: 60px;

    &:before {
      content: '';
      display: block;
      border-top: 1px solid #ebedf0;
      transform: scaleY(0.5);
    }
  }
}
.van-cell__value--alone {
  color: #888;
  font-size: 14px;
  font-weight: 700;
}
</style>
