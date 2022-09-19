<template>
  <div class="purchase-container">
    <!-- sort -->
    <sort-tab @zh-sort="zhSort" @discount-sort="discountSort" @see-only="seeOnly"></sort-tab>
    <!-- body -->
    <van-list v-model="loading" :finished="finished" :finished-text="finishedText" @load="onLoad">
      <purchase-item v-for="item in dataList" :key="item.pur_id" :info="item"></purchase-item>
    </van-list>
  </div>
</template>

<script>
import SortTab from './child/SortTab'
import PurchaseItem from './child/PurchaseItem'
import { getPurchaseList } from '../../api/purchaselist'
export default {
  name: 'purchaseList',
  data() {
    return {
      loading: false,
      finished: false,
      dataList: [],
      params: {
        page: 0,
        limit: 10,
        order: 1,
        field: 1,
        isable: 2
      }
    }
  },
  components: { PurchaseItem, SortTab },
  computed: {
    finishedText() {
      return this.dataList.length ? '没有更多了' : '暂无数据'
    }
  },
  created() {
    // this.getList()
  },
  methods: {
    getList() {
      getPurchaseList(this.params).then(res => {
        if (res.data.data) {
          this.dataList = [...this.dataList, ...res.data.data]
        }
        this.loading = false // 加载状态结束
        // res.data.data当没有数据返回的是null，所以不能直接用res.data.data.length判断，会报错，导致finished无法置为true
        if ((res.data.data && res.data.data.length < this.params.limit) || !res.data.data) {
          this.finished = true // 数据全部加载完成
        }
      })
    },
    zhSort(val) {
      this.initList()
      this.params.order = val ? 2 : 1
      this.params.field = 1
      this.getList()
    },
    discountSort(val) {
      this.initList()
      this.params.order = val ? 2 : 1
      this.params.field = 2
      this.getList()
    },
    seeOnly(val) {
      this.initList()
      this.params.isable = val ? 1 : 2
      this.getList()
    },
    onLoad() {
      this.params.page += 1
      this.getList()
    },
    initList() {
      this.finished = false
      this.params.page = 1
      this.dataList = []
    }
  }
}
</script>
