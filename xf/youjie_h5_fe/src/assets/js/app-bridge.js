window.iTouziAPP = (function() {
  function _request(action, params) {
    var iframe = document.createElement('iframe')

    var url = 'itouzi://' + action + ''
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
    /** 5.8.0 新增，打开原生 App 图片展示页面，支持拖动缩放，可展示单张或多张图片，展示多张时，可通过左右滑动查看。
     *  其中 imageUrls 参数为字符串数组，参数格式,多个为："url1,url2,url3",单个为："url1"
     *  selectIndex 表示进入页面后，默认显示第几张图片，0 表示第一张。只有多张图片时才传此参数，否则传空值，或这不传。
     **/
    openImagePage: function(imageUrls, selectIndex) {
      if (window.javaObj) {
        return javaObj.openImagePage(imageUrls)
      }
      _request('openImagePage', { imageUrls: encodeURIComponent(imageUrls), selectIndex: selectIndex })
    },
    /* 5.7.4 新增 **/
    getUserHashId: function() {
      if (window.javaObj) {
        return javaObj.getUserHashId()
      }
      return window.userHashId
    },
    /*5.4.0 新增 handleNavigationActionHidden 方法，用于控制导航条上左侧返回按钮和右侧关闭按钮的显示与隐藏。 0 不隐藏，1隐藏，-1 忽略，默认显示**/
    handleNavigationActionHidden: function(back_hidden, close_hidden) {
      if (window.javaObj) {
        return javaObj.handleNavigationActionHidden(back_hidden, close_hidden)
      }
      _request('handleNavigationActionHidden', {
        back_hidden: back_hidden,
        close_hidden: close_hidden
      })
    } /*打开智齿客服*/,
    openSobot: function() {
      if (window.javaObj) {
        return javaObj.openSobot()
      }
      _request('openSobot')
    },
    /*打开登录页面，如需跳转回（刷新）wap页，要添加跳转目标的url*/
    login: function(url) {
      _request('login', { url: encodeURIComponent(url) })
    },
    /*打开注册页面*/
    register: function() {
      if (window.javaObj) {
        return javaObj.register()
      }
      _request('register')
    },
    /*回到主页,一级页面 3.0.0 版本开始启用*/
    goHome: function() {
      if (window.javaObj) {
        return javaObj.goHome()
      }
      _request('goHome')
    },
    /*判断是否登录*/
    hasLogged: function() {
      if (window.javaObj) {
        return javaObj.hasLogged()
      }
      return window.hasLogged
    },
    /*获取App版本号*/
    getAppVersion: function() {
      if (window.javaObj) {
        return javaObj.getAppVersion()
      }
      return window.appVersion
    },
    /*打开新的Webview页面, 新页面关闭与老页面没有影响*/
    openPage: function(url) {
      if (window.javaObj) {
        return javaObj.openPage(url)
      }
      _request('openPage', { url: encodeURIComponent(url) })
    },
    /*分享  前端在调用此方法时，需区分Android和iOS，Android 不需要encode，iOS需要encode。*/
    // 2.4.0 新增 description(此次分享的描述信息)
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
      // 1.7.0新增java接口方式调用App分享功能，1.7.0及以上版本可用。2.4.0 新增 description(被分享页面描述)
      if (window.javaObj) {
        return javaObj.share(text, imageUrl, platform, flag, title, url, description || '')
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
    /*关闭页面*/
    close: function() {
      _request('close')
    },
    /*打开指定项目*/
    openInvestment: function(id) {
      if (window.javaObj) {
        return javaObj.openInvestment(id)
      }
      _request('openInvestment', { id: id })
    },
    /* 打开指定项目列表 3.0.0 开始已废弃 */
    openInvestmentList: function() {
      if (window.javaObj) {
        return javaObj.openInvestmentList()
      }
      _request('openInvestmentList')
    },
    /*打开债券列表*/
    openDebtList: function() {
      if (window.javaObj) {
        return javaObj.openDebtList()
      }
      _request('openDebtList')
    },
    // 以下是1.5.5 版本新加功能
    /*打开优惠券列表*/
    openCouponList: function() {
      if (window.javaObj) {
        return javaObj.openCouponList()
      }
      _request('openCouponList')
    },
    /*打开我的账户页面*/
    openMyAccountPage: function() {
      if (window.javaObj) {
        return javaObj.openMyAccountPage()
      }
      _request('openMyAccountPage')
    },
    /*打开账户设置页面 1.7.0*/
    openUserSetPage: function() {
      if (window.javaObj) {
        return javaObj.openMyAccountPage()
      }
      _request('openUserSetPage')
    },
    /*打开资产明细页面*/
    openCapitalPage: function() {
      if (window.javaObj) {
        return javaObj.openCapitalPage()
      }
      _request('openCapitalPage')
    },
    /*打开充值页面*/
    openRechargePage: function() {
      if (window.javaObj) {
        return javaObj.openRechargePage()
      }
      _request('openRechargePage')
    },
    /*打开提现页面 */
    openWithdrawPage: function() {
      if (window.javaObj) {
        return javaObj.openWithdrawPage()
      }
      _request('openWithdrawPage')
    },
    /*打开投资记录页面, 3.2.0 及以后版本废弃，使用 openAppPage 方法打开 App 内页面 */
    openInvestRecordsPage: function() {
      if (window.javaObj) {
        return javaObj.openInvestRecordsPage()
      }
      _request('openInvestRecordsPage')
    },
    /*打开交易记录页面*/
    openTradeList: function() {
      if (window.javaObj) {
        return javaObj.openTradeList()
      }
      _request('openTradeList')
    },
    /*打开还款日历页面*/
    openRepaymentPage: function() {
      if (window.javaObj) {
        return javaObj.openRepaymentPage()
      }
      _request('openRepaymentPage')
    },
    /*打开项目列表并指向某一类别, type值与直投列表筛选规则相同，2->担保，5->融租，6->保理，7->收藏，100->省心*/
    openInvestListByType: function(type) {
      if (window.javaObj) {
        return javaObj.openInvestListByType(type)
      }
      _request('openInvestListByType', { type: encodeURIComponent(type) })
    }, // -----------------1.6.0新加
    /*获取设备OPEN-UDID号------iOS 专属*/ getDeviceUDID: function() {
      return window.deviceUDID
    },
    /*获取设备IDFA号，优先使用------iOS 专属*/
    getDeviceIDFA: function() {
      return window.deviceIDFA
    },
    /*获取设备IMEI号------Android 专属*/
    getDeviceIMEI: function() {
      if (window.javaObj) {
        return javaObj.getDeviceIMEI()
      }
    },
    /*获取设备AndroidID号，优先使用------Android 专属*/
    getDeviceAndroidID: function() {
      if (window.javaObj) {
        return javaObj.getDeviceAndroidID()
      }
    },
    // 以下是1.7.0 版本添加
    /*
         （已弃用）打开安全设置页面,data数据请参考/api/user/checkSecurity 接口Reponse内的data数据格式、命名、含义
          请保证数据data数据格式为json数据格式，例如：'{paymentPwdStatus: 1,bankStatus: 1,mobileStatus: 1,
                                                        realNameStatus: 1,mobile: 199****3483,cardNo: 3****************0,
                                                        realName: \u5f20\u4e09\u6d4b,securityStatus: 1}'
         */
    openSecuritySettingPage: function(data) {
      if (window.javaObj) {
        return javaObj.openSecuritySettingPage(data)
      }
      _request('openSecuritySettingPage', { data: data })
    },
    /*打开邀请好友页面*/
    openInvitePage: function() {
      if (window.javaObj) {
        return javaObj.openInvitePage()
      }
      _request('openInvitePage')
    },
    // 以下是2.2.0 版本添加
    // 评估风险等级成功
    evaluateSuccess: function() {
      if (window.javaObj) {
        return javaObj.evaluateSuccess()
      }
      _request('evaluateSuccess')
    },
    // 打开积分商城页面
    openIntegral: function() {
      if (window.javaObj) {
        return javaObj.openIntegral()
      }
      _request('openIntegral')
    },
    // 以下是2.3.0 版本添加
    // 打开我的积分页面
    openUserIntegrate: function() {
      if (window.javaObj) {
        return javaObj.openUserIntegrate()
      }
      _request('openUserIntegrate')
    },
    // 打开签到中心
    openSignCenter: function() {
      if (window.javaObj) {
        return javaObj.openSignCenter()
      }
      _request('openSignCenter')
    },
    /*3.2.0 添加，用来分享纯图片和纯文本，前端在调用此方法时，需区分Android和iOS，Android 不需要encode，iOS需要encode(方法里做encode处理，传入参数不要encode)。*/
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
        return javaObj.pureShare(info, platforms, type, thumb || '', actName || '', description || '')
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

    // 启用原生分享并设置原生分享信息（只针对当前 URL，当页面跳转后隐藏分享图标）， 前端在调用此方法时，需区分Android和iOS，Android 不需要encode，iOS需要encode。
    // 2.4.0 新增 description(此次分享的描述信息)
    // text 分享的内容，
    // imageUrl 分享中图片绝对地址
    // platform 分享的平台，目前支持（QQ， Qzone， Sina（新浪微博）， WechatSession（微信）， WechatTimeline（微信朋友圈））请填写全部5个并用英文逗号分隔
    // flag 分享的活动名称
    // title 分享的标题
    // url 分享的链接(不需要encode)
    // description 对此次分享的描述信息，分享成功后，会记录到后台
    showUmengShare: function(text, imageUrl, platform, flag, title, url, description) {
      if (platform instanceof Array) {
        platform = platform.join(',')
      }
      var appVersion = this.getAppVersion()
      if (window.javaObj && appVersion) {
        appVersion = parseInt(appVersion.replace(/\./g, ''))
        if (appVersion > 230) {
          return javaObj.showUmengShare(text, imageUrl, platform, flag, title, url, description || '')
        } else {
          return false
        }
      }
      _request('showUmengShare', {
        text: encodeURIComponent(text),
        platform: platform,
        imageurl: encodeURIComponent(imageUrl),
        flag: flag,
        title: encodeURIComponent(title),
        url: encodeURIComponent(url),
        description: encodeURIComponent(description || '')
      })
    },

    // 打开微信或其他App；例如打开微信appScheme="weixin://"
    openOtherApp: function(appScheme) {
      if (window.javaObj) {
        return javaObj.openOtherApp(appScheme)
      }
      _request('openOtherApp', { appScheme: appScheme })
    },
    // 以下是3.0.0版本添加
    // 新网银行业务处理成功， 业务类型 type 1 开户，2 激活账户，3 充值，4 提现，5 投资，6 认购债权，7 债权转让，
    //                                      8 绑卡，9 解绑卡，10 修改交易密码，11 更换预留手机号，12 表示充值并投资，13 表示充值并认购债权，
    //                                      14 投资智选计划，15 预约智选计划，16 充值并投资智选计划，17 充值并预约智选计划,18 取消预约智选计划，19 退出智选计划
    bankHandleSucceeded: function(type) {
      if (window.javaObj) {
        return javaObj.bankHandleSucceeded(type)
      }
      _request('bankHandleSucceeded', { type: type })
    },
    // 新网银行业务处理失败，业务类型 type 1 开户，2 激活账户，3 充值，4 提现，5 投资，6 认购债权，7 债权转让，
    //                                     8 绑卡，9 解绑卡，10 修改交易密码，11 更换预留手机号，12 表示充值并投资，13 表示充值并认购债权
    //                                      14 投资智选计划，15 预约智选计划，16 充值并投资智选计划，17 充值并预约智选计划，18 取消预约智选计划，19 退出智选计划
    bankHandleFailed: function(type) {
      if (window.javaObj) {
        return javaObj.bankHandleFailed(type)
      }
      _request('bankHandleFailed', { type: type })
    },
    // 新网银行业务处理中止，业务类型 type 1 开户，2 激活账户，3 充值，4 提现，5 投资，6 认购债权，7 债权转让，
    //                                     8 绑卡，9 解绑卡，10 修改交易密码，11 更换预留手机号，12 表示充值并投资，13 表示充值并认购债权
    //                                      14 投资智选计划，15 预约智选计划，16 充值并投资智选计划，17 充值并预约智选计划，18 取消预约智选计划，19 退出智选计划
    bankHandleAbort: function(type) {
      if (window.javaObj) {
        return javaObj.bankHandleAbort(type)
      }
      _request('bankHandleAbort', { type: type })
    },
    // 复制网页内容到App
    copyText: function(text) {
      if (window.javaObj) {
        return javaObj.copyText(text)
      }
      _request('copyText', { text: text })
    },
    // 隐藏左上角返回按钮
    hideBackButton: function() {
      if (window.javaObj) {
        return javaObj.hideBackButton()
      }
      _request('hideBackButton')
    },
    // 回到到App一级页面“发现”页面
    goDiscovery: function() {
      if (window.javaObj) {
        return javaObj.goDiscovery()
      }
      _request('goDiscovery')
    },
    // 回到到App一级页面“我的”页面
    goUserHome: function() {
      if (window.javaObj) {
        return javaObj.goUserHome()
      }
      _request('goUserHome')
    },
    // 直投项目进入排队页后，H5告知客户端, status 为1表示排队中，为2表示排队结束
    onInvestQueue: function(status) {
      if (window.javaObj) {
        return javaObj.onInvestQueue(status)
      }
      _request('onInvestQueue', { status: status })
    },
    // 刷新当前页面
    refreshPage: function() {
      if (window.javaObj) {
        return javaObj.refreshPage()
      }
      _request('refreshPage')
    },
    /* 项目列表 */
    goInvestList: function() {
      if (window.javaObj) {
        return javaObj.goInvestList()
      }
      _request('goInvestList')
    },
    // 以下是 3.0.2 新增
    // 答题结束，type 1 表示积分调查问卷答题完成
    onFillInComplete: function(type) {
      if (window.javaObj) {
        return javaObj.onFillInComplete(type)
      }
      _request('onFillInComplete', { type: type })
    },
    /* 3.1.0添加 通过 schema 打开直投项目列表或者智选项目列表;打开智选列表schema：itouzi://openWisdomList，打开直投列表schema：itouzi://projectList */
    openProjectList: function(schema) {
      if (window.javaObj) {
        return javaObj.openProjectList(schema)
      }
      _request('openProjectList', { schema: schema })
    },
    /* 3.1.1 添加，通过 scheme 打开指定原生 App 页面， scheme 定义参考：http://confluence.itouzi.com/pages/viewpage.action?pageId=83825545 */
    openAppPage: function(schema) {
      if (window.javaObj) {
        return javaObj.openAppPage(schema)
      }
      // 应 iOS 要求，将“?”替换为了“&”
      _request('openAppPage', { schema: schema.replace('?', '&') })
    },
    /*3.2.0 添加，用来分享纯图片和纯文本，前端在调用此方法时，需区分Android和iOS，Android 不需要encode，iOS需要encode(方法里做encode处理，传入参数不要encode)。*/
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
        return javaObj.pureShare(info, platforms, type, thumb || '', actName || '', description || '')
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
    /* 3.2.0 添加，启用原生分享(页面右上角原生分享图标)，来分享纯文本或纯图片（只针对当前 URL，当页面跳转后隐藏分享图标），前端在调用此方法时，需区分Android和iOS，Android 不需要encode，iOS需要encode(方法里做encode处理，传入参数不要encode)。 */
    // info 分享的内容，可以是图片 url 或者 纯文本信息，具体根据 type 确定
    // platforms 分享的平台，目前支持（QQ(不支持纯文本分享), Qzone， Sina（新浪微博）， WechatSession（微信）， WechatTimeline（微信朋友圈）），多个平台时用英文逗号","隔开；一个平台时直接跳到对应 App，多个平台时调起原生分享面板，传"all"时调起全平台的原生分享面板；注意 QQ 好友不支持纯文本分享
    // type 1 表示纯图片分享，此时 info 传要分享的图片 url； 2 表示纯文本分享，此时 info 传要分享的纯文本信息
    // thumb 分享图片的缩略图 url，分享纯文本时(type=2)，传 '' 即可
    // actName 分享的活动名称，分享成功后，后台统计使用，跟分享操作本身无关
    // description 分享的活动描述信息，分享成功后，后台统计使用，跟分享操作本身无关
    // 注意：QQ 好友不支持纯文本分享，分享纯文本不能使用该平台(platforms 不能传 all 和 QQ，因为 all 默认包含 QQ 平台)
    showPureUmengShare: function(info, platforms, type, thumb, actName, description) {
      if (platforms instanceof Array) {
        platforms = platform.join(',')
      }
      if (window.javaObj) {
        return javaObj.showPureUmengShare(info, platforms, type, thumb || '', actName || '', description || '')
      }
      _request('showPureUmengShare', {
        info: info,
        platforms: platforms,
        type: type,
        thumb: thumb || '',
        actName: actName || '',
        description: description || ''
      })
    },
    Platforms: {
      // 朋友圈
      WechatTimeline: 'WechatTimeline',
      // 微信
      WechatSession: 'WechatSession',
      // 新浪
      Sina: 'Sina',
      // 空间
      Qzone: 'Qzone',
      // QQ
      QQ: 'QQ'
    }
  }

  for (var key in imps) {
    if (window.javaObj && window.javaObj[key]) {
      imps[key] = (function(mn) {
        return function() {
          var appVersion = window.javaObj.getAppVersion()
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
            return window.javaObj[mn].apply(window.javaObj, args)
          }
        }
      })(key)
    }
  }
  return imps
})()

/**
 * 该方法非 app-bridge 内置方法
 *
 * 由于app 往webView 【嵌入】相关的bridge接口是异步完成的，所以web端js在调用相关方法时，需先确保【嵌入】动作已经完成
 *
 * 功能：判断【嵌入】已完成，执行回调函数告知调用方
 *
 * 适用场景：通常是在进入页面需要立即执行app-bridge相关方法的功能；例如需要在 DOMContentLoaded 后执行相关app操作
 */
window.iTouziAPP._ready_ = function(callback) {
  var timeLong = 0,
    tick = 200
  var si = setInterval(function() {
    timeLong += tick
    if (window.iTouziAPP && iTouziAPP.getAppVersion() !== undefined) {
      clearInterval(si)
      callback.call()
    }
    // 5s 超时
    if (timeLong >= 5000) {
      clearInterval(si)
    }
  }, tick)
}
