<template>
  <div class="product-list-body">
    <template v-if="items.length > 0" v-for="(item, index) in items">
      <div
        v-if="index < 6"
        class="product-info"
        v-bind:class="{ border: showRightBorder(index) }"
        @click="productClick(item)"
      >
        <div class="product-food">
          <img class="product-icon" v-lazy="lazy" />
          <span class="icon-sellOut" v-if="item.good_stock == 0"></span>
          <!-- <activity-icon-on-product
            v-if="item.act_616_type"
            :offset="true"
            :typeCode="item.act_616_type"
          ></activity-icon-on-product> -->
        </div>
        <div class="product-title" ref="text">{{ item.name }}</div>
        <div class="product-bottom">
          <p class="product-price">
            <span class="orig-price">
              <span class="price-unit">￥</span>
              <label>{{ utils.formatFloat(item.current_price, true) }}</label>
            </span>
            <span class="hb-price" v-if="item.HB_SHOW > 0">
              <span class="price-unit">￥</span>
              <label>{{ utils.formatFloat(item.MONEY_SHOW, true) }}</label>
              <img src="../../../assets/image/change-icon/quanyi-price-icon.png" alt="" />
            </span>
          </p>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import { ENUM } from '../../../const/enum'
import { productList } from '../../../api/product'
export default {
  name: 'HomeProductHotBody',
  data() {
    return {
      s2: ENUM.SUPPLIERS_TYPE.COLLABORATOR,
      itemWidth: 0,
      itemHeight: 0,
      items: [],
      params: {
        category: this.category,
        sort_key: 0,
        sort_value: 2,
        page: 1,
        per_page: 6
      },
      lazy: {
        src: this.item.thumb,
        error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
        loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
      }
    }
  },
  props: ['data', 'category'],
  created() {
    if (!this.category) {
      this.items = this.data
    } else {
      this.getList()
    }
  },
  mounted() {},
  methods: {
    productClick: function(item) {
      this.$router.push({ name: 'product', query: { id: item.id } })
    },
    showRightBorder(index) {
      return index % 3 != 0
    },
    getList() {
      productList(this.params).then(res => {
        this.items = res.list
      })
    }
  }
}
</script>

<style lang="scss" scoped>
// .border {
//     border-right: 1px solid #F5F5F5;
// }
.product-list-body {
  padding: 10px 15px 15px;
  padding-bottom: 0;
  display: inline-flex;
  flex-flow: row wrap;
  align-content: flex-start;
  .product-info {
    box-sizing: border-box;
    // flex: 0 0 33%; /*这里百分25表示项目大小占1/4空间*/
    width: 110px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
    .product-food {
      position: relative;
      width: 100%;
      height: 0;
      padding-top: 100%;
      border-radius: 3px;
      overflow: hidden;
      .icon-sellOut {
        position: absolute;
        top: -1px;
        right: -1px;
        width: 38px;
        height: 38px;
        background: url('../../../assets/image/hh-icon/b0-home/sellout-icon.png') no-repeat;
        background-size: 100%;
      }
    }
    .product-icon {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
    }
    .product-title {
      color: $baseColor;
      font-size: 12px;
      line-height: 15px;
      margin-top: 10px;
      height: 29px;
      word-break: break-word;
      overflow: hidden;
      text-overflow: ellipsis;
      font-weight: normal;
      display: -webkit-box;
      /*! autoprefixer: ignore next */
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 2;
    }
    .product-bottom {
      margin-top: 8px;
      .product-price {
        display: flex;
        justify-content: flex-start;
        align-items: baseline;
        font-size: 0;
        overflow: hidden;
        white-space: nowrap;
        .orig-price {
          width: auto;
          color: #b75800;
          font-weight: bold;
          margin-right: 1px;
          margin-left: -1px;
          label {
            font-size: 15px;
            letter-spacing: -0.5px;
          }
          .price-unit {
            letter-spacing: 0;
            display: inline-block;
            @include sc(10px, #b75800);
            margin-right: -1px;
            line-height: 1;
          }
        }
        .hb-price {
          width: auto;
          color: #404040;
          font-weight: 400;
          label {
            font-size: 13px;
            letter-spacing: -0.6px;
          }
          .price-unit {
            letter-spacing: 0;
            display: inline-block;
            @include sc(9px, #404040);
            margin-right: -2px;
            line-height: 1;
          }
          img {
            width: 23px;
          }
        }
      }
      .product-buy {
        color: #b5b6b6;
        margin-top: 5px;
        font-size: 10px;
        line-height: 18px;
        white-space: nowrap;
        margin-left: 5px;
        text-align: right;
      }
    }
    &.border {
      margin-left: 7px;
    }
  }
}
</style>
