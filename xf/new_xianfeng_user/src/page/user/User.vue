<template>
  <div>
    <!-- 头部标题-->
    <common-header :title="title" style="border-bottom: 1px solid #F4F4F4;"></common-header>
    <div class="wrap">
      <div class="item">
        <div class="label">手机号</div>
        <div class="info">{{phone | phoneFilter}}</div>
      </div>
      <div class="item">
        <div class="label">姓名</div>
        <div class="info">{{name | nameFilter}}</div>
      </div>
      <div class="item">
        <div class="label">身份证号</div>
        <div class="info">{{idCard | idCardFilter}}</div>
      </div>
      <div class="item">
        <div class="label">银行卡号</div>
        <div class="info">{{bankCard | bankCardFilter}}</div>
      </div>
    </div>
    <!-- 退出登录按钮-->
    <div class="logout-btn" @click="excitBtn">
      <span>退出登录</span>
    </div>
  </div>
</template>

<script>
  import {
    mapState,
    mapMutations,
    mapGetters
  } from 'vuex'
  let that;
  export default {
    data() {
      return {
        userInfoObj: {},
        title: '个人信息',
        phone: '',
        name: '',
        idCard: '',
        bankCard: ''
      }
    },
    beforeCreate: function() {
      that = this;
    },
    created() {
      this.userInfoObj = JSON.parse(this.$route.query.userInfoObj);
      // userInfoObj = {
      //   mobile:'12345678963',
      //   real_name:'网25636',
      //   idno:'1234567890123456',
      //   bankcard:'1234567890123456'
      // }
    },
    filters: {
      phoneFilter() {
        let userInfoObj = that.userInfoObj
        if(userInfoObj.mobile){
          return userInfoObj.mobile.substr(0, 3) + "****" + userInfoObj.mobile.substr(7)
        }else{
         return ''
       }
      },
      nameFilter() {
        let str = '';
        let name = that.userInfoObj.real_name;
        that.name = name;
        if(name){
          for (let i = 0; i < name.length - 1; i++) {
            str += '*';
          }
          return name.substr(0, 1) + str;;
        }else{
         return ''
       }
      },
      idCardFilter() {
        let str = '';
        let idCard = that.userInfoObj.idno;
        that.idCard = idCard;
        if(idCard){
          for (let i = 0; i < idCard.length - 7; i++) {
            str += '*';
          }
          return idCard.substr(0, 3) + str + idCard.substr(idCard.length - 4);
        }else{
         return ''
       }
      },
      bankCardFilter() {
        let str = '';
        let bankCard = that.userInfoObj.bankcard;
        that.bankCard = bankCard;
       if(bankCard){
         for (let i = 0; i < bankCard.length - 8; i++) {
           str += '*';
         }
         return bankCard.substr(0, 4) + str + bankCard.substr(bankCard.length - 4);
       }else{
         return ''
       }
      }
    },
    methods: {
      ...mapMutations({
        clearToken: 'clearToken'
      }),

      excitBtn() {
        this.clearToken();
        localStorage.removeItem('m_assets_garden', {});
        localStorage.removeItem('is_set_pay_password', '');
        localStorage.removeItem('xianfeng', '');
        this.$router.push({
          path: "/login"
        });
      },
    }
  }
</script>

<style lang="less" scoped>
  .wrap {
    margin: 36px 16px 0 15px;

    .item {
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      height: 60px;
      line-height: 60px;
      border-bottom: 1px dashed rgba(198,198,198,0.3);

      .label {
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(112, 112, 112, 1);
      }

      .info {
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
      }
    }
  }

  .logout-btn {
    margin: 40px 45px;
    display: flex;
    flex-direction: row;
    justify-content: center;
    text-align: center;

    span {
      display: inline-block;
      height: 48px;
      line-height: 48px;
      background: rgba(255, 255, 255, 1);
      border-radius: 4px;
      border: 1px solid rgba(56, 52, 222, 1);
      font-size: 15px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(56, 52, 222, 1);
      width: 100%;
    }
  }
</style>
