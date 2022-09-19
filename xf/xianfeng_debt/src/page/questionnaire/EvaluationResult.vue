<template>
  <div class="e-result-container">
    <span class="e-title">您的风险测评等级</span>
    <p class="title" :class="{ small: !isDoneRiskTest }">{{ risk_level.level_name || '未参与' }}</p>
    <div class="btn-wrapper">
      <button class="deep" @click="goEvaluation">{{ risk_level.level_name ? '重新' : '开始' }}测评</button>
      <button @click="goDebt" v-if="risk_level.level_name">进入债转市场</button>
    </div>
  </div>
</template>
<script>
import { mapState, mapMutations, mapGetters } from 'vuex'
import { getRiskTestResult } from '../../api/user'
export default {
  name: 'EvaluationResult',
  data() {
    return {}
  },
  created() {
    if (!this.isOnline) {
      window.location.href = '/#/login'
      return
    } else if (!this.risk_level) {
      this.getResult()
    }
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      risk_level: state => state.auth.risk_level,
      selected_platformId: state => state.auth.selected_platformId
    }),
    ...mapGetters({
      isDoneRiskTest: 'isDoneRiskTest'
    })
  },
  methods: {
    ...mapMutations({
      saveRisk: 'saveRisk'
    }),
    getResult() {
      this.$loading.open()
      getRiskTestResult()
        .then(res => {
          if(res.code === 0){
            this.saveRisk({
              level_name: res.data.risk_level,
              level_id: res.data.level_risk_id
            })
          }else{
            this.$toast(res.info)
          }
          
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    goEvaluation() {
      this.$router.push({ name: 'evaluation', params: { type: 1 } })
    },
    goDebt() {
      this.$router.push({ name: 'debtMarket' })
    }
  }
}
</script>

<style lang="less" scoped>
.e-result-container {
  height: 100%;
  background-color: #fff;
  display: flex;
  align-items: center;
  flex-direction: column;
  .e-title {
    margin-top: 37px;
    font-size: 12px;
    color: #666;
    line-height: 17px;
  }
  .title {
    margin-top: 20px;
    color: @evaluationColor;
    font-size: 36px;
    letter-spacing: 1px;
    &.small {
      font-size: 20px;
    }
  }
  .btn-wrapper {
    margin-top: 84px;
    display: flex;
    flex-direction: column;
    align-items: center;
    button {
      width: 280px;
      height: 45px;
      border-radius: 6px;
      border: 1px solid @evaluationColor;
      margin-bottom: 32px;

      font-size: 16px;
      color: @evaluationColor;
      background-color: #fff;
      &.deep {
        color: #fff;
        background-color: @evaluationColor;
      }
    }
  }
}
</style>
