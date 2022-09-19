<template>
  <div v-if="red_cash_back" class="return-cash-popup-wrapper">
    <mt-popup v-model="popupVisible" position="center">
      <div class="return-cash-pop">
        <div class="head">
          <img src="../../assets/image/hh-icon/icon-close-white-circle.png" alt="" @click="close" />
        </div>
        <div class="body" :class="{ 'no-back': red_cash_back === 0 }">
          <template v-if="red_cash_back > 0">
            <div class="money">
              <span class="unit">￥</span>
              <span class="count">{{ red_cash_back }}</span>
            </div>
            <div class="btn" @click="goProfile">
              立即查看
            </div>
          </template>
          <template v-else>
            <div class="top">很遗憾<br />本次活动金额已抢光~~</div>
          </template>
          <div class="bottom">
            <template v-if="red_cash_back > 0">
              现金红包已存入您的账户<br />请前去{{
                isHHApp ? '个人中心账户余额中查看' : `下载${utils.storeName}app领取`
              }}
            </template>
            <template v-else>
              <template v-if="isHHApp">
                请前往首页参与其他活动~
              </template>
              <template v-else>
                更多下单返现活动<br />请前去下载{{ utils.storeName }}app参与
              </template>
            </template>
          </div>
        </div>
      </div>
    </mt-popup>
  </div>
</template>

<script>
export default {
  name: 'ReturnCashPopup',
  data() {
    return {
      popupVisible: true
    }
  },

  props: ['red_cash_back'],

  methods: {
    close() {
      this.popupVisible = false
    },

    goProfile() {
      this.$router.push({ name: 'HuankeAccount' })
    }
  }
}
</script>

<style lang="scss" scoped>
.return-cash-popup-wrapper {
  .mint-popup {
    background: transparent;
  }
  /deep/ .v-modal {
    opacity: 0.55;
  }
  .return-cash-pop {
    .head {
      text-align: right;
      img {
        width: 24px;
      }
    }
    .body {
      width: 290px;
      height: 380px;
      background-image: url('../../assets/image/hh-icon/b10-payResult/bg-1.png');
      background-size: 100%;
      background-position: center;
      background-repeat: no-repeat;
      padding-top: 180px;
      padding-bottom: 20px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between;
      &.no-back {
        background-image: url('../../assets/image/hh-icon/b10-payResult/bg-2.png');
      }
      .money {
        text-align: center;
        color: rgba(254, 237, 124, 1);
        font-family: PingFangSC;
        font-weight: 500;
        font-size: 0;
        letter-spacing: 1px;
        text-shadow: 0px 2px 4px rgba(231, 47, 81, 1);
        .unit {
          font-size: 42px;
        }
        .count {
          font-size: 70px;
        }
      }
      .top {
        padding-top: 30px;
        text-align: center;
        font-size: 19px;
        font-family: PingFangSC;
        font-weight: 500;
        color: rgba(254, 237, 124, 1);
        line-height: 28px;
      }
      .btn {
        margin: 0 auto;
        width: 168px;
        height: 35px;
        background: rgba(250, 173, 63, 1);
        border-radius: 18px;
        font-size: 14px;
        font-family: PingFangSC;
        font-weight: 400;
        color: rgba(123, 74, 5, 1);
        line-height: 35px;
        text-align: center;
      }
      .bottom {
        margin-top: 20px;
        text-align: center;
        font-size: 11px;
        font-family: PingFangSC;
        font-weight: 400;
        color: rgba(233, 156, 58, 1);
        line-height: 16px;
      }
    }
  }
}
</style>
