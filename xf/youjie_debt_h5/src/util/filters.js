import Vue from 'vue'
import utils from './util'

// 将时间戳转成日期
Vue.filter('convertTime', function(timeStamp) {
  return utils.formatDate('YYYY-MM-DD HH:mm:ss', timeStamp)
})
// 将时间戳转成人性化日期（当前 显示 时分，当年显示月日+时分 else 年 + 月日 + 时分）
Vue.filter('formatDateLocal', function(timeStamp) {
  return utils.formatDateLocal(timeStamp)
})
// 金额格式化
Vue.filter('formatMoney', function(value) {
  return utils.formatMoney(value, false)
})
