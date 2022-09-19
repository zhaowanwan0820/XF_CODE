<template>
  <div class="shopkeeper-wrapper">
    <div class="spkpr-head">店主专区</div>
    <div class="spkpr-body">
      <div class="spkpr-item" @click="goFan">
        <img src="../../../assets/image/hh-icon/f0-profile/icon-huanke.png" alt="" />
        <div>
          <div class="top">
            <span class="count">{{ isInternal ? unpayCount : rebateCount }}</span
            ><span>单</span>
          </div>
          <div class="bottom">分销返佣</div>
        </div>
      </div>
      <div class="spkpr-item" @click="goFriendPayList">
        <img src="../../../assets/image/hh-icon/f0-profile/icon-friendpay.png" alt="" />
        <div>
          <div class="top">
            <span class="count">{{ sharepayCount }}</span
            ><span>单</span>
          </div>
          <div class="bottom">好友代付</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'
export default {
  name: 'ProfileShopkeeper',
  data() {
    return {}
  },

  props: ['unpayCount', 'rebateCount', 'sharepayCount'],

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      isInternal: state => state.mlm.isInternal
    })
  },

  methods: {
    goFan() {
      this.$router.push({ name: 'HuankeOrder', params: { order: 10 } })
    },
    goFriendPayList() {
      if (this.isOnline) {
        this.$router.push({ name: 'friendPayOrder', params: { order: 'all' } })
      } else {
        this.showLogin()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.shopkeeper-wrapper {
  background-color: #ffffff;
  padding: 0 15px;
  .spkpr-head {
    padding: 5px 0 16px;
    font-size: 18px;
    font-weight: bold;
    color: #333;
    line-height: 25px;
  }
  .spkpr-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .spkpr-item {
    width: 165px;
    height: 64px;
    background-image: url('../../../assets/image/hh-icon/f0-profile/spkpr-bg.png');
    background-size: 100%;
    background-position: center;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    img {
      width: 32px;
      height: 32px;
      margin-left: 15px;
    }
    & > div {
      margin-left: 14px;
    }
    .top {
      display: flex;
      align-items: flex-end;
    }
    span {
      font-size: 16px;
      font-weight: 500;
      color: rgba(64, 64, 64, 1);
      line-height: 20px;
      &.count {
        font-size: 20px;
      }
    }
    .bottom {
      font-size: 12px;
      font-weight: 400;
      color: rgba(160, 160, 160, 1);
      line-height: 17px;
    }
  }
}
</style>
