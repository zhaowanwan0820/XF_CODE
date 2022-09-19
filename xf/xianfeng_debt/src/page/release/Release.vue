<template>
  <div class="release">
    <div class="release-box">
      <div class="release-box-name">{{ params.name }}</div>
      <div class="line"></div>
      <div class="form-conter">
        <van-field label="待还本金:" v-model="'¥ ' + params.wait_capital_val" disabled />
        <div class="form-flex">
          <div class="form-label">转让金额：</div>
          <div class="form-input">
            <input
              type="Number"
              placeholder="请输入转让金额"
              v-model.Number="form.money"
              @blur="moneyBlur"
              @input="handleInput"
            />
          </div>
        </div>
        <div class="form-flex">
          <div class="form-label">转让折扣：</div>
          <div class="form-input">
            <input type="Number" placeholder="请输入0.1~10的数字" v-model.Number="form.discount" @input="inputBlur" />
          </div>
          <span>折</span>
        </div>
        <div class="form-flex" style="white-space: nowrap;">
          <div class="form-label">有效期：</div>
          <div class="form-input">
            <van-dropdown-menu>
              <van-dropdown-item v-model="form.effect_days" :options="actions" />
            </van-dropdown-menu>
          </div>
          <span>天</span>
        </div>
        <div class="form-flex">
          <div class="form-label">回本金额：</div>
          <div class="form-input">
            <p>¥ {{ form.principal ? form.principal : '0' }}</p>
          </div>
        </div>
        <div class="form-flex">
          <div class="form-label">手续费率：</div>
          <div class="form-input">
            <p>0</p>
          </div>
        </div>
        <div class="form-flex">
          <div class="form-label">定向转让：</div>
          <div class="form-input" @click="imgClick">
            <img :src="isImage ? c_2 : c_1" alt="" />
            <label>认购码</label>
            <span>若您需指定认购方可使用认购码</span>
          </div>
        </div>
      </div>
    </div>
    <div class="bank-card">
      <h4>银行卡</h4>
      <p>债权转让资金由认购方直接转账到您的银行卡</p>
      <div class="bank-card-name">
        <label>用户名：</label>
        <span>{{ params.real_name }}</span>
      </div>
      <div class="bank-card-card">
        <label>银行卡号：</label>
        <div @click="inputChange">{{ params.bankCard ? params.bankCard : '请选择您的银行卡' }}</div>
      </div>
    </div>
    <van-action-sheet v-model="show_select" :actions="params.bank_info" cancel-text="取消" @select="onSelect" />
    <div class="release-btn">
      <div class="btn">
        <van-button :disabled="isdisabled" color="rgba(252, 129, 12, 1)" type="info" @click="submit">发布</van-button>
      </div>

      <!--      <div class="p-text">-->
      <!--        <p>债权转让成功的资金会转入您原机构的账户，请注意查收。</p>-->
      <!--      </div>-->
    </div>

<!--    <Footer :isdisabled="isdisabled" type="info" @submit="submit" />-->

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
import { setRelease, getProInfo } from '../../api/transfer'
import Footer from "@/components/Footer";
export default {
  name: 'Release',
  components: {Footer},
  data() {
    return {
      show: false,
      value: '',
      showKeyboard: true,
      params: {},
      form: {
        principal: 0, //回本金额
        deal_load_id: '', //投资记录ID（status=1必传）
        products: '', //所属产品：1尊享债转 2普惠供应链 3工场微金 4智多新
        money: '', //转让金额
        discount: '', //转让折扣(取值范围0.01至10)
        effect_days: 0, //有效期(10，20，30)
        bankcard_id: '', //银行卡ID
        transaction_password: '', //支付密码
        is_orient: 2, //定向转让(1是 2不是) //非必传默认2
        debt_id: '', //债权记录ID（status=2必传）
        status: '' //状态1:发布 2:重新发布
      },
      actions: [
        { text: '请选择债转有效期', value: 0 },
        { text: '10', value: 10 },
        { text: '20', value: 20 },
        { text: '30', value: 30 }
      ],
      isImage: true,
      c_1: require('../../assets/image/release/c-1.png'),
      c_2: require('../../assets/image/release/c-2.png'),
      isdisabled: false,
      show_select: false
    }
  },
  created() {
    this.form.deal_load_id = this.$route.query.id ? this.$route.query.id : ''
    this.form.products = this.$route.query.products
    this.form.status = this.$route.query.status ? this.$route.query.status : ''
    this.form.debt_id = this.$route.query.debt_id ? this.$route.query.debt_id : ''
    this.UserInfo()
  },
  methods: {
    //项目详情接口
    UserInfo() {
      getProInfo(this.form).then(res => {
        if (res.code == 0) {
          this.params = { ...res.data }
          if (res.data.bank_info.length == 0) {
            Dialog.alert({
              message: '您还未绑定银行卡，无法进行债转。请先绑定银行卡。'
            }).then(() => {
              this.$router.go(-1)
            })
          }
          this.params.wait_capital_val = this.toThousands(res.data.wait_capital)
          this.form.money = parseFloat(res.data.wait_capital)
          this.form.principal = this.toThousands(this.form.money)
          this.params.bank_info.forEach(val => {
            val.name = val.name + val.bankcard
          })
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
      let discount = this.form.discount
      if (this.params.wait_capital - 100 < 0) {
        if (this.params.wait_capital - this.form.money > 0) {
          Toast('待还本金不足￥100，请全部转让吧')
          return false
        }
      } else {
        if (this.form.money - 100 < 0 || this.form.money - this.params.wait_capital > 0) {
          Toast('您可转让的金额为￥100-¥' + this.params.wait_capital)
          return false
        }
        if (this.params.wait_capital - this.form.money < 100 && this.params.wait_capital - this.form.money > 0) {
          Toast('剩余待还本金不足￥100，请全部转让吧')
          return false
        }
      }
      if (!discount) {
        Toast('请输入转让折扣！')
        return false
      } else {
        if (Number(discount) < 0.1 || Number(discount) > 10 || Number(discount) < 0) {
          Toast('请重新确认债转折扣，应为0.1~10的数字！')
          return false
        }
      }
      if (!this.form.effect_days) {
        Toast('请选择债权转让有效期！')
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
            // this.$router.push({ name: 'settpass' })
            window.location.href = '/#/setPassWord'
          })
          return
        }
      }
      // if (!this.$store.getters.isSetPassword || !setPass) {
      //   this.$dialog.confirm({ message: '您还没有设置交易密码,请前往设置' }).then(() => {
      //     // this.$router.push({ name: 'settpass' })
      //     window.location.href = '/#/setPassWord'
      //   })
      //   return
      // }
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
        setRelease(this.form)
          .then(res => {
            if (res.code == 0) {
              this.$router.push({ name: 'success', query: { id: res.data.debt_id, code: res.data.buy_code } })
              // 发布转让 https://host/#/dept/success?id=res.data.debt_id&code=res.data.buy_code
              // const [url] = res.data || []
              // if (url) {
              //   window.location.href = url
              // }
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
    moneyBlur(e) {
      let num = e.target.value
      // if(num<100){
      //   this.$toast(`您可转让的金额为￥100 - ￥${this.params.wait_capita}`)
      // }
      if (this.form.discount) {
        this.form.principal = this.toThousands(this.accMul(num, this.form.discount / 10))
      } else {
        this.form.principal = 0
        this.form.principal = this.toThousands(this.form.money)
      }
    },
    inputBlur(e) {
      this.form.discount = e.target.value.replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3')
      if (this.form.money) {
        // this.form.principal = this.form.money * (e.target.value / 10)
        this.form.principal = this.toThousands(this.accMul(this.form.money, this.form.discount / 10))
      } else {
        this.form.principal = 0
      }
    },
    handleInput(e) {
      let n = e.target.value.replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3')
      if (Number(n) > Number(this.params.wait_capital)) {
        this.$toast('转让金额不能大于待还本金')
        return false
      }
    },
    accMul(arg1, arg2) {
      let m = 0,
        s1 = arg1.toString(),
        s2 = arg2.toString()
      try {
        m += s1.split('.')[1].length
      } catch (e) {}
      try {
        m += s2.split('.')[1].length
      } catch (e) {}
      // return ((Number(s1.replace('.', '')) * Number(s2.replace('.', ''))) / Math.pow(10, m)).toFixed(2)
      return Math.ceil(((Number(s1.replace('.', '')) * Number(s2.replace('.', ''))) / Math.pow(10, m)) * 100) / 100
    },
    //金额加，分隔
    toThousands(num) {
      num = (num || 0).toString()
      let number = 0,
        floatNum = '',
        intNum = ''
      if (num.indexOf('.') > 0) {
        number = num.indexOf('.')
        floatNum = num.substr(number)
        intNum = num.substring(0, number)
      } else {
        intNum = num
      }
      let result = [],
        counter = 0
      intNum = intNum.split('')
      for (let i = intNum.length - 1; i >= 0; i--) {
        counter++
        result.unshift(intNum[i])
        if (!(counter % 3) && i != 0) {
          result.unshift(',')
        }
      }
      return result.join('') + floatNum || ''
    },
    inputChange() {
      this.show_select = true
    },
    imgClick() {
      this.isImage = !this.isImage
      if (this.isImage) {
        this.form.is_orient = 2
      } else {
        this.form.is_orient = 1
      }
    },
    forget() {
      window.location.href = '/#/findPassWord'
    }
  }
}
</script>

<style lang="less" scoped>
.release .van-cell .van-cell__title span {
  font-weight: 400 !important;
  color: rgba(64, 64, 64, 1) !important;
}
.bank-card {
  margin-top: 10px;
  padding: 15px 20px 16px;
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
    margin-top: 19px;
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
    .van-dropdown-menu {
      // height: 38px;
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
    .btn {
      width: 309px;
      margin: 42px auto 29px;
      .van-button {
        width: 309px;
        height: 50px;
        font-size: 18px;
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
