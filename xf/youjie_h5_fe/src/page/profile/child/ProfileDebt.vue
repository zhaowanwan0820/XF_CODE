<template>
  <div>
    <div class="debt-head">债权转让</div>
    <div class="entrance-list">
      <div class="entrance" @click="goPage('mytransfer')">我的转让</div>
      <div class="entrance" @click="goPage('mysubscription')">我的认购</div>
      <div class="entrance" @click="goPage('debtMarket')">债转市场</div>
    </div>
  </div>
</template>

<script>
import { fetchAlone } from '../../../server/network'
import { mapActions } from 'vuex'
export default {
  name: 'ProfileDebt',
  methods: {
    ...mapActions({
      fetchWxAuthCheck: 'fetchWxAuthCheck'
    }),
    async goPage(name) {
      // 进入债转市场校验身份验证信息
      if (name == 'debtMarket') {
        let hasConfirm = true
        try {
          await this.fetchWxAuthCheck(true)
        } catch (e) {
          hasConfirm = false
        }
        if (!hasConfirm) {
          this.$cookie.set('is_debt', name)
          return
        }
      }
      window.location.href = `/debt/#/${name}`
      // fetchAlone(process.env.VUE_APP_DEBTSERVER_HOST+'/Launch/DebtGarden/TransLook','POST',null).then(res=>{
      //   if(res.data.risk_level==0){
      //     this.$messagebox.confirm('您还没有风险测评,请先进行风险测评').then(res=>{
      //       window.location.href=`/debt/#/questionnaire/1`
      //     })
      //   }else if(res.data.debt_status==0){
      //     this.$messagebox.confirm('您还没有签署债转服务协议,请前往签署').then(res=>{
      //       window.location.href=`/debt/#/debtAgreement`
      //     })
      //   }else{
      //     window.location.href=`/debt/#/${name}`
      //   }
      // })
    }
  }
}
</script>

<style lang="scss" scoped>
.debt-head {
  font-size: 18px;
  font-weight: bold;
  color: #333;
  line-height: 25px;
  padding: 15px;
}
.entrance-list {
  padding: 0 15px;
  display: flex;
  .entrance {
    flex: 1;
    height: 50px;
    line-height: 50px;
    background: rgba(249, 249, 249, 1);
    border-radius: 2px;
    text-align: center;
    font-size: 12px;
    color: #404040;
    & + .entrance {
      margin-left: 10px;
    }
    &:before {
      content: '';
      display: inline-block;
      width: 21px;
      height: 21px;
      margin-right: 10px;
      background-size: contain;
      vertical-align: middle;
    }
    &:nth-child(1):before {
      background-image: url('../../../assets/image/hh-icon/debt/entrance1@2x.png');
    }
    &:nth-child(2):before {
      background-image: url('../../../assets/image/hh-icon/debt/entrance2@2x.png');
    }
    &:nth-child(3):before {
      background-image: url('../../../assets/image/hh-icon/debt/entrance3@2x.png');
    }
  }
}
</style>
