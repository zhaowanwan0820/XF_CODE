<template>
  <div class="container">
    <mt-header class="header" title="还款兑付方案">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="detail-container" v-if="!empty">
      <div class="box">
        <div class="plan_time">
          <span class="time" v-show="!timeout && !success"> {{ cutTime }} </span>
          <span class="time gray" v-show="timeout && !success">
            兑付方案选择已截止<br />如有疑问，请咨询客服，客服电话：010-89920001
          </span>
          <!--          <div class="plan_title">{{ detail.title }}</div>-->
          <span class="succ" v-show="success"> 提交成功，将按您选择的方式对您进行还款 </span>
        </div>
      </div>
      <div class="box">
        <!-- <div class="plan_title">计划说明</div> -->
        <div class="con">
          <plan-info></plan-info>
        </div>
      </div>
      <div class="box">
        <div class="plan_title">所持有的项目信息</div>
        <div class="con">
          <table class="tablist" cellspacing="0" width="100%">
            <thead>
              <tr>
                <th>项目名称</th>
                <th>待还本金（元）</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in detail.dealList" :key="index">
                <td v-if="item.debtStatus == 1">
                  <div class="deal-name">{{ item.dealName }}</div>
                  <div class="hint">有进行中债转</div>
                </td>
                <td v-else>
                  {{ item.dealName }}
                </td>
                <td>{{ item.money }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="box">
        <div class="plan_title">兑付方案</div>
        <div class="con">
          <template v-if="success">
            <p class="wayi">{{ currentType(detail.wayId) }}</p>
          </template>
          <template v-else>
            <mt-radio v-model="type" :options="options"></mt-radio>
          </template>
        </div>
      </div>
      <div v-show="!success && !timeout">
        <div class="agrees">
          <label>
            <input type="checkbox" v-model="isAgreement" />我已阅读并同意该
            <span class="agree" @click="goAgreement(type)">《协议》</span> 内容
          </label>
        </div>
        <div class="button-wrapper">
          <button @click="nextType">同意该方案并提交</button>
        </div>
      </div>
      <div v-show="success" class="box">
        <div v-for="(item, index) in detail.contractRecordList" :key="index">
          <mt-cell
            :title="'还款兑付方案协议（' + (index + 1) + '）'"
            v-if="item.status == 2"
            @click.native="viewVote(item.contractAddr)"
            is-link
          ></mt-cell>
          <mt-cell
            :title="'还款兑付方案协议（' + (index + 1) + '）'"
            v-if="item.status == 0"
            value="协议生成中,请稍后查看"
          ></mt-cell>
          <mt-cell
            :title="'还款兑付方案协议（' + (index + 1) + '）'"
            v-if="item.status == 3"
            value="协议生成失败，请咨询客服"
          ></mt-cell>
        </div>
      </div>
    </div>
    <div class="empty" v-else>
      {{ emptycontant }}
    </div>
    <mt-popup class="mint-popup" v-model="showPopup" position="center">
      <div class="content">
        <div class="mint-msgbox-header">
          <div class="mint-msgbox-title">为确保是您本人操作，请输入手机验证码和交易密码进行验证</div>
        </div>
        <div class="mint-msgbox-content">
          <div class="mint-msgbox-input">
            <div class="set" v-show="codeNon">
              您还未设置交易密码请先
              <span class="goset" @click="goSetcode">去设置 >></span>
            </div>
            <div class="tel-wrapper">
              <span class="tabName">手机号</span>
              <span class="phone">{{ mobile }}</span>
            </div>
            <div class="tel-wrapper">
              <span class="tabName">验证码</span> <input type="number" v-model="sms_code" @input="checkVel" />
              <div class="vel" @click="getVerCodes" v-show="!isCountDown && !codeNon">获取验证码</div>
              <div class="vel" v-if="isCountDown">{{ countDown }}s</div>
            </div>
            <div class="tel-wrapper">
              <span class="tabName">交易密码</span><input type="password" v-model="transPass" @input="checkPas" />
              <div class="vel" @click="goSetcode" v-show="!codeNon">忘记密码?</div>
            </div>
            <div class="mint-msgbox-errormsg" v-show="errorshow">{{ errorHost }}</div>
          </div>
        </div>
        <div class="mint-msgbox-btns">
          <button class="mint-msgbox-btn mint-msgbox-confirm" @click="submit">确 定</button>
        </div>
      </div>
    </mt-popup>
  </div>
</template>
<script>
import { getDetail, getCode, getVelCode, submitPlan, checkCode, checkPass } from '../../api/newplane'
import { Radio, MessageBox, Indicator, Toast, Popup } from 'mint-ui'
import PlanInfo from './PlanInfo'
import { setTimeout } from 'timers'
export default {
  name: 'newPlan',
  data() {
    return {
      empty: false,
      paramsDetail: {
        planId: this.$route.query.id ? this.$route.query.id : '',
        type: this.$route.query.type ? this.$route.query.type : ''
      },
      emptycontant: '',
      sms_code: '', //验证码
      transPass: '', //交易密码
      pramas: {
        mobile: '',
        sms_code: 'wx_vcode'
      },
      detail: {
        title: '123',
        contractRecordList: [
          { status: 1, contractAddr: 'test' },
          { status: 0, contractAddr: '' },
          { status: 0, contractAddr: '' }
        ],
        transferFlag: ''
      },
      type: this.$route.query.pas_agree ? this.$route.query.pas_agree : '', //兑付
      options: [
        {
          label: '现金兑付',
          value: '1',
          disabled: false
        },
        {
          label: '实物抵债兑付',
          value: '2',
          disabled: false
        }
      ],
      cutTime: '0 天 0 小时 0 分钟 0 秒',
      isAgreement: false,
      showPopup: this.$route.query.pwd ? true : false,
      success: false,
      timeout: false,
      isCountDown: false, //是否展示倒计时
      countDown: 60,
      mobile: '',
      phone: '',
      codeNon: false,
      errorshow: false,
      errorHost: '',
      isVelGeting: false, //是否正在请求获取验证码接口，防止重复点击
      OSS: process.env.VUE_APP_OSS_SERVER
    }
  },
  components: {
    PlanInfo
  },
  created() {
    if (!this.$store.state.auth.isOnline) {
      Toast('请登录后操作！')
      this.$router.replace({ name: 'login', params: {} })
      return
    }
    this.getDetails()
    this.getCodes()
    if (this.showPopup) {
      this.isAgreement = true
    }
  },
  methods: {
    goBack() {
      if (this.$route.query.type == 2) {
        this.$router.push({ path: '/' })
      } else {
        this.$_goBack()
      }
    },
    viewVote(addr) {
      window.location.href = addr
    },
    getDetails() {
      Indicator.open()
      getDetail(this.paramsDetail)
        .then(
          res => {
            if (res.status == 200) {
              this.detail = res.data
              if (this.detail.wayId != 0) {
                this.success = true
                return
              }
              this.timeChange(res.data.cutoffTime)
            } else if (res.status == 30101) {
              this.$router.push('/home')
            }
          },
          err => {
            this.empty = true
            this.emptycontant = err.message
          }
        )
        .finally(() => {
          Indicator.close()
        })
    },
    timeChange(cutoffTime) {
      let now = Math.floor(this.getSysTime() / 1e3)
      let totalSeconds = cutoffTime - now
      let timer = setInterval(() => {
        totalSeconds--
        //天数
        let days = parseInt(totalSeconds / (60 * 60 * 24))
        let hours = parseInt((totalSeconds % (60 * 60 * 24)) / (60 * 60))
        let minutes = parseInt((totalSeconds % (60 * 60)) / 60)
        let seconds = totalSeconds % 60
        if (totalSeconds < 0) {
          totalSeconds = 0
          this.timeout = true
          this.showPopup = false
          clearInterval(timer)
          this.options.forEach(ite => {
            ite.disabled = true
          })
          return
        }
        this.cutTime = `${days} 天 ${hours} 小时  ${minutes} 分钟 ${seconds} 秒`
      }, 1000)
    },
    goAgreement(n) {
      if (!n) {
        MessageBox('提示', '选择兑付方案后才能查看对应协议，请先选择兑付方案！')
        return
      }
      if (n == 1) {
        this.$router.replace({
          name: 'voteAgree1',
          query: { id: this.paramsDetail.planId, type: this.paramsDetail.type }
        })
      } else {
        this.$router.replace({
          name: 'voteAgree2',
          query: { id: this.paramsDetail.planId, type: this.paramsDetail.type }
        })
      }
    },
    nextType() {
      this.detail.wayId = this.type
      if (!this.detail.wayId) {
        MessageBox('提示', '请选择兑付方案！')
        return
      }
      if (!this.isAgreement) {
        MessageBox('提示', '请阅读并勾选协议')
        return
      }
      if (this.detail.transferFlag == 1) {
        MessageBox('提示', '所持有项目有进行中的债转，请先去债转市场取消或者完结后才能选方案！')
        return
      }
      if (this.detail.debtConfirmationNum > 0) {
        MessageBox({
          title: '提示',
          message: '该兑付方案所持有的项目需全部确权，您有未确权的项目，请先去确权',
          closeOnClickModal: false,
          confirmButtonText: '去确权'
        }).then(action => {
          this.$router.replace({
            name: 'confirmationForXARH',
            query: { pas_agree: this.type, id: this.paramsDetail.planId, type: this.paramsDetail.type }
          })
        })
        return
      }
      this.showPopup = true
    },
    goSetcode() {
      // alert('跳转设置交易密码')
      this.$router.push({ name: 'transPwdSet', query: { pas_agree: this.type } })
    },
    getVerCodes() {
      // 获取验证码
      if (this.isVelGeting) {
        this.errorHost = '验证码已发送，两分钟内有效'
        this.errorshow = true
        return
      }
      this.isVelGeting = true
      getVelCode(this.pramas)
        .then(
          res => {
            if (res.code == 0) {
              this.isCountDown = true
              this.getCountDown()
            } else {
              this.errorHost = res.msg
              this.errorshow = true
            }
          },
          error => {
            console.log(error)
            this.errorHost = error.errorMsg
            this.errorshow = true
          }
        )
        .finally(() => {
          this.isVelGeting = false
        })
    },
    //获取验证码倒计时
    getCountDown() {
      if (this.isCountDown) {
        let timer = setInterval(() => {
          this.countDown--
          if (this.countDown <= 0) {
            this.isCountDown = false
            this.countDown = 60
            clearInterval(timer)
          }
        }, 1000)
      }
    },
    submit() {
      if (this.codeNon) {
        this.errorHost = '交易密码尚未设置，请先设置交易密码'
        this.errorshow = true
        return
      }
      if (!this.sms_code) {
        this.errorHost = '验证码不能为空'
        this.errorshow = true
        return
      }
      if (!this.transPass) {
        this.errorHost = '交易密码不能为空'
        this.errorshow = true
        return
      }
      let Code = false
      let pra = {
        mobile: this.pramas.mobile,
        code: this.sms_code
      }
      checkCode(pra).then(
        res => {
          console.log(res)
          if (res.code != 0) {
            this.errorHost = res.info
            this.errorshow = true
            this.isVelGeting = true
          } else {
            this.submitTrue()
          }
          if (res.code == 2405 || res.code == 2104) {
            this.isCountDown = false
            this.isVelGeting = false
          }
        },
        error => {
          this.errorHost = error.errorMsg
          this.errorshow = true
        }
      )
    },
    submitTrue() {
      Indicator.open()
      console.log(this.detail)
      let pramars = {
        planId: this.detail.id,
        wayId: this.type,
        transactionPassword: this.transPass
      }
      let dataStr = ''
      Object.keys(pramars).forEach(key => {
        dataStr += key + '=' + pramars[key] + '&'
      })
      dataStr = '?' + dataStr
      submitPlan(dataStr)
        .then(res => {
          if (res.status == 200) {
            Indicator.close()
            this.success = true
            this.showPopup = false
            // 修改详情接口传参
            this.paramsDetail.planId = this.detail.id
            this.paramsDetail.type = 1
            this.getDetails()
          } else {
            this.errorHost = res.message
            this.errorshow = true
          }
          if (res.status == 30102) {
            this.showPopup = false
            Toast(res.message)
            setTimeout(() => {
              this.timeout = true
            }, 1000)
          }
        })
        .finally(() => {
          Indicator.close()
        })
    },
    getCodes() {
      // 判断是否已设置交易密码
      getCode().then(res => {
        this.pramas.mobile = res.mobile
        // this.pramas.mobile ='10000300346'
        this.mobile = this.utils.formatPhone(res.mobile)
        if (res.transactionPassword == '0') {
          this.codeNon = true
        }
      })
    },
    // 验证手机验证码
    checkVel() {
      if (this.sms_code.length > 6) {
        this.sms_code = this.sms_code.slice(0, 6)
      }
      let velrule = /^\d{6}$/
      if (velrule.test(this.sms_code)) {
        this.velIsOk = true
      } else {
        return
      }
    },
    // 验证手机验证码
    checkPas() {
      if (this.transPass.length > 6) {
        this.transPass = this.transPass.slice(0, 6)
      }
      let velrule = /^\d{6}$/
      if (velrule.test(this.transPass)) {
        // this.velIsOk = true
      } else {
        return
      }
    },
    // 还款方式
    currentType(a) {
      a = a + ''
      let find = this.options.find(item => item.value == a)
      return find ? find.label : '现金兑付'
    }
  }
}
</script>
<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  display: -webkit-flex; /* Safari */
  flex-direction: column;
  .header {
    height: 50px;
    border-bottom: 1px solid #f4f4f4;
  }
  .detail-container {
    flex: 1;
    overflow-x: hidden;
    overflow-y: auto;
  }
  .empty {
    text-align: center;
    @include sc(14px, #666);
  }
  .set {
    text-align: center;
    @include sc(12px, #666);
    span {
      color: #f00;
    }
  }
  .box {
    background: #fff;
    margin-bottom: 11px;
    .con {
      padding: 0 10px 10px;
      @include sc(14px, #666);
      /deep/.mint-radio-label {
        @include sc(14px, #333);
      }
      .wayi {
        @include sc(14px, #333);
        padding-bottom: 10px;
        margin-top: -10px;
      }
    }
    /deep/.mint-cell-wrapper .mint-cell-value {
      width: auto;
      border-bottom: 1px solid #eee;
      font-size: 12px;
      color: #fc810c;
    }
    .mint-cell-title {
      width: 100%;
      @include sc(14px, #666);
    }
    /deep/.mint-cell-label {
      width: 100%;
    }
  }
  .plan_title {
    text-align: center;
    @include sc(16px, #333);
    font-weight: 600;
    line-height: 50px;
  }
  .agrees {
    @include sc(12px, #333);
    text-align: center;
    line-height: 30px;
    vertical-align: text-bottom;
    .agree {
      cursor: pointer;
      color: #3574fa;
    }
  }
  .plan_time {
    text-align: center;
    span {
      display: block;
      line-height: 30px;
      &.time {
        background: #ffe9d5;
        @include sc(14px, #fc810c);
        &.gray {
          background: #f3f3f3;
          line-height: 24px;
          padding: 6px 0;
          @include sc(14px, #a6a6a6);
        }
      }
      &.succ {
        @include sc(14px, #0fa840);
        font-weight: bold;
        padding: 10px 0;
      }
    }
  }
  .tablist {
    line-height: 30px;
    display: block;
    thead {
      width: 100%;
      display: block;
    }
    tbody {
      width: 100%;
      display: block;
      overflow: auto;
      max-height: 170px;
    }
    tr {
      display: flex;
    }
    th {
      background: #fff7ef;
      width: 50%;
      @include sc(14px, #666);
    }
    td {
      text-align: center;
      width: 50%;
      height: 50px;
      line-height: 50px;
      overflow: hidden;
      flex: 1;
      text-overflow: ellipsis;
      -webkit-line-clamp: 1; /* 限制在一个块元素显示的文本的行数 */
      @include sc(14px, #333);
      border-bottom: 1px solid#EDEFF2;
      .deal-name {
        line-height: 30px;
      }
      .hint {
        font-size: 12px;
        line-height: 20px;
        color: red;
      }
    }
  }
  /deep/ .mint-radio-core {
    width: 16px;
    height: 16px;
  }
  /deep/.mint-radio-input:checked + .mint-radio-core {
    background-image: url('../../assets/image/hh-icon/confirmation/icon-checked-on.png');
    background-size: 100%;
    border-color: #fc7f0c;
    &::after {
      display: none;
    }
    // }
  }
  input[type='checkbox'] {
    width: 14px;
    height: 14px;
    -webkit-appearance: none;
    border: none;
    margin: 0 4px;
    outline: none;
    background: url('../../assets/image/change-icon/check.png');
    background-size: 100%;
    vertical-align: text-bottom;
    &:checked {
      background: url('../../assets/image/change-icon/checked.png') no-repeat center;
      background-size: 100%;
    }
  }
  .button-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 10px;
    padding-bottom: 10px;
    button {
      width: 327px;
      height: 46px;
      color: #fff;
      font-size: 18px;
      line-height: 25px;
      background-color: #fc7f0c;
      border-radius: 2px;
      border: none;
      outline: none;
    }
  }
  .mint-popup {
    border-radius: 3px;
    width: 90%;
    overflow: hidden;
    .mint-msgbox-title {
      padding: 0 20px;
      line-height: 30px;
    }
    .mint-msgbox-content {
      padding: 0 20px 20px;
      @include sc(16px, #707070);
      .tel-wrapper {
        position: relative;
        display: flex;
        padding: 10px 0 24px;
        align-items: flex-end;
        .tabName {
          width: 80px;
          text-align: left;
          flex-shrink: 0;
        }
        .phone {
          color: #999;
        }
        input {
          border: none;
          flex-shrink: 1;
          border-radius: 0;
          border-bottom: 1px solid #bababa;
          box-shadow: none;
        }
        .vel {
          width: 70px;
          height: 16px;
          margin-left: 5px;
          text-align: center;
          color: #fc810c;
          font-size: 14px;
          flex-shrink: 0;
        }
        .deep {
          color: $markColor;
          pointer-events: auto;
        }
      }
    }
    /deep/.mint-msgbox-errormsg {
      text-align: center;
      display: block;
    }
  }
}
</style>
