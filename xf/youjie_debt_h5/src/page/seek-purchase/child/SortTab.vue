<template>
  <div class="sort-container">
    <van-row class="filter-list">
      <van-col :span="7" @click="sortByZH">
        <span :class="{ active: currentSortItem == 1 }"
          ><div class="textcon">综合排序</div>
          <div class="triangle" :class="{ overturn: zhDownUp, 'active-triangle': currentSortItem == 1 }"></div
        ></span>
      </van-col>
      <van-col :span="7" @click="sortByDiscount">
        <span :class="{ active: currentSortItem == 2 }"
          ><div class="textcon">求购折扣</div>
          <div class="triangle" :class="{ overturn: dicountDownUp, 'active-triangle': currentSortItem == 2 }"></div
        ></span>
      </van-col>
      <van-col :span="10" @click="seeOnly">
        <img class="filter-some-item" :src="chooseImgUrl" alt="" />
        <span>只看可转让的</span>
      </van-col>
    </van-row>
    <p class="show-tip">未包含您的项目的求购及未确权的债权不可进行转让！</p>
  </div>
</template>

<script>
export default {
  name: 'sortTab',
  data() {
    return {
      currentSortItem: 1,
      onlysee: false,
      zhDownUp: false, // 综合排序升or降标识
      dicountDownUp: false // 折扣排序升or降标识
    }
  },
  computed: {
    chooseImgUrl() {
      return this.onlysee
        ? require('../../../assets/image/filter/choosed.png')
        : require('../../../assets/image/filter/notchoose.png')
    }
  },
  methods: {
    sortByZH() {
      if (this.currentSortItem == 1) {
        this.zhDownUp = !this.zhDownUp
      }
      this.currentSortItem = 1
      this.$emit('zh-sort', this.zhDownUp)
    },
    sortByDiscount() {
      if (this.currentSortItem == 2) {
        this.dicountDownUp = !this.dicountDownUp
      }
      this.currentSortItem = 2
      this.$emit('discount-sort', this.dicountDownUp)
    },
    seeOnly() {
      this.onlysee = !this.onlysee
      this.$emit('see-only', this.onlysee)
    }
  }
}
</script>

<style lang="less" scoped>
.sort-container {
  background-color: #fff;
  .filter-list {
    height: 40px;
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
      .filter-some-item {
        width: 16px;
        height: 16px;
        margin-right: 6px;
      }
    }
  }
  .show-tip {
    padding-left: 15px;
    height: 47px;
    font-size: 12px;
    font-weight: 400;
    color: #999;
    line-height: 47px;
    background: #f9f9f9;
  }
  .overturn {
    -webkit-transform: rotate(135deg) !important;
    transform: rotate(135deg) !important;
    margin-top: -1px !important;
  }
  .active-triangle {
    border-color: transparent transparent #04b1a4 #04b1a4 !important;
  }
  .active {
    color: #04b1a4;
  }
}
</style>
