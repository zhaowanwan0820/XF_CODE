<template>
  <div class="containor">
    <div class="status-alert">
      <template v-if="subscribe">
        <p v-if="info.status == 1">
          您需在 <van-count-down :time="time" format="HH时mm分" /> 内完成转账并提交付款信息。
        </p>
        <p v-if="info.status == 6">卖方需在 <van-count-down :time="time" format="HH时mm分" /> 查账，并进行确认操作。</p>
        <p v-if="info.status == 7">
          客服已介入，请关注交易结果短信，并密切关注先锋债转市场，最终以其中该订单状态为准。
        </p>
      </template>
      <template v-else>
        <p v-if="info.status == 1">
          剩余时间 <van-count-down :time="time" format="DD天HH时mm分" /> 。 <br />若超时无人承接，系统将关闭交易
        </p>
        <p v-if="info.status == 5">
          买方需在 <van-count-down :time="time" format="HH时mm分" /> 内完成转账并提交付款信息。
        </p>
        <div v-if="info.status == 6">
          <p v-if="info.is_appeal != 0">
            客服已介入，请关注交易结果短信，并密切关注先锋债转市场，最终以其中该订单状态为准。
          </p>
          <p v-else>卖方需在 <van-count-down :time="time" format="HH时mm分" /> 查账，并进行确认操作。</p>
        </div>
      </template>
    </div>
    <div class="subject top">
      <div class="subName">
        <van-row>
          <van-col span="12" class="text-show">{{ product_name }}//{{ info.name }}</van-col>
          <van-col span="12" class="tr status"> {{ Status(info.status) }}</van-col>
        </van-row>
      </div>
      <div class="subNo">债转编号&nbsp;&nbsp; {{ info.serial_number || info.order_number }}</div>
      <van-row>
        <van-col span="8" class="tl">
          <div class="big-value">
            {{ info.money ? utils.formatMoney(info.money, true) : utils.formatMoney(info.wait_capital, true) }}
          </div>
          <div class="legend">待还本金</div>
        </van-col>
        <van-col span="8" class="tc">
          <div class="big-value">
            {{ info.discount }}
            <span>折</span>
          </div>
          <div class="legend">转让折扣</div>
        </van-col>
        <van-col span="8" class="tr">
          <div class="big-value">
            <!-- {{ utils.formatMoney(info.transferprice ? info.transferprice : (info.money * info.discount) / 10, true) }} -->
            {{ utils.formatMoney(info.transferprice ? info.transferprice : info.arrival_amount, true) }}
          </div>
          <div class="legend">转让价格</div>
        </van-col>
      </van-row>
    </div>
    <div class="subject-num" v-if="Showcode">
      <h4>
        认购码
        <span> {{ info.buy_code }}</span>
      </h4>
    </div>
    <div class="subject" v-if="Showpay">
      <div class="textTitle">收款信息</div>
      <p>
        <span>收款人姓名</span>
        <i>{{ info.payee_name || payee.payee_name }}</i>
      </p>
      <p>
        <span>开户行</span>
        <i>{{ info.payee_bankzone || payee.payee_bankzone }}</i>
      </p>
      <p>
        <span>收款帐号</span>
        <i> {{ info.payee_bankcard || payee.payee_bankcard }}</i>
      </p>
    </div>
    <div class="subject" v-if="Showpay">
      <div class="textTitle">付款信息</div>
      <p v-if="info.area_id == 0">
        <span>付款人姓名</span>
        <i>{{ info.payer_name }}</i>
      </p>
      <p v-if="info.area_id == 0">
        <span>开户行</span>
        <i>{{ info.payer_bankzone }}</i>
      </p>
      <p v-if="info.area_id == 0">
        <span>付款帐号</span>
        <i>{{ info.payer_bankcard }}</i>
      </p>
      <p>
        <span>付款金额</span>
        <i>{{ info.account || info.arrival_amount }}</i>
      </p>
      <p>
        <span>付款凭证</span>
        <template v-for="(item, index) in info.payment_voucher">
          <img :src="item" :key="index" @click="getImg(info.payment_voucher, index)" />
        </template>
      </p>
    </div>
    <div class="subject" v-if="Showorder">
      <div class="textTitle">订单信息</div>
      <p><span>订单编号</span>{{ info.order_number || info.serial_number }}</p>
      <p>
        <span v-if="info.addtime && info.addtime != 0">下单时间</span
        >{{ utils.formatDate('YYYY-MM-DD HH:mm', info.addtime) }}
      </p>
      <p v-if="info.submit_paytime && info.submit_paytime != 0">
        <span>付款时间</span>{{ utils.formatDate('YYYY-MM-DD HH:mm', info.submit_paytime) }}
      </p>
      <p v-if="info.appeal_addtime || info.adtime">
        <span>平台介入时间</span
        >{{
          utils.formatDate('YYYY-MM-DD HH:mm', info.appeal_addtime) || utils.formatDate('YYYY-MM-DD HH:mm', info.adtime)
        }}
      </p>
      <p v-if="info.successtime != 0">
        <span>交易成功时间</span>{{ utils.formatDate('YYYY-MM-DD HH:mm', info.successtime) }}
      </p>
    </div>
    <div class="btnbox" v-show="btnshow">
      <template v-if="subscribe">
        <div v-show="info.status == 1">
          <van-button class="subscription-btn" type="primary" plain @click="cancelOrder">取消订单</van-button>
          <van-button class="subscription-btn" type="primary" @click="goPay">转账付款</van-button>
        </div>
        <div v-show="info.status == 2">
          <van-button type="primary" @click="viewPDF" :disabled="contract_failed">{{ contract_btn_text }}</van-button>
        </div>
      </template>
      <template v-else>
        <div v-show="info.status == 1">
          <van-button class="subscription-btn" type="primary" @click="cancelDebt">撤销债转</van-button>
        </div>
        <div v-show="info.status == 6">
          <van-button class="subscription-btn" plain type="primary" @click="noPay">资金未到账</van-button>
          <van-button class="subscription-btn" type="primary" @click="makeCollections">确认收款</van-button>
        </div>
        <div v-show="info.status == 2">
          <van-button type="primary" @click="viewPDF" :disabled="contract_failed">{{ contract_btn_text }}</van-button>
        </div>
      </template>
    </div>
    <van-popup v-model="show" class="popBox">
      <div class="title">资金未到账请不要担心，客服将介入交易，进行买卖双方交易核实</div>
      <van-cell-group>
        <van-field
          v-model="params.outaccount"
          rows="4"
          autosize
          type="textarea"
          maxlength="100"
          placeholder="请描述您所遇到的具体情况"
          show-word-limit
        />
      </van-cell-group>
      <van-button type="primary" class="btn" @click="submit">提交</van-button>
    </van-popup>
    <van-popup v-model="showprot" closeable position="left" :style="{ width: '100%', height: '100%' }">
      <iframe :src="info.oss_download" frameborder="0" style="width: 100%;height: 100%;"></iframe>
    </van-popup>

    <!--  输入交易密码  -->
    <van-popup
      v-model="showkey"
      closeable
      position="bottom"
      :close-on-click-overlay="false"
      :style="{ height: 'auto', width: '100%', bottom: '-10px' }"
    >
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
import {
  getSubDetail,
  getTenderDetail,
  cancelOrder,
  PayeeInfo,
  CancelDebt,
  ConfirmGetMoney,
  NotGetMoney
} from '../../api/order'
import { Dialog, ImagePreview, Field, CountDown } from 'vant'
import { setTimeout } from 'timers'
import { mapState } from 'vuex'
export default {
  name: 'SubjecteDetail',
  data() {
    return {
      showkey: false,
      showprot: false,
      value: '',
      showKeyboard: true,
      src: '',
      subscribe: this.$route.params.id == 1 ? true : false, // true认购,false转让
      info: {},
      params: {
        products: this.$route.query.products, //1-尊享，2-普惠供应链，3-工场微金，4-智多新
        debt_id: this.$route.query.debt_id,
        debt_tender_id: this.$route.query.debt_id,
        outaccount: ''
      },
      params1: {
        products: this.$route.query.products, //1-尊享，2-普惠供应链，3-工场微金，4-智多新
        debt_id: this.$route.query.debt_id,
        transaction_password: ''
      },
      btnshow: false,
      Showcode: false,
      Showpay: false,
      Showorder: false,
      show: false,
      payee: {},
      time: 123123000,
      // imgurl: process.env.VUE_APP_APPSERVER_ORIGIN_PROD,
      promsg: ''
    }
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      user: state => state.auth
    }),
    // 生成合同失败
    contract_failed() {
      return this.info.remark_status == 1 || this.info.remark_status == ''
    },
    // 合同按钮文案
    contract_btn_text() {
      let s_name = ''
      if (this.contract_failed) {
        s_name = '合同生成中'
      } else {
        s_name = '债转合同'
      }
      return s_name
    },
    product_name() {
      let s_name = ''
      const product_id = this.params.products
      if (product_id == 1) {
        s_name = '尊享'
      } else if (product_id == 2) {
        s_name = '普惠'
      } else if (product_id == 3) {
        s_name = '工场微金'
      } else if (product_id == 4) {
        s_name = '智多新'
      }
      return s_name
    }
  },
  methods: {
    onChange(index) {
      this.index = index
    },
    getImg(images, index) {
      ImagePreview({
        images: images
      })
    },
    // 协议
    viewPDF() {
      if (this.info.remark_status == 2) {
        if (this.yjApp.getAppVersion() == undefined) {
          window.open(this.info.oss_download, '_blank')
        } else {
          this.yjApp.openAppPage(this.info.oss_download)
        }
      } else {
        Dialog.alert({
          message:
            '合同生成失败，请联系客服\n<a style="margin-top: 10px;display: inline-block;" href="tel:010-89929006">010-89929006</a>',
          confirmButtonText: '我知道了'
        })
      }
    },
    noPay() {
      // 资金未到账
      this.show = true
    },
    submit() {
      // 资金未到账提交
      NotGetMoney(this.params).then(res => {
        this.$toast(res.info)
        if (res.code == 0) {
          setTimeout(this.$router.push({ name: 'inService' }), 2000)
        }
      })
    },
    makeCollections() {
      // 确认收款
      Dialog.confirm({
        title: '提示',
        message: `是否确认收款？<br>确认收款后交易成功，系统将变更债权关系`
      }).then(() => {
        this.showkey = true
      })
    },

    //提交
    onInput(key) {
      this.value = (this.value + key).slice(0, 6)
      if (this.value.length == 6) {
        this.showkey = false
        this.$loading.open()
        this.params1.transaction_password = this.value
        ConfirmGetMoney(this.params1)
          .then(res => {
            this.$toast(res.info)
            if (res.code == 0) {
              setTimeout(this.$router.push({ name: 'success1' }), 2000)
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
    forget() {
      window.location.href = '/#/findPassWord'
    },
    cancelOrder() {
      // 认购取消订单
      Dialog.confirm({
        title: '提示',
        message: '是否取消该订单？'
      }).then(() => {
        cancelOrder(this.params).then(res => {
          this.$toast(res.info)
          if (res.code == 0) {
            setTimeout(this.$router.push({ name: 'mysubscription' }), 2000)
          }
        })
      })
    },
    cancelDebt() {
      // 债转撤销债转
      Dialog.confirm({
        title: '提示',
        message: '是否撤销该笔债转？'
      }).then(() => {
        CancelDebt(this.params).then(res => {
          this.$toast(res.info)
          if (res.code == 0) {
            setTimeout(this.$router.push({ name: 'mytransfer' }), 2000)
          }
        })
      })
    },
    goPay() {
      this.$router.push({
        name: 'transferpayments',
        query: { id: this.params.debt_id, products: this.params.products }
      })
    },
    getdetails() {
      if (this.subscribe) {
        // 认购
        // 收款人信息
        PayeeInfo(this.params).then(res => {
          if (res.code == 0) {
            this.payee = res.data
          } else {
            this.$toast(res.info)
          }
        })
        getTenderDetail(this.params).then(res => {
          if (res.code == 0) {
            this.info = {}
            this.info = res.data
            this.time = this.info.end_time * 1000
            this.Showorder = true
            // if (this.info.payment_voucher) {
            //   this.info.payment_voucher.forEach((val, index) => {
            //     this.info.payment_voucher[index] = this.imgurl + '/' + val
            //   })
            // }
            this.info.buy_code != 0 ? (this.Showcode = true) : (this.Showcode = false)
            if (this.info.status == 1) {
              this.btnshow = true
              this.Showpay = false
            } else {
              this.btnshow = false
              this.Showpay = true
            }
            if (this.info.status == 4 || this.info.status == 3) {
              this.Showpay = false
            }
            if (this.info.status == 2) {
              this.btnshow = true
            }
          } else {
            this.$toast(res.info)
          }
        })
        return
      } else {
        // 转让
        getSubDetail(this.params).then(res => {
          if (res.code == 0) {
            console.log(res)
            this.info = {}
            this.info = res.data
            this.time = this.info.count_down * 1000
            // if (this.info.payment_voucher) {
            //   this.info.payment_voucher.forEach((val, index) => {
            //     this.info.payment_voucher[index] = this.imgurl + '/' + val
            //   })
            // }
            if (this.info.buy_code != 0 && this.info.status != 3 && this.info.status != 4) {
              this.Showcode = true
            } else {
              this.Showcode = false
            }
            if (this.info.status == 1) {
              this.btnshow = true
            } else {
              this.btnshow = false
              this.Showpay = true
              this.Showorder = true
            }
            if (this.info.status == 6) {
              this.btnshow = true
            }
            if (this.info.status == 5) {
              this.btnshow = true
              this.Showorder = true
              this.Showpay = false
            }
            if (this.info.status == 2) {
              this.btnshow = true
            }
            if (this.info.status == 3 || this.info.status == 4) {
              this.Showpay = false
              this.Showcode = false
              this.Showorder = false
            }
          } else {
            this.$toast(res.info)
          }
        })
      }
    },
    Status(val) {
      if (this.subscribe) {
        return this.info.status_name
      } else {
        let opt = [
          // 转让状态
          { id: 1, name: '转让中' },
          { id: 2, name: '交易成功' },
          { id: 3, name: '交易取消' },
          { id: 4, name: '交易取消' },
          { id: 5, name: '待买方付款' }
        ]
        if (val == 6) {
          return this.info.is_appeal ? '待收款（客服介入）' : '待收款'
        } else {
          return this.findname(val, opt)
        }
      }
    },
    findname(val, list) {
      let finds = list.find(item => {
        if (item.id == val) {
          return item
        }
      })
      return finds ? finds.name : ''
    }
  },
  created() {
    this.getdetails()
  }
}
</script>

<style lang="less" scoped>
.containor {
  font-size: 14px;
  padding-bottom: 50px;

  .status-alert p {
    padding: 10px 20px;
    line-height: 20px;
    text-align: center;
    background-color: #ffefde;
    color: @themeColor;
    .van-count-down {
      display: inline-block;
    }
  }
  .subject,
  .subject-num {
    margin-bottom: 10px;
    background-color: #fff;
    padding: 10px 15px;
    &.top {
      margin: 0;
    }
    .subName {
      border-bottom: 1px solid rgba(244, 244, 244, 0.5);
      line-height: 40px;
      height: 40px;
      color: #888;
      font-weight: 500;
      .status {
        color: @themeColor;
        font-weight: normal;
      }
    }
    .subNo {
      color: #888;
      font-weight: 400;
      margin-top: 15px;
    }
    .big-value {
      padding-top: 15px;
      color: #404040;
      font-size: 19px;
      font-weight: 500;
      height: 30px;
    }
    .legend {
      color: #999;
      font-size: 13px;
    }
    .textTitle {
      font-weight: bold;
      color: #404040;
      line-height: 40px;
    }
    p {
      display: flex;
      line-height: 16px;
      padding: 7px 0;
      span {
        color: #707070;
        text-align: right;
        flex: 1;
        max-width: 90px;
        margin-right: 10px;
      }
      i {
        flex: 1;
        word-break: break-all;
        font-style: normal;
      }
      img {
        width: 75px;
        height: 75px;
        display: inline-block;
        margin-right: 10px;
        vertical-align: text-top;
      }
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
  .subject-num h4 {
    color: @themeColor;
    text-align: center;
    line-height: 80px;
    font-size: 14px;
    border-top: 1px solid rgba(244, 244, 244, 0.5);
    span {
      font-size: 16px;
    }
  }
  .btnbox {
    width: 100%;
    position: fixed;
    background: #fff;
    box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
    padding: 10px;
    box-sizing: border-box;
    bottom: 0;
    left: 0;
    text-align: right;
    button {
      line-height: 34px;
      height: 34px;
      margin-left: 20px;
      letter-spacing: 1px;
    }
  }
  .popBox {
    width: 80%;
    border-radius: 8px;
    padding-top: 20px;
    box-sizing: border-box;
    .title {
      font-size: 16px;
      color: #333;
      line-height: 30px;
      padding: 0 30px;
    }
    .van-cell {
      border: 1px solid #cacaca;
      margin: 20px;
      width: auto;
    }
    .btn {
      width: 100%;
      font-size: 16px;
    }
  }
}
.tr {
  text-align: right;
}
.tl {
  text-align: left;
}
.tc {
  text-align: center;
}
.text-show {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  word-break: break-all;
}
</style>
