<template>
  <div class="container-wrapper">
    <div v-for="(item, index) in items" class="container" @click="onclick">
      <div class="photo-wrapper">
        <img :src="getPhotoUrl(item)" />
      </div>
      <div class="right-wrapper">
        <div class="title">{{ item.goods.name }}</div>
        <div class="little-title">{{ item.goods.property }}</div>
        <div class="subtitle">
          <p class="product-price">
            <template v-if="item.product_price > 0 && item.amount > 0">
              <span class="price-unit">￥</span>
              <span>{{ utils.formatFloat(Number(item.product_price) / Number(item.amount)) }}</span>
            </template>
          </p>
          <p class="product-amount">x{{ item.amount }}</p>
        </div>
      </div>
    </div>
    <!-- 确认订单页 购买数量调整 -->
    <div class="buy-num" v-if="showEditNum && !isXiache">
      <label class="title">购买数量</label>
      <div class="edit-num">
        <div
          class="reduce ui-common"
          @click="reduceNumber(getItems[0].id, getItems[0].amount)"
          v-bind:class="{ 'reduce-opacity': getItems[0].amount <= 1 }"
        >
          -
        </div>
        <input type="number" min="1" v-model="getItems[0].amount" readonly="true" @click="ShowEdit" />
        <div
          class="add ui-common"
          v-if="getItems[0].attr_stock"
          @click="addNumber(getItems[0].id, getItems[0].amount, getItems[0].attr_stock)"
        >
          +
        </div>
        <div
          class="add ui-common"
          v-if="!getItems[0].attr_stock"
          @click="addNumber(getItems[0].id, getItems[0].amount, getItems[0].goods.good_stock)"
        >
          +
        </div>
      </div>
    </div>
    <edit-goods-number
      @showFlag="getShowFlag"
      @gNumber="getGNumber"
      :goodsNumber="getItems[0]"
      v-if="showflag"
    ></edit-goods-number>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { cartGet, cartUpdate } from '../../../api/cart'
import { Toast, MessageBox } from 'mint-ui'
import { Indicator } from 'mint-ui'
import EditGoodsNumber from '../../../components/common/EditGoodsNumber'

export default {
  data() {
    return {
      showflag: false,
      goodnum: '',
      only_purchase: this.items[0].goods.only_purchase ? this.items[0].goods.only_purchase : 0,
      now_purchase: this.items[0].goods.now_purchase
    }
  },
  props: {
    items: {
      type: Array
    },
    isXiache: {
      type: Boolean
    }
  },
  components: {
    EditGoodsNumber
  },
  computed: {
    ...mapState({
      tmpOrder: state => state.checkout.tmpOrder // 临时订单信息
    }),
    getItems: function() {
      let ret = []
      this.items.forEach((item, index) => {
        const totalPrice = item.price ? item.price : Number(item.goods.money_line) + Number(item.goods.current_price)
        ret.push({ ...item, totalPrice: totalPrice })
      })
      return ret
    },
    showEditNum() {
      // 是否显示 编辑商品数量功能；非购物车结算&&非竞拍商品&&未生成临时订单
      return !this.$route.params.isCart && !this.isC2B && !this.tmpOrder.debt_id
    }
  },
  methods: {
    getPhotoUrl(item) {
      let url = require('../../../assets/image/change-icon/default_image_02@2x.png')
      if (item && item.goods && item.goods.thumb) {
        url = item.goods.thumb
      }
      return url
    },
    /*
     *  addNumber: 数量增加
     *  @param: id 当前减少的商品id
     *  @param: amount 数量
     *  @param: stock 库存
     *  @param： index 当前减少的index
     */
    addNumber(id, amount, stock) {
      if (this.only_purchase) {
        let can_buy_num = this.only_purchase - this.now_purchase
        if (amount >= can_buy_num) {
          let toastConfig = '该商品每个用户每日限购' + this.only_purchase + '件哦'
          Toast(toastConfig)
          return
        }
      }
      if (amount < stock) {
        amount++
        if (id) {
          this.updateCartQuantity(id, amount)
        } else {
          this.$emit('amount', amount)
        }
      } else {
        Toast({
          message: '该商品不能购买更多了'
        })
      }
    },
    /*
     *  reduceNumber: 数量减少
     *  @param: id 当前减少的商品id
     *  @param: amount 数量
     *  @param： index 当前减少的index
     */
    reduceNumber(id, amount) {
      if (amount > 1) {
        amount--
        if (id) {
          this.updateCartQuantity(id, amount)
        } else {
          this.$emit('amount', amount)
        }
      } else {
        Toast({
          message: '该商品数量不能再减少了'
        })
      }
    },
    /*
     * updateCartQuantity: 商品数量加减更新数
     * @param: id 当前减少的商品id
     * @param: amount 数量
     * @param： index 当前操作的商品的index
     */
    updateCartQuantity(id, amount) {
      cartUpdate(id, amount).then(
        res => {
          Indicator.open(this.indicator)
          this.updateList(id, amount)
        },
        error => {
          Toast(error.errorMsg)
        }
      )
    },
    /*
     *  updateList: 加减之后更新列表
     */
    updateList(id, amount) {
      cartGet().then(res => {
        Indicator.close()
        res.forEach(item => {
          if (item.id == id) {
            this.items[0].amount = item.amount
          }
        })
      })
    },
    ShowEdit() {
      this.showflag = true
      this.goodnum = this.items[0].amount
    },
    getShowFlag(value) {
      this.showflag = value
    },
    getMinNum(stock = 0, purchaseLimit = 0) {
      return stock < purchaseLimit ? stock : purchaseLimit
    },
    getGNumber(value) {
      let limit = 0
      if (value.attr_stock) {
        // 销售属性（没有销售属性时 该值为库存数）库存
        limit = value.attr_stock
      }
      if (value.goods.only_purchase) {
        // 限购
        limit = value.goods.only_purchase
      }
      if (value.attr_stock && value.goods.only_purchase) {
        limit = this.getMinNum(value.attr_stock, value.goods.only_purchase)
      }

      // 先检查 数量限制
      if (value.amount < 1) {
        value.amount = 1
        Toast({
          message: '该商品数量不能再减少了'
        })
      } else if (limit && value.amount > limit) {
        value.amount = limit
        Toast({
          message: '该商品不能购买更多了'
        })
      }

      if (this.goodnum == value.amount) {
        return false
      }
      if (value.id) {
        this.updateCartQuantity(value.id, value.amount)
      } else {
        this.$emit('amount', value.amount)
      }
    },
    onclick() {
      this.$emit('onclick')
    },
    checkLimitNum(value, stock) {
      // 这段代码是什么意思？？？ 没看懂 by dlj
      let can_buy_num = value.goods.only_purchase - value.goods.now_purchase
      if (value.goods.only_purchase) {
        if (stock < can_buy_num) {
          value.amount = stock
          Toast({
            message: '该商品不能购买更多了'
          })
        } else {
          value.amount = can_buy_num
          let toastConfig = '该商品每个用户每日限购' + value.goods.only_purchase + '件哦'
          Toast(toastConfig)
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: flex-start;
  background-color: #fff;
  padding: 16px 15px;
  .photo-wrapper {
    @include wh(85px, 85px);
    overflow: hidden;
    img {
      width: 100%;
    }
  }
  .right-wrapper {
    margin-left: 12px;
    position: relative;
    flex: 1;
    .title {
      color: $baseColor;
      font-size: 13px;
      line-height: 16px;
      margin-bottom: 4px;
      word-break: break-word;
      overflow: hidden;
      text-overflow: ellipsis;
      font-weight: normal;
      display: -webkit-box;
      /*! autoprefixer: ignore next */
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 2;
    }
    .little-title {
      margin-bottom: 16px;
      display: inline-block;
      @include sc(10px, #888888, left center);
    }
    .subtitle {
      position: absolute;
      width: 100%;
      top: 70px;
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      align-items: baseline;
      font-size: 15px;
      .product-price {
        color: $baseColor;
        font-size: 0;
        span {
          font-size: 14px;
          font-weight: bold;
          &.price-unit {
            display: inline-block;
            @include sc(9px, $baseColor, left);
          }
        }
        img {
          width: 12px;
          height: 12px;
          transform: translateY(0.5px);
          margin-right: 1px;
        }
      }
      .product-amount {
        color: #888;
        font-size: 12px;
      }
    }
    .indicator {
      width: 7px;
      height: 12px;
      margin-left: 10px;
      margin-right: 14px;
    }
  }
}
.buy-num {
  padding: 0 15px 11px;
  background: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  @include thin-border(#f4f4f4, 15px);
  .title {
    font-size: 13px;
    color: #404040;
    font-weight: 300;
    line-height: 18px;
  }
  .edit-num {
    display: flex;
    justify-content: space-between;
    align-items: center;
    .ui-common {
      width: 9px;
      height: 18px;
      line-height: 18px;
      text-align: center;
      font-size: 16px;
      color: #404040;
    }
    .reduce {
      margin-right: 11px;
    }
    .reduce-opacity {
      opacity: 0.2;
    }
    .add {
      margin-left: 9px;
    }
    input[type='number'] {
      width: 25px;
      height: 18px;
      font-size: 12px;
      box-shadow: 0;
      background: #f4f4f4;
      color: #404040;
      text-align: center;
      border: none;
      border-radius: 0;
      &:focus {
        outline: none;
      }
    }
  }
}
</style>
