<template>
    <div class="container">
      <van-nav-bar :title="title" />
      <mine-list></mine-list>
      <div class="btn" @click="dropOut">退出登录</div>

    </div>
</template>

<script>
  import { mapState, mapMutations, mapActions } from 'vuex'
  import { Toast } from 'vant'
  import MineList from './child/MineList'
  export default {
    name: 'mine',
    data() {
      return {
        title: '我的',
        xf_from: false
      }
    },
    computed: {
      ...mapState({
        isOnline: state => state.auth.isOnline
      })
    },
    created() {
      if(!this.isOnline){
          // window.location.href = '/#/login?from=debtMarket'
          // return
      }
    },
    components: {
      MineList
    },
    methods: {
        dropOut() {
            localStorage.removeItem('m_assets_garden', {});
            localStorage.removeItem('auth', {});
            localStorage.removeItem('is_set_pay_password', '');
            localStorage.removeItem('xianfeng', '');
            this.$store.commit('removeuser')
            Toast(`退出成功`)
            window.location.href = '/#/login?from=debtMarket'
        }
    }
    
  }
   
</script>

<style lang="less" scoped>
    
    .btn {
        width: 100%;
        height: 55px;
        line-height: 55px;
        text-align: center;
        background-color: #fff;
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(51, 51, 51, 1);
        margin-top: 10px;
    }
</style>