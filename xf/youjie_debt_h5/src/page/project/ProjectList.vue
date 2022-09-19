<template>
  <div class="project-container">
    <!-- sort -->
    <filter-tab :list="typeList" @choose-type="chooseType" v-if="typeList.length"></filter-tab>
    <!-- body -->
    <van-list v-model="loading" :finished="finished" :finished-text="finishedText" @load="onLoad">
      <project-item v-for="item in dataList" :key="item.id" :info="item"></project-item>
    </van-list>
  </div>
</template>

<script>
import FilterTab from './child/FilterTab'
import ProjectItem from './child/ProjectItem'
import { getProjectList, getTypeList } from '../../api/projectlist'
export default {
  name: 'projectList',
  data() {
    return {
      loading: false,
      finished: false,
      typeList: [
        { value: 1, text: '尊享' },
        { value: 2, text: '普惠' }
      ],
      dataList: [],
      params: {
        products: 1,
        page: 0,
        limit: 10
      }
    }
  },
  components: { ProjectItem, FilterTab },
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
      getProjectList(this.params)
        .then(res => {
          if (res.data.data) {
            this.dataList = [...this.dataList, ...res.data.data]
          }
          this.loading = false // 加载状态结束
          // res.data.data当没有数据返回的是null，所以不能直接用res.data.data.length判断，会报错，导致finished无法置为true
          if ((res.data.data && res.data.data.length < this.params.limit) || !res.data.data) {
            this.finished = true // 数据全部加载完成
          }
        })
        .finally(() => {})
    },
    chooseType(val) {
      this.finished = false
      this.loading=true   //若不重置为true会导致触发两次请求
      this.dataList = []
      this.params.page = 1
      this.params.products = val
      this.getList()
    },
    onLoad() {
      this.params.page += 1
      this.getList()
    }
  }
}
</script>
