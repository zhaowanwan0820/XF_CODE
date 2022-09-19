<template>
  <div class="release">
     <div class="bank-card">
      <h4>银行卡</h4>
      <p>债权转让资金由求购方直接转账到您的银行卡</p>
      <div class="bank-card-name">
        <label>用户名：</label>
        <span>{{ card_name }}</span>
      </div>
      <div class="bank-card-card">
        <label>银行卡号：</label>
        <div @click="inputChange">{{ params.bankCard ? params.bankCard : '请选择您的银行卡' }}</div>
      </div>
    </div>
    <div class="subject-info">
        <div class="time-left">
          <van-count-down class="countdown" :time="time" format="DD 天 HH 时 mm 分 ss 秒" />
          <span class="text">剩余有效期：</span>
        </div>
        <van-row>
          <van-col span="8">
            <div class="big-value">{{ fmtDiscount }}<span>折</span></div>
            <div class="legend">折扣</div>
          </van-col>
          <van-col span="8">
            <div class="big-value">{{ userSelection.total  }} <span>元</span></div>
            <div class="legend">转让债权总额</div>
          </van-col>
          <van-col span="8">
            <div class="big-value">{{ ecovery_money }} <span>元</span></div>
            <div class="legend">预计回本金额</div>
          </van-col>
        </van-row>
    </div>
      <div class="debt-wrapper">
        <h3 style="margin-left:15px">请选择要出售的债权</h3>
        <main class="main">
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
            <div class="item" :key="item.deal_load_id" @click="onClick(item)">
              <van-row>
                <van-col span="18">
                  <p class="title">项目名称：{{ item.name }} </p>
                  <p class="title">待还本金：{{ item.wait_capital }} 元</p>
                </van-col>
                <van-col span="6">
                  <i class="i-check" :class="{ 'i-check-s': item._use }" v-if="!finalConfirm || item._use==true"></i>
                </van-col>
              </van-row>
            </div>
          </template>
        </van-list>
      </main>
    </div>
    <van-action-sheet v-model="show_select" :actions="params.bank_info" cancel-text="取消" @select="onSelect" />
    <div class="release-btn">
      <footer class="footer" >
        <van-row>
          <van-checkbox v-model="checked" checked-color="#FC810C">
            <label>
              同意按照
              <span @click="$router.push({ name: 'debtAgreementDemo' })">《债权转让协议范本》</span>格式生成协议
            </label>
          </van-checkbox>
        </van-row>
      </footer>
      <div class="btn">
        <van-button :disabled="isdisabled" color="rgba(252, 129, 12, 1)" type="info" @click="submit">确认出售</van-button>
      </div>
    </div>
    <van-popup v-model="show" closeable position="bottom" :close-on-click-overlay="false" :style="{ height: 'auto' }">
      <div class="pop-title">请输入交易密码</div>
      <!-- 密码输入框 -->
      <van-password-input :value="value" :focused="showKeyboard" @focus="showKeyboard = true" />
      <div class="forget">
        <span class="reset" @click="forget">忘记密码?</span>
      </div>
      <!-- 数字键盘 -->
      <van-number-keyboard
        :hide-on-click-outside="false"
        :show="showKeyboard"
        @input="onInput"
        @delete="onDelete"
        @blur="showKeyboard = false"
      />
    </van-popup>
  </div>
</template>

<script>
import { Toast, Dialog } from 'vant'
import { mapActions, mapGetters } from 'vuex'
import { setRelease } from '../../api/transfer'
import { getPurchaseDetails } from '../../api/debtmarket'
import { getUserBankCard } from '../../api/user'
import { getPurchaseList ,confirmSale,getPurchaseDebtList} from '../../api/debtmarket'
import { dec } from '../../util/decimal'
export default {
  name: 'SellConfirmation',
  data() {
    return {
      loading: false,
      finished: false,
      error: false,
      // 债权列表
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
      request_params: {
        area_id: 1,
        products: 2,
        page: 0,
        limit: 10
      },
      surplus_amount:0,//求购剩余额度
      ecovery_money:0,
      total_amount:0,
      show: false,
      time:0,
      discount:'',
      value: '',
      showKeyboard: true,
      params: {},
      card_name:'',
      form: {
        purchase_id:0,
        deal_load_id: {}, //投资记录ID
        bankcard_id: '', //银行卡ID
        transaction_password: '', //支付密码
      },
     
      isImage: true,
      c_1: require('../../assets/image/release/c-1.png'),
      c_2: require('../../assets/image/release/c-2.png'),
      isdisabled: false,
      show_select: false
    }
  },
  created() {

    this.form.purchase_id = this.$route.query.id ? this.$route.query.id : ''
    // this.form.products = 1
    // this.form.status = this.$route.query.status ? this.$route.query.status : ''
    // this.form.debt_id = this.$route.query.debt_id ? this.$route.query.debt_id : ''
    this.UserInfo()

  },
   computed: {
    fmtDiscount() {
      return this.utils.formatFloat(this.discount)
    },
    finishedText() {
      return this.dataList.length ? '没有更多了' : '暂无数据'
    },
    userSelectionIds() {
      //注意这里 todo 
      return this.userSelection.list.map(res => res.deal_load_id)
    },
    finalConfirm() {
      return this.getFinalConfirm(this.userSelection.total)
    },
  },
  watch: {
   
    dataList: {
      handler() {
        this.userSelection.list = this.dataList.filter(item => {
          return item._use === true
        })
       
        let last_select = { wait_capital: Infinity }
        this.userSelection.total = this.userSelection.list.reduce((accumulator, item) => {
          if (dec.cmp(dec.add(accumulator, item.wait_capital),this.surplus_amount) == 1) {
            // 找到最后的一笔债权
            last_select = item
          }
          return dec.add(accumulator, item.wait_capital)
        }, 0)

       // this.ecovery_money = this.utils.formatFloat(dec.mul(dec.div(this.discount,10),this.userSelection.total))
        this.ecovery_money = Math.round(dec.mul(dec.div(this.discount,10),this.userSelection.total)*100)/100
        //如果存在最后选择的债权，且额度超过总额，则取消选择
        if (last_select.wait_capital>0) {
          last_select._use = false
        }
        if(this.userSelection.total>this.surplus_amount){
          this.$toast('所选债权总额超过剩余求购金额')
        }
      },
      deep: true,
    },
  },
  
  methods: {
    onLoad() {
      this.loading = true
      this.request_params.page += 1
      getPurchaseDebtList(this.request_params).then(res => {
        if( res.code == 4007){
          window.location.href = '/#/login?from=debtMarket'
          return
        }
        if(this.request_params.page ==1 && res.code != 0){
           this.loading = false
           this.finished = true
           this.$toast('抱歉，您没有改笔求购所指定的债权')
           return ;
        }
        if (res.code == 0 ) {
           this.dataList.push(
              ...res.data.data.map(item => Object.assign(item, { _use: false })),
            )
          this.loading = false
        } else  {
          this.finished = true
          this.loading = false
        }
      })
    },
    onClick(item) {
      item._use = !item._use
    },
    getFinalConfirm(total, showErr = false) {
      let result = true
      const subtract = dec.sub(total, this.surplus_amount)
      if (subtract < 0) {
        result = false
      } else if (subtract > 0 ) {
        result = false
        this.$toast('超过剩余求购金额')
      }
      return result
    },

    //项目详情接口
    UserInfo() {
      getUserBankCard().then(res=>{
        if (res.code == 0) {
          this.params.bank_info = res.data
          if(this.params.bank_info.length >0){
              this.card_name = this.params.bank_info[0].card_name;
          }
          if (res.data.length == 0) {
            Dialog.alert({
              message: '您还未绑定银行卡，无法进行债转。请先绑定银行卡。'
            }).then(() => {
              this.$router.go(-1)
            })
          }
          this.params.bank_info.forEach(val => {
            val.name = val.name + val.bankcard
          })
        }
      })
      getPurchaseDetails(this.form).then(res => {
        if (res.code == 0) {
          let ct = Date.now()
          this.time = res.data.endtime * 1000 - ct
          this.discount = res.data.discount
          this.surplus_amount = res.data.surplus_amount
         
        } else {
          this.$toast(res.info)
          this.$router.go(-1)
        }
      })
    },
    //选择银行卡
    onSelect(item) {
      this.show_select = false
      this.params.bankCard = item.name
      this.form.bankcard_id = item.bankcard_id
    },
    submit() {
      if (this.userSelectionIds.length==0) {
        Toast('请选择要出售的债权')
        return false
      }
      if (!this.checked) {
        Toast('请阅读并同意协议')
        return false
      }
      if (!this.form.bankcard_id) {
        this.$toast('请选择您的银行卡！')
        return false
      }
      let setPass = localStorage.getItem('is_set_pay_password')
      let is_set_pay_password = JSON.parse(localStorage.getItem('m_assets_garden')).auth.user.is_set_pay_password
      if (!setPass) {
        if (!is_set_pay_password) {
          this.$dialog.confirm({ message: '您还没有设置交易密码,请前往设置' }).then(() => {
            window.location.href = '/#/setPassWord'
          })
          return
        }
      }
    
      this.value = ''
      this.showKeyboard = true
      this.show = true
    },
    //提交
    onInput(key) {
      this.value = (this.value + key).slice(0, 6)
      if (this.value.length == 6) {
        this.show = false
        this.form.transaction_password = this.value
        this.$loading.open()
        this.form.deal_load_id = this.userSelectionIds
       
        confirmSale(this.form)
          .then(res => {
            if (res.code == 0) {
              this.$router.push({ name: 'sellSuccess', query: { id: res.data.debt_id, code: res.data.buy_code } })
            } else {
              this.$toast(res.info)
            }
          })
          .finally(() => {
            this.$loading.close()
          })
      }
    },
    onDelete() {
      this.value = this.value.slice(0, this.value.length - 1)
    },
    
    inputChange() {
      this.show_select = true
    },
    
    forget() {
      window.location.href = '/#/findPassWord'
    }
  }
}
</script>

<style lang="less" scoped>
@mixin inner {
  width: 100%;
  margin: 0 auto;
}

.debt {
  &-wrapper {
    height: 100%;
    margin-bottom: 120px;
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
         height: 100%;
        //height: 450px;
        overflow-x: hidden;
        margin-top: 10px;
        .item {
          width: 350px;
          height: 100px;
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
            margin: 12px 0 0 0;
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
            margin-top: 30px;
            margin-right: 5px;;
            border: 1px solid #9b9b9b;
            border-radius: 50%;
            display: block;
            float: right;
            &-s {
              background: center center no-repeat url('../../assets/image/choose/checked.png');
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
    
  }
}
.release .van-cell .van-cell__title span {
  font-weight: 400 !important;
  color: rgba(64, 64, 64, 1) !important;
}
.time-left {
  height: 30px;
  line-height: 30px;
  padding: 10px 20px 0;
  // color: rgba(255, 255, 255, 0.64);
  .text {
    float: right;
  }
  .countdown {
    float: right;
    // color: rgba(255, 255, 255, 0.64);
  }
}
.subject-info {
  margin-top: 10px;
  padding: 10px 20px 10px;
  background-color: #fff;

  height: 118px;
  text-align: center;
  .big-value {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 22px;
    // color: #fff;
    margin-top: 15px;
    margin-bottom: 15px;
    span {
      font-size: 14px;
    }
  }
  .legend {
    // color: rgba(255, 255, 255, 0.4);
    font-size: 14px;
  }
}
.bank-card {
  margin-top: 10px;
  padding: 10px 20px 10px;
  background-color: #fff;
  h4 {
    height: 22px;
    font-size: 15px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    line-height: 21px;
    margin: 0 0 5px;
  }
  p {
    height: 17px;
    font-size: 12px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(153, 153, 153, 1);
    line-height: 17px;
  }
  label {
    width: 77px;
    height: 22px;
    font-size: 15px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    line-height: 21px;
  }
  .bank-card-name {
    margin-top: 14px;
    span {
      font-size: 15px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
    }
  }
  .bank-card-card {
    display: flex;
    align-items: center;
    margin-top: 14px;
    div {
      width: 240px;
      height: 38px;
      line-height: 38px;
      font-size: 15px;
      border: 1px solid rgba(231, 231, 231, 1);
      text-indent: 10px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }
}
.release {
  .line {
    height: 10px;
    background: #f9f9f9;
  }
  .van-cell {
    .van-cell__title {
      span {
        font-size: 18px;
        font-weight: 700;
        color: rgba(4, 177, 164, 1);
      }
    }

    .van-cell__value {
      text-align: left;
      color: rgba(153, 153, 153, 1);
    }
  }
  .release-box {
    .release-box-name {
      height: 22px;
      font-size: 16px;
      font-family: PingFangSC-Medium, PingFang SC;
      font-weight: 500;
      color: rgba(64, 64, 64, 1);
      line-height: 22px;
      background-color: #fff;
      padding: 11px 20px 12px;
    }
    .form-conter {
      padding: 15px 20px 20px;
      background-color: #fff;
      .van-cell {
        padding: 0;
      }
      font-family: PingFangSC-Regular, PingFang SC;
      padding-bottom: 10px;
      /deep/.van-cell__title {
        width: 80px;
        font-size: 15px;
        font-weight: 400;
        letter-spacing: 1px;
        color: rgba(102, 102, 102, 1);
      }
      /deep/.van-field__control {
        font-size: 18px;
        font-weight: 400;
        color: rgba(102, 102, 102, 1);
      }
      .form-flex {
        display: flex;
        align-items: center;
        padding: 10px 0;
        .form-label {
          min-width: 80px;
          font-size: 15px;
          font-weight: 400;
          letter-spacing: 1px;
          color: rgba(64, 64, 64, 1);
          white-space: nowrap;
        }
        .form-input {
          display: flex;
          align-items: center;
          input {
            width: 230px;
            height: 38px;
            font-size: 16px;
            font-weight: 400;
            color: rgba(102, 102, 102, 1);
            text-indent: 6px;
          }
          .van-dropdown-menu {
            width: 230px;
            /deep/.van-ellipsis {
              width: 230px;
            }
          }
          p {
            font-size: 17px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(64, 64, 64, 1);
          }
          img {
            width: 14px;
            height: 14px;
          }
          label {
            font-size: 15px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(64, 64, 64, 1);
            margin-left: 4px;
          }
          span {
            font-size: 12px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(153, 153, 153, 1);
          }
        }
        span {
          font-size: 17px;
          font-weight: 400;
          margin-left: 4px;
        }
      }
    }
    .van-field:not(:last-child):after {
      border: 0;
    }
   
    /deep/.van-dropdown-menu__title:after {
      right: 10px;
      content: '';
    }
    [class*='van-hairline']:after {
      border: 0;
    }
    input {
      background: none;
      outline: none;
      border: 1px solid rgba(231, 231, 231, 1);
    }
    input:focus {
      border: 1px solid rgba(231, 231, 231, 1);
    }
    input::-webkit-input-placeholder {
      color: rgba(204, 204, 204, 1);
      font-size: 14px;
      text-indent: 6px;
    }
    input::-moz-placeholder {
      /* Mozilla Firefox 19+ */
      color: rgba(204, 204, 204, 1);
      font-size: 14px;
      text-indent: 6px;
    }
    input:-moz-placeholder {
      /* Mozilla Firefox 4 to 18 */
      color: rgba(204, 204, 204, 1);
      font-size: 14px;
      text-indent: 6px;
    }
    input:-ms-input-placeholder {
      /* Internet Explorer 10-11 */
      color: rgba(204, 204, 204, 1);
      font-size: 14px;
      text-indent: 6px;
    }
  }
  .release-btn {
    width: 100%;
    z-index: 12;
    position:fixed; 
    bottom:0;
    background: #fbfbfb;
    .footer {
      height: 10px;
      flex: 0 0 auto;
      padding-left: 19px;
      padding-top: 18px;
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

      p {
        color: #4a4a4a;
        line-height: 24px;
        font-size: 14px;
        font-family: PingFangSC-Regular;
        letter-spacing: 0;
        margin: 2px 0 0 0;
      }
    }
    .btn {
      width: 309px;
      margin: 30px auto 20px;
      .van-button {
        width: 309px;
        height: 40px;
        font-size: 16px;
        background: rgba(4, 177, 164, 1);
        border-radius: 7px;
        border: 0;
      }
    }
    .p-text {
      p {
        text-align: center;
        font-size: 13px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(153, 153, 153, 1);
        padding-bottom: 20px;
      }
    }
  }
  .pop-title {
    line-height: 50px;
    text-align: center;
  }
  .van-password-input__security:after {
    border: 1px solid rgba(187, 187, 187, 1);
    content: '';
  }
  .forget {
    padding: 10px 10px 20px;
    text-align: center;
    color: #fc810c;
  }
}
/deep/ .van-dropdown-menu__bar {
  height: 38px;
  border: 1px solid rgba(231, 231, 231, 1);
}
</style>
