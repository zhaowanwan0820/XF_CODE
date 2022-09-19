<template>
  <div class="selective">
    <div class="selective-title">{{ utils.storeNameForShort }}优选</div>
    <div class="selective-body">
      <router-link tag="div" :to="{ name: 'products', query: { category: 174 } }" class="selective-item item-fresh">
        <div class="item-title">生鲜商品专区</div>
        <div class="item-sub-title">正宗海鲜 鲜得很!</div>
      </router-link>
      <div class="selective-item item-car" @click="goCarsList">
        <div class="item-title">汽车商品专区</div>
        <div class="item-sub-title">积分租赁汽车</div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {}
  },
  methods: {
    goCarsList() {
      this.Jump('https://lx.huanhuanyiwu.com/abs_base_web/webpage/huanhuan/index.html')
    }
  }
}
</script>

<style lang="scss" scoped>
.selective {
  padding: 20px 15px;
  background-color: #ffffff;
  margin-bottom: 10px;
  .selective-title {
    color: #404040;
    font-size: 16px;
    line-height: 1;
    font-family: PingFangSC;
    margin-bottom: 15px;
  }
  .selective-body {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .selective-item {
    height: 132px;
    width: 170px;
    box-sizing: border-box;
    padding-top: 20px;
    background-repeat: no-repeat;
    background-size: 100%;
    background-position: bottom 10px center;
    &.item-fresh {
      background-color: #f4fafc;
      background-image: url('../../../assets/image/hh-icon/b0-home/home-selective-fresh.png');
    }
    &.item-car {
      background-color: #f5fcf3;
      background-image: url('../../../assets/image/hh-icon/b0-home/home-selective-car.png');
    }
  }
  .item-title {
    color: #404040;
    font-size: 14px;
    line-height: 1;
    margin-left: 10px;
  }
  .item-sub-title {
    @include sc(10px, #95a6ac, left center);
    margin-left: 10px;
    margin-top: 5px;
  }
}
</style>
