<template>
  <div>
    <van-notice-bar
      text="风险提示：您本次投资需承担各类风险，本金可能遭受损失！"
      left-icon="warning"
      :scrollable="false"
      wrapable
    />
    <div class="subject-info">
      <div class="time-left">
        <van-count-down class="countdown" :time="time" format="DD 天 HH 时 mm 分 ss 秒" />
        <span class="text">剩余有效期：</span>
      </div>
      <van-row>
        <van-col span="8">
          <div class="big-value">{{ discount }} <span>折</span></div>
          <div class="legend">转让折扣</div>
        </van-col>
        <van-col span="8">
          <div class="big-value">{{ money }} <span>元</span></div>
          <div class="legend">转让金额</div>
        </van-col>
        <van-col span="8">
          <div class="big-value">{{ transferprice }} <span>元</span></div>
          <div class="legend">转让价格</div>
        </van-col>
      </van-row>
    </div>
    <ul class="subject-card" v-if="products==5">
      <li>
        <span class="card-title">产品名称</span><span class="card-value">{{ projectName }}</span>
      </li>
      <!-- <li><span class="card-title">年利率</span><span class="card-value">{{apr}}</span></li> -->
      <li>
        <span class="card-title">到期时间</span><span class="card-value">{{ project_end_time }}</span>
      </li>
      <li>
        <span class="card-title">还款方式</span><span class="card-value">{{ style }}</span>
      </li>
      <li>
        <span class="card-title">发行人/融资方简称</span><span class="card-value">{{ guarantor }}</span>
      </li>
    </ul>
     <ul class="subject-card" v-else>
      <li>
        <span class="card-title">项目名称</span><span class="card-value">{{ projectName }}</span>
      </li>
      <!-- <li><span class="card-title">年利率</span><span class="card-value">{{apr}}</span></li> -->
      <li>
        <span class="card-title">项目到期时间</span><span class="card-value">{{ project_end_time }}</span>
      </li>
      <li>
        <span class="card-title">还款方式</span><span class="card-value">{{ style }}</span>
      </li>
      <li>
        <span class="card-title">保障机构</span><span class="card-value">{{ guarantor }}</span>
      </li>
    </ul>
    <!--    <van-tabs v-model="active" line-width="60px">-->
    <!--      <van-tab title="项目信息">内容 1</van-tab>-->
    <!--      <van-tab title="保障方信息">内容 2</van-tab>-->
    <!--    </van-tabs>-->
    <van-button class="subscription-btn" type="primary" @click="subscription">立即认购</van-button>
    <van-dialog v-model="show" title="" show-cancel-button @confirm="submitCode">
      <div class="form-item">
        <label>认购码</label>
        <input type="text" v-model="code" placeholder="请输入认购码" />
      </div>
    </van-dialog>
  </div>
</template>

<script>
import { getDebtdetails, checkCode } from '../../api/debtmarket'
export default {
  name: 'SubjectDetails',
  data() {
    return {
      show: false,
      code: '',
      is_orient: '', //是否定向
      time: 0,
      active: 0,
      discount: '',
      transferprice: '',
      money: '',
      projectName: '',
      apr: '',
      project_end_time: '',
      style: '',
      guarantor: '',
      products:0
    }
  },

  methods: {
    subscription() {
      // if (this.$store.state.auth.risk_level && this.$store.state.auth.risk_level.level_id == 5) {
      if (this.is_orient == 1) {
        this.show = true
      } else {
        this.$router.push({
          name: 'subscriptionConfirmation',
          query: { id: this.$route.query.id, products: this.$route.query.products }
        })
      }
      // } else {
      //   this.$dialog
      //     .confirm({
      //       message: `您的风险承受能力等级为：${this.$store.state.auth.risk_level.level_name}，当前不符合债权认购资质（C5-积极型），请重新测评！`,
      //       confirmButtonText: '重新测评',
      //       cancelButtonText: '稍后'
      //     })
      //     .then(() => {
      //       this.$router.push({ name: 'evaluation', params: { type: 1 } })
      //     })
      //     .catch(() => {
      //       // on cancel
      //     })
      // }
    },
    submitCode() {
      if (!this.code) {
        this.$toast('认购码不能为空')
        return
      }
      this.$loading.open()
      checkCode({ debt_id: this.$route.query.id, products: this.$route.query.products, buy_code: this.code })
        .then(res => {
          if (res.code == 0) {
            this.$router.push({
              name: 'subscriptionConfirmation',
              query: { id: this.$route.query.id, products: this.$route.query.products, code: this.code }
            })
          } else {
            this.$toast(res.info)
          }
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    getdetails() {
      getDebtdetails({ debt_id: this.$route.query.id, products: this.$route.query.products }).then(res => {
        if (res.code == 0) {
          let ct = Date.now()
          this.time = res.data.endtime * 1000 - ct
          this.discount = res.data.discount
          this.transferprice = res.data.transferprice
          this.money = res.data.money
          this.projectName = res.data.name
          this.apr = res.data.apr
          this.project_end_time = this.utils.formatDate('YYYY-MM-DD', res.data.endtime)
          this.style = res.data.loantype_name
          this.guarantor = res.data.agency_name
          this.is_orient = res.data.is_orient
          this.products = this.$route.query.products
        }
      })
    }
  },
  created() {
    this.getdetails()
  }
}
</script>

<style lang="less" scoped>
.time-left {
  height: 30px;
  line-height: 30px;
  padding: 10px 20px 0;
  color: rgba(255, 255, 255, 0.64);
  .text {
    float: right;
  }
  .countdown {
    float: right;
    color: rgba(255, 255, 255, 0.64);
  }
}
.subject-info {
  height: 118px;
  background-color: #e07d1f;
  text-align: center;
  .big-value {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 22px;
    color: #fff;
    margin-top: 15px;
    span {
      font-size: 14px;
    }
  }
  .legend {
    color: rgba(255, 255, 255, 0.4);
    font-size: 14px;
  }
}
.subject-card {
  padding: 10px 15px;
  background-color: #fff;
  margin-bottom: 10px;
  li {
    font-size: 14px;
    line-height: 28px;
    display: flex;
    .card-title {
      width: 120px;
      color: #707070;
    }
    .card-value {
      flex: 1;
      text-align: right;
      color: #404040;
    }
  }
}
.subscription-btn {
  width: 100%;
  position: absolute;
  bottom: 0;
  left: 0;
}
.form-item {
  padding: 50px 15px;
  display: flex;
  line-height: 39px;
  label {
    width: 70px;
    white-space: nowrap;
  }
  input {
    height: 39px;
    border: 1px solid rgba(231, 231, 231, 1);
    flex: 1;
    text-indent: 1em;
  }
}
</style>
