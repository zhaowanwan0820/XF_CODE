<!-- Shopping.vue -->
<template>
  <mt-popup
    v-model="ShowcartInfo"
    position="bottom"
    v-if="detailInfo.properties.length || (detailInfo.instalment && detailInfo.instalment.length)"
    v-bind:close-on-click-modal="true"
  >
    <div class="ui-add-shopping" v-if="detailInfo">
      <div class="shopping-info">
        <div class="info-header">
          <img
            src="../../../assets/image/change-icon/default_image_02@2x.png"
            class="info-image"
            v-if="!detailInfo.photos || detailInfo.photos.length <= 0"
          />
          <img v-bind:src="getImgSrc" class="info-image" v-if="detailInfo.photos && detailInfo.photos.length > 0" />
          <div class="info-header-right">
            <!-- <span class="header-price" v-if="this.isExchange">{{ detailInfo.exchange_score }}积分</span> -->
            <div class="header-price info-price">
              <span class="price-unit">￥</span>
              <span>{{
                utils.formatFloat(Number(shoppingPrice.current_price) + Number(shoppingPrice.money_line))
              }}</span>
              <!-- <template v-if="shoppingPrice.money_line > 0">
                <img class="product-money-icon" src="../../../assets/image/hh-icon/b0-home/money-icon.png" />
                <span>{{ utils.formatFloat(shoppingPrice.money_line, true) }}</span>
              </template>
              <span v-if="shoppingPrice.money_line > 0 && shoppingPrice.current_price > 0">+</span>
              <template v-if="shoppingPrice.current_price > 0">
                <span class="price-unit">￥</span>
                <span>{{ utils.formatFloat(shoppingPrice.current_price) }}</span>
              </template> -->
            </div>
            <span class="prod-activity"
              ><span v-if="detailInfo.activity">{{ detailInfo.activity.name }}</span></span
            >
            <!-- {{ chooseinfo}} -->
            <div class="choose-info">
              <span>
                <template v-if="ids.length <= 0"
                  >请选择分类</template
                >
                <template v-else
                  >已选择: {{ choosedInfo.join(' ') }}</template
                >
              </span>
              <span class="prod-num">库存{{ currentStock }}</span>
            </div>
            <img src="../../../assets/image/hh-icon/icon-关闭.png" class="close" v-on:click="closeCartInfo(false)" />
          </div>
        </div>

        <div class="goods-detail-properties">
          <div
            class="goods-properties"
            v-if="detailInfo && detailInfo.properties && detailInfo.properties.length > 0"
            v-for="(item, index) in detailInfo.properties"
            :key="index"
          >
            <p>{{ item.name }}</p>
            <div class="properties-list">
              <div v-for="(key, keyindex) in item.attrs" :key="keyindex">
                <span
                  @click="setCurrentIndex(index, key.id, key)"
                  v-bind:class="{ 'active-properties': item.currentIndex == key.id }"
                  v-if="key.ishasstock"
                  >{{ key.attr_name }}</span
                >
                <span v-bind:class="{ 'disabled-properties': !key.ishasstock }" v-if="!key.ishasstock">
                  {{ key.attr_name }}
                </span>
              </div>
            </div>
          </div>
          <div class="info-body" id="info-body">
            <p>数量</p>
            <div class="ui-number">
              <div
                class="reduce ui-common"
                @click.stop="reduceNumber"
                v-bind:class="{ 'reduce-opacity': numbers <= 1 }"
              >
                -
              </div>
              <input
                type="number"
                min="1"
                class="number"
                value="1"
                v-model="numbers"
                readonly="true"
                disabled="disabled"
              />
              <div class="add ui-common" @click.stop="addNumber">+</div>
            </div>
          </div>
          <div
            class="instalment-wrapper"
            v-if="detailInfo && detailInfo.instalment && detailInfo.instalment.length > 0"
          >
            <div class="istlmt-w-title">分期</div>
            <div class="istlmt-w-body">
              <!--
              <div class="istlmt-w-item" :class="['method-0', { active: instalmentWay === 0 }]" @click="istlmtClick(0)">
                <img v-if="0 === instalmentWay" :src="instalmentIcon.active[0]" alt="" />
                <img v-else :src="instalmentIcon.normal[0]" alt="" />
                <div class="istlmt-w-num">仅1期</div>
                <div class="istlmt-w-price">
                  <p>
                    <span>现金：{{ utils.formatFloat(detailInfo.MONEY_SHOW, false) }}/期</span>
                    <span>积分：{{ utils.formatFloat(detailInfo.HB_SHOW, false) }}/期</span>
                  </p>
                  <p>
                    <span>总额：￥{{ utils.formatFloat(detailInfo.current_price, false) }}/期</span>
                  </p>
                </div>
              </div>
              -->
              <div
                class="istlmt-w-item"
                v-for="item in detailInfo.instalment"
                :class="[`method-${item.method}`, { active: item.id == instalmentWay }]"
                @click="istlmtClick(item.id)"
              >
                <img v-if="item.id == instalmentWay" :src="instalmentIcon.active[item.method]" alt="" />
                <img v-else :src="instalmentIcon.normal[item.method]" alt="" />
                <div class="istlmt-w-num">{{ item.method == 5 ? '全款付' : `分${item.num}期` }}</div>
                <div class="istlmt-w-price">
                  <p class="total">
                    <span>￥{{ utils.formatMoney(item.total_price, true) }}{{ txtPaymentType(item) }}</span>
                  </p>
                  <p class="desc">
                    <span>现金：{{ utils.formatMoney(item.cash, true) }}{{ txtPaymentType(item) }}</span>
                    <span>积分：{{ utils.formatMoney(item.surplus, true) }}{{ txtPaymentType(item) }}</span>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="info-footer">
          <slot></slot>
        </div>
      </div>
    </div>
  </mt-popup>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { Toast, MessageBox, Button } from 'mint-ui'
import { cartAdd } from '../../../api/cart'
import { instalmentIcon, PRODUCT_SHOW_SHOUQI } from '../static.js'

export default {
  data() {
    return {
      instalmentIcon,

      numbers: this.$store.state.detail.number > 0 ? this.$store.state.detail.number : 1, //todo 临时解决
      currentStock: this.$store.state.detail.detailInfo.good_stock,
      productId: this.$store.state.detail.currentProductId,
      now_purchase: this.$store.state.detail.detailInfo.now_purchase,
      only_purchase: this.$store.state.detail.detailInfo.only_purchase,
      toastConfig: {
        message: '商品达到每单限购数量',
        position: 'middle'
      },
      ids: [],
      info: [],
      ShowcartInfo: false
    }
  },

  props: {
    isShowcartInfo: {
      type: Boolean,
      default: false
    }
  },

  created() {
    this.ShowcartInfo = this.isShowcartInfo
    this.info = this.chooseinfo.specification
    this.ids = Object.assign([], this.chooseinfo.ids)
    this.buildProperties(true)
    if (!this.detailInfo || !this.detailInfo.properties || !this.detailInfo.properties.length) {
      // this.checkProduct(0)
    }
  },

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      detailInfo: state => state.detail.detailInfo,
      number: state => state.detail.number,
      chooseinfo: state => state.detail.chooseinfo,
      instalmentWay: state => state.detail.instalmentWay,
      stackChoosedIds: state => state.detail.stackChoosedIds
      // isExchange: state => state.score.isExchange
    }),
    ...mapGetters({
      getDetailUsableList: 'getDetailUsableList',
      getUsableList: 'getUsableList',
      shoppingPrice: 'getShoppingPrice'
    }),
    choosedInfo() {
      let arr = []
      if (this.chooseinfo.ids.length > 0) {
        arr = this.chooseinfo.specification.filter((item, index) => {
          return !index % 2
        })
      }
      return arr
    },
    propertiesImgs() {
      return this.detailInfo.properties.reduce((accumulator, currentValue) => {
        const obj = currentValue.attrs.reduce((ac, cur) => {
          ac[cur.id] = cur.image
          return ac
        }, {})
        return { ...accumulator, ...obj }
      }, {})
    },
    /**
     * 获取某个规格的商品图片
     */
    getImgSrc() {
      const ele = this.stackChoosedIds.find(ele => {
        return !!this.propertiesImgs[ele]
      })
      return (ele && this.propertiesImgs[ele]) || this.detailInfo.photos[0].thumb
    }
  },

  watch: {
    numbers: function(value) {
      if (value) {
        let good_stock = this.getGoodsStock()
        if (value <= 0) {
          this.numbers = 1
          this.toastConfig.message = '受不了了，宝贝不能再少了'
          Toast(this.toastConfig)
        } else if (value > good_stock) {
          this.toastConfig.message = '该商品不能购买更多了'
          Toast(this.toastConfig)
          this.numbers = good_stock
        }
        this.saveNumber(this.numbers)
      }
    },
    ShowcartInfo(value) {
      this.saveCartState(value)
    }
  },

  mounted() {},

  methods: {
    ...mapMutations({
      saveCartState: 'saveCartState',
      saveNumber: 'saveNumber',
      saveChooseInfo: 'saveChooseInfo',
      saveProperties: 'saveProperties',
      saveSelectedCartGoods: 'saveSelectedCartGoods',
      // saveExchangeScoreState: 'saveExchangeScoreState',
      itzAuthGuide: 'itzAuthGuide',
      savePrice: 'savePrice',
      saveInstalmentWay: 'saveInstalmentWay',
      editStackChoosedIds: 'editStackChoosedIds'
    }),
    ...mapActions({
      helperItzAuthCheck: 'helperItzAuthCheck',
      fetchCartNumber: 'fetchCartNumber'
    }),

    // 关闭购物车浮层
    closeCartInfo(value) {
      this.saveCartState(value)
    },

    /*
     * getGoodsStock: 获取是否还有库存
     */
    getGoodsStock() {
      let good_stock = 0
      if (this.ids.length <= 0) {
        good_stock = this.detailInfo.good_stock
      } else {
        let str = this.fromatArray('|', this.ids)
        let stock = this.isHasStock(str)
        good_stock = stock ? parseInt(stock) : this.detailInfo.good_stock
      }
      this.currentStock = good_stock
      return good_stock
    },

    // 数量加
    addNumber() {
      let good_stock = this.getGoodsStock()
      if (this.detailInfo.good_stock && this.numbers > good_stock) {
        this.toastConfig.message = '该商品不能购买更多了'
        Toast(this.toastConfig)
        this.numbers = good_stock
      } else {
        this.numbers++
      }
      // 商品限购
      if (this.only_purchase) {
        let can_buy_num = this.only_purchase - this.now_purchase
        if (this.numbers > can_buy_num) {
          this.toastConfig = '该商品每个用户每日限购' + this.only_purchase + '件哦'
          Toast(this.toastConfig)
          if (can_buy_num < 1) {
            this.numbers = 1
          } else {
            this.numbers = can_buy_num
          }
          return
        }
      }
    },

    // 数量减
    reduceNumber() {
      if (this.numbers > 1) {
        this.numbers--
      } else {
        this.numbers = 1
        this.toastConfig = '该商品数量不能再减少了'
        Toast(this.toastConfig)
      }
    },

    // 加入购物车
    checkProduct(checkout) {
      if (!this.isOnline) {
        this.$router.push({ name: 'login' })
      } else {
        if (this.detailInfo.properties.length > 0) {
          let status = true
          if (this.ids.filter(item => item).length != this.detailInfo.properties.length) {
            status = false
          } else {
            for (let i = 0; i <= this.ids.length - 1; i++) {
              if (this.ids[i] == '' || this.ids[i] == undefined) {
                status = false
                break
              }
            }
          }
          if (status) {
            this.checkStock(checkout)
          } else {
            Toast('请选择商品属性')
            return false
          }
          // } else if (this.only_purchase) {
          //   debugger
          //   // 限购
          //   let can_buy_num = this.only_purchase - this.now_purchase
          //   if (can_buy_num < 1) {
          //     let toastConfig = '该商品每个用户每日限购' + this.only_purchase + '件哦'
          //     Toast(toastConfig)
          //   } else {
          //     this.checkStock(checkout)
          //   }
        } else {
          this.checkStock(checkout)
        }
      }
    },

    checkStock(checkout) {
      let params = {
        product: this.productId,
        property: '',
        amount: this.numbers
      }
      if (this.ids.length > 0) {
        params.property = JSON.stringify(this.ids)
        let str = this.fromatArray('|', this.ids),
          stock = this.isHasStock(str)
        if (parseInt(stock) <= 0) {
          Toast('该商品不能购买更多了')
          return false
        }
      }

      let attr_index = this.detailInfo.stock.length - this.info[1] - 1
      if (checkout) {
        let cartGood = {
          goods_id: this.detailInfo.id,
          goods: this.detailInfo,
          property: '',
          attrs: params.property ? params.property : '[]',
          num: params.amount,
          amount: params.amount,
          chooseinfo: this.chooseinfo,
          price: Number(this.shoppingPrice.current_price) + Number(this.shoppingPrice.money_line),
          attr_stock: this.detailInfo.stock[attr_index].stock_number,
          only_purchase: this.only_purchase,
          now_purchase: this.now_purchase
        }

        if (this.chooseinfo.specification.length > 0) {
          let attrs = this.chooseinfo.specification
          for (let i = 0; i <= attrs.length - 1; i++) {
            cartGood.property = cartGood.property + '' + attrs[i]
          }
        }

        let cartGoods = [cartGood]
        // if (this.isExchange) {
        //   this.exchangeProduct(cartGoods)
        // } else {
        this.helperItzAuthCheck().then(res => {
          if (res.status) {
            // this.saveExchangeScoreState(0)
            this.saveSelectedCartGoods({ cartGoods: cartGoods })
            this.savePrice(
              params.amount * (Number(this.shoppingPrice.current_price) + Number(this.shoppingPrice.money_line))
            )
            this.$router.push({ name: 'checkout' })
          } else {
            this.closeCartInfo(false)
            // this.itzAuthGuide({ isHHApp: this.isHHApp })
          }
        })
        // }
      } else {
        this.addShopCart(params)
      }
    },

    // exchangeProduct(cartGoods) {
    //   this.closeCartInfo(false)
    //   MessageBox({
    //     title: '确认兑换',
    //     message: '是否使用' + this.detailInfo.exchange_score + '积分兑换',
    //     showCancelButton: true,
    //     cancelButtonText: '取消',
    //     cancelButtonClass: 'cancel-button',
    //     confirmButtonClass: 'confirm-button-red',
    //     confirmButtonText: '确认'
    //   }).then(action => {
    //     if (action === 'confirm') {
    //       // this.saveExchangeScoreState(this.detailInfo.exchange_score)
    //       this.saveSelectedCartGoods({ cartGoods: cartGoods })
    //       this.$router.push({
    //         name: 'checkout',
    //         params: { exchangeScore: this.detailInfo.exchange_score, isExchange: this.isExchange }
    //       })
    //     }
    //   })
    // },

    addShopCart(params) {
      cartAdd(params.product, JSON.parse(params.property), params.amount).then(
        res => {
          this.$parent.$emit('start-addcart-animation')
          this.saveNumber(this.numbers)
          this.fetchCartNumber()
          Toast('成功加入购物车')
          this.$parent.$emit('end-addcart-animation')
        },
        error => {
          Toast(error.errorMsg)
        }
      )
    },

    keyDown(event) {},

    /*
     * buildProperties: 构建多属性， 为每个规格添加当前点击的id值
     */
    buildProperties(isbckfill) {
      if (this.detailInfo && this.detailInfo.properties && this.detailInfo.properties.length > 0) {
        let properties = this.detailInfo.properties,
          len = properties.length,
          currentId = []
        // 循环 最外层的属性
        for (let p = 0; p <= len - 1; p++) {
          properties[p].currentIndex = ''
          //
          let attrs = properties[p].attrs
          for (let a = 0; a <= attrs.length - 1; a++) {
            this.setIsHasStock(p, attrs[a].id, attrs[a])
          }
          if (isbckfill) {
            // 数据回填
            if (this.ids.length > 0) {
              for (let x = 0; x <= this.ids.length - 1; x++) {
                if (x == p && this.ids[x]) {
                  properties[p].currentIndex = this.ids[x]
                }
              }
            }
            if (properties[p].currentIndex) {
              currentId[p] = properties[p].currentIndex
            }
          }
        }
        for (let i = 0; i <= currentId.length - 1; i++) {
          if (currentId[i]) {
            let newsData = this.getNewDataById(i, currentId[i])
            this.getCurrentStock(i, currentId[i], newsData)
          }
        }
      }
    },

    /*
     * setIsHasStock: 设置是否还有库存
     */
    setIsHasStock(index, id, item) {
      let data = this.detailInfo.stock,
        count = 0
      for (let s = 0; s <= data.length - 1; s++) {
        if (data[s].goods_attr.indexOf('' + id + '') >= 0) {
          count = count + data[s].stock_number
          if (count > 0) {
            item.ishasstock = true
          } else {
            item.ishasstock = false
          }
        }
      }
      this.detailInfo.properties = Object.assign([], this.detailInfo.properties)
    },

    /*
     * setCurrentIndex: 设置当前选中的规格的id,
     * @parmas: index 当前规格的index
     * @parmas: keyid 当前选择的规格的index
     */
    setCurrentIndex(index, keyid, item) {
      this.info = []
      this.ids = []
      // 设置展示规格图片
      // this.setPropertiesImg(index, keyid, item)
      // 点击选中或者取消选中状态
      this.cancalSelectedStatus(index, keyid, item)
      // 获取当前点击的id
      this.getIds()
      // 点击规格，判断关联属性是否有库存
      let newDatas = this.getNewDataById(index, this.detailInfo.properties[index].currentIndex)
      this.getCurrentStock(index, this.detailInfo.properties[index].currentIndex, newDatas)
      this.detailInfo.properties = Object.assign([], this.detailInfo.properties)
      this.getGoodsStock()
    },

    /*
     * cancalSelectedStatus: 选中或者取消选中状态
     */
    cancalSelectedStatus(index, keyid, item) {
      const currentId = this.detailInfo.properties[index].currentIndex
      if (currentId == item.id) {
        // 取消选中
        this.detailInfo.properties[index].currentIndex = ''
        this.editStackChoosedIds({ action: 'remove', id: item.id })
      } else {
        currentId && this.editStackChoosedIds({ action: 'remove', id: currentId })
        this.detailInfo.properties[index].currentIndex = item.id
        this.editStackChoosedIds({ action: 'add', id: item.id })
      }
      this.resetStock()
    },

    /*
     * resetStock
     */
    resetStock() {
      let properties = this.detailInfo.properties,
        len = properties.length
      // 循环 最外层的属性
      for (let p = 0; p <= len - 1; p++) {
        if (!properties[p].currentIndex) {
          let attrs = properties[p].attrs
          for (let a = 0; a <= attrs.length - 1; a++) {
            this.setIsHasStock(p, attrs[a].id, attrs[a])
          }
        }
      }
    },

    /*
     * getNewDataById: 根据id获取数据
     */
    getNewDataById(index, currentid) {
      if (currentid) {
        let data = this.detailInfo.stock,
          newData = []
        for (let i = 0; i <= data.length - 1; i++) {
          let goods_attr = data[i].goods_attr.split('|')
          for (let j = 0; j <= goods_attr.length - 1; j++) {
            if (goods_attr[j] == currentid && j == index) {
              newData.push(data[i])
            }
          }
        }
        return newData
      }
    },

    /*
     * getCurrentStock: 获取当前
     */
    getCurrentStock(index, currentid, newDatas) {
      if (newDatas) {
        let properties = this.detailInfo.properties
        for (let i = 0; i <= properties.length - 1; i++) {
          if (i != index) {
            //  循环每个属性下的规格
            let attrs = properties[i].attrs
            for (let j = 0; j <= attrs.length - 1; j++) {
              // 循环 库存
              let count = 0
              for (let n = 0; n <= newDatas.length - 1; n++) {
                if (newDatas[n].goods_attr.indexOf('' + attrs[j].id + '') >= 0) {
                  count += newDatas[n].stock_number
                }
              }
              if (count > 0) {
                attrs[j].ishasstock = true
              } else {
                attrs[j].ishasstock = false
              }
            }
          }
        }
        this.detailInfo.properties = Object.assign([], this.detailInfo.properties)
      } else {
        // this.buildProperties(false);
      }
    },

    /*
     * getIds: 获取选中的id
     */
    getIds() {
      let data = this.detailInfo.properties
      for (let i = 0, len = data.length - 1; i <= len; i++) {
        if (data[i].currentIndex) {
          this.ids[i] = data[i].currentIndex
        }
        for (let j = 0; j <= data[i].attrs.length - 1; j++) {
          if (data[i].attrs[j].id == data[i].currentIndex) {
            this.info.push(data[i].attrs[j].attr_name)
            this.info.push(j)
          }
        }
      }
      let info = {
        specification: this.info,
        ids: this.ids
      }
      this.saveChooseInfo(info)
    },

    /*
     * fromatArray: 格式化数组
     */
    fromatArray(delimiter, arrays) {
      let data = ''
      if (delimiter) {
        data = arrays.join(delimiter)
      }
      return delimiter ? data : arrays
    },

    /*
     * isHasStock: 是否还有库存
     */
    isHasStock(id) {
      let data = this.detailInfo.stock
      if (data.length > 0) {
        for (let i = 0, len = data.length; i <= len - 1; i++) {
          if (data[i].goods_attr == id) {
            return '' + data[i].stock_number + ''
          }
        }
      }
    },

    /**
     * 选择分期方式
     *
     * @param      {Number}  id      当前分期方式的id
     */
    istlmtClick(id) {
      this.saveInstalmentWay(id)
    },

    /**
     * 每期 or 首期
     */
    txtPaymentType(item) {
      if (item.method == 5) {
        return ''
      }
      return this.detailInfo.id == '9338' ? '/首期' : '/期'
    }
  }
}
</script>

<style lang="scss" scoped>
.mint-popup-bottom {
  overflow: initial;
  height: 78%;
}
.ui-add-shopping {
  /deep/ label {
    font-weight: 400;
  }
  .shopping-info {
    display: flex;
    flex-direction: column;
    background: rgba(255, 255, 255, 1);
    height: 100%;
    position: absolute;
    width: -webkit-fill-available;
    /* bottom: 0; */
    z-index: 10;
    width: 100%;
    .info-header {
      padding: 20px 15px 15px;
      display: flex;
      justify-content: flex-start;
      padding-bottom: 25px;
      img.info-image {
        width: 110px;
        height: 110px;
        border-radius: 3px;
        background-color: #f9f9f9;
      }
      div.info-header-right {
        margin-left: 15px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        position: relative;
        .header-price {
          position: relative;
          color: #b75800;
          font-size: 19px;
          font-weight: bold;
          font-size: 0;
          &.info-price {
            display: flex;
            align-items: baseline;
            span {
              font-weight: 600;
            }
          }
          span {
            font-size: 19px;
          }
          .product-money-icon {
            width: 14px;
            height: 14px;
          }
          .price-unit {
            font-size: 14px;
          }
        }
        & > span {
          display: block;
          color: #8f8e94;
          &.prod-activity {
            img {
              vertical-align: middle;
              padding-right: 10px;
              width: 50px;
              height: 20px;
            }
            span {
              display: inline;
              font-size: 14px;
              line-height: 14px;
              padding-bottom: 9px;
              padding-top: 12px;
            }
          }
          &.choose-num {
            line-height: 20px;
            font-size: 12px;
            line-height: 17px;
            color: #888;
            padding-top: 12px;
            // width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 15px;
            float: left;
          }
        }
        .choose-info {
          display: flex;
          justify-content: space-between;
          align-items: flex-end;
          color: #404040;
          margin-top: 5px;
          .prod-num {
            @include sc(11px, #999999);
            white-space: nowrap;
          }
        }
      }
      img.close {
        position: absolute;
        top: 0;
        right: 0;
        width: 12px;
        height: 12px;
        cursor: pointer;
        opacity: 1;
      }
    }
    div.goods-detail-properties {
      width: 100%;
      overflow: auto;
      flex: 1;
    }
    div.goods-properties {
      padding: 0 15px;
      & + .goods-properties {
        padding-top: 25px;
      }
      p {
        font-size: 13px;
        color: #999999;
        // line-height: 18px;
        margin: 0;
        padding: 0;
      }
      div.properties-list {
        div {
          display: inline-block;
          margin-top: 10px;
          margin-right: 10px;
          span {
            font-size: 12px;
            color: #b75800;
            line-height: 18px;
            background: #ffffff;
            border: 1px solid rgba(183, 88, 0, 0.5);
            display: inline-block;
            padding: 6px 23px;
            border-radius: 2px;
            cursor: pointer;
            &.active-properties {
              color: #ffffff;
              background: #fc7f0c;
              border: 1px solid #fc7f0c;
            }
            &.disabled-properties {
              opacity: 0.2;
            }
          }
        }
      }
    }
    .info-body {
      padding: 15px 15px 20px;
      margin-top: 10px;
      p {
        margin: 0;
        font-size: 13px;
        color: #999;
        padding: 0;
        padding-bottom: 12px;
      }
      div.ui-number {
        display: flex;
        border-radius: 3px 0 0 3px;
        input,
        div {
          height: 35px;
          text-align: center;
          color: #552e20;
          display: inline-block;
          padding: 0;
          margin: 0;
          border: 0;
          outline-offset: 0;
          border: 1px solid rgba(85, 46, 32, 0.5);
        }
        .ui-common {
          width: 47px;
          height: 35px;
          line-height: 33px;
          font-size: 18px;
          cursor: pointer;
        }
        .reduce {
          border-top-left-radius: 2px;
          border-bottom-left-radius: 2px;
          border-right-width: 0;
        }
        .reduce-opacity {
          border-color: #ccc;
          color: #ccc;
        }
        .add {
          border-top-right-radius: 2px;
          border-bottom-right-radius: 2px;
          border-left-width: 0;
        }
        input[type='number'] {
          width: 67px;
          border-radius: 0;
          border-image-width: 0;
          box-shadow: 0;
          vertical-align: bottom;
          background-color: #ffffff;
          &:focus {
            outline: none;
          }
        }
      }
    }
    .instalment-wrapper {
      padding: 0 15px 20px;
      .istlmt-w-title {
        margin: 0;
        font-size: 13px;
        color: #999;
        padding: 0;
        padding-bottom: 12px;
      }
      .istlmt-w-item {
        border-radius: 2px;
        padding: 12px 10px 12px 0;
        color: #552e20;
        border: 1px solid rgba(85, 46, 32, 0.5);
        display: flex;
        font-size: 12px;
        align-items: center;
        position: relative;
        box-sizing: border-box;
        & + .istlmt-w-item {
          margin-top: 15px;
        }
        &.active {
          border: 1px solid rgba(196, 125, 29, 1);
          background-color: rgba(196, 125, 29, 1);
          color: #ffffff;
          .desc span {
            color: rgba(255, 255, 255, 0.7);
          }
        }
        img {
          position: absolute;
          left: -1px;
          top: -1px;
          width: 52px;
        }
      }
      .istlmt-w-num {
        text-align: right;
        font-size: 14px;
        width: 75px;
        flex: 0 0 75px;
      }
      .istlmt-w-price {
        flex: 1 0 0;
        padding-left: 6px;
        line-height: 18px;
        .total {
          font-size: 14px;
          font-family: PingFangSC-Medium;
          font-weight: 500;
        }
        .desc {
          margin-top: 3px;
          @include sc(11px, rgba(85, 46, 32, 0.7), left center);
        }
        span + span {
          margin-left: 12px;
        }
      }
    }
    .info-footer {
      width: 100%;
      display: flex;
      .footer-button {
        flex: 1;
        @include button($margin: 0, $radius: 0);
        font-size: 16px;
        height: 50px;
        line-height: 50px;
        label {
          font-weight: 400;
        }
        &.add-cart-btn {
          color: #552e20;
          background-color: #fafafa;
        }
        &.buy-now-btn {
          color: #ffffff;
          background-color: #772508;
        }
      }
    }
  }
}
</style>
