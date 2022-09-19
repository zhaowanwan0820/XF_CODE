<template>
  <div>
    
     <ul class="subject-card">
       
       <div class="title">收购信息</div>
       <div class="link-top"></div>
      <li>
        <span class="card-title">在途债权合计：</span><span class="card-value">{{ wait_capital }}</span>
      </li>
      <!-- <li>
        <span class="card-title">收购折扣：</span><span class="card-value">{{ fmtDiscount }}</span>
      </li> -->
      <li>
        <span class="card-title">收购金额：</span><span class="card-value">{{ purchase_amount }}</span>
      </li>
      <li>
        <span class="card-title">收款人：</span><span class="card-value">{{ real_name }}</span>
      </li>
      <li>
        <span class="card-title">收款银行：</span><span class="card-value">{{ bank_name }}</span>
      </li>
      <li>
        <span class="card-title">银行卡号：</span><span class="card-value">{{ bank_card }}</span>
      </li>
      <li  v-if="status == 0">
        <span class="card-title"  >签约剩余时间：</span><span class="card-value"></span>
        <van-count-down class="countdown" :time="time" format="DD 天 HH 时 mm 分 ss 秒" />
      </li>
    </ul>

    <div class="subject-card">
       
        <div class="title">债权列表</div>
        <div class="link-top"></div>
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
          <template v-for="item in purchaseList">
            <div class="item" :key="item.deal_load_id" >
              <van-row>
                <van-col >
                  <p class="">项目名称：{{ item.deal_name }} </p>
                  <p class="">待还本金：{{ item.wait_capital }} 元</p>
                  <p class="">出借时间：{{ item.create_time }}</p>
                </van-col>
               
              </van-row>
            </div>
          </template>
        </van-list>
      </main>
    </div>

    <div class="sell-btn" v-if="status==0">
      <div class="btn">
             <van-button  color="rgba(252, 129, 12, 1)" type="primary" @click="sell">{{excludiveText}}</van-button>
       </div>
    </div>
    
      <ul class="subject-card" v-if="status != 0 && status != 5">
       
       <div class="title">合同与凭证</div>
       <div class="link-top"></div>
      <li>
        <span class="card-title" >债转合同：</span>
        <span class="card-value" v-if="contract_url"  @click="viewPDF()" style="margin-right:110px;color:blue">
          点击查看>
        </span>
      </li>

       <van-popup v-model="show" closeable position="left" :style="{ width: '100%', height: '100%' }">
            <iframe :src="contract_url" frameborder="0" style="width: 100%;height: 100%;"></iframe>
          </van-popup>
      <li style="line-height:80px;">
        <span class="card-title" style="height:80px" >付款凭证：</span>
         <template>
        <span class="card-value" v-if="credentials_url" @click="getImg(credentials_url)" style="margin-right:70px">
          <img :src="credentials_url"  />
        </span>
         <!-- <span class="card-value" v-if="credentials_url" style="margin-right:70px">
          <a :href="credentials_url" >点击下载</a>
        </span> -->
        </template>
      </li>
     
    </ul>
 
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { ImagePreview} from 'vant'

import { getPurchaseList } from '../../api/purchaselist'
import { getPurchaseInfo } from '../../api/purchaseInfo'


export default {
  name: '',
  data() {
    return {
      // 债权列表
      page: 1,
      loading: false,
      finished: false,
      error: false,
      show: false,
      code: '',
      is_orient: '', //是否定向
      time: 0,
      active: 0,
      discount: '',
      purchase_amount: '',
      real_name: '',
      bank_name: '',
      bank_card: '',
      transferprice: '',
      money: '',
      projectName: '',
      apr: '',
      project_end_time: '',
      style: '',
      guarantor: '',
      products:0,
      wait_capital:0,
      surplus_amount:0,
      scale:0,
      btn_diable:false,
      can_debt:false,
      purchaseList: [],
      status:0,
      credentials_url:'',
      contract_url:'',
      params: {
        id: 0,
        page: 0,
        limit: 10
      },
      form:{
        id:0,
      }
    }
  },
  created() {

    this.form.id = this.$route.query.id;
   
    this.getPurchaseDetail()

  },
   computed: {
    finishedText() {
      return ''
    },
    excludiveText() {
      return '同意收购'
    },
    fmtDiscount() {
      return this.utils.formatFloat(this.discount)+'折'
    },
   
     ...mapState({
      isOnline: state => state.auth.isOnline
    })
  },



  methods: {
      onLoad() {
        this.params.id  = this.$route.query.id;
        this.params.page  += 1;
          getPurchaseList(this.params).then(res => {
           
            if (res.data) {
      
              this.purchaseList = [...this.purchaseList, ...res.data]
            }
      
            this.loading = false // 加载状态结束
            //res.data当没有数据返回的是null，所以不能直接用res.data.data.length判断，会报错，导致finished无法置为true
            if ((res.data && res.data.length < this.params.limit) || !res.data) {
              this.finished = true // 数据全部加载完成
            }
          
          })
           this.$toast.clear()
      },

      getImg(images) {
       
        ImagePreview({
          images: [images]
        })
      },  
      viewPDF() {
       this.show = true
      },
     
       //项目详情接口
    getPurchaseDetail() {
        getPurchaseInfo(this.form).then(res => {
        if (res.code == 0) {
          let ct = Date.now()
          this.time = res.data.end_time * 1000 - ct
          this.wait_capital = res.data.wait_capital
          this.discount = res.data.discount
          this.purchase_amount = res.data.purchase_amount
          this.real_name = res.data.real_name
          this.bank_name = res.data.bank_name
          this.bank_card = res.data.bank_card
          this.credentials_url = res.data.credentials_url
          this.contract_url = res.data.contract_url
          this.status = res.data.status*1
         
        } else {
         
          // this.$toast(res.info)
          this.$router.go(-1)
        }
      })

    },
    sell() {
      // if(!this.isOnline){
      //     window.location.href = '/#/login?from=debtMarket'
      //     return
      // }
       this.$router.push({
        name: 'authSign',
        query: { id: this.$route.query.id,not_callback:1}
      })
     
    },
   
    
  },
  
}
</script>

<style lang="less" scoped>
 .link-top {
    width: 100%;
    height: 1px;
    border-top: solid #d5d8db 1px;
}
.main {
     @include inner;
     
      flex-direction: column;
      overflow: hidden;
     
      .list {
        height: 100%;
        height: 450px;
        margin-bottom: 80px;
       
        .item {
          // width: 350px;
          height: 110px;
          background: #fff;
          box-shadow: 0 2px 4px 0 rgba(238, 238, 238, 0.8);
          border-radius: 4px;
          margin: 10px auto 0;
          padding: 5px 20px 0 2px;
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
         
         }
        .van-list__finished-text {
          font-size: 12px;
          color: #9b9b9b;
        }
      }
    }
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
  .title{
     width: 100%;
    font-size: 16px;
    padding-bottom: 8px;
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
.list {
  flex: 1;
  overflow-y: auto;
}
img {
        width: 120px;
        height: 75px;
        display: inline-block;
        margin-right: 10px;
        vertical-align: text-top;
      }
</style>
