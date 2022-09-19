<template>
  <div class="transfer-item-container">
    <div class="transfer-item-wrapper" @click="goDetail">
      <div class="title">
        <van-row>
          <van-col :span="12" class="project-name text-show">{{ info.name }}</van-col>
          <van-col :span="12" class="order-status">{{ orderStatus }}</van-col>
        </van-row>
      </div>
      <div class="info">
        <!-- 待还本金 -->
        <div class="wait-money">
          <span class="num">{{ utils.formatMoney(info.money, true) }}</span>
          <p class="name">待还本金</p>
        </div>
        <!-- 转让折扣 -->
        <div class="transfer-discount">
          <span class="num">{{ info.discount }}</span>
          <p class="name">转让折扣</p>
        </div>
        <!-- 转让价格 -->
        <div class="transfer-price">
          <span class="num">{{ utils.formatMoney(info.arrival_amount, true) }}</span>
          <p class="name">转让价格</p>
        </div>
      </div>
      <div class="time-operate" v-if="s_transfering || s_waitmoney || s_canceled || s_success">
        <div class="expired-time" v-if="(s_transfering || (s_waitmoney && !is_appeal) && time)">
          剩余 <van-count-down :time="time" :format="timeFormat" />
        </div>
        <div class="empty"><!-- 占位 --></div>
        <div class="btn-box">
          <van-button
            size="small"
            type="primary"
            @click.stop="showCode(info.buy_code)"
            v-if="s_transfering && info.buy_code != 0"
            >认购码</van-button
          >
          <van-button size="small" plain type="primary" @click.stop="cancelOrder" v-if="s_transfering">撤销</van-button>
          <van-button class="big-btn" size="small" plain type="primary" @click.stop="notGetMoney" v-if="s_waitmoney"
            >资金未到账</van-button
          >
          <van-button size="small" type="primary" @click.stop="confirmGetMoney" v-if="s_waitmoney">确认收款</van-button>
<!--          <van-button
            size="small"
            type="primary"
            @click.native.stop="againPush"
            v-if="s_canceled"
            :disabled="!info.is_again_ok"
            >重新发布</van-button
          >-->
          <van-button
            class="big-btn"
            size="small"
            plain
            type="primary"
            @click.stop="viewDebt"
            v-if="s_success"
            :disabled="contract_failed"
            >{{ contract_btn_text }}</van-button
          >
        </div>
      </div>
    </div>

    <!-- 资金未到账弹出层 -->
    <van-popup v-model="showDialog">
      <div class="word">
        资金未到账请不要担心，客服将介入交易，进行买卖双方交易核实
      </div>
      <div class="remark">
        <van-cell-group>
          <van-field
            v-model="outaccount"
            @click.stop.native=""
            rows="8"
            autosize
            type="textarea"
            maxlength="100"
            placeholder="请描述您所遇到的具体情况"
            show-word-limit
            clearable
          />
        </van-cell-group>
      </div>
      <div class="btn">
        <van-button type="primary" @click.stop="submit">提交</van-button>
      </div>
    </van-popup>
    <!--  输入交易密码  -->
    <van-popup
      v-model="show"
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
import { CancelDebt, ConfirmGetMoney, NotGetMoney } from '../../../api/order'
import { setRepublish } from '../../../api/transfer'
import { TRANSFER_STATUS } from '../const'
export default {
  name: 'transferItem',
  props: ['info', 'products'],
  data() {
    return {
      TRANSFER_STATUS,
      showDialog: false,
      outaccount: '',
      params: {
        products: this.products,
        debt_id: this.info.debt_id
      },
      show: false,
      showKeyboard: true,
      operate: '',
      value: ''
    }
  },
  computed: {
    // 格式化状态
    orderStatus() {
      let _s = ''
      this.TRANSFER_STATUS.forEach(item => {
        if (item.id == this.info.status) {
          _s = item.name
          if (this.is_appeal && this.info.status == 6) {
            _s = '待收款(客服介入)'
          }
        }
      })
      return _s
    },
    // 客服是否介入
    is_appeal() {
      return this.info.is_appeal
    },
    // 转让中
    s_transfering() {
      return this.info.status == 1
    },
    // 待收款
    s_waitmoney() {
      return this.info.status == 6
    },
    // 已取消
    s_canceled() {
      return this.info.status == 3 || this.info.status == 4
    },
    // 交易成功
    s_success() {
      return this.info.status == 2
    },
    // 转让价格: 转让金额 * 折扣
    transferPrice() {
      return this.utils.formatMoney((Number(this.info.money) * Number(this.info.discount)) / 10, true)
    },
    time() {
      if (this.info.count_down > 0) {
        return this.info.count_down * 1e3
      } else {
        return 0
      }
    },
    timeFormat() {
      return this.s_transfering ? 'DD天HH时mm分ss秒' : 'HH时mm分ss秒'
    },
    // 生成合同中
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
    }
  },
  created() {},
  methods: {
    goDetail() {
      // /subscribeDetail/:id?products=products&debt_id=debt_id  1->认购，2->转让
      this.$router.push({
        name: 'subscribeDetail',
        params: { id: 2 },
        query: this.params
      })
    },
    // 展示认购码
    showCode(code) {
      this.cancelFail(`认购码： ${code}`)
    },
    // 撤销转让
    cancelOrder() {
      this.$dialog
        .confirm({
          message: '是否撤销该笔债转？',
          confirmButtonText: '是',
          cancelButtonText: '否'
        })
        .then(() => {
          CancelDebt(this.params).then(res => {
            if (res.code == 0) {
              this.$toast('操作成功')
              this.$emit('refresh')
            } else {
              this.cancelFail(res.info)
            }
          })
        })
        .catch(() => {})
    },
    // 撤销失败
    cancelFail(msg) {
      this.$dialog.alert({
        message: msg,
        confirmButtonText: '我知道了'
      })
    },
    // 确认收款
    confirmGetMoney() {
      this.value = ''
      this.$dialog
        .confirm({
          title: '是否确认收款？',
          message: '确认收款后交易成功，系统将变更债权关系',
          confirmButtonText: '是',
          cancelButtonText: '否'
        })
        .then(() => {
          this.show = true
          this.operate = 'confirm'
        })
        .catch(() => {})
    },
    // 资金未到账
    notGetMoney() {
      this.showDialog = true
      this.outaccount = ''
    },
    // 重新发布
    againPush(id) {
      console.log(this.operate)
      this.operate = 'publish'
      this.$router.push({
        name: 'release',
        query: { products: this.products, debt_id: this.info.debt_id, status: '2', id: this.info.tender_id }
      })
      // console.log('点击重新发布')
      // this.value = ''
      // this.showKeyboard = true
      // this.show = true
    },
    //提交
    onInput(key) {
      this.value = (this.value + key).slice(0, 6)
      if (this.value.length == 6) {
        this.show = false
        this.$loading.open()
        if (this.operate == 'confirm') {
          ConfirmGetMoney({ ...this.params, transaction_password: this.value })
            .then(res => {
              if (res.code == 0) {
                this.$toast('操作成功')
                this.$emit('refresh')
              } else {
                this.$toast(res.info)
              }
            })
            .finally(() => {
              this.$loading.close()
            })
        } else {
          setRepublish({ products: this.products, debt_id: this.info.debt_id, transaction_password: this.value })
            .then(res => {
              // console.log(res)
              if (res.code == 0) {
                this.$toast(res.info)
              } else {
                this.$toast(res.info)
              }
            })
            .finally(() => {
              this.$loading.close()
            })
        }
      }
    },
    onDelete() {
      this.value = this.value.slice(0, this.value.length - 1)
    },
    forget() {
      window.location.href = '/#/findPassWord'
    },
    submit() {
      if (!this.outaccount) {
        this.$toast('请输入描述')
        return
      }
      NotGetMoney({ outaccount: this.outaccount, ...this.params }).then(res => {
        if (res.code == 0) {
          this.$toast('操作成功')
          this.$emit('refresh')
        } else {
          this.$toast(res.info)
        }
      })
    },
    viewDebt() {
      if (this.info.remark_status == 2) {
        window.open(this.info.oss_download)
      } else {
        this.cancelFail(
          '合同生成失败，请联系客服\n<a style="margin-top: 10px;display: inline-block;" href="tel:010-89929006">010-89929006</a>'
        )
      }
    }
  }
}
</script>

<style lang="less" scoped>
.transfer-item-container {
  a {
    color: #075db3;
    margin-top: 10px;
    display: block;
  }
  .transfer-item-wrapper {
    background: #fff;
    margin-top: 10px;
    padding-left: 15px;
    .title {
      padding: 10px 15px 9px 0;
      height: 20px;
      font-size: 14px;
      font-weight: bold;
      color: #888;
      line-height: 20px;
      border-bottom: 1px solid #f4f4f4;
      .order-status {
        text-align: right;
        color: @themeColor;
        font-weight: 400;
      }
    }
    .info {
      margin-top: 14px;
      padding: 0 15px 10px 0;
      display: flex;
      align-items: stretch;

      .num {
        display: block;
        font-size: 18px;
        font-weight: bold;
        color: rgba(64, 64, 64, 1);
        line-height: 25px;
      }
      .name {
        font-size: 13px;
        font-weight: 400;
        color: rgba(153, 153, 153, 1);
        line-height: 18px;
        margin-top: 2px;
      }
      .wait-money {
        width: 133px;
        flex-basis: 133px;
      }
      .transfer-discount {
        width: 77px;
        flex-basis: 77px;
        text-align: center;
      }
      .transfer-price {
        flex: 1 0 0;
        text-align: right;
      }
    }
    .time-operate {
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 60px;
      padding-right: 15px;
      font-size: 14px;
      font-weight: 300;
      color: #404040;
      border-top: 1px solid #f4f4f4;
      .expired-time {
        display: flex;
        align-items: center;
      }
      /deep/ .van-count-down {
        font-size: 14px;
        margin-left: 10px;
      }
      .btn-box {
        /deep/ .van-button {
          margin-left: 15px;
          width: 70px;
        }
        .big-btn {
          width: 84px;
        }
      }
    }
    .text-show {
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      word-break: break-all;
    }
  }
  /deep/ .van-popup {
    /deep/ .van-hairline--top-bottom:after {
      border: 1px solid #cacaca;
      content: '';
    }
    width: 296px;
    padding: 20px;
    border-radius: 8px;
    box-sizing: border-box;
    .word {
      width: 100%;
      height: 56px;
      font-size: 16px;
      font-weight: 400;
      color: #333;
      line-height: 28px;
    }
    .remark {
      margin: 21px 0 49px;
    }
    .van-button {
      position: absolute;
      bottom: 0;
      left: 0;
      height: 48px;
      width: 296px;
    }
  }
  /deep/.van-password-input__security:after {
    content: '';
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
</style>
