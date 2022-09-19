<template>
  <div class="target-container">
    <!-- sort -->
    <sort-tab
      :list="typeList"
      @choose-type="chooseType"
      @zh-sort="zhSort"
      @discount-sort="discountSort"
      @search="searchName"
    ></sort-tab>

    <!-- body -->
    <van-list
      v-model="loading"
      :finished="finished"
      :finished-text="finishedText"
      @load="onLoad"
      ref="datalist"
      class="list"
    >
      <target-item v-for="item in dataList" :key="item.debt_id" :info="item"></target-item>
    </van-list>
  </div>
</template>

<script>
import SortTab from './child/SortTab'
import TargetItem from './child/TargetItem'
import { getDebtList } from '../../api/mydebt'
import { getTypeList } from '../../api/projectlist'
export default {
  name: 'targetList',
  data() {
    return {
      loading: false,
      finished: false,
      typeList: [
        { value: 1, text: '尊享' },
        { value: 2, text: '普惠' },
        // { value: 3, text: '工场微金' },
        { value: 4, text: '智多新' },
        { value: 5, text: '交易所' }
      ],
      dataList: [],
      params: {
        type: 2,
        products: 1,
        name: '',
        order: 2, // 排序 1：正序2：倒序（默认2）
        field: 1, //1：综合排序 2：转让折扣 （默认1）
        page: 0,
        limit: 10
      }
    }
  },
  components: { TargetItem, SortTab },
  computed: {
    finishedText() {
      return this.dataList.length ? '没有更多了' : '暂无数据'
    }
  },
  created() {
    // this.getType()
  },
  methods: {
    getType() {
      getTypeList().then(res => {
        res.data.forEach((item, index) => {
          let obj = {}
          obj.text = item.type_name
          obj.value = item.id
          this.typeList.push(obj)
        })
      })
    },
    getList() {
      getDebtList(this.params).then(res => {
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
    searchName(name) {
      this.initList()
      this.params.name = name
      this.getList()
    },
    zhSort(val) {
      this.initList()
      this.params.field = 1
      this.params.order = val ? 1 : 2
      this.getList()
    },
    discountSort(val) {
      this.initList()
      this.params.field = 2
      this.params.order = val ? 1 : 2
      this.getList()
    },
    chooseType(val) {
      this.initList()
      this.params.products = val
      this.getList()
    },
    onLoad() {
      this.params.page += 1
      this.getList()
    },
    initList() {
      this.params.name = ''
      this.finished = false
      this.loading = true
      this.params.page = 1
      this.dataList = []
    }
  }
}
</script>
<style lang="less" scoped>
.target-container {
  display: flex;
  flex-direction: column;
}
.list {
  flex: 1;
  overflow-y: auto;
}
</style>
