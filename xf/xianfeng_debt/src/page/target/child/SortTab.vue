<template>
  <div class="sort-container">
    <van-row class="filter-list" v-show="isSearch">
      <van-col :span="6" @click="sortByZH">
        <span :class="{ active: currentSortItem == 1 }"
          ><div class="textcon">综合排序</div>
          <div class="triangle init" :class="{ overturn: zhDownUp, 'active-triangle': currentSortItem == 1 }"></div
        ></span>
        <!-- <img :class="{ overturn: zhDownUp }" :src="zhSortImgUrl" alt="" /> -->
      </van-col>
      <van-col :span="6" @click="sortByDiscount">
        <span :class="{ active: currentSortItem == 2 }"
          ><div class="textcon">转让折扣</div>
          <div class="triangle" :class="{ overturn: dicountDownUp, 'active-triangle': currentSortItem == 2 }"></div
        ></span>
        <!-- <img :class="{ overturn: dicountDownUp }" :src="discountSortImgUrl" alt="" /> -->
      </van-col>
      <van-col :span="6">
        <van-dropdown-menu>
          <van-dropdown-item v-model="currentType" :options="option" @change="confirmType" />
        </van-dropdown-menu>
      </van-col>
      <van-col :span="6">
        <div class="search" @click="isSearch = false">搜索</div>
      </van-col>
    </van-row>
    <van-row v-show="!isSearch">
      <van-col :span="18">
        <div class="search-wrapper">
          <input
            v-model="name"
            type="search"
            class="search-box"
            placeholder="输入项目名称或债转编号"
            @keyup.enter="search(name)"
          />
        </div>
      </van-col>
      <van-col :span="6">
        <span class="cancel" @click="search(name)">搜索</span>
        <span class="cancel" @click="cancel">取消</span>
      </van-col>
    </van-row>
  </div>
</template>

<script>
export default {
  name: 'sortTab',
  props: ['list'],
  data() {
    return {
      isSearch: true,
      name: '',
      currentSortItem: 1,
      zhDownUp: false, // 综合排序升or降标识
      dicountDownUp: false, // 折扣排序升or降标识
      currentType: 1,
      option: this.list
    }
  },
  /*computed: {
    zhSortImgUrl() {
      return this.currentSortItem == 1
        ? require('../../../assets/image/filter/ascend-active.png')
        : require('../../../assets/image/filter/descend-not-active.png')
    },
    discountSortImgUrl() {
      return this.currentSortItem == 2
        ? require('../../../assets/image/filter/ascend-active.png')
        : require('../../../assets/image/filter/descend-not-active.png')
    }
  },*/
  methods: {
    search(name) {
      this.$emit('search', name)
    },
    cancel(){
      this.isSearch = true
      this.name=''
      this.$emit('search', '')
    },
    sortByZH() {
      // this.initType()
      if (this.currentSortItem == 1) {
        this.zhDownUp = !this.zhDownUp
      }
      this.currentSortItem = 1
      this.$emit('zh-sort', this.zhDownUp)
    },
    sortByDiscount() {
      // this.initType()
      if (this.currentSortItem == 2) {
        this.dicountDownUp = !this.dicountDownUp
      }
      this.currentSortItem = 2
      this.$emit('discount-sort', this.dicountDownUp)
    },
    confirmType(val) {
      this.$emit('choose-type', val)
    },
    initType() {
      this.currentType = 0
    }
  }
}
</script>

<style lang="less" scoped>
.sort-container {
  height: 40px;
  background-color: #fff;
  .filter-list {
    height: 100%;
    /deep/ .van-col {
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      span {
        position: relative;
        display: inline-block;
        height: 20px;
        font-size: 14px;
        font-weight: 400;
        line-height: 20px;
      }
      .triangle {
        position: absolute;
        top: 50%;
        right: -11px;
        margin-top: -5px;
        border-color: transparent transparent #333 #333;
        border-style: solid;
        border-width: 0.8vw;
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
        opacity: 0.8;
        content: '';
      }
      img {
        margin-left: 3px;
        width: 8px;
        height: 8px;
      }
    }
  }
  .overturn {
    -webkit-transform: rotate(135deg) !important;
    transform: rotate(135deg) !important;
    margin-top: -1px !important;
  }
  .active-triangle {
    border-color: transparent transparent #fc810c #fc810c !important;
  }
  .active {
    color: #fc810c;
  }
  /deep/ .van-dropdown-menu {
    width: 100%;
    height: 100%;
  }
  /deep/ .van-hairline--top-bottom:after,
  /deep/ .van-hairline-unset--top-bottom:after {
    border-width: 0;
  }
  /deep/.van-dropdown-menu__bar{
    height: 11vw;
    box-shadow:none;
  }
  .search {
    font-size: 14px;
    line-height: 20px;
  }
  .cancel {
    font-size: 14px;
    text-align: center;
    line-height: 40px;
    padding-left: 10px;
  }
  .search-wrapper {
    padding: 5px 0 5px 15px;
    .search-box {
      width: 100%;
      background-color: #f6f6f6;
      border: none;
      height: 30px;
      line-height: 30px;
      border-radius: 15px;
      text-indent: 1em;
    }
  }
}
</style>
