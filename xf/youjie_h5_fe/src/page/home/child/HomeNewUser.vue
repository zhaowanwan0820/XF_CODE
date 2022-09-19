<template>
  <div class="new-user-container">
    <img class="icon-animal" src="../../../assets/image/hh-icon/b0-home/new-user-animal.png" alt="" />
    <div class="more"><span>查看更多</span></div>
    <div class="new-user" v-lazy:background-image="lazy" @click="newUserClick">
      <template v-for="(item, index) in items">
        <div class="item" v-stat="{ id: `index_fresh_${index}` }">
          <div class="item-img">
            <img
              class="product-icon"
              v-lazy="{
                src: item.thumb,
                error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
                loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
              }"
            />
            <img src="../../../assets/image/hh-icon/b0-home/new-user-coin.png" alt="" class="coin-icon" />
          </div>
          <p class="title">
            <span>{{ item.name }}</span>
          </p>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      lazy: {
        src: require('../../../assets/image/hh-icon/b0-home/new-user-bg.png'),
        error: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-new-uswe-bg.png'),
        loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-new-uswe-bg.png')
      }
    }
  },
  props: ['items'],
  methods: {
    newUserClick() {
      this.Jump('https://m.huanhuanyiwu.com/operation/index.php?url=https%3A%2F%2Fwww.itouzi.com%2Fe%2Fyiuyangou_wap')
    }
  }
}
</script>

<style lang="scss" scoped>
.new-user-container {
  position: relative;
  height: 190px;
  background-color: #ffffff;
  .icon-animal {
    width: 80px;
    position: absolute;
    left: 0;
    top: -8px;
    z-index: 1;
  }
  .new-user {
    padding: 50px 28px 15px;
    background-size: 100%;
    background-repeat: no-repeat;
    background-position: top center;
    display: flex;
    justify-content: space-between;
  }
  .item {
    box-sizing: border-box;
    padding: 5px 10px 0;
    width: 100px;
    height: 120px;
    border-radius: 4px;
    background-color: #ffffff;
    position: relative;
  }
  .item-img {
    position: relative;
    width: 100%;
    height: 80px;
    text-align: center;
    .product-icon {
      max-height: 100%;
    }
    .coin-icon {
      position: absolute;
      width: 24px;
      height: 24px;
      bottom: 0px;
      right: -5px;
    }
  }
  .title {
    width: 107px;
    line-height: 16px;
    height: 29px;
    display: flex;
    justify-content: center;
    overflow: hidden;
    span {
      flex-grow: 1;
      display: inline-block;
      @include sc(9px, #404040, left center);
      word-break: break-all;
      overflow: hidden;
    }
  }
  .more {
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 19px;
    right: 28px;
    width: 65px;
    height: 20px;
    border-radius: 20px;
    background-color: rgba(151, 95, 90, 1);
    span {
      display: inline-block;
      @include sc(10px, #ffffff, center center);
    }
  }
}
</style>
