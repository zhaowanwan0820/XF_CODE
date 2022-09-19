<template>
  <div
    :class="{ container: true, 'filter-fixed': filterFixed }"
    v-infinite-scroll="getMore"
    infinite-scroll-distance="10"
  >
    <!-- 筛选、排序 部分 -->
    <div class="ui-product-filter" ref="filterBlock">
      <ul class="filter-list" ref="filterList">
        <li
          class="item"
          v-for="(item, index) in SORTKEY"
          :key="item.id"
          @click="setActiveSortkey(item, index)"
          v-stat="{ id: `supplier_${index === 1 ? 'countSort' : index === 2 ? 'priceCount' : 'newProduct'}` }"
          :class="{ sortactive: item.id == currentSortData.id, sortnormal: item.id != currentSortData.id }"
        >
          <a>{{ item.name }}</a>
          <div class="sort-turn" v-if="item.isTurn">
            <div class="trun-div">
              <img
                class="arrow-icon"
                :class="{ handstand: true }"
                src="../../../assets/image/hh-icon/b0-home/icon-search-箭头-down.png"
                v-if="index == currentSortIndex && price_sort_value === SORT_VALUE.ASC"
              />
              <img
                class="arrow-icon"
                :class="{ handstand: true }"
                src="../../../assets/image/hh-icon/b0-home/icon-search-箭头灰-down.svg"
                v-else
              />
              <img
                style="margin-top: -2px;"
                class="arrow-icon"
                src="../../../assets/image/hh-icon/b0-home/icon-search-箭头-down.png"
                v-if="index == currentSortIndex && price_sort_value === SORT_VALUE.DESC"
              />
              <img
                style="margin-top: -2px;"
                class="arrow-icon"
                src="../../../assets/image/hh-icon/b0-home/icon-search-箭头灰-down.svg"
                v-else
              />
            </div>
          </div>
        </li>
      </ul>
    </div>
    <div :class="{ 'filter-placeHolder': true }" :style="{ height: this.filterBlockSize.height + 'px' }"></div>
    <!-- 商品列表部分 -->
    <div class="product-body">
      <div class="clearfix">
        <product-body :item="item" v-for="(item, index) in productList" v-bind:key="index" :productId="item.id">
        </product-body>

        <div class="loading-wrapper">
          <p v-if="!isMore && productList.length > 0">没有更多了</p>
          <mt-spinner type="triple-bounce" color="#FD9F21" v-if="isMore"></mt-spinner>
        </div>

        <div class="wrapper-list-empty" v-if="productList.length <= 0 && !isMore">
          <div>
            <img src="../../../assets/image/change-icon/empty_goods@2x.png" />
            <p>暂无任何商品</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { SORTKEY } from '../static'
import { ENUM } from '../../../const/enum'
import { productList } from '../../../api/product'
import productBody from './ProductListBody'

export default {
  data() {
    return {
      SORTKEY: SORTKEY, // 排序数据
      SORT_VALUE: ENUM.SORT_VALUE,
      id: this.$route.query.id ? this.$route.query.id : null,
      currentSortData: SORTKEY[0], // 当前选中的排序数据(预设索引0的数据)
      currentSortIndex: 0, // 当前选中的排序，所在的数组索引
      price_sort_value: ENUM.SORT_VALUE.ASC, //默认升序排列

      productList: [],
      isMore: true, //是否有更多
      page: 1,
      loading: false, //是否在加载数据

      element: null,

      filterBlockSize: { height: 0 } // filter区域的尺寸
    }
  },
  props: {
    filterFixed: Boolean
  },
  components: {
    productBody
  },
  created() {
    this.loading = true
    this.getList()
  },
  mounted() {
    this.filterBlockSize.height = this.$refs.filterBlock.getBoundingClientRect().height
  },
  methods: {
    getList(isFirst) {
      if (isFirst) {
        this.dataInit()
      }
      let data = {}
      data['page'] = this.page
      data['per_page'] = 10
      data['shop'] = this.id
      data['sort_key'] = this.currentSortData.key
      data['sort_value'] = this.currentSortData.value
      if (this.currentSortData.id == 2) {
        data['sort_value'] = this.price_sort_value
      }
      productList(data).then(
        res => {
          if (this.productList.length) {
            this.productList = [...this.productList, ...res.list]
          } else {
            this.productList = res.list
          }
          this.isMore = res.paged.more == 1 ? true : false
          this.loading = false
        },
        error => {
          console.log(error)
        }
      )
    },
    setActiveSortkey(item, index) {
      if (this.currentSortIndex != index) {
        this.currentSortIndex = index
        this.currentSortData = this.SORTKEY[index]
        this.price_sort_value = ENUM.SORT_VALUE.ASC
      } else {
        if (this.price_sort_value === ENUM.SORT_VALUE.DESC) {
          this.price_sort_value = ENUM.SORT_VALUE.ASC
        } else {
          this.price_sort_value = ENUM.SORT_VALUE.DESC
        }
      }
      this.page = 1
      this.productList = []
      this.getList()
    },
    getMore() {
      if (this.loading) return
      if (this.isMore) {
        this.page += 1
        this.loading = true
        this.getList()
      }
    },

    /**
     * 初始化数据
     * 当页面再次出现并且切换了商家
     */
    dataInit() {
      this.id = this.$route.query.id ? this.$route.query.id : null
      this.currentSortData = SORTKEY[0]
      this.currentSortIndex = 0
      this.price_sort_value = ENUM.SORT_VALUE.ASC
      this.isMore = true
      this.page = 1
      this.loading = false
      this.productList = []
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-product-filter {
  background-color: #fff;
  ul.filter-list {
    display: flex;
    padding: 0 20px;
    justify-content: space-around;
    align-content: center;
    align-items: center;
    border: 0;
    li {
      font-size: 14px;
      color: #4e545d;
      position: relative;
      flex-basis: 110px;
      text-align: center;
      height: 40px;
      padding: 0;
      line-height: 40px;
      a {
        height: 30px;
        line-height: 30px;
        display: inline-block;
      }
      img {
        height: 4px;
        width: 8px;
        vertical-align: middle;
      }
    }
    li.sortactive {
      border-bottom-color: $primaryColor;
      a {
        color: $markColor;
      }
    }
    li.sortnormal {
      border-bottom-color: transparent;
      a {
        color: #888888;
      }
    }
    .arrow-icon {
      width: 10px;
      height: 9px;
    }
    .sort-turn {
      width: 10px;
      display: inline-block;
      position: relative;
      .trun-div {
        top: -13px;
        position: absolute;
      }
      img {
        float: left;
      }
    }
    .handstand {
      transform: rotate(180deg);
    }
  }
  .goTop {
    width: 42px;
    height: 42px;
    position: fixed;
    right: 14px;
    bottom: 92px;
  }
}
.filter-placeHolder {
  display: none;
}
.container {
  &.filter-fixed .ui-product-filter {
    z-index: 10001;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
  }
  &.filter-fixed .filter-placeHolder {
    display: block;
  }
}
// 商品列表部分
.product-body {
  position: relative;
  width: 100%;
  background-color: #fff;

  .loading-wrapper {
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 44px;
    p {
      color: #7c7f88;
      font-size: 12px;
      font-weight: 'Regular';
      padding: 0;
      margin: 0;
    }
    span {
      display: inline-block;
    }
    /deep/ .mint-spinner-triple-bounce-bounce1,
    /deep/ .mint-spinner-triple-bounce-bounce2,
    /deep/ .mint-spinner-triple-bounce-bounce3 {
      background-color: #f0f0f0 !important;
    }
  }
  .wrapper-list-empty {
    display: flex;
    justify-content: center;
    align-content: center;
    align-items: center;
    padding-top: 25%;
    div {
      display: flex;
      flex-direction: column;
      align-items: center;
      img {
        width: 75px;
        height: 75px;
      }
      p {
        text-align: center;
        margin-top: 27px;
        color: #a4aab3;
      }
    }
  }
}
</style>
