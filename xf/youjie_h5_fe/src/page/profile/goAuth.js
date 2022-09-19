import store from '../../store/index'
import { authCheck } from '../../api/auth-base'
export const goAuth = (router, isHHApp, hhApp) => {
  // 授权之前先联网检查授权状态
  authCheck(1).then(res => {
    if (res.data && res.data.status) {
      store.commit('saveAuthData', res)
    } else {
      // 待审核状态跳转爱投资授权页面
      if (store.getters.authStep == 1) {
        router.push({ name: 'authPage' })
        return
      }

      let versionGT010 = false // app 版本是否大于020

      let version = '0.1.0'
      if (hhApp.getAppVersion()) {
        version = hhApp.getAppVersion()
      }

      if (Number(version.replace(/\./g, '')) >= Number('020')) {
        versionGT010 = true
      }

      if (isHHApp && versionGT010) {
        // 同意过授权则直接去签署成功页
        if (store.getters.isAgree) {
          router.push({ name: 'AuthFirstStepResult' })
          // hhApp.openAppPage('yjmall://app_identify')
        } else {
          // 'https://m.huanhuanyiwu.com/h5/#/auth'
          router.push({ name: 'auth' })
          // hhApp.openNewPage(location.origin + location.pathname + '#/auth')
        }
      } else {
        router.push({ name: 'authPage' })
      }
    }
  })
}
