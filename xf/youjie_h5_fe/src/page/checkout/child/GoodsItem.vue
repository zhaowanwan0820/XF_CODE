<template>
  <div class="container">
    <img class="photo" :src="getPhotoUrl" />
    <div class="right-wrapper">
      <div class="product-header">
        <!-- <img class="image" v-if="getPromos" src="../../../assets/image/change-icon/c0_sale@2x.png" /> -->
        <label class="title">{{ getTitle }}</label>
      </div>
      <label class="subtitle">{{ item.property }}</label>
      <div class="desc-wrapper">
        <label class="price">￥{{ getPrice }}</label>
        <label class="count">x{{ item.amount }}</label>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    item: {
      type: Object
    }
  },
  computed: {
    getPhotoUrl: function() {
      let url = require('../../../assets/image/change-icon/default_image_02@2x.png')
      let item = this.item
      if (item && item.product && item.product.thumb) {
        url = item.product.thumb
      }
      return url
    },
    getTitle: function() {
      return this.getItemByKey('name')
    },
    getDesc: function() {
      return this.getItemByKey('desc')
    },
    getPrice: function() {
      // 分销商品、普通商品
      let totalPrice = 0
      if (this.item.mlmPrice) {
        totalPrice = this.item.mlmPrice
      } else {
        totalPrice = this.item.price
          ? this.item.price
          : Number(this.item.product.money_line) + Number(this.item.product.current_price)
      }
      return totalPrice ? this.utils.formatFloat(totalPrice) : '0'
    },
    getPromos: function() {
      if (this.item && this.item.product) {
        if (this.item.product.promos && this.item.product.promos.length) {
          return true
        } else {
          return false
        }
      } else {
        return false
      }
    }
  },
  methods: {
    getItemByKey: function(key) {
      let desc = ''
      let item = this.item
      if (item && item.product) {
        desc = item.product[key]
      }
      return desc
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: stretch;
  border-bottom: 1px solid $lineColor;
}
.photo {
  width: 90px;
  height: 90px;
  margin-left: 10px;
  margin-top: 10px;
  margin-right: 10px;
}
.right-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
}
.product-header {
  margin-top: 8px;
  margin-right: 10px;
  display: flex;
  align-items: center;
}
.image {
  width: 16px;
  height: 16px;
  margin-right: 4px;
}
.title {
  color: #4e545d;
  font-size: 14px;
}
.subtitle {
  margin-top: 6px;
  color: #7c7f88;
  font-size: 13px;
  margin-right: 8px;
}
.desc-wrapper {
  height: 20px;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  margin-top: 10px;
  margin-right: 10px;
}
.price {
  color: $primaryColor;
  font-size: 17px;
  margin-left: 0;
}
.count {
  color: #7c7f88;
  font-size: 16px;
  margin-right: 10px;
}
</style>
