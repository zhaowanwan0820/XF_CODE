<template>
  <div class="qn-container">
    <template v-if="is_new && type == 1">
      <questions-home-page @start="start"></questions-home-page>
    </template>
    <template v-else>
      <question-body v-if="qn_list.length" :list="qn_list" @submit="submit"></question-body>
    </template>
  </div>
</template>

<script>
import QuestionsHomePage from './QuestionsHomePage'
import QuestionBody from '../questionnaire/QuestionsBody'
import { getQusetion, submitQuestions } from '../../api/questionnaire'
import { mapState, mapMutations } from 'vuex'
export default {
  name: 'Evaluation',
  data() {
    return {
      type: this.$route.params.type, // 1风险评级问卷，2再投资问卷，3债消市场问卷
      is_new: true,
      qn_list: []
    }
  },
  created() {
    this.$loading.close()
    this.isOnline ? this.getQues() : this.$router.push('login')
  },
  components: {
    QuestionsHomePage,
    QuestionBody
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline
    })
  },
  methods: {
    ...mapMutations({
      saveRisk: 'saveRisk'
    }),
    getQues() {
      this.$loading.open()
      getQusetion(this.type)
        .then(res => {
          this.qn_list = res.data
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    start() {
      this.is_new = false
    },
    submit(res) {
      let data = { ...res, qstn_id: this.qn_list[0].qstn_id }
      data.answerArr = JSON.stringify(res.answerArr)
      this.$loading.open()
      submitQuestions(data)
        .then(response => {
          if (this.type == 1) {
            this.saveRisk({
              level_name: response.data.level_name,
              level_id: response.data.level_id
            })
            this.$router.push({ name: 'evaluationResult' })
          } else {
            this.$router.replace({ name: 'reinvest' })
          }
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    goBack() {
      return window.location.replace('/#/')
    }
  }
}
</script>
<style lang="less" scoped>
.qn-container {
  height: 100%;
  background-color: #fff;
}
</style>
