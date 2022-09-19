import Vue from 'vue'
import { dayjs } from '@/utils'

// 将时间戳转成日期
Vue.filter('timestampToDayFilter', function (timestamp) {
  if (!timestamp || timestamp <= 0) {
    return ''
  }
  return dayjs(timestamp ? timestamp * 1e3 : Date.now()).format('YYYY-MM-DD')
})

// 千分位(保留小数)
Vue.filter('toThousandFilter', function (num) {
  return (+num || 0).toString().replace(/^-?\d+/g, m => m.replace(/(?=(?!\b)(\d{3})+$)/g, ','))
})
