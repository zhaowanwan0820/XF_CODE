<!-- ProductListFilter.vue -->
<template>
  <div class="ui-product-filter">
    <ul class="filter-list">
      <li
        class="item"
        v-for="(item, index) in SORTKEY"
        :key="item.id"
        @click="setActiveSortkey(item, index)"
        :class="{ sortactive: item.id == currentSortData.id, sortnormal: item.id != currentSortData.id }"
      >
        <a v-if="item.isMore">{{ currentChildSortData[index].name }}</a>
        <a v-else>{{ item.name }}</a>

        <img
          class="arrow-icon"
          :class="{ handstand: item.isMore && isShowMore }"
          src="../../../assets/image/hh-icon/b0-home/icon-search-箭头-down.png"
          v-if="item.isMore && index == currentSortIndex"
        />
        <img
          class="arrow-icon"
          src="../../../assets/image/hh-icon/b0-home/icon-search-箭头灰-down.svg"
          v-if="item.isMore && index != currentSortIndex"
        />

        <div class="sort-turn" v-if="item.isTurn">
          <div class="trun-div">
            <img
              class="arrow-icon"
              :class="{ handstand: true }"
              src="../../../assets/image/hh-icon/b0-home/icon-search-箭头-down.png"
              v-if="index == currentSortIndex && !item.childId"
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
              v-if="index == currentSortIndex && item.childId"
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

    <div class="sort-model" v-if="isShowMore">
      <div v-for="(items, index) in itemChildSort" :key="index">
        <template v-if="index == currentSortIndex">
          <div
            v-for="item in items"
            :key="item.id"
            @click="getSortChild(item, index)"
            class="sort-list"
            :class="{ active: item.id == currentChildSortData[index].id }"
          >
            <a>{{ item.name }}</a>
            <img
              src="../../../assets/image/change-icon/c1_choose@2x.png"
              v-if="item.id == currentChildSortData[index].id"
            />
          </div>
        </template>
      </div>
    </div>
    <img class="goTop" src="../../../assets/image/hh-icon/b0-home/icon-返回顶部.svg" />
  </div>
</template>

<script>
import { Toast, Indicator } from 'mint-ui'
import { SORTKEY, SORTKEY_INDEX0_HB } from '../static'
import { mapMutations } from 'vuex'
export default {
  props: ['value', 'isHb'],

  data() {
    return {
      SORTKEY: [], // 排序数据
      currentSortIndex: 0, // 当前选中的排序，所在的数组索引
      currentSortData: SORTKEY[0], // 当前选中的排序数据(预设索引0的数据)
      itemChildSort: {}, // 主筛选栏下的子筛选数据
      currentChildSortData: {}, // 选中的筛选子集
      isShowMore: false, // 是否显示筛选模态框
      isFirst: true // 是否是第一次点击
    }
  },
  created() {
    this.initFilter()
  },

  methods: {
    ...mapMutations({
      isShowProductModel: 'changeIsShowProductModel'
      // changeSearch: 'changeSearch'
    }),

    /*
     * closeFiler: 关闭下拉筛选模态框
     */
    closeFiler() {
      this.isShowMore = false
      this.isShowProductModel(this.isShowMore)
    },

    /*
     * isShowDroupMenu: 点击显示下拉框， 并且显示模态框
     */
    isShowDroupMenu() {
      let item = this.currentSortData
      if (item.isMore) {
        this.isShowMore = true
      } else {
        this.isShowMore = false
      }
      this.isShowProductModel(this.isShowMore)
    },

    /*
     * setActiveSortkey: 点击切换数据并设置选中的样式
     * @param: item 当前选中的item
     */
    setActiveSortkey(item, index) {
      let isChangeTab
      this.currentSortData = item
      if (this.currentSortIndex != index) {
        this.currentSortIndex = index
        isChangeTab = true
        this.currentChildSortData[0] = this.SORTKEY[0].child[0] // 第一个tab跳回第一条子筛选
      }

      if (item.isMore) {
        if (this.isFirst) {
          this.isShowMore = !this.isShowMore
          this.isShowProductModel(this.isShowMore)
        } else if (!this.isFirst && !this.isShowMore) {
          this.getValue()
          this.isFirst = true
        }
      } else {
        this.isFirst = false
        this.closeFiler()
        if (item.isTurn && !isChangeTab) {
          item.childId ^= 1
        }
        this.getValue()
      }
    },

    /*
     * getValue: 向父级组件发送改变列表事件， 并传递当前的sort_key， sort_value
     */
    getValue() {
      let data = this.getSortValue()
      this.$parent.$emit('change-list', data)
    },

    /*
     *  getSortValue: 获取排序值
     */
    getSortValue() {
      let sort = this.currentSortData,
        value = { sort_key: '', sort_value: '' }
      if (sort.isMore) {
        value.sort_key = this.currentChildSortData[this.currentSortIndex].key
        value.sort_value = this.currentChildSortData[this.currentSortIndex].value
      } else if (sort.isTurn) {
        value.sort_key = sort.child[sort.childId].key
        value.sort_value = sort.child[sort.childId].value
      } else {
        value.sort_key = sort.key
        value.sort_value = sort.value
      }
      return value
    },

    /*
     *  getSortChild: 获取综合筛选的子集， 关闭父级的阴影模态框， 关闭子集， 获取列表数据
     *  @param: item 模态框的item
     */
    getSortChild(item, index) {
      this.isShowProductModel(false)
      this.currentChildSortData[index] = item
      this.isShowMore = !this.isShowMore
      this.getValue()
    },

    /**
     * 初始化
     */
    async initFilter() {
      let _SORTKEY = JSON.parse(JSON.stringify(SORTKEY))
      // A类用户从用户中心积分专区入口过来的，只能看到和筛选专区商品
      // 其他入口过来的，只能看到和筛选 非专区 商品
      if (this.isHb) {
        _SORTKEY[0] = SORTKEY_INDEX0_HB
        _SORTKEY.splice(1, 2)
      }

      _SORTKEY.forEach((item, index) => {
        if (item.child) {
          this.itemChildSort[index] = item.child
          this.currentChildSortData[index] = item.child[0]
        }

        if (this.value) {
          // 显示选择的主筛选
          if (item.key == this.value) {
            this.currentSortIndex = index
            this.currentSortData = item
          }
          if (item.child) {
            item.child.forEach((c_item, c_index) => {
              if (c_item.key == this.value) {
                this.currentChildSortData[index] = c_item
              }
            })
          }
        }
      })

      this.SORTKEY = _SORTKEY
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-product-filter {
  background-color: #fff;
  margin-top: 10px;
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
        color: #b75800;
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
  .sort-model {
    position: absolute;
    left: 0;
    width: 100%;
    z-index: 10;
    .sort-list {
      color: #4e545d;
      padding: 15px;
      font-size: 13px;
      font-family: 'PingFangSC';
      background-color: #fff;
      margin: 0;
      border-bottom: 1px solid #e8eaed;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-content: center;
      align-items: center;
      img {
        float: right;
        width: 16px;
        height: 16px;
      }
      &.active {
        color: $primaryColor;
      }
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
</style>
