<template>
  <div class="container">
    <mt-header class="header" fixed title="收支明细">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <top-list class="topList" :items="tabs" v-on:onIndexChange="onIndexChange"> </top-list>
    <base-list class="items-wrapper" :items="items" :isMore="isMore" :isLoaded="isLoaded" v-on:loadMore="loadMore">
      <balance-item
        v-for="(item, index) in items"
        :key="index"
        :class="{ 'section-header': index == 0, 'section-footer': index == Object.keys(items).length - 1 }"
        :item="item"
        @showItemDetail="getDetail"
      >
      </balance-item>
    </base-list>
    <div class="empty" v-if="isLoaded && items.length <= 0">
      <img src="../../assets/image/hh-icon/l0-list-icon/balance-list.png" alt="" />
      <p>暂无收支明细</p>
    </div>
  </div>
</template>

<script>
import { HeaderItem, TopList, BaseList } from '../../components/common'
import { Indicator, Toast } from 'mint-ui'
import BalanceItem from './child/BalanceItem'
import { balanceList } from '../../api/balance'
import { bondDetail } from '../../api/bond'
import { ENUM } from '../../const/enum'
export default {
  name: 'balanceHistory',
  components: {
    BalanceItem
  },
  data() {
    return {
      balance: 0,
      currentIndex: 0,
      tabs: [
        {
          id: 2,
          title: '收入'
        },
        {
          id: 3,
          title: '支出'
        }
      ],
      isLoaded: false,
      page: 1,
      items: [],
      isMore: 0
    }
  },
  created() {
    this.loadFirstPageData()
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    loadFirstPageData() {
      this.loadPageData(true)
    },
    loadMorePageData() {
      this.loadPageData(false)
    },
    loadPageData(isFirstPage) {
      this.page = isFirstPage ? 1 : this.page + 1
      let per_page = 10
      let status = null
      if (this.currentIndex === 0) {
        status = ENUM.BALANCE_STATUS.INCOME
      } else if (this.currentIndex === 1) {
        status = ENUM.BALANCE_STATUS.EXPENDITURE
      }

      balanceList(status, this.page, per_page).then(res => {
        for (let index in res.list) {
          res.list[index].detail = []
        }
        if (isFirstPage) {
          this.items = res.list
        } else {
          this.items = [...this.items, ...res.list]
        }
        this.isMore = res.paged.more
        this.isLoaded = true
      })
    },
    onIndexChange(index) {
      this.currentIndex = index
      this.loadFirstPageData()
    },
    getDetail(item) {
      bondDetail(item.id).then(res => {
        item.detail = res
      })
    },
    loadMore() {
      if (this.isMore) {
        this.loadMorePageData()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  overflow: hidden;
  display: flex;
  position: relative;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  background-color: #ffffff;
}
.header {
  @include header;
  @include thin-border();
}
.topList {
  position: fixed;
  width: 56%;
  margin-top: 44px;
  height: 31px;
  padding: 10px 22% 6px;
  z-index: 100;
  border-bottom: 1px solid $lineColor;
}
.items-wrapper {
  margin-top: 100px;
  overflow: auto;
}
.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  img {
    width: 135px;
    height: 135px;
    margin-top: 30px;
  }
  p {
    font-size: 18px;
    color: #666666;
    line-height: 25px;
    margin-top: 15px;
  }
}
</style>
