<!-- 我的认购 -->
<template>
  <div class="transfer-list-container">
    <switch-tab @change-project="changeProject"></switch-tab>
    <van-tabs :swipe-threshold="5" v-model="active" @change="tabChange" sticky :offset-top="46.5">
      <van-tab style="flex-basis: auto !important" v-for="(title, index) in titleList" :key="index" :title="title">
        <van-list v-model="loading" :finished="finished" :finished-text="finishedText" @load="onLoad">
          <transfer-item
            v-for="item in dataList"
            :key="item.debt_id"
            :info="item"
            :products="params.products"
            @refresh="tabChange"
          ></transfer-item>
        </van-list>
      </van-tab>
    </van-tabs>
  </div>
</template>

<script>
import { TRANSFER_TITLE_LIST, TRANSFER_TITLE_CODE } from './const'
import TransferItem from './child/TransferItem'
import { getTransferDebt } from '../../api/order'
import SwitchTab from '../../components/common/SwitchTab'
export default {
  name: 'mytransfer',
  data() {
    return {
      active: 0,
      titleList: TRANSFER_TITLE_LIST,
      loading: false,
      finished: false,
      dataList: [],
      // 我的转让参数
      params: {
        products: 1,
        status: 1,
        page: 0,
        limit: 10
      },
      src: ''
    }
  },
  components: { TransferItem, SwitchTab },
  computed: {
    finishedText() {
      return this.dataList.length ? '没有更多了' : '暂无数据'
    },
    source() {
      return this.params.products
    }
  },
  watch: {
    source() {
      this.tabChange()
    }
  },
  created() {},
  methods: {
    getList() {
      getTransferDebt(this.params)
        .then(res => {
          if (res.data.data) {
            this.dataList = [...this.dataList, ...res.data.data]
          }
          this.loading = false
          if ((res.data.data && res.data.data.length < this.params.limit) || !res.data.data) {
            this.finished = true
          }
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    onLoad() {
      this.params.page += 1
      this.getList()
    },
    tabChange() {
      this.$loading.open()
      this.dataList = []
      this.params.page = 1
      this.loading = true
      this.finished = false
      this.params.status = TRANSFER_TITLE_CODE[this.active]
      this.getList()
    },
    changeProject(val) {
      this.params.products = val
    }
  }
}
</script>

<style lang="less" scoped>
.transfer-list-container {
  /deep/ .van-tabs__nav {
    padding: 0 7px 15px 7px;
    display: flex;
    justify-content: space-between;
    /deep/ .van-tab {
      padding: 0 8px;
      width: fit-content;
      width: -webkit-fit-content;
      width: -moz-fit-content;
      flex: inherit;
      -webkit-flex: inherit;
    }
  }
  /deep/ .van-tabs__content {
    margin-top: 46.5px;
  }
}
</style>
