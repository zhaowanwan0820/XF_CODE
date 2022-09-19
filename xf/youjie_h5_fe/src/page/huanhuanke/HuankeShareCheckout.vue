<template>
  <div class="container" :class="{ static: currentIndex == 4 }">
    <div class="container-wrapper" v-show="currentIndex == 1">
      <mt-header class="header" title="分销返佣详情">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        <template slot="right">
          <span class="header-right" @click="showHHDesc">赚钱攻略</span>
        </template>
      </mt-header>
      <div class="mlm-content">
        <div class="mlm-prod-msg">
          <div class="product-img">
            <img :src="mlmProduct.thumb" v-if="mlmProduct.thumb" />
            <img src="../../assets/image/change-icon/default_image_02@3x.png" v-else />
          </div>
          <div class="product-name">{{ mlmProduct.name }}</div>
          <div class="origin-price" v-if="mlmProduct.origin_price">
            原价:￥{{ utils.formatMoney(mlmProduct.origin_price.price) }}
          </div>
        </div>
        <div class="price-content" v-if="shop_price_info">
          <huanke-share-checkout-set-price
            v-if="isLoad"
            :data="shop_price_info"
            :slots="slots"
            :act_desc="act_desc"
            :key="`setPriceKey${setPriceKey}`"
            @change="priceChanged"
            @update="priceUpdate"
            class="choose-price"
          ></huanke-share-checkout-set-price>
        </div>

        <share-say v-if="isLoad" v-model="comment"></share-say>
        <div class="rules">
          <input
            type="checkbox"
            id="checkbox"
            v-model="readCheckbox"
            class="rules-inpput"
            name="rules"
            :class="{ checked: readCheckbox }"
          />
          <label class="input-icon" for="checkbox"></label>
          <label class="rules-msg" for="checkbox">我已阅读并同意</label
          ><span @click.stop="changePage(3)">《分销返佣规则》</span>
        </div>
        <div class="action-wrapper">
          <gk-button class="button" v-if="readCheckbox" type="primary-secondary" v-on:click="saveAndShare">{{
            buttonTxt
          }}</gk-button>
          <gk-button class="button  disable" v-else type="primary-secondary">{{ buttonTxt }}</gk-button>
        </div>
      </div>
    </div>
    <div class="container-wrapper" v-show="currentIndex == 3">
      <mt-header class="header rules-header" title="分销返佣规则">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="changePage(1)"></header-item>
      </mt-header>
      <div class="rules-content">
        <div class="mlm-version">
          <span class="name name-rules">{{ utils.mlmUerName }}</span>
          <span class="version version-rules"></span>
        </div>
        <!-- 外部分销客 -->
        <!-- <template v-if="shop_price_info && shop_price_info.virtual_coin_price == 0"> -->
        <template>
          <p>
            1、分享商品后，买家通过您分享的链接（或海报中的二维码）购买此商品，您可以在【我的小店】-【付款笔数】-【全部订单】列表中查询到订单。
          </p>

          <p>2、您分享到微信朋友圈或微信群的分销商品链接，可被多个买家同时购买，您可获得多笔订单佣金。</p>

          <p>
            3、售卖后佣金会即时到账（冻结中），该订单买家【确认收货】7天后，该笔订单佣金将可提现。若该订单在买家【确认收货】7天内发生部分退款，则该订单视作【已取消】，全部佣金将原路退回。
          </p>

          <p>
            4、分销返佣规则及解释权归{{
              utils.storeName
            }}商城所有，如您对该规则有疑问，或有其他意见建议，请按照以下联系方式与我们联系。
          </p>

          <p>
            电话：{{ service_tel }}<br />
            邮箱：{{ utils.hhkEmail }}<br />
            售后QQ群：994056656（进群填写订单号）
          </p>
        </template>
        <!-- 没有代付， 去掉 -->
        <!-- <template v-else>
          <p>
            1、分享商品后，买家通过您分享的链接（或海报中的二维码）购买此商品，您可以在【我的小店】-【付款笔数】-【全部订单】列表中查询到订单，可进行代付积分并获得变现。
          </p>
        
          <p>
            2、您分享到微信朋友圈或微信群的分销商品链接，可被多个买家同时购买，代付积分后您可获得多笔变现。
          </p>
        
          <p>
            3、在分销订单中代付积分后，对应的变现现金及佣金会即时到账（冻结中），该订单买家【确认收货】7天后，该笔订单的变现现金及佣金将可提现。若该订单在买家【确认收货】7天内发生部分退款，则该订单视作【已取消】，全部佣金、代付的积分、变现的金额将原路退回。
          </p>
        
          <p>
            4、分销订单的代付积分有效期为买家【支付成功】至【确认收货】（7+30）天，超时后该笔订单将无法代付；没代付积分不影响平台佣金到账，平台佣金在买家确认收货7日后自动解冻，即时提现。
          </p>
        
          <p>
            5、分销返佣规则及解释权归{{ utils.storeName }}商城所有，如您对该规则有疑问，或有其他意见建议，请按照以下联系方式与我们联系。
          </p>
        
          <p>
            电话：{{ service_tel }}<br />
            邮箱：{{ utils.hhkEmail }}<br />
            售后QQ群：994056656（进群填写订单号）
          </p>
        </template> -->
      </div>
      <div class="rules-bottom">
        <gk-button class="button" type="primary-secondary" @click="agreeRules">同意</gk-button>
      </div>
    </div>
    <template v-if="share_options.url">
      <popup-mlm-share
        ref="mlmSharePopup"
        v-model="mlmShare"
        :options="options"
        :share_options="share_options"
      ></popup-mlm-share>
    </template>
  </div>
</template>
<script>
import { Toast } from 'mint-ui'
import { BANNERLINK } from './static'
import { MessageBox } from 'mint-ui'
import { HeaderItem, PopupMlmShare } from '../../components/common'
import { productDistGet, saveHasRead } from '../../api/product'
import { shareMlmProduct, mlmProductGet, getExclusivePrice } from '../../api/mlm'
import { sendBuryingPointInfo } from '../../api/buryingPoint'
import { ENUM } from '../../const/enum'
import { mapState, mapMutations } from 'vuex'
import HuankeShareCheckoutSetPrice from './child/HuankeShareCheckoutSetPrice'
import ShareSay from './child/ShareSay'
const SHARE_SAY_PLANS = [
  '我发现了一件物美价廉的商品，喜欢的话就出手吧！',
  '亲身体验，物美价廉，性价比超高！',
  '这里的价格本来就比X东X猫便宜，下单确认收货后，还能找领红包呦~~'
]

export default {
  name: 'HuankeAccount',
  data() {
    return {
      isLoad: false,
      SHARE_SAY_PLANS,
      activeIndex: null,

      currentIndex: 1, // 当前展示内容 1--详情; 2--分销客介绍; 3--分销返佣规则; 4--创建分销返佣海报
      user_money: 300, // 账户佣金
      readCheckbox: false,

      comment: '', // 分销客说
      sharePrice: 0, // 分销价
      act_desc: '', // 活动描述
      shareId: null,

      readRulesCount: ENUM.HAS_READ_CONFIG.MLM_RULE,

      id: this.$route.query.id || null,
      mlm_id: this.$route.query.mlm_id || null,
      isShop: this.$route.query.isShop || false,

      // 分享相关
      mlmShare: false,
      options: ['WechatSession', 'WechatTimeline', 'QQ', 'Qzone'],
      share_options: {
        text: '',
        imageUrl: '',
        flag: '',
        title: '',
        url: '',
        description: ''
      },

      setPriceKey: 0,
      shop_price_info: null, // 分销价信息
      slots: [
        // 价格选择滚动框
        {
          flex: 1,
          values: [],
          defaultIndex: 0,
          className: 'price-swiper-list',
          textAlign: 'center'
        }
      ],
      service_tel: ENUM.SERVICE.MASTER_TEL
    }
  },

  components: {
    HuankeShareCheckoutSetPrice,
    ShareSay
  },

  computed: {
    ...mapState({
      mlmProduct: state => state.mlm.mlmProduct,
      isOnline: state => state.auth.isOnline,
      user: state => state.auth.user
    }),
    getShareUrl() {
      let origin = window.location.origin
      let url = origin + '/h5/#/buyerProduct/' + this.shareId
      return url
    },
    buttonTxt() {
      let txt
      if (this.isShop) {
        txt = '保存并上架'
      } else {
        txt = '分销此商品'
      }
      return txt
    }
  },
  created() {
    if (!this.isOnline) {
      this.$_goBack()
    }

    if (this.user.read_marker && this.user.read_marker.indexOf(this.readRulesCount) > -1) {
      this.readCheckbox = true
    }

    let p1
    if (this.id && this.mlm_id) {
      p1 = this.getMlmProduct(false)
    } else if (this.id) {
      p1 = this.getMlmProduct(true)
    }
    let p2 = this.getMlmPrice()

    Promise.all([p1, p2]).then(res => {
      if (res[0].good_stock == 0 && !this.isShop) {
        MessageBox({
          title: '',
          message: '商品太火爆，已经售罄了。</br>商家补货后才可以继续购买，确定继续分销该商品吗？',
          showCancelButton: true,
          closeOnClickModal: false,
          cancelButtonText: '取消',
          cancelButtonClass: 'cancel-button',
          confirmButtonClass: 'confirm-button-red',
          confirmButtonText: '确认'
        }).then(action => {
          if (action === 'cancel') {
            this.$_goBack()
          }
        })
      }
      this.act_desc = res[1].act_desc
      res[1].base_price = parseFloat(res[1].base_price)
      this.slots[0].values = res[1].exc_price
      this.shop_price_info = res[1]
      this.slots[0].defaultIndex = res[1].exc_price.indexOf(res[1].base_price)
      if (this.slots[0].defaultIndex == -1) {
        this.slots[0].defaultIndex = 0
      }
      this.$nextTick(() => {
        this.isLoad = true
      })
    })
  },
  mounted() {
    window.onresize = function() {
      let dom = document.querySelector('.mlm-content')
      dom.scrollTop = 500
    }
    if (this.utils.getOpenBrowser() == 1) {
      document.body.addEventListener('focusin', () => {
        // 软键盘弹起事件
        clearTimeout(window.focusoutTimer)
      })
      document.body.addEventListener('focusout', () => {
        // 软键盘关闭事件
        clearTimeout(window.focusoutTimer)
        window.focusoutTimer = setTimeout(function() {
          window.scrollTo({ top: 0, left: 0, behavior: 'smooth' }) // 重点  =======当键盘收起的时候让页面回到原始位置
        }, 200)
      })
    }
  },
  methods: {
    ...mapMutations({
      saveMlmProduct: 'saveMlmProduct',
      clearMlmProduct: 'clearMlmProduct',
      saveUser: 'saveUser'
    }),
    inputSay() {
      if (this.comment.length > 50) {
        this.comment = this.comment.slice(0, 50)
      }
    },
    goBack() {
      this.$_goBack()
    },
    showHHDesc() {
      // 分销返佣攻略跳转到url
      window.location.href = BANNERLINK
    },
    saveAndShare() {
      const params = {
        goods_id: this.mlmProduct.id,
        remark: this.comment,
        shop_price: this.sharePrice,
        is_shop: this.isShop || 0
      }
      if (!this.user.read_marker || this.user.read_marker.indexOf(this.readRulesCount) == -1) {
        // 保存已读规则信息
        saveHasRead(this.readRulesCount).then(res => {
          let arr = this.user.read_marker ? [...this.user.read_marker] : []
          arr.push(this.readRulesCount)
          this.saveUser({ ...this.user, ...{ read_marker: arr } })
        })
      }
      shareMlmProduct(params).then(res => {
        if (!this.isShop) {
          this.shareProduct(res)
          // 分销单品按钮埋点
          // sendBuryingPointInfo({
          //   click_position: 'share_retail_product'
          // })
        } else {
          this.$_goBack()
        }
      })
    },
    agreeRules() {
      this.readCheckbox = true
      this.changePage(1)
    },
    changePage(index) {
      this.currentIndex = index
    },

    /**
     * 分享分销商品
     *
     * @param      {object}  res     分销商品的信息
     */
    shareProduct(res) {
      this.shareId = res.id
      this.share_options = {
        text: `￥${this.sharePrice} | 买到就省${this.mlmProduct.origin_price.price - this.sharePrice}！${this.comment ||
          this.SHARE_SAY_PLANS[0]}`,
        imageUrl: this.mlmProduct.thumb,
        flag: 'hh-mlm-share',
        title: this.mlmProduct.name,
        url: this.getShareUrl,
        description: `用户${this.user.id}对商品${this.mlmProduct.id}的分销分享`
      }
      setTimeout(() => {
        this.$refs.mlmSharePopup.open()
      }, 10)

      let _this = this
      window.onResume = function() {
        // wap 页面再次显示时调用
        window.onResume = null
        window.shareSuccessed = null

        if (_this.currentIndex != 4) {
          return
        }
        _this.$_goBack()
      }
      window.shareSuccessed = function() {
        // 分享成功后调用
        window.onResume = null
        window.shareSuccessed = null

        if (_this.currentIndex != 4) {
          return
        }
        _this.$_goBack()
      }
    },

    /**
     * 获取待分销商品信息
     *
     * @param      {boolean}  isMlm   true--请求待分销商品详情，false--请求买家侧看到的分销商品
     */
    getMlmProduct(isMlm) {
      return new Promise((resolve, reject) => {
        if (isMlm) {
          mlmProductGet(this.id).then(
            res => {
              this.saveMlmProduct(res)
              resolve(res)
            },
            err => {
              console.log(err)
            }
          )
        } else {
          productDistGet(this.mlm_id).then(
            res => {
              this.saveMlmProduct(res)
              this.comment = res.seller.remark
              resolve(res)
            },
            err => {
              console.log(err)
            }
          )
        }
      })
    },

    /**
     * 选择分销价格
     *
     * @param      {number}  value   分销价
     */
    priceChanged(value) {
      this.sharePrice = value
    },

    /**
     * 手动修改分销价格
     *
     * @param      {number}  value   分销价
     */
    async priceUpdate(value) {
      let valueIndex = this.shop_price_info.exc_price.indexOf(value)
      if (valueIndex > -1) {
        // 输入的数据在原来的数据列表里面只需修改一下默认位置即可（插件未提供跳转到指定位置的接口）
        this.slots[0].defaultIndex = valueIndex
      } else {
        const res = await this.getMlmPrice({ price: value })
        res.base_price = parseFloat(res.base_price)
        this.shop_price_info = res
        this.slots[0].values = res.exc_price
        this.slots[0].defaultIndex = res.exc_price.indexOf(res.base_price)
        if (this.slots[0].defaultIndex == -1) {
          this.slots[0].defaultIndex = 0
        }
      }

      // 通过子组件的key值变化来是组件自动更新
      this.setPriceKey += 1
    },

    /**
     * 获取分销价格信息
     */
    getMlmPrice(exit_params) {
      const base_params = {
        goods_id: this.id,
        is_single: this.$route.query.is_single
      }
      const params = { ...base_params, ...exit_params }
      return new Promise((resolve, reject) => {
        getExclusivePrice(params).then(
          res => {
            resolve(res)
          },
          err => {
            console.log(err)
          }
        )
      })
    }
  },
  beforeRouteLeave(to, from, next) {
    this.clearMlmProduct()
    window.onresize = null
    next()
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  .container-wrapper {
    height: 100%;
    display: flex;
    position: relative;
    flex-direction: column;
    justify-content: flex-start;
    background: #fff;
  }
}
.header {
  flex-basis: 50px;
  @include header;
  @include thin-border();
  .header-right {
    @include sc(16px, #552e20);
  }
  &.rules-header {
    /deep/ .mint-header-button {
      flex: 0.4;
    }
  }
  .share-icon /deep/ .icon {
    width: 18px;
    height: 18px;
  }
}
.mlm-content {
  flex: 1;
  background-color: #f4f4f4;
  overflow-y: auto;
  .mlm-prod-msg {
    padding: 17px 0 60px;
    background-color: #ffffff;
    background-image: url('../../assets/image/hh-icon/mlm/mlm-share-msg-bg.png');
    background-repeat: no-repeat;
    background-size: 100%;
    background-position: left top;
    .product-img {
      box-shadow: 0px 2px 4px 0px rgba(244, 231, 222, 0.58);
      margin: 0 auto;
      width: 180px;
      height: 180px;
      overflow: hidden;
      border-radius: 4px;
      img {
        width: 100%;
      }
    }
    .product-name {
      font-size: 14px;
      color: #666666;
      font-family: PingFangSC;
      font-weight: bold;
      line-height: 20px;
      margin: 15px 15px 0;
      height: 20px;
      text-align: center;
      word-break: break-word;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      /*! autoprefixer: ignore next */
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 1;
    }
    .origin-price {
      text-align: center;
      font-size: 13px;
      font-family: PingFangSC;
      font-weight: bold;
      color: rgba(102, 102, 102, 1);
      line-height: 18px;
      margin-top: 7px;
    }
  }
  .price-content {
    position: relative;
    padding: 0 15px 10px;
    z-index: 2;
    &:before {
      z-index: 0;
      content: '';
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 10px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 1) 0%, rgba(244, 244, 244, 1) 100%);
    }
    .choose-price {
      position: relative;
      z-index: 1;
      background: #ffffff;
    }
  }
  .rules {
    display: flex;
    align-items: center;
    margin-top: 20px;
    padding: 0 15px;
    label.input-icon {
      display: inline-block;
      @include wh(14px, 14px);
      background-size: 100%;
      border: 1px solid #b89385;
      border-radius: 1px;
      background-color: #ffffff;
      margin-right: 10px;
    }
    input {
      display: none;
      &:checked + label.input-icon {
        background-color: #772508;
        background-image: url('../../assets/image/hh-icon/icon-checkbox-active.png');
      }
      &:disabled + label.input-icon {
        visibility: hidden;
      }
    }
    .rules-msg {
      font-size: 12px;
      color: #666666;
      & + span {
        font-size: 12px;
        color: #552e20;
      }
    }
  }
  .action-wrapper {
    padding: 0 25px;
    margin: 30px 0;
  }
  .button {
    display: block;
    width: 100%;
    @include button($margin: 0, $radius: 2px, $spacing: 1px);
  }
}
.rules-content {
  flex: 1;
  padding: 20px 15px;
  overflow-x: hidden;
  overflow-y: auto;
  p {
    font-size: 12px;
    color: #666;
    line-height: 21px;
    margin-bottom: 20px;
  }
}
.rules-bottom {
  height: 44px;
  .button {
    display: block;
    width: 100%;
    @include button($margin: 0 0, $radius: 0, $spacing: 1px);
  }
}
.mlm-version {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  margin: 5px 0 20px;
  .name {
    line-height: 1;
    @include sc(15px, #999999);
    font-family: PingFangSC-Regular;
    &.name-rules {
      color: #707070;
    }
  }
  .version {
    display: inline-block;
    width: 21px;
    height: 9px;
    background-size: 100%;
    background-repeat: no-repeat;
    background-image: url('../../assets/image/hh-icon/mlm/mlm-share-checkout.png');
    margin-left: 3px;
    &.version-rules {
      background-image: url('../../assets/image/hh-icon/mlm/mlm-share-rule.png');
    }
  }
}
</style>
