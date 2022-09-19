<template>
  <div class="container">
    <div class="trans-title">
      <p>您需通过银行转账的方式，向转让人账号进行付款。</p>
      <p>
        请于<van-count-down :time="params.undertake_endtime * 1000" style="display: inline-block;" />
        秒进行支付并提交转账信息。
      </p>
    </div>
    <div class="seller">
      <div class="seller-title">收款人信息</div>
      <div class="seller-box" style="padding-top: 6px;" @click="isCopy(params.payee_name)">
        <label>收款人姓名</label>
        <p>{{ params.payee_name }}</p>
        <span>复制</span>
      </div>
      <div class="seller-box" @click="isCopy(params.payee_bankzone)" style="white-space: nowrap;">
        <label>开户行</label>
        <p>{{ params.payee_bankzone }}</p>
        <span>复制</span>
      </div>
      <div class="seller-box" @click="isCopy(params.payee_bankcard)">
        <label>银行卡号</label>
        <p>{{ params.payee_bankcard }}</p>
        <span>复制</span>
      </div>
      <div class="seller-message">注意资金安全，线下付款务必保留凭证</div>
    </div>
    <div class="block">
      <div class="seller-title">付款信息</div>
      <div class="block-box">
        <label><span>*</span>付款人姓名</label>
        <van-field v-model="form.payer_name" placeholder="中文、英文字母、空格" />
      </div>
      <div class="block-k">
        <label><span>*</span>开户行</label>
        <div class="form-input">
          <van-dropdown-menu>
            <van-dropdown-item v-model="form.payer_bankzone_id" :options="actions" />
          </van-dropdown-menu>
        </div>
      </div>
      <div class="block-box">
        <label><span>*</span>银行卡号</label>
        <van-field v-model="form.payer_bankcard" placeholder="请输入" @input="getInput" />
      </div>
      <div class="block-box">
        <label><span>*</span>付款金额</label>
        <van-field v-model="form.account" type="number" placeholder="请输入" />
      </div>
      <div class="files">
        <label><span>*</span>付款凭证</label>
        <van-uploader v-model="fileList" multiple :max-count="2" :upload-text="upLoad_text" />
      </div>
    </div>
    <div class="footer">
      <h4>注意事项：</h4>
      <p>1. 请在光线较好的环境下拍照,确保照片清晰可识别</p>
      <p>2. 银行转账截图的内容需确保信息可查验<span>(*务必带银行标识)</span></p>
      <p>3. 提交线下付款凭证代表您确定通过线下转账方式已将资金交割给转让人</p>
      <p>4. 请务必填写真实转账信息(尽可能用本人银行卡，以免出现争议)，如转账有误请及时联系平台</p>
    </div>
    <div class="btn" @click="submit">提交付款信息</div>
  </div>
</template>

<script>
import { getUserInfo, setCertificate } from '../../../api/transfer'
export default {
  name: 'TransferPayments',
  data() {
    return {
      params: {},
      form: {
        debt_tender_id: '', //认购债权记录ID
        products: '', //所属产品：1尊享债转 2普惠供应链 3工场微金 4智多新
        payer_name: '', //付款人姓名
        account: '', //付款金额
        payer_bankzone_id: 0, //付款人开户行id
        payer_bankzone: '', //付款人开户行
        payer_bankcard: '', //付款人银行卡号
        pay_voucher: '' //付款凭证
      },
      fileList: [],
      actions: [{ text: '请选择付款银行', value: 0 }]
    }
  },
  created() {
    this.form.debt_tender_id = this.$route.query.id
    this.form.products = this.$route.query.products
    this.getInfo()
  },
  computed: {
    upLoad_text() {
      return this.fileList.length + '/2'
    }
  },
  methods: {
    getInfo() {
      getUserInfo(this.form).then(res => {
        if (res.code == 0) {
          this.params = res.data
          this.actions = [...this.actions, ...res.data.payer_bankzone]
          res.data.payer_bankzone.forEach((val, i) => {
            this.actions[i + 1].text = val.bankzone
            this.actions[i + 1].value = val.bankcard_id
          })
        }
      })
    },
    //验证银行卡只能输入数字和字母
    getInput(e) {
      for (let i = 0; i < e.length; i++) {
        if (/^[0-9a-zA-Z]+$/.test(e[i])) {
          this.form.payer_bankcard = e
        } else {
          this.form.payer_bankcard = e.substr(0, i)
          return
        }
      }
    },
    isCopy(data) {
      let url = data
      let oInput = document.createElement('input')
      oInput.value = url
      document.body.appendChild(oInput)
      oInput.select() // 选择对象;
      document.execCommand('Copy') // 执行浏览器复制命令
      this.$toast('复制成功')
      oInput.remove()
    },
    submit() {
      let arr_img = []
      this.fileList.forEach(item => {
        arr_img.push(item.content)
      })
      this.form.pay_voucher = JSON.stringify(arr_img)
      this.actions.forEach(item => {
        if (item.value == this.form.payer_bankzone_id) {
          this.form.payer_bankzone = item.text
        }
      })
      if (!this.form.payer_name) {
        this.$toast('请填写付款人姓名')
        return false
      }
      if (!this.form.payer_bankzone) {
        this.$toast('请选择付款人开户行')
        return false
      }
      if (!this.form.payer_bankcard) {
        this.$toast('请填写付款银行卡号')
        return false
      }

      if (!this.form.account) {
        this.$toast('请填写付款金额')
        return false
      }
      if (this.fileList == 0) {
        this.$toast('请上传付款凭证')
        return false
      }
      this.$loading.open()
      setCertificate(this.form)
        .then(res => {
          // console.log(res)
          this.$loading.close()
          if (res.code == 0) {
            this.$toast(res.info)
            //liuchunhua 跳转
            this.$router.replace({
            name: "mysubscription"
          });
            // this.$router.go(-1)
          } else {
            this.$toast(res.info)
          }
        })
        .finally(() => {
          this.$loading.close()
        })
    }
  }
}
</script>

<style lang="less" scoped>
.container {
  /deep/.van-dropdown-menu__title:after {
    position: absolute;
    top: 50%;
    right: 6px;
    margin-top: -1.333vw;
    content: '';
  }
  .trans-title {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 58px;
    background-color: rgba(255, 248, 231, 1);
    text-align: center;
    p {
      line-height: 20px;
      font-size: 12px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(253, 127, 66, 1);
    }
  }
  .seller {
    margin: 10px 0;
    padding: 0 15px 0 20px;
    background-color: #fff;
    .seller-box {
      display: flex;
      align-items: center;
      height: 20px;
      line-height: 20px;
      margin-bottom: 15px;
      label {
        display: inline-block;
        width: 92px;
      }
      span {
        text-align: right;
        display: inline-block;
        width: 30px;
        font-size: 11px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(252, 129, 12, 1);
      }
      p {
        flex: auto;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }
    .seller-message {
      text-align: right;
      height: 17px;
      font-size: 12px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(153, 153, 153, 1);
      line-height: 17px;
      padding-bottom: 15px;
    }
  }
  .seller-title {
    height: 44px;
    line-height: 44px;
    font-size: 14px;
    font-family: PingFangSC-Semibold, PingFang SC;
    font-weight: 600;
    color: rgba(64, 64, 64, 1);
    border-bottom: 1px solid rgba(234, 234, 234, 1);
    margin-bottom: 10px;
  }
  .block {
    background-color: #fff;
    padding: 0 20px;
    .block-box {
      display: flex;
      align-items: center;
      padding-bottom: 20px;
      label {
        width: 85px;
      }
      /deep/.van-cell {
        padding: 0;
        flex: 1;
      }
      /deep/.van-cell__value {
        height: 35px;
        line-height: 35px;
        border: 1px solid rgba(231, 231, 231, 1);
        input {
          text-indent: 10px;
          font-size: 15px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(64, 64, 64, 1);
        }
      }
    }
    .block-k {
      display: flex;
      align-items: center;
      padding-bottom: 20px;
      .form-input {
        flex: 1;
        .van-dropdown-menu {
          height: 35px;
          line-height: 35px;
          /deep/.van-ellipsis {
            width: 230px;
            font-size: 15px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(64, 64, 64, 1);
          }
        }
      }
    }
    .files {
      display: flex;
    }
    label {
      display: inline-block;
      width: 85px;
      font-size: 14px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(112, 112, 112, 1);
      span {
        font-size: 14px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(224, 32, 32, 1);
        margin-right: 2px;
      }
    }
  }
  .footer {
    padding: 20px 20px 15px;
    margin-bottom: 50px;
    h4 {
      height: 17px;
      font-size: 12px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
      line-height: 17px;
      padding-bottom: 15px;
    }
    p {
      font-size: 12px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(153, 153, 153, 1);
      line-height: 17px;
      padding-bottom: 10px;
      span {
        color: rgba(224, 32, 32, 1);
      }
    }
  }
  .btn {
    position: fixed;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 50px;
    line-height: 50px;
    background: rgba(252, 129, 12, 1);
    font-size: 18px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(255, 255, 255, 1);
    text-align: center;
  }
}
/deep/ .van-dropdown-menu__item {
  height: 38px;
  border: 1px solid rgba(231, 231, 231, 1);
}

/deep/.van-dropdown-menu__bar {
  height: 38px;
}
</style>
