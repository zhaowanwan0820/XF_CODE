<template>
  <div>
    <div class="header">
      <div class="wrap">
        <div class="arrow" @click="goBack"><img src="../../static/images/back-arrow.png" alt=""></div>
        <div class="title">{{title}}</div>
      </div>
    </div>
    <div class="content">
      <div class="item" >
        <div class="top" @click="goDetail(1)">
          <div class="name">修改绑定手机号</div>
          <div class="arrow-box">
            <img src="../../static/images/common-arrow.png" alt />
          </div>
        </div>
        
        <div class="bottom" >
           <div class="middle">
            1、面向已绑定手机号，而手机号不可用的用户。  
          </div>
           <div class="middle">
            2、支持原网信(尊享、普惠、智多新)注册用户。
          </div>
           <div class="middle">         
            3、支持原工场微金注册用户。
          </div>
           <div class="middle">
            4、支持原交易所用户。
          </div>
        </div>
      </div>

      <!-- <div class="item" >
        <div class="top" @click="goDetail(2)">
          <div class="name">交易所用户绑定手机号</div>
          <div class="arrow-box">
            <img src="../../static/images/common-arrow.png" alt />
          </div>
        </div>
        <div class="bottom" >
          <div class="middle">
           1、面向可提供证件号且手机号未绑定过的用户。
          </div>
          <div class="middle">
            2、仅支持原交易所用户。
          </div>
        </div>
      </div> -->
    </div>
   
  </div>
</template>

<script>
  import commonHeader from "@/components/CommonHeader.vue";
  import {
    getPropertyRequest
  } from '../../api/propertyCompose.js'
  export default {
    components: {
      commonHeader
    },
    data() {
      return {
        title: '功能列表',
        infoObj: {},
      }
    },
    created() {
      document.getElementsByTagName("html")[0].style.backgroundColor = "#fff";
      //this.getProperty();
    },
    
    methods: {
      getProperty() {
        // getPropertyRequest().then(res => {
        //   if (res.code === 0) {
        //     console.log(res);
        //     this.infoObj = res.data;
        //   }
        // })
      },

      //type	1	int	 所属平台(默认1) 1尊享 2普惠 3金融工场（工场微金） 4智多新 5交易所
      goDetail(type) {
        if(type==1){
          this.$router.push({
            path: "/editPhoneNum",
            
          });
        }else if(type==2){
           this.$router.push({
            path: "/jysAuthReg",
            
          });
        }
       
      },
      goBack() {
        this.$router.replace({
          name: "login"
        })
      }
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
    margin: 59px 14px;

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
        align-items: center;
        padding: 2px 0 2px 20px;
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
