<!-- 我的认购 -->
<template>
  <div class="transfer-list-container">
    <van-dropdown-menu>
      <van-dropdown-item v-model="params.products" :options="typeList" />
      <van-dropdown-item v-model="params.status" :options="transerList" />
    </van-dropdown-menu>
    <van-list v-model="loading" :finished="finished" :finished-text="finishedText" @load="onLoad">
      <transfer-item
        v-for="item in dataList"
        :key="item.debt_id"
        :info="item"
        :products="params.products"
        @refresh="tabChange"
      ></transfer-item>
    </van-list>
  </div>
</template>

<script>
import { TYPE_LIST, TRANSFER_TITLE_LIST ,AREA_TYPE_LIST,AREA_TRANSFER_TITLE_LIST } from './const'
import TransferItem from './child/TransferItem'
import { getTransferDebt } from '../../api/order'
export default {
  name: 'mytransfer',
  data() {
    return {
      typeList: TYPE_LIST,
      transerList: TRANSFER_TITLE_LIST,
      loading: false,
      finished: false,
      dataList: [],
      // 我的转让参数
      params: {
        products: 1,
        status: 0,
        area_id:0,
      },
      page: 0,
      limit: 10,
      src: ''
    }
  },
  components: { TransferItem },
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
  created() {
    if(this.$route.query.area_id == 1){
      this.params.area_id = this.$route.query.area_id 
      this.typeList =  AREA_TYPE_LIST;
      this.transerList = AREA_TRANSFER_TITLE_LIST;
      this.params.products = 2;
      this.params.status = 0;
    }
   
  },
  methods: {
    getList() {
      getTransferDebt({ ...this.params, page: this.page, limit: this.limit })
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
      this.loading = true
      this.finished = false
      this.getList()
    }
  }
}
</script>
