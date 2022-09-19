var emptyReg=/^\s*$/;
function emptyFn(){};
var flexDisplay="flex";
var isAndroid=function () {
    var userAgent=navigator.userAgent;
    var isAndroid=false;
    if (userAgent.indexOf('Android') > -1 || userAgent.indexOf('Adr') > -1){
        isAndroid=true;
    }
    return isAndroid;
}();
var isIOS=function () {
    var userAgent=navigator.userAgent;
    var isIOS=false;
    if (/\(i[^;]+;( U;)? CPU.+Mac OS X/.test(userAgent) && !isAndroid){
        isIOS=true;
    }
    return isIOS;
}();
$.extend($.fn,{
    "longPress":function (callBack) {
        var startTime=0;
        var endTiem=0;
        var timer=null;
        var delayTime=750;
        var pointJson={};
        var deltaX=0;
        var deltaY=0;
        $(this).on('touchstart.longPress',function (event) {
            event.preventDefault();
            deltaX=0;
            deltaY=0;
            var touch=event.originalEvent.targetTouches[0];
            pointJson.x=touch.screenX;
            pointJson.y=touch.screenY;
            startTime=Date.now();
            timer=setTimeout(function () {
                if (deltaX<30 && deltaY<30){
                    callBack();
                }
            },delayTime);
        });
        $(this).on('touchend.longPress',function () {
            endTiem=Date.now();
            if (endTiem-startTime<=250){
                if (deltaX<30 && deltaY<30){
                    $(this).trigger('click');
                }
            }
            clearTimeout(timer);
        });
        $(this).on('touchmove.longPress',function (event) {
            var touch=event.originalEvent.targetTouches[0];
            pointJson.x2=touch.screenX;
            pointJson.y2=touch.screenY;
            deltaX+=Math.abs(pointJson.x2-pointJson.x);
            deltaY+=Math.abs(pointJson.y2-pointJson.y);
            pointJson.x = pointJson.x2;
            pointJson.y = pointJson.y2;
        });
    }
})
/**
 * 监听对象属性变化
 * @param obj
 * @param map
 */
function observeData(obj,map) {
    var defaultConfig={
        "configurable":true,
        "enumerable":true,
    }
    var config=null;
    var mapValue={};
    var mapItem=null;
    for (var i in map){
        if (map.hasOwnProperty(i)){
            mapItem=map[i];
            mapValue[i]=mapItem.initVal;
            config=$.extend({},defaultConfig,{
                "set":function(i){
                    return function (value) {
                        var oldValue=mapValue[i];
                        var returnVal;
                        map[i].set && (returnVal=map[i].set.apply(obj,[value,oldValue,i]));
                        if (typeof returnVal!="undefined"){
                            value = returnVal;
                        }
                        mapValue[i]=value;
                        map[i].setCallBack && map[i].setCallBack.apply(obj,[value,oldValue,i]);
                    }
                }(i),
                "get":function(i){
                    return function () {
                        return mapValue[i];
                    }
                }(i)
            });
            Object.defineProperty(obj,i,config);
        }
    }
    return obj;
}

//toast提示构造器
function ToastPop(jsonData) {
    this.option=$.extend({},this.defaultConfig,jsonData);
    this.init();
}
ToastPop.prototype={
    constructor:ToastPop,
    defaultConfig:{
        clickHide:false,
        content:'默认toast提示内容',
        delayHideTime:false
    },
    init:function () {
        var toastDom=null;
        var option=this.option;
        var _this=this;
        if(ToastPop.initDom){
            this.toastDom=ToastPop.initDom;
        }else{
            this.toastDom=ToastPop.initDom=$('<div class="toastPop" id="toastPop"><div class="inner">').appendTo('body');
        }
        toastDom=this.toastDom.show();
        toastDom.off('click');
        if (option.clickHide){
            toastDom.on('click',function () {
                toastDom.hide();
            });
        }
        if (option.delayHideTime){
            setTimeout(function () {
               toastDom.hide();
            },option.delayHideTime);
        }
        this.updateCont();
    },
    updateCont:function (content) {
        if (typeof content!="undefined"){
            this.option.content=content;
        }
        this.toastDom.find('.inner').empty().html(this.option.content);
    },
    show:function () {
        this.toastDom.show();
    },
    hide:function () {
        this.toastDom.hide();
    },
    getOption:function () {
        return this.option;
    }
}
//判断是不是数字
function isNum(num) {
    if (typeof num=="boolean" || emptyReg.test(num)){
        return false;
    }
    return !isNaN(num);
}
/**
 * 观察者模式
 * @param isUnique 注册事件是否保持唯一性
 * @constructor
 */
function Pubsub(isUnique){
    this.eventMap={};//订阅发布事件键值对
    this.unique=typeof isUnique !="undefined"?isUnique:true;
}
Pubsub.prototype={
    "constructor":Pubsub,
    //发布方法
    "publish":function(topic,args){
        var _this=this;
        var fns=this.eventMap[topic];
        var returnVal;

        if(!fns || fns.length==0){
            return;
        }
        fns.forEach(function(fnItem,index){
            returnVal=fnItem.apply(_this,args);
        });
        return returnVal;
    },
    //订阅方法
    "subscribe":function(topic,fn){
        var fns=[];
        if(typeof this.eventMap[topic] =="undefined"){
            this.eventMap[topic]=[];
        }
        fns=this.eventMap[topic];
        //如果fn是数组
        if(fn instanceof Array){
            fn.forEach(function(_,fn){
                this.subscribe(topic,fn);
            });
        }else{
            if(!this.unique || fns.indexOf(fn)==-1){
                fns.push(fn);
            }else{
                //console.error('不支持重复添加相同函数，请检查您的代码。');
            }
        }
    },
    //退订方法
    "unsubscribe":function(topic){
        var fns=this.eventMap[topic];
        if(!fns || fns.length==0){
            return;
        }
        this.eventMap[topic]=[];
    },
    //是否包含
    "has":function(topic){
        var fns=this.eventMap[topic];
        if(!fns || fns.length==0){
            return false;
        }else{
            return true;
        }
    }
}

//小于10的数字，前面补0
function addZeroPrefix(num) {
    if (num<10){
        return '0'+num;
    }else{
        return num;
    }
}

//获取验证码
function VerifyCode(json) {
    this.parConfig=json;
    this.dom=json.dom;
    this.timer=null;
    this.init();
}
VerifyCode.prototype={
    constructor:VerifyCode,
    defaultConfig:{
        time:60,
        startCallBack:emptyFn,
        stepCallBack:emptyFn,
        endCallBack:emptyFn
    },
    init:function () {
        var _this=this;
        $.extend(this,this.defaultConfig,this.parConfig);
        this.dom.on('click',function () {
            _this.start();
        });
    },
    start:function () {
        var dom=this.dom;
        var _this=this;
        var time=this.time;
        if (dom.hasClass('noValid')){
            return;
        }
        this.startCallBack();
        dom.addClass('noValid');
        dom.val(time+'s后重新获取')
        this.timer = setInterval(function () {
            time=time-1;
            _this.stepCallBack(time);
            if (time==0){
                _this.reset();
            }else{
                dom.val(time+'s后重新获取');
            }
        },1000);
    },
    reset:function () {
        this.endCallBack();
        this.dom.val('获取验证码').removeClass('noValid');
        clearInterval(this.timer);
        this.timer=null;
    }
}

//通过iframe触发scheme
function triggerScheme(scheme){
    var newIframe=$('<iframe src="'+scheme+'" style="display: none;"></iframe>').appendTo('body');
    newIframe.remove();
}

//获取页面主域名
function getLocationOrigin() {
    var origin=location.origin;
    return origin;
}

//jQuery扩展实例方法
$.extend($.fn,{
    "setWebviewUrl":function () {
        this.each(function () {
            var origin=getLocationOrigin();
            var wholeUrl=origin+$(this).data('urlPath');
            var webviewPreview=$(this).data('webviewPreview');
            var setHref=$(this).data('setHref');
            wholeUrl=webviewPreview+encodeURIComponent(wholeUrl);
            if (typeof setHref=="undefined" || setHref!=false){
                $(this).attr('href',wholeUrl);
            }
            $(this).attr('data-href-text',wholeUrl);
            $(this).removeAttr('data-url-path data-webview-preview data-set-href');
        });
    }
});

$('.setWebviewUrl').setWebviewUrl();

function getMonthRecord(dateStr) {
    dateStr=String(dateStr);
    var curYear=new Date().getFullYear();
    var curMonth=new Date().getMonth()+1;
    var tarYear=Number(dateStr.slice(0,4));
    var tarMonth=Number(dateStr.slice(4));
    var returnStr="";
    if (curYear!=tarYear){
        returnStr+=tarYear+'年';
    }
    if (curMonth==tarMonth){
        returnStr+='本月';
    }else{
        returnStr+=tarMonth+'月';
    }
    return returnStr;
}
//刷新页面
function refreshPage() {
   location.reload();
}
function checkServeTime(reqSource,token,nextFn) {
    var deferObj=$.ajax({
        "url":'/speedloan/checkServiceStatus',
        "method":'get',
        "data":{
            "token":token,
            "reqSource":reqSource
        }
    });
    deferObj.fail(function () {
        new ToastPop({
            "content":'服务器端异常，请稍后重试',
            "clickHide":true,
            "delayHideTime":2500
        })
    });
    deferObj.done(function (resultVal) {
        if (resultVal.errno!=0){
            new ToastPop({
                "content":resultVal.error,
                "clickHide":true,
                "delayHideTime":2500
            })
        }else{
            if (nextFn){
                nextFn(resultVal);
            }
        }
    })
    return deferObj;
}
$(function () {
    flexDisplay = function () {
        var newDiv = $('<div class="flexCssTmp">nihao</div>');
        newDiv.appendTo('body');
        var flexDisplay = newDiv.css('display');
        newDiv.remove();
        return flexDisplay;
    }();
});


