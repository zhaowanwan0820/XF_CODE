<template>
  <div class="subscription">
    <van-cell-group>
      <van-cell title="项目名称" v-model="projectName" />
      <!-- <van-cell title="年利率" v-model="apr" /> -->
      <van-cell title="转让折扣" v-model="discount" />
      <van-cell title="转让金额" v-model="money" />
      <van-cell title="转让价格" v-model="transferprice" />
      <van-cell title="手续费率" value="0" />
      <van-cell title="手续费" value="0" />
    </van-cell-group>
    <van-cell-group style="margin-top: 10px;">
      <van-cell title="支付方式" value="银行转账" />
      <van-cell title="支付金额" v-model="transferprice" />
    </van-cell-group>
    <div class="submit-bar">
      <div class="confirm-info">
        支付金额： <span>¥{{ transferprice }}</span>
      </div>
      <van-button class="submit-btn" type="primary" @click="next">提交</van-button>
    </div>
<!--    <Footer @submit="next" />-->
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
import { getDebtdetails, debtSubscription } from '../../api/debtmarket'
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import Footer from "@/components/Footer";
export default {
  name: 'SubscriptionConfirmation',
  components: {Footer},
  data() {
    return {
      show: false,
      value: '',
      showKeyboard: true,
      projectName: '',
      apr: '',
      discount: '',
      transferprice: '',
      money: ''
    }
  },
  mounted() {
    // window.addEventListener('storage',function(){//.auth.user.is_set_pay_password
    //   let item = JSON.parse(localStorage.getItem('m_assets_garden')).auth.user.is_set_pay_password;
    //   this.setPass = item;
    //   // this.$store.commit('updatedUserPwdStatus', num)
    //   console.log(this.setPass);
    // })
  },
  methods: {
    next() {
      let is_set_pay_password = JSON.parse(localStorage.getItem('m_assets_garden')).auth.user.is_set_pay_password
      let setPass = localStorage.getItem('is_set_pay_password')
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
      //     window.location.href='/#/setPassWord'
      //   })
      //   return
      // }
      this.value = ''
      this.showKeyboard = true
      this.show = true
    },
    onInput(key) {
      this.value = (this.value + key).slice(0, 6)
      if (this.value.length == 6) {
        this.subscriptionConfirm()
      }
    },
    onDelete() {
      this.value = this.value.slice(0, this.value.length - 1)
    },
    getdetails() {
      getDebtdetails({ debt_id: this.$route.query.id, products: this.$route.query.products }).then(res => {
        if (res.code == 0) {
          this.discount = res.data.discount
          this.transferprice = res.data.transferprice
          this.money = res.data.money
          this.projectName = res.data.name
          this.apr = res.data.apr
        }
      })
    },
    subscriptionConfirm() {
      let params = {
        products: this.$route.query.products,
        debtArr: JSON.stringify([{ debt_id: this.$route.query.id, money: this.money }]),
        transaction_password: this.value,
        buy_code: this.$route.query.code ? this.$route.query.code : ''
      }
      this.$loading.open()
      debtSubscription(params)
        .then(res => {
          if (res.code == 2076) {
            //交易密码错误
            this.$toast(res.info)
            return
          }
          this.show = false
          const { undertake_endtime: time, debt_tender_id: id, products } = res.data
          this.$router.push({
            name: 'subscriptionResult',
            query: { code: res.code, reason: res.info, time, id, products }
          })
          // 认购结果 https://host/#/dept/subscriptionResult?code=res.code&reason=res.info&time=res.data.undertake_endtime&id=res.data.debt_tender_id&products=res.data.products
          // const [url] = res.data || []
          // if (url) {
          //   window.location.href = url
          // }
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    forget() {
      window.location.href = '/#/findPassWord'
    }
  },
  created() {
    this.getdetails()
  }
}
</script>

<style lang="less" scoped>
.subscription {
  margin-top: 10px;
  .submit-bar {
    width: 100%;
    height: 50px;
    line-height: 50px;
    position: absolute;
    left: 0;
    bottom: 0;
    background-color: #fff;
    display: flex;
    .confirm-info {
      flex: 1;
      padding: 0 15px;
      color: #999;
      span {
        float: right;
        color: #404040;
        font-size: 16px;
      }
    }
    .submit-btn {
      width: 117px;
      height: 50px;
    }
  }
  .pop-title {
    line-height: 50px;
    text-align: center;
  }
  .van-password-input__security:after {
    content: '';
  }
  .forget {
    padding: 10px 10px 20px;
    text-align: center;
    color: #fc810c;
  }

  .footer {
    position: absolute;
    bottom: 0;
    left: 0;
    padding: 0 13px;
    .submit-btn {
      width: 100%;
      font-size: 16px;
      height: 40px;
      border-radius: 7px;
    }
    p {
      padding: 20px 0;
      font-size: 13px;
      line-height: 24px;
      color: #555;
      a {
        color: #f18c16;
      }
    }
  }
}
</style>
