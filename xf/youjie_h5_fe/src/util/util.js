import Accounting from 'accounting'
import Dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
import Cookies from 'js-cookie'
import hhApp from '../assets/js/mallApp-bridge.js'

// 支持 UTC 时间操作
Dayjs.extend(utc)

export default {
  mlmUserName: '换换客',
  hhkEmail: 'huanhuanke@huanhuanyiwu.com', // 换换客客服邮箱
  storeName: process.env.VUE_APP_STORENAME, // 商城名字
  storeNameForShort: process.env.VUE_APP_STORENAME_SHORT, // 商城简称（主要出现在合同）
  // logo（分享时可用于默认图片）
  app_icon: 'https://static.itzcdn.com/app/hhmall_res/app_icon.png',
  activityNameTmp: '玩转六一',
  getDomainA: {
    domain: document.domain
      .split('.')
      .slice(-2)
      .join('.')
  },

  getShareImage() {
    let imageUrl = window.location.origin + window.location.pathname + require('../assets/image/hh-icon/wx-logo.png')
    return imageUrl
  },

  /**
   * localstrage
   */
  fetch(key) {
    return JSON.parse(window.localStorage.getItem(key) || '[]')
  },
  save(key, value) {
    window.localStorage.setItem(key, JSON.stringify(value))
  },

  /**
   *  arrayUnique: 数组去重
   */
  arrayUnique(arr) {
    return Array.from(new Set(arr))
  },

  /**
   * @param start  开始字符展示位置
   * @param end 结束字符展示位置
   * @param target 目标字符
   */
  replaceStr(target, start, end, length) {
    let str = ''
    if (start) {
      str = target.substr(start, length) + '***'
    } else if (end) {
      str = '***' + target.substr(end, length)
    } else {
      str = target.substr(0, 1) + '***' + target.substr(target.length - 1, 1)
    }
    return str
  },

  /**
   * Cookie
   */
  getCookie(key) {
    return Cookies.get(key)
  },
  setCookie(key, val, options) {
    if (typeof val === 'object') {
      val = JSON.stringify(val)
    }
    return Cookies.set(key, val, options)
  },
  removeCookie(key, options) {
    return Cookies.remove(key, options)
  },

  /**
   * fmt 显示的格式
   * date 日期
   */
  formatDate(format, date) {
    return Dayjs(date ? date * 1e3 : Date.now()).format(format)
  },

  /**
   * 将时间戳转换为 格式化的 北京时间
   */
  formatToBJDate(format, timestamp) {
    // + 8 *60 *60 为北京时间
    return Dayjs(timestamp + 28800000)
      .utc()
      .format(format)
  },

  /**
   * 转为千分位
   * keepDecimal 为真时保留小数点最后的0
   */
  formatMoney(price, keepDecimal) {
    price = Accounting.formatNumber(price, 2)
    if (!keepDecimal) {
      price = price.replace(/(\.00|0)$/, '')
    }
    return price
  },

  /**
   * 格式化浮点数
   * noDecimal 为真时去除小数点最后的0
   */
  formatFloat(float = 0, noDecimal = true) {
    if (isNaN(float)) {
      console.error('Not a Number! >>> util.formatFloat')
      float = 0
    }
    float = Number(float).toFixed(2)
    if (noDecimal) {
      float = float.replace(/(\.00|0)$/, '')
    }
    return float
  },

  /**
   * 前面补0
   */
  padLeftZero(str) {
    str = String(str)
    return ('00' + str).substr(str.length)
  },

  /**
   * 手机号脱敏
   */
  formatPhone(phone) {
    return phone.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
  },

  /**
   * 取得URL中的某参数
   */
  getUrlKey(url, name) {
    return (
      decodeURIComponent(
        (new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(url) || ['', ''])[1].replace(/\+/g, '%20')
      ) || null
    )
  },

  /**
   * getOpenBrowser: 获取打开的设备
   * ua : 1-ios ; 2-andriod ; 3-PC
   */
  getOpenBrowser() {
    let browser = {
      versions: (function() {
        var u = navigator.userAgent,
          app = navigator.appVersion
        return {
          //移动终端浏览器版本信息
          trident: u.indexOf('Trident') > -1, //IE内核
          presto: u.indexOf('Presto') > -1, //opera内核
          webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
          gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
          mobile: !!u.match(/AppleWebKit.*Mobile.*/), //是否为移动终端
          ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
          android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或uc浏览器
          iPhone: u.indexOf('iPhone') > -1, //是否为iPhone或者QQHD浏览器
          iPad: u.indexOf('iPad') > -1, //是否iPad
          webApp: u.indexOf('Safari') == -1 //是否web应该程序，没有头部与底部
        }
      })(),
      language: (navigator.browserLanguage || navigator.language).toLowerCase()
    }
    let returnUa = undefined
    if (browser.versions.mobile) {
      //判断是否是移动设备打开。
      let ua = navigator.userAgent.toLowerCase() //获取判断用的对象
      //在微信中打开
      if (ua.match(/MicroMessenger/i) == 'micromessenger') {
      }
      //在新浪微博客户端打开
      if (ua.match(/WeiBo/i) == 'weibo') {
      }
      //在QQ空间打开
      if (ua.match(/QQ/i) == 'qq') {
      }
      //是否在IOS浏览器打开
      if (browser.versions.ios) {
        returnUa = 1
      }
      //是否在安卓浏览器打开
      if (browser.versions.android) {
        returnUa = 2
      }
    } else {
      //否则就是PC浏览器打开
      returnUa = 3
    }
    return returnUa
  },

  stopPrevent(event) {
    let e = event || window.event
    if (e.preventDefault) {
      e.preventDefault()
    } else {
      window.event.returnValue = false //IE
    }
  },

  fillTheScreen(obj) {
    const isWX = /micromessenger/.test(navigator.userAgent.toLowerCase())
    // why? document.documentElement.clientHeight - document.documentElement.offsetHeight
    let height = isWX ? document.documentElement.clientHeight : document.documentElement.offsetHeight
    if (!obj.target || !obj.totalHeight) return
    height = 1 - obj.totalHeight / height
    obj.target.style.height = height * 100 + 'vh'
  },

  // requestAnimationFrame
  requestAnimationFrame() {
    return (
      window.requestAnimationFrame ||
      window.webkitRequestAnimationFrame ||
      window.mozRequestAnimationFrame ||
      window.oRequestAnimationFrame ||
      window.msRequestAnimationFrame
    )
  },
  /**
   * 节流（控制function在高频操作中 以一定的频率触发）{例如监听 scroll 事件}，下边的实现 直接使用requestAnimationFrame保证触发频率>=16.7ms
   * 防抖（控制function在高频操作 结束后触发{如根据用户输入即时计算反馈}，若在wait时间内未操作，才可触发fuc）
   */
  throttle(func) {
    var ticking = false
    var raf = this.requestAnimationFrame()

    return function() {
      if (!ticking) {
        ticking = true

        raf(function() {
          ticking = false
          func()
        })
      }
    }
  },
  debounce(func, wait) {
    var timeout
    return function() {
      clearTimeout(timeout)
      // 指定 xx ms 后触发真正想进行的操作 handler
      timeout = setTimeout(func, wait)
    }
  },

  /**
   * beginAt 开始时间（时间戳）
   * endAt 结束时间（时间戳）
   */
  activityStatus(beginAt, endAt) {
    let status = -1 // (0: 未开始；1: 进行中；2: 已过期)
    let timestamp = Date.parse(new Date()) / 1000
    if (beginAt > timestamp) {
      status = 0
    } else if (timestamp > beginAt && timestamp < endAt) {
      status = 1
    } else if (timestamp > endAt) {
      status = 2
    }
    return status
  },

  /**
   * interval 时间间隔（单位为s）
   * 把秒数换为*天*时*分*秒的时间格式
   */
  formatTimeInterval(interval) {
    let format = null
    let day = parseInt(interval / 60.0 / 60.0 / 24.0)
    let hour = parseInt((interval / 60 / 60) % 24)
    let minute = parseInt((interval / 60) % 60)
    let second = interval % 60
    format = day + ' 天 ' + hour + ' 时 ' + minute + ' 分 ' + second + ' 秒'
    return format
  },

  /**
   * getunreadCount: 获取未读消息数
   */
  getunreadCount(zhiManager, scoped, key) {
    zhiManager.on('unread.count', function(data) {
      console.log(data)
    })
    zhiManager.on('receivemessage', function(ret) {
      scoped.key = ret
      console.log(ret)
    })
  },
  /**
   * 编辑uri中get参数
   */
  updateGetParameter(uri, key, value) {
    if (!value) {
      return uri
    }
    var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i')
    var separator = uri.indexOf('?') !== -1 ? '&' : '?'
    if (uri.match(re)) {
      return uri.replace(re, '$1' + key + '=' + value + '$2')
    } else {
      return uri + separator + key + '=' + value
    }
  },

  /**
   * 拆分商品价个、免限额的金额前端
   */
  splitMoneyLint(data, type) {
    if (Array.isArray(data)) {
      data.forEach((val, key) => {
        this.splitMoneyLint(data[key], type)
      })
    } else if (data) {
      let name = type ? type : 'current_price'
      data.HB_SHOW = data.money_line
      if (data.HB_SHOW == -1 || Number(data.HB_SHOW) > Number(data[name])) {
        data.HB_SHOW = data[name]
      }
      data['MONEY_SHOW'] = data[name] - data.HB_SHOW
    }
    return data
  },
  /**
   * fromatArray: 格式化数组
   */
  fromatArray(delimiter, arrays) {
    let data = ''
    if (delimiter) {
      data = arrays.join(delimiter)
    }
    return delimiter ? data : arrays
  },
  // 获取deviceID
  getDeviceID() {
    let isHHApp = window.navigator.userAgent.indexOf('_YOUJIEMALL_') > -1 ? true : false
    let deviceID = ''
    if (isHHApp) {
      deviceID = this.getOpenBrowser() === 1 ? hhApp.getDeviceIDFA() : hhApp.getDeviceAndroidID()
    } else {
      deviceID = this.getOpenBrowser() === 1 ? 'ios' : this.getOpenBrowser() === 2 ? 'android' : 'pc'
    }
    return deviceID
  },
  // 获取商城App版本号 et: 040
  getHhAppVersion() {
    // 通过UA 获取App版本号
    const reg = /(\_YOUJIEMALL\_IOS\_|\_YOUJIEMALL\_ANDROID\_|\_YOUJIEMALL\_)([\d\.]+)/
    const ua = window.navigator.userAgent
    const versionStr = ua.match(reg) ? ua.match(reg)[2] : '0'

    return parseInt(versionStr.replace(/\./g, ''))
  },
  /**
   * 滚动动画
   * element：dom元素
   * to(px)：滚动到哪个位置
   * duration(ms): 动画时长
   */
  scrollTopAni(element, to, duration) {
    if (duration <= 0) {
      element.scrollTop = to
      return
    }

    const diff = to - element.scrollTop
    const perTick = (diff / duration) * 16.67
    const raf = this.requestAnimationFrame()

    const animationLoop = () => {
      if ((diff >= 0 && element.scrollTop >= to) || (diff <= 0 && element.scrollTop <= to)) return
      raf(() => {
        element.scrollTop += perTick
        animationLoop()
      })
    }
    animationLoop()
  },
  /**
   * [timeSync 心跳（折半）时间同步]
   * @param  {int}      beginTime   [当前时间]ms
   * @param  {int}      endTime     [结束时间]ms
   * @param  {function} synchFunc   [时间同步方法] 需要返回promise
   * @param  {function} iterator    [每次同步完时间要做的事]
   * @return undefined
   */
  timeSync({ beginTime, endTime, synchFunc, iterator }) {
    let obj = {
      timeEnd: endTime,
      timeNow: beginTime
    }
    const timeGap = function() {
      return obj.timeEnd - obj.timeNow
    }
    // 计时器
    let timer
    // 默认时间校准方式为获取本地 时间戳
    const timeSynch =
      synchFunc ||
      (() => {
        return Promise.resolve(new Date().getTime())
      })
    const timerFn = function() {
      timer = setTimeout(function() {
        clearTimeout(timer)
        timeSynch().then(res => {
          obj.timeNow = res
          iterator(obj)
          reSynch()
        })
      }, timeGap() / 2)
    }
    const reSynch = function() {
      const t = timeGap()
      // 20s 内就不再同步了
      t >= 20000 && timerFn()
    }

    reSynch()

    const clearTimer = () => {
      if (timer) clearTimeout(timer)
    }

    return clearTimer
  },
  /**
   * 取得 当前时间 (ms)
   * return promise
   */
  getNowTime() {
    const requestUrl = window.location.href

    return new Promise((resolve, reject) => {
      // 发送请求的时间点
      const request_time = new Date().getTime()
      const xhr = new XMLHttpRequest()
      xhr.onreadystatechange = function() {
        if (4 == xhr.readyState) {
          // 收到回复的时间点
          const response_time = new Date().getTime()
          // 单程的网络耗时
          const network_time = (response_time - request_time) / 2

          // 服务器端给的时间点
          const t = (xhr.getResponseHeader('date') && new Date(xhr.getResponseHeader('date'))) || new Date()
          xhr.onreadystatechange = null
          /**
           * time 加入网络耗时后校准的服务器时间，
           * origin_time 未校准网络耗时的服务器时间
           */
          resolve({
            time: t.getTime() + network_time,
            origin_time: t.getTime()
          })
        }
      }
      xhr.open('HEAD', requestUrl, !0)
      xhr.send(null)
    })
  },

  // 使用canvas压缩图片并转换base64
  getImgToBase64(url, callback) {
    //将图片转换为Base64并压缩
    var canvas = document.createElement('canvas'),
      ctx = canvas.getContext('2d'),
      img = new Image()
    img.crossOrigin = 'Anonymous' //表示允许跨域
    img.onload = function() {
      var width = img.width // 图片原始宽度
      var height = img.height // 图片原始长度
      // 宽高比例
      var scale = width / height
      var widthResult = 375
      var heightResult = parseInt(widthResult / scale)
      canvas.height = heightResult // 转换图片像素大小
      canvas.width = widthResult
      // 将图片的（0, 0）坐标到(0 + width , 0+ height)坐标也就是整张图片 画到 canvas（0, 0）到（widthResult，heightResult）也就是整个canvas内
      //drawImage是canvas绘制图案的API
      ctx.drawImage(img, 0, 0, width, height, 0, 0, widthResult, heightResult) //drawImage是canvas绘制图案的API
      var dataURL = canvas.toDataURL('image/png', 0.7) //通过canvas获取图片的base64的URL
      callback(dataURL)
      canvas = null
    }
    img.src = url
  },

  /**
   * 指定时间点到来时 触发回调
   * params: targetTime: 目标时间 ms
   * return promise
   * TODO: 针对定时器的系统级处理方案？？？
   */
  async callWhenTimeComesTo(targetTime) {
    const timeNow = await this.getNowTime()
    await new Promise((resolve, reject) => {
      this.timeSync({
        beginTime: timeNow,
        endTime: targetTime,
        synchFunc: this.getNowTime,
        iterator: ({ timeNow, timeEnd }) => {
          const timeLeft = targetTime - timeNow <= 0 ? 0 : targetTime - timeNow
          if (timeLeft < 20000) {
            setTimeout(() => {
              resolve()
            }, timeLeft)
          }
        }
      })
    })
  },

  checkIDCard(val) {
    /**
     * 校验大陆身份证
     * 通过对身份证号码的地区码、出生日期码、校验码进行校验，简单判断是否为有效身份证号码
     *
     * @param      {string}  val     The ID card
     * @return     {boolean}
     */
    const checkDIDCard = val => {
      // 检查身份证第18位是否符合规格
      const checkCode = val => {
        var p = /^[1-9]\d{5}(18|19|20)\d{2}((0[1-9])|(1[0-2]))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/,
          factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2],
          parity = [1, 0, 'X', 9, 8, 7, 6, 5, 4, 3, 2],
          code = val.substring(17)

        if (p.test(val)) {
          var sum = 0
          for (var i = factor.length - 1; i >= 0; i--) {
            sum += val[i] * factor[i]
          }

          if (parity[sum % 11] == code.toUpperCase()) {
            return true
          }
        }

        return false
      }

      // 检查日期
      const checkDate = val => {
        var pattern = /^(18|19|20)\d{2}((0[1-9])|(1[0-2]))(([0-2][1-9])|10|20|30|31)$/
        if (pattern.test(val)) {
          var year = val.substring(0, 4),
            month = val.substring(4, 6),
            date = val.substring(6, 8),
            date2 = new Date(year + '-' + month + '-' + date)

          if (date2 && date2.getMonth() == parseInt(month) - 1) {
            return true
          }
        }
        return false
      }

      // 校验省份
      const checkProv = val => {
        var pattern = /^[1-9][0-9]/
        var provs = {
          11: '北京',
          12: '天津',
          13: '河北',
          14: '山西',
          15: '内蒙古',
          21: '辽宁',
          22: '吉林',
          23: '黑龙江',
          31: '上海',
          32: '江苏',
          33: '浙江',
          34: '安徽',
          35: '福建',
          36: '江西',
          37: '山东',
          41: '河南',
          42: '湖北',
          43: '湖',
          44: '广东',
          45: '广西',
          46: '海南',
          51: '四川',
          52: '贵州',
          53: '云南',
          54: '西藏',
          50: '重庆',
          61: '陕',
          62: '甘肃',
          63: '青海',
          64: '宁夏',
          65: '新疆',
          71: '台湾',
          81: '香港',
          82: '澳门'
        }
        if (pattern.test(val)) {
          if (provs[val]) {
            return true
          }
        }
        return false
      }

      if (checkCode(val)) {
        var date = val.substring(6, 14)
        if (checkDate(date)) {
          if (checkProv(val.substring(0, 2))) {
            return true
          }
        }
      }
      return false
    }

    // 检测台湾省份证
    const checkTIDCard = val => {
      const cardConf = {
        A: '10',
        B: '11',
        C: '12',
        D: '13',
        E: '14',
        F: '15',
        G: '16',
        H: '17',
        I: '34',
        J: '18',
        K: '19',
        M: '21',
        N: '22',
        O: '35',
        P: '23',
        Q: '24',
        R: '25',
        S: '26',
        T: '27',
        U: '28',
        V: '29',
        W: '32',
        X: '30',
        Z: '33'
      }
      // 10位身份证
      if (val.length != 10) {
        return false
      }
      const f = val.substring(0, 1),
        s = val.substring(1, 2),
        end = val.substring(9, 10)

      // 第一位地区码  见cardConf
      // 第二位性别码  1-男  2-女
      if (!cardConf[f] || ['1', '2'].indexOf(s) == -1) {
        return false
      }


      /**
       * 检验通算值
       * 通算值 =
       *     首字母对应的第一位验证码
       *   + 首字母对应的第二位验证码 * 9 
       *   + 性别码 * 8 
       *   + 第二位数字 * 7 
       *   + 第三位数字 * 6 
       *   + 第四位数字 * 5 
       *   + 第五位数字 * 4 
       *   + 第六位数字 * 3 
       *   + 第七位数字 * 2 
       *   + 第八位数字 * 1，
       *
       * 最后一位数 =10- 通算值的末尾数。
       */
      // = 
      const valArr = val.split('')
      let sum = 0
      for (var i = 0; i < valArr.length; i++) {
        if (i == 0) {
          sum += Number(cardConf[valArr[0]].substring(0, 1))
          continue
        }
        if (i == 1) {
          sum += cardConf[valArr[0]].substring(1, 2) * 9
          continue
        }

        sum += valArr[i - 1] * (10 - i)
      }

      const testEnd =
        10 -
        sum
          .toString()
          .split('')
          .reverse()[0]
      if (valArr[valArr.length - 1] == (testEnd == 10 ? 0 : testEnd)) {
        return true
      } else {
        return false
      }
    }

    return checkDIDCard(val) || checkTIDCard(val)
  }
}
