<template>
  <div>
    
    <div class="subject-info ">
      <h2 style="margin-top:10px">求购范围</h2>
      <div class="text">本专区的所有求购信息，仅针对借款人为“汇源”的债权。您可以点击下【我要出售】按钮，出售您手中借款人为“汇源”的债权。</div>
      <div style="height:15px"></div>
      <h2>关键词解读</h2>
      <div class="text">求购债权总额：是本次求购债权的债权总金额。</div>
      <div class="text"> 已购债权总额：指在本次求购债权总金额中，求购人已经成功求购到的总额。</div>
      <div class="text" >剩余求购额度：本次求购中，剩余可收购的债权额度。</div>
    </div>
   
     <ul class="subject-card">
      <li>
        <span class="card-title">求购债权总额：</span><span class="card-value">{{ total_amount }}</span>
      </li>
      <!-- <li><span class="card-title">年利率</span><span class="card-value">{{apr}}</span></li> -->
      <li>
        <span class="card-title">折扣</span><span class="card-value">{{ fmtDiscount }}</span>
      </li>
      <li>
        <span class="card-title">剩余求购额度：</span><span class="card-value">{{ surplus_amount }}</span>
      </li>
      <li>
        <span class="card-title">求购进度：</span><span class="card-value">{{ fmtScale }}</span>

      </li>
      <li>

        <span class="card-title">剩余有效期：</span><span class="card-value"></span>
                        <van-count-down class="countdown" :time="time" format="DD 天 HH 时 mm 分 ss 秒" />

      </li>
    </ul>

    <div class="sell-btn">
      <div class="btn">
             <van-button :class="{ disabled: isOnline && !can_debt }" color="rgba(252, 129, 12, 1)" type="primary" @click="sell">我要出售</van-button>
       </div>
    </div>
    
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { getPurchaseDetails, checkCode } from '../../api/debtmarket'
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
      products:0,
      total_amount:0,
      surplus_amount:0,
      scale:0,
      btn_diable:false,
      can_debt:false,
    }
  },
   computed: {
  
    fmtDiscount() {
      return this.utils.formatFloat(this.discount)+'折'
    },
    fmtScale() {
      return this.utils.formatFloat(this.scale) + '%'
    },
     ...mapState({
      isOnline: state => state.auth.isOnline
    })
  },

  methods: {
    sell() {
      
      if(!this.isOnline){
          window.location.href = '/#/login?from=debtMarket'
          return
      }
      this.$router.push({
        name: 'sellConfirmation',
        query: { id: this.$route.query.id, products: this.$route.query.products }
      })
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
              name: 'sellConfirmation',
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
      getPurchaseDetails({ purchase_id: this.$route.query.id, products: this.$route.query.products }).then(res => {
        if (res.code == 0) {
          let ct = Date.now()
          this.time = res.data.endtime * 1000 - ct
          this.discount = res.data.discount
          this.transferprice = res.data.transferprice
          this.money = res.data.money
          this.projectName = res.data.name
          this.apr = res.data.apr
          this.total_amount = res.data.total_amount
          this.scale = res.data.scale
          this.surplus_amount = res.data.surplus_amount
          this.can_debt = res.data.surplus_amount
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
  height: 220px;
  padding: 10px;
  background-color: rgba(255, 255, 255, 0.64);
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
  margin-top: 10px;
  padding: 10px 15px;
  background-color: #fff;
  margin-bottom: 10px;
  li {
    font-size: 14px;
    line-height: 28px;
    display: flex;
    .card-title {
      width: 120px;
      color: #4a4a4a;
    }
    .card-value {
      flex: 1;
      text-align: right;
      color: #4a4a4a;
    }
  }
}
.sell-btn {
    width: 100%;
    position:fixed; 
    bottom:0;
    background: #fbfbfb;
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
      .disabled {
      opacity: 0.3;
      pointer-events: none;
    }
  }    
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
.text {
  margin-left: 15px;
  margin-right: 15px;
  text-align: left;
  line-height: 20px;
  color: #4a4a4a;
}
h2{
 
  color: #333333;
}
</style>
