import { fetchAlone } from '../server/network'

// 当前端代码更新了，让旧的页面自动刷新(透过index.html <body buildTime="XXXX"> xxxx识别)
const appRegExp = /<body[^<>]*buildtime=['\"]?(\d*)['\"]?[^<>]*>/i
let client_buildTime = new RegExp(appRegExp).exec(document.body.outerHTML)
client_buildTime = client_buildTime ? client_buildTime[1] : 0

const refreshFn = time => {
  fetchAlone(`index.html?t=${new Date().getTime()}`).then(res => {
    let server_buildTime = new RegExp(appRegExp).exec(res)
    if (server_buildTime && client_buildTime != server_buildTime[1]) {
      console.info('前端版本更新了')
      setTimeout(() => {
        window.location.reload(true)
      }, time)
    }
  })
  setTimeout(() => {
    refreshFn(5e4) // 50s 后更新
  }, 6e4)
}

export default () => refreshFn(0) // 打开的是浏览器本地缓存的页面，立即对用户进行更新
