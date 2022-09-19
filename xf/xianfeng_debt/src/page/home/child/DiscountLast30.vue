<template>
  <div>
    <chart-common :chartData="dataLast30" :type="'cat'" key="last30"></chart-common>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import ChartCommon from './ChartCommon'

export default {
  name: 'DiscountLast30',
  data() {
    return {}
  },
  computed: {
    ...mapState({
      dataLast30: state => state.home.dataLast30
    })
  },
  components: {
    ChartCommon
  }
}
</script>

<style lang="less" scoped></style>
