<template>
  <div>
    <div class="header">
      <div class="wrap">
        <div class="arrow" @click="goBack">
          <!-- <img src="../../static/images/back-arrow.png" alt=""> -->
          </div>
        <div class="title">{{title}}</div>
      </div>
    </div>
    <div class="content">
      <div class="item" >
        <div class="top" >
          <div class="name">步骤一：实名认证</div>
          <div class="arrow-box">
          </div>
        </div>
        
        <div class="bottom" >
           <div class="middle">
             <p>为保证您的资金及信息安全，请点击前往“法大大”完成实名认证。  </p>    
           </div>
           <div class="btn" @click="goDetail(1)">
                <van-button :class="{ disabled: is_auth || !can_auth }" color="rgba(252, 129, 12, 1)" type="primary" >{{ showAuth }}</van-button>
           </div>
        </div>
       
        
      </div>

      <div class="item" >
        <div class="top">
          <div class="name">步骤二：线上签约</div>
         
        </div>
        <div class="bottom" >
          <div class="middle">
           <p>点击前往“法大大”平台完成签约，签约后受让人会在“一个工作日”内将款项打至您的收款账户。</p>
          </div>
          <div class="btn"  @click="goDetail(2)">
                <van-button :class="{ disabled: is_sign || !is_auth || !can_sign}" color="rgba(252, 129, 12, 1)" type="primary" >{{ showSign }}</van-button>
           </div>
        </div>
      </div>

      <div class="top" v-if="is_auth && is_sign" >
        <p>您已完成债转协议签署，请等待受让人付款；受让人将于“一个工作日”内付款，否则交易取消。</p>

      </div>
    </div>
   
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { userFddInfo } from '../../api/userFddInfo'
import { FddApi } from '../../api/fddApi'
  export default {

    data() {
      return {
        title: '功能列表',
        is_auth:false,
        is_sign:false,
        can_sign:false,
        can_auth:false,
        fdd_real_url:'',
        sign_contract_url:'',
        infoObj: {},
        form:{
          id:0
        }
      }
    },

     computed: {
  
    showAuth() {
      return this.is_auth ? "已完成":'点击认证';
    },
    showSign() {
      return this.is_sign ? "已完成":'点击签约';
    },
     ...mapState({
      isOnline: state => state.auth.isOnline
    })
  },
    created() {

      if(this.$route.query.viewpdf_url){
        this.$route.query.viewpdf_url = encodeURIComponent(this.$route.query.viewpdf_url);
      }
   
    this.form.id = this.$route.query.id;
    //浏览器全部参数
   
    if(this.$route.query.not_callback != 1){
      this.FddApi();
    }else{
      this.getUserFddInfo()
    }
    

  },
    methods: {

       getUserFddInfo() {
        userFddInfo(this.form).then(res => {
        if (res.code == 0) {
         
          this.sign_contract_url = res.data.sign_contract_url
          this.fdd_real_url = res.data.fdd_real_url
          this.is_sign = res.data.sign_contract_status == '1'
          this.is_auth = res.data.fdd_real_status == '1'
          this.can_sign = true;
          this.can_auth = true;
        } else {
         this.is_auth = res.data.fdd_real_status == '1'
          // this.is_sign = res.data.sign_contract_status == '1'
          this.$toast(res.info)
         // this.$router.go(-1)
        }
      })

    },
       FddApi() {
        FddApi(this.$route.query).then(res => {
        if (res.code == 0) {
          this.is_auth = res.data.fdd_real_status == '1'
          this.is_sign = res.data.sign_contract_status == '1'
         
        } else {
          
           this.$toast(res.info)
         // this.$router.go(-1)
        }
         this.getUserFddInfo();

      })

    },

      //type
      goDetail(type) {
        
        if(type==1){
          if(this.is_auth || !this.can_auth){
            console.log(type,77);
              return;
          }
          window.location.href = this.fdd_real_url 
          return
        }else if(type==2){
          if(!this.is_auth || !this.can_sign){
             console.log(type,88);
              return;
          }
           window.location.href = this.sign_contract_url 
          return
        }
       
      },
      goBack() {
        // this.$router.go(-1)
        this.$router.replace({
          name: "exclusiveDetails"
        })
      },
     
    }
  }
</script>

<style lang="less" scoped>
  .top-right{
    display: flex;
    flex-direction: row;
    align-items: center;
    img{
      width: 56px;
      height: 15px;
      margin-right: 5px;
    }
  }
  .bgflex {
    flex: none!important;
    width: 130px;
  }

  .opcitybg {
    text-align: center;
    background: rgba(57, 52, 223, 0.06);
    border-radius: 13px;
    padding: 0 3px;
    display: flex;
    flex-wrap: nowrap;
  }

  .content {
    margin: 20px 14px;

    .item {
      margin-bottom: 20px;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0px 1px 8px 0px rgba(53, 116, 250, 0.15);
      border-radius: 4px;

      .top {
        display: flex;
        
        justify-content: space-between;
        align-items: center;
        padding: 10px 0 10px 10px;
        margin: 0 0px 0 0;
        .arrow-box {
          display: flex;
          align-items: center;
          width: 30px;
          height: 30px;
          img {
            width: 8px;
            height: 14px;
          }
        }
        .name {
          text-align: center;
          min-width: 91px;
          font-size: 17px;
           color: rgba(51, 51, 51, 1);
          font-family: PingFangSC-Medium;
          height: 28px;
          line-height: 28px;
          border-radius: 0px 0px 30px 0px;
          padding: 3px 14px 3px 7px;
        }

      
      }

      .middle {
        display: flex;
        color: rgb(78, 77, 77);
        justify-content: space-between;
        align-items: left;
        font-size: 14px;
        padding: 2px 20px 2px 20px;
        p {  text-align: left;  display: inline-block  }
      }

      .bottom {
        font-size: 12px;
        font-family: PingFangSC-Regular;
        color: rgba(51, 51, 51, 1);
        padding: 14px 0;
        text-align: right;
        border-top: 0.5px dashed #00000026;

        span {
          display: inline-block;
          margin: 0 15px;
        }
      }
      .btn {
        width: 140px;
        margin: 30px auto 5px;
        .van-button {
        width: 140px;
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

  
  }

  .header {
    height: 36px;
    line-height: 36px;
    border-bottom: 1px solid #F4F4F4;
    background-color: #ffffff;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;

    .wrap {
      padding: 0 9px;
      display: flex;
      flex-direction: row;
      align-items: center;

      .arrow {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;

        img {
          width: 100%;
          height: 100%;
        }
      }

      .title {
        text-align: center;
        font-size: 18px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(51, 51, 51, 1);
        flex: 1;
        padding-right: 30px;
      }
    }

  }
  .dl-box-1{
    padding-bottom: 0!important;
  }
</style>
