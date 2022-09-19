<template>
  <div>
    <div class="header">
      <div class="wrap">
        <div class="arrow" @click="goBack"><img src="../../static/images/back-arrow.png" alt=""></div>
        <div class="title">{{title}}</div>
      </div>
    </div>
    <div class="content">
      <div class="item" v-if="infoObj.is_zx_had == 1">
        <div class="top">
          <div class="name">尊享</div>
          <div class="top-right">
            <img v-if="zxAmount == 0" src="../../static/images/paidOff_icon.png" />
            <div class="all-amount">{{zxAmount | formatMoney}}</div>
          </div>
        </div>
        <div class="middle">
          <div class="dl-box dl-box-1">
            <div class="wrap">
              <dl>
                <dt>在途本金(元)</dt>
                <dd>{{infoObj.zx_wait_capital | formatMoney}}</dd>
              </dl>
              <dl>
                <dt>在途利息(元)</dt>
                <dd>{{infoObj.zx_wait_interest | formatMoney}}</dd>
              </dl>
            </div>
          </div>
          <div class="dl-box">
            <div class="wrap">
              <dl>
                <dt>账户余额(元)</dt>
                <dd>{{infoObj.zx_money | formatMoney}}</dd>
              </dl>
              <dl>
                <dt>冻结金额(元)</dt>
                <dd>{{infoObj.zx_lock_money | formatMoney}}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bottom" @click="goDetail(1)">
          <span>查看明细</span>
        </div>
      </div>
      <!--普惠 -->
      <div class="item item-hp" v-if="infoObj.is_ph_had == 1 || infoObj.is_zdx_had == 1">
        <div class="top">
          <div class="name">普惠</div>
          <div class="top-right">
            <img v-if="phAmount == 0" src="../../static/images/paidOff_icon.png" />
            <div class="all-amount">{{phAmount | formatMoney}}</div>
          </div>
        </div>
        <div class="middle">
          <div class="dl-box dl-box-line" @click="goDetail(2)">
            <div class="wrap">
              <div class="left">
                <dl>
                  <dt>[ 除智多新 ] 在途本金(元)</dt>
                  <dd>{{infoObj.ph_wait_capital | formatMoney}}</dd>
                </dl>
                <dl>
                  <dt>[ 除智多新 ] 在途利息(元)</dt>
                  <dd>{{infoObj.ph_wait_interest | formatMoney}}</dd>
                </dl>
              </div>
              <div class="arrow-box right">
                <img src="../../static/images/common-arrow.png" />
              </div>
            </div>
          </div>
          <div class="dl-box dl-box-line" @click="goDetail(4)">
            <div class="wrap">
              <div class="left">
                <dl class="bgflex">
                  <dt class="opcitybg">[ 智多新 ] 在途本金(元)</dt>
                  <dd>{{infoObj.zdx_wait_capital | formatMoney}}</dd>
                </dl>
                <dl></dl>
              </div>
              <!-- <dl>
                  <dt>[ 智多新 ] 在途利息(元)</dt>
                  <dd>{{infoObj.zdx_wait_interest | formatMoney}}</dd>
                </dl> -->
              <div class="arrow-box">
                <img src="../../static/images/common-arrow.png" />
              </div>
            </div>
          </div>
          <div class="dl-box1">
            <div class="wrap">
              <div class="left">
                <dl>
                  <dt>账户余额</dt>
                  <dd>{{infoObj.ph_money | formatMoney}}</dd>
                </dl>
                <dl>
                  <dt>&nbsp;/&nbsp;</dt>
                  <dd>&nbsp;/&nbsp;</dd>
                </dl>
                <dl>
                  <dt class="opcitybg">其中智多新待加入金额(元)</dt>
                  <dd>{{infoObj.zdx_money | formatMoney}}</dd>
                </dl>
              </div>
              <dl>
                <dt>冻结金额(元)</dt>
                <dd>{{(infoObj.ph_lock_money+infoObj.zdx_lock_money) | formatMoney}}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      <!--金融工场 -->
      <div class="item" v-if="infoObj.is_jrgc_had == 1">
        <div class="top">
          <div class="name">工场微金</div>
          <div class="top-right">
            <img v-if="gcwjAmout==0" src="../../static/images/paidOff_icon.png" />
            <div class="all-amount">{{gcwjAmout | formatMoney}}</div>
          </div>
        </div>
        <div class="middle">
          <div class="dl-box dl-box-1">
            <div class="wrap">
              <dl>
                <dt>在途本金(元)</dt>
                <dd>{{infoObj.jrgc_wait_capital | formatMoney}}</dd>
              </dl>
              <dl>
                <dt>在途利息(元)</dt>
                <dd>{{infoObj.jrgc_wait_interest | formatMoney}}</dd>
              </dl>
            </div>
          </div>
          <div class="dl-box ">
            <div class="wrap">
              <dl>
                <dt>账户余额(元)</dt>
                <dd>{{infoObj.jrgc_money | formatMoney}}</dd>
              </dl>
              <dl>
                <dt>冻结金额(元)</dt>
                <dd>{{infoObj.jrgc_lock_money | formatMoney}}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bottom" @click="goDetail(3)">
          <span>查看明细</span>
        </div>
      </div>
      <!--交易所 -->
      <div class="item" v-if="infoObj.is_jys_had == 1">
        <div class="top">
          <div class="name">交易所</div>
          <div class="top-right">
            <img v-if="jysAmout==0" src="../../static/images/paidOff_icon.png" />
            <div class="all-amount">{{jysAmout | formatMoney}}</div>
          </div>
        </div>
        <div class="middle">
          <div class="dl-box dl-box-1">
            <div class="wrap">
              <dl>
                <dt>在途本金(元)</dt>
                <dd>{{infoObj.jys_wait_capital | formatMoney}}</dd>
              </dl>
              <dl>
                <dt>在途利息(元)</dt>
                <dd>{{infoObj.jys_wait_interest | formatMoney}}</dd>
              </dl>
            </div>
          </div>
          <div class="dl-box ">
            
          </div>
        </div>
        <div class="bottom" @click="goDetail(5)">
          <span>查看明细</span>
        </div>
      </div>
    </div>
    <div class="content" v-if="infoObj.is_zx_had != 1 && infoObj.is_ph_had != 1 &&infoObj.is_jrgc_had != 1 &&infoObj.is_zdx_had != 1">
      <null-data></null-data>
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
        title: '资产中心',
        infoObj: {},
      }
    },
    created() {
      this.getProperty();
    },
    computed:{
      zxAmount(){
        return Number(this.infoObj.zx_wait_capital)+Number(this.infoObj.zx_wait_interest)+Number(this.infoObj.zx_money)+Number(this.infoObj.zx_lock_money)
      },
      phAmount(){
        return Number(this.infoObj.ph_wait_capital)+Number(this.infoObj.ph_wait_interest)+Number(this.infoObj.ph_money)+Number(this.infoObj.ph_lock_money)+Number(this.infoObj.zdx_wait_capital)+Number(this.infoObj.zdx_wait_interest)+Number(this.infoObj.zdx_lock_money)
      },
      gcwjAmout(){
        return Number(this.infoObj.jrgc_wait_capital)+Number(this.infoObj.jrgc_wait_interest)+Number(this.infoObj.jrgc_money)+Number(this.infoObj.jrgc_lock_money)
      },
      jysAmout(){
        return Number(this.infoObj.jys_wait_capital)+Number(this.infoObj.jys_wait_interest)
      }
    },
    methods: {
      getProperty() {
        getPropertyRequest().then(res => {
          if (res.code === 0) {
            console.log(res);
            this.infoObj = res.data;
          }
        })
      },

      //type	1	int	 所属平台(默认1) 1尊享 2普惠 3金融工场（工场微金） 4智多新 5交易所
      goDetail(type) {
        this.$router.push({
          path: "/PropertyComposeDetail",
          query: {
            type: type
          }
        });
      },
      goBack() {
        this.$router.replace({
          name: "home"
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

    .item:nth-child(4n-3) {
      .top {
        .name {
          background: rgba(91, 87, 228, 1);
        }

        .all-amount {
          color: rgba(91, 87, 228, 1);
        }

      }
    }

    .item:nth-child(4n-2) {
      .top {

        .name {
          background: rgba(235, 93, 76, 1);
        }

        .all-amount {
          color: rgba(235, 93, 76, 1);
        }
      }
    }

    .item:nth-child(4n-1) {
      .top {

        .name {
          background: rgba(4, 177, 164, 1);
        }

        .all-amount {
          color: rgba(4, 177, 164, 1);
        }
      }
    }

    .item:nth-child(4n) {
      .top {

        .name {
          background: rgba(213, 143, 45, 1);
        }

        .all-amount {
          color: rgba(213, 143, 45, 1);
        }
      }
    }

    .item {
      margin-bottom: 20px;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0px 1px 8px 0px rgba(53, 116, 250, 0.15);
      border-radius: 4px;

      .top {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 22px 0 7px 0;
        margin: 0 15px 0 0;

        .name {
          text-align: center;
          min-width: 91px;
          font-size: 17px;
          font-family: PingFangSC-Medium;
          color: rgba(255, 255, 255, 1);
          height: 28px;
          line-height: 28px;
          border-radius: 0px 0px 30px 0px;
          padding: 3px 14px 3px 7px;
        }

        .all-amount {
          text-align: right;
          flex: 1;
          font-size: 22px;
          font-family: PingFangSC-Medium;
          color: rgba(255, 255, 255, 1);
        }
      }

      .middle {
        display: flex;
        flex-direction: column;
        align-items: center;

        .dl-box-line {
          border-bottom: 1px dashed rgba(0, 0, 0, 0.15);
        }

        .dl-box {
          width: 100%;
          padding: 13px 0 15px 0;

          .wrap {
            padding: 0 15px;
            display: flex;
            flex-direction: row;
            justify-content: space-between;

            dl:last-child {
              text-align: right;
              border-bottom: 0;
            }

            dl {
              flex: 1;

              dt {
                font-size: 12px;
                font-family: PingFangSC-Light;
                color: #666666;
              }

              dd {
                margin: 0;
                padding-top: 5px;
                font-size: 16px;
                font-family: PingFangSC-Regular;
                color: #666666;
                width: 100%;
              }
            }
          }

        }

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

    .item-hp {
      .middle {
        .dl-box {
          .wrap {
            display: flex;
            flex-direction: row;
            align-items: center;

            .left {
              display: flex;
              flex-direction: row;
              align-items: center;
              flex: 1;
            }

            .arrow-box {
              display: flex;
              align-items: center;
              margin-left: 13px;
              height: 100%;

              img {
                width: 6px;
                height: 11px;
                vertical-align: middle;
              }
            }
          }
        }

        .dl-box1 {
          width: 100%;
          padding: 13px 0 15px 0;

          .wrap {
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 0 15px;

            dl {
              dt {
                font-size: 10px;
                font-family: PingFangSC-Light;
                color: rgba(112, 112, 112, 1);
              }

              dd {
                margin: 0;
                padding-top: 5px;
                font-size: 15px;
                font-family: PingFangSC-Regular;
                color: rgba(112, 112, 112, 1);
                width: 100%;
              }
            }

            .left {
              display: flex;
              flex-direction: row;
              align-items: center;
              flex: 1;

            }

            .arrow-box {
              display: flex;
              align-items: center;
              margin-left: 13px;
              height: 100%;

              img {
                width: 6px;
                height: 11px;
                vertical-align: middle;
              }
            }
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
  .dl-box-1{
    padding-bottom: 0!important;
  }
</style>
