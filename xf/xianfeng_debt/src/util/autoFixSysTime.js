import store from '../store/index'

// 每5*60*1000修正一次时钟偏移
const timeInterval = 5 * 60000
setInterval(() => {
  store.dispatch('updateClockOffset')
}, timeInterval)
store.dispatch('updateClockOffset')
