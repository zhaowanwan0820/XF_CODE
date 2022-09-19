<template>
  <div class="debt-wrapper">
    <common-header />
    <main class="main">
      <van-dropdown-menu :overlay="false" active-color="#3833df" ref="menu">
        <van-dropdown-item v-model="currentType" :options="options" />
      </van-dropdown-menu>
      <van-list
        class="list"
        v-model="loading"
        :finished="finished"
        :finished-text="finishedText"
        @load="onLoad"
        :error.sync="error"
        error-text="请求失败，点击重新加载"
      >
        <template v-for="item in dataList">
          <div class="item" :key="item.id" @click="onClick(item)">
            <van-row>
              <van-col span="18">
                <p class="title">{{ item.name }}</p>
                <p class="account">{{ item.account }}</p>
                <p class="unit">债权金额（元）</p>
                <p class="date">{{ item.addtime | timestampToDayFilter }} 出借</p>
              </van-col>
              <van-col span="6">
                <span class="reserve" v-if="item.is_exchanging == 1">兑换处理中</span>
                <i class="i-check" :class="{ 'i-check-s': item._use }" v-else-if="!finalConfirm || item._use"></i>
              </van-col>
            </van-row>
          </div>
        </template>
      </van-list>
      <div v-if="currentType" class="amount">
        <p class="need">兑换债权金额： {{ needDebt | toThousandFilter }}</p>
        <p class="sel">已选债权金额： {{ userSelection.total | toThousandFilter }}</p>
      </div>
    </main>
    <Footer v-if="currentType" :isdisabled="!finalConfirm /* || !checked*/" @submit="onVerify" />
    <!--    <footer class="footer" v-if="currentType">
      <van-row>
        <van-checkbox v-model="checked">
          <label>
            同意按照
            <span @click="$router.push({ name: 'debt_agreement' })">《债权转让协议范本》</span>格式生成协议
          </label>
        </van-checkbox>
      </van-row>
      <van-row>
        <van-col span="15">
          <p class="need">兑换债权金额： {{ needDebt | toThousandFilter }}</p>
          <p class="sel">已选债权金额： {{ userSelection.total | toThousandFilter }}</p>
        </van-col>
        <van-col span="9">
          <van-button class="submit" :disabled="!finalConfirm || !checked" @click="onVerify">兑换</van-button>
        </van-col>
      </van-row>
    </footer>-->
    <van-dialog v-model="passwordDialogShow" title="请输入交易密码" show-cancel-button @confirm="onCheck">
      <!-- 密码输入浮窗 -->
      <van-password-input
        :value="password"
        info="密码为 6 位数字"
        :focused="showKeyboard"
        @focus="showKeyboard = true"
      />
    </van-dialog>
    <!-- 数字键盘 -->
    <van-number-keyboard
      :show="showKeyboard"
      @input="onInput"
      @delete="onDelete"
      @blur="showKeyboard = false"
      style="z-index: 9999;"
    />
  </div>
</template>

<script>
import CommonHeader from 'components/common/Header'
import { mapActions, mapGetters } from 'vuex'
import { Dialog } from 'vant'
// import { parseParams } from '@/utils'
import store from '@/store'
import Footer from '@/components/Footer'

const $env = store.getters.env
export default {
  name: 'Debt',
  // beforeRouteEnter(_, from, next) {
  //   // 若是通过返回跳过来的，就在往前跳
  //   if (!from.name) {
  //     next(vm => {
  //       vm.__goBack()
  //     })
  //   } else {
  //     next()
  //   }
  // },
  data() {
    return {
      // 选单
      currentType: 0,
      options: [{ text: '请选择债权类别', value: 0 }],
      // 债权列表
      loading: false,
      finished: false,
      error: false,
      dataList: [],
      page: 0,
      // 债权选择
      userSelection: {
        list: [],
        total: 0,
      },
      remainder: 5,
      // 密码弹窗
      passwordDialogShow: false,
      showKeyboard: false,
      password: '',
      checked: false,
    }
  },
  components: {
    Footer,
    CommonHeader,
  },
  watch: {
    currentType(_, oldValue) {
      // 选单的选中改变后删除默认的选择债权类别，并重新请求列表
      if (oldValue == 0) {
        this.options.shift()
      }
      this.reset()
    },
    dataList: {
      handler() {
        this.userSelection.list = this.dataList.filter(item => {
          return item._use === true
        })
        let minimum = { account: Infinity }
        this.userSelection.total = this.userSelection.list.reduce((accumulator, item) => {
          if (this.$dec.cmp(item.account, this.remainder * 2) > 0 && this.$dec.cmp(item.account, minimum.account) < 0) {
            // 找到不小于 5 * 2 元的最小值
            minimum = item
          }
          return this.$dec.add(accumulator, item.account)
        }, 0)
        // 若最小的债权扣除后还可以继续兑换，就取消选取最小的
        if (minimum && this.getFinalConfirm(this.$dec.sub(this.userSelection.total, minimum.account))) {
          minimum._use = false
        }
      },
      deep: true,
    },
  },
  async mounted() {
    // amount: "100"
    // appid: "666"
    // exchange_no: "E124343434343434"
    // goodsInfo: "{测试商品}"
    // goods_order_no: "G1212212"
    // openid: "12312"
    // redirect_url: "https://m.youjiemall.com/"

    this.changeEnv(this.$route.query)
    // if (process.env.NODE_ENV === 'development') {
    //   this.options.push(
    //     ...Object.entries({ 1: '尊享', 2: '普惠' }).map(([value, text]) => ({
    //       text,
    //       value,
    //     })),
    //   )
    //   return
    // }
    await this.getUserStatus().then(
      () => {
        if (!this.debtType) {
          return this.$toast.fail({
            message: '查无数据',
            onClose: () => {
              this.__goBack()
            },
          })
        }
        this.options.push(
          ...Object.entries(this.debtType).map(([value, text]) => ({
            text,
            value,
          })),
        )
      },
      message => {
        return this.$toast.fail({
          message,
          onClose: () => {
            this.__goBack()
          },
        })
      },
    )
  },
  computed: {
    ...mapGetters({
      needDebt: 'needDebt',
      setPassword: 'setPassword',
      debtType: 'debtType',
      redirectUrl: 'redirectUrl',
      exchangeNo: 'exchangeNo',
      ph_total_amount: 'ph_total_amount',
    }),
    finishedText() {
      return this.dataList.length ? '没有更多了' : '暂无数据'
    },
    userSelectionIds() {
      return this.userSelection.list.map(res => res)
    },
    finalConfirm() {
      return this.getFinalConfirm(this.userSelection.total)
    },
  },
  methods: {
    ...mapActions({
      changeEnv: 'changeEnv',
    }),

    onLoad() {
      // 列表加载
      if (!this.currentType) {
        this.loading = false
        return
      }

      this.loading = true
      this.$services.exchange.getDebtList(this.currentType, ++this.page).then(res => {
        const { data } = res
        // 加载状态结束
        this.loading = false
        if (data.code == 0) {
          if (data.data.list.length) {
            console.log($env.ph_total_amount, 999)
            if (this.ph_total_amount > 0) {
              this.dataList.push(...data.data.list.map(item => Object.assign(item, { _use: true })))
            } else {
              // 给每个数据添加一个用于判断是否选中的字段
              this.dataList.push(
                ...data.data.list.map(item =>
                  Object.assign(item, { _use: this.getDebtChangeStatus(item) ? false : -1 }),
                ),
              )
            }
          } else {
            this.finished = true
          }
        } else if (data.info) {
          this.finished = true
          this.$toast.fail(data.info)
        }
      })
    },
    onClick(item) {
      // 取消/选择债权
      if (item._use === -1 || (item._use === false && this.finalConfirm)) {
        return
      }
      item._use = !item._use
    },
    onInput(key) {
      // 输入密码
      this.password = (this.password + key).slice(0, 6)
    },
    onDelete() {
      // 删除密码
      this.password = this.password.slice(0, this.password.length - 1)
    },
    onVerify() {
      this.$services.exchange.getUserPasswordStatus().then(res => {
        const { data } = res
        if (data.code == 0) {
          if (data.data.pay_password_status == 0) {
            return this.dialogSetting()
          } else {
            return this.dialogPassword()
          }
        } else if (data.info) {
          this.$toast.fail(data.info)
        }
      })
    },
    onCheck() {
      // 提交密码

      if (this.password.length !== 6) {
        return this.$toast.fail('请输入 6 位数字密码')
      }
      this.onSubmit()
    },
    onSubmit() {
      // 提交债权兑换
      this.$services.exchange
        .submitDebt(this.currentType, this.userSelectionIds, this.password, this.redirectUrl)
        .then(res => {
          const { data } = res
          if (data.code == 0 && data.data) {
            window.location.href = data.data
            // this.$toast.success({
            //   message: '债权兑换积分成功!',
            //   onClose: () => {
            //     location.href = parseParams(this.redirectUrl, { exchange_no: this.exchangeNo })
            //   },
            // })
          } else if (data.info) {
            this.$toast.fail(data.info)
          }
        })
    },
    async getUserStatus() {
      return await this.$services.exchange.getUserStatus().then(res => {
        const { data } = res
        if (data.code == 0) {
          this.changeEnv(data.data)
        } else if (data.info) {
          return Promise.reject(data.info)
        }
      })
    },
    getDebtChangeStatus(item) {
      // 是否可以选择此债权
      return item.is_exchanging == 0
    },
    dialogSetting() {
      Dialog.confirm({
        title: '提示',
        message: '您尚未设置交易密码，为了您的账户安全，请前往设置。',
        confirmButtonText: '前往设置',
      }).then(() => {
        // 前往设置交易密码
        const host = process.env.NODE_ENV === 'development' ? 'http://qa1.xfuser.com' : 'https://m.xfuser.com'
        location.href = `${host}/#/setPassWord?set_password=2&sign_agreement=1&securityFlag=true`
      })
    },
    dialogPassword() {
      // 展示密码输入框
      this.password = ''
      this.passwordDialogShow = true
      this.showKeyboard = true
    },
    getFinalConfirm(total, showErr = false) {
      let result = true
      const subtract = this.$dec.sub(total, this.needDebt)
      if (subtract < 0) {
        result = false
      } else if (subtract > 0 && subtract < this.remainder) {
        result = false
        if (showErr) {
          this.$toast.fail(
            `兑换差额不得低于 ${this.remainder} 元${
              this.userSelection.list.length != this.dataList.length && '，请继续选择'
            }`,
          )
        }
      }
      return result
    },
    reset() {
      this.userSelection.list = []
      this.userSelection.total = 0
      this.page = 0
      this.dataList = []
      this.finished = false
      this.onLoad()
    },
  },
}
</script>

<style lang="scss">
@mixin inner {
  width: 100%;
  margin: 0 auto;
}

.debt {
  &-wrapper {
    height: 100%;
    padding-top: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    .main {
      @include inner;
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      .van-dropdown-menu {
        padding: 13px 16px 0;
        .van-ellipsis {
          font-size: 14px;
        }
        .van-dropdown-menu__bar {
          height: 40px;
          background: #f9f9f9;
          border-radius: 4px;
        }
        .van-dropdown-menu__item {
          justify-content: left;
          margin-left: 9px;
          margin-right: 26px;
        }
        .van-dropdown-menu__title {
          width: 100%;
          &::after {
            border-color: transparent transparent #4a4a4a #4a4a4a;
          }
        }
        .van-dropdown-item__content {
          width: 340px;
          margin: 0 auto;
          left: -50%;
          right: -50%;
        }
        .van-hairline--top-bottom:after,
        .van-hairline-unset--top-bottom:after {
          border-width: 0;
        }
      }

      .list {
        max-height: 100%;
        overflow-x: hidden;
        margin-top: 10px;
        .item {
          width: 350px;
          height: 124px;
          background: #fff;
          box-shadow: 0 2px 4px 0 rgba(238, 238, 238, 0.8);
          border-radius: 4px;
          margin: 10px auto 0;
          padding: 10px 20px 0 16px;
          overflow: hidden;
          &::after {
            display: none;
          }
          p {
            color: #4a4a4a;
            font-size: 14px;
            font-family: PingFangSC-Regular;
            letter-spacing: 0;
            margin: 2px 0 0 0;
            &.account {
              font-size: 28px;
              line-height: 40px;
            }
            &.unit {
              line-height: 20px;
            }
            &.date {
              color: #9b9b9b;
              font-size: 12px;
              line-height: 17px;
            }
          }
          .reserve {
            color: #3834df;
            font-size: 12px;
            float: right;
            border: 1px solid #3834df;
            border-radius: 10px;
            padding: 2px 5px;
            white-space: nowrap;
          }
          .i-check {
            width: 20px;
            height: 20px;
            margin-top: 42px;
            border: 1px solid #9b9b9b;
            border-radius: 50%;
            display: block;
            float: right;
            &-s {
              background: center center no-repeat url('~images/debt/已选@2x.png');
              background-size: 20px 20px;
              border: hidden;
            }
          }
        }
        .van-list__finished-text {
          font-size: 12px;
          color: #9b9b9b;
        }
      }
    }
    .van-checkbox__icon--checked .van-icon-success {
      color: #fff;
      background-color: #3834df;
      border-color: #3834df;
    }
    .amount {
      font-size: 13px;
      color: #333;
      height: 63px;
      margin: 8px 13px;

      p {
        margin: 0;
        & + p {
          margin-top: 15px;
        }
      }
    }
    /*.footer {
      height: 100px;
      flex: 0 0 auto;
      padding-left: 19px;
      padding-top: 18px;
      margin-top: 10px;
      border-top: 1px solid #ededed;

      .van-checkbox {
        label {
          font-size: 14px;
          color: #4a4a4a;
        }
        span {
          color: #3834df;
        }

        margin: 0 0 4px 0;
      }

      .van-button {
        width: 100px;
        height: 40px;
        border-radius: 100px;
        border-radius: 4px;
        color: #fff;
        background: #3834df;
        margin-top: 10px;
        margin-right: 20px;
        float: right;
      }
      p {
        color: #4a4a4a;
        line-height: 24px;
        font-size: 14px;
        font-family: PingFangSC-Regular;
        letter-spacing: 0;
        margin: 2px 0 0 0;
      }
    }*/
  }
}
</style>
