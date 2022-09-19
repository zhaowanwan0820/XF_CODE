/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayWithHoles.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/classCallCheck.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/classCallCheck.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/createClass.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/createClass.js ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

module.exports = _createClass;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js ***!
  \*********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _iterableToArrayLimit(arr, i) {
  var _arr = [];
  var _n = true;
  var _d = false;
  var _e = undefined;

  try {
    for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}

module.exports = _iterableToArrayLimit;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/nonIterableRest.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/nonIterableRest.js ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance");
}

module.exports = _nonIterableRest;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/slicedToArray.js":
/*!**************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/slicedToArray.js ***!
  \**************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayWithHoles = __webpack_require__(/*! ./arrayWithHoles */ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js");

var iterableToArrayLimit = __webpack_require__(/*! ./iterableToArrayLimit */ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js");

var nonIterableRest = __webpack_require__(/*! ./nonIterableRest */ "./node_modules/@babel/runtime/helpers/nonIterableRest.js");

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;

/***/ }),

/***/ "./src/js/base.js":
/*!************************!*\
  !*** ./src/js/base.js ***!
  \************************/
/*! exports provided: preLoadImg, showToast, unitObj, observeData, rangeRandom, emptyReg, showPop, triggerScheme, trackPrefix, isAndroid, isIOS */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "preLoadImg", function() { return preLoadImg; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "showToast", function() { return showToast; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "unitObj", function() { return unitObj; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "observeData", function() { return observeData; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "rangeRandom", function() { return rangeRandom; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "emptyReg", function() { return emptyReg; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "showPop", function() { return showPop; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "triggerScheme", function() { return triggerScheme; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "trackPrefix", function() { return trackPrefix; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isAndroid", function() { return isAndroid; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isIOS", function() { return isIOS; });
FastClick.attach(document.body);

function setLoad() {
  var percent = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
  $('#loadProgress').css({
    "width": percent + "%"
  });
  $('#loadRate').text(percent);
} //预加载图片


function preLoadImg(imgArr, completeCallBack) {
  var len = imgArr.length;

  if (len == 0) {
    completeCallBack.apply(null, [imgArr]);
    return;
  }

  var count = 0;

  function hook(newImg) {
    count++;
    var percent = parseInt(count * 100 / len);
    setLoad(percent);

    if (count >= len) {
      completeCallBack.apply(null, [imgArr]);
    } // console.log(Date.now(),newImg.currentSrc);//检测图片路径是不是有问题？

  }

  imgArr.forEach(function (imgPath, index) {
    var newImg = new Image();
    newImg.src = imgPath; // console.log(newImg.src)

    if (newImg.complete) {
      hook(newImg);
    } else {
      newImg.onload = function () {
        hook(newImg);
      };

      newImg.onerror = function () {
        console.error('图片路径出错', newImg);
        hook(newImg);
      };
    }
  });
}

var _flexDisplay = function () {
  var newDiv = $('<div class="flexCssTmp"></div>');
  newDiv.appendTo('body');
  var flexDisplay = newDiv.css('display');
  newDiv.remove();
  return flexDisplay;
}();

$.ajaxSetup({
  "headers": {
    'Cache-Control': "no-cache"
  }
});
$.extend($.fn, {
  "flexDisplay": function flexDisplay() {
    this.css({
      "display": _flexDisplay
    });
    return this;
  }
});
function showToast(html, duration) {
  var toast = $('#toast');
  toast.flexDisplay();
  var content = toast.find('.toastContent');
  content.html(html);

  if (typeof duration != "undefined") {
    setTimeout(function () {
      toast.hide();
    }, duration);
  }
}
var unitObj = {
  urlPara: function () {
    function getParaObj(urlStr) {
      var paraObj = {};
      var curParaMap = null;
      var curParaKey = "";
      var curparaVal = "";
      var parArr = urlStr.split('&');

      for (var i = 0, max = parArr.length; i < max; i++) {
        curParaMap = parArr[i].split('=');
        curParaKey = curParaMap[0];

        if (typeof curParaMap[1] != "undefined") {
          curparaVal = encodeURIComponent(curParaMap[1]);
        } else {
          curparaVal = "";
        }

        paraObj[curParaKey] = curparaVal;
      }

      return paraObj;
    }

    return {
      getPara: function getPara(urlStr, parName) {
        if (arguments.length = 1) {
          parName = urlStr;
          urlStr = location.search.slice(1);
        }

        return getParaObj(urlStr)[parName];
      },
      getAll: function getAll(urlStr) {
        if (typeof urlStr == "undefined") {
          urlStr = location.search.slice(1);
        }

        return getParaObj(urlStr);
      }
    };
  }()
  /**
   * 监听对象属性变化
   * @param obj
   * @param map
   */

};
function observeData(obj, map) {
  var defaultConfig = {
    "configurable": true,
    "enumerable": true
  };
  var config = null;
  var mapValue = {};
  var oldMapValue = {};
  var mapItem = null;

  for (var i in map) {
    if (map.hasOwnProperty(i)) {
      mapItem = map[i];
      oldMapValue[i] = mapValue[i] = mapItem.initVal;
      config = $.extend({}, defaultConfig, {
        "set": function (i) {
          return function (value) {
            var oldValue = oldMapValue[i] = mapValue[i];
            var returnVal;
            map[i].set && (returnVal = map[i].set.apply(obj, [value, oldValue, i]));

            if (typeof returnVal != "undefined") {
              value = returnVal;
            }

            mapValue[i] = value;
            map[i].setCallBack && map[i].setCallBack.apply(obj, [value, oldValue, i]);
          };
        }(i),
        "get": function (i) {
          return function () {
            return mapValue[i];
          };
        }(i)
      });
      Object.defineProperty(obj, i, config);
    }
  }

  obj.__recoverValue = function (property) {
    obj[property] = oldMapValue[property];
  };

  return obj;
}
function rangeRandom(max) {
  var min = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var add = max - min;
  return min + Math.round(add * Math.random());
}
var emptyReg = /^\s*$/;
function showPop(content) {
  var title = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : "温馨提示";
  var popWrap = $('#popWrap');
  popWrap.flexDisplay();

  if (!showPop.init) {
    showPop.init = true;
    popWrap.find('.popClose').on('click', function () {
      popWrap.hide();
    });
  }

  popWrap.find('.title').text(title);
  popWrap.find('.popInner .text').text(content);
}
showPop.init = false; //通过iframe触发scheme

function triggerScheme(scheme) {
  var newIframe = $("<iframe src=\"".concat(scheme, "\" style=\"display: none;\"></iframe>")).appendTo('body');
  newIframe.remove();
}
var trackPrefix = "2019春节助力活动_";
;

(function () {
  var htmlElement = document.documentElement;
  var winWidth = $(window).width();
  var htmlFontSize = parseInt(getComputedStyle(htmlElement).fontSize);
  var targetFontSize = winWidth / 375 * 100;

  if (targetFontSize > htmlFontSize + 10) {
    htmlElement.style.fontSize = "".concat(targetFontSize, "px");
  }
})();

var isAndroid = function () {
  var userAgent = navigator.userAgent;
  var isAndroid = false;

  if (userAgent.indexOf('Android') > -1 || userAgent.indexOf('Adr') > -1) {
    isAndroid = true;
  }

  return isAndroid;
}();
var isIOS = function () {
  var userAgent = navigator.userAgent;
  var isIOS = false;

  if (/\(i[^;]+;( U;)? CPU.+Mac OS X/.test(userAgent) && !isAndroid) {
    isIOS = true;
  }

  return isIOS;
}();

/***/ }),

/***/ "./src/js/countDown.js":
/*!*****************************!*\
  !*** ./src/js/countDown.js ***!
  \*****************************/
/*! exports provided: CountDown */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "CountDown", function() { return CountDown; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);


var countDownTpl = $('#countDownTpl').html();

var CountDown =
/*#__PURE__*/
function () {
  function CountDown() {
    var currentVal = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
    var nextVal = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : currentVal;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, CountDown);

    this.currentVal = currentVal;
    this.nextVal = nextVal;
    this.domInfo = {};
    this.init();
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(CountDown, [{
    key: "init",
    value: function init() {
      this.initDom();
    }
  }, {
    key: "initDom",
    value: function initDom() {
      var newDom = this.dom = $(countDownTpl);
      this.domInfo.topCurrent = $('span.top.current', newDom);
      this.domInfo.topNext = $('span.top.next', newDom);
      this.domInfo.bottomCurrent = $('span.bottom.current', newDom);
      this.domInfo.bottomNext = $('span.bottom.next', newDom);
      this.updatePanel();
    }
  }, {
    key: "setVal",
    value: function setVal() {
      var currentVal = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.currentVal;
      var nextVal = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.nextVal;
      Object.assign(this, {
        currentVal: currentVal,
        nextVal: nextVal
      });
      this.updatePanel();
      return this;
    }
  }, {
    key: "updatePanel",
    value: function updatePanel() {
      var _this$domInfo = this.domInfo,
          topCurrent = _this$domInfo.topCurrent,
          topNext = _this$domInfo.topNext,
          bottomCurrent = _this$domInfo.bottomCurrent,
          bottomNext = _this$domInfo.bottomNext;
      topCurrent.add(bottomCurrent).find('i').text(this.currentVal);
      topNext.add(bottomNext).find('i').text(this.nextVal);
      return this;
    }
  }, {
    key: "executeAni",
    value: function executeAni() {
      var _this = this;

      var callBack = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : $.noop;
      var nextVal = arguments.length > 1 ? arguments[1] : undefined;

      if (typeof nextVal != "undefined") {
        this.setVal(undefined, nextVal);
      }

      var _this$domInfo2 = this.domInfo,
          topCurrent = _this$domInfo2.topCurrent,
          topNext = _this$domInfo2.topNext,
          bottomCurrent = _this$domInfo2.bottomCurrent,
          bottomNext = _this$domInfo2.bottomNext;
      var interval = 200;
      bottomCurrent.get(0).offsetWidth;
      bottomCurrent.addClass('ani'); // debugger;

      setTimeout(function () {
        topNext.addClass('ani');
        setTimeout(function () {
          bottomCurrent.add(topNext).removeClass('ani');

          _this.setVal(_this.nextVal);

          callBack.apply(_this, []);
        }, interval);
      }, interval);
    }
  }]);

  return CountDown;
}();



/***/ }),

/***/ "./src/js/main.js":
/*!************************!*\
  !*** ./src/js/main.js ***!
  \************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _base__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./base */ "./src/js/base.js");
/* harmony import */ var _countDown__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./countDown */ "./src/js/countDown.js");



var getPara = _base__WEBPACK_IMPORTED_MODULE_1__["unitObj"].urlPara.getPara;
var showPanelDeferred = $.Deferred(); //loading结束时，再开始展示的效果

var initAjaxDeferred = initAjax();
var initAjaxData = null;
var imgPrefix = "/static/v3/images/activity/spring_festival_2019/main/";
var satisfyMinLimit = false; //是否满足最低投资档位

if (_base__WEBPACK_IMPORTED_MODULE_1__["isIOS"]) {
  Object(_base__WEBPACK_IMPORTED_MODULE_1__["triggerScheme"])('firstp2p://api?type=rightbtn&title=');
}

if (_base__WEBPACK_IMPORTED_MODULE_1__["isAndroid"]) {
  // console.log('isAndroid');
  $('#body_tag').addClass('isAndroid');
}

;

(function () {
  var preloadImgArr = ['alreadyLike.png', 'availableLike.png', 'codePoint.png', 'like_left_pointer.png', 'like_right_pointer.png', 'loadInner.png', 'multiple_bg_available.png', 'multiple_bg_disable.png', 'multiple_icon_available.png', 'multiple_icon_disable.png', 'notLike.png', 'people01.jpg', 'people02.jpg', 'people03.jpg', 'people04.jpg', 'peopleTitle.png', 'popBg.png', 'popClose.png', 'popLeft_bg.png', 'popRight_bg.png', 'progress.png', 'ruleBg.png', 'tabLi_active.png', 'tabLi_dim.png', 'topBanner.jpg', 'triangle.png', 'wx_circle.png', 'wx_people.png'];
  preloadImgArr = preloadImgArr.map(function (item, index) {
    return "".concat(imgPrefix).concat(item);
  });
  Object(_base__WEBPACK_IMPORTED_MODULE_1__["preLoadImg"])(preloadImgArr, function (imgArr) {
    // console.log(imgArr)
    setTimeout(function () {
      initAjaxDeferred.done(function (response) {
        if (response.code == 0) {
          initAjaxData = response;
          $('#loadingWrap').hide();
          initPanel(response.data);
          showPanelDeferred.resolve(); // $(window).scrollTop($('#timerTitle').offset().top);
        } else if (response.code == 20001) {
          Object(_base__WEBPACK_IMPORTED_MODULE_1__["triggerScheme"])('firstp2p://api?type=native&name=login');
        } else {
          Object(_base__WEBPACK_IMPORTED_MODULE_1__["showToast"])(response.message);
        }
      });
    }, imgArr.length == 0 ? 0 : 400);
  });
})();

var _countdownFn = null; //倒计时

;

(function () {
  var dayTimer = $('#dayTimer');
  var hourTimer = $('#hourTimer');
  var minuteTimer = $('#minuteTimer');
  var secondTimer = $('#secondTimer');
  var dtCountDown01 = new _countDown__WEBPACK_IMPORTED_MODULE_2__["CountDown"]();
  var dtCountDown02 = new _countDown__WEBPACK_IMPORTED_MODULE_2__["CountDown"]();
  var htCountDown01 = new _countDown__WEBPACK_IMPORTED_MODULE_2__["CountDown"]();
  var htCountDown02 = new _countDown__WEBPACK_IMPORTED_MODULE_2__["CountDown"]();
  var mtCountDown01 = new _countDown__WEBPACK_IMPORTED_MODULE_2__["CountDown"]();
  var mtCountDown02 = new _countDown__WEBPACK_IMPORTED_MODULE_2__["CountDown"]();
  var countDownWrap = $('#countDownWrap');
  var timerTitle = $('#timerTitle');
  /*const stCountDown01=new CountDown();
  const stCountDown02=new CountDown();*/

  dayTimer.prepend(dtCountDown01.dom, dtCountDown02.dom);
  hourTimer.prepend(htCountDown01.dom, htCountDown02.dom);
  minuteTimer.prepend(mtCountDown01.dom, mtCountDown02.dom); // secondTimer.prepend(stCountDown01.dom,stCountDown02.dom);

  function splitTime(countdownTime) {
    var day = Math.floor(countdownTime / (60 * 60 * 24));
    countdownTime = countdownTime % (60 * 60 * 24);
    var hour = Math.floor(countdownTime / (60 * 60));
    countdownTime = countdownTime % (60 * 60);
    var minute = Math.floor(countdownTime / 60);
    countdownTime = countdownTime % 60;
    var second = countdownTime;
    var returnObj = {
      day: day,
      hour: hour,
      minute: minute,
      second: second
    };
    Object.keys(returnObj).forEach(function (item) {
      returnObj[item] = String(returnObj[item]).padStart(2, 0);
    });
    return returnObj;
  }

  function setCountDownVal(timeObj) {
    var isAni = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    Object.keys(timeObj).forEach(function (item) {
      var countDown01 = null;
      var countDown02 = null;

      var _String$split = String(timeObj[item]).split(""),
          _String$split2 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_String$split, 2),
          val01 = _String$split2[0],
          val02 = _String$split2[1];

      switch (item) {
        case "day":
          {
            countDown01 = dtCountDown01;
            countDown02 = dtCountDown02;
            break;
          }
          ;

        case "hour":
          {
            countDown01 = htCountDown01;
            countDown02 = htCountDown02;
            break;
          }
          ;

        case "minute":
          {
            countDown01 = mtCountDown01;
            countDown02 = mtCountDown02;
            break;
          }
          ;
      }

      if (item != "second") {
        if (!isAni) {
          countDown01.setVal(val01, val01);
          countDown02.setVal(val02, val02);
        } else {
          if (val01 != countDown01.currentVal) {
            countDown01.executeAni($.noop, val01);
          }

          if (val02 != countDown02.currentVal) {
            countDown02.executeAni($.noop, val02);
          }
        }
      }
    });
  }

  _countdownFn = function countdownFn(data) {
    var status = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : data.status;
    var awardCode = data.awardCode;
    var ifHasAward = !_base__WEBPACK_IMPORTED_MODULE_1__["emptyReg"].test(awardCode);
    timerTitle.text(function () {
      var text = "";

      if (ifHasAward) {
        text = "金猪大礼包开奖密钥";
      } else {
        if (status == 1) {
          text = "活动开始倒计时";
        } else {
          text = "活动结束倒计时";
        }
      }

      return text;
    });

    if (ifHasAward) {
      var awardCodeTop = $('.awardCodeTop', countDownWrap);
      awardCodeTop.find('ul').append(function () {
        return '<li>' + awardCode.split("").join('</li><li>') + '</li>';
      });
      $('.awardCodeTop', countDownWrap).flexDisplay();
    } else {
      $('.countDownUl', countDownWrap).flexDisplay();
      var countdownTime = data[status == 1 ? "countdownTimeStart" : "countdownTime"];
      var timeObj = splitTime(countdownTime);
      setCountDownVal(timeObj);
      var timer = setInterval(function () {
        //剩余时间为0时，
        if (countdownTime == 0) {
          clearInterval(timer);

          if (status == 1) {
            _countdownFn(data, 2);

            location.reload();
          }

          return;
        }

        countdownTime--;
        var timeObj = splitTime(countdownTime);
        setCountDownVal(timeObj, true);
      }, 1000);
    }
  };
})();

function initAjax() {
  var ajaxDate = {};

  if (getPara('token')) {
    ajaxDate.token = getPara('token');
  } // console.log(getPara('token'));


  return $.ajax({
    "url": "/activity/SpringFestival2019Init",
    "dataType": "json",
    "data": ajaxDate
  });
}

initAjaxDeferred.fail(function () {
  Object(_base__WEBPACK_IMPORTED_MODULE_1__["showToast"])('服务器端异常');
});

function initPanel(data) {
  satisfyMinLimit = data.dealMoney >= data.awardConfig.deal[0].dealMoney * 10000;
  updatePanel(data, true);
  createTab(data);
  likeCountPeople(data);
  likeCountBtn(data);

  _countdownFn(data);
}
/**
 * 更新界面状态
 * @param isInit
 */


function updatePanel(data) {
  var isInit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

  if (isInit) {
    $('#awardMoney').text(data.status == 1 ? "新年大奖等您拿" : data.awardInfo.awardMoney);
    $('#dealMoney').text(data.dealMoney);
    $('#likeCount').text(data.likeCount);
  }
} //创建tab切换区域


function createTab(data) {
  var dealMoney = data.dealMoney,
      awardInfo = data.awardInfo,
      likeCount = data.likeCount;
  var _data$awardConfig = data.awardConfig,
      deal = _data$awardConfig.deal,
      times = _data$awardConfig.times;
  var tabTitle = $('#tabTitle');
  var dealMoneyArr = deal.map(function (item, index) {
    return item.dealMoney * 10000;
  }); // console.log(dealMoneyArr);

  var initIndex = function () {
    var index = -1;

    for (var i = 0; i < dealMoneyArr.length; i++) {
      if (dealMoney >= dealMoneyArr[i]) {
        index = i;
      } else {
        return index;
      }
    }

    return index;
  }();

  tabTitle.append(function () {
    var str = "";
    deal.forEach(function (item, index) {
      str += "<li class=\"".concat(index <= initIndex ? 'satisfied' : 'dissatisfied', "\">\u6EE1").concat(item.dealMoney, "\u4E07</li>");
    });
    return str.trim();
  });
  $('#times').text(awardInfo.times);

  if (awardInfo.nextCnt != false) {
    $('#nextCnt').text(awardInfo.nextCnt);
    $('#nextTimes').text(awardInfo.nextTimes);
  } else {
    $('#nextCntWrap').hide();
  }

  var liList = tabTitle.find('li');
  tabTitle.on('click', 'li', function () {
    var index = $(this).index();
    liList.removeClass('active');
    $(this).addClass('active');
    updateDataInfor(deal[index].awardMoney);
  });

  function updateDataInfor(newValue) {
    var awardMoneyBase = $('#awardMoneyBase');
    var oldValue = awardMoneyBase.data('originalValue');

    if (oldValue != newValue) {
      awardMoneyBase.text(newValue);
      awardMoneyBase.removeClass('ani');
      awardMoneyBase.get(0).offsetWidth;
      awardMoneyBase.addClass('ani');
    }

    awardMoneyBase.data('originalValue', newValue);
  }

  if (initIndex >= 0) {
    showPanelDeferred.done(function () {
      tabTitle.find('li').eq(initIndex).trigger('click');
    });
  }

  function progressFn(data) {
    var count = data.likeCount;
    count = Number(count);
    var cntArr = data.awardConfig.times.map(function (item) {
      return item.cnt;
    });
    var limitArr = [0.33, 0.99, 1.65, 2.31];
    var tabProgress = $('#tabProgress');
    var tipPointer = $('#tipPointer');
    var factorArr = $('#factorWrap').children();
    var likeCountArr = $('#likeCountLevelWrap').children();

    if (count == 0) {
      return;
    } else {
      var barWidth = function () {
        var barWidth = 0;

        if (count <= cntArr[0]) {
          barWidth = count / cntArr[0] * (limitArr[0] - 0.05);
        } else if (count <= cntArr[1]) {
          barWidth = limitArr[0] + (count - cntArr[0]) / (cntArr[1] - cntArr[0]) * 0.66 - 0.05;
        } else if (count <= cntArr[2]) {
          barWidth = limitArr[1] + (count - cntArr[1]) / (cntArr[2] - cntArr[1]) * 0.66 - 0.05;
        } else if (count <= cntArr[3]) {
          barWidth = limitArr[2] + (count - cntArr[2]) / (cntArr[3] - cntArr[2]) * 0.66 - 0.05;
        } else {
          barWidth = limitArr[3] + (count - cntArr[3]) / (cntArr[3] - cntArr[2]) * 0.66 - 0.05;

          if (barWidth > 2.54) {
            barWidth = 2.54;
          }
        }

        return barWidth;
      }(); // console.log(barWidth)


      tabProgress.animate({
        'width': barWidth + 'rem'
      }, {
        duration: 1000 * ((barWidth + 0.05) / 2.31),
        // duration:1000,
        easing: 'linear',
        step: function step(num) {
          limitArr.forEach(function (limit, index) {
            if (num + 0.05 >= limit) {
              factorArr.eq(index).add(likeCountArr.eq(index)).addClass('active');
            }
          });
        }
      });

      var pointPos = function () {
        var index = -1;

        for (var i = 0; i < cntArr.length; i++) {
          if (count >= cntArr[i]) {
            index = i;
          } else {
            break;
          }
        }

        return index == -1 ? 0 : limitArr[index];
      }(); // console.log(pointPos)


      if (pointPos > 0) {
        tipPointer.css({
          left: "".concat(pointPos, "rem")
        }).transition({
          'y': '-0.13rem'
        }, 1000 * (pointPos / 2.31), function () {});
      }
    }
  }

  function createTimes(data) {
    var times = data.awardConfig.times;
    var factorWrap = $('#factorWrap');
    var likeCountLevelWrap = $('#likeCountLevelWrap');
    factorWrap.html(function () {
      return '<div>' + times.map(function (item) {
        return "x".concat(item.times, "\u500D");
      }).join('</div><div>') + '</div>';
    });
    likeCountLevelWrap.html(function () {
      return '<div>' + times.map(function (item) {
        return "".concat(item.cnt, "\u4EBA");
      }).join('</div><div>') + '</div>';
    });
  }

  createTimes(data);
  showPanelDeferred.done(function () {
    progressFn(data);
  }); // console.log(dealMoney)
} //创建助力小分队


function likeCountPeople(data) {
  var likeCount = data.likeCount; // console.log(likeCount);

  var portraitContent = $('#portraitContent');
  $('#myLikeCountLeft').text("".concat(data.myLikeCountLeft, "\u6B21"));
  portraitContent.append(function () {
    var htmlStr = "";

    if (likeCount > 0) {
      htmlStr += "<div class='contentInner'>";

      for (var i = 0, max = Math.floor(likeCount / 5); i < max; i++) {
        htmlStr += "<ul class=\"portraitUl\">";

        for (var _i = 0; _i < 5; _i++) {
          htmlStr += "<li class=\"portraitLi\"><img src=\"".concat(imgPrefix, "people0").concat(Object(_base__WEBPACK_IMPORTED_MODULE_1__["rangeRandom"])(4, 1), ".jpg\"/>\n                </li>");
        }

        htmlStr += "</ul>";
      }

      var oddNum = likeCount % 5;

      if (oddNum > 0) {
        htmlStr += "<ul class=\"portraitUl\">";

        for (var _i2 = 0; _i2 < likeCount % 5; _i2++) {
          htmlStr += "<li class=\"portraitLi\"><img src=\"".concat(imgPrefix, "people0").concat(Object(_base__WEBPACK_IMPORTED_MODULE_1__["rangeRandom"])(4, 1), ".jpg\"/>\n                </li>");
        }

        htmlStr += "</ul>";
      }

      htmlStr += '</div>';
    } else {
      htmlStr += "<div class='emptyContent'>还没有助力的小伙伴</div>";
    }

    return htmlStr.trim();
  });
} //呼叫助力小分队


function likeCountBtn(data) {
  var likeBtn = $('#likeBtn');
  var shareWrap = $('#shareWrap');
  var myAwardCode = data.myAwardCode;
  var codeUl = $('#codeUl');
  var myCodeWrapInner = $('#myCodeWrapInner');

  function setShare(data) {
    var shareDlList = $('.shareDl');
    var _data$share = data.share,
        title = _data$share.title,
        content = _data$share.content,
        face = _data$share.face,
        link = _data$share.link;
    shareDlList.each(function (index, item) {
      $(item).find('a').attr({
        "href": "wechat://api?type=".concat(index == 0 ? "session" : "timeline", "&title=").concat(title, "&content=").concat(content, "&face=").concat(encodeURIComponent(face), "&url=").concat(encodeURIComponent(link))
      });
      $(item).find('a').on('click', function () {
        zhuge.track("".concat(_base__WEBPACK_IMPORTED_MODULE_1__["trackPrefix"], "\u547C\u53EB\u52A9\u529B\u5C0F\u5206\u961F\u5F39\u7A97\u4E2D_").concat(index == 0 ? "微信好友" : "朋友圈"));
      });
    });
  }

  setShare(data);

  if (data.status == 1 || !satisfyMinLimit || data.status == 3) {
    likeBtn.addClass('disabled');
  }

  likeBtn.on('click', function () {
    zhuge.track("".concat(_base__WEBPACK_IMPORTED_MODULE_1__["trackPrefix"], "\u547C\u53EB\u52A9\u529B\u5C0F\u5206\u961F"));

    if (data.status == 1) {
      Object(_base__WEBPACK_IMPORTED_MODULE_1__["showPop"])('新年活动尚未开始，还请耐心等待~');
    } else if (data.status == 3) {
      Object(_base__WEBPACK_IMPORTED_MODULE_1__["showPop"])('新年活动已经结束~');
    } else if (!satisfyMinLimit) {
      Object(_base__WEBPACK_IMPORTED_MODULE_1__["showPop"])('投资额度尚未达标，赶快去投资吧~');
    } else {
      shareWrap.flexDisplay();
    }
  });
  shareWrap.on('click', '.topMask', function () {
    zhuge.track("".concat(_base__WEBPACK_IMPORTED_MODULE_1__["trackPrefix"], "\u547C\u53EB\u52A9\u529B\u5C0F\u5206\u961F\u5F39\u7A97\u4E2D_\u5173\u95ED\u6309\u94AE"));
    shareWrap.hide();
  });

  if (data.status == 1 || _base__WEBPACK_IMPORTED_MODULE_1__["emptyReg"].test(myAwardCode)) {
    myCodeWrapInner.addClass('disabled');
  } else {
    codeUl.append(function () {
      return '<li>' + myAwardCode.split("").join('</li><li>') + '</li>';
    });
  }

  function myCodeAni(targetStatus) {
    var myCodeLabelWidth = 1.08;

    if (myCodeWrapInner.data('lock') == 1) {
      return;
    }

    myCodeWrapInner.data('lock', 1);
    myCodeWrapInner.transition({
      width: targetStatus == "unfold" ? '3.75rem' : "".concat(myCodeLabelWidth, "rem")
    }, 400, function () {
      myCodeWrapInner.data('status', targetStatus);
      myCodeWrapInner.data('lock', 0);
    });
  }

  myCodeWrapInner.find('label').on('click', function () {
    if (data.status == 1) {
      Object(_base__WEBPACK_IMPORTED_MODULE_1__["showPop"])('新年活动尚未开始，还请耐心等待~');
    } else if (_base__WEBPACK_IMPORTED_MODULE_1__["emptyReg"].test(myAwardCode)) {
      if (data.status == 3) {
        Object(_base__WEBPACK_IMPORTED_MODULE_1__["showPop"])('新年活动已经结束~');
      } else {
        Object(_base__WEBPACK_IMPORTED_MODULE_1__["showPop"])('您尚未获得可开启神秘大奖的密钥。赶快去投资、助力吧~');
      }
    } else {
      myCodeAni(myCodeWrapInner.data('status') == "fold" ? "unfold" : "fold");
    }
  });
  myCodeWrapInner.find('.codeContent').on('click', function () {
    myCodeAni("fold");
  });
}

zhuge.track("".concat(_base__WEBPACK_IMPORTED_MODULE_1__["trackPrefix"], "\u6D3B\u52A8\u4E3B\u9875\u9762\u8F7D\u5165"));

/***/ }),

/***/ 0:
/*!******************************!*\
  !*** multi ./src/js/main.js ***!
  \******************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! D:\HTMLCode\配置游戏\spring_festival_2019\src\js\main.js */"./src/js/main.js");


/***/ })

/******/ });
//# sourceMappingURL=main.js.map