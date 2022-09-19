import store from '../store/index'
// test
// import { Toast } from 'mint-ui'

// 每5*60*1000修正一次时钟偏移
const timeInterval = 5 * 60000
setInterval(() => {
  store.dispatch('updateClockOffset')
}, timeInterval)
store.dispatch('updateClockOffset')

// 浏览器web页面被重新唤醒时
// 参考：https://developer.mozilla.org/zh-CN/docs/Web/API/Page_Visibility_API
// 设置隐藏属性和改变可见属性的事件的名称
// let hidden, visibilityChange
// if (typeof document.hidden !== 'undefined') {
//   // Opera 12.10 and Firefox 18 and later support
//   hidden = 'hidden'
//   visibilityChange = 'visibilitychange'
// } else if (typeof document.msHidden !== 'undefined') {
//   hidden = 'msHidden'
//   visibilityChange = 'msvisibilitychange'
// } else if (typeof document.webkitHidden !== 'undefined') {
//   hidden = 'webkitHidden'
//   visibilityChange = 'webkitvisibilitychange'
// }
// const handleVisibilityChange = () => {
//   if (!document[hidden]) {
//     store.dispatch('updateClockOffset')
//   }
// }
// // 如果浏览器不支持addEventListener 或 Page Visibility API 给出警告
// if (typeof document.addEventListener === 'undefined' || typeof document[hidden] === 'undefined') {
//   console.error(
//     'This function requires a browser, such as Google Chrome or Firefox, that supports the Page Visibility API.'
//   )
// } else {
//   // 处理页面可见属性的改变
//   document.addEventListener(visibilityChange, handleVisibilityChange, false)
// }
