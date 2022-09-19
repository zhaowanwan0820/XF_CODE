<template>
  <div class="container">
    <mt-header class="header" title="选择债权">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      <header-item slot="right" title="兑换规则" v-on:onclick="goRules" v-if="order"></header-item>
    </mt-header>
    <div class="bond-wrapper">
      <div class="bond-content-wrapper">
        <mt-navbar class="navbar-container" v-model="selectedNavbar">
          <mt-tab-item class="navbar-container-item" id="1">尊享</mt-tab-item>
          <mt-tab-item class="navbar-container-item" id="2">普惠</mt-tab-item>
        </mt-navbar>
        <!-- tab-container -->
        <mt-tab-container class="tab-container" v-model="selectedNavbar">
          <mt-tab-container-item class="tab-container-item" id="1">
            <base-list
              class="items-wrapper"
              :items="items"
              :isMore="isMore"
              :isLoaded="isLoaded"
              v-on:loadMore="loadMore"
            >
<!--              <p class="title-confirmed-debt" v-if="items.length > 0">已确认的债权：</p>-->
              <bond-debt-item
                v-for="(item, index) in items"
                :key="index"
                :index="index"
                :item="item"
                :checkId="checkId[index]"
                :disabledCheckbox="disabledCheckbox"
                @clickBondId="clickBondId"
              >
              </bond-debt-item>
              <empty-list
                :type="selectedNavbar"
                :isHasDebt="isHasDebt"
                v-if="isLoaded && items.length === 0"
              ></empty-list>
            </base-list>
          </mt-tab-container-item>
          <mt-tab-container-item class="tab-container-item" id="2">
            <base-list
              v-if="selectedNavbar == '2' || navbar2Inited"
              class="items-wrapper"
              :items="itemsWise"
              :isMore="isMoreWise"
              :isLoaded="isLoadedWise"
              v-on:loadMore="loadMoreWise"
            >
<!--              <p class="title-confirmed-debt" v-if="itemsWise.length > 0">已确认的债权：</p>-->
              <bond-debt-item
                v-for="(item, index) in itemsWise"
                :key="index"
                :index="index"
                :item="item"
                :checkId="checkIdWise[index]"
                :disabledCheckbox="disabledCheckbox"
                @clickBondId="clickBondIdWise"
              >
              </bond-debt-item>
              <empty-list
                :type="selectedNavbar"
                :isHasDebt="isHasWiseDebt"
                v-if="isLoadedWise && itemsWise.length === 0"
              ></empty-list>
            </base-list>
          </mt-tab-container-item>
        </mt-tab-container>
      </div>
      <div class="show-text" v-if="isShowText == 1">
          您现在购买的是0元购活动商品。积分兑换成功后，请及时提交订单！若放弃提交，可能无法继续参与0元购活动。
      </div>
      <div class="wrapper-bottom">
        <div class="content">
          <label>所需债权金额：{{ utils.formatMoney(exchangeBond) }}</label>
          <label
            >转出债权金额：<span class="red">{{ utils.formatMoney(selectedTotal) }}</span></label
          >
        </div>
        <gk-button :type="pass ? 'primary-secondary' : 'disable-secondary'" class="button" v-on:click="pass && submit()"
          >兑换</gk-button
        >
      </div>
    </div>
  </div>
</template>

<script>
import $cookie from 'js-cookie'
import { mapState, mapMutations, mapActions } from 'vuex'
import { HeaderItem, Button, BaseList } from '../../components/common'
import BondDebtItem from './child/BondDebtItem'
import EmptyList from './child/EmptyList'
import { Header, Indicator, MessageBox, Toast } from 'mint-ui'
import { bondList, bondChange, getDebtType } from '../../api/bond'
import { ENUM } from '../../const/enum'
export default {
  name: 'bondDebt',
  components: {
    BondDebtItem,
    EmptyList
  },
  beforeRouteEnter(to, from, next) {
    let routename = ['payment', 'friendPayConfirm', 'checkout', 'paymentHuan']
    if (routename.indexOf(from['name']) != -1) {
      let path = { path: from['path'], query: from['query'] }
      $cookie.set('bondForm', JSON.stringify(path))
    }
    next()
  },
  data() {
    return {
      total: 0,
      pass: false,
      disabledCheckbox: false,

      checkId: [],
      selectedTotal: 0,
      lastAccount: 0,

      isLoaded: false,
      page: 1,
      items: [],
      isMore: 1,

      isLoadedWise: false,
      itemsWise: [],
      checkIdWise: [],
      isMoreWise: 1,
      pageWise: 1,

      selectedNavbar: '1', // 选中的tab
      navbar2Inited: false, // 第二个tab 内容是否已初始化

      isHasDebt: false, // 是否持有 尊享 的债权
      isHasWiseDebt: false, // 是否持有 普惠 的债权

      isExchanging: false, // 防重复提交

      isShowText:''//判断appoint_debt，用户是否参加的是0元购活动
    }
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      exchangeBond: state => state.bond.exchangeBond,
      canExchangePartially: state => state.bond.canExchangePartially,
      order: state => state.bond.exchangeBondOrderId,
      product: state => state.bond.exchangeBondProductId,
      debt_id: state => state.bond.debt_id
    })
  },
  watch: {
    total(total) {
      let pass = false
      let disabledCheckbox = false
      if (total >= this.exchangeBond) {
        let syzq = total - this.exchangeBond // 选择的债权剩余债权
        pass = true
        disabledCheckbox = true
        if (syzq < 100 || (total < 200 && syzq < 200)) {
          // 剩余债权全兑换
          // 兑换后债权小于100
        } else if (this.exchangeBond < 100) {
          // 最少兑换 100 积分
          total = 100
        } else {
          total = this.exchangeBond
        }
      }
      if (this.canExchangePartially && total > 0) {
        pass = true
      }
      this.selectedTotal = Math.abs(total)
      this.pass = pass
      this.disabledCheckbox = disabledCheckbox
    }
  },
  created() {
    // 债权兑换页面的入口 1、确认支付页（下单支付、好友代付） 2、规则说明页 3、无入口 直接进入
    if (!this.isOnline) {
      return this.goBack()
    }

    // 1、从 确认支付页 过来，将相关参数记录到store
    if (this.$route.params.need) {
      this.saveExchangeBondState({
        bond: this.$route.params.need,
        order: this.$route.params.order || '', // 订单id
        product: this.$route.params.product || '', // 商品id
        canPartial: this.$route.params.canPartial, // 只有混合支付(没有订单号)才允许选择部份债权
        debt_id: this.$route.params.debt_id // 兑换债权前生成的临时订单的关联id
      })
    }

    // 2、从 规则说明页 过来 doNothing，相关信息只需要从 store 中获取
    // 3、无入口 直接进入，则 跳回去
    if (!this.exchangeBond) {
      return this.goBack()
    }

    // 查询用户持有的债权类型
    this.getUserDebtType()
  },
  mounted() {
    // 3、无入口 直接进入，则 跳回去
    if (!this.exchangeBond) {
      this.goBack()
    }
  },
  // activated
  methods: {
    ...mapMutations({
      itzAuthGuide: 'itzAuthGuide',
      saveExchangeBondState: 'saveExchangeBondState'
    }),
    ...mapActions({
      helperItzAuthCheck: 'helperItzAuthCheck'
    }),
    loadPageData(type) {
      type = type || 1
      let page
      if (type == 1) {
        page = this.page
        this.page += 1
        this.isLoaded = false
      } else if (type == 2) {
        page = this.pageWise
        this.pageWise += 1
        this.isLoadedWise = false
      }

      const params = {
        page: page,
        per_page: 10,
        product: this.product,
        debt_type: type,
        debt_id: this.debt_id
      }

      const showLoad = !!(1 == page && 1 == type)
      if (showLoad) {
        this.$indicator.open()
      }

      bondList(params)
        .then(res => {
          if (type == 1) {
            this.items = [...this.items, ...res.list]
            this.isMore = res.paged.more
            this.isLoaded = true
          } else if (type == 2) {
            this.itemsWise = [...this.itemsWise, ...res.list]
            this.isMoreWise = res.paged.more
            this.isLoadedWise = true
          }
          this.isShowText = res.appoint_debt
        })
        .finally(() => {
          if (showLoad) {
            this.$indicator.close()
          }
        })
    },
    clickBondId(index) {
      this.checkId[index] = !this.checkId[index]
      // 精度计算修正，若需求量大可以考虑 https://github.com/nefe/number-precision
      this.getTotalCount()
    },
    clickBondIdWise(index) {
      this.checkIdWise[index] = !this.checkIdWise[index]
      // 精度计算修正，若需求量大可以考虑 https://github.com/nefe/number-precision
      this.getTotalCount()
    },
    submit() {
      const msg = `<p class="info">确认兑换积分？</p><p class="subinfo"> 积分用于购物抵现，兑换成功后无法再退回至债权, 请知晓！</p>`
      const sendVal = this.selectedTotal

      MessageBox.confirm(msg, '').then(
        action => {
          if (action === 'confirm') {
            if (this.isExchanging) return

            let bond_ids = this.checkId.reduce((accumulator, val, key) => {
              if (val) {
                accumulator.push(this.items[key].id)
              }
              return accumulator
            }, [])
            let bond_wise_ids = this.checkIdWise.reduce((accumulator, val, key) => {
              if (val) {
                accumulator.push(this.itemsWise[key].id)
              }
              return accumulator
            }, [])

            let time = new Date().getTime()
            const params = {
              account: sendVal,
              bond_ids: bond_ids, // 数组
              bond_wise_ids: bond_wise_ids, // 数组
              order: this.order,
              product: this.product, // 商品id数组
              debt_id: this.debt_id
            }

            this.isExchanging = true
            Indicator.open()

            bondChange(params)
              .then(
                res => {
                  this.$toast('兑换成功')
                  setTimeout(() => {
                    // 成功后 回去来源页
                    this.$_goBack()
                  }, time + 2e3 - new Date().getTime())
                },
                error => {
                  Toast(error.errorMsg)
                  this.$_goBack()
                }
              )
              .finally(() => {
                this.isExchanging = false
                Indicator.close()
              })
          }
        },
        cancel => {}
      )
    },
    loadMore() {
      if (this.isMore) {
        this.loadPageData(1)
      }
    },
    loadMoreWise() {
      if (this.isMoreWise) {
        this.loadPageData(2)
      }
      this.navbar2Inited = true
    },
    goBack() {
      if (window.history.length <= 1) {
        this.$router.push({ path: '/' })
      } else {
        this.$_goBack()
      }
    },
    goRules() {
      this.$router.push('/bondRules')
    },
    clear() {
      this.bond = ''
    },

    /**
     * 计算当前选中的债权总和
     */
    getTotalCount() {
      this.total = parseFloat(
        this.checkId
          .reduce((accumulator, val, key) => {
            if (val) {
              if (accumulator >= this.exchangeBond) {
                this.checkId[key] = false
              } else {
                accumulator += this.$accounting.unformat(this.items[key].account)
                this.lastAccount = this.items[key].account
              }
            }
            return accumulator
          }, 0)
          .toPrecision(12)
      )
      this.total = parseFloat(
        this.checkIdWise
          .reduce((accumulator, val, key) => {
            if (val) {
              if (accumulator >= this.exchangeBond) {
                this.checkIdWise[key] = false
              } else {
                accumulator += this.$accounting.unformat(this.itemsWise[key].account)
                this.lastAccount = this.itemsWise[key].account
              }
            }
            return accumulator
          }, this.total || 0)
          .toPrecision(12)
      )
    },
    // 查询用户持有的债权类型
    getUserDebtType() {
      getDebtType().then(res => {
        this.isHasDebt = res.debt_type == 1 || res.debt_type == 3
        this.isHasWiseDebt = res.debt_type == 2 || res.debt_type == 3
      })
    }
  }
}
</script>

<style scoped>
.mint-msgbox .info {
  color: #404040;
  font-size: 15px;
  line-height: 24px;
}
.mint-msgbox .subinfo {
  color: #888;
  font-size: 12px;
  line-height: 18px;
  margin-top: 10px;
}
</style>
<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  height: 100%;
  width: 100%;
  background-color: $mainbgColor;

  .header {
    @include header;
    @include thin-border(#f4f4f4, 0, 0);
  }
  .button {
    @include button($margin: 10px 20px 10px);
    width: 90px;
  }
  .bond-wrapper {
    flex: 1 0 0;
    display: flex;
    flex-direction: column;
  }
  .bond-content-wrapper {
    flex: 1 0 0;
    display: flex;
    flex-direction: column;
    position: relative;
    flex-grow: 1;
    overflow: hidden;
    .navbar-container {
      @include thin-border(#f4f4f4, 0, 0);
      flex-shrink: 0;
      .navbar-container-item {
        box-sizing: border-box;
        padding: 10px;
        &.is-selected {
          margin-bottom: 0;
          border: none;
          color: #fc7f0c;
          position: relative;
          &:before {
            content: '';
            display: block;
            width: 50px;
            height: 2px;
            background-color: #fc7f0c;
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
          }
        }
      }
    }
    .tab-container {
      flex: 1 0 0;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      /deep/ .mint-tab-container-wrap {
        overflow: hidden;
        flex: 1 0 0;
      }
      .tab-container-item {
        display: flex;
        flex-direction: column;
      }
    }
  }
  .items-wrapper {
    overflow: auto;
    flex: 1 0 0;
  }
  .wrapper-bottom {
    color: $baseColor;
    font-size: 14px;
    line-height: 19px;
    background-color: #fff;
    border-top: 1px solid #ccc;
    display: flex;
    height: 64px;
    .content {
      width: 100%;
      display: flex;
      flex: 1 0 0;
      flex-direction: column;
      padding: 8px 20px;
    }
    label {
      flex: 1 0 0;
      .red {
        color: #fc7f0c;
      }
    }
  }
  .title-confirmed-debt {
    font-size: 12px;
    color: #666;
    margin-top: 10px;
    padding-left: 20px;
  }
  .show-text{
    padding: 0 15px;
    height:66px;
    background:rgba(255,228,202,1);
    font-size:13px;
    font-family:PingFangSC-Regular,PingFang SC;
    font-weight:400;
    color:rgba(255,111,0,1);
    line-height:18px;
    display: flex;
    align-items: center;
  }
}
</style>
