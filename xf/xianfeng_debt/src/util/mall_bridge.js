import utils from './util'

// test

var hhmall = (function() {
  // IOS 用
  function _request(action, params) {
    const appVersion = utils.getHhAppVersion()
    if (appVersion >= 40) {
      window.webkit.messageHandlers[action].postMessage(params)
      return
    }

    var iframe = document.createElement('iframe')
    var url = 'yjmall://' + action + ''
    if (params) {
      url += '?'
      var pairs = []
      for (var key in params) {
        pairs.push(key + '=' + params[key])
      }
      url += pairs.join('&')
    }
    iframe.src = url
    iframe.width = 1
    iframe.height = 1
    iframe.style.visibility = 'hidden'
    iframe.style.position = 'absolute'
    iframe.style.bottom = 0
    iframe.style.left = 0
    iframe.onload = function() {
      document.body.removeChild(iframe)
    }
    iframe.onerror = function() {
      document.body.removeChild(iframe)
    }
    document.body.appendChild(iframe)
  }
  var imps = {
    /*登录，并传递userId*/
    loginSuccess: function(userToken) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.loginSuccess(userToken)
      }
      _request('loginSuccess', { userToken: encodeURIComponent(userToken) })
    } /*退出登录*/,
    logout: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.logout()
      }
      _request('logout')
    } /*调用 app 检查更新功能*/,
    appUpdate: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.appUpdate()
      }
      _request('appUpdate')
    } /*H5告诉移动端，当前页面内容是否滑动到了顶部*/,
    isTop: function(isTop) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.isTop(isTop)
      }
      _request('isTop', { isTop: isTop })
    } /*打开移动端新页面，并跳转到指定 url 地址，url 目前支持纯页面地址，即 http 和 https 开头的地址*/,
    openNewPage: function(url) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.openNewPage(url)
      }
      _request('openNewPage', { url: encodeURIComponent(url) })
    } /*H5需要使用客户端存储在本地存储持久化数据时调用*/,
    setData: function(key, value) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.setData(key, value)
      }
      _request('setData', { key: key, value: value })
    } /*H5获取使用客户端存储机制存储到本地的数据*/,
    getData: function(key) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.getData(key)
      }
      return getDataFromIos('getData', key)
      // return window[key]
    } /*获取App版本号*/,
    getAppVersion: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.getAppVersion()
      }
      return getDataFromIos('appVersion')
    } /*打开新的Webview页面, 新页面关闭与老页面没有影响*/, // url 传绝对路径
    openPage: function(url) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.openPage(url)
      }
      _request('openPage', { url: encodeURIComponent(url) })
    },
    /*分享 前端在调用此方法时，需区分Android和iOS，Android 不需要encode，iOS需要encode。*/
    // description(此次分享的描述信息)
    // text 分享的内容，
    // imageUrl 分享中图片绝对地址
    // platform 分享的平台，目前支持（QQ， Qzone， Sina（新浪微博）， WechatSession（微信）， WechatTimeline（微信朋友圈）），多个平台时用英文逗号","隔开。一个平台时直接跳到对应 App，多个平台时调起原生分享面板，传"all"时调起全平台的原生分享面板
    // flag 分享的活动名称
    // title 分享的标题
    // url 分享的链接（需要encode）
    // description 对此次分享的描述信息，分享成功后，会记录到后台
    share: function(text, imageUrl, platform, flag, title, url, description) {
      if (platform instanceof Array) {
        platform = platform.join(',')
      }
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.share(text, imageUrl, platform, flag, title, url, description || '')
      }
      _request('share', {
        text: text,
        platform: platform,
        imageurl: imageUrl,
        flag: flag,
        title: title,
        url: url,
        description: description || ''
      })
    },
    // 0.3.0 添加，用来分享纯图片和纯文本，前端在调用此方法时，需区分Android和iOS，Android 不需要encode，iOS需要encode(方法里做encode处理，传入参数不要encode)。*/
    // info 分享的内容，可以是图片 url 或者 纯文本信息，具体根据 type 确定s
    // platforms 分享的平台，目前支持（QQ(不支持纯文本分享)， Qzone， Sina（新浪微博）， WechatSession（微信）， WechatTimeline（微信朋友圈）），多个平台时用英文逗号","隔开。一个平台时直接跳到对应 App，多个平台时调起原生分享面板，传"all"时调起全平台的原生分享面板
    // type 1 表示纯图片分享，此时 info 传要分享的图片 url； 2 表示纯文本分享，此时 info 传要分享的纯文本信息
    // thumb 分享图片的缩略图 url，分享纯文本时(type=2)，传 '' 即可
    // actName 分享的活动名称，分享成功后，后台统计使用，跟分享操作本身无关
    // description 分享的活动描述信息，分享成功后，后台统计使用，跟分享操作本身无关
    // 额外说明：图片大小最好不要超过250k，缩略图不要超过18k，如果超过太多，图片会被压缩（最好不要分享1M以上的图片，压缩效率会很低很低，导致分享变得很慢很慢）
    // 注意：QQ 好友不支持纯文本分享，分享纯文本不能使用该平台(platforms 不能传 all 和 QQ，因为 all 默认包含 QQ 平台)
    pureShare: function(info, platforms, type, thumb, actName, description) {
      if (platforms instanceof Array) {
        platforms = platforms.join(',')
      }
      if (window.javaObj) {
        return window.javaObj.pureShare(info, platforms, type, thumb || '', actName || '', description || '')
      }
      _request('pureShare', {
        info: info,
        platforms: platforms,
        type: type,
        thumb: thumb || '',
        actName: actName || '',
        description: description || ''
      })
    },
    /*关闭页面*/
    close: function() {
      _request('close')
    } /*获取设备OPEN-UDID号------iOS 专属*/, // -----------------1.6.0新加
    getDeviceUDID: function() {
      return getDataFromIos('deviceUDID')
    } /*获取设备IDFA号，优先使用------iOS 专属*/,
    getDeviceIDFA: function() {
      return getDataFromIos('deviceIDFA')
    } /*获取设备IMEI号------Android 专属*/,
    getDeviceIMEI: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.getDeviceIMEI()
      }
    } /*获取设备AndroidID号，优先使用------Android 专属*/,
    getDeviceAndroidID: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.getDeviceAndroidID()
      }
    }, // 打开微信或其他App；例如打开微信appScheme="weixin://"
    openOtherApp: function(scheme) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.openOtherApp(scheme)
      }
      _request('openOtherApp', { scheme: scheme })
    }, // 复制网页内容到App
    copyText: function(text) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.copyText(text)
      }
      _request('copyText', { text: text })
    }, // 刷新当前页面
    refreshPage: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.refreshPage()
      }
      _request('refreshPage')
    }, // 获取网络状态,-1 未联网，1 4G，2 WIFI
    getNetType: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.getNetType()
      }
      _request('getNetType')
    }, // 播放视频 url:视频地址
    playVideo: function(url) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.playVideo(url)
      }
      _request('playVideo', { url: url })
    }, // 打开原生页面
    openAppPage: function(schema) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.openAppPage(schema)
      }
      _request('openAppPage', { schema: schema })
    }, // 去评分
    toScore: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.toScore()
      }
      _request('toScore')
    }, // 打开原生支付页面，type 1 表示 微信，2 支付宝 orderInfo 注意区分微信和支付宝订单格式
    openPay: function(type, orderInfo) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.openPay(type, orderInfo)
      }
      _request('openPay', { type: type, orderInfo: orderInfo })
    },
    // 0.2.3 新增
    isWXAppInstalled: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.isWXAppInstalled()
      }
      return getDataFromIos('isWXAppInstalled')
    },
    // 0.4.0 新增
    handleNavigation: function(show) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.handleNavigation(show)
      }
      _request('handleNavigation', { show: show })
    },
    // 0.4.0 新增
    cartChanged: function(num) {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.cartChanged(num)
      }
      _request('cartChanged', { num: num })
    },
    // 0.4.0 新增 返回上一级
    goBack: function() {
      if (window.youJieJavaObj) {
        return window.youJieJavaObj.goBack()
      }
      _request('goBack')
    }
  }
  for (var key in imps) {
    if (window.youJieJavaObj && window.youJieJavaObj[key]) {
      imps[key] = (function(mn) {
        return function() {
          var appVersion = window.youJieJavaObj.getAppVersion()
          appVersion = parseInt(appVersion.replace(/\./g, ''))
          if (mn == 'showUmengShare' && appVersion < 231) {
            //android2.3.1版本以下版本不调用友盟分享
            return false
          } else {
            var args = Array.prototype.slice.call(arguments)
            if (mn == 'share') {
              if (appVersion < 231) {
                // 为了支持旧版本，去掉新加的description参数
                args = args.slice(0, 6)
              } else if (args.length == 6) {
                // 新版本如果只有6个参数，增加默认为空的最后一个参数
                args.push('')
              }
            }
            return window.youJieJavaObj[mn].apply(window.youJieJavaObj, args)
          }
        }
      })(key)
    }
  }
  return imps
})()

// 从ios app原生同步获取数据（区分 V040 之前的版本）
const getDataFromIos = (func, key) => {
  const appVersion = utils.getHhAppVersion()
  if (appVersion >= 40) {
    return window.prompt(func, key)
  } else {
    return window[func]
  }
}

/**
 * !!!在更新Bridge方法时，不要动下边的代码
 * 该方法非 app-bridge 内置方法
 * 由于app 往webView 【嵌入】相关的bridge接口是异步完成的，所以web端js在调用相关方法时，需先确保【嵌入】动作已经完成
 * 功能：判断【嵌入】已完成，执行回调函数告知调用方
 * 适用场景：通常是在进入页面需要立即执行app-bridge相关方法的功能；例如需要在 DOMContentLoaded 后执行相关app操作
 */
hhmall._ready_ = function() {
  return new Promise((resolve, reject) => {
    let timeLong = 0
    const tick = 50
    let st = null

    const itector = () => {
      if (hhmall.getAppVersion() !== undefined) {
        clearTimeout(st)
        resolve()
        return
      }
      timeLong += tick
      // 5s 超时
      if (timeLong >= 5000) {
        clearTimeout(st)
        reject()
        return
      }
      st = setTimeout(itector, tick)
    }
    itector()
  })
}

export default hhmall
