var Zepto=function(){function F(a){return null==a?String(a):x[y.call(a)]||"object"}function G(a){return"function"==F(a)}function H(a){return null!=a&&a==a.window}function I(a){return null!=a&&a.nodeType==a.DOCUMENT_NODE}function J(a){return"object"==F(a)}function K(a){return J(a)&&!H(a)&&Object.getPrototypeOf(a)==Object.prototype}function L(a){var b=!!a&&"length"in a&&a.length,d=c.type(a);return"function"!=d&&!H(a)&&("array"==d||0===b||"number"==typeof b&&b>0&&b-1 in a)}function M(a){return g.call(a,function(a){return null!=a})}function N(a){return a.length>0?c.fn.concat.apply([],a):a}function O(a){return a.replace(/::/g,"/").replace(/([A-Z]+)([A-Z][a-z])/g,"$1_$2").replace(/([a-z\d])([A-Z])/g,"$1_$2").replace(/_/g,"-").toLowerCase()}function P(a){return a in k?k[a]:k[a]=new RegExp("(^|\\s)"+a+"(\\s|$)")}function Q(a,b){return"number"!=typeof b||l[O(a)]?b:b+"px"}function R(a){var b,c;return j[a]||(b=i.createElement(a),i.body.appendChild(b),c=getComputedStyle(b,"").getPropertyValue("display"),b.parentNode.removeChild(b),"none"==c&&(c="block"),j[a]=c),j[a]}function S(a){return"children"in a?h.call(a.children):c.map(a.childNodes,function(a){return 1==a.nodeType?a:void 0})}function T(a,b){var c,d=a?a.length:0;for(c=0;d>c;c++)this[c]=a[c];this.length=d,this.selector=b||""}function U(c,d,e){for(b in d)e&&(K(d[b])||E(d[b]))?(K(d[b])&&!K(c[b])&&(c[b]={}),E(d[b])&&!E(c[b])&&(c[b]=[]),U(c[b],d[b],e)):d[b]!==a&&(c[b]=d[b])}function V(a,b){return null==b?c(a):c(a).filter(b)}function W(a,b,c,d){return G(b)?b.call(a,c,d):b}function X(a,b,c){null==c?a.removeAttribute(b):a.setAttribute(b,c)}function Y(b,c){var d=b.className||"",e=d&&d.baseVal!==a;return c===a?e?d.baseVal:d:(e?d.baseVal=c:b.className=c,void 0)}function Z(a){try{return a?"true"==a||("false"==a?!1:"null"==a?null:+a+""==a?+a:/^[\[\{]/.test(a)?c.parseJSON(a):a):a}catch(b){return a}}function $(a,b){b(a);for(var c=0,d=a.childNodes.length;d>c;c++)$(a.childNodes[c],b)}var a,b,c,d,A,B,e=[],f=e.concat,g=e.filter,h=e.slice,i=window.document,j={},k={},l={"column-count":1,columns:1,"font-weight":1,"line-height":1,opacity:1,"z-index":1,zoom:1},m=/^\s*<(\w+|!)[^>]*>/,n=/^<(\w+)\s*\/?>(?:<\/\1>|)$/,o=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/gi,p=/^(?:body|html)$/i,q=/([A-Z])/g,r=["val","css","html","text","data","width","height","offset"],s=["after","prepend","before","append"],t=i.createElement("table"),u=i.createElement("tr"),v={tr:i.createElement("tbody"),tbody:t,thead:t,tfoot:t,td:u,th:u,"*":i.createElement("div")},w=/^[\w-]*$/,x={},y=x.toString,z={},C=i.createElement("div"),D={tabindex:"tabIndex",readonly:"readOnly","for":"htmlFor","class":"className",maxlength:"maxLength",cellspacing:"cellSpacing",cellpadding:"cellPadding",rowspan:"rowSpan",colspan:"colSpan",usemap:"useMap",frameborder:"frameBorder",contenteditable:"contentEditable"},E=Array.isArray||function(a){return a instanceof Array};return z.matches=function(a,b){var c,d,e,f;return b&&a&&1===a.nodeType?(c=a.matches||a.webkitMatchesSelector||a.mozMatchesSelector||a.oMatchesSelector||a.matchesSelector)?c.call(a,b):(e=a.parentNode,f=!e,f&&(e=C).appendChild(a),d=~z.qsa(e,b).indexOf(a),f&&C.removeChild(a),d):!1},A=function(a){return a.replace(/-+(.)?/g,function(a,b){return b?b.toUpperCase():""})},B=function(a){return g.call(a,function(b,c){return a.indexOf(b)==c})},z.fragment=function(b,d,e){var f,g,j;return n.test(b)&&(f=c(i.createElement(RegExp.$1))),f||(b.replace&&(b=b.replace(o,"<$1></$2>")),d===a&&(d=m.test(b)&&RegExp.$1),d in v||(d="*"),j=v[d],j.innerHTML=""+b,f=c.each(h.call(j.childNodes),function(){j.removeChild(this)})),K(e)&&(g=c(f),c.each(e,function(a,b){r.indexOf(a)>-1?g[a](b):g.attr(a,b)})),f},z.Z=function(a,b){return new T(a,b)},z.isZ=function(a){return a instanceof z.Z},z.init=function(b,d){var e;if(!b)return z.Z();if("string"==typeof b)if(b=b.trim(),"<"==b[0]&&m.test(b))e=z.fragment(b,RegExp.$1,d),b=null;else{if(d!==a)return c(d).find(b);e=z.qsa(i,b)}else{if(G(b))return c(i).ready(b);if(z.isZ(b))return b;if(E(b))e=M(b);else if(J(b))e=[b],b=null;else if(m.test(b))e=z.fragment(b.trim(),RegExp.$1,d),b=null;else{if(d!==a)return c(d).find(b);e=z.qsa(i,b)}}return z.Z(e,b)},c=function(a,b){return z.init(a,b)},c.extend=function(a){var b,c=h.call(arguments,1);return"boolean"==typeof a&&(b=a,a=c.shift()),c.forEach(function(c){U(a,c,b)}),a},z.qsa=function(a,b){var c,d="#"==b[0],e=!d&&"."==b[0],f=d||e?b.slice(1):b,g=w.test(f);return a.getElementById&&g&&d?(c=a.getElementById(f))?[c]:[]:1!==a.nodeType&&9!==a.nodeType&&11!==a.nodeType?[]:h.call(g&&!d&&a.getElementsByClassName?e?a.getElementsByClassName(f):a.getElementsByTagName(b):a.querySelectorAll(b))},c.contains=i.documentElement.contains?function(a,b){return a!==b&&a.contains(b)}:function(a,b){for(;b&&(b=b.parentNode);)if(b===a)return!0;return!1},c.type=F,c.isFunction=G,c.isWindow=H,c.isArray=E,c.isPlainObject=K,c.isEmptyObject=function(a){var b;for(b in a)return!1;return!0},c.isNumeric=function(a){var b=Number(a),c=typeof a;return null!=a&&"boolean"!=c&&("string"!=c||a.length)&&!isNaN(b)&&isFinite(b)||!1},c.inArray=function(a,b,c){return e.indexOf.call(b,a,c)},c.camelCase=A,c.trim=function(a){return null==a?"":String.prototype.trim.call(a)},c.uuid=0,c.support={},c.expr={},c.noop=function(){},c.map=function(a,b){var c,e,f,d=[];if(L(a))for(e=0;e<a.length;e++)c=b(a[e],e),null!=c&&d.push(c);else for(f in a)c=b(a[f],f),null!=c&&d.push(c);return N(d)},c.each=function(a,b){var c,d;if(L(a)){for(c=0;c<a.length;c++)if(b.call(a[c],c,a[c])===!1)return a}else for(d in a)if(b.call(a[d],d,a[d])===!1)return a;return a},c.grep=function(a,b){return g.call(a,b)},window.JSON&&(c.parseJSON=JSON.parse),c.each("Boolean Number String Function Array Date RegExp Object Error".split(" "),function(a,b){x["[object "+b+"]"]=b.toLowerCase()}),c.fn={constructor:z.Z,length:0,forEach:e.forEach,reduce:e.reduce,push:e.push,sort:e.sort,splice:e.splice,indexOf:e.indexOf,concat:function(){var a,b,c=[];for(a=0;a<arguments.length;a++)b=arguments[a],c[a]=z.isZ(b)?b.toArray():b;return f.apply(z.isZ(this)?this.toArray():this,c)},map:function(a){return c(c.map(this,function(b,c){return a.call(b,c,b)}))},slice:function(){return c(h.apply(this,arguments))},ready:function(a){if("complete"===i.readyState||"loading"!==i.readyState&&!i.documentElement.doScroll)setTimeout(function(){a(c)},0);else{var b=function(){i.removeEventListener("DOMContentLoaded",b,!1),window.removeEventListener("load",b,!1),a(c)};i.addEventListener("DOMContentLoaded",b,!1),window.addEventListener("load",b,!1)}return this},get:function(b){return b===a?h.call(this):this[b>=0?b:b+this.length]},toArray:function(){return this.get()},size:function(){return this.length},remove:function(){return this.each(function(){null!=this.parentNode&&this.parentNode.removeChild(this)})},each:function(a){return e.every.call(this,function(b,c){return a.call(b,c,b)!==!1}),this},filter:function(a){return G(a)?this.not(this.not(a)):c(g.call(this,function(b){return z.matches(b,a)}))},add:function(a,b){return c(B(this.concat(c(a,b))))},is:function(a){return"string"==typeof a?this.length>0&&z.matches(this[0],a):a&&this.selector==a.selector},not:function(b){var e,d=[];return G(b)&&b.call!==a?this.each(function(a){b.call(this,a)||d.push(this)}):(e="string"==typeof b?this.filter(b):L(b)&&G(b.item)?h.call(b):c(b),this.forEach(function(a){e.indexOf(a)<0&&d.push(a)})),c(d)},has:function(a){return this.filter(function(){return J(a)?c.contains(this,a):c(this).find(a).size()})},eq:function(a){return-1===a?this.slice(a):this.slice(a,+a+1)},first:function(){var a=this[0];return a&&!J(a)?a:c(a)},last:function(){var a=this[this.length-1];return a&&!J(a)?a:c(a)},find:function(a){var b,d=this;return b=a?"object"==typeof a?c(a).filter(function(){var a=this;return e.some.call(d,function(b){return c.contains(b,a)})}):1==this.length?c(z.qsa(this[0],a)):this.map(function(){return z.qsa(this,a)}):c()},closest:function(a,b){var d=[],e="object"==typeof a&&c(a);return this.each(function(c,f){for(;f&&!(e?e.indexOf(f)>=0:z.matches(f,a));)f=f!==b&&!I(f)&&f.parentNode;f&&d.indexOf(f)<0&&d.push(f)}),c(d)},parents:function(a){for(var b=[],d=this;d.length>0;)d=c.map(d,function(a){return(a=a.parentNode)&&!I(a)&&b.indexOf(a)<0?(b.push(a),a):void 0});return V(b,a)},parent:function(a){return V(B(this.pluck("parentNode")),a)},children:function(a){return V(this.map(function(){return S(this)}),a)},contents:function(){return this.map(function(){return this.contentDocument||h.call(this.childNodes)})},siblings:function(a){return V(this.map(function(a,b){return g.call(S(b.parentNode),function(a){return a!==b})}),a)},empty:function(){return this.each(function(){this.innerHTML=""})},pluck:function(a){return c.map(this,function(b){return b[a]})},show:function(){return this.each(function(){"none"==this.style.display&&(this.style.display=""),"none"==getComputedStyle(this,"").getPropertyValue("display")&&(this.style.display=R(this.nodeName))})},replaceWith:function(a){return this.before(a).remove()},wrap:function(a){var d,e,b=G(a);return this[0]&&!b&&(d=c(a).get(0),e=d.parentNode||this.length>1),this.each(function(f){c(this).wrapAll(b?a.call(this,f):e?d.cloneNode(!0):d)})},wrapAll:function(a){if(this[0]){c(this[0]).before(a=c(a));for(var b;(b=a.children()).length;)a=b.first();c(a).append(this)}return this},wrapInner:function(a){var b=G(a);return this.each(function(d){var e=c(this),f=e.contents(),g=b?a.call(this,d):a;f.length?f.wrapAll(g):e.append(g)})},unwrap:function(){return this.parent().each(function(){c(this).replaceWith(c(this).children())}),this},clone:function(){return this.map(function(){return this.cloneNode(!0)})},hide:function(){return this.css("display","none")},toggle:function(b){return this.each(function(){var d=c(this);(b===a?"none"==d.css("display"):b)?d.show():d.hide()})},prev:function(a){return c(this.pluck("previousElementSibling")).filter(a||"*")},next:function(a){return c(this.pluck("nextElementSibling")).filter(a||"*")},html:function(a){return 0 in arguments?this.each(function(b){var d=this.innerHTML;c(this).empty().append(W(this,a,b,d))}):0 in this?this[0].innerHTML:null},text:function(a){return 0 in arguments?this.each(function(b){var c=W(this,a,b,this.textContent);this.textContent=null==c?"":""+c}):0 in this?this.pluck("textContent").join(""):null},attr:function(c,d){var e;return"string"!=typeof c||1 in arguments?this.each(function(a){if(1===this.nodeType)if(J(c))for(b in c)X(this,b,c[b]);else X(this,c,W(this,d,a,this.getAttribute(c)))}):0 in this&&1==this[0].nodeType&&null!=(e=this[0].getAttribute(c))?e:a},removeAttr:function(a){return this.each(function(){1===this.nodeType&&a.split(" ").forEach(function(a){X(this,a)},this)})},prop:function(a,c){return a=D[a]||a,"string"!=typeof a||1 in arguments?this.each(function(d){if(J(a))for(b in a)this[D[b]||b]=a[b];else this[a]=W(this,c,d,this[a])}):this[0]&&this[0][a]},removeProp:function(a){return a=D[a]||a,this.each(function(){delete this[a]})},data:function(b,c){var d="data-"+b.replace(q,"-$1").toLowerCase(),e=1 in arguments?this.attr(d,c):this.attr(d);return null!==e?Z(e):a},val:function(a){return 0 in arguments?(null==a&&(a=""),this.each(function(b){this.value=W(this,a,b,this.value)})):this[0]&&(this[0].multiple?c(this[0]).find("option").filter(function(){return this.selected}).pluck("value"):this[0].value)},offset:function(a){if(a)return this.each(function(b){var d=c(this),e=W(this,a,b,d.offset()),f=d.offsetParent().offset(),g={top:e.top-f.top,left:e.left-f.left};"static"==d.css("position")&&(g["position"]="relative"),d.css(g)});if(!this.length)return null;if(i.documentElement!==this[0]&&!c.contains(i.documentElement,this[0]))return{top:0,left:0};var b=this[0].getBoundingClientRect();return{left:b.left+window.pageXOffset,top:b.top+window.pageYOffset,width:Math.round(b.width),height:Math.round(b.height)}},css:function(a,d){var e,f,g,h;if(arguments.length<2){if(e=this[0],"string"==typeof a){if(!e)return;return e.style[A(a)]||getComputedStyle(e,"").getPropertyValue(a)}if(E(a)){if(!e)return;return f={},g=getComputedStyle(e,""),c.each(a,function(a,b){f[b]=e.style[A(b)]||g.getPropertyValue(b)}),f}}if(h="","string"==F(a))d||0===d?h=O(a)+":"+Q(a,d):this.each(function(){this.style.removeProperty(O(a))});else for(b in a)a[b]||0===a[b]?h+=O(b)+":"+Q(b,a[b])+";":this.each(function(){this.style.removeProperty(O(b))});return this.each(function(){this.style.cssText+=";"+h})},index:function(a){return a?this.indexOf(c(a)[0]):this.parent().children().indexOf(this[0])},hasClass:function(a){return a?e.some.call(this,function(a){return this.test(Y(a))},P(a)):!1},addClass:function(a){return a?this.each(function(b){if("className"in this){d=[];var e=Y(this),f=W(this,a,b,e);f.split(/\s+/g).forEach(function(a){c(this).hasClass(a)||d.push(a)},this),d.length&&Y(this,e+(e?" ":"")+d.join(" "))}}):this},removeClass:function(b){return this.each(function(c){if("className"in this){if(b===a)return Y(this,"");d=Y(this),W(this,b,c,d).split(/\s+/g).forEach(function(a){d=d.replace(P(a)," ")}),Y(this,d.trim())}})},toggleClass:function(b,d){return b?this.each(function(e){var f=c(this),g=W(this,b,e,Y(this));g.split(/\s+/g).forEach(function(b){(d===a?!f.hasClass(b):d)?f.addClass(b):f.removeClass(b)})}):this},scrollTop:function(b){if(this.length){var c="scrollTop"in this[0];return b===a?c?this[0].scrollTop:this[0].pageYOffset:this.each(c?function(){this.scrollTop=b}:function(){this.scrollTo(this.scrollX,b)})}},scrollLeft:function(b){if(this.length){var c="scrollLeft"in this[0];return b===a?c?this[0].scrollLeft:this[0].pageXOffset:this.each(c?function(){this.scrollLeft=b}:function(){this.scrollTo(b,this.scrollY)})}},position:function(){if(this.length){var a=this[0],b=this.offsetParent(),d=this.offset(),e=p.test(b[0].nodeName)?{top:0,left:0}:b.offset();return d.top-=parseFloat(c(a).css("margin-top"))||0,d.left-=parseFloat(c(a).css("margin-left"))||0,e.top+=parseFloat(c(b[0]).css("border-top-width"))||0,e.left+=parseFloat(c(b[0]).css("border-left-width"))||0,{top:d.top-e.top,left:d.left-e.left}}},offsetParent:function(){return this.map(function(){for(var a=this.offsetParent||i.body;a&&!p.test(a.nodeName)&&"static"==c(a).css("position");)a=a.offsetParent;return a})}},c.fn.detach=c.fn.remove,["width","height"].forEach(function(b){var d=b.replace(/./,function(a){return a[0].toUpperCase()});c.fn[b]=function(e){var f,g=this[0];return e===a?H(g)?g["inner"+d]:I(g)?g.documentElement["scroll"+d]:(f=this.offset())&&f[b]:this.each(function(a){g=c(this),g.css(b,W(this,e,a,g[b]()))})}}),s.forEach(function(b,d){var e=d%2;c.fn[b]=function(){var b,g,f=c.map(arguments,function(d){var e=[];return b=F(d),"array"==b?(d.forEach(function(b){return b.nodeType!==a?e.push(b):c.zepto.isZ(b)?e=e.concat(b.get()):(e=e.concat(z.fragment(b)),void 0)}),e):"object"==b||null==d?d:z.fragment(d)}),h=this.length>1;return f.length<1?this:this.each(function(a,b){g=e?b:b.parentNode,b=0==d?b.nextSibling:1==d?b.firstChild:2==d?b:null;var j=c.contains(i.documentElement,g);f.forEach(function(a){if(h)a=a.cloneNode(!0);else if(!g)return c(a).remove();g.insertBefore(a,b),j&&$(a,function(a){if(!(null==a.nodeName||"SCRIPT"!==a.nodeName.toUpperCase()||a.type&&"text/javascript"!==a.type||a.src)){var b=a.ownerDocument?a.ownerDocument.defaultView:window;b["eval"].call(b,a.innerHTML)}})})})},c.fn[e?b+"To":"insert"+(d?"Before":"After")]=function(a){return c(a)[b](this),this}}),z.Z.prototype=T.prototype=c.fn,z.uniq=B,z.deserializeValue=Z,c.zepto=z,c}();window.Zepto=Zepto,function(a){function l(a){return a._zid||(a._zid=b++)}function m(a,b,c,d){if(b=n(b),b.ns)var e=o(b.ns);return(g[l(a)]||[]).filter(function(a){return!(!a||b.e&&a.e!=b.e||b.ns&&!e.test(a.ns)||c&&l(a.fn)!==l(c)||d&&a.sel!=d)})}function n(a){var b=(""+a).split(".");return{e:b[0],ns:b.slice(1).sort().join(" ")}}function o(a){return new RegExp("(?:^| )"+a.replace(" "," .* ?")+"(?: |$)")}function p(a,b){return a.del&&!i&&a.e in j||!!b}function q(a){return k[a]||i&&j[a]||a}function r(b,d,e,f,h,i,j){var m=l(b),o=g[m]||(g[m]=[]);d.split(/\s/).forEach(function(d){var g,l;return"ready"==d?a(document).ready(e):(g=n(d),g.fn=e,g.sel=h,g.e in k&&(e=function(b){var c=b.relatedTarget;return!c||c!==this&&!a.contains(this,c)?g.fn.apply(this,arguments):void 0}),g.del=i,l=i||e,g.proxy=function(a){if(a=x(a),!a.isImmediatePropagationStopped()){a.data=f;var d=l.apply(b,a._args==c?[a]:[a].concat(a._args));return d===!1&&(a.preventDefault(),a.stopPropagation()),d}},g.i=o.length,o.push(g),"addEventListener"in b&&b.addEventListener(q(g.e),g.proxy,p(g,j)),void 0)})}function s(a,b,c,d,e){var f=l(a);(b||"").split(/\s/).forEach(function(b){m(a,b,c,d).forEach(function(b){delete g[f][b.i],"removeEventListener"in a&&a.removeEventListener(q(b.e),b.proxy,p(b,e))})})}function x(b,d){if(d||!b.isDefaultPrevented){d||(d=b),a.each(w,function(a,c){var e=d[a];b[a]=function(){return this[c]=t,e&&e.apply(d,arguments)},b[c]=u});try{b.timeStamp||(b.timeStamp=Date.now())}catch(e){}(d.defaultPrevented!==c?d.defaultPrevented:"returnValue"in d?d.returnValue===!1:d.getPreventDefault&&d.getPreventDefault())&&(b.isDefaultPrevented=t)}return b}function y(a){var b,d={originalEvent:a};for(b in a)v.test(b)||a[b]===c||(d[b]=a[b]);return x(d,a)}var c,t,u,v,w,b=1,d=Array.prototype.slice,e=a.isFunction,f=function(a){return"string"==typeof a},g={},h={},i="onfocusin"in window,j={focus:"focusin",blur:"focusout"},k={mouseenter:"mouseover",mouseleave:"mouseout"};h.click=h.mousedown=h.mouseup=h.mousemove="MouseEvents",a.event={add:r,remove:s},a.proxy=function(b,c){var h,g=2 in arguments&&d.call(arguments,2);if(e(b))return h=function(){return b.apply(c,g?g.concat(d.call(arguments)):arguments)},h._zid=l(b),h;if(f(c))return g?(g.unshift(b[c],b),a.proxy.apply(null,g)):a.proxy(b[c],b);throw new TypeError("expected function")},a.fn.bind=function(a,b,c){return this.on(a,b,c)},a.fn.unbind=function(a,b){return this.off(a,b)},a.fn.one=function(a,b,c,d){return this.on(a,b,c,d,1)},t=function(){return!0},u=function(){return!1},v=/^([A-Z]|returnValue$|layer[XY]$|webkitMovement[XY]$)/,w={preventDefault:"isDefaultPrevented",stopImmediatePropagation:"isImmediatePropagationStopped",stopPropagation:"isPropagationStopped"},a.fn.delegate=function(a,b,c){return this.on(b,a,c)},a.fn.undelegate=function(a,b,c){return this.off(b,a,c)},a.fn.live=function(b,c){return a(document.body).delegate(this.selector,b,c),this},a.fn.die=function(b,c){return a(document.body).undelegate(this.selector,b,c),this},a.fn.on=function(b,g,h,i,j){var k,l,m=this;return b&&!f(b)?(a.each(b,function(a,b){m.on(a,g,h,b,j)}),m):(f(g)||e(i)||i===!1||(i=h,h=g,g=c),(i===c||h===!1)&&(i=h,h=c),i===!1&&(i=u),m.each(function(c,e){j&&(k=function(a){return s(e,a.type,i),i.apply(this,arguments)}),g&&(l=function(b){var c,f=a(b.target).closest(g,e).get(0);return f&&f!==e?(c=a.extend(y(b),{currentTarget:f,liveFired:e}),(k||i).apply(f,[c].concat(d.call(arguments,1)))):void 0}),r(e,b,i,h,g,l||k)}))},a.fn.off=function(b,d,g){var h=this;return b&&!f(b)?(a.each(b,function(a,b){h.off(a,d,b)}),h):(f(d)||e(g)||g===!1||(g=d,d=c),g===!1&&(g=u),h.each(function(){s(this,b,g,d)}))},a.fn.trigger=function(b,c){return b=f(b)||a.isPlainObject(b)?a.Event(b):x(b),b._args=c,this.each(function(){b.type in j&&"function"==typeof this[b.type]?this[b.type]():"dispatchEvent"in this?this.dispatchEvent(b):a(this).triggerHandler(b,c)})},a.fn.triggerHandler=function(b,c){var d,e;return this.each(function(g,h){d=y(f(b)?a.Event(b):b),d._args=c,d.target=h,a.each(m(h,b.type||b),function(a,b){return e=b.proxy(d),d.isImmediatePropagationStopped()?!1:void 0})}),e},"focusin focusout focus blur load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select keydown keypress keyup error".split(" ").forEach(function(b){a.fn[b]=function(a){return 0 in arguments?this.bind(b,a):this.trigger(b)}}),a.Event=function(a,b){var c,d,e;if(f(a)||(b=a,a=b.type),c=document.createEvent(h[a]||"Events"),d=!0,b)for(e in b)"bubbles"==e?d=!!b[e]:c[e]=b[e];return c.initEvent(a,d,!0),x(c)}}(Zepto),function(a){function n(a,b,c,d){return Math.abs(a-b)>=Math.abs(c-d)?a-b>0?"Left":"Right":c-d>0?"Up":"Down"}function o(){f=null,b.last&&(b.el.trigger("longTap"),b={})}function p(){f&&clearTimeout(f),f=null}function q(){c&&clearTimeout(c),d&&clearTimeout(d),e&&clearTimeout(e),f&&clearTimeout(f),c=d=e=f=null,b={}}function r(a){return("touch"==a.pointerType||a.pointerType==a.MSPOINTER_TYPE_TOUCH)&&a.isPrimary}function s(a,b){return a.type=="pointer"+b||a.type.toLowerCase()=="mspointer"+b}function t(){m&&(a(document).off(l.down,i).off(l.up,j).off(l.move,k).off(l.cancel,q),a(window).off("scroll",q),q(),m=!1)}function u(u){var v,w,z,A,x=0,y=0;t(),l=u&&"down"in u?u:"ontouchstart"in document?{down:"touchstart",up:"touchend",move:"touchmove",cancel:"touchcancel"}:"onpointerdown"in document?{down:"pointerdown",up:"pointerup",move:"pointermove",cancel:"pointercancel"}:"onmspointerdown"in document?{down:"MSPointerDown",up:"MSPointerUp",move:"MSPointerMove",cancel:"MSPointerCancel"}:!1,l&&("MSGesture"in window&&(h=new MSGesture,h.target=document.body,a(document).bind("MSGestureEnd",function(a){var c=a.velocityX>1?"Right":a.velocityX<-1?"Left":a.velocityY>1?"Down":a.velocityY<-1?"Up":null;c&&(b.el.trigger("swipe"),b.el.trigger("swipe"+c))})),i=function(d){(!(A=s(d,"down"))||r(d))&&(z=A?d:d.touches[0],d.touches&&1===d.touches.length&&b.x2&&(b.x2=void 0,b.y2=void 0),v=Date.now(),w=v-(b.last||v),b.el=a("tagName"in z.target?z.target:z.target.parentNode),c&&clearTimeout(c),b.x1=z.pageX,b.y1=z.pageY,w>0&&250>=w&&(b.isDoubleTap=!0),b.last=v,f=setTimeout(o,g),h&&A&&h.addPointer(d.pointerId))},k=function(a){(!(A=s(a,"move"))||r(a))&&(z=A?a:a.touches[0],p(),b.x2=z.pageX,b.y2=z.pageY,x+=Math.abs(b.x1-b.x2),y+=Math.abs(b.y1-b.y2))},j=function(f){(!(A=s(f,"up"))||r(f))&&(p(),b.x2&&Math.abs(b.x1-b.x2)>30||b.y2&&Math.abs(b.y1-b.y2)>30?e=setTimeout(function(){b.el&&(b.el.trigger("swipe"),b.el.trigger("swipe"+n(b.x1,b.x2,b.y1,b.y2))),b={}},0):"last"in b&&(30>x&&30>y?d=setTimeout(function(){var d=a.Event("tap");d.cancelTouch=q,b.el&&b.el.trigger(d),b.isDoubleTap?(b.el&&b.el.trigger("doubleTap"),b={}):c=setTimeout(function(){c=null,b.el&&b.el.trigger("singleTap"),b={}},250)},0):b={}),x=y=0)},a(document).on(l.up,j).on(l.down,i).on(l.move,k),a(document).on(l.cancel,q),a(window).on("scroll",q),m=!0)}var c,d,e,f,h,i,j,k,l,b={},g=750,m=!1;["swipe","swipeLeft","swipeRight","swipeUp","swipeDown","doubleTap","tap","singleTap","longTap"].forEach(function(b){a.fn[b]=function(a){return this.on(b,a)}}),a.touch={setup:u},a(document).ready(u)}(Zepto);
/**
 * ???????????????????????????
 * @constructor
 * @param jsonObj json?????????????????????
 */
var NumkeyBoard=(function () {
    var html='<div class="num_keyBoardBox" id="num_keyBoardBox">'+
        '    <div class="underMask"></div>'+
        '    <div class="contBox">'+
        '    <div class="header">'+
        '        <a href="javascript:;" class="okBtn">??????</a>'+
        '    </div>'+
        '    <div class="panel">'+
        '        <div class="lineItme">'+
        '            <div class="cellItem" data-num="1">'+
        '                <span>1</span>'+
        '                <span></span>'+
        '            </div>'+
        '            <div class="cellItem" data-num="2">'+
        '                <span>2</span>'+
        '                <span>abc</span>'+
        '            </div>'+
        '            <div class="cellItem" data-num="3">'+
        '                <span>3</span>'+
        '                <span>def</span>'+
        '            </div>'+
        '        </div>'+
        '        <div class="lineItme">'+
        '            <div class="cellItem" data-num="4">'+
        '                <span>4</span>'+
        '                <span>ghi</span>'+
        '            </div>'+
        '            <div class="cellItem" data-num="5">'+
        '                <span>5</span>'+
        '                <span>jkl</span>'+
        '            </div>'+
        '            <div class="cellItem" data-num="6">'+
        '                <span>6</span>'+
        '                <span>mno</span>'+
        '            </div>'+
        '        </div>'+
        '        <div class="lineItme">'+
        '            <div class="cellItem" data-num="7">'+
        '                <span>7</span>'+
        '                <span>pqrs</span>'+
        '            </div>'+
        '            <div class="cellItem" data-num="8">'+
        '                <span>8</span>'+
        '                <span>tuv</span>'+
        '            </div>'+
        '            <div class="cellItem" data-num="9">'+
        '                <span>9</span>'+
        '                <span>wxyz</span>'+
        '            </div>'+
        '        </div>'+
        '        <div class="lineItme">'+
        '            <div class="cellItem operator" data-num=".">'+
        '                <span class="dit">.</span>'+
        '            </div>'+
        '            <div class="cellItem" data-num="0">'+
        '                <span class="zero">0</span>'+
        '            </div>'+
        '            <div class="cellItem operator del">'+
        '            </div>'+
        '        </div>'+
        '    </div>'+
        '    </div>'+
        '</div>';
    function NumkeyBoard(){
        this.valText="";
        this.pubsubObj=new Pubsub();//????????????????????????
        this.init();
    }
    NumkeyBoard.prototype={
        //???????????????
        "constructor":NumkeyBoard,
        //html??????????????????
        "htmlStr":html,
        //???????????????
        "init":function(){
            var keyBoardBox=null;
            if(Zepto('#num_keyBoardBox').size()!=0){
                this.keyBoard=Zepto('#num_keyBoardBox');
            }else{
                this.keyBoard=keyBoardBox=Zepto(this.htmlStr).appendTo('body');
            }
            this.bindEvent();
        },
        //??????dom??????
        "bindEvent":function(){
            var keyBoard=this.keyBoard;
            var _this=this;
            keyBoard.on('touchstart','.cellItem',function(event){
                var _this=Zepto(this);
                if(!Zepto(this).hasClass('operator')){
                    Zepto(this).removeClass('aniRun');
                    this.offsetWidth;
                    Zepto(this).addClass('aniRun');
                    setTimeout(function () {
                        _this.removeClass('aniRun');
                    },400);
                }
            });
            keyBoard.on('tap','.cellItem',function(event){
                var curNum=Zepto(this).data('num');
                if(typeof curNum != "undefined"){
                    _this.output(curNum);
                }else if(Zepto(this).hasClass('del')){
                    _this.delput();
                }
            });
            keyBoard.find('.del').on('longTap',function(){
                _this.setVal('');
            });
            keyBoard.find('.okBtn').on('tap',function(){
                _this.hide();
            });
            keyBoard.find('.underMask').on("tap",function () {
                _this.hide();
            });
        },
        //????????????
        "on":function(topic,fn){
            var pubsubObj=this.pubsubObj;
            pubsubObj.subscribe(topic,fn);
        },
        "trigger":function (topic,args) {
            var pubsubObj=this.pubsubObj;
            if(pubsubObj.has(topic)){
                return pubsubObj.publish(topic,[this].concat(args));
            }
        },
        //?????????????????????????????????
        "output":function(curNum){
            var valText=this.getVal();
            curNum=this.trigger('keyBoard:output',[curNum]);
            if(curNum !== ""){
                this.setVal(valText+curNum);
            }
        },
        //??????????????????
        "delput":function(){
            var valText=this.getVal();
            valText=valText.substr(0,valText.length-1);
            this.setVal(valText);
        },
        //??????????????????
        "show":function(){
            this.keyBoard.css('display',flexDisplay);
        },
        //??????????????????
        "hide":function(){
            if (isIOS){//IOS??????????????????????????????tap??????????????????
                $(this.keyBoard.get(0)).fadeOut(400);
            }else{
                this.keyBoard.hide();
            }
            this.trigger('keyBoard:hide',[this.getVal()]);
        },
        //?????????????????????
        "getVal":function(){
            return this.valText;
        },
        //??????????????????????????????????????????inputEvent??????
        "setVal":function(text){
            var valText=this.getVal();
            if(text != valText){
                this.valText=text;
                this.trigger('keyBoard:set',[text]);
                this.inputEvent();
            }
            return text;
        },
        "initValText":function (text,fn,args) {
            this.valText=text;
            if (typeof fn != "undefined"){
                if (typeof args == "undefined"){
                    args=[];
                }
                fn.apply(this,args);
            }
        },
        //?????????????????????
        "inputEvent":function(){
            var valText=this.getVal();
            this.trigger('keyBoard:input',[valText]);
        }
    }
    return NumkeyBoard;
})();