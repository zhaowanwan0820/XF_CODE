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
    </van-cell-group>
    <div class="submit-bar">
      <div class="confirm-info">
        支付金额： <span>¥{{ transferprice }}</span>
      </div>
      <van-button class="submit-btn" type="primary" @click="next">提交</van-button>
    </div>
    <van-popup v-model="show" closeable position="bottom" :close-on-click-overlay="false" :style="{ height: 'auto' }">
      <div class="pop-title">请输入交易密码</div>
      <!-- 密码输入框 -->
      <van-password-input :value="value" :focused="showKeyboard" @focus="showKeyboard = true" />
      <div class="forget">
        <span class="reset" @click="forget">忘记密码?</span>
      </div>
      <!-- 数字键盘 -->
      <van-number-keyboard :hide-on-click-outside="false" :show="showKeyboard" @input="onInput" @delete="onDelete" @blur="showKeyboard = false" />
    </van-popup>
  </div>
</template>

<script>
import { getDebtdetails, debtSubscription } from '../../api/debtmarket'
export default {
  name: 'SubscriptionConfirmation',
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
  methods: {
    next() {
      if (!this.$store.getters.isSetPassword) {
        this.$dialog.confirm({ message: '您还没有设置交易密码,请前往设置' }).then(() => {
          // this.$router.push({ name: 'settpass' })
          window.location.href='/h5#/transPwdSet'
        })
        return
      }
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
      getDebtdetails({ debt_id: this.$route.query.id,products: this.$route.query.products }).then(res => {
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
        buy_code:this.$route.query.code?this.$route.query.code:''
      }
      this.$loading.open()
      debtSubscription(params).then(res => {
        if(res.code==2076){   //交易密码错误
          this.$toast(res.info)
          return
        }
        this.show = false
        this.$router.push({ name: 'subscriptionResult', query: { code: res.code,reason:res.info,time:res.data.undertake_endtime } })
      }).finally(() => {
        this.$loading.close()
      })
    },
    forget(){
      window.location.href='/h5/#/transPwdSet'
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
  .forget{
    padding: 10px 10px 20px;
    text-align: center;
    color: #fc810c;
  }
}
</style>
