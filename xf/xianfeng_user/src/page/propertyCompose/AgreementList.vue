<template>
  <div>
     <div class="header">
       <div class="wrap">
         <div class="arrow" @click="goBack"><img src="../../static/images/back-arrow.png" alt=""></div>
         <div class="title">{{title}}</div>
       </div>
     </div>
     <div class="item-box">
       <div class="item" v-for="(val,index) in agreementList" :key='index'>
         <div class="name">{{val.title}}</div>
         <div class="btn" @click="checkDetail(val.url)">
           <div>查看</div>
           <div class="arrow-box">
             <img src="../../static/images/common-arrow.png" alt="">
           </div>
         </div>
       </div>
     </div>
     <!-- pdfjs-->
     <div class="iframe-wrap" v-show="showPdf">
       <div class="header">
         <div class="wrap">
           <div class="arrow" @click="closePdf"><img src="../../static/images/close-btn.png" alt=""></div>
           <div class="title">合同详情</div>
         </div>
       </div>
       <iframe :src="pdfSrc" frameborder="0" width="100%" height="100%"></iframe>
     </div>
  </div>
</template>

<script>
  import commonHeader from "@/components/CommonHeader.vue";
  import {getAgreementRequest,getAgreementDetailRequest} from '../../api/propertyCompose.js'
  export default{
    components:{
      commonHeader
    },
    data(){
      return{
        title:'合同和协议',
        params:{
          id:'',//投资记录ID
          platform_id:''//平台ID
        },
        agreementList:[],
        pdfSrc: '',
        showPdf:false
      }
    },
    mounted() {
      document.querySelector('body').setAttribute('style', 'background-color:#F4F4F4')
      this.init()
    },
    beforeDestroy() {
      document.querySelector('body').removeAttribute('style')
    },
    methods:{
      init(){
        this.params.id = this.$route.query.id;
        this.params.platform_id = this.$route.query.platform_id;
        getAgreementRequest(this.params).then(res=>{
          console.log(res);
          if(res.code === 0){
            this.agreementList = res.data;
          }
        })
      },
      goBack(){
        this.$router.push({
          path:'/lendingDetails',
          query:{
            id:this.params.id,
            platform_id:this.params.platform_id
          }
        })
      },
      checkDetail(url){
        console.log(url)
        getAgreementDetailRequest(url).then(res => {
          this.showPdf = true
          console.log(res);
          let url = window.URL.createObjectURL(new Blob([res]))
          this.pdfSrc = '../../../plug/pdf/web/viewer.html?file=' + encodeURIComponent(url)
        })
      },
      closePdf(){
        this.showPdf = false,
        this.pdfSrc = ''
      }
    }
  }
</script>

<style lang="less" scoped>
  .iframe-wrap {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    bottom: 0;
    z-index: 200;
    iframe{
      margin-top: 38px;
    }
  }
  .item-box{
    margin-top: 46px;
    background: #fff;
    .item:first-child{
      border: none;
    }
    .item{
      display: flex;
      flex-direction: row;
      align-items: center;
      height: 60px;
      padding: 0 15px;
      border-top: 1px solid #F4F4F4;

      .name{
        height:21px;
        font-size:15px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:400;
        color:rgba(51,51,51,1);
        flex: 1;
      }
      .btn{
        height:21px;
        font-size:15px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:400;
        color:rgba(51,51,0,1);
        display: flex;
        flex-direction: row;
        align-items: center;
        .arrow-box {
          display: flex;
          align-items: center;
          width: 6px;
          height: 11px;
          margin-left: 10px;
          img {
            width: 100%;
            height: 100%;
          }
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
</style>
