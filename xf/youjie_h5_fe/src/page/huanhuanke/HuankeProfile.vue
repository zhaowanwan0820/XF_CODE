<template>
  <div class="container">
    <mt-header class="header" fixed :title="utils.mlmUserName">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <div class="account" @click="goAccount">
        <label>账户佣金</label>
        <div class="num">
          <label>￥</label><span>{{ utils.formatMoney(user_money) }}</span>
        </div>
      </div>
      <div class="items" @click="goFan">
        <div class="left">
          <img src="../../assets/image/hh-icon/mlm/icon-fan@3x.png" alt="" />
          <p>分销返佣</p>
        </div>
        <div class="right">
          <p v-if="isInternal">未代付{{ unpay_count }}笔</p>
          <p v-else>已返佣{{ rebate_count }}笔</p>
          <img src="../../assets/image/hh-icon/mlm/icon-tip@3x.png" alt="" />
        </div>
      </div>
      <div class="items" @click="goFriendPayList">
        <div class="left">
          <img src="../../assets/image/hh-icon/mlm/icon-friend@3x.png" alt="" />
          <p>好友代付</p>
        </div>
        <div class="right">
          <p class="gray">已代付{{ sharepay_count }}笔</p>
          <img src="../../assets/image/hh-icon/mlm/icon-tip@3x.png" alt="" />
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import HeaderItem from './child/HeaderItem'
import { huanAccount } from '../../api/huanhuanke'
import { ENUM } from '../../const/enum'
import { mapState } from 'vuex'

export default {
  name: 'HuankeProfile',
  data() {
    return {
      user_money: 0, //账户佣金
      unpay_count: 0, //返佣
      sharepay_count: 0, //代付笔数
      rebate_count: 0 //返佣笔数
    }
  },
  components: {
    HeaderItem
  },
  created() {
    this.getHuankeProfile()
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      orderStatus: state => state.order.orderStatus,
      isInternal: state => state.mlm.isInternal
    })
  },
  methods: {
    getHuankeProfile() {
      huanAccount(ENUM.HUANKE_STATUS.ALL).then(
        res => {
          this.user_money = Number(res.user_money) + Number(res.frozen_money)
          this.unpay_count = res.unpay_count
          this.rebate_count = res.rebate_count
          this.sharepay_count = res.sharepay_count
        },
        error => {
          console.log(error)
        }
      )
    },
    goAccount() {
      this.$router.push({ name: 'HuankeAccount' })
    },
    goFriendPayList() {
      if (this.isOnline) {
        this.$router.push({ name: 'friendPayOrder', params: { order: 'all' } })
      } else {
        this.showLogin()
      }
    },
    goBack() {
      this.$_goBack()
    },
    goFan() {
      this.$router.push({ name: 'HuankeOrder', params: { order: this.orderStatus } })
    },
    showLogin() {
      this.$router.push({ name: 'login' })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  background: #fff;
  display: flex;
  position: relative;
  flex-direction: column;
  justify-content: flex-start;
}
.header {
  @include header;
  color: #fff;
  background: rgba(0, 0, 0, 0);
}
.content {
  background: url('../../assets/image/hh-icon/mlm/bg-profile.png') no-repeat;
  background-color: linear-gradient(180deg, rgba(245, 233, 223, 1) 0%, rgba(255, 255, 255, 1) 100%);
  background-size: 375px 165px;
  padding-top: 99px;
  display: flex;
  flex-direction: column;
  align-items: center;
  .account {
    width: 315px;
    height: 80px;
    padding: 0 15px;
    background: #fff;
    box-shadow: 0px 3px 8px 0px rgba(226, 200, 204, 0.27);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 2px;
    margin-bottom: 7px;
    .num {
      color: #404040;
      label {
        display: inline-block;
        font-size: 14px;
        line-height: 20px;
        font-weight: 600;
      }
      span {
        display: inline-block;
        font-size: 20px;
        line-height: 24px;
        font-weight: bold;
      }
    }
  }
  .items {
    width: 317px;
    height: 66px;
    display: flex;
    justify-content: space-between;
    padding: 0 16px 0 12px;
    background: #fff;
    border: 1px dotted rgba(85, 46, 32, 0.2);
    margin-top: 20px;
    .left {
      display: flex;
      justify-content: center;
      align-items: center;
      img {
        width: 35px;
        height: 35px;
        margin-right: 10px;
      }
      p {
        font-size: 16px;
        color: #404040;
        line-height: 22px;
      }
    }
    .right {
      display: flex;
      justify-content: center;
      align-items: center;
      p {
        font-size: 12px;
        color: #552e20;
        line-height: 17px;
        margin-right: 3px;
      }
      img {
        width: 6px;
        height: 6px;
      }
      .gray {
        color: #999;
      }
    }
  }
}
</style>
