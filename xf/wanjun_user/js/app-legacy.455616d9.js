(()=>{var e={7294:(e,n,r)=>{"use strict";r.r(n);var i=r(2512),o=r.n(i),t=r(1736),a=r.n(t),u=new(o())({id:"icon-enter",use:"icon-enter-usage",viewBox:"0 0 1024 1024",content:'<symbol class="icon" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="icon-enter"><defs><style type="text/css">@font-face { font-family: feedback-iconfont; src: url("//at.alicdn.com/t/font_1031158_u69w8yhxdu.woff2?t=1630033759944") format("woff2"), url("//at.alicdn.com/t/font_1031158_u69w8yhxdu.woff?t=1630033759944") format("woff"), url("//at.alicdn.com/t/font_1031158_u69w8yhxdu.ttf?t=1630033759944") format("truetype"); }\n</style></defs><path d="M693.792 498.24l-320-297.664a32 32 0 0 0-43.584 46.848l295.36 274.752-295.84 286.848a31.968 31.968 0 1 0 44.544 45.92l320-310.272a31.968 31.968 0 0 0-0.48-46.4" p-id="3932" /></symbol>'});a().add(u);n["default"]=u},5634:(e,n,r)=>{"use strict";r.r(n);var i=r(2512),o=r.n(i),t=r(1736),a=r.n(t),u=new(o())({id:"icon-tick",use:"icon-tick-usage",viewBox:"0 0 1024 1024",content:'<symbol viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="icon-tick"><defs><style type="text/css">@font-face { font-family: feedback-iconfont; src: url("//at.alicdn.com/t/font_1031158_u69w8yhxdu.woff2?t=1630033759944") format("woff2"), url("//at.alicdn.com/t/font_1031158_u69w8yhxdu.woff?t=1630033759944") format("woff"), url("//at.alicdn.com/t/font_1031158_u69w8yhxdu.ttf?t=1630033759944") format("truetype"); }\n</style></defs><path d="M233.244444 546.133333c-5.688889 0-5.688889-5.688889-5.688888-11.377777l22.755555-28.444445c5.688889-5.688889 11.377778-5.688889 11.377778 0l164.977778 108.088889c5.688889 5.688889 22.755556 5.688889 28.444444 0l420.977778-335.644444c5.688889-5.688889 11.377778 0 11.377778 0l22.755555 17.066666v11.377778l-455.111111 438.044444c-5.688889 5.688889-17.066667 5.688889-22.755555 0L233.244444 546.133333z" p-id="2277" /></symbol>'});a().add(u);n["default"]=u},5703:(e,n,r)=>{"use strict";var i;r.d(n,{I:()=>i}),function(e){e["LOGGED_IN_USER"]="LOGGED_IN_USER",e["USER_LOGIN_CREDENTIALS"]="USER_LOGIN_CREDENTIALS"}(i||(i={}))},1259:(e,n,r)=>{"use strict";r.d(n,{BB:()=>a,LQ:()=>u});r(4747);var i=r(7139),o=r(3396);function t(e,n){let r=arguments.length>2&&void 0!==arguments[2]&&arguments[2];const t=(0,i.oR)(),a=n(e),u={};return Object.keys(a).forEach((e=>{const n=a[e].bind({$store:t});u[e]=r?n:(0,o.Fl)(n)})),u}const a=e=>{let n=e.nameSpace,r=e.actions,o=i.nv;return n&&(o=(0,i._p)(n).mapActions),t(r,o,!0)},u=e=>{let n=e.nameSpace,r=e.states,o=i.rn;return n&&(o=(0,i._p)(n).mapState),t(r,o)}},2973:(e,n,r)=>{"use strict";r(6992),r(8674),r(9601),r(7727);var i=r(9242),o=r(3396),t=r(1259),a=(0,o.aZ)({name:"App",setup(){const e=(0,t.BB)({actions:["initializeLogin"]}),n=e.initializeLogin;return n(),()=>(0,o.Wm)((0,o.up)("router-view"),null,null)}}),u=(r(3948),r(678)),c=(0,o.aZ)({name:"HomeView",setup(){return()=>(0,o.Wm)((0,o.up)("router-view"),null,null)}}),d=r(717),s=r(5703);const l=[{path:"/",redirect:"/user"},{path:"/user",name:"home",component:c,children:[{path:"",name:"user-index",component:()=>r.e(441).then(r.bind(r,4075))},{path:"info",name:"user-info",component:()=>r.e(441).then(r.bind(r,2741))}]},{path:"/claims",component:c,children:[{path:"",component:()=>r.e(623).then(r.bind(r,3561))}]},{path:"/login",name:"login",component:()=>r.e(535).then(r.bind(r,3067))}],f=(0,u.p7)({history:(0,u.PO)(""),routes:l});f.beforeEach(((e,n,r)=>{(0,d.qe)(s.I.LOGGED_IN_USER).then((n=>{null!==n&&void 0!==n&&n.token||"/login"===e.path?null!==n&&void 0!==n&&n.token&&"/login"!==e.path||(null===n||void 0===n||!n.token)&&"/login"===e.path?r():r({path:"/"}):r({path:"/login"})})).catch((()=>{r({path:"/login"})}))}));var p=f,x=r(8534),h=(r(6133),r(7139)),m=r(1293);const O="UPDATE_LOGIN_USER";var w=(0,h.MT)({state:{user:null},getters:{},mutations:{[O](e,n){(0,d.xL)(s.I.LOGGED_IN_USER,n),e.user=n}},actions:{login(e,n){return(0,x.Z)(regeneratorRuntime.mark((function r(){var i,o,t;return regeneratorRuntime.wrap((function(r){while(1)switch(r.prev=r.next){case 0:return i=e.commit,r.next=3,(0,m.v)("/user/XFUser/Login",n);case 3:return o=r.sent,t=o.result,i(O,t),r.abrupt("return",{result:t});case 7:case"end":return r.stop()}}),r)})))()},logout(e){return(0,x.Z)(regeneratorRuntime.mark((function n(){var r;return regeneratorRuntime.wrap((function(n){while(1)switch(n.prev=n.next){case 0:r=e.commit,r(O,null);case 2:case"end":return n.stop()}}),n)})))()},initializeLogin(e){return(0,x.Z)(regeneratorRuntime.mark((function n(){var r,i;return regeneratorRuntime.wrap((function(n){while(1)switch(n.prev=n.next){case 0:return r=e.commit,n.next=3,(0,d.qe)(s.I.LOGGED_IN_USER);case 3:i=n.sent,i&&r(O,i);case 5:case"end":return n.stop()}}),n)})))()},queryUser(e){return(0,x.Z)(regeneratorRuntime.mark((function n(){var r,i,o,t,a;return regeneratorRuntime.wrap((function(n){while(1)switch(n.prev=n.next){case 0:return r=e.commit,i=e.state.user,n.next=3,(0,m.v)("/user/XFUser/UserInfo");case 3:o=n.sent,t=o.result,a=Object.assign({},i,t),r(O,a);case 7:case"end":return n.stop()}}),n)})))()}},modules:{}}),g=r(4677);r(343),r(4747);const v={install(e){let n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{imports:[]},i=n.imports;const o="@/assets/icons/svg";if(i.length>0)i.forEach((e=>{r(1838)(`${o}/${e}.svg`)}));else{const e=r(2007);e.keys().map(e)}}};var $=v;const b={draggable:!1,position:g.Vr.BOTTOM_CENTER,timeout:2e3,icon:!1,closeButton:!1,toastClassName:"mt-toast-dark",hideProgressBar:!1};(0,i.ri)(a).use($).use(w).use(p).use(g.ZP,b).mount("#app")},1293:(e,n,r)=>{"use strict";r.d(n,{v:()=>A});var i=r(4648),o=r(2078),t=r(8534),a=(r(9714),r(6133),r(6265)),u=r.n(a),c=r(5410),d=r.n(c),s=r(717),l=r(5703),f=r(3775),p=r.n(f),x=(r(4916),r(3123),r(1004)),h=r.n(x);r(4723);let m,O,w,g,v=[["a7","640x1136",["iPhone 5","iPhone 5s"]],["a7","1536x2048",["iPad Air","iPad Mini 2","iPad Mini 3"]],["a8","640x1136",["iPod touch (6th gen)"]],["a8","750x1334",["iPhone 6"]],["a8","1242x2208",["iPhone 6 Plus"]],["a8","1536x2048",["iPad Air 2","iPad Mini 4"]],["a9","640x1136",["iPhone SE"]],["a9","750x1334",["iPhone 6s"]],["a9","1242x2208",["iPhone 6s Plus"]],["a9x","1536x2048",["iPad Pro (1st gen 9.7-inch)"]],["a9x","2048x2732",["iPad Pro (1st gen 12.9-inch)"]],["a10","750x1334",["iPhone 7"]],["a10","1242x2208",["iPhone 7 Plus"]],["a10x","1668x2224",["iPad Pro (2th gen 10.5-inch)"]],["a10x","2048x2732",["iPad Pro (2th gen 12.9-inch)"]],["a11","750x1334",["iPhone 8"]],["a11","1242x2208",["iPhone 8 Plus"]],["a11","1125x2436",["iPhone X"]],["a12","828x1792",["iPhone Xr"]],["a12","1125x2436",["iPhone Xs"]],["a12","1242x2688",["iPhone Xs Max"]],["a12x","1668x2388",["iPad Pro (3rd gen 11-inch)"]],["a12x","2048x2732",["iPad Pro (3rd gen 12.9-inch)"]],["a13","828x1792",["iPhone 11"]],["a13","1125x2436",["iPhone 11 Pro"]],["a13","1242x2688",["iPhone 11 Pro Max"]],["a13","750x1334",["iPhone SE2"]],["a14","1170x2532",["iPhone 12","iPhone 12 Pro"]],["a14","1284x2778",["iPhone 12 Pro Max"]],["a14","1080x2340",["iPhone 12 Mini"]],["a15","1080x2340",["iPhone 13 Mini"]],["a15","1170x2532",["iPhone 13","iPhone 13 Pro"]],["a15","1284x2778",["iPhone 13 Pro Max"]],["a15","750x1334",["iPhone SE3"]]];function $(){return null==m&&(m=document.createElement("canvas")),m}function b(){return null==O&&(O=$().getContext("experimental-webgl")),O}function E(){var e=window.devicePixelRatio||1;return Math.min(screen.width,screen.height)*e+"x"+Math.max(screen.width,screen.height)*e}function y(){if(null==w){const e=b().getExtension("WEBGL_debug_renderer_info");w=null==e?"unknown":b().getParameter(e.UNMASKED_RENDERER_WEBGL)}return w}const S=()=>{if(null==g){const r=y(),i=r.match(/^apple\s+([_a-z0-9-]+)\s+gpu$/i),o=E();if(g=["unknown"],i)for(var e=0;e<v.length;e++){var n=v[e];if(i[1].toLowerCase()===n[0]&&o===n[1]){g=n[2];break}}}return g};function P(){let e=function(e,n){for(let r in e)if(e[r].indexOf(n)>0)return r;return-1},n=navigator.userAgent,r=new(h())(n),i=r.os(),o="";if("iOS"===i)i=r.os()+r.version("iPhone"),o=S();else if("AndroidOS"===i){i=r.os()+r.version("Android");let t=n.split(";"),a=e(t,"Build/");a>-1&&(o=t[a].substring(0,t[a].indexOf("Build/")))}const t="UnknownPhone"!==r.mobile()?r.mobile():"UnknownPhone"!==r.phone()?r.phone():"";return"iOS"===i?`${o}(${i})`:`${t}${o}(${i})`}const k=["code","data","info","msg"],B=new(p())(navigator.userAgent).browser;u().defaults.headers["Content-Type"]="application/x-www-form-urlencoded",u().interceptors.request.use(function(){var e=(0,t.Z)(regeneratorRuntime.mark((function e(n){var r,i,o;return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.prev=0,e.next=3,(0,s.qe)(l.I.LOGGED_IN_USER);case 3:if(e.t0=e.sent,e.t0){e.next=6;break}e.t0={};case 6:return r=e.t0,i=r.token,o={"X-WJ-AUTHORIZATION":i},n.headers=Object.assign({},n.headers,o),e.abrupt("return",n);case 13:return e.prev=13,e.t1=e["catch"](0),e.abrupt("return",Promise.reject(e.t1));case 16:case"end":return e.stop()}}),e,null,[[0,13]])})));return function(n){return e.apply(this,arguments)}}());const M=e=>!e||200===e||"000000"===e,_=e=>{let n=e.data;const r=n.code,t=n.data,a=n.info,u=n.msg,c=void 0===u?a:u,d=(0,o.Z)(n,k);return M(r)?(0,i.Z)({code:r,result:t,msg:c},d):(1016===r&&(0,s.PS)().then((()=>{location.href="/login"})),Promise.reject({code:r,result:t,msg:c}))},C=e=>{var n;const r={},i=null===(n=e.request)||void 0===n?void 0:n.status;return"Network Error"===e.message&&0===i?r.msg="网络请求错误，请检查您的网络":r.msg=404===i?"服务器跑到火星上去了":500===i?"服务器发生故障":e.toString(),Promise.reject(r)};u().interceptors.response.use(_,C);const A=function(){var e=(0,t.Z)(regeneratorRuntime.mark((function e(n){var r,o,t=arguments;return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return r=t.length>1&&void 0!==t[1]?t[1]:{},o=t.length>2&&void 0!==t[2]?t[2]:{},e.abrupt("return",u().post(n,d().stringify((0,i.Z)((0,i.Z)({},r),{},{add_device:P(),add_browser:B})),(0,i.Z)({baseURL:"https://qa1api.wjzcgl.com"},o)));case 3:case"end":return e.stop()}}),e)})));return function(n){return e.apply(this,arguments)}}()},717:(e,n,r)=>{"use strict";r.d(n,{PS:()=>u,qe:()=>a,xL:()=>t});var i=r(7165),o=r.n(i);function t(e,n){return o().setItem(e,n)}function a(e){return o().getItem(e)}function u(){return o().clear()}},3775:function(e,n,r){var i;r(4916),r(5306),r(3123),r(4723),r(1058),function(o,t){i=function(){return t(o)}.call(n,r,n,e),void 0===i||(e.exports=i)}("undefined"!==typeof self?self:this,(function(e){var n=e||{},r="undefined"!=typeof e.navigator?e.navigator:{},i=function(e,n){var i=r.mimeTypes;for(var o in i)if(i[o][e]==n)return!0;return!1};return function(e){var o=e||r.userAgent||{},t=this,a={Trident:o.indexOf("Trident")>-1||o.indexOf("NET CLR")>-1,Presto:o.indexOf("Presto")>-1,WebKit:o.indexOf("AppleWebKit")>-1,Gecko:o.indexOf("Gecko/")>-1,KHTML:o.indexOf("KHTML/")>-1,Safari:o.indexOf("Safari")>-1,Chrome:o.indexOf("Chrome")>-1||o.indexOf("CriOS")>-1,IE:o.indexOf("MSIE")>-1||o.indexOf("Trident")>-1,Edge:o.indexOf("Edge")>-1||o.indexOf("Edg/")>-1||o.indexOf("EdgA")>-1||o.indexOf("EdgiOS")>-1,Firefox:o.indexOf("Firefox")>-1||o.indexOf("FxiOS")>-1,"Firefox Focus":o.indexOf("Focus")>-1,Chromium:o.indexOf("Chromium")>-1,Opera:o.indexOf("Opera")>-1||o.indexOf("OPR")>-1,Vivaldi:o.indexOf("Vivaldi")>-1,Yandex:o.indexOf("YaBrowser")>-1,Arora:o.indexOf("Arora")>-1,Lunascape:o.indexOf("Lunascape")>-1,QupZilla:o.indexOf("QupZilla")>-1,"Coc Coc":o.indexOf("coc_coc_browser")>-1,Kindle:o.indexOf("Kindle")>-1||o.indexOf("Silk/")>-1,Iceweasel:o.indexOf("Iceweasel")>-1,Konqueror:o.indexOf("Konqueror")>-1,Iceape:o.indexOf("Iceape")>-1,SeaMonkey:o.indexOf("SeaMonkey")>-1,Epiphany:o.indexOf("Epiphany")>-1,360:o.indexOf("QihooBrowser")>-1||o.indexOf("QHBrowser")>-1,"360EE":o.indexOf("360EE")>-1,"360SE":o.indexOf("360SE")>-1,UC:o.indexOf("UCBrowser")>-1||o.indexOf(" UBrowser")>-1||o.indexOf("UCWEB")>-1,QQBrowser:o.indexOf("QQBrowser")>-1,QQ:o.indexOf("QQ/")>-1,Baidu:o.indexOf("Baidu")>-1||o.indexOf("BIDUBrowser")>-1||o.indexOf("baidubrowser")>-1||o.indexOf("baiduboxapp")>-1||o.indexOf("BaiduHD")>-1,Maxthon:o.indexOf("Maxthon")>-1,Sogou:o.indexOf("MetaSr")>-1||o.indexOf("Sogou")>-1,Liebao:o.indexOf("LBBROWSER")>-1||o.indexOf("LieBaoFast")>-1,"2345Explorer":o.indexOf("2345Explorer")>-1||o.indexOf("Mb2345Browser")>-1||o.indexOf("2345chrome")>-1,"115Browser":o.indexOf("115Browser")>-1,TheWorld:o.indexOf("TheWorld")>-1,XiaoMi:o.indexOf("MiuiBrowser")>-1,Quark:o.indexOf("Quark")>-1,Qiyu:o.indexOf("Qiyu")>-1,Wechat:o.indexOf("MicroMessenger")>-1,WechatWork:o.indexOf("wxwork/")>-1,Taobao:o.indexOf("AliApp(TB")>-1,Alipay:o.indexOf("AliApp(AP")>-1,Weibo:o.indexOf("Weibo")>-1,Douban:o.indexOf("com.douban.frodo")>-1,Suning:o.indexOf("SNEBUY-APP")>-1,iQiYi:o.indexOf("IqiyiApp")>-1,DingTalk:o.indexOf("DingTalk")>-1,Huawei:o.indexOf("HuaweiBrowser")>-1||o.indexOf("HUAWEI/")>-1||o.indexOf("HONOR")>-1,Vivo:o.indexOf("VivoBrowser")>-1,Windows:o.indexOf("Windows")>-1,Linux:o.indexOf("Linux")>-1||o.indexOf("X11")>-1,"Mac OS":o.indexOf("Macintosh")>-1,Android:o.indexOf("Android")>-1||o.indexOf("Adr")>-1,HarmonyOS:o.indexOf("HarmonyOS")>-1,Ubuntu:o.indexOf("Ubuntu")>-1,FreeBSD:o.indexOf("FreeBSD")>-1,Debian:o.indexOf("Debian")>-1,"Windows Phone":o.indexOf("IEMobile")>-1||o.indexOf("Windows Phone")>-1,BlackBerry:o.indexOf("BlackBerry")>-1||o.indexOf("RIM")>-1,MeeGo:o.indexOf("MeeGo")>-1,Symbian:o.indexOf("Symbian")>-1,iOS:o.indexOf("like Mac OS X")>-1,"Chrome OS":o.indexOf("CrOS")>-1,WebOS:o.indexOf("hpwOS")>-1,Mobile:o.indexOf("Mobi")>-1||o.indexOf("iPh")>-1||o.indexOf("480")>-1,Tablet:o.indexOf("Tablet")>-1||o.indexOf("Pad")>-1||o.indexOf("Nexus 7")>-1||"MacIntel"===navigator.platform&&navigator.maxTouchPoints>1,isWebview:o.indexOf("; wv)")>-1},u=!1;if(n.chrome){var c=o.replace(/^.*Chrome\/([\d]+).*$/,"$1");n.chrome.adblock2345||n.chrome.common2345?a["2345Explorer"]=!0:i("type","application/360softmgrplugin")||i("type","application/mozilla-npqihooquicklogin")||c>36&&n.showModalDialog?u=!0:c>45&&(u=i("type","application/vnd.chromium.remoting-viewer"),!u&&c>=69&&(u=i("type","application/hwepass2001.installepass2001")||i("type","application/asx")))}a["Mobile"]?a["Mobile"]=!(o.indexOf("iPad")>-1):u&&(i("type","application/gameplugin")||r&&"undefined"!==typeof r["connection"]&&"undefined"==typeof r["connection"]["saveData"]?a["360SE"]=!0:a["360EE"]=!0),a["Baidu"]&&a["Opera"]?a["Baidu"]=!1:a["iOS"]&&(a["Safari"]=!0);var d={engine:["WebKit","Trident","Gecko","Presto","KHTML"],browser:["Safari","Chrome","Edge","IE","Firefox","Firefox Focus","Chromium","Opera","Vivaldi","Yandex","Arora","Lunascape","QupZilla","Coc Coc","Kindle","Iceweasel","Konqueror","Iceape","SeaMonkey","Epiphany","XiaoMi","Vivo","360","360SE","360EE","UC","QQBrowser","QQ","Huawei","Baidu","Maxthon","Sogou","Liebao","2345Explorer","115Browser","TheWorld","Quark","Qiyu","Wechat","WechatWork","Taobao","Alipay","Weibo","Douban","Suning","iQiYi","DingTalk"],os:["Windows","Linux","Mac OS","Android","HarmonyOS","Ubuntu","FreeBSD","Debian","iOS","Windows Phone","BlackBerry","MeeGo","Symbian","Chrome OS","WebOS"],device:["Mobile","Tablet"]};for(var s in t.device="PC",t.language=function(){var e=r.browserLanguage||r.language,n=e.split("-");return n[1]&&(n[1]=n[1].toUpperCase()),n.join("_")}(),d)for(var l=0;l<d[s].length;l++){var f=d[s][l];a[f]&&(t[s]=f)}var p={Windows:function(){var e=o.replace(/^Mozilla\/\d.0 \(Windows NT ([\d.]+)[;)].*$/,"$1"),n={10:"10",6.4:"10",6.3:"8.1",6.2:"8",6.1:"7","6.0":"Vista",5.2:"XP",5.1:"XP","5.0":"2000"};return n[e]||e},Android:function(){return o.replace(/^.*Android ([\d.]+);.*$/,"$1")},HarmonyOS:function(){var e=o.replace(/^Mozilla.*Android ([\d.]+)[;)].*$/,"$1"),n={10:"2"};return n[e]||""},iOS:function(){return o.replace(/^.*OS ([\d_]+) like.*$/,"$1").replace(/_/g,".")},Debian:function(){return o.replace(/^.*Debian\/([\d.]+).*$/,"$1")},"Windows Phone":function(){return o.replace(/^.*Windows Phone( OS)? ([\d.]+);.*$/,"$2")},"Mac OS":function(){return o.replace(/^.*Mac OS X ([\d_]+).*$/,"$1").replace(/_/g,".")},WebOS:function(){return o.replace(/^.*hpwOS\/([\d.]+);.*$/,"$1")}};t.osVersion="",p[t.os]&&(t.osVersion=p[t.os](),t.osVersion==o&&(t.osVersion="")),t.isWebview=a["isWebview"];var x={Safari:function(){return o.replace(/^.*Version\/([\d.]+).*$/,"$1")},Chrome:function(){return o.replace(/^.*Chrome\/([\d.]+).*$/,"$1").replace(/^.*CriOS\/([\d.]+).*$/,"$1")},IE:function(){return o.replace(/^.*MSIE ([\d.]+).*$/,"$1").replace(/^.*rv:([\d.]+).*$/,"$1")},Edge:function(){return o.replace(/^.*Edge\/([\d.]+).*$/,"$1").replace(/^.*Edg\/([\d.]+).*$/,"$1").replace(/^.*EdgA\/([\d.]+).*$/,"$1").replace(/^.*EdgiOS\/([\d.]+).*$/,"$1")},Firefox:function(){return o.replace(/^.*Firefox\/([\d.]+).*$/,"$1").replace(/^.*FxiOS\/([\d.]+).*$/,"$1")},"Firefox Focus":function(){return o.replace(/^.*Focus\/([\d.]+).*$/,"$1")},Chromium:function(){return o.replace(/^.*Chromium\/([\d.]+).*$/,"$1")},Opera:function(){return o.replace(/^.*Opera\/([\d.]+).*$/,"$1").replace(/^.*OPR\/([\d.]+).*$/,"$1")},Vivaldi:function(){return o.replace(/^.*Vivaldi\/([\d.]+).*$/,"$1")},Yandex:function(){return o.replace(/^.*YaBrowser\/([\d.]+).*$/,"$1")},Arora:function(){return o.replace(/^.*Arora\/([\d.]+).*$/,"$1")},Lunascape:function(){return o.replace(/^.*Lunascape[\/\s]([\d.]+).*$/,"$1")},QupZilla:function(){return o.replace(/^.*QupZilla[\/\s]([\d.]+).*$/,"$1")},"Coc Coc":function(){return o.replace(/^.*coc_coc_browser\/([\d.]+).*$/,"$1")},Kindle:function(){return o.replace(/^.*Version\/([\d.]+).*$/,"$1")},Iceweasel:function(){return o.replace(/^.*Iceweasel\/([\d.]+).*$/,"$1")},Konqueror:function(){return o.replace(/^.*Konqueror\/([\d.]+).*$/,"$1")},Iceape:function(){return o.replace(/^.*Iceape\/([\d.]+).*$/,"$1")},SeaMonkey:function(){return o.replace(/^.*SeaMonkey\/([\d.]+).*$/,"$1")},Epiphany:function(){return o.replace(/^.*Epiphany\/([\d.]+).*$/,"$1")},360:function(){return o.replace(/^.*QihooBrowser\/([\d.]+).*$/,"$1")},"360SE":function(){var e={86:"13.0",78:"12.0",69:"11.0",63:"10.0",55:"9.1",45:"8.1",42:"8.0",31:"7.0",21:"6.3"},n=o.replace(/^.*Chrome\/([\d]+).*$/,"$1");return e[n]||""},"360EE":function(){var e={95:"21",86:"13.0",78:"12.0",69:"11.0",63:"9.5",55:"9.0",50:"8.7",30:"7.5"},n=o.replace(/^.*Chrome\/([\d]+).*$/,"$1");return e[n]||""},Maxthon:function(){return o.replace(/^.*Maxthon\/([\d.]+).*$/,"$1")},QQBrowser:function(){return o.replace(/^.*QQBrowser\/([\d.]+).*$/,"$1")},QQ:function(){return o.replace(/^.*QQ\/([\d.]+).*$/,"$1")},Baidu:function(){return o.replace(/^.*BIDUBrowser[\s\/]([\d.]+).*$/,"$1").replace(/^.*baiduboxapp\/([\d.]+).*$/,"$1")},UC:function(){return o.replace(/^.*UC?Browser\/([\d.]+).*$/,"$1")},Sogou:function(){return o.replace(/^.*SE ([\d.X]+).*$/,"$1").replace(/^.*SogouMobileBrowser\/([\d.]+).*$/,"$1")},Liebao:function(){var e="";o.indexOf("LieBaoFast")>-1&&(e=o.replace(/^.*LieBaoFast\/([\d.]+).*$/,"$1"));var n={57:"6.5",49:"6.0",46:"5.9",42:"5.3",39:"5.2",34:"5.0",29:"4.5",21:"4.0"},r=o.replace(/^.*Chrome\/([\d]+).*$/,"$1");return e||n[r]||""},"2345Explorer":function(){var e={69:"10.0",55:"9.9"},n=navigator.userAgent.replace(/^.*Chrome\/([\d]+).*$/,"$1");return e[n]||o.replace(/^.*2345Explorer\/([\d.]+).*$/,"$1").replace(/^.*Mb2345Browser\/([\d.]+).*$/,"$1")},"115Browser":function(){return o.replace(/^.*115Browser\/([\d.]+).*$/,"$1")},TheWorld:function(){return o.replace(/^.*TheWorld ([\d.]+).*$/,"$1")},XiaoMi:function(){return o.replace(/^.*MiuiBrowser\/([\d.]+).*$/,"$1")},Vivo:function(){return o.replace(/^.*VivoBrowser\/([\d.]+).*$/,"$1")},Quark:function(){return o.replace(/^.*Quark\/([\d.]+).*$/,"$1")},Qiyu:function(){return o.replace(/^.*Qiyu\/([\d.]+).*$/,"$1")},Wechat:function(){return o.replace(/^.*MicroMessenger\/([\d.]+).*$/,"$1")},WechatWork:function(){return o.replace(/^.*wxwork\/([\d.]+).*$/,"$1")},Taobao:function(){return o.replace(/^.*AliApp\(TB\/([\d.]+).*$/,"$1")},Alipay:function(){return o.replace(/^.*AliApp\(AP\/([\d.]+).*$/,"$1")},Weibo:function(){return o.replace(/^.*weibo__([\d.]+).*$/,"$1")},Douban:function(){return o.replace(/^.*com.douban.frodo\/([\d.]+).*$/,"$1")},Suning:function(){return o.replace(/^.*SNEBUY-APP([\d.]+).*$/,"$1")},iQiYi:function(){return o.replace(/^.*IqiyiVersion\/([\d.]+).*$/,"$1")},DingTalk:function(){return o.replace(/^.*DingTalk\/([\d.]+).*$/,"$1")},Huawei:function(){return o.replace(/^.*Version\/([\d.]+).*$/,"$1").replace(/^.*HuaweiBrowser\/([\d.]+).*$/,"$1")}};t.version="",x[t.browser]&&(t.version=x[t.browser](),t.version==o&&(t.version="")),"Chrome"==t.browser&&o.match(/\S+Browser/)&&(t.browser=o.match(/\S+Browser/)[0],t.version=o.replace(/^.*Browser\/([\d.]+).*$/,"$1")),"Firefox"!=t.browser||!window.clientInformation&&window.u2f||(t.browser+=" Nightly"),"Edge"==t.browser?t.engine=parseInt(t.version)>75?"Blink":"EdgeHTML":(a["Chrome"]&&"WebKit"==t.engine&&parseInt(x["Chrome"]())>27||"Opera"==t.browser&&parseInt(t.version)>12||"Yandex"==t.browser)&&(t.engine="Blink")}}))},2007:(e,n,r)=>{var i={"./enter.svg":7294,"./tick.svg":5634};function o(e){var n=t(e);return r(n)}function t(e){if(!r.o(i,e)){var n=new Error("Cannot find module '"+e+"'");throw n.code="MODULE_NOT_FOUND",n}return i[e]}o.keys=function(){return Object.keys(i)},o.resolve=t,e.exports=o,o.id=2007},1838:e=>{function n(e){var n=new Error("Cannot find module '"+e+"'");throw n.code="MODULE_NOT_FOUND",n}n.keys=()=>[],n.resolve=n,n.id=1838,e.exports=n},4654:()=>{}},n={};function r(i){var o=n[i];if(void 0!==o)return o.exports;var t=n[i]={exports:{}};return e[i].call(t.exports,t,t.exports,r),t.exports}r.m=e,(()=>{r.amdD=function(){throw new Error("define cannot be used indirect")}})(),(()=>{var e=[];r.O=(n,i,o,t)=>{if(!i){var a=1/0;for(s=0;s<e.length;s++){i=e[s][0],o=e[s][1],t=e[s][2];for(var u=!0,c=0;c<i.length;c++)(!1&t||a>=t)&&Object.keys(r.O).every((e=>r.O[e](i[c])))?i.splice(c--,1):(u=!1,t<a&&(a=t));if(u){e.splice(s--,1);var d=o();void 0!==d&&(n=d)}}return n}t=t||0;for(var s=e.length;s>0&&e[s-1][2]>t;s--)e[s]=e[s-1];e[s]=[i,o,t]}})(),(()=>{r.n=e=>{var n=e&&e.__esModule?()=>e["default"]:()=>e;return r.d(n,{a:n}),n}})(),(()=>{r.d=(e,n)=>{for(var i in n)r.o(n,i)&&!r.o(e,i)&&Object.defineProperty(e,i,{enumerable:!0,get:n[i]})}})(),(()=>{r.f={},r.e=e=>Promise.all(Object.keys(r.f).reduce(((n,i)=>(r.f[i](e,n),n)),[]))})(),(()=>{r.u=e=>"js/"+{441:"user.index",535:"login",623:"claims.list"}[e]+"-legacy."+{441:"9aff94cd",535:"ef28ef40",623:"73a21816"}[e]+".js"})(),(()=>{r.miniCssF=e=>"css/"+{441:"user.index",535:"login",623:"claims.list"}[e]+"."+{441:"7cb24576",535:"99b96c26",623:"907e5f81"}[e]+".css"})(),(()=>{r.g=function(){if("object"===typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(e){if("object"===typeof window)return window}}()})(),(()=>{r.o=(e,n)=>Object.prototype.hasOwnProperty.call(e,n)})(),(()=>{var e={},n="wanjun-center:";r.l=(i,o,t,a)=>{if(e[i])e[i].push(o);else{var u,c;if(void 0!==t)for(var d=document.getElementsByTagName("script"),s=0;s<d.length;s++){var l=d[s];if(l.getAttribute("src")==i||l.getAttribute("data-webpack")==n+t){u=l;break}}u||(c=!0,u=document.createElement("script"),u.charset="utf-8",u.timeout=120,r.nc&&u.setAttribute("nonce",r.nc),u.setAttribute("data-webpack",n+t),u.src=i),e[i]=[o];var f=(n,r)=>{u.onerror=u.onload=null,clearTimeout(p);var o=e[i];if(delete e[i],u.parentNode&&u.parentNode.removeChild(u),o&&o.forEach((e=>e(r))),n)return n(r)},p=setTimeout(f.bind(null,void 0,{type:"timeout",target:u}),12e4);u.onerror=f.bind(null,u.onerror),u.onload=f.bind(null,u.onload),c&&document.head.appendChild(u)}}})(),(()=>{r.r=e=>{"undefined"!==typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}})(),(()=>{r.p=""})(),(()=>{var e=(e,n,r,i)=>{var o=document.createElement("link");o.rel="stylesheet",o.type="text/css";var t=t=>{if(o.onerror=o.onload=null,"load"===t.type)r();else{var a=t&&("load"===t.type?"missing":t.type),u=t&&t.target&&t.target.href||n,c=new Error("Loading CSS chunk "+e+" failed.\n("+u+")");c.code="CSS_CHUNK_LOAD_FAILED",c.type=a,c.request=u,o.parentNode.removeChild(o),i(c)}};return o.onerror=o.onload=t,o.href=n,document.head.appendChild(o),o},n=(e,n)=>{for(var r=document.getElementsByTagName("link"),i=0;i<r.length;i++){var o=r[i],t=o.getAttribute("data-href")||o.getAttribute("href");if("stylesheet"===o.rel&&(t===e||t===n))return o}var a=document.getElementsByTagName("style");for(i=0;i<a.length;i++){o=a[i],t=o.getAttribute("data-href");if(t===e||t===n)return o}},i=i=>new Promise(((o,t)=>{var a=r.miniCssF(i),u=r.p+a;if(n(a,u))return o();e(i,u,o,t)})),o={143:0};r.f.miniCss=(e,n)=>{var r={441:1,535:1,623:1};o[e]?n.push(o[e]):0!==o[e]&&r[e]&&n.push(o[e]=i(e).then((()=>{o[e]=0}),(n=>{throw delete o[e],n})))}})(),(()=>{var e={143:0};r.f.j=(n,i)=>{var o=r.o(e,n)?e[n]:void 0;if(0!==o)if(o)i.push(o[2]);else{var t=new Promise(((r,i)=>o=e[n]=[r,i]));i.push(o[2]=t);var a=r.p+r.u(n),u=new Error,c=i=>{if(r.o(e,n)&&(o=e[n],0!==o&&(e[n]=void 0),o)){var t=i&&("load"===i.type?"missing":i.type),a=i&&i.target&&i.target.src;u.message="Loading chunk "+n+" failed.\n("+t+": "+a+")",u.name="ChunkLoadError",u.type=t,u.request=a,o[1](u)}};r.l(a,c,"chunk-"+n,n)}},r.O.j=n=>0===e[n];var n=(n,i)=>{var o,t,a=i[0],u=i[1],c=i[2],d=0;if(a.some((n=>0!==e[n]))){for(o in u)r.o(u,o)&&(r.m[o]=u[o]);if(c)var s=c(r)}for(n&&n(i);d<a.length;d++)t=a[d],r.o(e,t)&&e[t]&&e[t][0](),e[t]=0;return r.O(s)},i=self["webpackChunkwanjun_center"]=self["webpackChunkwanjun_center"]||[];i.forEach(n.bind(null,0)),i.push=n.bind(null,i.push.bind(i))})();var i=r.O(void 0,[998],(()=>r(2973)));i=r.O(i)})();
//# sourceMappingURL=app-legacy.455616d9.js.map