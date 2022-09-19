<template>
  <div>
    <div class="header">
      <div class="wrap">
        <div class="arrow" @click="goBack">
          <img src="../../static/images/back-arrow.png" alt="" />
        </div>
        <div class="title">{{ title }}</div>
      </div>
    </div>
    <div class="wrap">
      <div class="top">
        <div class="state state-ing" v-if="loanObj.status == 1">还款中</div>
        <div class="state state-ed" v-if="loanObj.status == 0 || loanObj.status == 2">已结清</div>
        <div class="state state-wait" v-if="loanObj.status == 3">待确认</div>

        <div class="deal-name">{{ loanObj.deal_name }}</div>
      </div>
      <div class="amount-box">
        <div class="top" v-if="params.platform_id != 4">
          <div class="left">
            <div class="label">在途本金(元)</div>
            <div class="amount">{{ loanObj.wait_capital | formatMoney }}</div>
          </div>
          <div class="right">
            <div class="label">在途利息(元)</div>
            <div class="amount">{{ loanObj.wait_interest | formatMoney }}</div>
          </div>
        </div>
        <div class="top-1" v-else>
          <div class="left">
            <div class="label">在途本金(元)</div>
            <div class="amount">{{ loanObj.wait_capital | formatMoney }}</div>
          </div>
        </div>
        <div class="bottom" v-if="params.platform_id == 5">
          <div class="label">投资金额(元)</div>
          <div class="amount">{{ loanObj.money | formatMoney }}</div>
        </div>
        <div class="bottom" v-else>
          <div class="label">原始出借金额(元)</div>
          <div class="amount">{{ loanObj.money | formatMoney }}</div>
        </div>
       
      </div>
      <div class="contracts-box" v-if="params.platform_id != 5">
        <div class="wrap" @click="goAgreement">
          <div class="icon"><img src="../../static/images/hetong-icon.png" alt=""></div>
          <div class="name">合同和协议</div>
          <div class="btn">
            <div>查看</div>
            <div class="arrow"><img src="../../static/images/common-arrow.png" alt=""></div>
          </div>
        </div>
      </div>

      <div class="voucher-box" v-if='params.platform_id == 5'>
        <div class="submit-status status0" v-if="voucherObj.status==0">未上传</div>
        <div class="submit-status status1" v-if="voucherObj.status==1">审核中</div>
        <div class="submit-status status2" v-if="voucherObj.status==2">审核成功</div>
        <div class="submit-status status3" v-if="voucherObj.status==3">审核失败</div>
        <span class="voucher-tip">合同及交易凭证</span>
        <div class="arrow-box" @click="goVoucher">
            <span class="arrow-text" v-if="voucherObj.status==0||voucherObj.status==3">上传</span>
            <span class="arrow-text" v-if="voucherObj.status==1||voucherObj.status==2">查看</span>
            <img src="../../static/images/common-arrow.png" alt="">
          </div>
      </div>
      
      <div class="item-box">
       

        <div class="item" v-show=" params.platform_id == 1 &&  loanObj.jys_record_number != ''">
           <div class="left">
             <div class="label">交易所产品名称</div>
               <div class="amount">{{ loanObj.jys_record_number }}</div>
          </div>
        </div>
        <div class="item" v-if="params.platform_id == 1 || params.platform_id == 2 || params.platform_id == 3">
          <div class="left">
            <div class="label">年化借款利率%</div>
            <div class="amount">{{ loanObj.rate | formatMoney }}</div>
          </div>
          <div class="right">
            <div class="label">期限</div>
            <div class="amount">{{ loanObj.repay_time }}</div>
          </div>
        </div>
        <div class="item" v-else-if="params.platform_id == 5">
          <div class="left" v-if="loanObj.max_rate > loanObj.rate">
            <div class="label">利率区间%</div>
            <div class="amount">{{ loanObj.rate | formatMoney }}~{{ loanObj.max_rate | formatMoney }}</div>
          </div>
          <div class="left" v-else>
            <div class="label">年利率%</div>
            <div class="amount">{{ loanObj.rate | formatMoney }}</div>
          </div>
          <div class="right">
            <div class="label">期限</div>
            <div class="amount">{{ loanObj.repay_time }}</div>
          </div>
        </div>

         <div class="item" v-if="params.platform_id == 5">
          <div class="left">
            <div class="label">起息日</div>
            <div class="amount">{{ loanObj.create_time | convertTime2 }}</div>
          </div>
          <div class="right">
            <div class="label">到期时间</div>
            <div class="amount">{{ loanObj.repayment_date| convertTime2 }}</div>
          </div>
        </div>

        <div class="item">
          <div class="left" v-if="params.platform_id != 4">
            <div class="label">还款方式</div>
            <div class="amount">{{ loanObj.loantype }}</div>
          </div>
          <div class="right" v-if="params.platform_id != 5 ">
            <div class="label">出借时间</div>
            <div class="amount">{{ loanObj.create_time | convertTime2 }}</div>
          </div>
          
        </div>
        <div class="item" v-if="params.platform_id == 1 || params.platform_id == 2 || params.platform_id == 3">
          <div class="left">
            <div class="label">借款人</div>
            <div class="amount">{{ loanObj.company_name }}</div>
          </div>
        </div>
        <div class="item" v-else-if="params.platform_id == 5">
          <div class="left">
            <div class="label">发行人/融资方简称</div>
            <div class="amount">{{ loanObj.company_name }}</div>
          </div>
        </div>
      </div>
    </div>
    <div class="fundsUse" v-if="params.platform_id == 5">
      <div class="label">登记备案场所</div>
      <div class="info">{{ loanObj.registration_address }}</div>
    </div>
    <div class="fundsUse" v-else>
       <div class="label">项目资金用途情况</div>
      <div class="info">借款人已按照既定的资金用途使用资金</div>
    </div>
    <!-- 非交易所产品使用 pdfjs-->
    <div class="iframe-wrap" v-show="showPdf">
      <div class="header">
        <div class="wrap">
          <div class="arrow" @click="closePdf">
            <img src="../../static/images/close-btn.png" alt="" />
          </div>
          <div class="title">合同详情</div>
        </div>
      </div>
      <iframe :src="pdfSrc" frameborder="0" width="100%" height="100%"></iframe>
    </div>
  </div>
</template>

<script>
import commonHeader from "@/components/CommonHeader.vue";
import {
  getLoanDetailRequest,
  getAgreementRequest,
  getAgreementDetailRequest,
  getDealLoadAuditInfoRequest
} from "../../api/propertyCompose.js";
import { Toast } from "vant";
export default {
  components: {
    commonHeader
  },
  data() {
    return {
      title: "",
      params: {
        id: "", //投资记录ID
        platform_id: "" //平台ID
      },
      loanObj: {},
      pdfSrc: "",
      showPdf: false,
      voucherObj:{},
    };
  },
  mounted() {
    document.getElementsByTagName("html")[0].style.backgroundColor = "#fff";
    this.init();
    this.loanDetail();
    if(this.params.platform_id == 5){
      this.getDealLoadAuditInfo();
    }
   
  },
  methods: {
    init() {
      this.params.id = this.$route.query.id;
      this.params.platform_id = this.$route.query.platform_id*1;
      if (this.params.platform_id == 1) {
        this.title = "尊享出借详情";
      } else if (this.params.platform_id == 2) {
        this.title = "普惠出借详情";
      } else if (this.params.platform_id == 3) {
        this.title = "工场微金出借详情";
      } else if (this.params.platform_id == 4) {
        this.title = "智多新出借详情";
      } else if (this.params.platform_id == 5) {
        this.title = "交易所投资详情";
      }
    },
    goVoucher(){
      this.$router.push({
          path:'/voucherDetail',
          query:{
            id: this.params.id,
            platform_id: this.params.platform_id
          }
        })
    },
    //跳转去协议列表页
    goAgreement() {
      getAgreementRequest(this.params).then(res => {
        console.log(res);
        if (res.code === 0) {
          if (res.data.length == 1) {
            this.showPdf = true;
            let url = res.data[0].url;
            getAgreementDetailRequest(url).then(res => {
              // console.log(res);
              let url = window.URL.createObjectURL(new Blob([res]));
              this.pdfSrc =
                "../../../plug/pdf/web/viewer.html?file=" +
                encodeURIComponent(url);
            });
          } else if (res.data.length > 1) {
            this.$router.push({
              path: "/agreementList",
              query: {
                id: this.params.id,
                platform_id: this.params.platform_id
              }
            });
          } else {
            Toast("暂无可查看的合同");
          }
        } else {
          Toast(res.info);
        }
      });
    },
    loanDetail() {
      getLoanDetailRequest(this.params).then(res => {
        console.log(res);
        if (res.code === 0) {
          this.loanObj = res.data;
        }
      });
    },
    getDealLoadAuditInfo(){
       getDealLoadAuditInfoRequest(this.params).then(res => {
        if (res.code === 0) {
          this.voucherObj = res.data;
        }
      });
    },
    goBack() {
      this.$router.push({
        path: "/PropertyComposeDetail",
        query: {
          type: this.params.platform_id
        }
      });
    },
    closePdf() {
      (this.showPdf = false), (this.pdfSrc = "");
    }
  }
};
</script>

<style lang="less" scoped>
.iframe-wrap {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  bottom: 0;
  z-index: 200;
  iframe {
    margin-top: 38px;
  }
}

.wrap {
  padding: 0 15px;

  .top {
    padding-top: 36px;
    display: flex;
    flex-direction: row;
    align-items: center;

    .state {
      text-align: center;
      margin: 14px 10px 15px 0;
      width: 63px;
      height: 22px;
      line-height: 22px;
      border-radius: 16px;
      font-size: 13px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(255, 255, 255, 1);
    }

    .state-ing{
      background:rgba(44,196,65);
    }
    .state-wait{
      background:rgba(254,128,13,1)
    }
    .state-ed{
      background:rgba(234,87,73,1)
    }

    .deal-name {
      font-size: 15px;
      font-family: PingFangSC-Medium, PingFang SC;
      font-weight: 500;
      color: rgba(64, 64, 64, 1);
    }
  }

  .amount-box {
    width: 100%;
    background: rgba(37, 46, 75, 1);
    box-shadow: 0px 2px 10px 0px rgba(207, 207, 207, 1);
    border-radius: 2px;
    margin-bottom: 20px;

    .label {
      height: 21px;
      font-size: 15px;
      font-family: PingFangSC-Light, PingFang SC;
      font-weight: 300;
      color: rgba(255, 255, 255, 0.45);
      line-height: 21px;
    }

    .amount {
      height: 25px;
      font-size: 18px;
      font-family: PingFangSC-Medium, PingFang SC;
      font-weight: 500;
      color: rgba(255, 255, 255, 1);
      line-height: 25px;
    }

    .top {
      padding: 25px 15px 21px 15px;
      display: flex;
      flex-direction: row;
      align-items: center;
      // text-align: left;
      justify-content: center;

      .amount {
        margin-top: 10px;
      }

      .left {
        flex: 1;
      }

      .right {
        flex: 1;
      }
    }

    .top-1 {
      padding: 25px 15px 21px 15px;
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: center;

      .amount {
        margin-top: 10px;
      }

      .left {
        text-align: center;
      }
    }

    .bottom {
      border-top: 0.5px dashed rgba(190, 190, 190, 0.5);
      padding: 16px 15px;
      display: flex;
      flex-direction: row;
      align-items: center;

      .amount {
        margin-left: 10px;
      }
    }
  }

  .contracts-box {
    margin: 0 0 30px 0;
    height: 52px;
    background: rgba(255, 255, 255, 1);
    box-shadow: 0px 2px 10px 0px rgba(231, 231, 231, 1);

    .wrap {
      padding: 0 10px;
      display: flex;
      flex-direction: row;
      align-items: center;
      height: 100%;

      .icon {
        width: 22px;
        height: 19px;

        img {
          width: 100%;
          height: 100%;
        }
      }

      .name {
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(112, 112, 112, 1);
        flex: 1;
        margin-left: 5px;
      }

      .btn {
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(51, 51, 0, 1);
        display: flex;
        flex-direction: row;
        align-items: center;

        .arrow {
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
  .voucher-box{
    margin: 12.5px 0px 17.5px 0px;
    // background: #f0fff0;
    height: 40px;
    line-height: 40px;
    // border-radius: 40px;
    box-shadow: 5px 5px 5px rgba(0,0,0,0.1);
    overflow: hidden;
    display:flex;

    .submit-status{
      width: 75px;
      height: 40px;
      display: flex;
      // border-radius: 40px;
      justify-content: center;
      color: #fff;
      line-height: 40px;
      font-size: 14px;
      font-weight: bold;
    }
    .status0{
      background: #ff993b;
    }
    .status1{
      background: #3eb3eb;
    }
    .status2{
      background: #47dd7c;
    }
    .status3{
      background: #ff4b0c;
    }

    .voucher-tip{
      display: flex;
      font-size: 13px;
      height: 40px;
      line-height: 40px;
      color: #bbb;
      margin-left: 15px;
    }
    .arrow-box{
      margin-left: 25vw;
      color: #bbb;
      font-size: 13px;
      vertical-align: middle;
      margin-right: 15px;

      img{
        width:9px;
        height: 15px;
        margin-left: 10px;
        vertical-align: middle;
        margin-top: -2px;
      }
    }

  }
  .item-box {
    .item {
      display: flex;
      flex-direction: row;
      align-items: center;
      padding: 0 10px;
      margin-bottom: 25px;

      .label {
        height: 18px;
        font-size: 13px;
        font-family: PingFangSC-Light, PingFang SC;
        font-weight: 300;
        color: rgba(153, 153, 153, 1);
        line-height: 18px;
        margin-bottom: 8px;
      }

      .info {
        height: 18px;
        font-size: 13px;
        font-family: PingFangSC-Medium, PingFang SC;
        font-weight: 500;
        color: rgba(64, 64, 64, 1);
        line-height: 18px;
      }

      .left,
      .right {
        display: felx;
        flex-direction: column;
        align-items: center;
        flex: 1;
      }
    }
  }
}

.fundsUse {
  margin: 20px 0 20px 15px;
  border-top: 0.5px dashed rgba(190, 190, 190, 0.5);

  .label {
    height: 18px;
    font-size: 13px;
    font-family: PingFangSC-Light, PingFang SC;
    font-weight: 300;
    color: rgba(153, 153, 153, 1);
    line-height: 18px;
    padding: 20px 10px 0 10px;
  }

  .info {
    height: 18px;
    font-size: 13px;
    font-family: PingFangSC-Medium, PingFang SC;
    font-weight: 500;
    color: rgba(64, 64, 64, 1);
    line-height: 18px;
    padding: 8px 10px 0 10px;
  }
}

.header {
  height: 36px;
  line-height: 36px;
  border-bottom: 1px solid #f4f4f4;
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
