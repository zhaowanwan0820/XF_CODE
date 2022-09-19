<template>
  <div class="content">
    <template v-if="!isBatch">
      <div class="filter-wrapper">
        <div
          class="filter-item"
          v-for="item in SORTKEY"
          :key="item.id"
          @click="PickFilter(item)"
          :class="{ 'filter-item-pick': item.key == select.sort_key }"
        >
          <label>{{ item.name }}</label>
          <div class="turn" v-show="item.key == select.sort_key">
            <img
              class="arrow-icon"
              src="../../../assets/image/hh-icon/b0-home/icon-search-箭头-down.png"
              v-if="select.sort_value === SORT_VALUE.ASC"
            />
            <img
              class="arrow-icon handstand"
              src="../../../assets/image/hh-icon/b0-home/icon-search-箭头-down.png"
              v-if="select.sort_value === SORT_VALUE.DESC"
            />
          </div>
        </div>
        <div class="batch-btn-wrapper">
          <button>
            <label @click="batch()">批量上架</label>
          </button>
        </div>
      </div>
    </template>
    <template v-else>
      <div class="batch-wrapper">
        <button>
          <label @click="cancel()">取消</label>
        </button>
        <button class="deep" @click="upToShell">
          <label>上架({{ listLength }})</label>
        </button>
      </div>
    </template>

    <div class="list-wrapper">
      <div class="ul-wrapper">
        <ul class="category-wrapper scroll-container-keepAlive">
          <li class="category-item" @click="PickCategory(0)" :class="{ 'category-item-pick': 0 == select.cat_id }">
            <div class="left"></div>
            <label>热卖</label>
          </li>
          <li
            class="category-item"
            v-if="category_list.length"
            v-for="item in category_list"
            :key="item.id"
            @click="PickCategory(item.id)"
            :class="{ 'category-item-pick': item.id == select.cat_id }"
          >
            <div class="left"></div>
            <label>{{ item.name }}</label>
          </li>
        </ul>
      </div>

      <div class="filter">
        <!-- 商品列表 -->
        <div class="product-list scroll-container-keepAlive" v-infinite-scroll="getMore" infinite-scroll-distance="10">
          <div
            class="list-item"
            v-for="(item, index) in product_list"
            :key="index"
            @click="selectItem(item.id, item.is_exist, index)"
          >
            <div class="item-info" @click="goMlmProdut(item.id)">
              <img
                v-lazy="{
                  src: item.thumb,
                  error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
                  loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
                }"
              />
              <div class="info-r">
                <p>{{ item.name }}</p>
                <div class="price">
                  <span class="icon">￥</span>
                  <span class="price-num">{{ utils.formatFloat(item.price) }}</span>
                  <div class="price-wrapper">
                    <!-- <label class="left"></label>
                    <label class="high-price">
                      <span>最高赚￥{{ utils.formatFloat(item.rebate_price) }}</span>
                    </label>
                    <label class="right"></label> -->
                    <div>
                      <span>最高赚￥{{ utils.formatFloat(item.rebate_price) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="btn-wrapper">
              <span>销量{{ item.sales_count }}</span>
              <template v-if="!isBatch">
                <label class="no-batch">
                  <button><span @click="addGoods(item.id)">分销单品</span></button>
                  <button class="d" v-if="!item.is_exist"><span @click="addInShop(item.id)">加入小店</span></button>
                  <button class="a" v-else><span>已加入</span></button>
                </label>
              </template>
              <template v-else>
                <div class="checkout" v-if="!item.is_exist">
                  <label :for="index" :class="{ select: checkedObj[item.id] }"></label>
                </div>
                <span v-else>已加入</span>
              </template>
            </div>
          </div>

          <div class="loading-wrapper">
            <p v-if="!isMore && product_list.length > 0">没有更多了</p>
            <mt-spinner type="triple-bounce" color="#FD9F21" v-if="isMore"></mt-spinner>
          </div>

          <div class="wrapper-list-empty" v-if="product_list.length <= 0 && !isMore">
            <div>
              <img src="../../../assets/image/hh-icon/empty-list-icon.png" />
              <p>暂无任何商品</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { getPickProductList, getCategory, lotsOnShelf } from '../../../api/huanhuanke'

import { ENUM } from '../../../const/enum'
import { SORTKEY } from '../static'
import { Indicator, Toast } from 'mint-ui'
import { mapState, mapMutations, mapActions } from 'vuex'

export default {
  name: 'GoodsList',
  props: {
    keyword: {
      type: String
    }
  },
  data() {
    return {
      SORT_VALUE: ENUM.SORT_VALUE,
      SORTKEY: SORTKEY,
      select: {
        cat_id: 0, //商品分类
        sort_key: 0, //1推荐数 2佣金数 3销量 4新品
        sort_value: 1, //升、降序
        page: 1,
        per_page: 10,
        product: ''
      },
      category_list: [], //分类列表
      product_list: [],
      isMore: true, //是否有更多
      loading: false, //是否在加载数据
      isBatch: false, //是否批量上架
      checkedObj: {}, //选中的商品
      hasCheckIndex: [] //当前页面选中加入的商品
    }
  },
  created() {
    this.getCategoryList()
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline
    }),
    listLength() {
      return Object.keys(this.checkedObj).length
    }
  },
  methods: {
    ...mapMutations({
      saveId: 'saveId',
      setPopup: 'setPopup'
    }),
    // 获取分类列表
    getCategoryList() {
      getCategory().then(
        res => {
          this.category_list = res
          if (res.length) {
            this.resetFilter()
            this.loading = true
            this.getList()
          }
        },
        error => {}
      )
    },
    resetFilter() {
      this.select.sort_key = -1 // 默认不要用户自定义的排序，走系统的推荐排序
      this.select.sort_value = -1
      this.select.page = 1
    },
    PickCategory(id) {
      if (this.select.cat_id === id) return
      this.product_list = []
      this.select.cat_id = id
      this.resetFilter()
      this.getList()
      this.hasCheckIndex = []
    },
    PickFilter(item) {
      if (item.key != this.select.sort_key) {
        this.select.sort_key = item.key
        this.select.sort_value = item.value
      } else {
        if (this.select.sort_value == this.SORT_VALUE.DESC) {
          this.select.sort_value = this.SORT_VALUE.ASC
        } else {
          this.select.sort_value = this.SORT_VALUE.DESC
        }
      }
      this.loading = false
      this.getList(true)
    },
    getList(isReset, selectId) {
      isReset ? (this.select.page = 1) : ''
      let data = {}
      data = { ...this.select }
      data['keyword'] = this.keyword
      if (selectId) {
        data['page'] = 1
        data['product'] = selectId
      }
      Indicator.open()
      getPickProductList(data)
        .then(
          res => {
            if (selectId) {
              this.product_list.forEach(val => {
                if (val.id === selectId) {
                  val.is_exist = res.list[0].is_exist
                }
              })
            } else {
              res.list.forEach(val => {
                val.checked = this.checkedObj[val.id] ? true : false
              })
              if (this.product_list.length && !isReset) {
                this.product_list = [...this.product_list, ...res.list]
              } else {
                this.product_list = res.list
              }
              this.isMore = res.paged && res.paged.more == 1 ? true : false
            }
            this.loading = false
          },
          error => {
            console.log(error)
          }
        )
        .finally(() => {
          Indicator.close()
        })
    },
    getMore() {
      if (this.loading || !this.select.cat_id) return
      if (this.isMore) {
        this.select.page += 1
        this.loading = true
        this.getList()
      }
    },
    addGoods(id) {
      if (this.isOnline) {
        this.$router.push({ name: 'huankeShareCheckout', query: { id: id, is_single: 1 } })
      } else {
        this.$router.push({ name: 'login' })
      }
    },
    addInShop(id) {
      if (this.isOnline) {
        this.saveId(id)
        this.$router.push({ name: 'huankeShareCheckout', query: { id: id, isShop: 1 } })
      } else {
        this.$router.push({ name: 'login' })
      }
    },
    goMlmProdut(id) {
      if (this.isBatch) return
      this.$router.push({ name: 'sharerDetail', query: { id: id } })
    },
    selectItem(id, is_exist, index) {
      if (is_exist) return
      this.changeStatus(id, index)
      this.product_list[index].checked = !this.product_list[index].checked
    },
    batch() {
      this.isBatch = true
    },
    cancel() {
      this.isBatch = false
      this.checkedObj = {}
    },
    close() {
      this.isBatch = false
      this.hasCheckIndex.forEach(index => {
        this.product_list[index].is_exist = true
      })
      Toast('商品已上架')
    },
    changeStatus(id, index) {
      let obj = { ...this.checkedObj }
      let arr = [...this.hasCheckIndex]
      let isAdd = obj[id]
      if (isAdd) {
        delete obj[id]
        let index0 = arr.findIndex((val, ind) => {
          if (val === index) return ind
        })
        arr.splice(index0, 1)
      } else {
        obj[id] = id
        arr.push(index)
      }
      this.checkedObj = { ...obj }
      this.hasCheckIndex = [...arr]
    },
    upToShell() {
      let arr = [...Object.keys(this.checkedObj)]
      if (!arr.length) return
      lotsOnShelf(arr).then(
        res => {
          this.setPopup(true)
          this.checkedObj = {}
        },
        error => {
          console.log(error)
        }
      )
    }
  }
}
</script>

<style lang="scss" scoped>
.content {
  height: 100%;
  display: flex;
  flex-direction: column;
  .filter-wrapper {
    width: 100%;
    height: 42px;
    display: flex;
    align-items: center;
    .filter-item {
      font-size: 14px;
      font-weight: 400;
      color: #888;
      line-height: 20px;
      padding: 0 15px;

      display: flex;
      align-items: center;
      label {
        margin-right: 4px;
      }
      .turn {
        display: flex;
        align-items: center;
        justify-content: center;
        .arrow-icon {
          width: 7px;
          height: 7px;
        }
        .handstand {
          transform: rotate(180deg);
        }
      }
    }
    .filter-item-pick {
      color: #552e20;
    }
    .batch-btn-wrapper {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      button {
        width: 70px;
        height: 24px;
        background-color: #fff;
        border-radius: 2px;
        border: 1px solid rgba(119, 37, 8, 1);
        label {
          font-size: 14px;
          font-weight: 400;
          color: #772508;
          line-height: 24px;
        }
      }
    }
  }
  .batch-wrapper {
    padding: 0 15px;
    height: 42px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    button {
      min-width: 70px;
      height: 24px;
      border-radius: 2px;
      border: 1px solid #772508;
      background-color: #fff;
      margin-left: 17px;
      label {
        font-size: 14px;
        font-weight: 400;
        color: #772508;
        line-height: 24px;
        padding: 0 7px;
      }
      &.deep {
        background-color: #772508;
        label {
          color: #fff;
        }
      }
    }
  }
}
.list-wrapper {
  display: flex;
  flex: 1;
  overflow: hidden;
  .ul-wrapper {
    display: flex;
    flex-direction: column;
    background: #fff;
  }
  .category-wrapper {
    flex: 1;
    width: 85px;
    overflow: auto;
    background-color: #f9f9f9;
    .category-item {
      position: relative;
      width: 100%;
      height: 50px;
      font-size: 14px;
      font-weight: 400;
      color: #888;
      text-align: center;
      line-height: 50px;

      .left {
        position: absolute;
        left: 0;
        top: 17.5px;
        width: 2px;
        height: 15px;
      }
    }
    .category-item-pick {
      background: #fff;
      .left {
        background: #552e20;
      }
    }
  }
  .filter {
    flex: 1;
    padding-left: 10px;
    display: flex;
    flex-direction: column;

    .product-list {
      flex: 1;
      overflow: auto;
      .list-item {
        padding-top: 15px;
        padding-right: 15px;
        border-bottom: 1px dotted rgba(85, 46, 32, 0.2);
      }
      .item-info {
        display: flex;
        img {
          width: 85px;
          height: 85px;
        }
        .info-r {
          flex: 1;
          padding-left: 10px;
          p {
            font-size: 13px;
            font-weight: 400;
            color: #404040;
            line-height: 18px;
            height: 36px;

            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
          }
        }
        .price {
          display: flex;
          padding-top: 18px;
          align-items: center;
          .icon {
            @include sc(10px, #772508);
            font-weight: bold;
            line-height: 11px;
            padding-top: 4px;
          }
          .price-num {
            font-size: 18px;
            font-weight: bold;
            color: #772508;
            line-height: 21px;
            padding-right: 8px;
          }
          .price-wrapper {
            div {
              min-width: 71px;
              height: 24px;
              padding-left: 15px;
              display: flex;
              align-items: center;
              justify-content: center;
              padding-left: 15px;
              background: url('../../../assets/image/hh-icon/mlm/bg-price-new.png') center no-repeat;
              background-size: 86px 24px;
              span {
                @include sc(9px, #fff);
                font-weight: 400;
                line-height: 13px;
                text-align: center;
              }
            }
          }
        }
      }
      .btn-wrapper {
        padding: 10px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0;
        span {
          @include sc(11px, #888);
          font-weight: 400;
          line-height: 16px;
        }
        .no-batch {
          button {
            width: 62px;
            height: 24px;
            border-radius: 2px;
            border: 1px solid #552e20;
            background-color: #fff;
            box-sizing: border-box;
            color: #552e20;
            span {
              display: inline-block;
              @include sc(11px, #552e20);
              font-weight: 400;
              line-height: 1;
            }
            & + button {
              margin-left: 12px;
            }
            &.d {
              background-color: #552e20;
              span {
                color: #fff;
              }
            }
            &.a {
              border: none;
            }
          }
        }
        .checkout {
          width: 22px;
          height: 22px;
          position: relative;
          label {
            position: absolute;
            top: 0;
            left: 0;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: url('../../../assets/image/hh-icon/mlm/checked-no.png') center no-repeat;
            background-size: 22px;
            &.select {
              background-image: url('../../../assets/image/hh-icon/mlm/checked.png');
            }
          }
        }
      }
    }
  }
}
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
  padding-top: 45%;
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
      margin-top: 11px;
      color: #a4aab3;
    }
  }
}
</style>
