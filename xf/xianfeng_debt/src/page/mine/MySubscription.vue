<!-- 我的认购 -->
<template>
  <div class="subscription-list-container">
    <van-dropdown-menu>
      <van-dropdown-item v-model="params.products" :options="typeList" />
      <van-dropdown-item v-model="params.status" :options="subscriptionList" />
    </van-dropdown-menu>
    <van-list v-model="loading" :finished="finished" :finished-text="finishedText" @load="onLoad">
      <subscription-item
        :successful="index == 1"
        v-for="item in dataList"
        :key="item.debt_tender_id"
        :info="item"
        :products="params.products"
        @refresh="tabChange"
      ></subscription-item>
    </van-list>
  </div>
</template>

<script>
import { TYPE_LIST, SUBSCIPTION_TITLE_LIST } from './const'
import SubscriptionItem from './child/SubscriptionItem'
import { getSubscriptionList } from '../../api/order'
export default {
  name: 'mysubsciption',
  data() {
    return {
      typeList: TYPE_LIST,
      subscriptionList: SUBSCIPTION_TITLE_LIST,
      loading: false,
      finished: false,
      dataList: [],
      // 我的转让参数
      params: {
        products: 1,
        status: 10
      },
      page: 0,
      limit: 10,
      src: ''
    }
  },
  components: { SubscriptionItem },
  computed: {
    finishedText() {
      return this.dataList.length ? '没有更多了' : '暂无数据'
    }
  },
  watch: {
    params: {
      handler() {
        this.tabChange()
      },
      deep: true
    }
  },
  created() {},
  methods: {
    getList() {
      getSubscriptionList({ ...this.params, page: this.page, limit: this.limit })
        .then(res => {
          if (res.data.data) {
            this.dataList = [...this.dataList, ...res.data.data]
          }
          this.loading = false
          if ((res.data.data && res.data.data.length < this.limit) || !res.data.data) {
            this.finished = true
          }
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    onLoad() {
      this.page += 1
      this.getList()
    },
    tabChange() {
      this.$loading.open()
      this.dataList = []
      this.page = 1
      this.loading = true // 避免请求两次接口
      this.finished = false
      this.getList()
    }
  }
}
</script>
