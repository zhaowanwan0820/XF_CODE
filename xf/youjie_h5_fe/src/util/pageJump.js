import Vue from 'vue'
import hhApp from '../assets/js/mallApp-bridge'
import utils from './util'

export const pageJump = url => {
  if (!Vue.prototype.isHHApp) {
    return doJump(url)
  }

  // 关闭原生 下拉刷新
  hhApp.isTop(false)

  // App V0.4.0 新页面打开
  const appVersion = utils.getHhAppVersion()
  if (appVersion >= 40) {
    hhApp.openNewPage(url)
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
