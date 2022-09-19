(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["weight7~weight8"],{"287e":function(t,e,o){"use strict";
/*!
 * better-scroll / core
 * (c) 2016-2019 ustbhuangyi
 * Released under the MIT License.
 */
/*! *****************************************************************************
Copyright (c) Microsoft Corporation. All rights reserved.
Licensed under the Apache License, Version 2.0 (the "License"); you may not use
this file except in compliance with the License. You may obtain a copy of the
License at http://www.apache.org/licenses/LICENSE-2.0

THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
MERCHANTABLITY OR NON-INFRINGEMENT.

See the Apache Version 2.0 License for specific language governing permissions
and limitations under the License.
***************************************************************************** */var i=function(t,e){return i=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(t,e){t.__proto__=e}||function(t,e){for(var o in e)e.hasOwnProperty(o)&&(t[o]=e[o])},i(t,e)};function n(t,e){function o(){this.constructor=t}i(t,e),t.prototype=null===e?Object.create(e):(o.prototype=e.prototype,new o)}var r=function(){return r=Object.assign||function(t){for(var e,o=1,i=arguments.length;o<i;o++)for(var n in e=arguments[o],e)Object.prototype.hasOwnProperty.call(e,n)&&(t[n]=e[n]);return t},r.apply(this,arguments)};function s(t){console.error("[BScroll warn]: "+t)}var h="undefined"!==typeof window,c=h&&navigator.userAgent.toLowerCase(),a=c&&/wechatdevtools/.test(c),l=c&&c.indexOf("android")>0;function u(){return window.performance&&window.performance.now?window.performance.now()+window.performance.timing.navigationStart:+new Date}function p(t){for(var e=[],o=1;o<arguments.length;o++)e[o-1]=arguments[o];for(var i=0;i<e.length;i++){var n=e[i];for(var r in n)t[r]=n[r]}return t}function f(t){return void 0===t||null===t}var d=h&&document.createElement("div").style,v=function(){if(!h)return!1;var t={webkit:"webkitTransform",Moz:"MozTransform",O:"OTransform",ms:"msTransform",standard:"transform"};for(var e in t)if(void 0!==d[t[e]])return e;return!1}();function m(t){return!1===v?t:"standard"===v?"transitionEnd"===t?"transitionend":t:v+t.charAt(0).toUpperCase()+t.substr(1)}function y(t){return"string"===typeof t?document.querySelector(t):t}function g(t,e,o,i){t.addEventListener(e,o,{passive:!1,capture:!!i})}function k(t,e,o,i){t.removeEventListener(e,o,{capture:!!i})}function b(t){var e=0,o=0;while(t)e-=t.offsetLeft,o-=t.offsetTop,t=t.offsetParent;return{left:e,top:o}}v&&"standard"!==v&&v.toLowerCase();var T=m("transform"),P=m("transition"),w=h&&m("perspective")in d,S=h&&("ontouchstart"in window||a),E=h&&P in d,O={transform:T,transition:P,transitionTimingFunction:m("transitionTimingFunction"),transitionDuration:m("transitionDuration"),transitionDelay:m("transitionDelay"),transformOrigin:m("transformOrigin"),transitionEnd:m("transitionEnd")},D={touchstart:1,touchmove:1,touchend:1,mousedown:2,mousemove:2,mouseup:2};function B(t){if(t instanceof window.SVGElement){var e=t.getBoundingClientRect();return{top:e.top,left:e.left,width:e.width,height:e.height}}return{top:t.offsetTop,left:t.offsetLeft,width:t.offsetWidth,height:t.offsetHeight}}function Y(t,e){for(var o in e)if(e[o].test(t[o]))return!0;return!1}var X=Y;function _(t,e){var o=document.createEvent("Event");o.initEvent(e,!0,!0),o.pageX=t.pageX,o.pageY=t.pageY,t.target.dispatchEvent(o)}function x(t,e){var o;void 0===e&&(e="click"),"mouseup"===t.type?o=t:"touchend"!==t.type&&"touchcancel"!==t.type||(o=t.changedTouches[0]);var i,n={};o&&(n.screenX=o.screenX||0,n.screenY=o.screenY||0,n.clientX=o.clientX||0,n.clientY=o.clientY||0);var r=!0,s=!0;if("undefined"!==typeof MouseEvent)try{i=new MouseEvent(e,p({bubbles:r,cancelable:s},n))}catch(t){h()}else h();function h(){i=document.createEvent("Event"),i.initEvent(e,r,s),p(i,n)}i.forwardedTouchEvent=!0,i._constructed=!0,t.target.dispatchEvent(i)}function L(t){x(t,"dblclick")}var M={swipe:{style:"cubic-bezier(0.23, 1, 0.32, 1)",fn:function(t){return 1+--t*t*t*t*t}},swipeBounce:{style:"cubic-bezier(0.25, 0.46, 0.45, 0.94)",fn:function(t){return t*(2-t)}},bounce:{style:"cubic-bezier(0.165, 0.84, 0.44, 1)",fn:function(t){return 1- --t*t*t*t}}},C=100/60,j=h&&window;function z(){}var H=function(){return h?j.requestAnimationFrame||j.webkitRequestAnimationFrame||j.mozRequestAnimationFrame||j.oRequestAnimationFrame||function(t){return window.setTimeout(t,(t.interval||C)/2)}:z}(),A=function(){return h?j.cancelAnimationFrame||j.webkitCancelAnimationFrame||j.mozCancelAnimationFrame||j.oCancelAnimationFrame||function(t){window.clearTimeout(t)}:z}(),F=function(t){},N={enumerable:!0,configurable:!0,get:F,set:F},R=function(t,e){for(var o=e.split("."),i=0;i<o.length-1;i++)if(t=t[o[i]],"object"!==typeof t||!t)return;var n=o.pop();return"function"===typeof t[n]?function(){return t[n].apply(t,arguments)}:t[n]},K=function(t,e,o){for(var i,n=e.split("."),r=0;r<n.length-1;r++)i=n[r],t[i]||(t[i]={}),t=t[i];t[n.pop()]=o};function I(t,e,o){N.get=function(){return R(this,e)},N.set=function(t){K(this,e,t)},Object.defineProperty(t,o,N)}var V,W,q,U,Z,J,$=function(){function t(t){this.events={},this.eventTypes={},this.registerType(t)}return t.prototype.on=function(t,e,o){return void 0===o&&(o=this),this._checkInTypes(t),this.events[t]||(this.events[t]=[]),this.events[t].push([e,o]),this},t.prototype.once=function(t,e,o){var i=this;void 0===o&&(o=this),this._checkInTypes(t);var n=function(){for(var r=[],s=0;s<arguments.length;s++)r[s]=arguments[s];i.off(t,n),e.apply(o,r)};return n.fn=e,this.on(t,n),this},t.prototype.off=function(t,e){if(!t&&!e)return this.events={},this;if(t){if(this._checkInTypes(t),!e)return this.events[t]=[],this;var o=this.events[t];if(!o)return this;var i=o.length;while(i--)(o[i][0]===e||o[i][0]&&o[i][0].fn===e)&&o.splice(i,1);return this}},t.prototype.trigger=function(t){for(var e=[],o=1;o<arguments.length;o++)e[o-1]=arguments[o];this._checkInTypes(t);var i=this.events[t];if(i)for(var n,r=i.length,s=i.slice(),h=0;h<r;h++){var c=s[h],a=c[0],l=c[1];if(a&&(n=a.apply(l,e),!0===n))return n}},t.prototype.registerType=function(t){var e=this;t.forEach((function(t){e.eventTypes[t]=t}))},t.prototype.destroy=function(){this.events={},this.eventTypes={}},t.prototype._checkInTypes=function(t){var e=this.eventTypes,o=e[t]===t;o||s('EventEmitter has used unknown event type: "'+t+'", should be oneof ['+Object.keys(e)+"]")},t}();(function(t){t[t["Positive"]=1]="Positive",t[t["Negative"]=-1]="Negative",t[t["Default"]=0]="Default"})(V||(V={})),function(t){t["Default"]="",t["Horizontal"]="horizontal",t["Vertical"]="vertical",t["None"]="none"}(W||(W={})),function(t){t["None"]="",t["Horizontal"]="horizontal",t["Vertical"]="vertical"}(q||(q={})),function(t){t[t["Touch"]=1]="Touch",t[t["Mouse"]=2]="Mouse"}(U||(U={})),function(t){t[t["Left"]=0]="Left",t[t["Middle"]=1]="Middle",t[t["Right"]=2]="Right"}(Z||(Z={})),function(t){t[t["Default"]=0]="Default",t[t["Throttle"]=1]="Throttle",t[t["Normal"]=2]="Normal",t[t["Realtime"]=3]="Realtime"}(J||(J={}));var G=function(){function t(){this.startX=0,this.startY=0,this.scrollX=!1,this.scrollY=!0,this.freeScroll=!1,this.directionLockThreshold=5,this.eventPassthrough=q.None,this.click=!1,this.dblclick=!1,this.tap="",this.bounce={top:!0,bottom:!0,left:!0,right:!0},this.bounceTime=800,this.momentum=!0,this.momentumLimitTime=300,this.momentumLimitDistance=15,this.swipeTime=2500,this.swipeBounceTime=500,this.deceleration=.0015,this.flickLimitTime=200,this.flickLimitDistance=100,this.resizePolling=60,this.probeType=J.Default,this.stopPropagation=!1,this.preventDefault=!0,this.preventDefaultException={tagName:/^(INPUT|TEXTAREA|BUTTON|SELECT|AUDIO)$/},this.tagException={tagName:/^TEXTAREA$/},this.HWCompositing=!0,this.useTransition=!0,this.bindToWrapper=!1,this.disableMouse=S,this.disableTouch=!S,this.autoBlur=!0}return t.prototype.merge=function(t){if(!t)return this;for(var e in t)this[e]=t[e];return this},t.prototype.process=function(){return this.translateZ=this.HWCompositing&&w?" translateZ(0)":"",this.useTransition=this.useTransition&&E,this.preventDefault=!this.eventPassthrough&&this.preventDefault,this.scrollX=this.eventPassthrough!==q.Horizontal&&this.scrollX,this.scrollY=this.eventPassthrough!==q.Vertical&&this.scrollY,this.freeScroll=this.freeScroll&&!this.eventPassthrough,this.scrollX=!!this.freeScroll||this.scrollX,this.scrollY=!!this.freeScroll||this.scrollY,this.directionLockThreshold=this.eventPassthrough?0:this.directionLockThreshold,this},t}(),Q=function(){function t(t,e){this.wrapper=t,this.events=e,this.addDOMEvents()}return t.prototype.destroy=function(){this.removeDOMEvents(),this.events=[]},t.prototype.addDOMEvents=function(){this.handleDOMEvents(g)},t.prototype.removeDOMEvents=function(){this.handleDOMEvents(k)},t.prototype.handleDOMEvents=function(t){var e=this,o=this.wrapper;this.events.forEach((function(i){t(o,i.name,e,!!i.capture)}))},t.prototype.handleEvent=function(t){var e=t.type;this.events.some((function(o){return o.name===e&&(o.handler(t),!0)}))},t}(),tt=function(){function t(t,e){this.wrapper=t,this.options=e,this.hooks=new $(["beforeStart","start","move","end","click"]),this.handleDOMEvents()}return t.prototype.handleDOMEvents=function(){var t=this.options,e=t.bindToWrapper,o=t.disableMouse,i=t.disableTouch,n=t.click,r=this.wrapper,s=e?r:window,h=[],c=[],a=S&&!i,l=!o;n&&h.push({name:"click",handler:this.click.bind(this),capture:!0}),a&&(h.push({name:"touchstart",handler:this.start.bind(this)}),c.push({name:"touchmove",handler:this.move.bind(this)},{name:"touchend",handler:this.end.bind(this)},{name:"touchcancel",handler:this.end.bind(this)})),l&&(h.push({name:"mousedown",handler:this.start.bind(this)}),c.push({name:"mousemove",handler:this.move.bind(this)},{name:"mouseup",handler:this.end.bind(this)})),this.wrapperEventRegister=new Q(r,h),this.targetEventRegister=new Q(s,c)},t.prototype.beforeHandler=function(t,e){var o=this.options,i=o.preventDefault,n=o.stopPropagation,r=o.preventDefaultException,s={start:function(){return i&&!Y(t.target,r)},end:function(){return i&&!Y(t.target,r)},move:function(){return i}};s[e]()&&t.preventDefault(),n&&t.stopPropagation()},t.prototype.setInitiated=function(t){void 0===t&&(t=0),this.initiated=t},t.prototype.start=function(t){var e=D[t.type];if(!this.initiated||this.initiated===e)if(this.setInitiated(e),X(t.target,this.options.tagException))this.setInitiated();else if((e!==U.Mouse||t.button===Z.Left)&&!this.hooks.trigger(this.hooks.eventTypes.beforeStart,t)){this.beforeHandler(t,"start");var o=t.touches?t.touches[0]:t;this.pointX=o.pageX,this.pointY=o.pageY,this.hooks.trigger(this.hooks.eventTypes.start,t)}},t.prototype.move=function(t){if(D[t.type]===this.initiated){this.beforeHandler(t,"move");var e=t.touches?t.touches[0]:t,o=e.pageX-this.pointX,i=e.pageY-this.pointY;if(this.pointX=e.pageX,this.pointY=e.pageY,!this.hooks.trigger(this.hooks.eventTypes.move,{deltaX:o,deltaY:i,e:t})){var n=document.documentElement.scrollLeft||window.pageXOffset||document.body.scrollLeft,r=document.documentElement.scrollTop||window.pageYOffset||document.body.scrollTop,s=this.pointX-n,h=this.pointY-r;(s>document.documentElement.clientWidth-this.options.momentumLimitDistance||s<this.options.momentumLimitDistance||h<this.options.momentumLimitDistance||h>document.documentElement.clientHeight-this.options.momentumLimitDistance)&&this.end(t)}}},t.prototype.end=function(t){D[t.type]===this.initiated&&(this.setInitiated(),this.beforeHandler(t,"end"),this.hooks.trigger(this.hooks.eventTypes.end,t))},t.prototype.click=function(t){this.hooks.trigger(this.hooks.eventTypes.click,t)},t.prototype.destroy=function(){this.wrapperEventRegister.destroy(),this.targetEventRegister.destroy(),this.hooks.destroy()},t}(),et={x:["translateX","px"],y:["translateY","px"]},ot=function(){function t(t){this.content=t,this.style=t.style,this.hooks=new $(["beforeTranslate","translate"])}return t.prototype.getComputedPosition=function(){var t=window.getComputedStyle(this.content,null),e=t[O.transform].split(")")[0].split(", "),o=+(e[12]||e[4]),i=+(e[13]||e[5]);return{x:o,y:i}},t.prototype.translate=function(t){var e=[];Object.keys(t).forEach((function(o){if(et[o]){var i=et[o][0];if(i){var n=et[o][1],r=t[o];e.push(i+"("+r+n+")")}}})),this.hooks.trigger(this.hooks.eventTypes.beforeTranslate,e,t),this.style[O.transform]=""+e.join(" "),this.hooks.trigger(this.hooks.eventTypes.translate,t)},t.prototype.destroy=function(){this.hooks.destroy()},t}(),it=function(){function t(t,e,o){this.content=t,this.translater=e,this.options=o,this.hooks=new $(["move","end","beforeForceStop","forceStop","time","timeFunction"]),this.style=t.style}return t.prototype.translate=function(t){this.translater.translate(t)},t.prototype.setPending=function(t){this.pending=t},t.prototype.setForceStopped=function(t){this.forceStopped=t},t.prototype.destroy=function(){this.hooks.destroy(),A(this.timer)},t}(),nt=function(t){function e(){return null!==t&&t.apply(this,arguments)||this}return n(e,t),e.prototype.startProbe=function(){var t=this,e=function(){var o=t.translater.getComputedPosition();t.hooks.trigger(t.hooks.eventTypes.move,o),t.pending?t.timer=H(e):t.hooks.trigger(t.hooks.eventTypes.end,o)};A(this.timer),this.timer=H(e)},e.prototype.transitionTime=function(t){void 0===t&&(t=0),this.style[O.transitionDuration]=t+"ms",this.hooks.trigger(this.hooks.eventTypes.time,t)},e.prototype.transitionTimingFunction=function(t){this.style[O.transitionTimingFunction]=t,this.hooks.trigger(this.hooks.eventTypes.timeFunction,t)},e.prototype.move=function(t,e,o,i,n){this.setPending(o>0&&(t.x!==e.x||t.y!==e.y)),this.transitionTimingFunction(i),this.transitionTime(o),this.translate(e),o&&this.options.probeType===J.Realtime&&this.startProbe(),o||(this._reflow=this.content.offsetHeight),o||n||(this.hooks.trigger(this.hooks.eventTypes.move,e),this.hooks.trigger(this.hooks.eventTypes.end,e))},e.prototype.stop=function(){if(this.pending){this.setPending(!1),A(this.timer);var t=this.translater.getComputedPosition(),e=t.x,o=t.y;if(this.transitionTime(),this.translate({x:e,y:o}),this.setForceStopped(!0),this.hooks.trigger(this.hooks.eventTypes.beforeForceStop,{x:e,y:o}))return;this.hooks.trigger(this.hooks.eventTypes.forceStop,{x:e,y:o})}},e}(it),rt=function(t){function e(){return null!==t&&t.apply(this,arguments)||this}return n(e,t),e.prototype.move=function(t,e,o,i,n){if(!o){if(this.translate(e),this._reflow=this.content.offsetHeight,n)return;return this.hooks.trigger(this.hooks.eventTypes.move,e),void this.hooks.trigger(this.hooks.eventTypes.end,e)}this.animate(t,e,o,i)},e.prototype.animate=function(t,e,o,i){var n=this,r=u(),s=r+o,h=function(){var c=u();if(c>=s)return n.translate(e),n.hooks.trigger(n.hooks.eventTypes.move,e),void n.hooks.trigger(n.hooks.eventTypes.end,e);c=(c-r)/o;var a=i(c),l={};Object.keys(e).forEach((function(o){var i=t[o],n=e[o];l[o]=(n-i)*a+i})),n.translate(l),n.pending&&(n.timer=H(h)),n.options.probeType===J.Realtime&&n.hooks.trigger(n.hooks.eventTypes.move,l)};this.setPending(!0),A(this.timer),h()},e.prototype.stop=function(){if(this.pending){this.setPending(!1),A(this.timer);var t=this.translater.getComputedPosition();if(this.setForceStopped(!0),this.hooks.trigger(this.hooks.eventTypes.beforeForceStop,t))return;this.hooks.trigger(this.hooks.eventTypes.forceStop,t)}},e}(it);function st(t,e,o){var i=o.useTransition,n={};return Object.defineProperty(n,"probeType",{enumerable:!0,configurable:!1,get:function(){return o.probeType}}),i?new nt(t,e,n):new rt(t,e,n)}var ht,ct,at,lt,ut,pt=function(){function t(t,e){this.wrapper=t,this.options=e,this.hooks=new $(["momentum","end"]),this.content=this.wrapper.children[0],this.currentPos=0,this.startPos=0}return t.prototype.start=function(){this.direction=V.Default,this.movingDirection=V.Default,this.dist=0},t.prototype.move=function(t){t=this.hasScroll?t:0,this.movingDirection=t>0?V.Negative:t<0?V.Positive:V.Default;var e=this.currentPos+t;return(e>this.minScrollPos||e<this.maxScrollPos)&&(e=e>this.minScrollPos&&this.options.bounces[0]||e<this.maxScrollPos&&this.options.bounces[1]?this.currentPos+t/3:e>this.minScrollPos?this.minScrollPos:this.maxScrollPos),e},t.prototype.end=function(t){var e={duration:0},o=Math.abs(this.currentPos-this.startPos);if(this.options.momentum&&t<this.options.momentumLimitTime&&o>this.options.momentumLimitDistance){var i=this.direction===V.Negative&&this.options.bounces[0]||this.direction===V.Positive&&this.options.bounces[1]?this.wrapperSize:0;e=this.hasScroll?this.momentum(this.currentPos,this.startPos,t,this.maxScrollPos,this.minScrollPos,i,this.options):{destination:this.currentPos,duration:0}}else this.hooks.trigger(this.hooks.eventTypes.end,e);return e},t.prototype.momentum=function(t,e,o,i,n,r,s){void 0===s&&(s=this.options);var h=t-e,c=Math.abs(h)/o,a=s.deceleration,l=s.swipeBounceTime,u=s.swipeTime,p={destination:t+c/a*(h<0?-1:1),duration:u,rate:15};return this.hooks.trigger(this.hooks.eventTypes.momentum,p,h),p.destination<i?(p.destination=r?Math.max(i-r/4,i-r/p.rate*c):i,p.duration=l):p.destination>n&&(p.destination=r?Math.min(n+r/4,n+r/p.rate*c):n,p.duration=l),p.destination=Math.round(p.destination),p},t.prototype.updateDirection=function(){var t=Math.round(this.currentPos)-this.absStartPos;this.direction=t>0?V.Negative:t<0?V.Positive:V.Default},t.prototype.refresh=function(){var t=this.options.rect,e=t.size,o=t.position,i="static"===window.getComputedStyle(this.wrapper,null).position,n=B(this.wrapper);this.wrapperSize=n[e];var r=B(this.content);this.contentSize=r[e],this.relativeOffset=r[o],i&&(this.relativeOffset-=n[o]),this.minScrollPos=0,this.maxScrollPos=this.wrapperSize-this.contentSize,this.maxScrollPos<0&&(this.maxScrollPos-=this.relativeOffset,this.minScrollPos=-this.relativeOffset),this.hasScroll=this.options.scrollable&&this.maxScrollPos<this.minScrollPos,this.hasScroll||(this.maxScrollPos=this.minScrollPos,this.contentSize=this.wrapperSize),this.direction=0},t.prototype.updatePosition=function(t){this.currentPos=t},t.prototype.getCurrentPos=function(){return Math.round(this.currentPos)},t.prototype.checkInBoundary=function(){var t=this.adjustPosition(this.currentPos),e=t===this.getCurrentPos();return{position:t,inBoundary:e}},t.prototype.adjustPosition=function(t){var e=Math.round(t);return!this.hasScroll||e>this.minScrollPos?e=this.minScrollPos:e<this.maxScrollPos&&(e=this.maxScrollPos),e},t.prototype.updateStartPos=function(){this.startPos=this.currentPos},t.prototype.updateAbsStartPos=function(){this.absStartPos=this.currentPos},t.prototype.resetStartPos=function(){this.updateStartPos(),this.updateAbsStartPos()},t.prototype.getAbsDist=function(t){return this.dist+=t,Math.abs(this.dist)},t.prototype.destroy=function(){this.hooks.destroy()},t}();(function(t){t["Yes"]="yes",t["No"]="no"})(ut||(ut={}));var ft=(ht={},ht[ut.Yes]=function(t){return!0},ht[ut.No]=function(t){return t.preventDefault(),!1},ht),dt=(ct={},ct[W.Horizontal]=(at={},at[ut.Yes]=q.Horizontal,at[ut.No]=q.Vertical,at),ct[W.Vertical]=(lt={},lt[ut.Yes]=q.Vertical,lt[ut.No]=q.Horizontal,lt),ct),vt=function(){function t(t,e,o){this.directionLockThreshold=t,this.freeScroll=e,this.eventPassthrough=o,this.reset()}return t.prototype.reset=function(){this.directionLocked=W.Default},t.prototype.checkMovingDirection=function(t,e,o){return this.computeDirectionLock(t,e),this.handleEventPassthrough(o)},t.prototype.adjustDelta=function(t,e){return this.directionLocked===W.Horizontal?e=0:this.directionLocked===W.Vertical&&(t=0),{deltaX:t,deltaY:e}},t.prototype.computeDirectionLock=function(t,e){this.directionLocked!==W.Default||this.freeScroll||(t>e+this.directionLockThreshold?this.directionLocked=W.Horizontal:e>=t+this.directionLockThreshold?this.directionLocked=W.Vertical:this.directionLocked=W.None)},t.prototype.handleEventPassthrough=function(t){var e=dt[this.directionLocked];if(e){if(this.eventPassthrough===e[ut.Yes])return ft[ut.Yes](t);if(this.eventPassthrough===e[ut.No])return ft[ut.No](t)}return!1},t}(),mt=function(){function t(t,e,o,i,n){this.hooks=new $(["start","beforeMove","scrollStart","scroll","beforeEnd","end","scrollEnd"]),this.scrollBehaviorX=t,this.scrollBehaviorY=e,this.actionsHandler=o,this.animater=i,this.options=n,this.directionLockAction=new vt(n.directionLockThreshold,n.freeScroll,n.eventPassthrough),this.enabled=!0,this.bindActionsHandler()}return t.prototype.bindActionsHandler=function(){var t=this;this.actionsHandler.hooks.on(this.actionsHandler.hooks.eventTypes.start,(function(e){return!t.enabled||t.handleStart(e)})),this.actionsHandler.hooks.on(this.actionsHandler.hooks.eventTypes.move,(function(e){var o=e.deltaX,i=e.deltaY,n=e.e;return!t.enabled||t.handleMove(o,i,n)})),this.actionsHandler.hooks.on(this.actionsHandler.hooks.eventTypes.end,(function(e){return!t.enabled||t.handleEnd(e)})),this.actionsHandler.hooks.on(this.actionsHandler.hooks.eventTypes.click,(function(e){t.enabled&&!e._constructed&&t.handleClick(e)}))},t.prototype.handleStart=function(t){var e=u();this.moved=!1,this.startTime=e,this.directionLockAction.reset(),this.scrollBehaviorX.start(),this.scrollBehaviorY.start(),this.animater.stop(),this.scrollBehaviorX.resetStartPos(),this.scrollBehaviorY.resetStartPos(),this.hooks.trigger(this.hooks.eventTypes.start,t)},t.prototype.handleMove=function(t,e,o){if(!this.hooks.trigger(this.hooks.eventTypes.beforeMove,o)){var i=this.scrollBehaviorX.getAbsDist(t),n=this.scrollBehaviorY.getAbsDist(e),r=u();if(this.checkMomentum(i,n,r))return!0;if(this.directionLockAction.checkMovingDirection(i,n,o))return this.actionsHandler.setInitiated(),!0;var s=this.directionLockAction.adjustDelta(t,e),h=this.scrollBehaviorX.move(s.deltaX),c=this.scrollBehaviorY.move(s.deltaY);this.moved||(this.moved=!0,this.hooks.trigger(this.hooks.eventTypes.scrollStart)),this.animater.translate({x:h,y:c}),this.dispatchScroll(r)}},t.prototype.dispatchScroll=function(t){t-this.startTime>this.options.momentumLimitTime&&(this.startTime=t,this.scrollBehaviorX.updateStartPos(),this.scrollBehaviorY.updateStartPos(),this.options.probeType===J.Throttle&&this.hooks.trigger(this.hooks.eventTypes.scroll,this.getCurrentPos())),this.options.probeType>J.Throttle&&this.hooks.trigger(this.hooks.eventTypes.scroll,this.getCurrentPos())},t.prototype.checkMomentum=function(t,e,o){return o-this.endTime>this.options.momentumLimitTime&&e<this.options.momentumLimitDistance&&t<this.options.momentumLimitDistance},t.prototype.handleEnd=function(t){if(!this.hooks.trigger(this.hooks.eventTypes.beforeEnd,t)){var e=this.getCurrentPos();if(this.scrollBehaviorX.updateDirection(),this.scrollBehaviorY.updateDirection(),this.hooks.trigger(this.hooks.eventTypes.end,t,e))return!0;this.animater.translate(e),this.endTime=u();var o=this.endTime-this.startTime;this.hooks.trigger(this.hooks.eventTypes.scrollEnd,e,o)}},t.prototype.handleClick=function(t){Y(t.target,this.options.preventDefaultException)||(t.preventDefault(),t.stopPropagation())},t.prototype.getCurrentPos=function(){return{x:this.scrollBehaviorX.getCurrentPos(),y:this.scrollBehaviorY.getCurrentPos()}},t.prototype.refresh=function(){this.endTime=0},t.prototype.destroy=function(){this.hooks.destroy()},t}();function yt(t){var e=["click","bindToWrapper","disableMouse","disableTouch","preventDefault","stopPropagation","tagException","preventDefaultException"].reduce((function(e,o){return e[o]=t[o],e}),{});return e}function gt(t,e,o,i){var n=["momentum","momentumLimitTime","momentumLimitDistance","deceleration","swipeBounceTime","swipeTime"].reduce((function(e,o){return e[o]=t[o],e}),{});return n.scrollable=t[e],n.bounces=o,n.rect=i,n}function kt(t,e,o){o.forEach((function(o){var i,n;"string"===typeof o?i=n=o:(i=o.source,n=o.target),t.on(i,(function(){for(var t=[],o=0;o<arguments.length;o++)t[o]=arguments[o];return e.trigger.apply(e,[n].concat(t))}))}))}var bt,Tt=function(){function t(t,e){this.hooks=new $(["beforeStart","beforeMove","beforeScrollStart","scrollStart","scroll","beforeEnd","scrollEnd","refresh","touchEnd","end","flick","scrollCancel","momentum","scrollTo","ignoreDisMoveForSamePos","scrollToElement"]),this.wrapper=t,this.content=t.children[0],this.options=e;var o=this.options.bounce,i=o.left,n=void 0===i||i,r=o.right,s=void 0===r||r,h=o.top,c=void 0===h||h,a=o.bottom,l=void 0===a||a;this.scrollBehaviorX=new pt(t,gt(e,"scrollX",[n,s],{size:"width",position:"left"})),this.scrollBehaviorY=new pt(t,gt(e,"scrollY",[c,l],{size:"height",position:"top"})),this.translater=new ot(this.content),this.animater=st(this.content,this.translater,this.options),this.actionsHandler=new tt(t,yt(this.options)),this.actions=new mt(this.scrollBehaviorX,this.scrollBehaviorY,this.actionsHandler,this.animater,this.options);var u=this.resize.bind(this);this.resizeRegister=new Q(window,[{name:"orientationchange",handler:u},{name:"resize",handler:u}]),this.transitionEndRegister=new Q(this.content,[{name:O.transitionEnd,handler:this.transitionEnd.bind(this)}]),this.init()}return t.prototype.init=function(){var t=this;this.bindTranslater(),this.bindAnimater(),this.bindActions(),this.hooks.on(this.hooks.eventTypes.scrollEnd,(function(){t.togglePointerEvents(!0)}))},t.prototype.bindTranslater=function(){var t=this,e=this.translater.hooks;e.on(e.eventTypes.beforeTranslate,(function(e){t.options.translateZ&&e.push(t.options.translateZ)})),e.on(e.eventTypes.translate,(function(e){t.updatePositions(e),t.togglePointerEvents(!1)}))},t.prototype.bindAnimater=function(){var t=this;this.animater.hooks.on(this.animater.hooks.eventTypes.end,(function(e){t.resetPosition(t.options.bounceTime)||(t.animater.setPending(!1),t.hooks.trigger(t.hooks.eventTypes.scrollEnd,e))})),kt(this.animater.hooks,this.hooks,[{source:this.animater.hooks.eventTypes.move,target:this.hooks.eventTypes.scroll},{source:this.animater.hooks.eventTypes.forceStop,target:this.hooks.eventTypes.scrollEnd}])},t.prototype.bindActions=function(){var t=this,e=this.actions;kt(e.hooks,this.hooks,[{source:e.hooks.eventTypes.start,target:this.hooks.eventTypes.beforeStart},{source:e.hooks.eventTypes.start,target:this.hooks.eventTypes.beforeScrollStart},{source:e.hooks.eventTypes.beforeMove,target:this.hooks.eventTypes.beforeMove},{source:e.hooks.eventTypes.scrollStart,target:this.hooks.eventTypes.scrollStart},{source:e.hooks.eventTypes.scroll,target:this.hooks.eventTypes.scroll},{source:e.hooks.eventTypes.beforeEnd,target:this.hooks.eventTypes.beforeEnd}]),e.hooks.on(e.hooks.eventTypes.end,(function(o,i){return t.hooks.trigger(t.hooks.eventTypes.touchEnd,i),!!t.hooks.trigger(t.hooks.eventTypes.end,i)||(!e.moved&&t.checkClick(o)?(t.animater.setForceStopped(!1),t.hooks.trigger(t.hooks.eventTypes.scrollCancel),!0):(t.animater.setForceStopped(!1),!!t.resetPosition(t.options.bounceTime,M.bounce)||void 0))})),e.hooks.on(e.hooks.eventTypes.scrollEnd,(function(e,o){var i=Math.abs(e.x-t.scrollBehaviorX.startPos),n=Math.abs(e.y-t.scrollBehaviorY.startPos);t.checkFlick(o,i,n)?t.hooks.trigger(t.hooks.eventTypes.flick):t.momentum(e,o)||t.hooks.trigger(t.hooks.eventTypes.scrollEnd,e)}))},t.prototype.checkFlick=function(t,e,o){if(this.hooks.events.flick.length>1&&t<this.options.flickLimitTime&&e<this.options.flickLimitDistance&&o<this.options.flickLimitDistance)return!0},t.prototype.momentum=function(t,e){var o={time:0,easing:M.swiper,newX:t.x,newY:t.y},i=this.scrollBehaviorX.end(e),n=this.scrollBehaviorY.end(e);if(o.newX=f(i.destination)?o.newX:i.destination,o.newY=f(n.destination)?o.newY:n.destination,o.time=Math.max(i.duration,n.duration),this.hooks.trigger(this.hooks.eventTypes.momentum,o,this),o.newX!==t.x||o.newY!==t.y)return(o.newX>this.scrollBehaviorX.minScrollPos||o.newX<this.scrollBehaviorX.maxScrollPos||o.newY>this.scrollBehaviorY.minScrollPos||o.newY<this.scrollBehaviorY.maxScrollPos)&&(o.easing=M.swipeBounce),this.scrollTo(o.newX,o.newY,o.time,o.easing),!0},t.prototype.checkClick=function(t){var e={preventClick:this.animater.forceStopped};if(this.hooks.trigger(this.hooks.eventTypes.checkClick))return!0;if(!e.preventClick){var o=this.options.dblclick,i=!1;if(o&&this.lastClickTime){var n=o.delay,r=void 0===n?300:n;u()-this.lastClickTime<r&&(i=!0,L(t))}return this.options.tap&&_(t,this.options.tap),this.options.click&&!Y(t.target,this.options.preventDefaultException)&&x(t),this.lastClickTime=i?null:u(),!0}return!1},t.prototype.resize=function(){var t=this;this.actions.enabled&&(l&&(this.wrapper.scrollTop=0),clearTimeout(this.resizeTimeout),this.resizeTimeout=window.setTimeout((function(){t.refresh()}),this.options.resizePolling))},t.prototype.transitionEnd=function(t){if(t.target===this.content&&this.animater.pending){var e=this.animater;e.transitionTime(),this.resetPosition(this.options.bounceTime,M.bounce)||(this.animater.setPending(!1),this.options.probeType!==J.Realtime&&this.hooks.trigger(this.hooks.eventTypes.scrollEnd,this.getCurrentPos()))}},t.prototype.togglePointerEvents=function(t){void 0===t&&(t=!0);for(var e=this.content.children.length?this.content.children:[this.content],o=t?"auto":"none",i=0;i<e.length;i++){var n=e[i];n.isBScroll||(n.style.pointerEvents=o)}},t.prototype.refresh=function(){this.scrollBehaviorX.refresh(),this.scrollBehaviorY.refresh(),this.actions.refresh(),this.wrapperOffset=b(this.wrapper)},t.prototype.scrollBy=function(t,e,o,i){void 0===o&&(o=0);var n=this.getCurrentPos(),r=n.x,s=n.y;i=i||M.bounce,t+=r,e+=s,this.scrollTo(t,e,o,i)},t.prototype.scrollTo=function(t,e,o,i,n,s){void 0===o&&(o=0),void 0===n&&(n={start:{},end:{}}),i=i||M.bounce;var h=this.options.useTransition?i.style:i.fn,c=this.getCurrentPos(),a=r({x:c.x,y:c.y},n.start),l=r({x:t,y:e},n.end);this.hooks.trigger(this.hooks.eventTypes.scrollTo,l),(this.hooks.trigger(this.hooks.eventTypes.ignoreDisMoveForSamePos)||a.x!==l.x||a.y!==l.y)&&this.animater.move(a,l,o,h,s)},t.prototype.scrollToElement=function(t,e,o,i,n){var r=y(t),s=b(r),h=function(t,e,o){return"number"===typeof t?t:t?Math.round(e/2-o/2):0};o=h(o,r.offsetWidth,this.wrapper.offsetWidth),i=h(i,r.offsetHeight,this.wrapper.offsetHeight);var c=function(t,e,o,i){return t-=e,t=i.adjustPosition(t-o),t};s.left=c(s.left,this.wrapperOffset.left,o,this.scrollBehaviorX),s.top=c(s.top,this.wrapperOffset.top,i,this.scrollBehaviorY),this.hooks.trigger(this.hooks.eventTypes.scrollToElement,r,s)||this.scrollTo(s.left,s.top,e,n)},t.prototype.resetPosition=function(t,e){void 0===t&&(t=0),e=e||M.bounce;var o=this.scrollBehaviorX.checkInBoundary(),i=o.position,n=o.inBoundary,r=this.scrollBehaviorY.checkInBoundary(),s=r.position,h=r.inBoundary;return(!n||!h)&&(this.scrollTo(i,s,t,e),!0)},t.prototype.updatePositions=function(t){this.scrollBehaviorX.updatePosition(t.x),this.scrollBehaviorY.updatePosition(t.y)},t.prototype.getCurrentPos=function(){return this.actions.getCurrentPos()},t.prototype.enable=function(){this.actions.enabled=!0},t.prototype.disable=function(){A(this.animater.timer),this.actions.enabled=!1},t.prototype.destroy=function(){var t=this,e=["resizeRegister","transitionEndRegister","actionsHandler","actions","hooks","animater","translater","scrollBehaviorX","scrollBehaviorY"];e.forEach((function(e){return t[e].destroy()}))},t}(),Pt=[{sourceKey:"scroller.scrollBehaviorX.currentPos",key:"x"},{sourceKey:"scroller.scrollBehaviorY.currentPos",key:"y"},{sourceKey:"scroller.scrollBehaviorX.hasScroll",key:"hasHorizontalScroll"},{sourceKey:"scroller.scrollBehaviorY.hasScroll",key:"hasVerticalScroll"},{sourceKey:"scroller.scrollBehaviorX.contentSize",key:"scrollerWidth"},{sourceKey:"scroller.scrollBehaviorY.contentSize",key:"scrollerHeight"},{sourceKey:"scroller.scrollBehaviorX.maxScrollPos",key:"maxScrollX"},{sourceKey:"scroller.scrollBehaviorY.maxScrollPos",key:"maxScrollY"},{sourceKey:"scroller.scrollBehaviorX.minScrollPos",key:"minScrollX"},{sourceKey:"scroller.scrollBehaviorY.minScrollPos",key:"minScrollY"},{sourceKey:"scroller.scrollBehaviorX.movingDirection",key:"movingDirectionX"},{sourceKey:"scroller.scrollBehaviorY.movingDirection",key:"movingDirectionY"},{sourceKey:"scroller.scrollBehaviorX.direction",key:"directionX"},{sourceKey:"scroller.scrollBehaviorY.direction",key:"directionY"},{sourceKey:"scroller.actions.enabled",key:"enabled"},{sourceKey:"scroller.animater.pending",key:"pending"},{sourceKey:"scroller.animater.stop",key:"stop"},{sourceKey:"scroller.scrollTo",key:"scrollTo"},{sourceKey:"scroller.scrollBy",key:"scrollBy"},{sourceKey:"scroller.scrollToElement",key:"scrollToElement"},{sourceKey:"scroller.resetPosition",key:"resetPosition"}];(function(t){t["Pre"]="pre",t["Post"]="post"})(bt||(bt={}));var wt=function(t){function e(e,o){var i=t.call(this,["refresh","enable","disable","beforeScrollStart","scrollStart","scroll","scrollEnd","scrollCancel","touchEnd","flick","destroy"])||this,n=y(e);if(!n)return s("Can not resolve the wrapper DOM."),i;var r=n.children[0];return r?(i.plugins={},i.options=(new G).merge(o).process(),i.hooks=new $(["init","refresh","enable","disable","destroy"]),i.init(n),i):(s("The wrapper need at least one child element to be scroller."),i)}return n(e,t),e.use=function(t){var e=t.pluginName,o=this.plugins.some((function(e){return t===e.ctor}));return o?this:f(e)?(s("Plugin Class must specify plugin's name in static property by 'pluginName' field."),this):this.pluginsMap[e]?(s("This plugin has been registered, maybe you need change plugin's name"),this):(this.pluginsMap[e]=!0,this.plugins.push({name:e,enforce:t.enforce,ctor:t}),this)},e.prototype.init=function(t){this.wrapper=t,t.isBScroll=!0,this.scroller=new Tt(t,this.options),this.eventBubbling(),this.handleAutoBlur(),this.innerRefresh(),this.scroller.scrollTo(this.options.startX,this.options.startY),this.enable(),this.proxy(Pt),this.applyPlugins()},e.prototype.applyPlugins=function(){var t=this,e=this.options;this.constructor.plugins.sort((function(t,e){var o,i=(o={},o[bt.Pre]=-1,o[bt.Post]=1,o),n=t.enforce?i[t.enforce]:0,r=e.enforce?i[e.enforce]:0;return n-r})).forEach((function(o){var i=o.ctor;e[o.name]&&"function"===typeof i&&(t.plugins[o.name]=new i(t))}))},e.prototype.handleAutoBlur=function(){this.options.autoBlur&&this.on(this.eventTypes.beforeScrollStart,(function(){var t=document.activeElement;!t||"INPUT"!==t.tagName&&"TEXTAREA"!==t.tagName||t.blur()}))},e.prototype.eventBubbling=function(){kt(this.scroller.hooks,this,["beforeScrollStart","scrollStart","scroll","scrollEnd","scrollCancel","touchEnd","flick"])},e.prototype.innerRefresh=function(){this.scroller.refresh(),this.hooks.trigger(this.hooks.eventTypes.refresh),this.trigger(this.eventTypes.refresh)},e.prototype.proxy=function(t){var e=this;t.forEach((function(t){var o=t.key,i=t.sourceKey;I(e,i,o)}))},e.prototype.refresh=function(){this.innerRefresh(),this.scroller.resetPosition()},e.prototype.enable=function(){this.scroller.enable(),this.hooks.trigger(this.hooks.eventTypes.enable),this.trigger(this.eventTypes.enable)},e.prototype.disable=function(){this.scroller.disable(),this.hooks.trigger(this.hooks.eventTypes.disable),this.trigger(this.eventTypes.disable)},e.prototype.destroy=function(){this.hooks.trigger(this.hooks.eventTypes.destroy),this.trigger(this.eventTypes.destroy),this.scroller.destroy()},e.prototype.eventRegister=function(t){this.registerType(t)},e.plugins=[],e.pluginsMap={},e}($);e["a"]=wt},c4c8:function(t,e,o){"use strict";o.d(e,"c",(function(){return c})),o.d(e,"b",(function(){return a})),o.d(e,"a",(function(){return l})),o.d(e,"d",(function(){return u})),o.d(e,"l",(function(){return p})),o.d(e,"i",(function(){return f})),o.d(e,"h",(function(){return d})),o.d(e,"j",(function(){return v})),o.d(e,"n",(function(){return m})),o.d(e,"k",(function(){return y})),o.d(e,"m",(function(){return g})),o.d(e,"p",(function(){return k})),o.d(e,"q",(function(){return b})),o.d(e,"r",(function(){return T})),o.d(e,"f",(function(){return P})),o.d(e,"e",(function(){return w})),o.d(e,"g",(function(){return S})),o.d(e,"o",(function(){return E}));var i=o("65c6"),n=o("0e0b");function r(t,e){var o=Object.keys(t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(t);e&&(i=i.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),o.push.apply(o,i)}return o}function s(t){for(var e=1;e<arguments.length;e++){var o=null!=arguments[e]?arguments[e]:{};e%2?r(Object(o),!0).forEach((function(e){h(t,e,o[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(o)):r(Object(o)).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(o,e))}))}return t}function h(t,e,o){return e in t?Object.defineProperty(t,e,{value:o,enumerable:!0,configurable:!0,writable:!0}):t[e]=o,t}var c=function(t,e,o,n,r){return Object(i["b"])("/hh/hh.review.product.list","post",{product:t,grade:e,show_reply:o,page:n,per_page:r})},a=function(t){return Object(i["b"])("/hh/hh.product.comment.list","post",s({},t))},l=function(t){return Object(i["b"])("/hh/hh.product.comment.first","post",{goods_id:t})},u=function(t){return Object(i["b"])("/hh/hh.review.product.subtotal","post",{product:t})},p=function(t){return new Promise((function(e,o){t.category&&(t.category=(""+t.category).split(",")),Object(i["b"])("/hh/hh.product.list","POST",{brand_id:t.brand_id,cat_id:t.category,sort_key:t.sort_key,sort_value:t.sort_value,keyword:t.keyword,page:t.page,per_page:t.per_page,shop:t.shop,admin_order:t.admin_order,tags_id:t.tags_id,is_newbie:t.is_newbie,appoint:t.appoint}).then((function(t){n["a"].splitMoneyLint(t.list),e(t)}),(function(t){o(t)}))}))},f=function(t,e){return new Promise((function(o,r){Object(i["b"])("/hh/hh.product.get","POST",{product:t,preview:e}).then((function(t){n["a"].splitMoneyLint(t),o(t)}),(function(t){r(t)}))}))},d=function(t,e){return new Promise((function(o,r){Object(i["b"])("/hh/hh.product.get","POST",{mlm_id:t,preview:e}).then((function(t){n["a"].splitMoneyLint(t),o(t)}),(function(t){r(t)}))}))},v=function(t){return Object(i["b"])("/hh/hh.product.like","POST",{product:t})},m=function(t){return Object(i["b"])("/hh/hh.product.unlike","POST",{product:t})},y=function(t,e){return Object(i["b"])("/hh/hh.product.liked.list","POST",{page:t,per_page:e})},g=function(t){return Object(i["b"])("/hh/hh.product.purchase","POST",{product:t.product,mlm_id:t.mlm_id,property:t.property,amount:t.amount,consignee:t.consignee,comment:t.comment,coupon_id:t.coupon_id,instalment_id:t.instalment_id,secbuy_id:t.secbuy_id,tiket:t.tiket,train_sn:t.train_sn,reserved:t.reserved})},k=function(t){var e=t.consignee,o=t.coupon_id,n=t.temp_order,r=t.reserved;return Object(i["b"])("/hh/hh.product.accept","POST",{consignee:e,coupon_id:o,temp_order:n,reserved:r})},b=function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:1;return Object(i["b"])("/hh/hh.save.notification","POST",{notification_tag:t})},T=function(t){return Object(i["b"])("/hh/hh.train.bill","POST",{products:t})},P=function(t){var e=t.consignee,o=t.products,n=t.train_sn,r=t.isCart,s=void 0!==r&&r,h=t.temp_order;return Object(i["b"])("/hh/hh.product.group","POST",{consignee:e,temp_order:h,products:o,train_sn:n,isCart:s})},w=function(t,e){return new Promise((function(e,o){Object(i["b"])("/activity/wx.gift.get","POST",{product:t}).then((function(t){n["a"].splitMoneyLint(t),e(t)}),(function(t){o(t)}))}))},S=function(t){t.consignee;var e=t.products;t.train_sn,t.isCart,t.temp_order;return Object(i["b"])("/activity/wx.gift.checkout","POST",s({},e[0]))},E=function(t){return Object(i["b"])("/activity/wx.gift.purchase","POST",{product:t.product,property:t.property,amount:t.amount,consignee:t.consignee,comment:t.comment,instalment_id:t.instalment_id})}}}]);