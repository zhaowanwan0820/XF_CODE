<template>
  <div class="redeemwrapper" v-if="show">
    <img class="dialog-img" src="../../assets/image/hh-icon/dialog-img@2x.png" width="148" alt="" />
    <div class="dialog-body">
      <img class="close-img" src="../../assets/image/hh-icon/guanbi@2x.png" width="26" alt="" @click="show = false" />
      <p>尊敬的客户您好：</p>
      <p>您的还款兑付方案待确认，请您点击下方立即查看按钮进行查看，感谢您的配合！</p>
      <div class="btn-wrapper">
        <div class="link-btn" @click="$router.push({ name: 'newPlanVote', query: { type: 2 } })">立即查看</div>
      </div>
    </div>
  </div>
</template>

<script>
import { getDialogStatus } from '../../api/newplane'
export default {
  name: 'RedeemDialog',
  data() {
    return {
      show: true
    }
  },
  activated() {
    if (this.$store.state.auth.isOnline) {
      getDialogStatus().then(res => {
        if (res.status == 200) {
          this.show = true
          this.$parent.changePriority()
        } else {
          this.show = false
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.redeemwrapper {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  .dialog-img {
    margin-bottom: -90px;
    z-index: 10;
  }
  .dialog-body {
    position: relative;
    width: 310px;
    height: 274px;
    background: #fff;
    border-radius: 8px;
    box-sizing: border-box;
    padding: 90px 20px 0;
    color: #666;
    font-size: 15px;
    line-height: 26px;
    letter-spacing: 0.6px;
    .close-img {
      position: absolute;
      top: -50px;
      right: -10px;
    }
    .btn-wrapper {
      text-align: center;
      margin-top: 10px;
      .link-btn {
        display: inline-block;
        width: 228px;
        height: 40px;
        line-height: 40px;
        background-color: #fc810c;
        border-radius: 20px;
        text-align: center;
        color: #fff;
      }
    }
  }
}
</style>
