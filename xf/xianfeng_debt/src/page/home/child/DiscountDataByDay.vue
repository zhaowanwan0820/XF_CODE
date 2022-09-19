<template>
  <div>
    <chart-common :chartData="dataByDay" :type="'timeCat'" key="byDay"></chart-common>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import ChartCommon from './ChartCommon'

export default {
  name: 'DiscountDataByDay',
  data() {
    return {}
  },
  computed: {
    ...mapState({
      dataByDay: state => state.home.dataByDay
    })
  },
  components: {
    ChartCommon
  }
}
</script>

<style lang="less" scoped></style>
