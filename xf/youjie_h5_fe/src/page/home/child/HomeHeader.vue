<template>
  <div class="home-header">
    <div class="input-wrapper">
      <div class="input" :class="{ 'is-fixed': headerBackgroundOpacite == 1 }" @click="onSearch" :style="getInputStyle">
        <img v-if="headerBackgroundOpacite == 1" src="../../../assets/image/hh-icon/search-brown.png" alt="" />
        <img v-else src="../../../assets/image/hh-icon/search-white.png" alt="" />
        <span>请输入搜索内容</span>
      </div>
    </div>
    <!-- <div class="msg" @click="goMsg">
      <div class="red" v-if="isRead"></div>
    </div> -->
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { unReadMsg } from '../../../api/message'
export default {
  name: 'HomeHeader',
  props: ['headerBackgroundOpacite'],
  data() {
    return {
      isRead: false
    }
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline
    }),
    getInputStyle() {
      const style = {}
      if (this.headerBackgroundOpacite <= 0.3) {
        style.backgroundColor = 'rgba(255, 255, 255, 0.3)'
      } else {
        style.backgroundColor = `rgba(244, 244, 244, ${this.headerBackgroundOpacite})`
      }
      return style
    }
    // ...mapGetters({
    //   isHasUnreadCount: 'isHasUnreadCount'
    // })
  },
  created() {
    // this.getUnReadMsg()
  },
  methods: {
    // getUnReadMsg() {
    //   unReadMsg().then(res => {
    //     this.isRead = res && res.isRead === 'yes' ? true : false
    //   })
    // },
    onSearch() {
      this.$router.push({ name: 'search', params: { isFromHome: true } })
    },
    goMsg() {
      this.$router.push({ name: 'message' })
    }
    // rightClick() {
    //   if (this.isOnline) {
    //     this.$router.push({ name: 'messageCenter' })
    //   } else {
    //     this.$router.push({ name: 'login' })
    //   }
    // }
  }
}
</script>

<style lang="scss" scoped>
.home-header {
  height: 50px;
  display: flex;
  align-items: center;
  .input-wrapper {
    flex: 1;
    padding: 0 30px;
    .input {
      width: 100%;
      color: #d5d5d5;
      height: 30px;
      border-radius: 15px;
      // opacity: 0.5;
      background-color: rgba(255, 255, 255, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      &.is-fixed {
        span {
          color: rgba(85, 46, 32, 1);
          opacity: 0.6;
        }
      }
      span {
        line-height: 15px;
        font-size: 15px;
        font-family: PingFangSC-Light;
        font-weight: 300;
        color: rgba(255, 255, 255, 1);
        margin-left: 8px;
        margin-top: 1px;
      }
      img {
        width: 15px;
        height: 15px;
      }
    }
  }
  .msg {
    @include wh(18px, 18px);
    font-size: 0;
    margin-left: 17px;
    margin-right: 19px;
    position: relative;
    background: url('../../../assets/image/hh-icon/b0-home/icon-unReadMsg.png') no-repeat;
    background-size: 18px 18px;
    .red {
      position: absolute;
      top: -4px;
      right: -4px;
      width: 7px;
      height: 7px;
      background: #9b210b;
      border-radius: 50%;
    }
  }
}
</style>
