<template>
  <div class="container">
    <div class="discount">
      <p class="show-tip">您的可转让在途项目(不含正在交易中的项目):</p>
      <span>求购折扣：{{ this.utils.formatFloat(discount) }}折</span>
    </div>
    <div class="debt-list">
      <van-list v-model="loading" :finished="finished" :finished-text="finishedText" @load="onLoad">
        <debt-item v-for="item in debtList" :key="item.tender_id" :item="item"></debt-item>
      </van-list>
    </div>
    <transfer-footer></transfer-footer>
  </div>
</template>
<script>
import DebtItem from './child/DebtItem'
import TransferFooter from './TransferFooter'
import { mapState, mapMutations } from 'vuex'
import { getDebtList } from '../../api/transfer.js'

export default {
  name: 'TransferDebt',
  data() {
    return {
      pur_id: this.$route.params.id,
      discount: '', //折扣
      loading: false, //正在加载
      finished: false, //是否完成
      expected: '',
      total: 0
    }
  },
  components: {
    TransferFooter,
    DebtItem
  },
  created() {
    this.getList()
  },
  computed: {
    ...mapState({
      debtList: state => state.transfer.debtList
    }),
    finishedText() {
      return this.debtList.length ? '没有更多了' : '暂无数据'
    }
  },
  methods: {
    ...mapMutations({
      saveDebtList: 'saveDebtList',
      clearDebtList: 'clearDebtList',
      saveDebtInfo: 'saveDebtInfo',
      clearDebtInfo: 'clearDebtInfo'
    }),
    getList() {
      if (this.loading) return
      this.loading = true
      getDebtList(this.pur_id)
        .then(
          res => {
            this.discount = res.data.discount ? res.data.discount : this.$route.query.discount
            this.saveDebtList(res.data.list)
            this.saveDebtInfo(res.data)
          },
          err => {
            console.log(err)
          }
        )
        .finally(() => {
          this.loading = false
        })
    },
    onLoad() {
      console.log('onLoad')
      this.loading = false

      // 数据全部加载完成(没有下一页啦)
      if (this.debtList.length >= this.total) {
        this.finished = true
      }
    },
    goBack() {
      this.$router.go(-1)
    }
  },
  beforeDestroy() {
    this.clearDebtList()
    this.clearDebtInfo()
  }
}
</script>
<style lang="less" scoped>
.container {
  display: flex;
  flex-direction: column;
}
.discount {
  width: 100%;
  height: 44px;
  display: flex;
  align-items: center;
  background-color: #fff;
  .show-tip {
    color: #999;
    font-size: 14px;
    padding-left: 15px;
    flex: 1;
  }
  span {
    font-size: 16px;
    color: @themeColor;
    line-height: 22px;
    display: inline-block;
    width: 130px;
    white-space: nowrap;
  }
}

.debt-list {
  flex: 1;
  overflow: auto;
}
</style>
