import Vue from 'vue'
import utils from './util'
import yjApp from './mall_bridge'

export const pageJump = url => {
  if (!Vue.prototype.isApp) {
    return doJump(url)
  }
  // 关闭原生 下拉刷新
  yjApp.isTop(false)

  // App V0.4.0 新页面打开
  const appVersion = utils.getHhAppVersion()
  if (appVersion >= 40) {
    yjApp.openNewPage(url)
    return
  }

  // Android
  if (2 == utils.getOpenBrowser()) {
    return doJump(url)
  }

  // 非Android
  setTimeout(() => {
    doJump(url)
  }, 20)
}


const doJump = url => {
  window.location.href = url
}
