<template>
  <div class="cart-list" v-if="!isEmpty">
    <div class="cart-shop-wrapper" v-for="(cartitem, index) in fmtCartList" :key="index">
      <div class="cart-shop-header" @click="goSupplier(cartitem.sn)">
        <img class="shop-icon" src="../../../assets/image/change-icon/shop-icon@2x.png" alt="" />
        <span>{{ cartitem.shop_name }}</span>
        <img class="icon-tip" src="../../../assets/image/hh-icon/supplier/icon-tip.png" alt="" />
      </div>
      <div class="cart-list-wrapper">
        <div class="list" v-for="(item, index) in cartitem.list" :key="index">
          <div class="list-checkbox">
            <input
              v-if="!isDeleteMode"
              type="checkbox"
              class="checkbox"
              :class="{ 'pre-sale': isPreSale(item.goods) }"
              :id="`${item.sn}_${index}`"
              v-model="item.checked"
              @change="changeSingleStatus"
              :disabled="item.goods.good_stock == 0 || isPreSale(item.goods)"
              v-stat="{ id: 'shopcart_btn_check' }"
            />
            <input
              v-else
              type="checkbox"
              class="checkbox"
              :id="`${item.sn}_${index}`"
              v-model="item.checked"
              ref="goodsList"
            />
            <label :for="`${item.sn}_${index}`"></label>
          </div>
          <div class="list-item" @click="itemClick(item)" v-stat="{ id: `shopcart_shops_${item.goods.id}` }">
            <div class="item">
              <div class="ui-image">
                <img
                  v-lazy="{
                    src: item.goods.thumb,
                    error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
                    loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
                  }"
                />
              </div>
              <div class="list-info">
                <div class="product-header">
                  <h3 class="product-title" v-bind:class="{ 'disabled-list': item.goods.good_stock == 0 }">
                    {{ item.goods.name }}
                  </h3>
                </div>
                <h3 class="property-info">{{ item.property }}</h3>
                <div class="limit-buy" v-if="item.goods.only_purchase">
                  <p>该商品每日限购{{ item.goods.only_purchase }}件</p>
                </div>
                <div class="limit-buy" v-if="isPreSale(item.goods)">
                  <p>{{ showSaleTime(item.goods) }}</p>
                </div>
                <div class="price-amount-wrapper">
                  <div class="info-price">
                    <p v-bind:class="{ 'disabled-list': item.goods.good_stock == 0 }">
                      <span class="price-unit">￥</span>
                      <span>{{ utils.formatFloat(item.price) }}</span>
                    </p>
                  </div>
                  <div class="cart-number">
                    <div class="ui-number" v-if="!isDeleteMode">
                      <div
                        class="reduce ui-common"
                        @click.stop="reduceNumber(item)"
                        v-bind:class="{ 'reduce-opacity': item.amount <= 1 }"
                      >
                        ——
                      </div>
                      <span class="number" @click.stop="ShowEdit(item, index)">{{ item.amount }}</span>
                      <div class="add ui-common" @click.stop="addNumber(item)">+</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="mask-wrapper" v-if="item.goods.good_stock == 0 && !isDeleteMode">
            <p>商品已失效</p>
          </div>
        </div>
        <edit-goods-number
          @commit="editNumCom"
          @close="closeEditNum"
          :goods="currentEditNumGoods"
          v-if="popUpEditNum"
        ></edit-goods-number>
      </div>
    </div>
  </div>
  <div class="cart-list-empty" v-else>
    <img src="../../../assets/image/hh-icon/l0-list-icon/cart-list.png" />
    <p>您的购物车还是空的</p>
    <gk-button class="button" type="primary-secondary-white" v-on:click="goHome" v-stat="{ id: 'shopcart_btn_around' }"
      >随便逛逛</gk-button
    >
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { Indicator } from 'mint-ui'
import { Toast, MessageBox } from 'mint-ui'
import { cartUpdate } from '../../../api/cart'
import EditGoodsNumber from './EditGoodsNumber'
import { checkGoodsAmoutValid } from './util'
export default {
  data() {
    return {
      popUpEditNum: false, // 直接编辑商品购买数量
      currentEditNumGoods: null // 当前-正在直接编辑商品购买数量 的商品
    }
  },
  computed: {
    ...mapState({
      mode: state => state.cart.mode,
      cartList: state => state.cart.goodsList
    }),
    ...mapGetters({
      isEmpty: 'cart_isEmpty'
    }),
    isDeleteMode() {
      return this.mode == 2
    },
    fmtCartList() {
      // 将goodsList整合为二维数组
      let arr = []
      let arr_sn = []
      arr_sn = Array.from(
        new Set(
          this.cartList.map(item => {
            return item.sn
          })
        )
      )
      console.log(arr_sn);
      arr_sn.forEach(item => {
        arr.push({ sn: item, shop_name: '', list: [] })
      })
      arr.forEach(item => {
        this.cartList.forEach(ele => {
          if (ele.sn === item.sn) {
            item.shop_name = ele.shop_name
            item.list.push(ele)
          }
        })
      })
      console.log(arr,"---");
      return arr
    }
  },
  created() {
    Indicator.open()
    this.fetchCartList().then(res => {
      Indicator.close()
    })
  },
  methods: {
    ...mapMutations({
      setCartIsLoading: 'setCartIsLoading',
      editGoodNumber: 'editGoodNumber',
      changeChoosenStatus: 'changeChoosenStatus'
    }),
    ...mapActions({
      fetchOrderPrice: 'fetchOrderPrice',
      fetchCartList: 'fetchCartList'
    }),
    goSupplier(id) {
      this.$router.push({ name: 'Supplier', query: { id: id } })
    },
    /*
     *  结算模式下，改变单个商品是否选中的状态后，计算商品价格数据
     */
    changeSingleStatus() {
      this.getNewOrderprice()
    },
    // 计算 结算数据
    getNewOrderprice() {
      Indicator.open()
      this.fetchOrderPrice().then(() => {
        Indicator.close()
      })
    },
    commonEditNumber(item, amount) {
      if (amount < 1) {
        Toast({
          message: '商品数量不能再减少了'
        })
        return
      }
      // 校验 库存 | 限购
      const check = checkGoodsAmoutValid(item, amount)

      // check['msg'] &&
      //   Toast({
      //     message: check['msg']
      //   })
      // 更新前端购买数量
      this.editGoodNumber({ action: 2, item: item, amount: check['amount'] })
      // 更新后端购物车数据
      this.updateCartQuantity(item.id, check['amount'])
      // 计算商品价格数据
      this.getNewOrderprice()
    },
    // 数量减少
    reduceNumber(item) {
      let amount = item.amount
      this.commonEditNumber(item, --amount)
    },
    // 数量增加
    addNumber(item) {
      let amount = item.amount
      this.commonEditNumber(item, ++amount)
    },
    /*
     * 编辑购物车商品数量后 更新后端数据
     */
    updateCartQuantity(id, amount) {
      cartUpdate(id, amount).then(
        res => {},
        error => {
          Toast(error.errorMsg || '数据更新失败')
        }
      )
    },
    /*
     *  商品列表项 点击
     */
    itemClick(item) {
      if (!this.isDeleteMode) {
        this.goDetail(item.goods_id)
        return
      }
      this.changeChoosenStatus(item)
    },
    // 跳转到详情
    goDetail(id) {
      this.$router.push({ name: 'product', query: { id: id } })
    },
    // 直接编辑商品数量
    ShowEdit(item, index) {
      this.currentEditNumGoods = item
      this.popUpEditNum = true
    },
    // 编辑商品数量 提交
    editNumCom(amount) {
      this.commonEditNumber(this.currentEditNumGoods, amount)
    },
    // 取消修改 商品数量
    closeEditNum() {
      this.currentEditNumGoods = null
      this.popUpEditNum = false
    },
    /**
     * 判断是否在预售中
     */
    isPreSale(good) {
      const now = parseInt(new Date().getTime() / 1000)
      return !!(good.is_pre_sale && good.sale_time > now)
    },
    /**
     * 预售商品的 开始售卖时间
     */
    showSaleTime(good) {
      return this.utils.formatDate('MM月DD日 HH:mm', good.sale_time) + ' 开始购买'
    },
    goHome() {
      this.$router.push({ name: 'home' })
    }
  },
  components: {
    EditGoodsNumber
  }
}
</script>

<style lang="scss" scoped>
.cart-shop-wrapper {
  background-color: #fff;
  margin-top: 10px;
  .cart-shop-header {
    padding: 16px 0 16px 18px;
    display: flex;
    align-items: center;
    img.shop-icon {
      width: 14px;
    }
    span {
      margin-left: 10px;
      font-size: 13px;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
      line-height: 15px;
    }
    img.icon-tip {
      width: 3px;
      margin-left: 9px;
      position: relative;
      top: 1px;
    }
  }
  .cart-list-wrapper {
    width: 100%;
    margin-bottom: 10px;
    .list {
      // background-color: #fff;
      padding-left: 15px;
      // border-bottom: 1px solid #e8eaed;
      display: flex;
      // margin-top: 10px;
      align-content: center;
      align-items: center;
      position: relative;
      div.list-checkbox {
        width: 23px;
        height: 22px;
        flex-basis: 22px;
        flex-shrink: 0;
        position: relative;
        margin-right: 10px;
        label {
          @include wh(22px, 22px);
          @include thin-border-2019([top, left, bottom, right], #979797, 50%);
          position: absolute;
          top: 0;
          left: 1px;
          display: inline-block;
        }
        input {
          display: none;
          &:checked + label {
            @include wh(22px, 22px);
            @include thin-border-2019([top, left, bottom, right], #fc7f0c, 50%);
            background: url('../../../assets/image/hh-icon/icon-checkbox-active.png') no-repeat;
            background-size: cover;
          }
          &:focus {
            outline-offset: 0;
          }
          &.pre-sale + label {
            background-color: #e2e2e2;
          }
        }
      }
      .list-item {
        display: flex;
        width: 100%;
        flex-direction: column;
        border-top: 1px dotted rgba(64, 64, 64, 0.1);
        padding: 15px 15px 15px 0;
        div.item {
          display: flex;
          width: 100%;
          div.ui-image {
            flex-shrink: 0;
            width: 100px;
            height: 100px;
            flex-basis: 100px;
            position: relative;
            border-radius: 4px;
            img {
              width: 100%;
              height: 100%;
              border-radius: 4px;
            }
            span.promos {
              position: absolute;
              background: url('../../../assets/image/change-icon/label@2x.png') no-repeat;
              width: 36px;
              height: 19px;
              color: #fff;
              font-size: 10px;
              top: 0;
              /* left: 0; */
              background-size: cover;
              font-weight: 100;
              line-height: 19px;
              text-align: left;
              padding-left: 5px;
            }
            span.stock-info {
              position: absolute;
              height: 20px;
              background: rgba(243, 244, 245, 1);
              line-height: 20px;
              text-align: center;
              font-size: 14px;
              color: $primaryColor;
              width: 100%;
              bottom: 0;
              left: 0;
              border-radius: 0 0 4px 4px;
            }
          }
          div.list-info {
            position: relative;
            margin-left: 10px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-content: center;
            justify-content: flex-start;
            .product-header {
              display: flex;
              align-items: center;
              .promos-icon {
                width: 16px;
                height: 16px;
                margin-right: 4px;
              }
              .product-title {
                font-size: 14px;
                line-height: 20px;
                color: $baseColor;
                margin-bottom: 7px;
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
                overflow: hidden;
                &.disabled-list {
                  color: #a4aab3;
                }
              }
            }
            h3 {
              font-size: 14px;
              color: rgba(78, 84, 93, 1);
              padding: 0;
              margin: 0;
              display: -webkit-box;
              -webkit-box-orient: vertical;
              -webkit-line-clamp: 2;
              overflow: hidden;
              &.disabled-list {
                color: #a4aab3;
              }
            }
            h3.property-info {
              font-size: 11px;
              color: #b5b6b6;
            }
            div.price-amount-wrapper {
              display: flex;
              margin-top: 7px;
              position: absolute;
              width: 100%;
              top: 75px;
            }
            div.info-price {
              width: auto;
              flex: 1 0 0;
              font-weight: bold;
              white-space: nowrap;
              text-overflow: ellipsis;
              color: #b75800;
              img {
                width: 12px;
                height: 12px;
                margin-right: 1px;
              }
              .price-unit {
                font-size: 12px;
              }
              p {
                font-weight: bold;
                font-size: 0;
                display: flex;
                align-items: baseline;
                &.disabled-list {
                  color: #a4aab3;
                }
                span {
                  font-size: 15px;
                }
              }
            }
            div.limit-buy {
              p {
                color: $markColor;
                font-size: 12px;
              }
            }
            div.cart-number {
              display: flex;
              flex-direction: row;
              justify-content: flex-end;
            }
            div.ui-number {
              height: 18px;
              display: flex;
              border-radius: 3px 0 0 3px;

              .ui-common {
                width: 18px;
                height: 18px;
                cursor: pointer;
              }
              .reduce,
              .add {
                width: 9px;
                height: 18px;
                font-size: 16px;
                font-weight: 400;
                color: rgba(64, 64, 64, 1);
                line-height: 18px;
              }
              .reduce {
                margin-right: 11px;
                overflow: hidden;
              }
              .reduce-opacity {
                opacity: 0.2;
              }
              .add {
                margin-left: 9px;
              }
              span.number {
                display: inline-block;
                width: 25px;
                height: 18px;
                background: #f4f4f4;
                font-size: 12px;
                color: #404040;
                font-weight: 400;
                line-height: 18px;
                text-align: center;
              }
            }
          }
        }
        p.list-promotion-info {
          margin-top: 12px;
          padding: 8px 0;
          line-height: auto;
          font-size: 10px;
          color: #000;
          background: #f8f8f8;
          width: 100%;
          span {
            border: 1px solid $primaryColor;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 10px;
            color: $primaryColor;
            margin: 0 10px;
            text-align: center;
          }
        }
      }
      .mask-wrapper {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        justify-content: center;
        align-items: center;
        p {
          color: #fff;
        }
      }
    }
  }
}

.has-bottom {
  bottom: 94px;
}

.cart-list-empty {
  background: #ffffff;
  padding: 30px 0 70px;
  text-align: center;
  img {
    width: 135px;
    height: 135px;
  }
  p {
    margin-top: 10px;
    font-size: 18px;
    font-family: PingFangSC-Medium;
    font-weight: 500;
    color: rgba(102, 102, 102, 1);
    line-height: 25px;
  }
  .button {
    @include button($margin: 0 15px, $radius: 2px, $spacing: 2px);
    width: 140px;
    height: 36px;
    font-size: 14px;
    font-family: 'PingFangSC-Regular';
    margin-top: 40px;
  }
}
</style>
