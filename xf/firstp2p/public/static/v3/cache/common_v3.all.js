(function(e,t){var n,r,i=typeof t,o=e.location,a=e.document,s=a.documentElement,l=e.jQuery,u=e.$,c={},p=[],f="1.10.2",d=p.concat,h=p.push,g=p.slice,m=p.indexOf,y=c.toString,v=c.hasOwnProperty,b=f.trim,x=function(e,t){return new x.fn.init(e,t,r)},w=/[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,T=/\S+/g,C=/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,N=/^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]*))$/,k=/^<(\w+)\s*\/?>(?:<\/\1>|)$/,E=/^[\],:{}\s]*$/,S=/(?:^|:|,)(?:\s*\[)+/g,A=/\\(?:["\\\/bfnrt]|u[\da-fA-F]{4})/g,j=/"[^"\\\r\n]*"|true|false|null|-?(?:\d+\.|)\d+(?:[eE][+-]?\d+|)/g,D=/^-ms-/,L=/-([\da-z])/gi,H=function(e,t){return t.toUpperCase()},q=function(e){(a.addEventListener||"load"===e.type||"complete"===a.readyState)&&(_(),x.ready())},_=function(){a.addEventListener?(a.removeEventListener("DOMContentLoaded",q,!1),e.removeEventListener("load",q,!1)):(a.detachEvent("onreadystatechange",q),e.detachEvent("onload",q))};x.fn=x.prototype={jquery:f,constructor:x,init:function(e,n,r){var i,o;if(!e)return this;if("string"==typeof e){if(i="<"===e.charAt(0)&&">"===e.charAt(e.length-1)&&e.length>=3?[null,e,null]:N.exec(e),!i||!i[1]&&n)return!n||n.jquery?(n||r).find(e):this.constructor(n).find(e);if(i[1]){if(n=n instanceof x?n[0]:n,x.merge(this,x.parseHTML(i[1],n&&n.nodeType?n.ownerDocument||n:a,!0)),k.test(i[1])&&x.isPlainObject(n))for(i in n)x.isFunction(this[i])?this[i](n[i]):this.attr(i,n[i]);return this}if(o=a.getElementById(i[2]),o&&o.parentNode){if(o.id!==i[2])return r.find(e);this.length=1,this[0]=o}return this.context=a,this.selector=e,this}return e.nodeType?(this.context=this[0]=e,this.length=1,this):x.isFunction(e)?r.ready(e):(e.selector!==t&&(this.selector=e.selector,this.context=e.context),x.makeArray(e,this))},selector:"",length:0,toArray:function(){return g.call(this)},get:function(e){return null==e?this.toArray():0>e?this[this.length+e]:this[e]},pushStack:function(e){var t=x.merge(this.constructor(),e);return t.prevObject=this,t.context=this.context,t},each:function(e,t){return x.each(this,e,t)},ready:function(e){return x.ready.promise().done(e),this},slice:function(){return this.pushStack(g.apply(this,arguments))},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},eq:function(e){var t=this.length,n=+e+(0>e?t:0);return this.pushStack(n>=0&&t>n?[this[n]]:[])},map:function(e){return this.pushStack(x.map(this,function(t,n){return e.call(t,n,t)}))},end:function(){return this.prevObject||this.constructor(null)},push:h,sort:[].sort,splice:[].splice},x.fn.init.prototype=x.fn,x.extend=x.fn.extend=function(){var e,n,r,i,o,a,s=arguments[0]||{},l=1,u=arguments.length,c=!1;for("boolean"==typeof s&&(c=s,s=arguments[1]||{},l=2),"object"==typeof s||x.isFunction(s)||(s={}),u===l&&(s=this,--l);u>l;l++)if(null!=(o=arguments[l]))for(i in o)e=s[i],r=o[i],s!==r&&(c&&r&&(x.isPlainObject(r)||(n=x.isArray(r)))?(n?(n=!1,a=e&&x.isArray(e)?e:[]):a=e&&x.isPlainObject(e)?e:{},s[i]=x.extend(c,a,r)):r!==t&&(s[i]=r));return s},x.extend({expando:"jQuery"+(f+Math.random()).replace(/\D/g,""),noConflict:function(t){return e.$===x&&(e.$=u),t&&e.jQuery===x&&(e.jQuery=l),x},isReady:!1,readyWait:1,holdReady:function(e){e?x.readyWait++:x.ready(!0)},ready:function(e){if(e===!0?!--x.readyWait:!x.isReady){if(!a.body)return setTimeout(x.ready);x.isReady=!0,e!==!0&&--x.readyWait>0||(n.resolveWith(a,[x]),x.fn.trigger&&x(a).trigger("ready").off("ready"))}},isFunction:function(e){return"function"===x.type(e)},isArray:Array.isArray||function(e){return"array"===x.type(e)},isWindow:function(e){return null!=e&&e==e.window},isNumeric:function(e){return!isNaN(parseFloat(e))&&isFinite(e)},type:function(e){return null==e?e+"":"object"==typeof e||"function"==typeof e?c[y.call(e)]||"object":typeof e},isPlainObject:function(e){var n;if(!e||"object"!==x.type(e)||e.nodeType||x.isWindow(e))return!1;try{if(e.constructor&&!v.call(e,"constructor")&&!v.call(e.constructor.prototype,"isPrototypeOf"))return!1}catch(r){return!1}if(x.support.ownLast)for(n in e)return v.call(e,n);for(n in e);return n===t||v.call(e,n)},isEmptyObject:function(e){var t;for(t in e)return!1;return!0},error:function(e){throw Error(e)},parseHTML:function(e,t,n){if(!e||"string"!=typeof e)return null;"boolean"==typeof t&&(n=t,t=!1),t=t||a;var r=k.exec(e),i=!n&&[];return r?[t.createElement(r[1])]:(r=x.buildFragment([e],t,i),i&&x(i).remove(),x.merge([],r.childNodes))},parseJSON:function(n){return e.JSON&&e.JSON.parse?e.JSON.parse(n):null===n?n:"string"==typeof n&&(n=x.trim(n),n&&E.test(n.replace(A,"@").replace(j,"]").replace(S,"")))?Function("return "+n)():(x.error("Invalid JSON: "+n),t)},parseXML:function(n){var r,i;if(!n||"string"!=typeof n)return null;try{e.DOMParser?(i=new DOMParser,r=i.parseFromString(n,"text/xml")):(r=new ActiveXObject("Microsoft.XMLDOM"),r.async="false",r.loadXML(n))}catch(o){r=t}return r&&r.documentElement&&!r.getElementsByTagName("parsererror").length||x.error("Invalid XML: "+n),r},noop:function(){},globalEval:function(t){t&&x.trim(t)&&(e.execScript||function(t){e.eval.call(e,t)})(t)},camelCase:function(e){return e.replace(D,"ms-").replace(L,H)},nodeName:function(e,t){return e.nodeName&&e.nodeName.toLowerCase()===t.toLowerCase()},each:function(e,t,n){var r,i=0,o=e.length,a=M(e);if(n){if(a){for(;o>i;i++)if(r=t.apply(e[i],n),r===!1)break}else for(i in e)if(r=t.apply(e[i],n),r===!1)break}else if(a){for(;o>i;i++)if(r=t.call(e[i],i,e[i]),r===!1)break}else for(i in e)if(r=t.call(e[i],i,e[i]),r===!1)break;return e},trim:b&&!b.call("\ufeff\u00a0")?function(e){return null==e?"":b.call(e)}:function(e){return null==e?"":(e+"").replace(C,"")},makeArray:function(e,t){var n=t||[];return null!=e&&(M(Object(e))?x.merge(n,"string"==typeof e?[e]:e):h.call(n,e)),n},inArray:function(e,t,n){var r;if(t){if(m)return m.call(t,e,n);for(r=t.length,n=n?0>n?Math.max(0,r+n):n:0;r>n;n++)if(n in t&&t[n]===e)return n}return-1},merge:function(e,n){var r=n.length,i=e.length,o=0;if("number"==typeof r)for(;r>o;o++)e[i++]=n[o];else while(n[o]!==t)e[i++]=n[o++];return e.length=i,e},grep:function(e,t,n){var r,i=[],o=0,a=e.length;for(n=!!n;a>o;o++)r=!!t(e[o],o),n!==r&&i.push(e[o]);return i},map:function(e,t,n){var r,i=0,o=e.length,a=M(e),s=[];if(a)for(;o>i;i++)r=t(e[i],i,n),null!=r&&(s[s.length]=r);else for(i in e)r=t(e[i],i,n),null!=r&&(s[s.length]=r);return d.apply([],s)},guid:1,proxy:function(e,n){var r,i,o;return"string"==typeof n&&(o=e[n],n=e,e=o),x.isFunction(e)?(r=g.call(arguments,2),i=function(){return e.apply(n||this,r.concat(g.call(arguments)))},i.guid=e.guid=e.guid||x.guid++,i):t},access:function(e,n,r,i,o,a,s){var l=0,u=e.length,c=null==r;if("object"===x.type(r)){o=!0;for(l in r)x.access(e,n,l,r[l],!0,a,s)}else if(i!==t&&(o=!0,x.isFunction(i)||(s=!0),c&&(s?(n.call(e,i),n=null):(c=n,n=function(e,t,n){return c.call(x(e),n)})),n))for(;u>l;l++)n(e[l],r,s?i:i.call(e[l],l,n(e[l],r)));return o?e:c?n.call(e):u?n(e[0],r):a},now:function(){return(new Date).getTime()},swap:function(e,t,n,r){var i,o,a={};for(o in t)a[o]=e.style[o],e.style[o]=t[o];i=n.apply(e,r||[]);for(o in t)e.style[o]=a[o];return i}}),x.ready.promise=function(t){if(!n)if(n=x.Deferred(),"complete"===a.readyState)setTimeout(x.ready);else if(a.addEventListener)a.addEventListener("DOMContentLoaded",q,!1),e.addEventListener("load",q,!1);else{a.attachEvent("onreadystatechange",q),e.attachEvent("onload",q);var r=!1;try{r=null==e.frameElement&&a.documentElement}catch(i){}r&&r.doScroll&&function o(){if(!x.isReady){try{r.doScroll("left")}catch(e){return setTimeout(o,50)}_(),x.ready()}}()}return n.promise(t)},x.each("Boolean Number String Function Array Date RegExp Object Error".split(" "),function(e,t){c["[object "+t+"]"]=t.toLowerCase()});function M(e){var t=e.length,n=x.type(e);return x.isWindow(e)?!1:1===e.nodeType&&t?!0:"array"===n||"function"!==n&&(0===t||"number"==typeof t&&t>0&&t-1 in e)}r=x(a),function(e,t){var n,r,i,o,a,s,l,u,c,p,f,d,h,g,m,y,v,b="sizzle"+-new Date,w=e.document,T=0,C=0,N=st(),k=st(),E=st(),S=!1,A=function(e,t){return e===t?(S=!0,0):0},j=typeof t,D=1<<31,L={}.hasOwnProperty,H=[],q=H.pop,_=H.push,M=H.push,O=H.slice,F=H.indexOf||function(e){var t=0,n=this.length;for(;n>t;t++)if(this[t]===e)return t;return-1},B="checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",P="[\\x20\\t\\r\\n\\f]",R="(?:\\\\.|[\\w-]|[^\\x00-\\xa0])+",W=R.replace("w","w#"),$="\\["+P+"*("+R+")"+P+"*(?:([*^$|!~]?=)"+P+"*(?:(['\"])((?:\\\\.|[^\\\\])*?)\\3|("+W+")|)|)"+P+"*\\]",I=":("+R+")(?:\\(((['\"])((?:\\\\.|[^\\\\])*?)\\3|((?:\\\\.|[^\\\\()[\\]]|"+$.replace(3,8)+")*)|.*)\\)|)",z=RegExp("^"+P+"+|((?:^|[^\\\\])(?:\\\\.)*)"+P+"+$","g"),X=RegExp("^"+P+"*,"+P+"*"),U=RegExp("^"+P+"*([>+~]|"+P+")"+P+"*"),V=RegExp(P+"*[+~]"),Y=RegExp("="+P+"*([^\\]'\"]*)"+P+"*\\]","g"),J=RegExp(I),G=RegExp("^"+W+"$"),Q={ID:RegExp("^#("+R+")"),CLASS:RegExp("^\\.("+R+")"),TAG:RegExp("^("+R.replace("w","w*")+")"),ATTR:RegExp("^"+$),PSEUDO:RegExp("^"+I),CHILD:RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\("+P+"*(even|odd|(([+-]|)(\\d*)n|)"+P+"*(?:([+-]|)"+P+"*(\\d+)|))"+P+"*\\)|)","i"),bool:RegExp("^(?:"+B+")$","i"),needsContext:RegExp("^"+P+"*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\("+P+"*((?:-\\d)?\\d*)"+P+"*\\)|)(?=[^-]|$)","i")},K=/^[^{]+\{\s*\[native \w/,Z=/^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,et=/^(?:input|select|textarea|button)$/i,tt=/^h\d$/i,nt=/'|\\/g,rt=RegExp("\\\\([\\da-f]{1,6}"+P+"?|("+P+")|.)","ig"),it=function(e,t,n){var r="0x"+t-65536;return r!==r||n?t:0>r?String.fromCharCode(r+65536):String.fromCharCode(55296|r>>10,56320|1023&r)};try{M.apply(H=O.call(w.childNodes),w.childNodes),H[w.childNodes.length].nodeType}catch(ot){M={apply:H.length?function(e,t){_.apply(e,O.call(t))}:function(e,t){var n=e.length,r=0;while(e[n++]=t[r++]);e.length=n-1}}}function at(e,t,n,i){var o,a,s,l,u,c,d,m,y,x;if((t?t.ownerDocument||t:w)!==f&&p(t),t=t||f,n=n||[],!e||"string"!=typeof e)return n;if(1!==(l=t.nodeType)&&9!==l)return[];if(h&&!i){if(o=Z.exec(e))if(s=o[1]){if(9===l){if(a=t.getElementById(s),!a||!a.parentNode)return n;if(a.id===s)return n.push(a),n}else if(t.ownerDocument&&(a=t.ownerDocument.getElementById(s))&&v(t,a)&&a.id===s)return n.push(a),n}else{if(o[2])return M.apply(n,t.getElementsByTagName(e)),n;if((s=o[3])&&r.getElementsByClassName&&t.getElementsByClassName)return M.apply(n,t.getElementsByClassName(s)),n}if(r.qsa&&(!g||!g.test(e))){if(m=d=b,y=t,x=9===l&&e,1===l&&"object"!==t.nodeName.toLowerCase()){c=mt(e),(d=t.getAttribute("id"))?m=d.replace(nt,"\\$&"):t.setAttribute("id",m),m="[id='"+m+"'] ",u=c.length;while(u--)c[u]=m+yt(c[u]);y=V.test(e)&&t.parentNode||t,x=c.join(",")}if(x)try{return M.apply(n,y.querySelectorAll(x)),n}catch(T){}finally{d||t.removeAttribute("id")}}}return kt(e.replace(z,"$1"),t,n,i)}function st(){var e=[];function t(n,r){return e.push(n+=" ")>o.cacheLength&&delete t[e.shift()],t[n]=r}return t}function lt(e){return e[b]=!0,e}function ut(e){var t=f.createElement("div");try{return!!e(t)}catch(n){return!1}finally{t.parentNode&&t.parentNode.removeChild(t),t=null}}function ct(e,t){var n=e.split("|"),r=e.length;while(r--)o.attrHandle[n[r]]=t}function pt(e,t){var n=t&&e,r=n&&1===e.nodeType&&1===t.nodeType&&(~t.sourceIndex||D)-(~e.sourceIndex||D);if(r)return r;if(n)while(n=n.nextSibling)if(n===t)return-1;return e?1:-1}function ft(e){return function(t){var n=t.nodeName.toLowerCase();return"input"===n&&t.type===e}}function dt(e){return function(t){var n=t.nodeName.toLowerCase();return("input"===n||"button"===n)&&t.type===e}}function ht(e){return lt(function(t){return t=+t,lt(function(n,r){var i,o=e([],n.length,t),a=o.length;while(a--)n[i=o[a]]&&(n[i]=!(r[i]=n[i]))})})}s=at.isXML=function(e){var t=e&&(e.ownerDocument||e).documentElement;return t?"HTML"!==t.nodeName:!1},r=at.support={},p=at.setDocument=function(e){var n=e?e.ownerDocument||e:w,i=n.defaultView;return n!==f&&9===n.nodeType&&n.documentElement?(f=n,d=n.documentElement,h=!s(n),i&&i.attachEvent&&i!==i.top&&i.attachEvent("onbeforeunload",function(){p()}),r.attributes=ut(function(e){return e.className="i",!e.getAttribute("className")}),r.getElementsByTagName=ut(function(e){return e.appendChild(n.createComment("")),!e.getElementsByTagName("*").length}),r.getElementsByClassName=ut(function(e){return e.innerHTML="<div class='a'></div><div class='a i'></div>",e.firstChild.className="i",2===e.getElementsByClassName("i").length}),r.getById=ut(function(e){return d.appendChild(e).id=b,!n.getElementsByName||!n.getElementsByName(b).length}),r.getById?(o.find.ID=function(e,t){if(typeof t.getElementById!==j&&h){var n=t.getElementById(e);return n&&n.parentNode?[n]:[]}},o.filter.ID=function(e){var t=e.replace(rt,it);return function(e){return e.getAttribute("id")===t}}):(delete o.find.ID,o.filter.ID=function(e){var t=e.replace(rt,it);return function(e){var n=typeof e.getAttributeNode!==j&&e.getAttributeNode("id");return n&&n.value===t}}),o.find.TAG=r.getElementsByTagName?function(e,n){return typeof n.getElementsByTagName!==j?n.getElementsByTagName(e):t}:function(e,t){var n,r=[],i=0,o=t.getElementsByTagName(e);if("*"===e){while(n=o[i++])1===n.nodeType&&r.push(n);return r}return o},o.find.CLASS=r.getElementsByClassName&&function(e,n){return typeof n.getElementsByClassName!==j&&h?n.getElementsByClassName(e):t},m=[],g=[],(r.qsa=K.test(n.querySelectorAll))&&(ut(function(e){e.innerHTML="<select><option selected=''></option></select>",e.querySelectorAll("[selected]").length||g.push("\\["+P+"*(?:value|"+B+")"),e.querySelectorAll(":checked").length||g.push(":checked")}),ut(function(e){var t=n.createElement("input");t.setAttribute("type","hidden"),e.appendChild(t).setAttribute("t",""),e.querySelectorAll("[t^='']").length&&g.push("[*^$]="+P+"*(?:''|\"\")"),e.querySelectorAll(":enabled").length||g.push(":enabled",":disabled"),e.querySelectorAll("*,:x"),g.push(",.*:")})),(r.matchesSelector=K.test(y=d.webkitMatchesSelector||d.mozMatchesSelector||d.oMatchesSelector||d.msMatchesSelector))&&ut(function(e){r.disconnectedMatch=y.call(e,"div"),y.call(e,"[s!='']:x"),m.push("!=",I)}),g=g.length&&RegExp(g.join("|")),m=m.length&&RegExp(m.join("|")),v=K.test(d.contains)||d.compareDocumentPosition?function(e,t){var n=9===e.nodeType?e.documentElement:e,r=t&&t.parentNode;return e===r||!(!r||1!==r.nodeType||!(n.contains?n.contains(r):e.compareDocumentPosition&&16&e.compareDocumentPosition(r)))}:function(e,t){if(t)while(t=t.parentNode)if(t===e)return!0;return!1},A=d.compareDocumentPosition?function(e,t){if(e===t)return S=!0,0;var i=t.compareDocumentPosition&&e.compareDocumentPosition&&e.compareDocumentPosition(t);return i?1&i||!r.sortDetached&&t.compareDocumentPosition(e)===i?e===n||v(w,e)?-1:t===n||v(w,t)?1:c?F.call(c,e)-F.call(c,t):0:4&i?-1:1:e.compareDocumentPosition?-1:1}:function(e,t){var r,i=0,o=e.parentNode,a=t.parentNode,s=[e],l=[t];if(e===t)return S=!0,0;if(!o||!a)return e===n?-1:t===n?1:o?-1:a?1:c?F.call(c,e)-F.call(c,t):0;if(o===a)return pt(e,t);r=e;while(r=r.parentNode)s.unshift(r);r=t;while(r=r.parentNode)l.unshift(r);while(s[i]===l[i])i++;return i?pt(s[i],l[i]):s[i]===w?-1:l[i]===w?1:0},n):f},at.matches=function(e,t){return at(e,null,null,t)},at.matchesSelector=function(e,t){if((e.ownerDocument||e)!==f&&p(e),t=t.replace(Y,"='$1']"),!(!r.matchesSelector||!h||m&&m.test(t)||g&&g.test(t)))try{var n=y.call(e,t);if(n||r.disconnectedMatch||e.document&&11!==e.document.nodeType)return n}catch(i){}return at(t,f,null,[e]).length>0},at.contains=function(e,t){return(e.ownerDocument||e)!==f&&p(e),v(e,t)},at.attr=function(e,n){(e.ownerDocument||e)!==f&&p(e);var i=o.attrHandle[n.toLowerCase()],a=i&&L.call(o.attrHandle,n.toLowerCase())?i(e,n,!h):t;return a===t?r.attributes||!h?e.getAttribute(n):(a=e.getAttributeNode(n))&&a.specified?a.value:null:a},at.error=function(e){throw Error("Syntax error, unrecognized expression: "+e)},at.uniqueSort=function(e){var t,n=[],i=0,o=0;if(S=!r.detectDuplicates,c=!r.sortStable&&e.slice(0),e.sort(A),S){while(t=e[o++])t===e[o]&&(i=n.push(o));while(i--)e.splice(n[i],1)}return e},a=at.getText=function(e){var t,n="",r=0,i=e.nodeType;if(i){if(1===i||9===i||11===i){if("string"==typeof e.textContent)return e.textContent;for(e=e.firstChild;e;e=e.nextSibling)n+=a(e)}else if(3===i||4===i)return e.nodeValue}else for(;t=e[r];r++)n+=a(t);return n},o=at.selectors={cacheLength:50,createPseudo:lt,match:Q,attrHandle:{},find:{},relative:{">":{dir:"parentNode",first:!0}," ":{dir:"parentNode"},"+":{dir:"previousSibling",first:!0},"~":{dir:"previousSibling"}},preFilter:{ATTR:function(e){return e[1]=e[1].replace(rt,it),e[3]=(e[4]||e[5]||"").replace(rt,it),"~="===e[2]&&(e[3]=" "+e[3]+" "),e.slice(0,4)},CHILD:function(e){return e[1]=e[1].toLowerCase(),"nth"===e[1].slice(0,3)?(e[3]||at.error(e[0]),e[4]=+(e[4]?e[5]+(e[6]||1):2*("even"===e[3]||"odd"===e[3])),e[5]=+(e[7]+e[8]||"odd"===e[3])):e[3]&&at.error(e[0]),e},PSEUDO:function(e){var n,r=!e[5]&&e[2];return Q.CHILD.test(e[0])?null:(e[3]&&e[4]!==t?e[2]=e[4]:r&&J.test(r)&&(n=mt(r,!0))&&(n=r.indexOf(")",r.length-n)-r.length)&&(e[0]=e[0].slice(0,n),e[2]=r.slice(0,n)),e.slice(0,3))}},filter:{TAG:function(e){var t=e.replace(rt,it).toLowerCase();return"*"===e?function(){return!0}:function(e){return e.nodeName&&e.nodeName.toLowerCase()===t}},CLASS:function(e){var t=N[e+" "];return t||(t=RegExp("(^|"+P+")"+e+"("+P+"|$)"))&&N(e,function(e){return t.test("string"==typeof e.className&&e.className||typeof e.getAttribute!==j&&e.getAttribute("class")||"")})},ATTR:function(e,t,n){return function(r){var i=at.attr(r,e);return null==i?"!="===t:t?(i+="","="===t?i===n:"!="===t?i!==n:"^="===t?n&&0===i.indexOf(n):"*="===t?n&&i.indexOf(n)>-1:"$="===t?n&&i.slice(-n.length)===n:"~="===t?(" "+i+" ").indexOf(n)>-1:"|="===t?i===n||i.slice(0,n.length+1)===n+"-":!1):!0}},CHILD:function(e,t,n,r,i){var o="nth"!==e.slice(0,3),a="last"!==e.slice(-4),s="of-type"===t;return 1===r&&0===i?function(e){return!!e.parentNode}:function(t,n,l){var u,c,p,f,d,h,g=o!==a?"nextSibling":"previousSibling",m=t.parentNode,y=s&&t.nodeName.toLowerCase(),v=!l&&!s;if(m){if(o){while(g){p=t;while(p=p[g])if(s?p.nodeName.toLowerCase()===y:1===p.nodeType)return!1;h=g="only"===e&&!h&&"nextSibling"}return!0}if(h=[a?m.firstChild:m.lastChild],a&&v){c=m[b]||(m[b]={}),u=c[e]||[],d=u[0]===T&&u[1],f=u[0]===T&&u[2],p=d&&m.childNodes[d];while(p=++d&&p&&p[g]||(f=d=0)||h.pop())if(1===p.nodeType&&++f&&p===t){c[e]=[T,d,f];break}}else if(v&&(u=(t[b]||(t[b]={}))[e])&&u[0]===T)f=u[1];else while(p=++d&&p&&p[g]||(f=d=0)||h.pop())if((s?p.nodeName.toLowerCase()===y:1===p.nodeType)&&++f&&(v&&((p[b]||(p[b]={}))[e]=[T,f]),p===t))break;return f-=i,f===r||0===f%r&&f/r>=0}}},PSEUDO:function(e,t){var n,r=o.pseudos[e]||o.setFilters[e.toLowerCase()]||at.error("unsupported pseudo: "+e);return r[b]?r(t):r.length>1?(n=[e,e,"",t],o.setFilters.hasOwnProperty(e.toLowerCase())?lt(function(e,n){var i,o=r(e,t),a=o.length;while(a--)i=F.call(e,o[a]),e[i]=!(n[i]=o[a])}):function(e){return r(e,0,n)}):r}},pseudos:{not:lt(function(e){var t=[],n=[],r=l(e.replace(z,"$1"));return r[b]?lt(function(e,t,n,i){var o,a=r(e,null,i,[]),s=e.length;while(s--)(o=a[s])&&(e[s]=!(t[s]=o))}):function(e,i,o){return t[0]=e,r(t,null,o,n),!n.pop()}}),has:lt(function(e){return function(t){return at(e,t).length>0}}),contains:lt(function(e){return function(t){return(t.textContent||t.innerText||a(t)).indexOf(e)>-1}}),lang:lt(function(e){return G.test(e||"")||at.error("unsupported lang: "+e),e=e.replace(rt,it).toLowerCase(),function(t){var n;do if(n=h?t.lang:t.getAttribute("xml:lang")||t.getAttribute("lang"))return n=n.toLowerCase(),n===e||0===n.indexOf(e+"-");while((t=t.parentNode)&&1===t.nodeType);return!1}}),target:function(t){var n=e.location&&e.location.hash;return n&&n.slice(1)===t.id},root:function(e){return e===d},focus:function(e){return e===f.activeElement&&(!f.hasFocus||f.hasFocus())&&!!(e.type||e.href||~e.tabIndex)},enabled:function(e){return e.disabled===!1},disabled:function(e){return e.disabled===!0},checked:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&!!e.checked||"option"===t&&!!e.selected},selected:function(e){return e.parentNode&&e.parentNode.selectedIndex,e.selected===!0},empty:function(e){for(e=e.firstChild;e;e=e.nextSibling)if(e.nodeName>"@"||3===e.nodeType||4===e.nodeType)return!1;return!0},parent:function(e){return!o.pseudos.empty(e)},header:function(e){return tt.test(e.nodeName)},input:function(e){return et.test(e.nodeName)},button:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&"button"===e.type||"button"===t},text:function(e){var t;return"input"===e.nodeName.toLowerCase()&&"text"===e.type&&(null==(t=e.getAttribute("type"))||t.toLowerCase()===e.type)},first:ht(function(){return[0]}),last:ht(function(e,t){return[t-1]}),eq:ht(function(e,t,n){return[0>n?n+t:n]}),even:ht(function(e,t){var n=0;for(;t>n;n+=2)e.push(n);return e}),odd:ht(function(e,t){var n=1;for(;t>n;n+=2)e.push(n);return e}),lt:ht(function(e,t,n){var r=0>n?n+t:n;for(;--r>=0;)e.push(r);return e}),gt:ht(function(e,t,n){var r=0>n?n+t:n;for(;t>++r;)e.push(r);return e})}},o.pseudos.nth=o.pseudos.eq;for(n in{radio:!0,checkbox:!0,file:!0,password:!0,image:!0})o.pseudos[n]=ft(n);for(n in{submit:!0,reset:!0})o.pseudos[n]=dt(n);function gt(){}gt.prototype=o.filters=o.pseudos,o.setFilters=new gt;function mt(e,t){var n,r,i,a,s,l,u,c=k[e+" "];if(c)return t?0:c.slice(0);s=e,l=[],u=o.preFilter;while(s){(!n||(r=X.exec(s)))&&(r&&(s=s.slice(r[0].length)||s),l.push(i=[])),n=!1,(r=U.exec(s))&&(n=r.shift(),i.push({value:n,type:r[0].replace(z," ")}),s=s.slice(n.length));for(a in o.filter)!(r=Q[a].exec(s))||u[a]&&!(r=u[a](r))||(n=r.shift(),i.push({value:n,type:a,matches:r}),s=s.slice(n.length));if(!n)break}return t?s.length:s?at.error(e):k(e,l).slice(0)}function yt(e){var t=0,n=e.length,r="";for(;n>t;t++)r+=e[t].value;return r}function vt(e,t,n){var r=t.dir,o=n&&"parentNode"===r,a=C++;return t.first?function(t,n,i){while(t=t[r])if(1===t.nodeType||o)return e(t,n,i)}:function(t,n,s){var l,u,c,p=T+" "+a;if(s){while(t=t[r])if((1===t.nodeType||o)&&e(t,n,s))return!0}else while(t=t[r])if(1===t.nodeType||o)if(c=t[b]||(t[b]={}),(u=c[r])&&u[0]===p){if((l=u[1])===!0||l===i)return l===!0}else if(u=c[r]=[p],u[1]=e(t,n,s)||i,u[1]===!0)return!0}}function bt(e){return e.length>1?function(t,n,r){var i=e.length;while(i--)if(!e[i](t,n,r))return!1;return!0}:e[0]}function xt(e,t,n,r,i){var o,a=[],s=0,l=e.length,u=null!=t;for(;l>s;s++)(o=e[s])&&(!n||n(o,r,i))&&(a.push(o),u&&t.push(s));return a}function wt(e,t,n,r,i,o){return r&&!r[b]&&(r=wt(r)),i&&!i[b]&&(i=wt(i,o)),lt(function(o,a,s,l){var u,c,p,f=[],d=[],h=a.length,g=o||Nt(t||"*",s.nodeType?[s]:s,[]),m=!e||!o&&t?g:xt(g,f,e,s,l),y=n?i||(o?e:h||r)?[]:a:m;if(n&&n(m,y,s,l),r){u=xt(y,d),r(u,[],s,l),c=u.length;while(c--)(p=u[c])&&(y[d[c]]=!(m[d[c]]=p))}if(o){if(i||e){if(i){u=[],c=y.length;while(c--)(p=y[c])&&u.push(m[c]=p);i(null,y=[],u,l)}c=y.length;while(c--)(p=y[c])&&(u=i?F.call(o,p):f[c])>-1&&(o[u]=!(a[u]=p))}}else y=xt(y===a?y.splice(h,y.length):y),i?i(null,a,y,l):M.apply(a,y)})}function Tt(e){var t,n,r,i=e.length,a=o.relative[e[0].type],s=a||o.relative[" "],l=a?1:0,c=vt(function(e){return e===t},s,!0),p=vt(function(e){return F.call(t,e)>-1},s,!0),f=[function(e,n,r){return!a&&(r||n!==u)||((t=n).nodeType?c(e,n,r):p(e,n,r))}];for(;i>l;l++)if(n=o.relative[e[l].type])f=[vt(bt(f),n)];else{if(n=o.filter[e[l].type].apply(null,e[l].matches),n[b]){for(r=++l;i>r;r++)if(o.relative[e[r].type])break;return wt(l>1&&bt(f),l>1&&yt(e.slice(0,l-1).concat({value:" "===e[l-2].type?"*":""})).replace(z,"$1"),n,r>l&&Tt(e.slice(l,r)),i>r&&Tt(e=e.slice(r)),i>r&&yt(e))}f.push(n)}return bt(f)}function Ct(e,t){var n=0,r=t.length>0,a=e.length>0,s=function(s,l,c,p,d){var h,g,m,y=[],v=0,b="0",x=s&&[],w=null!=d,C=u,N=s||a&&o.find.TAG("*",d&&l.parentNode||l),k=T+=null==C?1:Math.random()||.1;for(w&&(u=l!==f&&l,i=n);null!=(h=N[b]);b++){if(a&&h){g=0;while(m=e[g++])if(m(h,l,c)){p.push(h);break}w&&(T=k,i=++n)}r&&((h=!m&&h)&&v--,s&&x.push(h))}if(v+=b,r&&b!==v){g=0;while(m=t[g++])m(x,y,l,c);if(s){if(v>0)while(b--)x[b]||y[b]||(y[b]=q.call(p));y=xt(y)}M.apply(p,y),w&&!s&&y.length>0&&v+t.length>1&&at.uniqueSort(p)}return w&&(T=k,u=C),x};return r?lt(s):s}l=at.compile=function(e,t){var n,r=[],i=[],o=E[e+" "];if(!o){t||(t=mt(e)),n=t.length;while(n--)o=Tt(t[n]),o[b]?r.push(o):i.push(o);o=E(e,Ct(i,r))}return o};function Nt(e,t,n){var r=0,i=t.length;for(;i>r;r++)at(e,t[r],n);return n}function kt(e,t,n,i){var a,s,u,c,p,f=mt(e);if(!i&&1===f.length){if(s=f[0]=f[0].slice(0),s.length>2&&"ID"===(u=s[0]).type&&r.getById&&9===t.nodeType&&h&&o.relative[s[1].type]){if(t=(o.find.ID(u.matches[0].replace(rt,it),t)||[])[0],!t)return n;e=e.slice(s.shift().value.length)}a=Q.needsContext.test(e)?0:s.length;while(a--){if(u=s[a],o.relative[c=u.type])break;if((p=o.find[c])&&(i=p(u.matches[0].replace(rt,it),V.test(s[0].type)&&t.parentNode||t))){if(s.splice(a,1),e=i.length&&yt(s),!e)return M.apply(n,i),n;break}}}return l(e,f)(i,t,!h,n,V.test(e)),n}r.sortStable=b.split("").sort(A).join("")===b,r.detectDuplicates=S,p(),r.sortDetached=ut(function(e){return 1&e.compareDocumentPosition(f.createElement("div"))}),ut(function(e){return e.innerHTML="<a href='#'></a>","#"===e.firstChild.getAttribute("href")})||ct("type|href|height|width",function(e,n,r){return r?t:e.getAttribute(n,"type"===n.toLowerCase()?1:2)}),r.attributes&&ut(function(e){return e.innerHTML="<input/>",e.firstChild.setAttribute("value",""),""===e.firstChild.getAttribute("value")})||ct("value",function(e,n,r){return r||"input"!==e.nodeName.toLowerCase()?t:e.defaultValue}),ut(function(e){return null==e.getAttribute("disabled")})||ct(B,function(e,n,r){var i;return r?t:(i=e.getAttributeNode(n))&&i.specified?i.value:e[n]===!0?n.toLowerCase():null}),x.find=at,x.expr=at.selectors,x.expr[":"]=x.expr.pseudos,x.unique=at.uniqueSort,x.text=at.getText,x.isXMLDoc=at.isXML,x.contains=at.contains}(e);var O={};function F(e){var t=O[e]={};return x.each(e.match(T)||[],function(e,n){t[n]=!0}),t}x.Callbacks=function(e){e="string"==typeof e?O[e]||F(e):x.extend({},e);var n,r,i,o,a,s,l=[],u=!e.once&&[],c=function(t){for(r=e.memory&&t,i=!0,a=s||0,s=0,o=l.length,n=!0;l&&o>a;a++)if(l[a].apply(t[0],t[1])===!1&&e.stopOnFalse){r=!1;break}n=!1,l&&(u?u.length&&c(u.shift()):r?l=[]:p.disable())},p={add:function(){if(l){var t=l.length;(function i(t){x.each(t,function(t,n){var r=x.type(n);"function"===r?e.unique&&p.has(n)||l.push(n):n&&n.length&&"string"!==r&&i(n)})})(arguments),n?o=l.length:r&&(s=t,c(r))}return this},remove:function(){return l&&x.each(arguments,function(e,t){var r;while((r=x.inArray(t,l,r))>-1)l.splice(r,1),n&&(o>=r&&o--,a>=r&&a--)}),this},has:function(e){return e?x.inArray(e,l)>-1:!(!l||!l.length)},empty:function(){return l=[],o=0,this},disable:function(){return l=u=r=t,this},disabled:function(){return!l},lock:function(){return u=t,r||p.disable(),this},locked:function(){return!u},fireWith:function(e,t){return!l||i&&!u||(t=t||[],t=[e,t.slice?t.slice():t],n?u.push(t):c(t)),this},fire:function(){return p.fireWith(this,arguments),this},fired:function(){return!!i}};return p},x.extend({Deferred:function(e){var t=[["resolve","done",x.Callbacks("once memory"),"resolved"],["reject","fail",x.Callbacks("once memory"),"rejected"],["notify","progress",x.Callbacks("memory")]],n="pending",r={state:function(){return n},always:function(){return i.done(arguments).fail(arguments),this},then:function(){var e=arguments;return x.Deferred(function(n){x.each(t,function(t,o){var a=o[0],s=x.isFunction(e[t])&&e[t];i[o[1]](function(){var e=s&&s.apply(this,arguments);e&&x.isFunction(e.promise)?e.promise().done(n.resolve).fail(n.reject).progress(n.notify):n[a+"With"](this===r?n.promise():this,s?[e]:arguments)})}),e=null}).promise()},promise:function(e){return null!=e?x.extend(e,r):r}},i={};return r.pipe=r.then,x.each(t,function(e,o){var a=o[2],s=o[3];r[o[1]]=a.add,s&&a.add(function(){n=s},t[1^e][2].disable,t[2][2].lock),i[o[0]]=function(){return i[o[0]+"With"](this===i?r:this,arguments),this},i[o[0]+"With"]=a.fireWith}),r.promise(i),e&&e.call(i,i),i},when:function(e){var t=0,n=g.call(arguments),r=n.length,i=1!==r||e&&x.isFunction(e.promise)?r:0,o=1===i?e:x.Deferred(),a=function(e,t,n){return function(r){t[e]=this,n[e]=arguments.length>1?g.call(arguments):r,n===s?o.notifyWith(t,n):--i||o.resolveWith(t,n)}},s,l,u;if(r>1)for(s=Array(r),l=Array(r),u=Array(r);r>t;t++)n[t]&&x.isFunction(n[t].promise)?n[t].promise().done(a(t,u,n)).fail(o.reject).progress(a(t,l,s)):--i;return i||o.resolveWith(u,n),o.promise()}}),x.support=function(t){var n,r,o,s,l,u,c,p,f,d=a.createElement("div");if(d.setAttribute("className","t"),d.innerHTML="  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>",n=d.getElementsByTagName("*")||[],r=d.getElementsByTagName("a")[0],!r||!r.style||!n.length)return t;s=a.createElement("select"),u=s.appendChild(a.createElement("option")),o=d.getElementsByTagName("input")[0],r.style.cssText="top:1px;float:left;opacity:.5",t.getSetAttribute="t"!==d.className,t.leadingWhitespace=3===d.firstChild.nodeType,t.tbody=!d.getElementsByTagName("tbody").length,t.htmlSerialize=!!d.getElementsByTagName("link").length,t.style=/top/.test(r.getAttribute("style")),t.hrefNormalized="/a"===r.getAttribute("href"),t.opacity=/^0.5/.test(r.style.opacity),t.cssFloat=!!r.style.cssFloat,t.checkOn=!!o.value,t.optSelected=u.selected,t.enctype=!!a.createElement("form").enctype,t.html5Clone="<:nav></:nav>"!==a.createElement("nav").cloneNode(!0).outerHTML,t.inlineBlockNeedsLayout=!1,t.shrinkWrapBlocks=!1,t.pixelPosition=!1,t.deleteExpando=!0,t.noCloneEvent=!0,t.reliableMarginRight=!0,t.boxSizingReliable=!0,o.checked=!0,t.noCloneChecked=o.cloneNode(!0).checked,s.disabled=!0,t.optDisabled=!u.disabled;try{delete d.test}catch(h){t.deleteExpando=!1}o=a.createElement("input"),o.setAttribute("value",""),t.input=""===o.getAttribute("value"),o.value="t",o.setAttribute("type","radio"),t.radioValue="t"===o.value,o.setAttribute("checked","t"),o.setAttribute("name","t"),l=a.createDocumentFragment(),l.appendChild(o),t.appendChecked=o.checked,t.checkClone=l.cloneNode(!0).cloneNode(!0).lastChild.checked,d.attachEvent&&(d.attachEvent("onclick",function(){t.noCloneEvent=!1}),d.cloneNode(!0).click());for(f in{submit:!0,change:!0,focusin:!0})d.setAttribute(c="on"+f,"t"),t[f+"Bubbles"]=c in e||d.attributes[c].expando===!1;d.style.backgroundClip="content-box",d.cloneNode(!0).style.backgroundClip="",t.clearCloneStyle="content-box"===d.style.backgroundClip;for(f in x(t))break;return t.ownLast="0"!==f,x(function(){var n,r,o,s="padding:0;margin:0;border:0;display:block;box-sizing:content-box;-moz-box-sizing:content-box;-webkit-box-sizing:content-box;",l=a.getElementsByTagName("body")[0];l&&(n=a.createElement("div"),n.style.cssText="border:0;width:0;height:0;position:absolute;top:0;left:-9999px;margin-top:1px",l.appendChild(n).appendChild(d),d.innerHTML="<table><tr><td></td><td>t</td></tr></table>",o=d.getElementsByTagName("td"),o[0].style.cssText="padding:0;margin:0;border:0;display:none",p=0===o[0].offsetHeight,o[0].style.display="",o[1].style.display="none",t.reliableHiddenOffsets=p&&0===o[0].offsetHeight,d.innerHTML="",d.style.cssText="box-sizing:border-box;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;padding:1px;border:1px;display:block;width:4px;margin-top:1%;position:absolute;top:1%;",x.swap(l,null!=l.style.zoom?{zoom:1}:{},function(){t.boxSizing=4===d.offsetWidth}),e.getComputedStyle&&(t.pixelPosition="1%"!==(e.getComputedStyle(d,null)||{}).top,t.boxSizingReliable="4px"===(e.getComputedStyle(d,null)||{width:"4px"}).width,r=d.appendChild(a.createElement("div")),r.style.cssText=d.style.cssText=s,r.style.marginRight=r.style.width="0",d.style.width="1px",t.reliableMarginRight=!parseFloat((e.getComputedStyle(r,null)||{}).marginRight)),typeof d.style.zoom!==i&&(d.innerHTML="",d.style.cssText=s+"width:1px;padding:1px;display:inline;zoom:1",t.inlineBlockNeedsLayout=3===d.offsetWidth,d.style.display="block",d.innerHTML="<div></div>",d.firstChild.style.width="5px",t.shrinkWrapBlocks=3!==d.offsetWidth,t.inlineBlockNeedsLayout&&(l.style.zoom=1)),l.removeChild(n),n=d=o=r=null)}),n=s=l=u=r=o=null,t
}({});var B=/(?:\{[\s\S]*\}|\[[\s\S]*\])$/,P=/([A-Z])/g;function R(e,n,r,i){if(x.acceptData(e)){var o,a,s=x.expando,l=e.nodeType,u=l?x.cache:e,c=l?e[s]:e[s]&&s;if(c&&u[c]&&(i||u[c].data)||r!==t||"string"!=typeof n)return c||(c=l?e[s]=p.pop()||x.guid++:s),u[c]||(u[c]=l?{}:{toJSON:x.noop}),("object"==typeof n||"function"==typeof n)&&(i?u[c]=x.extend(u[c],n):u[c].data=x.extend(u[c].data,n)),a=u[c],i||(a.data||(a.data={}),a=a.data),r!==t&&(a[x.camelCase(n)]=r),"string"==typeof n?(o=a[n],null==o&&(o=a[x.camelCase(n)])):o=a,o}}function W(e,t,n){if(x.acceptData(e)){var r,i,o=e.nodeType,a=o?x.cache:e,s=o?e[x.expando]:x.expando;if(a[s]){if(t&&(r=n?a[s]:a[s].data)){x.isArray(t)?t=t.concat(x.map(t,x.camelCase)):t in r?t=[t]:(t=x.camelCase(t),t=t in r?[t]:t.split(" ")),i=t.length;while(i--)delete r[t[i]];if(n?!I(r):!x.isEmptyObject(r))return}(n||(delete a[s].data,I(a[s])))&&(o?x.cleanData([e],!0):x.support.deleteExpando||a!=a.window?delete a[s]:a[s]=null)}}}x.extend({cache:{},noData:{applet:!0,embed:!0,object:"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"},hasData:function(e){return e=e.nodeType?x.cache[e[x.expando]]:e[x.expando],!!e&&!I(e)},data:function(e,t,n){return R(e,t,n)},removeData:function(e,t){return W(e,t)},_data:function(e,t,n){return R(e,t,n,!0)},_removeData:function(e,t){return W(e,t,!0)},acceptData:function(e){if(e.nodeType&&1!==e.nodeType&&9!==e.nodeType)return!1;var t=e.nodeName&&x.noData[e.nodeName.toLowerCase()];return!t||t!==!0&&e.getAttribute("classid")===t}}),x.fn.extend({data:function(e,n){var r,i,o=null,a=0,s=this[0];if(e===t){if(this.length&&(o=x.data(s),1===s.nodeType&&!x._data(s,"parsedAttrs"))){for(r=s.attributes;r.length>a;a++)i=r[a].name,0===i.indexOf("data-")&&(i=x.camelCase(i.slice(5)),$(s,i,o[i]));x._data(s,"parsedAttrs",!0)}return o}return"object"==typeof e?this.each(function(){x.data(this,e)}):arguments.length>1?this.each(function(){x.data(this,e,n)}):s?$(s,e,x.data(s,e)):null},removeData:function(e){return this.each(function(){x.removeData(this,e)})}});function $(e,n,r){if(r===t&&1===e.nodeType){var i="data-"+n.replace(P,"-$1").toLowerCase();if(r=e.getAttribute(i),"string"==typeof r){try{r="true"===r?!0:"false"===r?!1:"null"===r?null:+r+""===r?+r:B.test(r)?x.parseJSON(r):r}catch(o){}x.data(e,n,r)}else r=t}return r}function I(e){var t;for(t in e)if(("data"!==t||!x.isEmptyObject(e[t]))&&"toJSON"!==t)return!1;return!0}x.extend({queue:function(e,n,r){var i;return e?(n=(n||"fx")+"queue",i=x._data(e,n),r&&(!i||x.isArray(r)?i=x._data(e,n,x.makeArray(r)):i.push(r)),i||[]):t},dequeue:function(e,t){t=t||"fx";var n=x.queue(e,t),r=n.length,i=n.shift(),o=x._queueHooks(e,t),a=function(){x.dequeue(e,t)};"inprogress"===i&&(i=n.shift(),r--),i&&("fx"===t&&n.unshift("inprogress"),delete o.stop,i.call(e,a,o)),!r&&o&&o.empty.fire()},_queueHooks:function(e,t){var n=t+"queueHooks";return x._data(e,n)||x._data(e,n,{empty:x.Callbacks("once memory").add(function(){x._removeData(e,t+"queue"),x._removeData(e,n)})})}}),x.fn.extend({queue:function(e,n){var r=2;return"string"!=typeof e&&(n=e,e="fx",r--),r>arguments.length?x.queue(this[0],e):n===t?this:this.each(function(){var t=x.queue(this,e,n);x._queueHooks(this,e),"fx"===e&&"inprogress"!==t[0]&&x.dequeue(this,e)})},dequeue:function(e){return this.each(function(){x.dequeue(this,e)})},delay:function(e,t){return e=x.fx?x.fx.speeds[e]||e:e,t=t||"fx",this.queue(t,function(t,n){var r=setTimeout(t,e);n.stop=function(){clearTimeout(r)}})},clearQueue:function(e){return this.queue(e||"fx",[])},promise:function(e,n){var r,i=1,o=x.Deferred(),a=this,s=this.length,l=function(){--i||o.resolveWith(a,[a])};"string"!=typeof e&&(n=e,e=t),e=e||"fx";while(s--)r=x._data(a[s],e+"queueHooks"),r&&r.empty&&(i++,r.empty.add(l));return l(),o.promise(n)}});var z,X,U=/[\t\r\n\f]/g,V=/\r/g,Y=/^(?:input|select|textarea|button|object)$/i,J=/^(?:a|area)$/i,G=/^(?:checked|selected)$/i,Q=x.support.getSetAttribute,K=x.support.input;x.fn.extend({attr:function(e,t){return x.access(this,x.attr,e,t,arguments.length>1)},removeAttr:function(e){return this.each(function(){x.removeAttr(this,e)})},prop:function(e,t){return x.access(this,x.prop,e,t,arguments.length>1)},removeProp:function(e){return e=x.propFix[e]||e,this.each(function(){try{this[e]=t,delete this[e]}catch(n){}})},addClass:function(e){var t,n,r,i,o,a=0,s=this.length,l="string"==typeof e&&e;if(x.isFunction(e))return this.each(function(t){x(this).addClass(e.call(this,t,this.className))});if(l)for(t=(e||"").match(T)||[];s>a;a++)if(n=this[a],r=1===n.nodeType&&(n.className?(" "+n.className+" ").replace(U," "):" ")){o=0;while(i=t[o++])0>r.indexOf(" "+i+" ")&&(r+=i+" ");n.className=x.trim(r)}return this},removeClass:function(e){var t,n,r,i,o,a=0,s=this.length,l=0===arguments.length||"string"==typeof e&&e;if(x.isFunction(e))return this.each(function(t){x(this).removeClass(e.call(this,t,this.className))});if(l)for(t=(e||"").match(T)||[];s>a;a++)if(n=this[a],r=1===n.nodeType&&(n.className?(" "+n.className+" ").replace(U," "):"")){o=0;while(i=t[o++])while(r.indexOf(" "+i+" ")>=0)r=r.replace(" "+i+" "," ");n.className=e?x.trim(r):""}return this},toggleClass:function(e,t){var n=typeof e;return"boolean"==typeof t&&"string"===n?t?this.addClass(e):this.removeClass(e):x.isFunction(e)?this.each(function(n){x(this).toggleClass(e.call(this,n,this.className,t),t)}):this.each(function(){if("string"===n){var t,r=0,o=x(this),a=e.match(T)||[];while(t=a[r++])o.hasClass(t)?o.removeClass(t):o.addClass(t)}else(n===i||"boolean"===n)&&(this.className&&x._data(this,"__className__",this.className),this.className=this.className||e===!1?"":x._data(this,"__className__")||"")})},hasClass:function(e){var t=" "+e+" ",n=0,r=this.length;for(;r>n;n++)if(1===this[n].nodeType&&(" "+this[n].className+" ").replace(U," ").indexOf(t)>=0)return!0;return!1},val:function(e){var n,r,i,o=this[0];{if(arguments.length)return i=x.isFunction(e),this.each(function(n){var o;1===this.nodeType&&(o=i?e.call(this,n,x(this).val()):e,null==o?o="":"number"==typeof o?o+="":x.isArray(o)&&(o=x.map(o,function(e){return null==e?"":e+""})),r=x.valHooks[this.type]||x.valHooks[this.nodeName.toLowerCase()],r&&"set"in r&&r.set(this,o,"value")!==t||(this.value=o))});if(o)return r=x.valHooks[o.type]||x.valHooks[o.nodeName.toLowerCase()],r&&"get"in r&&(n=r.get(o,"value"))!==t?n:(n=o.value,"string"==typeof n?n.replace(V,""):null==n?"":n)}}}),x.extend({valHooks:{option:{get:function(e){var t=x.find.attr(e,"value");return null!=t?t:e.text}},select:{get:function(e){var t,n,r=e.options,i=e.selectedIndex,o="select-one"===e.type||0>i,a=o?null:[],s=o?i+1:r.length,l=0>i?s:o?i:0;for(;s>l;l++)if(n=r[l],!(!n.selected&&l!==i||(x.support.optDisabled?n.disabled:null!==n.getAttribute("disabled"))||n.parentNode.disabled&&x.nodeName(n.parentNode,"optgroup"))){if(t=x(n).val(),o)return t;a.push(t)}return a},set:function(e,t){var n,r,i=e.options,o=x.makeArray(t),a=i.length;while(a--)r=i[a],(r.selected=x.inArray(x(r).val(),o)>=0)&&(n=!0);return n||(e.selectedIndex=-1),o}}},attr:function(e,n,r){var o,a,s=e.nodeType;if(e&&3!==s&&8!==s&&2!==s)return typeof e.getAttribute===i?x.prop(e,n,r):(1===s&&x.isXMLDoc(e)||(n=n.toLowerCase(),o=x.attrHooks[n]||(x.expr.match.bool.test(n)?X:z)),r===t?o&&"get"in o&&null!==(a=o.get(e,n))?a:(a=x.find.attr(e,n),null==a?t:a):null!==r?o&&"set"in o&&(a=o.set(e,r,n))!==t?a:(e.setAttribute(n,r+""),r):(x.removeAttr(e,n),t))},removeAttr:function(e,t){var n,r,i=0,o=t&&t.match(T);if(o&&1===e.nodeType)while(n=o[i++])r=x.propFix[n]||n,x.expr.match.bool.test(n)?K&&Q||!G.test(n)?e[r]=!1:e[x.camelCase("default-"+n)]=e[r]=!1:x.attr(e,n,""),e.removeAttribute(Q?n:r)},attrHooks:{type:{set:function(e,t){if(!x.support.radioValue&&"radio"===t&&x.nodeName(e,"input")){var n=e.value;return e.setAttribute("type",t),n&&(e.value=n),t}}}},propFix:{"for":"htmlFor","class":"className"},prop:function(e,n,r){var i,o,a,s=e.nodeType;if(e&&3!==s&&8!==s&&2!==s)return a=1!==s||!x.isXMLDoc(e),a&&(n=x.propFix[n]||n,o=x.propHooks[n]),r!==t?o&&"set"in o&&(i=o.set(e,r,n))!==t?i:e[n]=r:o&&"get"in o&&null!==(i=o.get(e,n))?i:e[n]},propHooks:{tabIndex:{get:function(e){var t=x.find.attr(e,"tabindex");return t?parseInt(t,10):Y.test(e.nodeName)||J.test(e.nodeName)&&e.href?0:-1}}}}),X={set:function(e,t,n){return t===!1?x.removeAttr(e,n):K&&Q||!G.test(n)?e.setAttribute(!Q&&x.propFix[n]||n,n):e[x.camelCase("default-"+n)]=e[n]=!0,n}},x.each(x.expr.match.bool.source.match(/\w+/g),function(e,n){var r=x.expr.attrHandle[n]||x.find.attr;x.expr.attrHandle[n]=K&&Q||!G.test(n)?function(e,n,i){var o=x.expr.attrHandle[n],a=i?t:(x.expr.attrHandle[n]=t)!=r(e,n,i)?n.toLowerCase():null;return x.expr.attrHandle[n]=o,a}:function(e,n,r){return r?t:e[x.camelCase("default-"+n)]?n.toLowerCase():null}}),K&&Q||(x.attrHooks.value={set:function(e,n,r){return x.nodeName(e,"input")?(e.defaultValue=n,t):z&&z.set(e,n,r)}}),Q||(z={set:function(e,n,r){var i=e.getAttributeNode(r);return i||e.setAttributeNode(i=e.ownerDocument.createAttribute(r)),i.value=n+="","value"===r||n===e.getAttribute(r)?n:t}},x.expr.attrHandle.id=x.expr.attrHandle.name=x.expr.attrHandle.coords=function(e,n,r){var i;return r?t:(i=e.getAttributeNode(n))&&""!==i.value?i.value:null},x.valHooks.button={get:function(e,n){var r=e.getAttributeNode(n);return r&&r.specified?r.value:t},set:z.set},x.attrHooks.contenteditable={set:function(e,t,n){z.set(e,""===t?!1:t,n)}},x.each(["width","height"],function(e,n){x.attrHooks[n]={set:function(e,r){return""===r?(e.setAttribute(n,"auto"),r):t}}})),x.support.hrefNormalized||x.each(["href","src"],function(e,t){x.propHooks[t]={get:function(e){return e.getAttribute(t,4)}}}),x.support.style||(x.attrHooks.style={get:function(e){return e.style.cssText||t},set:function(e,t){return e.style.cssText=t+""}}),x.support.optSelected||(x.propHooks.selected={get:function(e){var t=e.parentNode;return t&&(t.selectedIndex,t.parentNode&&t.parentNode.selectedIndex),null}}),x.each(["tabIndex","readOnly","maxLength","cellSpacing","cellPadding","rowSpan","colSpan","useMap","frameBorder","contentEditable"],function(){x.propFix[this.toLowerCase()]=this}),x.support.enctype||(x.propFix.enctype="encoding"),x.each(["radio","checkbox"],function(){x.valHooks[this]={set:function(e,n){return x.isArray(n)?e.checked=x.inArray(x(e).val(),n)>=0:t}},x.support.checkOn||(x.valHooks[this].get=function(e){return null===e.getAttribute("value")?"on":e.value})});var Z=/^(?:input|select|textarea)$/i,et=/^key/,tt=/^(?:mouse|contextmenu)|click/,nt=/^(?:focusinfocus|focusoutblur)$/,rt=/^([^.]*)(?:\.(.+)|)$/;function it(){return!0}function ot(){return!1}function at(){try{return a.activeElement}catch(e){}}x.event={global:{},add:function(e,n,r,o,a){var s,l,u,c,p,f,d,h,g,m,y,v=x._data(e);if(v){r.handler&&(c=r,r=c.handler,a=c.selector),r.guid||(r.guid=x.guid++),(l=v.events)||(l=v.events={}),(f=v.handle)||(f=v.handle=function(e){return typeof x===i||e&&x.event.triggered===e.type?t:x.event.dispatch.apply(f.elem,arguments)},f.elem=e),n=(n||"").match(T)||[""],u=n.length;while(u--)s=rt.exec(n[u])||[],g=y=s[1],m=(s[2]||"").split(".").sort(),g&&(p=x.event.special[g]||{},g=(a?p.delegateType:p.bindType)||g,p=x.event.special[g]||{},d=x.extend({type:g,origType:y,data:o,handler:r,guid:r.guid,selector:a,needsContext:a&&x.expr.match.needsContext.test(a),namespace:m.join(".")},c),(h=l[g])||(h=l[g]=[],h.delegateCount=0,p.setup&&p.setup.call(e,o,m,f)!==!1||(e.addEventListener?e.addEventListener(g,f,!1):e.attachEvent&&e.attachEvent("on"+g,f))),p.add&&(p.add.call(e,d),d.handler.guid||(d.handler.guid=r.guid)),a?h.splice(h.delegateCount++,0,d):h.push(d),x.event.global[g]=!0);e=null}},remove:function(e,t,n,r,i){var o,a,s,l,u,c,p,f,d,h,g,m=x.hasData(e)&&x._data(e);if(m&&(c=m.events)){t=(t||"").match(T)||[""],u=t.length;while(u--)if(s=rt.exec(t[u])||[],d=g=s[1],h=(s[2]||"").split(".").sort(),d){p=x.event.special[d]||{},d=(r?p.delegateType:p.bindType)||d,f=c[d]||[],s=s[2]&&RegExp("(^|\\.)"+h.join("\\.(?:.*\\.|)")+"(\\.|$)"),l=o=f.length;while(o--)a=f[o],!i&&g!==a.origType||n&&n.guid!==a.guid||s&&!s.test(a.namespace)||r&&r!==a.selector&&("**"!==r||!a.selector)||(f.splice(o,1),a.selector&&f.delegateCount--,p.remove&&p.remove.call(e,a));l&&!f.length&&(p.teardown&&p.teardown.call(e,h,m.handle)!==!1||x.removeEvent(e,d,m.handle),delete c[d])}else for(d in c)x.event.remove(e,d+t[u],n,r,!0);x.isEmptyObject(c)&&(delete m.handle,x._removeData(e,"events"))}},trigger:function(n,r,i,o){var s,l,u,c,p,f,d,h=[i||a],g=v.call(n,"type")?n.type:n,m=v.call(n,"namespace")?n.namespace.split("."):[];if(u=f=i=i||a,3!==i.nodeType&&8!==i.nodeType&&!nt.test(g+x.event.triggered)&&(g.indexOf(".")>=0&&(m=g.split("."),g=m.shift(),m.sort()),l=0>g.indexOf(":")&&"on"+g,n=n[x.expando]?n:new x.Event(g,"object"==typeof n&&n),n.isTrigger=o?2:3,n.namespace=m.join("."),n.namespace_re=n.namespace?RegExp("(^|\\.)"+m.join("\\.(?:.*\\.|)")+"(\\.|$)"):null,n.result=t,n.target||(n.target=i),r=null==r?[n]:x.makeArray(r,[n]),p=x.event.special[g]||{},o||!p.trigger||p.trigger.apply(i,r)!==!1)){if(!o&&!p.noBubble&&!x.isWindow(i)){for(c=p.delegateType||g,nt.test(c+g)||(u=u.parentNode);u;u=u.parentNode)h.push(u),f=u;f===(i.ownerDocument||a)&&h.push(f.defaultView||f.parentWindow||e)}d=0;while((u=h[d++])&&!n.isPropagationStopped())n.type=d>1?c:p.bindType||g,s=(x._data(u,"events")||{})[n.type]&&x._data(u,"handle"),s&&s.apply(u,r),s=l&&u[l],s&&x.acceptData(u)&&s.apply&&s.apply(u,r)===!1&&n.preventDefault();if(n.type=g,!o&&!n.isDefaultPrevented()&&(!p._default||p._default.apply(h.pop(),r)===!1)&&x.acceptData(i)&&l&&i[g]&&!x.isWindow(i)){f=i[l],f&&(i[l]=null),x.event.triggered=g;try{i[g]()}catch(y){}x.event.triggered=t,f&&(i[l]=f)}return n.result}},dispatch:function(e){e=x.event.fix(e);var n,r,i,o,a,s=[],l=g.call(arguments),u=(x._data(this,"events")||{})[e.type]||[],c=x.event.special[e.type]||{};if(l[0]=e,e.delegateTarget=this,!c.preDispatch||c.preDispatch.call(this,e)!==!1){s=x.event.handlers.call(this,e,u),n=0;while((o=s[n++])&&!e.isPropagationStopped()){e.currentTarget=o.elem,a=0;while((i=o.handlers[a++])&&!e.isImmediatePropagationStopped())(!e.namespace_re||e.namespace_re.test(i.namespace))&&(e.handleObj=i,e.data=i.data,r=((x.event.special[i.origType]||{}).handle||i.handler).apply(o.elem,l),r!==t&&(e.result=r)===!1&&(e.preventDefault(),e.stopPropagation()))}return c.postDispatch&&c.postDispatch.call(this,e),e.result}},handlers:function(e,n){var r,i,o,a,s=[],l=n.delegateCount,u=e.target;if(l&&u.nodeType&&(!e.button||"click"!==e.type))for(;u!=this;u=u.parentNode||this)if(1===u.nodeType&&(u.disabled!==!0||"click"!==e.type)){for(o=[],a=0;l>a;a++)i=n[a],r=i.selector+" ",o[r]===t&&(o[r]=i.needsContext?x(r,this).index(u)>=0:x.find(r,this,null,[u]).length),o[r]&&o.push(i);o.length&&s.push({elem:u,handlers:o})}return n.length>l&&s.push({elem:this,handlers:n.slice(l)}),s},fix:function(e){if(e[x.expando])return e;var t,n,r,i=e.type,o=e,s=this.fixHooks[i];s||(this.fixHooks[i]=s=tt.test(i)?this.mouseHooks:et.test(i)?this.keyHooks:{}),r=s.props?this.props.concat(s.props):this.props,e=new x.Event(o),t=r.length;while(t--)n=r[t],e[n]=o[n];return e.target||(e.target=o.srcElement||a),3===e.target.nodeType&&(e.target=e.target.parentNode),e.metaKey=!!e.metaKey,s.filter?s.filter(e,o):e},props:"altKey bubbles cancelable ctrlKey currentTarget eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),fixHooks:{},keyHooks:{props:"char charCode key keyCode".split(" "),filter:function(e,t){return null==e.which&&(e.which=null!=t.charCode?t.charCode:t.keyCode),e}},mouseHooks:{props:"button buttons clientX clientY fromElement offsetX offsetY pageX pageY screenX screenY toElement".split(" "),filter:function(e,n){var r,i,o,s=n.button,l=n.fromElement;return null==e.pageX&&null!=n.clientX&&(i=e.target.ownerDocument||a,o=i.documentElement,r=i.body,e.pageX=n.clientX+(o&&o.scrollLeft||r&&r.scrollLeft||0)-(o&&o.clientLeft||r&&r.clientLeft||0),e.pageY=n.clientY+(o&&o.scrollTop||r&&r.scrollTop||0)-(o&&o.clientTop||r&&r.clientTop||0)),!e.relatedTarget&&l&&(e.relatedTarget=l===e.target?n.toElement:l),e.which||s===t||(e.which=1&s?1:2&s?3:4&s?2:0),e}},special:{load:{noBubble:!0},focus:{trigger:function(){if(this!==at()&&this.focus)try{return this.focus(),!1}catch(e){}},delegateType:"focusin"},blur:{trigger:function(){return this===at()&&this.blur?(this.blur(),!1):t},delegateType:"focusout"},click:{trigger:function(){return x.nodeName(this,"input")&&"checkbox"===this.type&&this.click?(this.click(),!1):t},_default:function(e){return x.nodeName(e.target,"a")}},beforeunload:{postDispatch:function(e){e.result!==t&&(e.originalEvent.returnValue=e.result)}}},simulate:function(e,t,n,r){var i=x.extend(new x.Event,n,{type:e,isSimulated:!0,originalEvent:{}});r?x.event.trigger(i,null,t):x.event.dispatch.call(t,i),i.isDefaultPrevented()&&n.preventDefault()}},x.removeEvent=a.removeEventListener?function(e,t,n){e.removeEventListener&&e.removeEventListener(t,n,!1)}:function(e,t,n){var r="on"+t;e.detachEvent&&(typeof e[r]===i&&(e[r]=null),e.detachEvent(r,n))},x.Event=function(e,n){return this instanceof x.Event?(e&&e.type?(this.originalEvent=e,this.type=e.type,this.isDefaultPrevented=e.defaultPrevented||e.returnValue===!1||e.getPreventDefault&&e.getPreventDefault()?it:ot):this.type=e,n&&x.extend(this,n),this.timeStamp=e&&e.timeStamp||x.now(),this[x.expando]=!0,t):new x.Event(e,n)},x.Event.prototype={isDefaultPrevented:ot,isPropagationStopped:ot,isImmediatePropagationStopped:ot,preventDefault:function(){var e=this.originalEvent;this.isDefaultPrevented=it,e&&(e.preventDefault?e.preventDefault():e.returnValue=!1)},stopPropagation:function(){var e=this.originalEvent;this.isPropagationStopped=it,e&&(e.stopPropagation&&e.stopPropagation(),e.cancelBubble=!0)},stopImmediatePropagation:function(){this.isImmediatePropagationStopped=it,this.stopPropagation()}},x.each({mouseenter:"mouseover",mouseleave:"mouseout"},function(e,t){x.event.special[e]={delegateType:t,bindType:t,handle:function(e){var n,r=this,i=e.relatedTarget,o=e.handleObj;return(!i||i!==r&&!x.contains(r,i))&&(e.type=o.origType,n=o.handler.apply(this,arguments),e.type=t),n}}}),x.support.submitBubbles||(x.event.special.submit={setup:function(){return x.nodeName(this,"form")?!1:(x.event.add(this,"click._submit keypress._submit",function(e){var n=e.target,r=x.nodeName(n,"input")||x.nodeName(n,"button")?n.form:t;r&&!x._data(r,"submitBubbles")&&(x.event.add(r,"submit._submit",function(e){e._submit_bubble=!0}),x._data(r,"submitBubbles",!0))}),t)},postDispatch:function(e){e._submit_bubble&&(delete e._submit_bubble,this.parentNode&&!e.isTrigger&&x.event.simulate("submit",this.parentNode,e,!0))},teardown:function(){return x.nodeName(this,"form")?!1:(x.event.remove(this,"._submit"),t)}}),x.support.changeBubbles||(x.event.special.change={setup:function(){return Z.test(this.nodeName)?(("checkbox"===this.type||"radio"===this.type)&&(x.event.add(this,"propertychange._change",function(e){"checked"===e.originalEvent.propertyName&&(this._just_changed=!0)}),x.event.add(this,"click._change",function(e){this._just_changed&&!e.isTrigger&&(this._just_changed=!1),x.event.simulate("change",this,e,!0)})),!1):(x.event.add(this,"beforeactivate._change",function(e){var t=e.target;Z.test(t.nodeName)&&!x._data(t,"changeBubbles")&&(x.event.add(t,"change._change",function(e){!this.parentNode||e.isSimulated||e.isTrigger||x.event.simulate("change",this.parentNode,e,!0)}),x._data(t,"changeBubbles",!0))}),t)},handle:function(e){var n=e.target;return this!==n||e.isSimulated||e.isTrigger||"radio"!==n.type&&"checkbox"!==n.type?e.handleObj.handler.apply(this,arguments):t},teardown:function(){return x.event.remove(this,"._change"),!Z.test(this.nodeName)}}),x.support.focusinBubbles||x.each({focus:"focusin",blur:"focusout"},function(e,t){var n=0,r=function(e){x.event.simulate(t,e.target,x.event.fix(e),!0)};x.event.special[t]={setup:function(){0===n++&&a.addEventListener(e,r,!0)},teardown:function(){0===--n&&a.removeEventListener(e,r,!0)}}}),x.fn.extend({on:function(e,n,r,i,o){var a,s;if("object"==typeof e){"string"!=typeof n&&(r=r||n,n=t);for(a in e)this.on(a,n,r,e[a],o);return this}if(null==r&&null==i?(i=n,r=n=t):null==i&&("string"==typeof n?(i=r,r=t):(i=r,r=n,n=t)),i===!1)i=ot;else if(!i)return this;return 1===o&&(s=i,i=function(e){return x().off(e),s.apply(this,arguments)},i.guid=s.guid||(s.guid=x.guid++)),this.each(function(){x.event.add(this,e,i,r,n)})},one:function(e,t,n,r){return this.on(e,t,n,r,1)},off:function(e,n,r){var i,o;if(e&&e.preventDefault&&e.handleObj)return i=e.handleObj,x(e.delegateTarget).off(i.namespace?i.origType+"."+i.namespace:i.origType,i.selector,i.handler),this;if("object"==typeof e){for(o in e)this.off(o,n,e[o]);return this}return(n===!1||"function"==typeof n)&&(r=n,n=t),r===!1&&(r=ot),this.each(function(){x.event.remove(this,e,r,n)})},trigger:function(e,t){return this.each(function(){x.event.trigger(e,t,this)})},triggerHandler:function(e,n){var r=this[0];return r?x.event.trigger(e,n,r,!0):t}});var st=/^.[^:#\[\.,]*$/,lt=/^(?:parents|prev(?:Until|All))/,ut=x.expr.match.needsContext,ct={children:!0,contents:!0,next:!0,prev:!0};x.fn.extend({find:function(e){var t,n=[],r=this,i=r.length;if("string"!=typeof e)return this.pushStack(x(e).filter(function(){for(t=0;i>t;t++)if(x.contains(r[t],this))return!0}));for(t=0;i>t;t++)x.find(e,r[t],n);return n=this.pushStack(i>1?x.unique(n):n),n.selector=this.selector?this.selector+" "+e:e,n},has:function(e){var t,n=x(e,this),r=n.length;return this.filter(function(){for(t=0;r>t;t++)if(x.contains(this,n[t]))return!0})},not:function(e){return this.pushStack(ft(this,e||[],!0))},filter:function(e){return this.pushStack(ft(this,e||[],!1))},is:function(e){return!!ft(this,"string"==typeof e&&ut.test(e)?x(e):e||[],!1).length},closest:function(e,t){var n,r=0,i=this.length,o=[],a=ut.test(e)||"string"!=typeof e?x(e,t||this.context):0;for(;i>r;r++)for(n=this[r];n&&n!==t;n=n.parentNode)if(11>n.nodeType&&(a?a.index(n)>-1:1===n.nodeType&&x.find.matchesSelector(n,e))){n=o.push(n);break}return this.pushStack(o.length>1?x.unique(o):o)},index:function(e){return e?"string"==typeof e?x.inArray(this[0],x(e)):x.inArray(e.jquery?e[0]:e,this):this[0]&&this[0].parentNode?this.first().prevAll().length:-1},add:function(e,t){var n="string"==typeof e?x(e,t):x.makeArray(e&&e.nodeType?[e]:e),r=x.merge(this.get(),n);return this.pushStack(x.unique(r))},addBack:function(e){return this.add(null==e?this.prevObject:this.prevObject.filter(e))}});function pt(e,t){do e=e[t];while(e&&1!==e.nodeType);return e}x.each({parent:function(e){var t=e.parentNode;return t&&11!==t.nodeType?t:null},parents:function(e){return x.dir(e,"parentNode")},parentsUntil:function(e,t,n){return x.dir(e,"parentNode",n)},next:function(e){return pt(e,"nextSibling")},prev:function(e){return pt(e,"previousSibling")},nextAll:function(e){return x.dir(e,"nextSibling")},prevAll:function(e){return x.dir(e,"previousSibling")},nextUntil:function(e,t,n){return x.dir(e,"nextSibling",n)},prevUntil:function(e,t,n){return x.dir(e,"previousSibling",n)},siblings:function(e){return x.sibling((e.parentNode||{}).firstChild,e)},children:function(e){return x.sibling(e.firstChild)},contents:function(e){return x.nodeName(e,"iframe")?e.contentDocument||e.contentWindow.document:x.merge([],e.childNodes)}},function(e,t){x.fn[e]=function(n,r){var i=x.map(this,t,n);return"Until"!==e.slice(-5)&&(r=n),r&&"string"==typeof r&&(i=x.filter(r,i)),this.length>1&&(ct[e]||(i=x.unique(i)),lt.test(e)&&(i=i.reverse())),this.pushStack(i)}}),x.extend({filter:function(e,t,n){var r=t[0];return n&&(e=":not("+e+")"),1===t.length&&1===r.nodeType?x.find.matchesSelector(r,e)?[r]:[]:x.find.matches(e,x.grep(t,function(e){return 1===e.nodeType}))},dir:function(e,n,r){var i=[],o=e[n];while(o&&9!==o.nodeType&&(r===t||1!==o.nodeType||!x(o).is(r)))1===o.nodeType&&i.push(o),o=o[n];return i},sibling:function(e,t){var n=[];for(;e;e=e.nextSibling)1===e.nodeType&&e!==t&&n.push(e);return n}});function ft(e,t,n){if(x.isFunction(t))return x.grep(e,function(e,r){return!!t.call(e,r,e)!==n});if(t.nodeType)return x.grep(e,function(e){return e===t!==n});if("string"==typeof t){if(st.test(t))return x.filter(t,e,n);t=x.filter(t,e)}return x.grep(e,function(e){return x.inArray(e,t)>=0!==n})}function dt(e){var t=ht.split("|"),n=e.createDocumentFragment();if(n.createElement)while(t.length)n.createElement(t.pop());return n}var ht="abbr|article|aside|audio|bdi|canvas|data|datalist|details|figcaption|figure|footer|header|hgroup|mark|meter|nav|output|progress|section|summary|time|video",gt=/ jQuery\d+="(?:null|\d+)"/g,mt=RegExp("<(?:"+ht+")[\\s/>]","i"),yt=/^\s+/,vt=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/gi,bt=/<([\w:]+)/,xt=/<tbody/i,wt=/<|&#?\w+;/,Tt=/<(?:script|style|link)/i,Ct=/^(?:checkbox|radio)$/i,Nt=/checked\s*(?:[^=]|=\s*.checked.)/i,kt=/^$|\/(?:java|ecma)script/i,Et=/^true\/(.*)/,St=/^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g,At={option:[1,"<select multiple='multiple'>","</select>"],legend:[1,"<fieldset>","</fieldset>"],area:[1,"<map>","</map>"],param:[1,"<object>","</object>"],thead:[1,"<table>","</table>"],tr:[2,"<table><tbody>","</tbody></table>"],col:[2,"<table><tbody></tbody><colgroup>","</colgroup></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],_default:x.support.htmlSerialize?[0,"",""]:[1,"X<div>","</div>"]},jt=dt(a),Dt=jt.appendChild(a.createElement("div"));At.optgroup=At.option,At.tbody=At.tfoot=At.colgroup=At.caption=At.thead,At.th=At.td,x.fn.extend({text:function(e){return x.access(this,function(e){return e===t?x.text(this):this.empty().append((this[0]&&this[0].ownerDocument||a).createTextNode(e))},null,e,arguments.length)},append:function(){return this.domManip(arguments,function(e){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var t=Lt(this,e);t.appendChild(e)}})},prepend:function(){return this.domManip(arguments,function(e){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var t=Lt(this,e);t.insertBefore(e,t.firstChild)}})},before:function(){return this.domManip(arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this)})},after:function(){return this.domManip(arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this.nextSibling)})},remove:function(e,t){var n,r=e?x.filter(e,this):this,i=0;for(;null!=(n=r[i]);i++)t||1!==n.nodeType||x.cleanData(Ft(n)),n.parentNode&&(t&&x.contains(n.ownerDocument,n)&&_t(Ft(n,"script")),n.parentNode.removeChild(n));return this},empty:function(){var e,t=0;for(;null!=(e=this[t]);t++){1===e.nodeType&&x.cleanData(Ft(e,!1));while(e.firstChild)e.removeChild(e.firstChild);e.options&&x.nodeName(e,"select")&&(e.options.length=0)}return this},clone:function(e,t){return e=null==e?!1:e,t=null==t?e:t,this.map(function(){return x.clone(this,e,t)})},html:function(e){return x.access(this,function(e){var n=this[0]||{},r=0,i=this.length;if(e===t)return 1===n.nodeType?n.innerHTML.replace(gt,""):t;if(!("string"!=typeof e||Tt.test(e)||!x.support.htmlSerialize&&mt.test(e)||!x.support.leadingWhitespace&&yt.test(e)||At[(bt.exec(e)||["",""])[1].toLowerCase()])){e=e.replace(vt,"<$1></$2>");try{for(;i>r;r++)n=this[r]||{},1===n.nodeType&&(x.cleanData(Ft(n,!1)),n.innerHTML=e);n=0}catch(o){}}n&&this.empty().append(e)},null,e,arguments.length)},replaceWith:function(){var e=x.map(this,function(e){return[e.nextSibling,e.parentNode]}),t=0;return this.domManip(arguments,function(n){var r=e[t++],i=e[t++];i&&(r&&r.parentNode!==i&&(r=this.nextSibling),x(this).remove(),i.insertBefore(n,r))},!0),t?this:this.remove()},detach:function(e){return this.remove(e,!0)},domManip:function(e,t,n){e=d.apply([],e);var r,i,o,a,s,l,u=0,c=this.length,p=this,f=c-1,h=e[0],g=x.isFunction(h);if(g||!(1>=c||"string"!=typeof h||x.support.checkClone)&&Nt.test(h))return this.each(function(r){var i=p.eq(r);g&&(e[0]=h.call(this,r,i.html())),i.domManip(e,t,n)});if(c&&(l=x.buildFragment(e,this[0].ownerDocument,!1,!n&&this),r=l.firstChild,1===l.childNodes.length&&(l=r),r)){for(a=x.map(Ft(l,"script"),Ht),o=a.length;c>u;u++)i=l,u!==f&&(i=x.clone(i,!0,!0),o&&x.merge(a,Ft(i,"script"))),t.call(this[u],i,u);if(o)for(s=a[a.length-1].ownerDocument,x.map(a,qt),u=0;o>u;u++)i=a[u],kt.test(i.type||"")&&!x._data(i,"globalEval")&&x.contains(s,i)&&(i.src?x._evalUrl(i.src):x.globalEval((i.text||i.textContent||i.innerHTML||"").replace(St,"")));l=r=null}return this}});function Lt(e,t){return x.nodeName(e,"table")&&x.nodeName(1===t.nodeType?t:t.firstChild,"tr")?e.getElementsByTagName("tbody")[0]||e.appendChild(e.ownerDocument.createElement("tbody")):e}function Ht(e){return e.type=(null!==x.find.attr(e,"type"))+"/"+e.type,e}function qt(e){var t=Et.exec(e.type);return t?e.type=t[1]:e.removeAttribute("type"),e}function _t(e,t){var n,r=0;for(;null!=(n=e[r]);r++)x._data(n,"globalEval",!t||x._data(t[r],"globalEval"))}function Mt(e,t){if(1===t.nodeType&&x.hasData(e)){var n,r,i,o=x._data(e),a=x._data(t,o),s=o.events;if(s){delete a.handle,a.events={};for(n in s)for(r=0,i=s[n].length;i>r;r++)x.event.add(t,n,s[n][r])}a.data&&(a.data=x.extend({},a.data))}}function Ot(e,t){var n,r,i;if(1===t.nodeType){if(n=t.nodeName.toLowerCase(),!x.support.noCloneEvent&&t[x.expando]){i=x._data(t);for(r in i.events)x.removeEvent(t,r,i.handle);t.removeAttribute(x.expando)}"script"===n&&t.text!==e.text?(Ht(t).text=e.text,qt(t)):"object"===n?(t.parentNode&&(t.outerHTML=e.outerHTML),x.support.html5Clone&&e.innerHTML&&!x.trim(t.innerHTML)&&(t.innerHTML=e.innerHTML)):"input"===n&&Ct.test(e.type)?(t.defaultChecked=t.checked=e.checked,t.value!==e.value&&(t.value=e.value)):"option"===n?t.defaultSelected=t.selected=e.defaultSelected:("input"===n||"textarea"===n)&&(t.defaultValue=e.defaultValue)}}x.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(e,t){x.fn[e]=function(e){var n,r=0,i=[],o=x(e),a=o.length-1;for(;a>=r;r++)n=r===a?this:this.clone(!0),x(o[r])[t](n),h.apply(i,n.get());return this.pushStack(i)}});function Ft(e,n){var r,o,a=0,s=typeof e.getElementsByTagName!==i?e.getElementsByTagName(n||"*"):typeof e.querySelectorAll!==i?e.querySelectorAll(n||"*"):t;if(!s)for(s=[],r=e.childNodes||e;null!=(o=r[a]);a++)!n||x.nodeName(o,n)?s.push(o):x.merge(s,Ft(o,n));return n===t||n&&x.nodeName(e,n)?x.merge([e],s):s}function Bt(e){Ct.test(e.type)&&(e.defaultChecked=e.checked)}x.extend({clone:function(e,t,n){var r,i,o,a,s,l=x.contains(e.ownerDocument,e);if(x.support.html5Clone||x.isXMLDoc(e)||!mt.test("<"+e.nodeName+">")?o=e.cloneNode(!0):(Dt.innerHTML=e.outerHTML,Dt.removeChild(o=Dt.firstChild)),!(x.support.noCloneEvent&&x.support.noCloneChecked||1!==e.nodeType&&11!==e.nodeType||x.isXMLDoc(e)))for(r=Ft(o),s=Ft(e),a=0;null!=(i=s[a]);++a)r[a]&&Ot(i,r[a]);if(t)if(n)for(s=s||Ft(e),r=r||Ft(o),a=0;null!=(i=s[a]);a++)Mt(i,r[a]);else Mt(e,o);return r=Ft(o,"script"),r.length>0&&_t(r,!l&&Ft(e,"script")),r=s=i=null,o},buildFragment:function(e,t,n,r){var i,o,a,s,l,u,c,p=e.length,f=dt(t),d=[],h=0;for(;p>h;h++)if(o=e[h],o||0===o)if("object"===x.type(o))x.merge(d,o.nodeType?[o]:o);else if(wt.test(o)){s=s||f.appendChild(t.createElement("div")),l=(bt.exec(o)||["",""])[1].toLowerCase(),c=At[l]||At._default,s.innerHTML=c[1]+o.replace(vt,"<$1></$2>")+c[2],i=c[0];while(i--)s=s.lastChild;if(!x.support.leadingWhitespace&&yt.test(o)&&d.push(t.createTextNode(yt.exec(o)[0])),!x.support.tbody){o="table"!==l||xt.test(o)?"<table>"!==c[1]||xt.test(o)?0:s:s.firstChild,i=o&&o.childNodes.length;while(i--)x.nodeName(u=o.childNodes[i],"tbody")&&!u.childNodes.length&&o.removeChild(u)}x.merge(d,s.childNodes),s.textContent="";while(s.firstChild)s.removeChild(s.firstChild);s=f.lastChild}else d.push(t.createTextNode(o));s&&f.removeChild(s),x.support.appendChecked||x.grep(Ft(d,"input"),Bt),h=0;while(o=d[h++])if((!r||-1===x.inArray(o,r))&&(a=x.contains(o.ownerDocument,o),s=Ft(f.appendChild(o),"script"),a&&_t(s),n)){i=0;while(o=s[i++])kt.test(o.type||"")&&n.push(o)}return s=null,f},cleanData:function(e,t){var n,r,o,a,s=0,l=x.expando,u=x.cache,c=x.support.deleteExpando,f=x.event.special;for(;null!=(n=e[s]);s++)if((t||x.acceptData(n))&&(o=n[l],a=o&&u[o])){if(a.events)for(r in a.events)f[r]?x.event.remove(n,r):x.removeEvent(n,r,a.handle);
u[o]&&(delete u[o],c?delete n[l]:typeof n.removeAttribute!==i?n.removeAttribute(l):n[l]=null,p.push(o))}},_evalUrl:function(e){return x.ajax({url:e,type:"GET",dataType:"script",async:!1,global:!1,"throws":!0})}}),x.fn.extend({wrapAll:function(e){if(x.isFunction(e))return this.each(function(t){x(this).wrapAll(e.call(this,t))});if(this[0]){var t=x(e,this[0].ownerDocument).eq(0).clone(!0);this[0].parentNode&&t.insertBefore(this[0]),t.map(function(){var e=this;while(e.firstChild&&1===e.firstChild.nodeType)e=e.firstChild;return e}).append(this)}return this},wrapInner:function(e){return x.isFunction(e)?this.each(function(t){x(this).wrapInner(e.call(this,t))}):this.each(function(){var t=x(this),n=t.contents();n.length?n.wrapAll(e):t.append(e)})},wrap:function(e){var t=x.isFunction(e);return this.each(function(n){x(this).wrapAll(t?e.call(this,n):e)})},unwrap:function(){return this.parent().each(function(){x.nodeName(this,"body")||x(this).replaceWith(this.childNodes)}).end()}});var Pt,Rt,Wt,$t=/alpha\([^)]*\)/i,It=/opacity\s*=\s*([^)]*)/,zt=/^(top|right|bottom|left)$/,Xt=/^(none|table(?!-c[ea]).+)/,Ut=/^margin/,Vt=RegExp("^("+w+")(.*)$","i"),Yt=RegExp("^("+w+")(?!px)[a-z%]+$","i"),Jt=RegExp("^([+-])=("+w+")","i"),Gt={BODY:"block"},Qt={position:"absolute",visibility:"hidden",display:"block"},Kt={letterSpacing:0,fontWeight:400},Zt=["Top","Right","Bottom","Left"],en=["Webkit","O","Moz","ms"];function tn(e,t){if(t in e)return t;var n=t.charAt(0).toUpperCase()+t.slice(1),r=t,i=en.length;while(i--)if(t=en[i]+n,t in e)return t;return r}function nn(e,t){return e=t||e,"none"===x.css(e,"display")||!x.contains(e.ownerDocument,e)}function rn(e,t){var n,r,i,o=[],a=0,s=e.length;for(;s>a;a++)r=e[a],r.style&&(o[a]=x._data(r,"olddisplay"),n=r.style.display,t?(o[a]||"none"!==n||(r.style.display=""),""===r.style.display&&nn(r)&&(o[a]=x._data(r,"olddisplay",ln(r.nodeName)))):o[a]||(i=nn(r),(n&&"none"!==n||!i)&&x._data(r,"olddisplay",i?n:x.css(r,"display"))));for(a=0;s>a;a++)r=e[a],r.style&&(t&&"none"!==r.style.display&&""!==r.style.display||(r.style.display=t?o[a]||"":"none"));return e}x.fn.extend({css:function(e,n){return x.access(this,function(e,n,r){var i,o,a={},s=0;if(x.isArray(n)){for(o=Rt(e),i=n.length;i>s;s++)a[n[s]]=x.css(e,n[s],!1,o);return a}return r!==t?x.style(e,n,r):x.css(e,n)},e,n,arguments.length>1)},show:function(){return rn(this,!0)},hide:function(){return rn(this)},toggle:function(e){return"boolean"==typeof e?e?this.show():this.hide():this.each(function(){nn(this)?x(this).show():x(this).hide()})}}),x.extend({cssHooks:{opacity:{get:function(e,t){if(t){var n=Wt(e,"opacity");return""===n?"1":n}}}},cssNumber:{columnCount:!0,fillOpacity:!0,fontWeight:!0,lineHeight:!0,opacity:!0,order:!0,orphans:!0,widows:!0,zIndex:!0,zoom:!0},cssProps:{"float":x.support.cssFloat?"cssFloat":"styleFloat"},style:function(e,n,r,i){if(e&&3!==e.nodeType&&8!==e.nodeType&&e.style){var o,a,s,l=x.camelCase(n),u=e.style;if(n=x.cssProps[l]||(x.cssProps[l]=tn(u,l)),s=x.cssHooks[n]||x.cssHooks[l],r===t)return s&&"get"in s&&(o=s.get(e,!1,i))!==t?o:u[n];if(a=typeof r,"string"===a&&(o=Jt.exec(r))&&(r=(o[1]+1)*o[2]+parseFloat(x.css(e,n)),a="number"),!(null==r||"number"===a&&isNaN(r)||("number"!==a||x.cssNumber[l]||(r+="px"),x.support.clearCloneStyle||""!==r||0!==n.indexOf("background")||(u[n]="inherit"),s&&"set"in s&&(r=s.set(e,r,i))===t)))try{u[n]=r}catch(c){}}},css:function(e,n,r,i){var o,a,s,l=x.camelCase(n);return n=x.cssProps[l]||(x.cssProps[l]=tn(e.style,l)),s=x.cssHooks[n]||x.cssHooks[l],s&&"get"in s&&(a=s.get(e,!0,r)),a===t&&(a=Wt(e,n,i)),"normal"===a&&n in Kt&&(a=Kt[n]),""===r||r?(o=parseFloat(a),r===!0||x.isNumeric(o)?o||0:a):a}}),e.getComputedStyle?(Rt=function(t){return e.getComputedStyle(t,null)},Wt=function(e,n,r){var i,o,a,s=r||Rt(e),l=s?s.getPropertyValue(n)||s[n]:t,u=e.style;return s&&(""!==l||x.contains(e.ownerDocument,e)||(l=x.style(e,n)),Yt.test(l)&&Ut.test(n)&&(i=u.width,o=u.minWidth,a=u.maxWidth,u.minWidth=u.maxWidth=u.width=l,l=s.width,u.width=i,u.minWidth=o,u.maxWidth=a)),l}):a.documentElement.currentStyle&&(Rt=function(e){return e.currentStyle},Wt=function(e,n,r){var i,o,a,s=r||Rt(e),l=s?s[n]:t,u=e.style;return null==l&&u&&u[n]&&(l=u[n]),Yt.test(l)&&!zt.test(n)&&(i=u.left,o=e.runtimeStyle,a=o&&o.left,a&&(o.left=e.currentStyle.left),u.left="fontSize"===n?"1em":l,l=u.pixelLeft+"px",u.left=i,a&&(o.left=a)),""===l?"auto":l});function on(e,t,n){var r=Vt.exec(t);return r?Math.max(0,r[1]-(n||0))+(r[2]||"px"):t}function an(e,t,n,r,i){var o=n===(r?"border":"content")?4:"width"===t?1:0,a=0;for(;4>o;o+=2)"margin"===n&&(a+=x.css(e,n+Zt[o],!0,i)),r?("content"===n&&(a-=x.css(e,"padding"+Zt[o],!0,i)),"margin"!==n&&(a-=x.css(e,"border"+Zt[o]+"Width",!0,i))):(a+=x.css(e,"padding"+Zt[o],!0,i),"padding"!==n&&(a+=x.css(e,"border"+Zt[o]+"Width",!0,i)));return a}function sn(e,t,n){var r=!0,i="width"===t?e.offsetWidth:e.offsetHeight,o=Rt(e),a=x.support.boxSizing&&"border-box"===x.css(e,"boxSizing",!1,o);if(0>=i||null==i){if(i=Wt(e,t,o),(0>i||null==i)&&(i=e.style[t]),Yt.test(i))return i;r=a&&(x.support.boxSizingReliable||i===e.style[t]),i=parseFloat(i)||0}return i+an(e,t,n||(a?"border":"content"),r,o)+"px"}function ln(e){var t=a,n=Gt[e];return n||(n=un(e,t),"none"!==n&&n||(Pt=(Pt||x("<iframe frameborder='0' width='0' height='0'/>").css("cssText","display:block !important")).appendTo(t.documentElement),t=(Pt[0].contentWindow||Pt[0].contentDocument).document,t.write("<!doctype html><html><body>"),t.close(),n=un(e,t),Pt.detach()),Gt[e]=n),n}function un(e,t){var n=x(t.createElement(e)).appendTo(t.body),r=x.css(n[0],"display");return n.remove(),r}x.each(["height","width"],function(e,n){x.cssHooks[n]={get:function(e,r,i){return r?0===e.offsetWidth&&Xt.test(x.css(e,"display"))?x.swap(e,Qt,function(){return sn(e,n,i)}):sn(e,n,i):t},set:function(e,t,r){var i=r&&Rt(e);return on(e,t,r?an(e,n,r,x.support.boxSizing&&"border-box"===x.css(e,"boxSizing",!1,i),i):0)}}}),x.support.opacity||(x.cssHooks.opacity={get:function(e,t){return It.test((t&&e.currentStyle?e.currentStyle.filter:e.style.filter)||"")?.01*parseFloat(RegExp.$1)+"":t?"1":""},set:function(e,t){var n=e.style,r=e.currentStyle,i=x.isNumeric(t)?"alpha(opacity="+100*t+")":"",o=r&&r.filter||n.filter||"";n.zoom=1,(t>=1||""===t)&&""===x.trim(o.replace($t,""))&&n.removeAttribute&&(n.removeAttribute("filter"),""===t||r&&!r.filter)||(n.filter=$t.test(o)?o.replace($t,i):o+" "+i)}}),x(function(){x.support.reliableMarginRight||(x.cssHooks.marginRight={get:function(e,n){return n?x.swap(e,{display:"inline-block"},Wt,[e,"marginRight"]):t}}),!x.support.pixelPosition&&x.fn.position&&x.each(["top","left"],function(e,n){x.cssHooks[n]={get:function(e,r){return r?(r=Wt(e,n),Yt.test(r)?x(e).position()[n]+"px":r):t}}})}),x.expr&&x.expr.filters&&(x.expr.filters.hidden=function(e){return 0>=e.offsetWidth&&0>=e.offsetHeight||!x.support.reliableHiddenOffsets&&"none"===(e.style&&e.style.display||x.css(e,"display"))},x.expr.filters.visible=function(e){return!x.expr.filters.hidden(e)}),x.each({margin:"",padding:"",border:"Width"},function(e,t){x.cssHooks[e+t]={expand:function(n){var r=0,i={},o="string"==typeof n?n.split(" "):[n];for(;4>r;r++)i[e+Zt[r]+t]=o[r]||o[r-2]||o[0];return i}},Ut.test(e)||(x.cssHooks[e+t].set=on)});var cn=/%20/g,pn=/\[\]$/,fn=/\r?\n/g,dn=/^(?:submit|button|image|reset|file)$/i,hn=/^(?:input|select|textarea|keygen)/i;x.fn.extend({serialize:function(){return x.param(this.serializeArray())},serializeArray:function(){return this.map(function(){var e=x.prop(this,"elements");return e?x.makeArray(e):this}).filter(function(){var e=this.type;return this.name&&!x(this).is(":disabled")&&hn.test(this.nodeName)&&!dn.test(e)&&(this.checked||!Ct.test(e))}).map(function(e,t){var n=x(this).val();return null==n?null:x.isArray(n)?x.map(n,function(e){return{name:t.name,value:e.replace(fn,"\r\n")}}):{name:t.name,value:n.replace(fn,"\r\n")}}).get()}}),x.param=function(e,n){var r,i=[],o=function(e,t){t=x.isFunction(t)?t():null==t?"":t,i[i.length]=encodeURIComponent(e)+"="+encodeURIComponent(t)};if(n===t&&(n=x.ajaxSettings&&x.ajaxSettings.traditional),x.isArray(e)||e.jquery&&!x.isPlainObject(e))x.each(e,function(){o(this.name,this.value)});else for(r in e)gn(r,e[r],n,o);return i.join("&").replace(cn,"+")};function gn(e,t,n,r){var i;if(x.isArray(t))x.each(t,function(t,i){n||pn.test(e)?r(e,i):gn(e+"["+("object"==typeof i?t:"")+"]",i,n,r)});else if(n||"object"!==x.type(t))r(e,t);else for(i in t)gn(e+"["+i+"]",t[i],n,r)}x.each("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error contextmenu".split(" "),function(e,t){x.fn[t]=function(e,n){return arguments.length>0?this.on(t,null,e,n):this.trigger(t)}}),x.fn.extend({hover:function(e,t){return this.mouseenter(e).mouseleave(t||e)},bind:function(e,t,n){return this.on(e,null,t,n)},unbind:function(e,t){return this.off(e,null,t)},delegate:function(e,t,n,r){return this.on(t,e,n,r)},undelegate:function(e,t,n){return 1===arguments.length?this.off(e,"**"):this.off(t,e||"**",n)}});var mn,yn,vn=x.now(),bn=/\?/,xn=/#.*$/,wn=/([?&])_=[^&]*/,Tn=/^(.*?):[ \t]*([^\r\n]*)\r?$/gm,Cn=/^(?:about|app|app-storage|.+-extension|file|res|widget):$/,Nn=/^(?:GET|HEAD)$/,kn=/^\/\//,En=/^([\w.+-]+:)(?:\/\/([^\/?#:]*)(?::(\d+)|)|)/,Sn=x.fn.load,An={},jn={},Dn="*/".concat("*");try{yn=o.href}catch(Ln){yn=a.createElement("a"),yn.href="",yn=yn.href}mn=En.exec(yn.toLowerCase())||[];function Hn(e){return function(t,n){"string"!=typeof t&&(n=t,t="*");var r,i=0,o=t.toLowerCase().match(T)||[];if(x.isFunction(n))while(r=o[i++])"+"===r[0]?(r=r.slice(1)||"*",(e[r]=e[r]||[]).unshift(n)):(e[r]=e[r]||[]).push(n)}}function qn(e,n,r,i){var o={},a=e===jn;function s(l){var u;return o[l]=!0,x.each(e[l]||[],function(e,l){var c=l(n,r,i);return"string"!=typeof c||a||o[c]?a?!(u=c):t:(n.dataTypes.unshift(c),s(c),!1)}),u}return s(n.dataTypes[0])||!o["*"]&&s("*")}function _n(e,n){var r,i,o=x.ajaxSettings.flatOptions||{};for(i in n)n[i]!==t&&((o[i]?e:r||(r={}))[i]=n[i]);return r&&x.extend(!0,e,r),e}x.fn.load=function(e,n,r){if("string"!=typeof e&&Sn)return Sn.apply(this,arguments);var i,o,a,s=this,l=e.indexOf(" ");return l>=0&&(i=e.slice(l,e.length),e=e.slice(0,l)),x.isFunction(n)?(r=n,n=t):n&&"object"==typeof n&&(a="POST"),s.length>0&&x.ajax({url:e,type:a,dataType:"html",data:n}).done(function(e){o=arguments,s.html(i?x("<div>").append(x.parseHTML(e)).find(i):e)}).complete(r&&function(e,t){s.each(r,o||[e.responseText,t,e])}),this},x.each(["ajaxStart","ajaxStop","ajaxComplete","ajaxError","ajaxSuccess","ajaxSend"],function(e,t){x.fn[t]=function(e){return this.on(t,e)}}),x.extend({active:0,lastModified:{},etag:{},ajaxSettings:{url:yn,type:"GET",isLocal:Cn.test(mn[1]),global:!0,processData:!0,async:!0,contentType:"application/x-www-form-urlencoded; charset=UTF-8",accepts:{"*":Dn,text:"text/plain",html:"text/html",xml:"application/xml, text/xml",json:"application/json, text/javascript"},contents:{xml:/xml/,html:/html/,json:/json/},responseFields:{xml:"responseXML",text:"responseText",json:"responseJSON"},converters:{"* text":String,"text html":!0,"text json":x.parseJSON,"text xml":x.parseXML},flatOptions:{url:!0,context:!0}},ajaxSetup:function(e,t){return t?_n(_n(e,x.ajaxSettings),t):_n(x.ajaxSettings,e)},ajaxPrefilter:Hn(An),ajaxTransport:Hn(jn),ajax:function(e,n){"object"==typeof e&&(n=e,e=t),n=n||{};var r,i,o,a,s,l,u,c,p=x.ajaxSetup({},n),f=p.context||p,d=p.context&&(f.nodeType||f.jquery)?x(f):x.event,h=x.Deferred(),g=x.Callbacks("once memory"),m=p.statusCode||{},y={},v={},b=0,w="canceled",C={readyState:0,getResponseHeader:function(e){var t;if(2===b){if(!c){c={};while(t=Tn.exec(a))c[t[1].toLowerCase()]=t[2]}t=c[e.toLowerCase()]}return null==t?null:t},getAllResponseHeaders:function(){return 2===b?a:null},setRequestHeader:function(e,t){var n=e.toLowerCase();return b||(e=v[n]=v[n]||e,y[e]=t),this},overrideMimeType:function(e){return b||(p.mimeType=e),this},statusCode:function(e){var t;if(e)if(2>b)for(t in e)m[t]=[m[t],e[t]];else C.always(e[C.status]);return this},abort:function(e){var t=e||w;return u&&u.abort(t),k(0,t),this}};if(h.promise(C).complete=g.add,C.success=C.done,C.error=C.fail,p.url=((e||p.url||yn)+"").replace(xn,"").replace(kn,mn[1]+"//"),p.type=n.method||n.type||p.method||p.type,p.dataTypes=x.trim(p.dataType||"*").toLowerCase().match(T)||[""],null==p.crossDomain&&(r=En.exec(p.url.toLowerCase()),p.crossDomain=!(!r||r[1]===mn[1]&&r[2]===mn[2]&&(r[3]||("http:"===r[1]?"80":"443"))===(mn[3]||("http:"===mn[1]?"80":"443")))),p.data&&p.processData&&"string"!=typeof p.data&&(p.data=x.param(p.data,p.traditional)),qn(An,p,n,C),2===b)return C;l=p.global,l&&0===x.active++&&x.event.trigger("ajaxStart"),p.type=p.type.toUpperCase(),p.hasContent=!Nn.test(p.type),o=p.url,p.hasContent||(p.data&&(o=p.url+=(bn.test(o)?"&":"?")+p.data,delete p.data),p.cache===!1&&(p.url=wn.test(o)?o.replace(wn,"$1_="+vn++):o+(bn.test(o)?"&":"?")+"_="+vn++)),p.ifModified&&(x.lastModified[o]&&C.setRequestHeader("If-Modified-Since",x.lastModified[o]),x.etag[o]&&C.setRequestHeader("If-None-Match",x.etag[o])),(p.data&&p.hasContent&&p.contentType!==!1||n.contentType)&&C.setRequestHeader("Content-Type",p.contentType),C.setRequestHeader("Accept",p.dataTypes[0]&&p.accepts[p.dataTypes[0]]?p.accepts[p.dataTypes[0]]+("*"!==p.dataTypes[0]?", "+Dn+"; q=0.01":""):p.accepts["*"]);for(i in p.headers)C.setRequestHeader(i,p.headers[i]);if(p.beforeSend&&(p.beforeSend.call(f,C,p)===!1||2===b))return C.abort();w="abort";for(i in{success:1,error:1,complete:1})C[i](p[i]);if(u=qn(jn,p,n,C)){C.readyState=1,l&&d.trigger("ajaxSend",[C,p]),p.async&&p.timeout>0&&(s=setTimeout(function(){C.abort("timeout")},p.timeout));try{b=1,u.send(y,k)}catch(N){if(!(2>b))throw N;k(-1,N)}}else k(-1,"No Transport");function k(e,n,r,i){var c,y,v,w,T,N=n;2!==b&&(b=2,s&&clearTimeout(s),u=t,a=i||"",C.readyState=e>0?4:0,c=e>=200&&300>e||304===e,r&&(w=Mn(p,C,r)),w=On(p,w,C,c),c?(p.ifModified&&(T=C.getResponseHeader("Last-Modified"),T&&(x.lastModified[o]=T),T=C.getResponseHeader("etag"),T&&(x.etag[o]=T)),204===e||"HEAD"===p.type?N="nocontent":304===e?N="notmodified":(N=w.state,y=w.data,v=w.error,c=!v)):(v=N,(e||!N)&&(N="error",0>e&&(e=0))),C.status=e,C.statusText=(n||N)+"",c?h.resolveWith(f,[y,N,C]):h.rejectWith(f,[C,N,v]),C.statusCode(m),m=t,l&&d.trigger(c?"ajaxSuccess":"ajaxError",[C,p,c?y:v]),g.fireWith(f,[C,N]),l&&(d.trigger("ajaxComplete",[C,p]),--x.active||x.event.trigger("ajaxStop")))}return C},getJSON:function(e,t,n){return x.get(e,t,n,"json")},getScript:function(e,n){return x.get(e,t,n,"script")}}),x.each(["get","post"],function(e,n){x[n]=function(e,r,i,o){return x.isFunction(r)&&(o=o||i,i=r,r=t),x.ajax({url:e,type:n,dataType:o,data:r,success:i})}});function Mn(e,n,r){var i,o,a,s,l=e.contents,u=e.dataTypes;while("*"===u[0])u.shift(),o===t&&(o=e.mimeType||n.getResponseHeader("Content-Type"));if(o)for(s in l)if(l[s]&&l[s].test(o)){u.unshift(s);break}if(u[0]in r)a=u[0];else{for(s in r){if(!u[0]||e.converters[s+" "+u[0]]){a=s;break}i||(i=s)}a=a||i}return a?(a!==u[0]&&u.unshift(a),r[a]):t}function On(e,t,n,r){var i,o,a,s,l,u={},c=e.dataTypes.slice();if(c[1])for(a in e.converters)u[a.toLowerCase()]=e.converters[a];o=c.shift();while(o)if(e.responseFields[o]&&(n[e.responseFields[o]]=t),!l&&r&&e.dataFilter&&(t=e.dataFilter(t,e.dataType)),l=o,o=c.shift())if("*"===o)o=l;else if("*"!==l&&l!==o){if(a=u[l+" "+o]||u["* "+o],!a)for(i in u)if(s=i.split(" "),s[1]===o&&(a=u[l+" "+s[0]]||u["* "+s[0]])){a===!0?a=u[i]:u[i]!==!0&&(o=s[0],c.unshift(s[1]));break}if(a!==!0)if(a&&e["throws"])t=a(t);else try{t=a(t)}catch(p){return{state:"parsererror",error:a?p:"No conversion from "+l+" to "+o}}}return{state:"success",data:t}}x.ajaxSetup({accepts:{script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},contents:{script:/(?:java|ecma)script/},converters:{"text script":function(e){return x.globalEval(e),e}}}),x.ajaxPrefilter("script",function(e){e.cache===t&&(e.cache=!1),e.crossDomain&&(e.type="GET",e.global=!1)}),x.ajaxTransport("script",function(e){if(e.crossDomain){var n,r=a.head||x("head")[0]||a.documentElement;return{send:function(t,i){n=a.createElement("script"),n.async=!0,e.scriptCharset&&(n.charset=e.scriptCharset),n.src=e.url,n.onload=n.onreadystatechange=function(e,t){(t||!n.readyState||/loaded|complete/.test(n.readyState))&&(n.onload=n.onreadystatechange=null,n.parentNode&&n.parentNode.removeChild(n),n=null,t||i(200,"success"))},r.insertBefore(n,r.firstChild)},abort:function(){n&&n.onload(t,!0)}}}});var Fn=[],Bn=/(=)\?(?=&|$)|\?\?/;x.ajaxSetup({jsonp:"callback",jsonpCallback:function(){var e=Fn.pop()||x.expando+"_"+vn++;return this[e]=!0,e}}),x.ajaxPrefilter("json jsonp",function(n,r,i){var o,a,s,l=n.jsonp!==!1&&(Bn.test(n.url)?"url":"string"==typeof n.data&&!(n.contentType||"").indexOf("application/x-www-form-urlencoded")&&Bn.test(n.data)&&"data");return l||"jsonp"===n.dataTypes[0]?(o=n.jsonpCallback=x.isFunction(n.jsonpCallback)?n.jsonpCallback():n.jsonpCallback,l?n[l]=n[l].replace(Bn,"$1"+o):n.jsonp!==!1&&(n.url+=(bn.test(n.url)?"&":"?")+n.jsonp+"="+o),n.converters["script json"]=function(){return s||x.error(o+" was not called"),s[0]},n.dataTypes[0]="json",a=e[o],e[o]=function(){s=arguments},i.always(function(){e[o]=a,n[o]&&(n.jsonpCallback=r.jsonpCallback,Fn.push(o)),s&&x.isFunction(a)&&a(s[0]),s=a=t}),"script"):t});var Pn,Rn,Wn=0,$n=e.ActiveXObject&&function(){var e;for(e in Pn)Pn[e](t,!0)};function In(){try{return new e.XMLHttpRequest}catch(t){}}function zn(){try{return new e.ActiveXObject("Microsoft.XMLHTTP")}catch(t){}}x.ajaxSettings.xhr=e.ActiveXObject?function(){return!this.isLocal&&In()||zn()}:In,Rn=x.ajaxSettings.xhr(),x.support.cors=!!Rn&&"withCredentials"in Rn,Rn=x.support.ajax=!!Rn,Rn&&x.ajaxTransport(function(n){if(!n.crossDomain||x.support.cors){var r;return{send:function(i,o){var a,s,l=n.xhr();if(n.username?l.open(n.type,n.url,n.async,n.username,n.password):l.open(n.type,n.url,n.async),n.xhrFields)for(s in n.xhrFields)l[s]=n.xhrFields[s];n.mimeType&&l.overrideMimeType&&l.overrideMimeType(n.mimeType),n.crossDomain||i["X-Requested-With"]||(i["X-Requested-With"]="XMLHttpRequest");try{for(s in i)l.setRequestHeader(s,i[s])}catch(u){}l.send(n.hasContent&&n.data||null),r=function(e,i){var s,u,c,p;try{if(r&&(i||4===l.readyState))if(r=t,a&&(l.onreadystatechange=x.noop,$n&&delete Pn[a]),i)4!==l.readyState&&l.abort();else{p={},s=l.status,u=l.getAllResponseHeaders(),"string"==typeof l.responseText&&(p.text=l.responseText);try{c=l.statusText}catch(f){c=""}s||!n.isLocal||n.crossDomain?1223===s&&(s=204):s=p.text?200:404}}catch(d){i||o(-1,d)}p&&o(s,c,p,u)},n.async?4===l.readyState?setTimeout(r):(a=++Wn,$n&&(Pn||(Pn={},x(e).unload($n)),Pn[a]=r),l.onreadystatechange=r):r()},abort:function(){r&&r(t,!0)}}}});var Xn,Un,Vn=/^(?:toggle|show|hide)$/,Yn=RegExp("^(?:([+-])=|)("+w+")([a-z%]*)$","i"),Jn=/queueHooks$/,Gn=[nr],Qn={"*":[function(e,t){var n=this.createTween(e,t),r=n.cur(),i=Yn.exec(t),o=i&&i[3]||(x.cssNumber[e]?"":"px"),a=(x.cssNumber[e]||"px"!==o&&+r)&&Yn.exec(x.css(n.elem,e)),s=1,l=20;if(a&&a[3]!==o){o=o||a[3],i=i||[],a=+r||1;do s=s||".5",a/=s,x.style(n.elem,e,a+o);while(s!==(s=n.cur()/r)&&1!==s&&--l)}return i&&(a=n.start=+a||+r||0,n.unit=o,n.end=i[1]?a+(i[1]+1)*i[2]:+i[2]),n}]};function Kn(){return setTimeout(function(){Xn=t}),Xn=x.now()}function Zn(e,t,n){var r,i=(Qn[t]||[]).concat(Qn["*"]),o=0,a=i.length;for(;a>o;o++)if(r=i[o].call(n,t,e))return r}function er(e,t,n){var r,i,o=0,a=Gn.length,s=x.Deferred().always(function(){delete l.elem}),l=function(){if(i)return!1;var t=Xn||Kn(),n=Math.max(0,u.startTime+u.duration-t),r=n/u.duration||0,o=1-r,a=0,l=u.tweens.length;for(;l>a;a++)u.tweens[a].run(o);return s.notifyWith(e,[u,o,n]),1>o&&l?n:(s.resolveWith(e,[u]),!1)},u=s.promise({elem:e,props:x.extend({},t),opts:x.extend(!0,{specialEasing:{}},n),originalProperties:t,originalOptions:n,startTime:Xn||Kn(),duration:n.duration,tweens:[],createTween:function(t,n){var r=x.Tween(e,u.opts,t,n,u.opts.specialEasing[t]||u.opts.easing);return u.tweens.push(r),r},stop:function(t){var n=0,r=t?u.tweens.length:0;if(i)return this;for(i=!0;r>n;n++)u.tweens[n].run(1);return t?s.resolveWith(e,[u,t]):s.rejectWith(e,[u,t]),this}}),c=u.props;for(tr(c,u.opts.specialEasing);a>o;o++)if(r=Gn[o].call(u,e,c,u.opts))return r;return x.map(c,Zn,u),x.isFunction(u.opts.start)&&u.opts.start.call(e,u),x.fx.timer(x.extend(l,{elem:e,anim:u,queue:u.opts.queue})),u.progress(u.opts.progress).done(u.opts.done,u.opts.complete).fail(u.opts.fail).always(u.opts.always)}function tr(e,t){var n,r,i,o,a;for(n in e)if(r=x.camelCase(n),i=t[r],o=e[n],x.isArray(o)&&(i=o[1],o=e[n]=o[0]),n!==r&&(e[r]=o,delete e[n]),a=x.cssHooks[r],a&&"expand"in a){o=a.expand(o),delete e[r];for(n in o)n in e||(e[n]=o[n],t[n]=i)}else t[r]=i}x.Animation=x.extend(er,{tweener:function(e,t){x.isFunction(e)?(t=e,e=["*"]):e=e.split(" ");var n,r=0,i=e.length;for(;i>r;r++)n=e[r],Qn[n]=Qn[n]||[],Qn[n].unshift(t)},prefilter:function(e,t){t?Gn.unshift(e):Gn.push(e)}});function nr(e,t,n){var r,i,o,a,s,l,u=this,c={},p=e.style,f=e.nodeType&&nn(e),d=x._data(e,"fxshow");n.queue||(s=x._queueHooks(e,"fx"),null==s.unqueued&&(s.unqueued=0,l=s.empty.fire,s.empty.fire=function(){s.unqueued||l()}),s.unqueued++,u.always(function(){u.always(function(){s.unqueued--,x.queue(e,"fx").length||s.empty.fire()})})),1===e.nodeType&&("height"in t||"width"in t)&&(n.overflow=[p.overflow,p.overflowX,p.overflowY],"inline"===x.css(e,"display")&&"none"===x.css(e,"float")&&(x.support.inlineBlockNeedsLayout&&"inline"!==ln(e.nodeName)?p.zoom=1:p.display="inline-block")),n.overflow&&(p.overflow="hidden",x.support.shrinkWrapBlocks||u.always(function(){p.overflow=n.overflow[0],p.overflowX=n.overflow[1],p.overflowY=n.overflow[2]}));for(r in t)if(i=t[r],Vn.exec(i)){if(delete t[r],o=o||"toggle"===i,i===(f?"hide":"show"))continue;c[r]=d&&d[r]||x.style(e,r)}if(!x.isEmptyObject(c)){d?"hidden"in d&&(f=d.hidden):d=x._data(e,"fxshow",{}),o&&(d.hidden=!f),f?x(e).show():u.done(function(){x(e).hide()}),u.done(function(){var t;x._removeData(e,"fxshow");for(t in c)x.style(e,t,c[t])});for(r in c)a=Zn(f?d[r]:0,r,u),r in d||(d[r]=a.start,f&&(a.end=a.start,a.start="width"===r||"height"===r?1:0))}}function rr(e,t,n,r,i){return new rr.prototype.init(e,t,n,r,i)}x.Tween=rr,rr.prototype={constructor:rr,init:function(e,t,n,r,i,o){this.elem=e,this.prop=n,this.easing=i||"swing",this.options=t,this.start=this.now=this.cur(),this.end=r,this.unit=o||(x.cssNumber[n]?"":"px")},cur:function(){var e=rr.propHooks[this.prop];return e&&e.get?e.get(this):rr.propHooks._default.get(this)},run:function(e){var t,n=rr.propHooks[this.prop];return this.pos=t=this.options.duration?x.easing[this.easing](e,this.options.duration*e,0,1,this.options.duration):e,this.now=(this.end-this.start)*t+this.start,this.options.step&&this.options.step.call(this.elem,this.now,this),n&&n.set?n.set(this):rr.propHooks._default.set(this),this}},rr.prototype.init.prototype=rr.prototype,rr.propHooks={_default:{get:function(e){var t;return null==e.elem[e.prop]||e.elem.style&&null!=e.elem.style[e.prop]?(t=x.css(e.elem,e.prop,""),t&&"auto"!==t?t:0):e.elem[e.prop]},set:function(e){x.fx.step[e.prop]?x.fx.step[e.prop](e):e.elem.style&&(null!=e.elem.style[x.cssProps[e.prop]]||x.cssHooks[e.prop])?x.style(e.elem,e.prop,e.now+e.unit):e.elem[e.prop]=e.now}}},rr.propHooks.scrollTop=rr.propHooks.scrollLeft={set:function(e){e.elem.nodeType&&e.elem.parentNode&&(e.elem[e.prop]=e.now)}},x.each(["toggle","show","hide"],function(e,t){var n=x.fn[t];x.fn[t]=function(e,r,i){return null==e||"boolean"==typeof e?n.apply(this,arguments):this.animate(ir(t,!0),e,r,i)}}),x.fn.extend({fadeTo:function(e,t,n,r){return this.filter(nn).css("opacity",0).show().end().animate({opacity:t},e,n,r)},animate:function(e,t,n,r){var i=x.isEmptyObject(e),o=x.speed(t,n,r),a=function(){var t=er(this,x.extend({},e),o);(i||x._data(this,"finish"))&&t.stop(!0)};return a.finish=a,i||o.queue===!1?this.each(a):this.queue(o.queue,a)},stop:function(e,n,r){var i=function(e){var t=e.stop;delete e.stop,t(r)};return"string"!=typeof e&&(r=n,n=e,e=t),n&&e!==!1&&this.queue(e||"fx",[]),this.each(function(){var t=!0,n=null!=e&&e+"queueHooks",o=x.timers,a=x._data(this);if(n)a[n]&&a[n].stop&&i(a[n]);else for(n in a)a[n]&&a[n].stop&&Jn.test(n)&&i(a[n]);for(n=o.length;n--;)o[n].elem!==this||null!=e&&o[n].queue!==e||(o[n].anim.stop(r),t=!1,o.splice(n,1));(t||!r)&&x.dequeue(this,e)})},finish:function(e){return e!==!1&&(e=e||"fx"),this.each(function(){var t,n=x._data(this),r=n[e+"queue"],i=n[e+"queueHooks"],o=x.timers,a=r?r.length:0;for(n.finish=!0,x.queue(this,e,[]),i&&i.stop&&i.stop.call(this,!0),t=o.length;t--;)o[t].elem===this&&o[t].queue===e&&(o[t].anim.stop(!0),o.splice(t,1));for(t=0;a>t;t++)r[t]&&r[t].finish&&r[t].finish.call(this);delete n.finish})}});function ir(e,t){var n,r={height:e},i=0;for(t=t?1:0;4>i;i+=2-t)n=Zt[i],r["margin"+n]=r["padding"+n]=e;return t&&(r.opacity=r.width=e),r}x.each({slideDown:ir("show"),slideUp:ir("hide"),slideToggle:ir("toggle"),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(e,t){x.fn[e]=function(e,n,r){return this.animate(t,e,n,r)}}),x.speed=function(e,t,n){var r=e&&"object"==typeof e?x.extend({},e):{complete:n||!n&&t||x.isFunction(e)&&e,duration:e,easing:n&&t||t&&!x.isFunction(t)&&t};return r.duration=x.fx.off?0:"number"==typeof r.duration?r.duration:r.duration in x.fx.speeds?x.fx.speeds[r.duration]:x.fx.speeds._default,(null==r.queue||r.queue===!0)&&(r.queue="fx"),r.old=r.complete,r.complete=function(){x.isFunction(r.old)&&r.old.call(this),r.queue&&x.dequeue(this,r.queue)},r},x.easing={linear:function(e){return e},swing:function(e){return.5-Math.cos(e*Math.PI)/2}},x.timers=[],x.fx=rr.prototype.init,x.fx.tick=function(){var e,n=x.timers,r=0;for(Xn=x.now();n.length>r;r++)e=n[r],e()||n[r]!==e||n.splice(r--,1);n.length||x.fx.stop(),Xn=t},x.fx.timer=function(e){e()&&x.timers.push(e)&&x.fx.start()},x.fx.interval=13,x.fx.start=function(){Un||(Un=setInterval(x.fx.tick,x.fx.interval))},x.fx.stop=function(){clearInterval(Un),Un=null},x.fx.speeds={slow:600,fast:200,_default:400},x.fx.step={},x.expr&&x.expr.filters&&(x.expr.filters.animated=function(e){return x.grep(x.timers,function(t){return e===t.elem}).length}),x.fn.offset=function(e){if(arguments.length)return e===t?this:this.each(function(t){x.offset.setOffset(this,e,t)});var n,r,o={top:0,left:0},a=this[0],s=a&&a.ownerDocument;if(s)return n=s.documentElement,x.contains(n,a)?(typeof a.getBoundingClientRect!==i&&(o=a.getBoundingClientRect()),r=or(s),{top:o.top+(r.pageYOffset||n.scrollTop)-(n.clientTop||0),left:o.left+(r.pageXOffset||n.scrollLeft)-(n.clientLeft||0)}):o},x.offset={setOffset:function(e,t,n){var r=x.css(e,"position");"static"===r&&(e.style.position="relative");var i=x(e),o=i.offset(),a=x.css(e,"top"),s=x.css(e,"left"),l=("absolute"===r||"fixed"===r)&&x.inArray("auto",[a,s])>-1,u={},c={},p,f;l?(c=i.position(),p=c.top,f=c.left):(p=parseFloat(a)||0,f=parseFloat(s)||0),x.isFunction(t)&&(t=t.call(e,n,o)),null!=t.top&&(u.top=t.top-o.top+p),null!=t.left&&(u.left=t.left-o.left+f),"using"in t?t.using.call(e,u):i.css(u)}},x.fn.extend({position:function(){if(this[0]){var e,t,n={top:0,left:0},r=this[0];return"fixed"===x.css(r,"position")?t=r.getBoundingClientRect():(e=this.offsetParent(),t=this.offset(),x.nodeName(e[0],"html")||(n=e.offset()),n.top+=x.css(e[0],"borderTopWidth",!0),n.left+=x.css(e[0],"borderLeftWidth",!0)),{top:t.top-n.top-x.css(r,"marginTop",!0),left:t.left-n.left-x.css(r,"marginLeft",!0)}}},offsetParent:function(){return this.map(function(){var e=this.offsetParent||s;while(e&&!x.nodeName(e,"html")&&"static"===x.css(e,"position"))e=e.offsetParent;return e||s})}}),x.each({scrollLeft:"pageXOffset",scrollTop:"pageYOffset"},function(e,n){var r=/Y/.test(n);x.fn[e]=function(i){return x.access(this,function(e,i,o){var a=or(e);return o===t?a?n in a?a[n]:a.document.documentElement[i]:e[i]:(a?a.scrollTo(r?x(a).scrollLeft():o,r?o:x(a).scrollTop()):e[i]=o,t)},e,i,arguments.length,null)}});function or(e){return x.isWindow(e)?e:9===e.nodeType?e.defaultView||e.parentWindow:!1}x.each({Height:"height",Width:"width"},function(e,n){x.each({padding:"inner"+e,content:n,"":"outer"+e},function(r,i){x.fn[i]=function(i,o){var a=arguments.length&&(r||"boolean"!=typeof i),s=r||(i===!0||o===!0?"margin":"border");return x.access(this,function(n,r,i){var o;return x.isWindow(n)?n.document.documentElement["client"+e]:9===n.nodeType?(o=n.documentElement,Math.max(n.body["scroll"+e],o["scroll"+e],n.body["offset"+e],o["offset"+e],o["client"+e])):i===t?x.css(n,r,s):x.style(n,r,i,s)},n,a?i:t,a,null)}})}),x.fn.size=function(){return this.length},x.fn.andSelf=x.fn.addBack,"object"==typeof module&&module&&"object"==typeof module.exports?module.exports=x:(e.jQuery=e.$=x,"function"==typeof define&&define.amd&&define("jquery",[],function(){return x}))})(window);
(function($){
	//??????jquery ?????????
	var agent = navigator.userAgent.toLowerCase();
	if(typeof $.browser == "undefined"){
		 $.browser = {};
	}
	$.browser.mozilla = /firefox/.test(agent);
	$.browser.webkit = /webkit/.test(agent);
	$.browser.opera = /opera/.test(agent);
	$.browser.msie = /msie/.test(agent);
})(jQuery);;;(function($){$.fn.bgIframe=$.fn.bgiframe=function(s){if($.browser.msie&&/6.0/.test(navigator.userAgent)){s=$.extend({top:'auto',left:'auto',width:'auto',height:'auto',opacity:true,src:'javascript:false;'},s||{});var prop=function(n){return n&&n.constructor==Number?n+'px':n;},html='<iframe class="bgiframe"frameborder="0"tabindex="-1"src="'+s.src+'"'+'style="display:block;position:absolute;z-index:-1;'+(s.opacity!==false?'filter:Alpha(Opacity=\'0\');':'')+'top:'+(s.top=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderTopWidth)||0)*-1)+\'px\')':prop(s.top))+';'+'left:'+(s.left=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderLeftWidth)||0)*-1)+\'px\')':prop(s.left))+';'+'width:'+(s.width=='auto'?'expression(this.parentNode.offsetWidth+\'px\')':prop(s.width))+';'+'height:'+(s.height=='auto'?'expression(this.parentNode.offsetHeight+\'px\')':prop(s.height))+';'+'"/>';return this.each(function(){if($('> iframe.bgiframe',this).length==0)this.insertBefore(document.createElement('html'),this.firstChild);});}return this;};})(jQuery);

/**
 * weebox.js
 */
;(function($) {

    var weebox = function(content, options) {
        var self = this;
        this._dragging = false;
        this._content = content;
        this._options = options;
        this.dh = null;
        this.mh = null;
        this.dt = null;
        this.dc = null;
        this.bo = null;
        this.bc = null;
        this.selector = null;
        this.ajaxurl = null;
        this.options = null;
        this.defaults = {
            boxid: null,
            boxclass: null,
            type: 'dialog',
            title: '',
            width: 0,
            height: 0,
            timeout: 0,
            draggable: true,
            modal: true,
            focus: null,
            position: 'center',
            overlay: 50,
            showTitle: true,
            showButton: true,
            showCancel: true,
            showOk: true,
            okBtnName: '??????',
            cancelBtnName: '??????',
            contentType: 'text',
            contentChange: false,
            clickClose: false,
            zIndex: 999,
            animate: false,
            trigger: null,
            onclose: null,
            onopen: null,
            onok: null
        };
        this.types = new Array(
            "dialog",
            "error",
            "warning",
            "success",
            "prompt",
            "box"
        );
        this.titles = {
            "error": "!! Error !!",
            "warning": "Warning!",
            "success": "Success",
            "prompt": "Please Choose",
            "dialog": "Dialog",
            "box": ""
        };

        this.initOptions = function() {
            if (typeof(self._options) == "undefined") {
                self._options = {};
            }
            if (typeof(self._options.type) == "undefined") {
                self._options.type = 'dialog';
            }
            if (!$.inArray(self._options.type, self.types)) {
                self._options.type = self.types[0];
            }
            if (typeof(self._options.boxclass) == "undefined") {
                self._options.boxclass = self._options.type + "box";
            }
            if (typeof(self._options.title) == "undefined") {
                self._options.title = self.titles[self._options.type];
            }
            if (content.substr(0, 1) == "#") {
                self._options.contentType = 'selector';
                self.selector = content;
            }
            self.options = $.extend({}, self.defaults, self._options);
        };

        this.initBox = function() {
            var html = '';
            if (self.options.type == 'wee') {
                html = '<div class="weedialog">' +
                    '   <div class="dialog-top">' +
                    '       <div class="dialog-tl"></div>' +
                    '       <div class="dialog-tc"></div>' +
                    '       <div class="dialog-tr"></div>' +
                    '   </div>' +
                    '   <table width="100%" border="0" cellspacing="0" cellpadding="0" >' +
                    '       <tr>' +
                    '           <td class="dialog-cl"></td>' +
                    '           <td>' +
                    '               <div class="dialog-header">' +
                    '                   <div class="dialog-title"></div>' +
                    '                   <div class="dialog-close"></div>' +
                    '               </div>' +
                    '               <div class="dialog-content"></div>' +
                    '               <div class="dialog-button">' +
                    // '                    <input type="button" class="dialog-ok" value="??????">' +
                    // '                    <input type="button" class="dialog-cancel" value="??????">' +

                    '                   <a  class="btn-base dialog-ok"><span>' + self.options.okBtnName + '</span></a>' +
                    '                   <a  class="btn-base dialog-cancel" style="display: inline-block;"><span>' + self.options.cancelBtnName + '</span></a>' +
                    '               </div>' +
                    '           </td>' +
                    '           <td class="dialog-cr"></td>' +
                    '       </tr>' +
                    '   </table>' +
                    '   <div class="dialog-bot">' +
                    '       <div class="dialog-bl"></div>' +
                    '       <div class="dialog-bc"></div>' +
                    '       <div class="dialog-br"></div>' +
                    '   </div>' +
                    '</div>';
                $(".dialog-box").find(".dialog-close").click();

            } else {
                html = "<div class='dialog-box'>" +
                    "<div class='dialog-header'>" +
                    "<div class='dialog-title'></div>" +
                    "<div class='dialog-close'></div>" +
                    "</div>" +
                    "<div class='dialog-content'></div>" +
                    "<div style='clear:both'></div>" +
                    "<div class='dialog-button'>" +
                    // "<input type='button' class='dialog-ok' value='??????'>" +
                    // "<input type='button' class='dialog-cancel' value='??????'>" +
                    "<a  class='btn-base dialog-ok'><span>'+self.options.okBtnName+'</span></a>" +
                    "<a  class='btn-base dialog-cancel'><span>'+self.options.cancelBtnName+'</span></a>" +
                    "</div>" +
                    "</div>";
            }
            self.dh = $(html).appendTo('body').hide().css({
                position: 'absolute',
                overflow: 'hidden',
                zIndex: self.options.zIndex
            });
            self.dt = self.dh.find('.dialog-title');
            self.dc = self.dh.find('.dialog-content');
            self.db = self.dh.find('.dialog-button');
            self.bo = self.dh.find('.dialog-ok');
            self.bc = self.dh.find('.dialog-cancel');
            self.db.show();
            if (self.options.boxid) {
                self.dh.attr('id', self.options.boxid);
            }
            if (self.options.boxclass) {
                self.dh.addClass(self.options.boxclass);
            }
            if (self.options.height > 0) {
                self.dc.css('height', self.options.height);
            }
            if (self.options.contentType == 'iframe') {
                self.dc.css('padding', "0");
                self.db.hide();
            }

            if (self.options.width > 0) {
                self.dh.css('width', self.options.width);
            }
            self.dh.bgiframe();
            !!self.options.dialogReady && self.options.dialogReady();
        }

        this.initMask = function() {
            if (self.options.modal) {
                self.mh = $("<div class='dialog-mask'></div>")
                    .appendTo('body').hide().css({
                        opacity: self.options.overlay / 100,
                        filter: 'alpha(opacity=' + self.options.overlay + ')',
                        width: self.bwidth(),
                        height: self.bheight(),
                        zIndex: self.options.zIndex - 1
                    });
            }
        }

        this.initContent = function(content) {
            self.dh.find(".dialog-ok").val(self.options.okBtnName);
            self.dh.find(".dialog-cancel").val(self.options.cancelBtnName);
            self.dh.find('.dialog-title').html(self.options.title);
            if (!self.options.showTitle) {
                self.dh.find('.dialog-header').hide();
            }
            if (!self.options.showButton) {
                self.dh.find('.dialog-button').hide();
            }
            if (!self.options.showCancel) {
                self.dh.find('.dialog-cancel').hide();
            }
            if (!self.options.showOk) {
                self.dh.find(".dialog-ok").hide();
            }
            if (self.options.contentType == "selector") {
                self.selector = self._content;
                self._content = $(self.selector).html();
                self.setContent(self._content);
                //if have checkbox do
                var cs = $(self.selector).find(':checkbox');
                self.dh.find('.dialog-content').find(':checkbox').each(function(i) {
                    this.checked = cs[i].checked;
                });
                $(self.selector).empty();
                self.onopen();
                self.show();
                self.focus();
            } else if (self.options.contentType == "ajax") {
                self.ajaxurl = self._content;
                self.setContent('<div class="dialog-loading"></div>');
                self.show();
                $.get(self.ajaxurl, function(data) {
                    self._content = data;
                    self.setContent(self._content);
                    self.onopen();
                    self.focus();
                    if (self.options.position == 'center') {
                        self.setCenterPosition();
                    }
                });
            } else if (self.options.contentType == "iframe") {
                self.setContent('<iframe frameborder="0" width="100%" height="100%" src="' + self._content + '"></iframe>');
                self.onopen();
                self.show();
                self.focus();
            } else {
                self.setContent(self._content);
                self.onopen();
                self.show();
                self.focus();
            }
        }

        this.initEvent = function() {
            self.dh.find(".dialog-close, .dialog-cancel, .dialog-ok").unbind('click').click(function() {
                self.close();
                if (self.options.type == 'wee') {
                    $(".dialog-box").find(".dialog-close").click();
                }
            });
            if (typeof(self.options.onok) == "function") {
                self.dh.find(".dialog-ok").unbind('click').bind("click" ,function(){
                    self.options.onok(self);
                });
            }
            if (typeof(self.options.oncancel) == "function") {
                self.dh.find(".dialog-cancel").unbind('click').bind("click" , function(){
                    self.options.oncancel(self);
                });
            }
            if (self.options.timeout > 0) {
                window.setTimeout(self.close, (self.options.timeout * 1000));
            }
            this.draggable();
        }

        this.draggable = function() {
            if (self.options.draggable && self.options.showTitle) {
                self.dh.find('.dialog-header').mousedown(function(event) {
                    self._ox = self.dh.position().left;
                    self._oy = self.dh.position().top;
                    self._mx = event.clientX;
                    self._my = event.clientY;
                    self._dragging = true;
                });
                if (self.mh) {
                    var handle = self.mh;
                } else {
                    var handle = $(document);
                }
                $(document).mousemove(function(event) {
                    if (self._dragging == true) {
                        //window.status = "X:"+event.clientX+"Y:"+event.clientY;
                        self.dh.css({
                            left: self._ox + event.clientX - self._mx,
                            top: self._oy + event.clientY - self._my
                        });
                    }
                }).mouseup(function() {
                    self._mx = null;
                    self._my = null;
                    self._dragging = false;
                });
                var e = self.dh.find('.dialog-header').get(0);
                e.unselectable = "on";
                e.onselectstart = function() {
                    return false;
                };
                if (e.style) {
                    e.style.MozUserSelect = "none";
                }
            }
        }

        this.onopen = function() {
            if (typeof(self.options.onopen) == "function") {
                self.options.onopen();
            }
        }

        this.show = function() {
            if (self.options.position == 'center') {
                self.setCenterPosition();
            }
            if (self.options.position == 'element') {
                self.setElementPosition();
            }
            if (self.options.animate) {
                self.dh.fadeIn("slow");
                if (self.mh) {
                    self.mh.fadeIn("normal");
                }
            } else {
                self.dh.show();
                if (self.mh) {
                    self.mh.show();
                }
            }
        }

        this.focus = function() {
            if (self.options.focus) {
                self.dh.find(self.options.focus).focus();
            } else {
                self.dh.find('.dialog-cancel').focus();
            }
        }

        this.find = function(selector) {
            return self.dh.find(selector);
        }

        this.setTitle = function(title) {
            self.dh.find('.dialog-title').html(title);
        }

        this.getTitle = function() {
            return self.dh.find('.dialog-title').html();
        }

        this.setContent = function(content) {
            self.dh.find('.dialog-content').html(content);
        }

        this.getContent = function() {
            return self.dh.find('.dialog-content').html();
        }

        this.hideButton = function(btname) {
            self.dh.find('.dialog-' + btname).hide();
        }

        this.showButton = function(btname) {
            self.dh.find('.dialog-' + btname).show();
        }

        this.setButtonTitle = function(btname, title) {
            self.dh.find('.dialog-' + btname).val(title);
        }

        this.close = function() {
            if (self.animate) {
                self.dh.fadeOut("slow", function() {
                    self.dh.hide();
                });
                if (self.mh) {
                    self.mh.fadeOut("normal", function() {
                        self.mh.hide();
                    });
                }
            } else {
                self.dh.hide();
                if (self.mh) {
                    self.mh.hide();
                }
            }
            if (self.options.contentType == 'selector') {
                if (self.options.contentChange) {
                    //if have checkbox do
                    var cs = self.find(':checkbox');
                    $(self.selector).html(self.getContent());
                    if (cs.length > 0) {
                        $(self.selector).find(':checkbox').each(function(i) {
                            this.checked = cs[i].checked;
                        });
                    }
                } else {
                    $(self.selector).html(self._content);
                }
            }
            if (typeof(self.options.onclose) == "function") {
                self.options.onclose(self);
            }
            self.dh.remove();
            if (self.mh) {
                self.mh.remove();
            }
        }

        this.bheight = function() {
            if ($.browser.msie && $.browser.version < 7) {
                var scrollHeight = Math.max(
                    document.documentElement.scrollHeight,
                    document.body.scrollHeight
                );
                var offsetHeight = Math.max(
                    document.documentElement.offsetHeight,
                    document.body.offsetHeight
                );

                if (scrollHeight < offsetHeight) {
                    return $(window).height();
                } else {
                    return scrollHeight;
                }
            } else {
                return $(document).height();
            }
        }

        this.bwidth = function() {
            if ($.browser.msie && $.browser.version < 7) {
                var scrollWidth = Math.max(
                    document.documentElement.scrollWidth,
                    document.body.scrollWidth
                );
                var offsetWidth = Math.max(
                    document.documentElement.offsetWidth,
                    document.body.offsetWidth
                );

                if (scrollWidth < offsetWidth) {
                    return $(window).width();
                } else {
                    return scrollWidth;
                }
            } else {
                return $(document).width();
            }
        }

        this.setCenterPosition = function() {
            var wnd = $(window),
                doc = $(document),
                pTop = doc.scrollTop(),
                pLeft = doc.scrollLeft(),
                minTop = pTop;
            pTop += (wnd.height() - self.dh.height()) / 2;
            pTop = Math.max(pTop, minTop);
            pLeft += (wnd.width() - self.dh.width()) / 2;
            self.dh.css({
                top: pTop,
                left: pLeft
            });

        }

        //      this.setElementPosition = function() {
        //          var trigger = $("#"+self.options.trigger);
        //          if (trigger.length == 0) {
        //              alert('??????????????????????????????');
        //              self.close();
        //              return false;
        //          }
        //          var scrollWidth = 0;
        //          if (!$.browser.msie || $.browser.version >= 7) {
        //              scrollWidth = $(window).width() - document.body.scrollWidth;
        //          }
        //
        //          var left = Math.max(document.documentElement.scrollLeft, document.body.scrollLeft)+trigger.position().left;
        //          if (left+self.dh.width() > document.body.clientWidth) {
        //              left = trigger.position().left + trigger.width() + scrollWidth - self.dh.width();
        //          }
        //          var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop)+trigger.position().top;
        //          if (top+self.dh.height()+trigger.height() > document.documentElement.clientHeight) {
        //              top = top - self.dh.height() - 5;
        //          } else {
        //              top = top + trigger.height() + 5;
        //          }
        //          self.dh.css({top: top, left: left});
        //          return true;
        //      }

        this.setElementPosition = function() {
            var trigger = $(self.options.trigger);
            if (trigger.length == 0) {
                alert('??????????????????????????????');
                self.close();
                return false;
            }
            var left = trigger.offset().left;
            var top = trigger.offset().top + 25;
            self.dh.css({
                top: top,
                left: left
            });
            return true;
        }

        //???????????????
        this.initialize = function() {
                self.initOptions();
                self.initMask();
                self.initBox();
                self.initContent();
                self.initEvent();
                return self;
            }
            //?????????
        this.initialize();
    }

    var weeboxs = function() {
        var self = this;
        this._onbox = false;
        this._opening = false;
        this.boxs = new Array();
        this.zIndex = 999;
        this.push = function(box) {
            this.boxs.push(box);
        }
        this.pop = function() {
            if (this.boxs.length > 0) {
                return this.boxs.pop();
            } else {
                return false;
            }
        }
        this.open = function(content, options) {
            self._opening = true;
            if (typeof(options) == "undefined") {
                options = {};
            }
            if (options.boxid) {
                this.close(options.boxid);
            }
            options.zIndex = this.zIndex;
            this.zIndex += 10;
            var box = new weebox(content, options);
            box.dh.click(function() {
                self._onbox = true;
            });
            this.push(box);
            return box;
        }
        this.close = function(id) {
            if (id) {
                for (var i = 0; i < this.boxs.length; i++) {
                    if (this.boxs[i].dh.attr('id') == id) {
                        this.boxs[i].close();
                        this.boxs.splice(i, 1);
                    }
                }
            } else {
                this.pop().close();
            }
        }
        this.length = function() {
            return this.boxs.length;
        }
        this.getTopBox = function() {
            return this.boxs[this.boxs.length - 1];
        }
        this.find = function(selector) {
            return this.getTopBox().dh.find(selector);
        }
        this.setTitle = function(title) {
            this.getTopBox().setTitle(title);
        }
        this.getTitle = function() {
            return this.getTopBox().getTitle();
        }
        this.setContent = function(content) {
            this.getTopBox().setContent(content);
        }
        this.getContent = function() {
            return this.getTopBox().getContent();
        }
        this.hideButton = function(btname) {
            this.getTopBox().hideButton(btname);
        }
        this.showButton = function(btname) {
            this.getTopBox().showButton(btname);
        }
        this.setButtonTitle = function(btname, title) {
            this.getTopBox().setButtonTitle(btname, title);
        }
        $(window).scroll(function() {
            if (self.length() > 0) {
                var box = self.getTopBox();
                if (box.options.position == "center") {
                    self.getTopBox().setCenterPosition();
                }
            }
        });
        $(document).click(function() {
            if (self.length() > 0) {
                var box = self.getTopBox();
                if (!self._opening && !self._onbox && box.options.clickClose) {
                    box.close();
                }
            }
            self._opening = false;
            self._onbox = false;
        });
    }
    $.extend({
        weeboxs: new weeboxs()
    });
})(jQuery);

;
(function($) {
    $(function() {
        if (typeof Firstp2p === "undefined") {
            Firstp2p = {};
        }

		//???????????????
		Firstp2p.alert = function(obj) {
			var settings = $.extend({
				title: "??????",
				width: 300,
				showButton: true,
				okBtnName: '??????',
				boxclass: '',
				ok: $.noop,
				text: "",
				close: $.noop
			}, obj),
			html = '',
			instance = null;
			html += '<div>' + settings.text + '</div>';
			instance = $.weeboxs.open(html, {
				boxid: null,
				boxclass: settings.boxclass,
				contentType: 'selector',
				showButton: settings.showButton,
				showOk: true,
				okBtnName: settings.okBtnName,
				showCancel: false,
				title: settings.title,
				width: settings.width,
				type: 'wee',
				onclose: function() {
					!!settings.close && settings.close(settings);
				},
				onok: function(obj) {
                    !!settings.ok && settings.ok(settings);
                    obj.close(obj);
				},
				onopen : function(){
                    //instance.close();
                    !!settings.open && settings.open(settings);
                }
            });
            return instance;
        };

        //?????????????????????klk
        Firstp2p.confirm = function(obj) {
            var settings = $.extend({
                title : "??????" ,
                okBtnName : "??????",
                boxclass : '',
                width: 300,
                ok: $.noop,
                text : "" ,
                cancel : $.noop,
                close : $.noop
            } , obj),
            html = '',
            instance = null;
            html += '<div>'+ settings.text +'</div>';
            instance = $.weeboxs.open(html, {
                boxid: null,
                boxclass: settings.boxclass,
                contentType: 'text',
                showButton: true,
                showOk: true,
                okBtnName: settings.okBtnName,
                showCancel: true,
                CancelBtnName: '??????',
                title: settings.title,
                width: settings.width,
                type: 'wee',
                onclose: function() {
                    !!settings.close && settings.close(settings);
                },
                onok: function() {
                    instance.close();
                    !!settings.ok && settings.ok(settings);
                    
                },
                oncancel : function(){
                     instance.close();
                    settings.cancel(settings);
                   
                }
            });
            return instance;
        };

        Firstp2p.getMessage = function(obj){
            var quhao = obj.mobile_code;
            quhao = quhao.replace(/[^0-9]/, "");
            quhao = quhao == "86" ? "" : (quhao + "-");
            var phone = obj.mobile;
            var phonelabel = quhao + phone;
            phonelabel = phonelabel.replace(/([0-9\-]*)([0-9]{4})([0-9]{4})/, function(_0, _1, _2, _3) {return _1 + "****" + _3 });
            var settings = $.extend({
                title : '??????????????????????????????????????????',
                html : '<div class="wee-send">\
                        <div class="send-input">\
                            <div class="error-box" style="visibility:hidden;">\
                                <div class="error-wrap">\
                                    <div class="e-text" style="width: 305px;">?????????6??????????????????</div>\
                                </div>\
                            </div>\
                            <p>??????&nbsp;<span class="color_green">' + phonelabel + ' </span>&nbsp;??????????????????</p>\
                            <input type="text" class="ipt-txt w150" id="pop_code" placeholder="???????????????" maxlength="10" value="">\
                            <input type="button"  class="reg-sprite btn-blue-h34 btn-gray-h34 j_sendMessage" value="??????">\
                        </div>\
                        </div>',
                msgUrl : '/user/MCode',
                postUrl : '/user/webDoLogin'
            } , obj ),
            errorSpan = "",
            status = "",
            timer = null,
            msglock = false,
            setProperty = function () {
                var button = $(".ui_send_msg .j_sendMessage");
                bgGray();
                _reset();
            },
            bgGray = function() {
                var button = $(".ui_send_msg .j_sendMessage");
                button.addClass("btn-gray-h34");
                button.val("???????????????...");
                button.attr("disabled", "disabled");
            },
            _set = function(msg) {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'visible');
                errorSpan.find('.e-text').html(msg);
                $(".ui_send_msg .ipt-txt").addClass("err-shadow");
            },
            _reset = function() {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'hidden');
                errorSpan.find('.e-text').html('');
            },
            updateTimeLabel = function(duration) {
                var timeRemained = duration;
                var button = $(".ui_send_msg .j_sendMessage");
                timer = setInterval(function() {
                    button.val(timeRemained + '??????????????????');
                    timeRemained -= 1;
                    if (timeRemained == -1) {
                        clearInterval(timer);
                        msglock = false;
                        button.val('????????????').removeAttr('disabled').removeClass("btn-gray-h34");
                    }
                }, 1000);
            },
            callback = function(data) {
                if (!msglock) {
                    updateTimeLabel(60);
                    msglock = true;
                }
                if (!!data && data.code != 1) {
                    _set(data.message);
                } else {
                    _reset();
                }
            },
            getCode = function() {
                var data = {
                    "type": 9,
                    "mobile" : $("#valid_phone").val(),
                    "token" : $("#token").val(),
                    "token_id" : $("#token_id").val(),
                    "country_code" : $("#country_code").val()
                };
                var getcodeUrl = settings.msgUrl;
                $.ajax({
                    url: getcodeUrl,
                    type: "post",
                    data: data,
                    dataType: "json",
                    beforeSend: function() {
                    },
                    success: function(result) {
                        setProperty();
                        callback(result);
                    },
                    error: function() {

                    }
                });
            };

            $.weeboxs.open(settings.html , {
                boxid: null,
                boxclass: 'ui_send_msg',
                contentType: 'text',
                showButton: true,
                showOk: true,
                okBtnName: '??????',
                showCancel: false,
                title: settings.title,
                width: 463,
                height: 125,
                type: 'wee',
                onopen : function(){
                    getCode();
                },
                onclose: function() {
                    //location.href = "/user/login"
                },
                onok: function() {
                    var $text = $(".ui_send_msg .error-box").find('.e-text'),
                        showError = function() {
                            $(".ui_send_msg .error-box").css({
                                'display': 'block',
                                'visibility': 'visible'
                            });
                            $(".ui_send_msg .ipt-txt").addClass("err-shadow");
                        };

                    if (!/^\d{6}$/.test($(".ui_send_msg #pop_code").val())) {
                        showError();
                        $text.html("?????????6??????????????????");
                        return;
                    }
                    var data = {
                        "code": $(".ui_send_msg #pop_code").val(),
                        "mobile" : $("#valid_phone").val()
                    };
                    $.ajax({
                        url: settings.postUrl,
                        type: "post",
                        data: data,
                        dataType: "json",
                        beforeSend: function() {
                            // $text.html("????????????????????????...");
                        },
                        success: function(data) {
                            // alert(JSON.stringify(data));
                            if (data.errorCode === 0) {
                                $("#loginForm").append('<input type="hidden" name="code" value="'+ $("#pop_code").val() +'" >').unbind("submit").submit();
                                 $.weeboxs.close();
                            } else {
                                showError();
                                $text.html(data.errorMsg);

                            }
                        },
                        error: function() {
                            showError();
                            $text.html("????????????????????????????????????");
                        }
                    });
                    // $.weeboxs.close();
                }
            });
            // ???????????????????????????????????????????????????
            $('body').on("click", ".j_sendMessage", function() {
                getCode();
            });

        };

        // ???????????????????????????????????????
        Firstp2p.getMsg = function(obj){
            var quhao = obj.data ? obj.data.info.mobile_code : "86";
            quhao = quhao.replace(/[^0-9]/, "");
            quhao = quhao == "86" ? "" : (quhao + "-");
            var phone = $("#mobile").val();
            var phonelabel = quhao + phone;
            phonelabel = phonelabel.replace(/([0-9\-]*)([0-9]{4})([0-9]{4})/, function(_0, _1, _2, _3) {return _1 + "****" + _3 });
            var settings = $.extend(true , {
                data : {},
                title : '?????????????????????',
                html : '<div class="wee-send">\
                        <div class="send-input">\
                            <div class="error-box" style="visibility:hidden;">\
                                <div class="error-wrap">\
                                    <div class="e-text" id="j_e_text" style="width: 305px;">?????????6??????????????????</div>\
                                </div>\
                            </div>\
                            <p>??????&nbsp;<span class="color_green">' + phonelabel + ' </span>&nbsp;??????????????????</p>\
                            <input type="text" class="ipt-txt w150" id="pop_code" placeholder="???????????????" maxlength="10" value="">\
                            <input type="button"  class="reg-sprite btn-blue-h34 btn-gray-h34 j_sendMessage" value="??????">\
                        </div>\
                        </div>',
                msgUrl : '/user/MCode',
                draggable : true ,
                postUrl : '/user/CheckPwdCode',
                callback : function(data) {
                    if (!msglock) {
                        updateTimeLabel(60);
                        msglock = true;
                    }
                    if (!!data && data.code != 1) {
                        _set(data.message);
                    } else {
                        _reset();
                    }
                },
                callbackpost : function(data , settings){
                    if (data.info.code === "1") {
                        $.weeboxs.close();
                        window.location.href = data.jump;
                    } else {
                        settings.showError();
                        $("#j_e_text").html(data.info.msg);
                    }
                }
            } , obj ),
            errorSpan = "",
            status = "",
            timer = null,
            msglock = false,
            setProperty = function () {
                var button = $(".ui_send_msg .j_sendMessage");
                bgGray();
                _reset();
            },
            bgGray = function() {
                var button = $(".ui_send_msg .j_sendMessage");
                button.addClass("btn-gray-h34");
                button.val("???????????????...");
                button.attr("disabled", "disabled");
            },
            _set = function(msg) {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'visible');
                errorSpan.find('.e-text').html(msg);
                $(".ui_send_msg .ipt-txt").addClass("err-shadow");
            },
            _reset = function() {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'hidden');
                errorSpan.find('.e-text').html('');
            },
            updateTimeLabel = function(duration) {
                var timeRemained = duration;
                var button = $(".ui_send_msg .j_sendMessage");
                timer = setInterval(function() {
                    button.val(timeRemained + '??????????????????');
                    timeRemained -= 1;
                    if (timeRemained == -1) {
                        clearInterval(timer);
                        msglock = false;
                        button.val('????????????').removeAttr('disabled').removeClass("btn-gray-h34");
                    }
                }, 1000);
            },
            getCode = function() {
                var data = settings.dataGetCode;
                var getcodeUrl = settings.msgUrl;
                $.ajax({
                    url: getcodeUrl,
                    type: "post",
                    data: data,
                    dataType: "json",
                    beforeSend: function() {
                    },
                    success: function(result) {
                        setProperty();
                        settings.callback(result);
                    },
                    error: function() {

                    }
                });
            };

            $.weeboxs.open(settings.html , {
                boxid: null,
                boxclass: 'ui_send_msg',
                contentType: 'text',
                showButton: true,
                showOk: true,
                okBtnName: '??????',
                draggable : settings.draggable ,
                showCancel: false,
                title: settings.title,
                width: 463,
                height: 125,
                type: 'wee',
                onopen : function(){
                    getCode();
                },
                onclose: function() {
                    !!settings.onclose && settings.onclose();
                },
                onok: function() {
                    var $text = $(".ui_send_msg .error-box").find('.e-text');
                    if(!settings.showError){
                        settings.showError = function() {
                            $(".ui_send_msg .error-box").css({
                                'display': 'block',
                                'visibility': 'visible'
                            });
                            $(".ui_send_msg .ipt-txt").addClass("err-shadow");
                        };
                    }


                    if (!/^\d{6}$/.test($(".ui_send_msg #pop_code").val())) {
                        settings.showError();
                        $text.html("?????????6??????????????????");
                        return;
                    }
                    settings.dataVerrifyCode.code = $(".ui_send_msg #pop_code").val();
                    //console.log(settings.dataVerrifyCode);
                    $.ajax({
                        url: settings.postUrl,
                        type: "post",
                        data: settings.dataVerrifyCode,
                        dataType: "json",
                        beforeSend: function() {
                            // $text.html("????????????????????????...");
                        },
                        success: function(data) {
                            settings.callbackpost(data , settings);
                        },
                        error: function() {
                            settings.showError();
                            $text.html("????????????????????????????????????");
                        }
                    });
                    // $.weeboxs.close();
                }
            });


            // ???????????????????????????????????????????????????
            $('body').on("click", ".j_sendMessage", function() {
                getCode();
            });

        };

    });

})(jQuery);;;(function($) {
    var config = null;

    $(function() {
            //????????????????????????
            try {
                if (typeof (USER_INFO) != 'undefined' && USER_INFO == 1) {
                    myAccount();
                }
            } catch (e) {

            }
            if(location.pathname.indexOf("/product") > -1){
                $("#top_nav li").removeClass("select");
                $("#top_nav li:eq(1)").addClass("select");
            }else if(location.pathname.indexOf("/id-aboutp2p") > -1){
                $("#top_nav li").removeClass("select");
                $("#top_nav li:eq(3)").addClass("select");
            }
        })
        //????????????
    function myAccount() {
        var ele = $('.my_account');
        var ele_msg = $('.j_showMenu2');
        if(ele.find("ul").length > 0){
            ele.hover(
                function() {
                    ele.addClass("select");
                },
                function() {
                    ele.removeClass("select");
                }
            );
        }

        ele_msg.hover(
            function() {
                ele_msg.addClass("select");
            },
            function() {
                ele_msg.removeClass("select");
            }
        )

    }
})(jQuery, "firstp2p common.js");

var Firstp2p = {
    mobileReg: {
        'cn': /^1[3456789]\d{9}$/,
        'hk': /^[968]\d{7}$/,
        'mo': /^[68]\d{7}$/,
        'tw': /^09\d{8}$/,
        'us': /^\d{10}$/,
        'ca': /^\d{10}$/,
        'uk': /^7\d{9}$/
    },
    isIE7 : function(){
       return navigator.appVersion.search(/MSIE 7/i) != -1;
    },
    // 3????????????????????????js
    goPay: function(options){
        var defaultSettings = {
            number: 3,
            $obj: $("#second"),
            callback : function(){
                $("#bindCardForm").submit();
            }
        },
        settings = $.extend(true, defaultSettings, options),
        num = settings.number;
        $(settings.$obj).html(num);
        var goes = setInterval(function(){
                num--;
                $(settings.$obj).html(num);
                if(num === 0 ){
                    clearInterval(goes);
                    settings.callback();
                }
        },1000);
    }
};

//?????????????????????????????????
(function($) {
    $(function() {
        if (typeof forceChangePwd !== 'undefined' && !!forceChangePwd&&isEnterprise!=1) {
            //?????????????????????????????????
            if( window.location.pathname!='/user/savepwd'){
                //?????????
                var promptStr = '';
                promptStr = '<div class="pop-tit"><i></i>??????????????????????????????????????????????????????????????????????????????</div>' +
                    '<div class="tc">??????<a href="javascript:void(0)" class="blue" id="edit-btn">??????????????????</a></div>';
                Firstp2p.alert({
                    text: '<div class="f16">' + promptStr + '</div>',
                    ok: function(dialog) {
                        dialog.close();
                    },
                    width: 560,
                    showButton: false,
                    boxclass: "commpany-popbox"
                });
            }
            $("body").on("click", ".dialog-close , #edit-btn" ,function() {
                $.weeboxs.close();
                if( window.location.pathname!='/user/editpwd'){
                    location.href = '/user/editpwd';
                }
            });
        }

    });
})(jQuery);

//???????????????is_dflh???????????????????????????????????????
(function($) {
    $(function() {
    //is_dflh??????1???????????????????????????
        if (typeof user_info_is_dflh !== 'undefined' && user_info_is_dflh == 1) {
            var transDialogStr = $('#trans_protocal_str').val();
            $('.transDialog .dialog_user').prepend(transDialogStr);
            $('.transDialog').removeClass('tsfDgHide');
            $("body").css({"overflow-y": "hidden"});
            $('.transDialog').on('click','#agreeTrans', function(){
                var _this = $(this),
                lock =  _this.data('lock');
                if(lock == 0){
                    _this.data('lock','1');
                    $.ajax({
                        url: '/user/transferConfirm',
                        type: 'post',
                        data: {},
                        dataType: 'json',
                        success: function(data) {
                            if (data.code == 0) {
                                $('.transDialog').addClass('tsfDgHide');
                                $('.transDialog').remove();
                                $("body").css({"overflow-y": "auto"});
                            }else{
                                alert(data.msg);
                            }
                             _this.data('lock','0');
                        },
                        error: function() {
                            alert("???????????????????????????");
                            _this.data('lock','0');
                        }
                    })
                }
            });
        }
    });
})(jQuery);




//??????-??????
(function($) {
    $(function() {

       Firstp2p.supervision = {
          // ????????????P2P??????
           kaihu : function(data){
           var str = " <div class='p2pImg'></div><p class='openTips'>??????"+account_p2p+"</p><p class='acDetail'>????????????????????????????????????????????????"+account_p2p+"</p>";
           var settings = $.extend({
                  title: "??????"+account_p2p,
                  ok: $.noop,
                  text: str,
                  close: $.noop
              }, data),
              html = '',
              instance = null;
              html += '' + settings.text + '';
              instance = $.weeboxs.open(html, {
                  boxid: 'cg_openP2pAccount',
                  boxclass: 'p2pAccountDg',
                  contentType: 'text',
                  showButton: true,
                  showOk: true,
                  showCancel:true,
                  title: settings.title,
                  width: 300,
                  type: 'wee',
                  onok: function(settings) {
                      settings.close(settings);
                      window.open("/payment/transit?srv=register");
                      Firstp2p.supervision.wancheng();
                  }
           });
          },
           //????????????-??????p2p?????????????????????
           zhuanlicai : function(data){
               var dynamicMap={
                   "1002":{
                       "title":account_wx + "????????????????????????????????????",
                       "boxclass":"transferBl"
                   },
                   "1004":{
                       "title":account_p2p+"????????????????????????????????????",
                       "boxclass":"transferBl_top2p"
                   }
               }
               var inforJson=dynamicMap[data.status];
              var that = this;
              var str = "<p class='hz_less'>"+inforJson.title+"</p>\
                <div style='display:inline-block;' class='p2pImg'></div><div style='display:inline-block;' class='fxImg'></div><div style='display:inline-block;' class='transferImg'></div><p class='openTips'>???????????????"+ data.data.transferMoney +"</p>";
              var settings = $.extend({
                    title: "????????????",
                    text : str,
                    boxclass: inforJson.boxclass,
                    close: $.noop
               }, data),
              html = '',
              instance = null;
              html += '' + settings.text + '';
              var dialog = null;
              instance = $.weeboxs.open(html, {
                  boxid: null,
                  boxclass: settings.boxclass,
                  contentType: 'text',
                  showButton: true,
                  showOk: true,
                  showCancel:true,
                  title: settings.title,
                  width: 300,
                  type: 'wee',
                  onok: function(settings) {
                     if(!!data.data){
                          window.open(data.data.url);
                          Firstp2p.supervision.lunxun({
                              sCallback : function(obj){

                                  if(obj.status == 1){
                                      !!dialog && dialog.close();
                                      !!settings && settings.close();
                                      that.resetsubmit();
                                      clearInterval(lunxunTimer);
                                  }else if(obj.status == 2){
                                      Firstp2p.alert({
                                         text : obj.msg
                                      });
                                      that.resetsubmit();
                                      clearInterval(lunxunTimer);
                                  }else if(obj.status == 0){
                                      that.resetsubmit();
                                  }

                              },
                              url : "/supervision/RechargeQuery",
                              data : {
                                orderId : data.data.orderId
                              }

                          });
                          settings.close(settings);
                          dialog = that.finish();
                      }
                  },
                  onclose : function(){
                      that.resetsubmit();
                      $("#J_bid_submit").data("wancheng_dialog_lock" , "1");

                  }
           });
          },
          //????????????-?????????????????????p2p??????
          zhuanwdp2p : function(data){
              var that = this;
              var str = "<p class='hz_less'></p>\
                <div style='display:inline-block;' class='p2pImg'></div><div style='display:inline-block;' class='fxImg'></div><div style='display:inline-block;' class='transferImg'></div><p class='openTips'>???????????????"+ data.data.transferMoney +"</p>\
                <div class='notipsCont'><input name='missTips' id='missTips' class='missTips' type='checkbox' value='????????????'/><label class='hznotips' for='missTips'></label><span class='bztsTxt'>?????????????????????????????????</span></div>";
              var settings = $.extend({
                    title: "????????????",
                    text : str,
                    boxclass: '',
                    close: $.noop
               }, data),
              html = '',
              instance = null;
              html += '' + settings.text + '';
              var dialog = null;
              instance = $.weeboxs.open(html, {
                  boxid: null,
                  boxclass: 'transfer_hz transferBl_notips',
                  contentType: 'text',
                  showButton: true,
                  showOk: true,
                  showCancel:false,
                  title: settings.title,
                  width: 300,
                  type: 'wee',
                  onok: function(settings) {
                    $.ajax({
                        url: '/supervision/MoneyTransfer?money='+ data.data.transferMoney +'&direction='+ data.data.direction +'',
                        type: 'post',
                        data: {},
                        dataType: 'json',
                        success: function(data) {
                          Firstp2p.alert({
                              text : '<div class="tc">'+  data.info +'</div>',
                              ok : function(dialog){
                                  dialog.close(dialog);
                              }
                          });
                        },
                        error: function() {
                          Firstp2p.alert({
                              text : '<div class="tc">?????????????????????????????????</div>',
                              ok : function(dialog){
                                  dialog.close(dialog);
                              }
                          });
                        }
                    })
                    settings.close(settings);
                  },
                  onclose : function(){
                      that.resetsubmit();
                  }
              });
          },


           //??????????????????
            shouquan : function(data){
            var str = "<div class='psdImg'></div><p class='openTips'>??????????????????????????????</p><p class='acDetail'>???????????????????????????"+account_p2p+"????????????????????????????????????"+account_wx+"?????????????????????????????????</p>";
           var settings = $.extend({
                  title: "??????????????????",
                  text : str,
                  boxclass: 'transferBl_top2p'
              }, data),
              html = '',
              instance = null;
              html += '' + settings.text + '';
              instance = $.weeboxs.open(html, {
                   boxid: 'cg_password_free',
                   boxclass: 'passwordFree',
                   contentType: 'text',
                   showButton: true,
                   showOk: true,
                   showCancel:true,
                   okBtnName: '??????',
                   cancelBtnName: '??????',
                   showCancel: false,
                   title: settings.title,
                   width: 300,
                   type: 'wee',
                   onok: function(obj) {
                       window.open('/payment/Transit?srv=freePaymentQuickBid');
                       obj.close(obj);
                   }
               });
               return instance;
          },

          //?????? ?????? ????????????????????????
           wancheng : function(obj) {
                var that = this;
                var settings = $.extend({
                       title: "????????????",
                       ok: $.noop,
                       text: '<p class="czwc">???????????????????????????????????????????????????????????????</p><p class="doneDgTt" style="color:red;">???????????????????????????????????????????????????</p>',
                       close: $.noop
                   }, obj),
                   html = '',
                   instance = null;
                   html += '' + settings.text + '';
                   instance = $.weeboxs.open(html, {
                       boxid: null,
                       boxclass: 'done_Confirm',
                       contentType: 'text',
                       showButton: true,
                       showOk: true,
                       showCancel:true,
                       okBtnName: '???????????????',
                       cancelBtnName: '??????????????????',
                       title: settings.title,
                       width: 300,
                       type: 'wee',
                       onok: function(object) {
                            if(typeof(doneBankOperate_url) != "undefined" && doneBankOperate_url != ""){
                              window.location.href = doneBankOperate_url;
                            }else{
                              location.reload();
                            }
                       },
                       oncancel: function(object) {
                           location.reload();
                       }
                });
                return instance;
           },

           finish : function(obj) {
                var that = this;
                var settings = $.extend({
                       title: "????????????",
                       ok: $.noop,
                       text: '<p class="czwc">???????????????????????????????????????????????????????????????</p><p class="doneDgTt" style="color:red;">???????????????????????????????????????????????????</p>',
                       close: $.noop
                   }, obj),
                   html = '',
                   instance = null;
                   html += '' + settings.text + '';
                   instance = $.weeboxs.open(html, {
                       boxid: null,
                       boxclass: 'done_Confirm',
                       contentType: 'text',
                       showButton: true,
                       showOk: true,
                       showCancel:true,
                       okBtnName: '???????????????',
                       cancelBtnName: '??????????????????',
                       title: settings.title,
                       width: 300,
                       type: 'wee',
                       onclose: function(object) {
                          //object.close(object);
                          that.resetsubmit();

                       },
                       onok: function(object) {
                          that.resetsubmit();
                          clearInterval(lunxunTimer);
                          object.close(settings);
                       },
                       oncancel: function(object) {
                          that.resetsubmit();
                          clearInterval(lunxunTimer);
                          object.close(object);
                       }
                });
                return instance;
           },
           //js?????????
           lunxun : function(obj){

              var that = this ,
              ram = new Date().getTime(),
              settings = $.extend({
                 time : 5 ,
                 url : location.href,
                 data : "",
                 dataType : "json" ,
                 sCallback : $.noop ,
                 eCallback : $.noop ,
                 type : "get",
                 timer : 3000
              } , obj),
              lunxun = function(){
                $.ajax({
                   url : settings.url ,
                   type : settings.type ,
                   data : settings.data ,
                   dataType : settings.dataType ,
                   success : function(data){
                      settings.sCallback(data);
                   },
                   error : function(data){
                      //eCallback(data);
                      clearInterval(lunxunTimer);
                      console.log(settings.url + '????????????????????????????????????');
                   }

                });
              };
              if(typeof lunxunTimer != 'undefined'){
                clearInterval(lunxunTimer);
              }
              //lunxun();
              window["lunxunTimer"] = setInterval(function(){
                 lunxun();
              }, settings.timer);
           },
           //??????????????????
           isKaihu : function(){
                var that = this;
                $.ajax({
                   url: '/account/isOpenAccount',
                   type: "get",
                   data: '',
                   dataType: "json",
                   success: function(json) {
                        if(json.errno == 0){
                            if(json.data.status == 0){
                                that.kaihu();
                            }
                        }else{
                            Firstp2p.alert({
                                text : '<div class="tc">'+  json.error +'</div>',
                                ok : function(dialog){
                                    dialog.close(dialog);
                                }
                            });
                        }
                   }
                });
           },
           resetsubmit : function(){
               $("#J_bid_submit").removeAttr('disabled').val('??????');
           },

           ph_account : function(data,lockObj){
               var ph_location_href = data,
                   _this = lockObj,
                   _lock = _this.data('lock');
               if(isSvUser == 1){
                   window.location.href = ""+ ph_location_href;
               }else{
                  if(_lock == 0){
                   _this.data('lock','1');
                   $.ajax({
                      url: '/deal/isOpenAccount',
                      type: "get",
                      dataType: "json",
                      success: function(json) {
                           if(json.errno === 0){
                               if(json.data.status == 1 || json.data.wxStatus == 0){
                                   window.location.href = ""+ ph_location_href;
                               }else{
                                   Firstp2p.supervision.kaihu();
                                   $('#cg_openP2pAccount .dialog-close').wrap("<a class='btn-base dialog-cancel'></a>");
                                   if(typeof window["_openSvButton_"] !== 'undefined' && window["_openSvButton_"] == 1){
                                       $('.p2pAccountDg .dialog-title').html("??????"+account_p2p);
                                       $('.p2pAccountDg .openTips').html("??????"+account_p2p);
                                   }
                               }
                           }else{
                               Firstp2p.alert({
                                   text : '<div class="tc">'+  json.error +'</div>',
                                   ok : function(dialog){
                                       dialog.close();
                                   }
                               });
                           }
                        _this.data('lock','0');
                      },
                      error: function(){
                           Firstp2p.alert({
                               text : '<div class="tc">?????????????????????????????????</div>',
                               ok : function(dialog){
                                   dialog.close();
                               }
                           });
                           _this.data('lock','0');
                      }
                   });
                  }
               }

           },
           ph_account_newWindow : function(data,lockObj){
               var ph_location_href = data,
                   _this = lockObj,
                   _lock = _this.data('lock');
               if(isSvUser == 1){
                   window.open(ph_location_href);
               }else{
                  if(_lock == 0){
                     _this.data('lock','1');
                     $.ajax({
                        url: '/deal/isOpenAccount',
                        type: "get",
                        dataType: "json",
                        async:false,
                        success: function(json) {
                             if(json.errno === 0){
                                 if(json.data.status == 1){
                                    window.open(ph_location_href);
                                 }else{
                                     Firstp2p.supervision.kaihu();
                                     $('#cg_openP2pAccount .dialog-close').wrap("<a class='btn-base dialog-cancel'></a>");
                                     if(typeof window["_openSvButton_"] !== 'undefined' && window["_openSvButton_"] == 1){
                                        $('.p2pAccountDg .dialog-title').html("??????"+account_p2p);
                                        $('.p2pAccountDg .openTips').html("??????"+account_p2p);
                                     }
                                 }
                             }else{
                                 Firstp2p.alert({
                                     text : '<div class="tc">'+  json.error +'</div>',
                                     ok : function(dialog){
                                         dialog.close();
                                     }
                                 });
                             }
                          _this.data('lock','0');
                        },
                        error: function(){
                             Firstp2p.alert({
                                 text : '<div class="tc">?????????????????????????????????</div>',
                                 ok : function(dialog){
                                     dialog.close();
                                 }
                             });
                             _this.data('lock','0');
                        }
                     });
                  }
               }

           }


       }
       //????????????-??????????????????
       $("body").on('click','.missTips',function(){
          if($(this).is(":checked")){
              $.ajax({
                 url: '/payment/setNotPromptTransfer',
                 type: "get",
                 dataType: "json",
                 success: function(json) {
                 },
                 error: function(){
                 }
              });
          }
       });

        
       //???????????????_wxFreepayment_????????????0?????????????????????
       if (typeof _wxFreepayment_ !== 'undefined' && _wxFreepayment_ !== '' && _wxFreepayment_ == 0) {
           var cgDialogStr = $('#cg_protocal_str').val(),
               cgDialogTitle = $('#cg_protocal_title').val();
           $('#cgProtocol .dialog_user').prepend(cgDialogStr);
           $('#cgProtocol .cgTitle').prepend(cgDialogTitle);
           $('#cgProtocol').removeClass('none1');
           $("body").css({"overflow-y": "hidden"});
           $('#cgProtocol').on('click','#agreeCgPt', function(){
               var _this = $(this),
               lock =  _this.data('lock');
               if(lock == 0){
                   _this.data('lock','1');
                   $.ajax({
                       url: '/user/sign_wxfreepayment',
                       type: 'post',
                       data: {},
                       dataType: 'json',
                       success: function(data) {
                           if (data.errno == 0 && data.data.status == 1) {
                               $('#cgProtocol').addClass('none1');
                               $('#cgProtocol').remove();
                               $("body").css({"overflow-y": "auto"});
                           }else if(data.errno ==1045){
                              Firstp2p.alert({
                                    text : '<div class="tc">'+  data.error +'</div>',
                                    ok : function(dialog){
                                        dialog.close();
                                        window.location.href="/user/login";
                                    }
                              });
                           }else{
                               Firstp2p.alert({
                                     text : '<div class="tc">'+  data.error +'</div>',
                                     ok : function(dialog){
                                         dialog.close();
                                     }
                               });
                           }
                            _this.data('lock','0');
                       },
                       error: function() {
                           Firstp2p.alert({
                                 text : '<div class="tc">???????????????????????????</div>',
                                 ok : function(dialog){
                                     dialog.close();
                                 }
                           });
                           _this.data('lock','0');
                       }
                   })
               }
           });
       }

    });
})(jQuery);


var p2pBrowser = (function () {
    var u = navigator.userAgent
    return {
    wx: /MicroMessenger/i.test(u),
    webkit: /AppleWebKit/i.test(u),
    gecko: /gecko/i.test(u),
    ios: /\(i[^;]+;( U;)? CPU.+Mac OS X/.test(u),
    android: /android/i.test(u),
    iPhone: /iPhone/i.test(u),
    iPad: /iPad/i.test(u),
    app: /wx/i.test(u),
    androidApp: /wxAndroid/i.test(u),
    iosApp: /wxiOS/i.test(u)
    }
})();/*
 author: baoyue@leju.sina.com.cn
 date:  2011-12-01
 */

/*
 tab click or hover;
 cur????????????????????????
 noneClass : ?????????class
 tagPos : ????????????????????????
 hideFirst : ???????????????????????????????????????????????????
 tabLab ??? ???????????????
 tabConLab ??? ?????????????????????
 */
;(function($) {
    $.fn.goodTab = function(options) {
        var settings = {
            evt: "click",
            cur: "current",
            noneClass: "none",
            tagPos: 0,
            hideFirst: false,
            ajaxImg: false,
            returnFalse: true,
            ajaxImgFuc: function() {},
            clickEvent: "",
            tabLab: ".tab",
            tabConLab: ".tabContent"
        };
        if ( !! options) {
            $.extend(settings, options);
        }
        return this.each(function() {
            var $self = $(this),
                cur = settings.cur,
                $tab = $self.find(settings.tabLab) || $self.find(settings.tabLab.toLowerCase()),
                evt = settings.evt,
                tagPos = settings.tagPos,
                noneClass = settings.noneClass,
                $tabCon = $self.find(settings.tabConLab) || $self.find(settings.tabConLab.toLowerCase()),
                ajaxImg = settings.ajaxImg,
                returnFalse = settings.returnFalse;
            if (!$tab || !$tab.length) return;
            $tabCon.addClass(noneClass);
            $tab.removeClass(cur);
            $tab.each(function(i, v) {
                if ($(v).data("pos")) {
                    tagPos = i;
                }
            });
            $tabCon.eq(tagPos).removeClass(noneClass);
            $tab.eq(tagPos).addClass(cur);
            if (evt != 'hover') {
                $tab.bind(evt, function() {
                    var index = $tab.index($(this));
                    justShow($(this));
                    ret($(this)); 
                    !! settings.clickEvent && settings.clickEvent($(this) , index);
                    if ( !! returnFalse) {
                        return false;
                    } else {
                        return true;
                    }
                });
            } else {
                $tab.hover(function() {
                    justShow($(this));
                }).click(function() {
                    ret($(this));
                });
            }


            function justShow(t) {
                if ( !! !ajaxImg) {
                    doShow(t);
                } else {
                    settings.ajaxImgFuc($tab, $tabCon, t, cur, noneClass);
                }

            }

            function ret(obj) {
                if (obj.attr("href") === '#') {
                    return false;
                }
            }

            function doShow(object) {
                var index = $tab.index(object);
                if ($tab.length === 1 || $tab.eq(0).parent()[0] == $tab.eq(1).parent()[0]) {
                    $tab.each(function(i) {
                        $(this).removeClass(cur);
                        if (i == index) {
                            $(this).addClass(cur);
                        }
                    });
                } else if ($tab.eq(0).parent() !== $tab.eq(1).parent()) {
                    $tab.eq(index).addClass(cur).parent().siblings().find($tab).removeClass(cur);
                }
                $tabCon.addClass(noneClass).eq(index).removeClass(noneClass);
            }
        });
    }
})(jQuery);;/**
 * ????????????X
 * @nampespace Firstp2p
 */
if (typeof Firstp2p == "undefined") {
	Firstp2p = {};
}

//??????
(function(){

   /**
    * @module paginate
    * @author johnsong

    * @param  {String} ??????jquery???????????????id????????? "#paginate"(??????)
    * @param  {Object} paginate???????????????(??????)
    * @constructor
    */

    var paginate = function(el, opts) {

        //??????el??????????????? ??????????????????
        if(!$(el).length) {
            return;
        }

        /**
         * ????????????????????????????????????????????????
         * @private
         * @type {jQueryDomObject}
         */
        this._el = $(el);

        /**
         * ????????????????????????
         * @private
         * @type {Object}
         */

        this.defaultSettings = {
            items: 1,
            itemsOnPage: 1,
            pages: 0,
            displayedPages: 8,
            edges: 2,
            currentPage: 1,
            hrefTextPrefix: "#page=",
            hrefTextSuffix: "",
            prevText: "Prev",
            nextText: "Next",
            ellipseText: "&hellip;",
            cssStyle: "light-theme",
            inputText: false,
            selectOnClick: true,
            onPageClick: function (pageNumber, $obj) {}
        };

        /**
         * ??????????????????????????????????????????
         * @private
         * @type {Object}
         */
        this._opts = $.extend({} ,this.defaultSettings , opts);

        /**
         * ????????????
         * @attribute  items
         * @type {Integer} ????????????1?????????
         */
        this.items = this._opts.items;

        /**
         * ????????????????????????
         * @attribute  itemsOnPage
         * @type {Integer} ?????????????????????1?????????
         */
        this.itemsOnPage = this._opts.itemsOnPage;

        /**
         * ?????????
         * @attribute pages
         * @type {Integer} ?????????0??????????????????????????????????????????????????????????????????
         */
        this.pages = this._opts.pages;

        /**
         * ?????????????????????
         * @attribute displayedPages
         * @type {Integer} ?????????5???????????????????????????????????????
         */
        this.displayedPages = this._opts.displayedPages;

        /**
         * ???????????????????????????????????????????????????????????????
         * @attribute edges
         * @type {Integer} ?????????2??????????????????????????????????????????????????????????????????????????????
         */
        this.edges = this._opts.edges;

        /**
         * ???????????????
         * @attribute currentPage
         * @type {Integer} ?????????2???????????????????????????
         */
        this.currentPage = this._opts.currentPage;

        /**
         * ???????????????href??????
         * @attribute hrefTextPrefix
         * @type {String} ?????????"page="?????????href??????????????????
         */
        this.hrefTextPrefix = this._opts.hrefTextPrefix;

        /**
         * ???????????????href??????
         * @attribute hrefTextSuffix
         * @type {String} ?????????""????????????????????????href??????????????????
         */
        this.hrefTextSuffix = this._opts.hrefTextSuffix;

        /**
         * ?????????????????????
         * @attribute prevText
         * @type {String} ?????????"Prev"
         */
        this.prevText = this._opts.prevText;

        /**
         * ?????????????????????
         * @attribute nextText
         * @type {String} ?????????"Next"
         */
        this.nextText = this._opts.nextText;

        /**
         * ????????????????????????
         * @attribute ellipseText
         * @type {String} ?????????"&hellip;"???"..."
         */
        this.ellipseText = this._opts.ellipseText;

        /**
         * ??????css style class???
         * @attribute cssStyle
         * @type {String} ?????????"The class of the CSS theme"
         */
        this.cssStyle = this._opts.cssStyle;

        /**
         * ?????????????????????????????????
         * @attribute selectOnClick
         * @type {Boolean} ????????????true?????????????????????????????????????????????????????????false
         */
        this.selectOnClick = this._opts.selectOnClick;

        /**
         * ???????????????????????????
         * @attribute inputText
         * @type {Boolean} ????????????true
         */
        this.inputText = this._opts.inputText;

        /**
         * ???????????????????????????
         * @method onPageClick
         * @type {Function} ??????????????????????????????????????????
         * @pageNumber {Integer} ?????????????????????????????????
         * @event {Event Type} ?????????????????????????????????????????? ???click
         */
        this.onPageClick = this._opts.onPageClick;

        /**
         * ????????????????????????
         */
        this.init();
    };

    $.extend(paginate.prototype, {
        /**
         * ???????????????
         * @method init
         * @return undefined
         */
        init: function() {
            //?????????????????????
            this.pages = this.pages ? this.pages : Math.ceil(this.items / this.itemsOnPage) ? Math.ceil(this.items / this.itemsOnPage) : 1;
            this.currentPage = this.currentPage - 1;
            this.halfDisplayed = this.displayedPages / 2;

            //??????render???????????????paginate
            this.render();
        },

        /**
         * ????????????
         *
         * @method render
         * @return undefined
         */
        render: function() {
            var interval = this._getInterval(this),
                that = this._el,
                i;
            this.destroy.call(this._el);
            var $panel = that.prop("tagName") === "UL" ? this._el : $("<ul></ul>").appendTo(that);

            // ?????????????????????
            if (this.prevText) {
                this._appendItem.call(this, this.currentPage - 1, {text: "?????????", classes: "prev", title: "?????????"});
            }

            //?????????????????????????????????
            if (interval.start > 0 && this.edges > 0) {
                var end = Math.min(this.edges, interval.start);
                for (i = 0; i < end; i++) {
                    this._appendItem.call(this, i);
                }
                if (this.edges < interval.start && (interval.start -this.edges != 1)) {
                    $panel.append("<li class='disabled'><span class='ellipse'>" + this.ellipseText + "</span></li>");
                } else if (interval.start - this.edges == 1) {
                    this._appendItem.call(this, this.edges);
                }
            }

            // ????????????????????????
            for (i = interval.start; i < interval.end; i++) {
                this._appendItem.call(this, i);
            }

            // ?????????????????????????????????
            if (interval.end < this.pages && this.edges > 0) {
                if (this.pages - this.edges > interval.end && (this.pages - this.edges - interval.end != 1)) {
                    $panel.append("<li class='disabled'><span class='ellipse'>" + this.ellipseText + "</span></li>");
                } else if (this.pages - this.edges - interval.end == 1) {
                    this._appendItem.call(this, interval.end++);
                }
                var begin = Math.max(this.pages - this.edges, interval.end);
                for (i = begin; i < this.pages; i++) {
                    this._appendItem.call(this, i);
                }
            }

            // ?????????????????????
            if (this.nextText) {
                this._appendItem.call(this, this.currentPage + 1, {text: "?????????", classes: "next", title: "?????????"});
            }

            //???????????????????????????
            if (this.inputText) {
                this._addPageInput.call(this);
            }
        },

        /**
         * ??????????????????????????????
         * @required
         * @method _getInterval
         * @return Object
         */
        _getInterval: function() {
            return {
                start: Math.ceil(this.currentPage > this.halfDisplayed ? Math.max(Math.min(this.currentPage - this.halfDisplayed, (this.pages - this.displayedPages)), 0) : 0),
                end: Math.ceil(this.currentPage > this.halfDisplayed ? Math.min(this.currentPage + this.halfDisplayed, this.pages) : Math.min(this.displayedPages, this.pages))
            };
        },

        /**
         * ??????
         * @method destroy
         * @return dom object
         */

        destroy: function() {
            $(this).find("a").off("click");
            this.empty();
            return this;
        },

        /**
         * ????????????
         * @method rerender
         * @return dom object
         */

        rerender: function() {
            this.render.call(this);
            return this;
        },

        /**
         * ????????????
         * @method _appendItem
         * @return undefined
         */
        _appendItem: function(pageIndex, opts) {
            var that = this,
                self = that._el,
                options,
                $link,
                $linkWrapper = $("<li></li>"),
                $ul = self.find("ul");
            pageIndex = pageIndex < 0 ? 0 : (pageIndex < this.pages ? pageIndex : this.pages - 1);
            options = $.extend({
                text: pageIndex + 1,
                classes: "",
                title: ""
            }, opts || {});
            if (pageIndex == this.currentPage) {
                if (this.disabled) {
                    $linkWrapper.addClass("disabled");
                } else {
                    $linkWrapper.addClass("active");
                }
                $link = $("<span class='current'>" + (options.text) + "</span>");
            } else {
                $link = $("<a href='" + this.hrefTextPrefix + (pageIndex + 1) + location.hash + this.hrefTextSuffix + "' class ='page-link'>" + (options.text) + "</a>");
                $link.bind("click", function(e) {
                    e.preventDefault();
                    that._selectPage(pageIndex, $(this));
                });
            }
            if (options.classes) {
                $link.addClass(options.classes);
            }
            if (options.title) {
                $link.attr("title", options.title);
            }
            $linkWrapper.append($link);
            if ($ul.length) {
                $ul.append($linkWrapper);
            } else {
                self.append($linkWrapper);
            }
        },

        /**
         * ?????????????????????
         * @method _addPageInput
         * @return undefined
         */
        _addPageInput: function() {
            var that = this,
                self = that._el.prop("tagName") === "UL" ? this._el : this._el.find("ul");
                $pageInput = $("<li class='to'><span class='graytext'>???</span></li><li class='wrapinput'><input type='' class='pageinput' /></li><li class='pgcoder'><span class='graytext'>???</span></li>");
            self.append($pageInput);
            var pgInputtext = $pageInput.find("input");
            pgInputtext.on("keydown", function(e) {//?????????????????????
                var keyPressed = e.which,
                    $thisVal = $(this).val(),
                    hasDecimalPoint = $(this).val().indexOf('.') == -1;
                if (keyPressed == 46 || keyPressed == 8 || ((keyPressed == 190 || keyPressed == 110) && (!hasDecimalPoint)) || keyPressed == 9 || keyPressed == 27 || (keyPressed == 65 && e.ctrlKey === true) || (keyPressed >= 35 && keyPressed <= 39)) {
                    return;
                } else {
                    if (e.shiftKey || (keyPressed < 48 || keyPressed > 57) && (keyPressed < 96 || keyPressed > 105)) {
                        e.preventDefault();
                    }
                }
            });
            pgInputtext.on("keyup", function(e) {//?????????????????????
                var keyPressed = e.which,
                    $thisVal = parseInt($(this).val(), 10);
                if ($thisVal > that.pages) {
                    $thisVal = that.pages;
                }
                if ($thisVal == 0) {
                    $thisVal = 1;
                }
                if (!isNaN($thisVal) &&keyPressed == 13) {
                    return that._selectPage.call(that, $thisVal - 1, e);
                }
            });
        },

        /**
         * ????????????
         *
         * @event click callback
         * @param {pageIndex} ????????????
         * @param {e} ????????????
         */
        _selectPage: function(pageIndex, $obj) {
            this.currentPage = pageIndex;
            if(this.selectOnClick) {
                this.render.call(this);
            }
            if ($(".pageinput").length) {
                $(".pageinput").focus();
            }
            return this.onPageClick(pageIndex + 1, $obj, this);
        }
    });

    /**
     * ??????????????????X???, ???????????????: X.paginate

     *
        new X.paginate('#mypaginate',{
            pages: 400,
            onPageClick: function (pageNumber, event) {//?????????????????????
            }
        });
     *
     * @extends X ?????????X
     * @class  paginate????????????
     */
    
    Firstp2p.paginate = function(el, opts) {
        return new paginate(el, opts);
    }
})();;//?????????
;(function($) {
    $(function() {
        if( !!window.ActiveXObject && !window.XMLHttpRequest){
              return;
        }
        var bool=true;
        $('<div class="layAppTopnew">\
        <div class="app">\
            <a class="app_btn" href="javascript:void(0)"></a>\
            <div class="app_box">\
                <i class="icon_a"></i>\
                <div class="app_tab_con"></div>\
                <h2>????????????APP</h2>\
            </div>\
        </div>\
        <div class="serve_box">\
            <a class="serve" href="javascript:void(0)"></a>\
            <div class="serve_con">\
                <i class="icon_a"></i>\
                <h2>95782</h2>\
                <p>???????????? 8:00-20:00</p>\
            </div>\
        </div>\
        </div>').appendTo($("body"));
        $appDownload=$('<a class="backToTop" href="javascript:void(0)"></a>').appendTo($(".layAppTopnew")).hide();
            //????????????????????????????????????
        if ($('#isNewUser_hidden').val()==1){
            if ($('.JS_is_wxlc').val()==1){
                $('<a href="/activity/NewUserPage" class="isNewUser_11626 abs newbie"></a>').appendTo($(".layAppTopnew"));
            }else if ($('#JS_is_firstp2p').val()==1){
                $('<a href="/activity/NewUserP2p" class="isNewUser_11626 p2p"></a>').appendTo('.layAppTopnew');
            } else {
                $('<a href="/activity/NewUserPage" class="isNewUser_11626 fixed"></a>').appendTo('body');
            }
        }
        
            $('.app_btn').hover(function(){
                // $('.app_box').stop().show();
                //$(".app_box").stop().animate({left:"-137px","opacity":"1"});              
                var $img = $("<img />");
                if(bool){
                    bool=false;
                    $img.attr("src","../static/v2/images/common/download.png");
                    $(".app_tab_con").append($img);
                    }               
            },function(){
            })
            
        $backToTopEle = $('.backToTop').click(function() {
            $("html,body").animate({
                    scrollTop: 0
                },
                300);
        });
        $(window).bind("scroll",
            function() {  
                var st = $(document).scrollTop();
                var winh = $(window).height();
                var ban= $(".banner_slide").length;
                if(st > 300){
                        $('.layAppTopnew').css({"height":"212px"});
                        $appDownload.show();
                    }else{
                        $appDownload.hide();
                        $('.layAppTopnew').css({"height":"141px"});
                    }
        });
    })  
})(jQuery);
;var LANG = {"LOGIN":"??????","TOURIST":"??????","LOGINOUT":"??????","REGISTER":"??????","FREE_REGISTER":"????????????","SWITCH_CITY":"????????????","OTHER_CITY":"????????????","SHARE_TO":"?????????","ORIGIN_PRICE":"??????","CURRENT_PRICE":"??????","DISCOUNT":"??????","SAVE_PRICE":"??????","DISCOUNT_OFF":"???","TIME_LEFT":"????????????","DEAL_NOT_BEGIN":"?????????????????????????????????","DEAL_BEGIN_FORMAT":"??????%s??????","DEAL_BID_COUNT":"?????????","MUST_NEED_BID":"??????","DEAL_BID_SUCCESS":"???????????????","CANT_BID_BY_YOURSELF":"??????????????????????????????","DEAL_SUCCESS_CONTINUE_BUY":"????????????????????????????????????","DEAL_FAILD_OPEN":"?????????????????????","DEAL_BID_FULL":"?????????","SUCCESS_TIME_TIP":"%s ?????????????????????%s","GO_TOP":"??????","DEAL_DETAIL":"????????????","OPEN_TIME":"????????????","BUS_ROUTE":"????????????","ORDER_TRACT":"????????????","SUPPLIER_DETAIL":"????????????","MORE_SUPPLIER_DETAIL":"??????????????????????????????","SIDE_DEAL_LIST":"??????????????????","INVITE_REFERRALS":"????????????","INVITE_REFERRALS_TIP":"????????????????????????????????? ,????????????<strong>%s</strong>??????","CLICK_GET_REFERRALS":"????????????????????????????????????","DEAL_MESSAGE":"????????????","ALL_MESSAGE":"????????????","ASK":"????????????","MAIL_SUBMIT":"????????????","MAIL_SUBMIT_TIP":"??????????????????????????????????????????","DEAL_COOPERATION":"????????????","DEAL_COOPERATION_TIP":"??????????????????????????????<br/>???<a href=\"%s\">????????????</a>?????????????????????","DEAL_CURRENT":"????????????","DEAL_ORDER":"????????????","DEAL_SECOND":"????????????","DEAL_ORDER_TITLE":"??????","DEAL_SECOND_TITLE":"??????","ORDER_PRICE":"??????","SECOND_PRICE":"?????????","NO_DEAL_TIP":"%s???????????????????????????????????????????????????????????? :)","DEAL_LIST":"????????????","WEEK_1":"?????????","WEEK_2":"?????????","WEEK_3":"?????????","WEEK_4":"?????????","WEEK_5":"?????????","WEEK_6":"?????????","WEEK_0":"?????????","ADD_FIRST_MESSAGE":"????????????","NO_END_TIME":"??????????????????","FEEDBACK":"????????????","USER_REGISTER":"????????????","USER_LOGIN":"????????????","CLOSE":"??????","OR":"??????","PLEASE_LOGIN":"?????????","HAVE_ACCOUNT":"?????????????????????","ERROR_TITLE":"????????????","SUCCESS_TITLE":"????????????","JUMP_TIP":"???????????? %s ?????????????????? <a href=\"%s\">??????</a> ??????","USER_PWD_CONFIRM_ERROR":"??????????????????","EMPTY_ERROR_TIP":"%s????????????","FORMAT_ERROR_TIP":"%s??????????????????????????????","EXIST_ERROR_TIP":"%s???????????????????????????","USER_PWD_ERROR":"???????????????","IDNO_ERROR":"???????????????????????????????????????","IDNO_LINK_ERR":"??????????????????????????????","USER_TITLE_USER_NAME":"??????","USER_TITLE_USER_NAME_TIP":"3-15???????????????????????????????????????","USER_TITLE_EMAIL":"Email","USER_TITLE_EMAIL_TIP":"???????????????????????????????????????","USER_TITLE_MOBILE":"????????????","USER_TITLE_USER_PWD":"??????","USER_TITLE_USER_PWD_TIP":"?????? 4 ????????? ","USER_TITLE_USER_CONFIRM_PWD":"????????????","USER_TITLE_MOBILE_TIP":"??????????????????????????????????????????","SIGNUP_CITY":"????????????","REGISTER_SUCCESS":"????????????","SUBMIT_DEAL":"???????????????????????????","FORGET_PASSWORD":"?????????????","AUTO_LOGIN":"?????????????????????","NOT_REGISTER":"??????????????????","REGISTER_RIGHT_NOW":"??????<a href=\"%s\">??????</a>?????????30??????","ALREADY_LOGIN":"????????????","LOGIN_SUCCESS":"????????????","USER_NOT_EXIST":"???????????????","PASSWORD_ERROR":"????????????","USER_NOT_VERIFY":"?????????????????????","LOGINOUT_SUCCESS":"????????????","PLEASE_LOGIN_FIRST":"????????????","SELECT_AND_ADDCART":"????????????????????????","CART_LIST":"?????????","OK_POST":"???????????????","MESSAGE_LOGIN_TIP":"	??????<a href=\"%s\">??????</a>??????<a href=\"%s\">??????</a>?????????","MESSAGE_POST_SUCCESS":"????????????","REPLY":"??????","REPLY_POST_SUCCESS":"????????????","MESSAGE_CONTENT_EMPTY":"??????????????????","CART_CHECK":"????????????","DEAL_ERROR_1":"???????????????","DEAL_ERROR_2":"?????????","DEAL_ERROR_3":"?????????","DEAL_ERROR_4":"????????????????????????","DEAL_ERROR_5":"???????????????????????????","DEAL_ERROR_6":"???????????????????????????","DEAL_ERROR_COMMON":"???????????????????????????????????????????????????????????????????????????????????????????????????????????????","DEAL_USER_MAX_BOUGHT":"?????????????????????%s???","DEAL_USER_MIN_BOUGHT":"????????????????????????%s???","DEAL_MAX_BOUGHT":"????????????%s???","NO_ARTICLE":"??????????????????","MESSAGE_SUBMIT_FAST":"?????????????????????????????????","REFERRAL_PAGE":"????????????","COPY":"??????","SHARE_LINK":"?????????????????????????????????????????? MSN ??? QQ ???????????????","LOTTERY_SHARE_LINK":"??????????????????????????????????????????????????????????????????????????????????????????","SHARE_REFERRAL":"????????????????????????????????????????????????","JS_COPY_SUCCESS":"???????????????","JS_COPY_NOT_SUCCESS":"????????????????????????????????????","JS_NO_ALLOW":"?????????????????????\n ??????????????????????????????\'about:config\'?????????\n?????????\'signed.applets.codebase_principal_support\'?????????\'true\'","MAIL_SUBSCRIBE":"??????????????????????????????","SMS_SUBSCRIBE":"????????????","SMS_UNSUBSCRIBE":"????????????","CONFIRM_BUY":"?????????????????????","MAIL_ADDRESS":"????????????","MAIL_ADDRESS_TIP":"????????????????????????????????????????????????","SELECT_YOUR_CITY":"??????????????????","SUBSCRIBE":"??????","SUBMIT_TOO_FAST":"???????????????","EMAIL_EMPTY_TIP":"????????????????????????","EMAIL_FORMAT_ERROR_TIP":"????????????????????????","SUBSCRIBE_SUCCESS":"????????????","REFRESH_TOO_FAST":"????????????","FILL_CORRECT_CONSIGNEE_ADDRESS":"??????????????????????????????????????????","FILL_CORRECT_CONSIGNEE":"??????????????????","FILL_CORRECT_ADDRESS":"???????????????","FILL_CORRECT_ZIP":"???????????????","FILL_MOBILE_PHONE":"?????????????????????","FILL_CORRECT_MOBILE_PHONE":"??????????????????????????????","PLEASE_SELECT_DELIVERY":"?????????????????????","PLEASE_SELECT_PAYMENT":"?????????????????????","PAY_TOTAL_PRICE":"????????????","PAY_NOW":"????????????","NOTICE_SN_NOT_EXIST":"????????????????????????","PAY_SUCCESS":"????????????","PAYMENT_NOT_EXIST":"???????????????????????????????????????????????????","ORDER_SN":"?????????","NOTICE_SN":"????????????","MODIFY_PAYMENT_TYPE":"????????????????????????","OTHER_DEAL":"????????????","MY_ORDERS":"????????????","NOTICE_PAY_SUCCESS":"?????????????????????,????????????????????????????????????????????????????????????????????????????????????","PAYMENT_INCHARGE":"%s????????????????????????????????????????????????????????????","DELETE_INCHARGE":"%s???????????????????????????????????????????????????????????????","OUTOFSTOCK_INCHARGE":"%s???????????????????????????????????????????????????????????????","OUTOFMONEY_INCHARGE":"%s????????????????????????????????????????????????????????????","MONEYORDER_INCHARGE":"%s????????????","SUCCESS_BUY_COUNT_TIP":"??????%s???????????????????????????","UNSUCCESS_BUY_COUNT_TIP":"??????%s?????????","NO_BEGIN_TIME":"??????????????????","ORDER_RETURN_MONEY":"%s???????????????","ORDER_RETURN_SCORE":"%s???????????????","PAY_SUCCESS_CONGRATUATION":"????????????????????????","YOU_HAVE_GOT_COUPON":"????????????%s","YOU_HAVE_GOT_COUPON_TIP":"??????%s?????????????????????????????????????????????????????????????????????","GET_COUPON":"??????%s","PRINT_COUPON":"?????????????????????????????? %s ???????????????%s","MAIL_COUPON":"???????????????%s???????????? %s ?????????%s","SMS_COUPON":"???????????????%s???????????? %s ?????????%s","SMS":"??????","MAIL":"??????","PRINT":"??????","USER_COUPON_LIST":"????????????","FORM_TOPIC":"??????","FORM_EVENT":"??????","UC_CENTER":"????????????","UC_TOPIC":"????????????","UC_CENTER_F":"??????%s","UC_ORDER":"????????????","UC_MONEY":"????????????","UC_ACCOUNT":"????????????","UC_MOBILE":"????????????","UC_SETWEIBO":"???????????????","UC_INVITE":"????????????","UC_VOUCER":"???????????????","UC_CONSIGNEE":"????????????","UC_CARRY":"????????????","UC_COLLECT":"???????????????","UC_INVEST":"????????????","UC_EARNINGS":"????????????","UC_AUTO_BID":"????????????","UC_MSG":"??????","UC_DEAL_REFUND":"????????????","UC_DEAL_BORROWED":"??????????????????","UC_DEAL_GUARANTOR":"???????????????","UC_DEAL_BORROW_STAT":"????????????","UC_NOTICE":"??????","UC_PRIVATE_MSG":"??????","UC_MSG_SETTING":"????????????","UC_MSG_SETTING_TIPS":"??????????????????","UC_CREDIT":"??????","UC_MEDAL":"????????????","SAVE_USER_SUCCESS":"????????????????????????","UC_LOGS":"????????????","UC_MONEY_INCHARGE":"????????????","PLEASE_INPUT_CORRECT_INCHARGE":"??????????????????????????????","UC_ORDER_VIEW":"????????????","ORDER_PAY_STATUS_0":"?????????","ORDER_PAY_STATUS_1":"????????????","ORDER_PAY_STATUS_2":"?????????","UC_BANK":"?????????????????????","UC_ORDER_MODIFY":"????????????","ORDER_DELIVERY_STATUS_0":"?????????","ORDER_DELIVERY_STATUS_1":"????????????","ORDER_DELIVERY_STATUS_2":"????????????","ORDER_DELIVERY_STATUS_5":"????????????","ORDER_ORDER_STATUS_0":"?????????","ORDER_ORDER_STATUS_1":"?????????","USER_INCHARGE_DONE":"?????????%s????????????","USER_INFO":"????????????","USER_GROUP":"?????????????????????","USER_SCORE":"????????????","USER_MONEY":"????????????","USER_REFERRAL_TOTAL":"??????????????????","LAST_LOGIN_IP":"????????????IP","WAIT_VERIFY_USER":"?????????????????????","REFERRALS_LOG":"??????%s?????????%s?????????????????????%s","CONFIRM_ARRIVAL":"????????????","ARRIVALED":"?????????","INVALID_MESSAGE_TYPE":"?????????????????????","UC_VOUCHER":"???????????????","UC_MESSAGE":"????????????","UC_VOUCHER_INCHARGE":"???????????????","UC_VOUCHER_EXCHARGE":"???????????????","VOUCHER_INCHARGE_LOG":"???????????????%s??????%s","INVALID_VOUCHER":"??????????????????","EXCHANGE_VOUCHER_LIMIT":"????????????????????????%s???","EXCHANGE_VOUCHER_USE_SCORE":"??????[%s]???????????????%s??????","EXCHANGE_SUCCESS":"????????????","EXCHANGE_FAILED":"????????????","VERIFY_CODE_ERROR":"???????????????","DEAL_ITEM":"??????","PRICE":"??????","AVG_PRICE":"??????","AVG_PRICE_0":"--","AVG_PRICE_1":"50??????","AVG_PRICE_2":"51-100","AVG_PRICE_3":"101-200","AVG_PRICE_4":"201-400","AVG_PRICE_5":"400??????","TOTAL_PRICE":"??????","CONSIGNEE_INFO":"????????????","CONSIGNEE":"?????????","REGION_INFO":"????????????","REGION_LV1":"??????","REGION_LV2":"???","REGION_LV3":"???","REGION_LV4":"???","SELECT_PLEASE":"?????????","ADDRESS":"????????????","PHONE":"??????","REAL_NAME":"????????????","ZIP":"??????","MOBILE":"??????","DELIVERY_INFO":"????????????","NO_SUPPORT_DELIVERY":"????????????????????????????????????","DELETE":"??????","ORDER_MEMO":"????????????","PAYMENT_INFO":"????????????","DEAL_TOTAL_PRICE":"????????????","DELIVERY_FEE":"??????","PAYMENT_FEE":"?????????","USER_DISCOUNT":"????????????","ACCOUNT_PAY_AMOUNT":"??????????????????","ECV_PAY_AMOUNT":"?????????????????????","ACCOUNT_PAY":"????????????","ECV_PAY":"???????????????","PAY_TOTAL_PRICE_ORDER":"???????????????","PAYMENT_BY":"?????? [%s] ??????","RETURN_TOTAL_MONEY":"???????????????????????????","RETURN_TOTAL_SCORE":"???????????????????????????","SAVE_AND_NEXT":"???????????????","SAVE":"??????","IDNO":"????????????","TWO_ENTER_IDNO_ERROR":"?????????????????????????????????!","UC_WORK_AUTH":"????????????","OFFICE":"????????????","JOBTYPE":"????????????","WORK_REGION":"????????????","OFFICETYPE":"????????????","OFFICEDOMAIN":"????????????","OFFICECALE":"????????????","POSITION":"??????","SALEAY":"?????????","WORKYEAR":"????????????????????????","WORKPHONE":"????????????","WORKEMAIL":"????????????","OFFICEADDRESS":"????????????","URGENTCONTACT":"??????","URGENTRELATION":"??????","URGENTMOBILE":"??????","INCHARGE_TO_SITE":"?????????%s????????????????????????","UC_ECV_INCHARGE":"???????????????","YOUR_TOTAL_MONEY":"???????????????????????????","YOUR_TOTAL_SCORE":"???????????????","MODIFY":"??????","USER_TITLE_USER_NEW_PWD":"?????????","USER_TITLE_USER_NEW_PWD_TIP":"??????????????????????????????????????????","COUPON_SN":"??????????????????","COUPON_PWD":"??????","COUPON_BEGIN_TIME":"????????????","COUPON_END_TIME":"????????????","COUPON_USE_TIME":"????????????","OPERATION":"??????","HAS_VALID":"?????????","NEVER_EXPIRED":"????????????","NOT_USED":"?????????","SEND_COUPON_SMS":"?????????","SEND_COUPON_MAIL":"?????????","VIEW":"??????","TOTAL_REFERRALS":"?????????","REGISTER_TIME":"????????????","REFERRALS_GEN":"?????????","REFERRALS_PAID":"?????????","REFERRALS_ORDER":"????????????","REFERRALS_GEN_TIME":"????????????","REFERRALS_PAID_TIME":"????????????","REFERRALS_STATUS":"??????","YES":"???","NO":"???","INCHARGE_SN":"??????","INCHARGE_MONEY":"????????????","PAYMENT_STATUS":"????????????","PAYMENT_GEN_TIME":"????????????","PAYMENT_PAID_TIME":"????????????","PAYMENT_SN":"????????????","PAID_INCHARGE_DONE":"????????????","PAID":"??????","PRICE_AMOUNT":"????????????","DO_INCHARGE":"??????","EVENT":"??????","SCORE":"??????","CREATE_TIME":"??????","ORDER_CREATE_TIME":"????????????","ORDER_STATUS":"????????????","CANCEL":"??????","PAY_STATUS":"????????????","CONTINUE_PAY":"????????????","DELIVERY_STATUS":"????????????","ADMIN_LAST_OP_TIME":"???????????????????????????","UNIT_PRICE":"??????","NUMBER":"??????","TOTAL_SCORE":"????????????","DELIVERY_NOTICE":"????????????","VOUCHER_NAME":"???????????????","VOUCHER_MONEY":"??????","EXCHANGE_SCORE_VOUCHER":"????????????","DO_EXCHANGE":"??????","VOUCHER_SN":"??????????????????","VOUCHER_PASSWORD":"??????","RESET":"??????","VOUCHER_INFO":"???????????????","VOUCHER_TIME":"????????????","VOUCHER_USE_LIMIT":"????????????","VOUCHER_USE_COUNT":"????????????","TO":"???","CONFIRM_ORDER_AND_PAY":"?????????????????????","BACK_MODIFY_CART":"??????????????????","HOUR":"???","MIN":"???","DAY":"???","SEC":"???","RESET_EMAIL_TIP":"?????????????????? Email ??????","RESET_PASSWORD":"????????????","MOBILE_NUMBER":"?????????","MOBILE_EMPTY_TIP":"????????????????????????","VERIFY_CODE":"?????????","QULIFY_CODE_HAS_SEND":"?????????????????????????????????","QULIFY_CODE":"?????????","PLEASE_ENTER_QULIFY_CODE":"??????????????????????????????","SMS_SUBSCRIBE_SUCCESS":"?????????????????????","SMS_UNSUBSCRIBE_SUCCESS":"?????????????????????","SMS_SUBSCRIBE_SUCCESS_TIP":"????????????????????????????????????????????????","RESEND_VERIFY_MAIL":"????????????????????????","NOT_VERIFY_EMAIL":"????????????%s??????????????????????????????????????????????????????????????????????????????????????????????????????","SELECT_LOCATION":"???????????????????????????????????????","SEND_SUCCESS":"????????????","SEND_EXCEED_LIMIT":"??????????????????","SMS_NOT_ALLOW":"???????????????????????????","MAIL_NOT_ALLOW":"???????????????????????????","NO_SUPPLIER_LOCATION":"???????????????????????????????????????????????????","DEAL_COUPON_PRINT":"?????????????????????","INVALID_ACCESS":"????????????","UPDATE_SUCCESS":"????????????","SUBSCRIBE_FAILED":"????????????","MOBILE_NOT_SUBSCRIBE":"????????????????????????","SORRY_YOU_R_LATE":"?????????????????????","ALL_SOLD_OUT":"????????????????????????","DONT_MISS_DEAL":"?????????????????????????????????????????????????????????????????????","YOU_PAY_ORDER_SUCCESS":"?????????????????????????????????????????????","VIEW_COUPON_DATA":"??????????????????%s","INVALID_ORDER_DATA":"?????????????????????","INVALID_DELIVERY_DATA":"???????????????","CONFIRM_ARRIVAL_SUCCESS":"??????????????????","CANCEL_ORDER_SUCCESS":"??????????????????","CANCEL_ORDER_FAILED":"??????????????????","REGISTER_MAIL_SEND_SUCCESS":"????????????????????????????????????","NEXT_TO_VERIFY":"???????????????????????????Email","SENT_VERIFY_MAIL_FOR_VERIFY":"????????????????????????????????????%s???????????????????????????????????????????????????????????????????????????","GO_ACTIVE_NOW":"???????????????????????????","NOT_RECIEVE_EMAIL":"??????????????????","MAIL_IS_CONSIDER_AS_TRASH":"???????????????????????????????????????????????????????????????????????????","NO_RECIEVE_EMAIL_TIP":"???  %s ??? %s ?????????????????????????????????????????????????????????????????????","ALL":"??????","ALL_DEALS":"??????????????????","LAST_SUCCESS_DEALS":"??????????????????","YOU_GOT_COUPON":"?????????????????????","PAYMENT_NOTICE":"????????????","CANCEN_EMAIL":"????????????","MAIL_UNSUBSCRIBE":"????????????","MAIL_NOT_EXIST":"??????????????????","MAIL_UNSUBSCRIBE_VERIFY":"????????????????????????????????????????????????","MAIL_UNSUBSCRIBE_SUCCESS":"???????????????????????????","MAIL_UNSUBSCRIBE_FAILED":"??????????????????","SCORE_NOT_ENOUGHT":"???????????????????????????","MONEY_NOT_ENOUGHT":"???????????????????????????","CARRY_MONEY_NOT_ENOUGHT":"????????????????????????","CARRY_MONEY_NOT_TRUE":"?????????????????????","CARRY_MONEY_DECIMAL":"????????????????????????????????????????????????","BID_MONEY_NOT_TRUE":"??????????????????????????????","LAST_BID_AT_ONCE_NOT_TRUE":"????????????????????????%s","BID_MONEY_TIMES_NOT_TRUE":"????????????????????????%d????????????","PLASE_ENTER_CARRY_BANK":"???????????????","PLASE_ENTER_CARRY_BANK_CODE":"?????????????????????","PLASE_ENTER_CARRY_CFR_BANK_CODE":"???????????????????????????","TWO_ENTER_CARRY_BANK_CODE_ERROR":"?????????????????????????????????!","CARRY_BANK_ERR":"???????????????????????????!","CARRY_SUBMIT_SUCCESS":"???????????????????????????????????????????????????","MIN_CARRY_MONEY":"?????????????????????0.1???","SCORE_LIST":"????????????","EXCHANGE_COUNT":"????????????","EXCHANGE_SCORE":"??????","MAX_EXCHANGE":"??????","NO_LIMIT":"?????????","VIEW_MAP":"????????????","VERIFY_COUPON":"???????????????","COUPON_INVALID":"?????????????????????????????????","COUPON_IS_VALID":"[ %s ] ???????????? %s ??????????????????","SUPPLIER_LOGIN":"????????????","COUPON_INVALID_SUPPLIER":"??????????????????????????????????????????????????????","COUPON_INVALID_USED":"????????????%s?????????","COUPON_USED_OK":"??????????????????????????????%s","SUPPLIER_ACCOUNT":"????????????","SUPPLIER_PASSWORD":"????????????","PLEASE_ENTER_ACCOUNT":"?????????????????????","PLEASE_ENTER_PASSWORD":"?????????????????????","SUPPLIER_LOGIN_FAILED":"??????????????????","SUPPLIER_MODIFY_PWD":"??????????????????","SUPPLIER_NOT_LOGIN":"???????????????","SUPPLIER_CONFIRM_PASSWORD":"????????????","SUPPLIER_NEW_PASSWORD":"?????????","DOMODIFY":"??????","PLEASE_ENTER_NEW_PASSWORD":"??????????????????","NEW_PASSWORD_CONFIRM_FAILED":"??????????????????","PASSWORD_MODIFY_SUCCESS":"??????????????????","SUPPLIER":"?????????","HELLO":"??????","MODIFY_PASSWORD":"????????????","COUPON_VERIFY_TIP":"????????????????????????????????????   ????????????????????????????????????","COUPON_SN_SHORT":"???????????????","COUPON_PWD_SHORT":"???????????????","COUPON_SEACHER":"??????(????????????????????????)","COUPON_CONFIRM":"????????????(?????????,??????????????????)","ORDER_TYPE_1":"??????","ORDER_TYPE_2":"??????","ORDER_TYPE_3":"??????","AFTER_SALE":"????????????","AFTER_SALE_1":"?????????","AFTER_SALE_2":"?????????","AFTER_SALE_3":"?????????,?????????","AFTER_SALE_0":"??????","ADMIN_MEMO":"???????????????","BUY_COUNT_NOT_GT_ZERO":"????????????????????????","INSUFFCIENT_SCORE":"????????????","SHOP_CLOSE":"??????????????????","VOTE":"?????????","NO_VOTE":"??????????????????????????????","OTHER_VOTE":"??????","OK_AND_SUBMIT":"???????????????","VOTE_SUCCESS":"??????????????????","YOU_VOTED":"?????????????????????","YOU_DONT_CHOICE":"???????????????????????????","NO_THIS_USER":"???????????????","VERIFY_SUCCESS":"????????????","VERIFY_FAILED":"????????????","HAS_VERIFIED":"????????????","SEND_HAS_SUCCESS":"??????????????????????????????","GET_PASSWORD_BACK":"????????????","MAIL_FORMAT_ERROR":"??????????????????","NO_THIS_MAIL":"?????????????????????","SET_NEW_PASSWORD":"???????????????","PASSWORD_VERIFY_FAILED":"??????????????????","NEW_PWD_SET_SUCCESS":"??????????????????","NEW_PWD_SET_FAILED":"??????????????????","MY_ACCOUNT":"????????????","BUY_NUMBER":"????????????","IS_VALID":"??????","IS_INVALID_ECV":"??????????????????","FRIEND_LINK":"????????????","SITE":"???","DEAL_HISTORY_LIST":"????????????","DEAL_NOTICE_LIST":"????????????","SECOND_LIST":"????????????","SECOND_HISTORY_LIST":"??????????????????","SECOND_NOTICE_LIST":"????????????","ORDER_LIST":"????????????","ORDER_HISTORY_LIST":"??????????????????","ORDER_NOTICE_LIST":"????????????","SINGLE_STYLE_TIP":"???????????????????????????","MULTI_STYLE_TIP":"???????????????????????????","KEYWORD":"?????????","TIME":"??????","SEARCH":"??????","NO_DEALS":"??????????????????????????????","SAVE_PRICE_TOTAL":"??????????????????","DEAL_SCORE":"????????????","SCORE_EXPIRED":"???????????????????????????","INVALID_PAYMENT":"?????????????????????","SUPPLIER_ORDER_LIST":"????????????","SUPPLIER_ORDER_VIEW":"????????????","SUPPLIER_COUPON_LIST":"???????????????","SUPPLIER_ORDER_SN":"?????????","SUPPLIER_COUPON_SN":"??????????????????","SUPPLIER_USER_NAME":"????????????","SUPPLIER_DEAL_NAME":"????????????","USER_LOGIN_BIND":"?????????????????????????????????","REGISTER_BIND":"???????????????","INVALID_VISIT":"????????????","UC_AVATAR":"????????????","VIEW_DETAIL":"??????","CAN_USED":"????????????","DISPLAY_STYLE":"????????????","BEFORE_BUY":"????????????","AFTER_BUY":"????????????","AFTER_BUY_MESSAGE_TIP":"?????????????????????????????????????????????","SUPPLIER_NAME":"????????????","CONTACT_NAME":"?????????","CONTACT":"????????????","DEAL_INFO":"????????????","CONTACT_TIP":"???????????????????????????, ???:??????,QQ ???????????????????????????","DEAL_CITY":"????????????","SUPPLIER_NAME_EMPTY":"?????????????????????","CONTACT_NAME_EMPTY":"??????????????????","CONTACT_EMPTY":"?????????????????????","SUPPLIER_LIST":"????????????","SUPPLIER_TEL":"??????","SUPPLIER_CONTACT":"?????????","SUPPLIER_ADDRESS":"??????","SUPPLIER_DEAL_TOTAL":"????????????%s?????????","SUPPLIER_DEAL_PRICE":"?????????","SUPPLIER_COMMENT_TOTAL":"??????%s????????????","NO_COMMENT":"?????????????????????","COMMENT_TYPE":"????????????","COMMENT":"??????","COMMENT5":"??????","COMMENT3":"??????","COMMENT1":"??????","COMMENT0":"???","NO_SUPPLIER":"???????????????","SUPPLIER_LOCATIONS":"????????????","SUPPLIER_DEALS":"??????????????????","LOADING":"??????????????????...","SUPPLIER_COMMENT_SAY":"??????","SUPPLIER_COMMENT_SAY_TO_TOPIC":"???%s???%s????????????","USER_FAV_THIS":"???%s??????????????????","USER_RELAY_THIS":"???%s?????????????????????","SUPPLIER_DAY":"???","SUPPLIER_MIN":"?????????","SUPPLIER_HOUR":"?????????","SUPPLIER_SEC":"??????","SUPPLIER_MON":"???","SUPPLIER_YEAR":"???","ADMIN_REPLY":"???????????????","ADD_SUPPLIER_COMMENT":"????????????","COMMENT_EMPTY_TIP":"????????????????????????","SUPPLIER_COMMENT":"????????????","VIEW_SUPPLIER_INFO":"??????????????????","DEAL_COMMENT":"????????????","ADD_FIRST_MESSAGE_BEFORE_BUY":"?????????????????????????????????","ADD_FIRST_MESSAGE_AFTER_BUY":"?????????????????????????????????","PAYMENT_TIP":"????????????","PAYMENT_INFO_TIP":"??????????????????????????????????????????","PAYMENT_NOTICE_TIP_1":"??????????????????????????????????????????","PAYMENT_NOTICE_TIP_2":"????????????????????????????????????????????????????????????","PAYMENT_HAS_DONE":"???????????????","PAYMENT_ERROR":"???????????????????????????????????????","PAYMENT_NOT_PAID_RENOTICE":"??????????????????????????????????????????????????????????????????????????????","CN_LANG":"???????????????","NO_COUPON_GEN":"??????????????????","SUPPLIER_DEAL_LIST":"????????????","COUPON_USED":"????????????","NOT_BEGIN_DEAL":"?????????","BEGAN_DEAL":"?????????","SALE_TOTAL":"????????????","COUPON_TOTAL":"?????????","COUPON_CONFIRM_COUNT":"?????????","COUPON_NOT_BEGIN":"?????????","COUPON_ENDED":"?????????","COUPON_MOBILE":"????????????????????????","COUPON_MOBILE_TIP":"??????????????????????????????%s????????????????????????????????????????????????????????????","COUPON_MOBILE_EMPTY":"???????????????????????????????????????????????????????????????","TRACK_EXPRESS":"????????????","SORT_TYPE":"????????????","DEAL_TIME":"????????????","DEAL_PRICE":"????????????","DEAL_BUY_COUNT":"????????????","SORT_DEFAULT":"??????","SIDE_DEAL_MESSAGE":"????????????","DEAL_ASK":"????????????","REOPEN_NUMBER":"??????<span class=\"ct\">%s</span>????????? ","REOPEN_SUBMIT":"??????????????????","REOPEN_SUBMIT_FAST":"??????????????????","REOPEN_SUBMIT_OK":"??????????????????","REOPEN_SUBMIT_FAILED":"????????????","BID_SUBMIT_FAILED":"????????????","LOTTERY_MOBILE":"???????????????","GET_VERIFY_CODE":"???????????????","VERIFY_MOBILE_EMPTY":"??????????????????","DO_GET":"??????","MOBILE_VERIFY_CODE":"???????????????","VERIFY_CODE_SEND_FAST":"??????????????????????????????????????????????????????5??????!","MOBILE_USED_BIND":"???????????????????????????","MOBILE_VERIFY_SEND_OK":"??????????????????????????????????????????","BIND_MOBILE_VERIFY_ERROR":"???????????????","BIND_MOBILE_NOT_VERIFY":"??????????????????????????????????????????","MOBILE_VERIFIED":"??????????????????????????????","MOBILE_BIND_SUCCESS":"????????????","YOU_HAVE_GOT_LOTTERY":"??????????????????????????????","GO_TO_VIEW_LOTTERY":"???????????????????????????????????????????????????????????????","VIEW_NOW_LOTTERY":"????????????????????????????????????","UC_LOTTERY":"????????????","UC_CT":"????????????","UC_KTV":"KTV??????","UC_TAKEAWAY":"????????????","LOTTERY_SN":"?????????","LOTTERY_DEAL_NAME":"??????????????????","LOTTERY_BUYER":"?????????","LOTTERY_CREATE_TIME":"????????????","API_LIST":"????????????API??????","SMS_OFF":"???????????????","GET_INVITE_LOTTERY":"???????????????????????????????????????????????????????????????????????????????????????????????????","TOTAL_LOTTERY_COUNT":"????????????%s????????????","INVITE_LOTTERY_DEAL_TIP":"?????????????????????????????????????????????","CART_EMPTY_TIP":"??????????????????????????????","MODIFY_BIND":"??????????????????","SUPPLIER_NOT_MATCH":"??????????????????","ORDER_USER":"????????????","CONSIGNEE_MOBILE":"???????????????","NOT_LIMIT_TIME":"?????????","COUPON_HAS_USED":"??????????????????","REDELIVERY":"????????????","DODELIVERY":"????????????","NO_DELIVERY_AUTH":"??????????????????","SUPPLIER_ORDER_DELIVERY":"????????????","ITEM_HAS_DELIVERYED":"?????????","ITEM_NOT_DELIVERYED":"?????????","DELIVERY_SN":"????????????","DELIVERY_MEMO":"????????????","PLEASE_SELECT_DELIVERY_ITEM":"?????????????????????","DELIVERY_SUCCESS":"????????????","NO_RESULT":"??????????????????","DOWNLOAD_ORDER":"????????????","COUPON_VERIFY_LOG":"???????????????","COUPON_USE_LOG":"???????????????","DEAL_COUPON_LIST":"???????????????","NO_DEAL_COUPON":"?????????????????????","DEAL_DELETE_COUPON":"?????????????????????","NOT_CONFIRM":"?????????","COUPON_ORDER_GOODS":"????????????","CATE_DEAL":"??????","PURPOSE":"??????","ALL_CATE_DEAL":"??????","SORT_BEGIN_TIME":"??????","SORT_BUY_COUNT":"??????","SORT_SORT":"??????","SORT_CURRENT_PRICE":"??????","SITE_NOTICE_LIST":"????????????","MORE":"??????","VIEW_CART":"???????????????","TUANGOU":"??????","PROMOTE_PRICE":"?????????","HOT_LIST":"????????????","NEW_LIST":"????????????","BEST_LIST":"????????????","SALE_LIST":"????????????","NO_GOODS":"??????????????????","PLEASE_SELECT":"?????????","SELECTED_ATTR":"?????????","BRAND":"??????","BUY_NOW":"????????????","EXCHANGE_NOW":"????????????","BRAND_INFO":"????????????","ARTICLE_CATE":"????????????","ARTICLE_LIST":"????????????","GOODS_DETAIL":"????????????","GOODS_COMMENT":"????????????","GOODS_CATE":"????????????","TODAY_DEAL":"????????????","WELCOME":"????????????","PLEASE_FIRST":"??????","MORE_CATE":"????????????","SHOP_NOTICE":"????????????","GOODS_FILTER":"????????????","GOODS_LIST":"????????????","SORT_BY":"??????","STYLE_LIST":"??????","ADVANCED_SEARCH":"????????????","ALL_SHOP_CATE":"????????????","ALL_BRAND":"????????????","KW_TIP":"??????????????????????????????????????????????????????","GOODS_SEARCH":"????????????","INPUT_KW_PLEASE":"??????????????????","TOTAL_COMMENT_BUY":"??????%s???????????????,","GOOD_COMMENT_PERCENT":"????????? %s ???","VIEW_COMMENT":"????????????","COLLECT":"??????","COLLECT_SUCCESS":"????????????","GOODS_COLLECT_EXIST":"??????????????????????????????","INVALID_GOODS":"?????????????????????","ADDCART_SUCCESS":"?????????????????????","YOU_HAVE_ADD":"????????????","KEEP_BUYING":"????????????","TO_CART":"???????????????","CLEAR_CART":"???????????????","GO_TO_PICK":"?????????????????????????????????????????? ","PICK_GOODS_TO_CART":"????????????","OR_GO_TO":"?????????","MY_COLLECT":"???????????????","FOR_CHECK":"??????","PAY_TOTAL_PRICE_NO_DELIVERY":"???????????????????????????????????????","NOT_ENOUGH_SCORE":"??????????????????","ADD_CART_ERR":"?????????????????????","UC_STORE_FAV":"???????????????","UC_MENU_FAV":"????????????","COLLECT_GOODS_NAME":"??????????????????","COLLECT_STORE_NAME":"??????????????????","COLLECT_TIME":"????????????","DELETE_SUCCESS":"????????????","INVALID_COLLECT":"?????????????????????","SHOP_SYSTEM":"????????????","CLICK_COUNT":"?????????","WELCOME_BACK":"???????????????","WELCOME_BACK_HD":"???????????????","LAST_LOGIN_TIME":"???????????????????????????","LOGIN_IP":"??????IP","MAY_FROM":"????????????","YOU_LEVEL_IS":"???????????????","TEL":"??????","HOME_PAGE":"??????","SHOP_HOME":"??????","CITY_NOTICE":"??????","RELATE_LIST":"????????????","MORE_USER_INFO":"??????????????????","NATICE_PLACE":"??????","USER_REGION":"???????????????","USER_PROVINCE":"??????","USER_CITY":"??????","USER_SEX":"??????","USER_MY_INTRO":"????????????","USER_SEX_NULL":"??????","USER_SEX_0":"???","USER_SEX_1":"???","FOCUS_THEY":"????????????","CANCEL_FOCUS":"????????????","YOU_MAY_FOCUS":"?????????????????????","SAME_CITY":"????????????????????????","RAND_USER":"????????????","MY_FANS":"????????????","MY_FOCUS":"????????????","UC_CENTER_FOCUSTOPIC":"??????????????????","TA_FANS":"TA?????????","TA_FOCUS":"TA?????????","FANS":"??????","FOCUS":"??????","CHANGE_RAND":"?????????","TOPIC_TITLE":"??????","TOPIC_GOOD":"??????","TOPIC_BAD":"??????","YOU_HAVE_VOTE":"?????????????????????","TOPIC_NULL":"???????????????","TOPIC_YOURSELF":"??????????????????????????????","TOPIC_SHOW":"????????????","TOPIC_REPLY":"????????????","TOPIC_COMMENT":"??????","TOPIC_REPLY_LIST":"????????????","BUY_COMMENT_TIP":"???????????????????????????????????????????????????????????????????????????????????????","USER_POST":"????????????","WHOS_SPACE":"%s?????????","USER_NOT_EXISTS":"???????????????","FOCUS_SELF":"??????????????????","COMMENT_ORIGIN_NOTEXIST":"????????????  - ?????????????????????","COMMENT_TO_GOODS":"???%s????????????","SPACE_HOME":"????????????","SPACE_DEAL":"????????????","SPACE_FOCUS":"????????????","SPACE_FOCUSTOPIC":"??????????????????","SPACE_LEND":"????????????","LOCATION_NULL":"??????","ALBUM":"??????","FACE":"??????","TOPIC_IMAGE_TIP":"??????????????????????????????????????????????????????","EXPRESSION_QQ":"QQ","EXPRESSION_TUSIJI":"?????????","ONLY_IMG":"[??????]??????????????????...","SELECT_CITY_BY_PY":"?????????????????????????????????","ALL_CITY":"??????","SELECT_CITY":"???????????????","YOUHUI_CATE":"???????????????","FREE_YOUHUI":"?????????","END_TIME":"????????????","RECOMMEND_SUPPLIER":"????????????","NO_YOUHUI_LIST":"??????????????????????????????","YOUHUI_LIST":"???????????????","YOUHUI_FILTER":"???????????????","AREA_LIST":"?????????","HOT_YOUHUI_LIST":"???????????????","SMS_DOWNLOAD":"????????????","YOUHUI_PRINT":"????????????","YOUHUI_NO_SUPPORT_SMS":"?????????????????????????????????","YOUHUI_NO_SUPPORT_PRINT":"?????????????????????????????????","YOU_WILL_GET_SMS":"???????????????????????????","SMS_LIMIT_OVER":"?????????????????????????????????","SMS_SEND_FAILED":"??????????????????","SMS_SEND_SUCCESS":"?????????????????????????????????","YOUHUI_END_TIME_OVER":"??????????????????","YOUHUI_NO_EXIST":"??????????????????","NEAR_YOUHUI":"????????????","STORE_LIST":"????????????","STORE_TAKEAWAY_LIST":"????????????","STORE_KTV_LIST":"KTV??????","STORE_FILTER":"????????????","NO_STORE_LIST":"???????????????????????????","NO_STORE_INFO":"??????????????????","TOPIC_SHARE":"??????","TOPIC_EVENT":"??????","EVENT_TAG":"[??????]","MERCHANT_USER":"[??????]","FAV_TOPIC":"??????","FAV":"??????","RELAY_TOPIC":"??????","RELAY_SUCCESS":"????????????","FAV_SUCCESS":"????????????","ORIGIN_TOPIC":"????????????","THIS_TOPIC":"?????????","ORIGIN_DELETE":"??????????????????","DEAL_MAIL_SUBMIT":"??????????????????????????????????????????","SHOP_CART":"?????????","YOUHUI_VERIFY":"???????????????","YOUHUI_VERIFY_TIP":"?????????????????????????????????????????????","YOUHUI_SN_SHORT":"??????????????????","YOUHUI_SEARCH":"???????????????","YOUHUI_INVALID":"??????????????????","YOUHUI_SN_INVALID":"???????????????????????????","YOUHUI_NOT_BEGIN":"?????????????????????,????????????%s","YOUHUI_HAS_END":"???????????????%s??????","SUPPLIER_LOGIN_FIRST":"????????????","YOUHUI_HAS_USED":"??????????????????%s?????????","YOUHUI_SN":"?????????","IS_VALID_YOUHUI":"??????","YOUHUI_ORDER_COUNT":"????????????","ORDER_COUNT_PERSON":"???","IS_PRIVATE_ROOM":"??????","ORDER_DATE_TIME":"????????????","CONFIRM_USE_YOUHUI":"???????????????????","UC_CENTER_INDEX":"??????????????????","UC_CENTER_MYTOPIC":"????????????","UC_CENTER_MYFAV":"????????????","UC_CENTER_MYCOMMENT":"????????????","UC_CENTER_MYDEAL":"????????????","UC_CENTER_ATME":"@????????????","UC_CENTER_LEND":"????????????","GRADUATION":"????????????","GRADUATION_1":"???????????????","GRADUATION_2":"??????","GRADUATION_3":"??????","GRADUATION_4":"??????????????????","GRADUATEDYEAR":"????????????","UNIVERSITY":"????????????","MARRIAGE":"????????????","HASCHILD":"????????????","HASHOUSE":"????????????","HOUSELOAN":"????????????","HASCAR":"????????????","CARLOAN":"????????????","SUBMIT_FORM":"??????","SEND_TOPIC_TIP":"?????????????????????????????????","SEND_EVENT_TIP":"?????????????????????????????????","AJAX_LOADING":"???????????????","TOPIC_ITEM_REPLY":"??????","TOPIC_NOT_EXIST":"????????????????????????","TOPIC_SELF":"????????????????????????","TOPIC_FAVED":"??????????????????","TOPIC_TAG":"??????","OTHER_TAG":"??????","OTHER_TAG_TIP":"??????????????????????????????","HOT_TAG":"????????????","HOT_TITLE":"????????????","TOPIC_TYPE_ALL":"????????????","TOPIC_TYPE_DEAL":"????????????","TOPIC_TYPE_STORE":"????????????","TOPIC_TYPE_RECOMMEND":"????????????","NO_TOPIC_RESULT":"?????????????????????????????????????????????","FLOOR":"???","TOPIC_NOT_YOURS":"???????????????????????????","TOPIC_NOT_EXISTS":"???????????????","REPLY_NOT_EXISTS":"???????????????","REPLY_NOT_YOURS":"???????????????????????????","CONFIRM_DELETE_TOPIC":"????????????????????????","CONFIRM_DELETE_RELAY":"????????????????????????","QUAN_LIST":"??????","NO_AUTH":"?????????????????????????????????","PLEASE_SPEC_DEAL":"???????????????","SEARCH_TYPE_GOODS":"?????????","SEARCH_TYPE_YOUHUI":"?????????","SEARCH_TYPE_TOPIC":"?????????","TOPIC_FILTER_ALL":"??????","TOPIC_FILTER_MYFOCUS":"????????????","TOPIC_FILTER_DAREN":"??????","TOPIC_FILTER_MERCHANT":"??????","BY_CATE":"?????????","BY_REGION":"?????????","GO_TO_VIEW":"?????????","HOT_STORE":"????????????","VIEW_DETAIL_INFO":"????????????","HAS_VIEW":"????????????","HAS_BUY":"????????????","NEED_BUY_YOUHUI":"???????????????","TUAN":"??????","TUAN_LIST":"????????????","NO_TUAN_LIST":"???????????????????????????","REC_TUAN":"????????????","REC_YOUHUI":"???????????????","REGSITER_NOW":"??????????????????<a href=\"%s\">????????????</a>","STEP_HEAD_TIP":"???????????????%s???????????????%s","STEP_ALL_TIP":"???????????????3??????????????????%s??????????????????","STEP_ONE":"??????????????????","STEP_TWO":"??????????????????","STEP_THREE":"??????????????????","STEP_NEXT":"??????>>","STEP_NOW_AVATAR":"????????????","STEP_UP_AVATAR":"???????????????","STEP_UP_AVATAR_TYPE":"??????%s???????????????","STEP_USER_FOLLOW_TIP":"<b>?????????????????????</b>???????????????????????????????????????????????????????????????????????????","USER_BIRTHDAY":"????????????","TUAN_END_DATETIME":"??????????????????%s","BRAND_DISCOUNT":"????????????","YOUHUI_EVENT":"??????","EVENT_TIME":"????????????","SUBMIT_COUNTED":"????????????","REPLY_COUNTED":"?????????","EVENT_NOT_EXIST":"???????????????","EVENT_NOT_START":"?????????????????????","EVENT_SUBMIT_END":"?????????????????????","EVENT_SUBMIT":"????????????","EVENT_SUBMITTED":"?????????????????????","EVENT_SUBMIT_FAILED":"????????????","EVENT_SUBMIT_SUCCESS":"????????????","PLEASE_INPUT":"?????????","MSG_COUNT":"??????","SELECT_ALL":"?????? ","SYSTEM_PM":"????????????","I":"???","SAY":"???","SAYTO":"???","TOTAL_PM":"???%s???","PLEASE_SELECT_PMGROUP":"??????????????????????????????","PLEASE_SELECT_PM":"???????????????????????????","CONFIRM_DELETE_PMGROUP":"??????????????????????????????????????????","WRITE_PM":"???TA??????","VIEW_AND_REPLY":"????????????","TO_USER":"?????????","CONTENT":"??????","DO_SEND":"??????","TO_USER_EMPTY":"??????????????????","FANS_ONLY":"?????????????????????","PM_LIST":"??????????????????","SETWEIBO":"?????????????????????","DAREN_SHOW":"?????????","DAREN_SUBMIT":"????????????","ALL_CATEGORY":"????????????","ALL_QUAN_LIST":"????????????","HOT_CATEGORY":"????????????","HOT_SUPPLIERS":"????????????","SERVICES_TEL":"??????????????????","WORK_TIME":"?????????","TUAN_PROMOTION":"???????????????","REC_STORE":"???????????????","GOOD_RATE":"?????????","TO_DOWNLOAD":"????????????","PROMOTION_EVENT":"???????????????","DOWN_YOUHUI_LIST":"???????????????????????????","DOWN_COUNT":"??????????????????","dp_point_0":"????????????","dp_point_1":"???","dp_point_2":"??????","dp_point_3":"???","dp_point_4":"??????","dp_point_5":"?????????","PLASE_ENTER_TRUE_FIXED_AMOUNT":"???????????????????????????????????????200?????????????????????50????????????","PLASE_ENTER_TRUE_MIN_RATE":"??????????????????????????????","PLASE_ENTER_TRUE_MAX_RATE":"??????????????????????????????","MIN_RATE_NOTMORE_MAX_RATE":"???????????????????????????????????????????????????","MIN_PERIOD_NOMORE_MAX_PERIOD":"???????????????????????????????????????????????????","MIN_LEVEL_NOMORE_MAX_LEVEL":"???????????????????????????????????????????????????","PLASE_ENTER_TRUE_RETAIN_AMOUNT":"????????????????????????????????????","SAVE_AUTOBID_SUCCESS":"?????????????????????????????????","FINANCIAL_MANAGEMENT":"????????????","TOOLS":"?????????","CALCULATE":"???????????????","T_CHECK_MOBILE":"??????????????????","T_CHECK_IP":"IP????????????","T_CONTACT":"??????????????????","APPLY_BORROW":"????????????","APPLY_AMOUNT":"????????????","UPLOAD_DATA":"????????????","TOKEN_ERR":"???????????????????????????????????????"};;//globalObj ????????????????????? ?????????????????????????????????????????????
if(typeof(globalObj) == "undefined"){
     globalObj = {};
}

      

/*
?????????????????????????????? X?????????????????????????????????????????????

 */

var X = {
    ajax: function(obj) {
        var online = obj.online || true,
            data = obj.data || "??????ajax??????";
        if ( !! online) {
            $.ajax({
                url: obj.url,
                dataType: obj.dataType || "json",
                type: obj.type || 'post',
                data: data ,
                success: function(data) { 
                    !! obj.fn && obj.fn(data);
                },
                error: function(e) { 
                    !! obj.errorFn && obj.errorFn();
                    return;
                }
            });
        } else { 
            !! obj.testFn && obj.testFn(data);
        }
    },
    ajaxPost : function($obj, options){
          var   $t = $obj,
               settings = {
                   url : $t.data("url"),
                   data : {
                         cid: $t.data("cid"),
                         proid: $t.data("proid")
                    },
                   type : "post",
                   sFn : function(json , $t){
                        if(json.status == 1){
                               X.locationReload(null , 1500);
                         }
                          newAlert({
                           msg: json.msg,
                               fn: function() {

                                 }
                        });
                   }
            };
          $.extend(settings , options);
          X.ajax({
              url: settings.url,
              data: settings.data,
              type : settings.type,
              fn: function(json) {
                  settings.sFn(json , $t);
              }

          });
          
    },
    ajaxDel : function($obj , options){
        var settings = {
             url : $obj.attr("action"),
             data : $obj.serialize(),
             type : "post",
             msg : "????????????????????????????????????????????????",
             callback : function(data){
                  alert(data.msg);
                  if(data.data && data.data.url){
                        X.locationReload(data.data.url);
                  }
                  X.locationReload(null ,500);
                  
             }
        };
        $.extend(settings , options);
        newComfirm({
            msg: settings.msg,
            fn: function(data) {
                if (data.btn == "yes") {
                    var $f = $obj;
                    X.ajax({
                        url : settings.url,
                        type : settings.type,
                        data : settings.data,
                        fn : function(data){
                               !!settings.callback && settings.callback(data);
                        }
                    });


                }
            }
        });
    },
    locationReload : function(url,time){
        var time = time || 1500;
        if(!url){
              setTimeout("location.reload()",time);
        }else{
            setTimeout(function(){
               location.replace(url);
            },time);
        }
        
    },
    formPost : function($form , option){
        var settings = {
            prettySelect : false,
            ajaxFormValidationMethod : "post",
            ajaxFormValidation : true,
            modForm : null,
            onFailure :null,
            cFn : function(status, form, json, options){
                  if ( !! status) {
                      if ( !! json.status) {
                          if ( !! json.data && !! json.data.url) {
                              X.locationReload(json.data.url, 2000);
                              newAlert({
                                  msg: json.msg,
                                  fn: function() {
                                      location.href = json.data.url;
                                  }
                              });
                          } else {
                              X.locationReload(null, 2000);
                              newAlert({
                                  msg: json.msg,
                                  fn: function() {
                                      location.reload();
                                  }
                              });

                          }
                      } else {
                          alert(json.msg);
                      }
                  }
            }
        };
        $.extend(settings , option);
        $form.valid({
            onFailure : function(form){
                !!settings.onFailure && settings.onFailure(form);
            },
            prettySelect : settings.prettySelect,
            ajaxFormValidation: settings.ajaxFormValidation,
            ajaxFormValidationMethod: settings.ajaxFormValidationMethod,
            modForm : function(form, json, options){
                !!settings.modForm && settings.modForm(form, json, options);

            },
            onAjaxFormComplete: function(status, form, json, options) {
                  settings.cFn(status, form, json, options);
            }
        });

    },
    accMul : function (arg1,arg2){
        var m=0,s1=arg1.toString(),s2=arg2.toString();
        try{m+=s1.split(".")[1].length}catch(e){};
        try{m+=s2.split(".")[1].length}catch(e){};
        return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m);
     },
     accDiv : function (arg1,arg2){
        var t1=0,t2=0,r1,r2;
        try{t1=arg1.toString().split(".")[1].length}catch(e){};
        try{t2=arg2.toString().split(".")[1].length}catch(e){};
        with(Math){
        r1=Number(arg1.toString().replace(".",""));
        r2=Number(arg2.toString().replace(".",""));
        return (r1/r2)*pow(10,t2-t1);
        }
    },
    accAdd : function (arg1,arg2){ 
        var r1,r2,m; 
        try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0} ;
        try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0} ;
        m=Math.pow(10,Math.max(r1,r2)) ;
        return (arg1*m+arg2*m)/m ;
    },
    accSub : function (arg1,arg2){
         var r1,r2,m,n;
         try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0};
         try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0};
         m=Math.pow(10,Math.max(r1,r2));
         n=(r1>=r2)?r1:r2;
         return ((arg1*m-arg2*m)/m).toFixed(n);
     },
    textLimit: function($o, config) {
        $o.on('input propertychange', function(evt) {
               var $o = $(this); 
               var _maxlen = $o.attr("maxlength") || (config.maxlength || 300);
               var content_len = !! $o[0] ? $o.val().length : 0;
               var $next = $o.parent().find(config.nexttarget);
               var str = "";
               if (content_len <= _maxlen) {            
                   if (config.nexttarget) {
                       $next.html(content_len + "/" + _maxlen);
                   } 
               } else {
                   str = $o.val();
                   $o.val(str.replace(new RegExp("(.{"+ _maxlen +"}).+"), "$1"));
                   $next.html(_maxlen + "/" + _maxlen);
               }
        });
       $o.trigger("input").trigger("propertychange");
    }

};;/*! nice Validator 0.7.3
 * (c) 2012-2014 Jony Zhang <zj86@live.cn>, MIT Licensed
 * http://niceue.com/validator/
 */
/*jshint evil:true, expr:true, strict:false*/
/*global define*/
(function($, undefined) {
    "use strict";

    var NS = 'validator',
        CLS_NS = '.' + NS,
        CLS_NS_RULE = '.rule',
        CLS_NS_FIELD = '.field',
        CLS_NS_FORM = '.form',
        CLS_WRAPPER = 'nice-' + NS,
        CLS_MSG_OK = 'n-ok',
        CLS_MSG_ERROR = 'n-error',
        CLS_MSG_TIP = 'n-tip',
        CLS_MSG_LOADING = 'n-loading',
        CLS_MSG_BOX = 'msg-box',
        ARIA_REQUIRED = 'aria-required',
        ARIA_INVALID = 'aria-invalid',
        DATA_RULE = 'data-rule',
        DATA_MSG = 'data-msg',
        DATA_TIP = 'data-tip',
        DATA_OK = 'data-ok',
        DATA_TARGET = 'data-target',
        DATA_INPUT_STATUS = 'data-inputstatus',
        NOVALIDATE = 'novalidate',
        INPUT_SELECTOR = ':verifiable',

        rRules = /(!?)\s?(\w+)(?:\[\s*(.*?\]?)\s*\]|\(\s*(.*?\)?)\s*\))?\s*(;|\||&)?/g,
        rRule = /(\w+)(?:\[\s*(.*?\]?)\s*\]|\(\s*(.*?\)?)\s*\))?/,
        rDisplay = /(?:([^:;\(\[]*):)?(.*)/,
        rDoubleBytes = /[^\x00-\xff]/g,
        rPos = /^.*(top|right|bottom|left).*$/,
        rAjaxType = /(?:(post|get):)?(.+)/i,
        rUnsafe = /<|>/g,

        noop = $.noop,
        proxy = $.proxy,
        isFunction = $.isFunction,
        isArray = $.isArray,
        isString = function(s) {
            return typeof s === 'string';
        },
        isObject = function(o) {
            return o && Object.prototype.toString.call(o) === '[object Object]';
        },
        isIE6 = !window.XMLHttpRequest,
        attr = function(el, key, value) {
            if (value !== undefined) {
                if (value === null) el.removeAttribute(key);
                else el.setAttribute(key, '' + value);
            } else {
                return el.getAttribute(key);
            }
        },
        debug = window.console || {
            log: noop,
            info: noop
        },
        submitButton,
        novalidateonce,

        defaults = {
            ajaxDataCode : "code",
            ajaxDataMsg : "msg",
            debug: 0,
            timely: 1,
            theme: 'default',
            ignore: '',
            //stopOnError: false,
            //focusCleanup: false,
            focusInvalid: true,
            beforeSubmit: noop,
            //dataFilter: null,
            //valid: null,
            //invalid: null,

            validClass: 'n-valid',
            invalidClass: 'n-invalid',

            msgWrapper: 'span',
            msgMaker: function(opt) {
                var html,
                    cls = {
                        error: CLS_MSG_ERROR,
                        ok: CLS_MSG_OK,
                        tip: CLS_MSG_TIP,
                        loading: CLS_MSG_LOADING
                    }[opt.type];

                html = '<span class="msg-wrap '+ cls +'" role="alert">';
                html += opt.arrow + opt.icon + '<span class="n-msg">' + opt.msg + '</span>';
                html += '</span>';
                return html;
            },
            msgIcon: '<span class="n-icon"></span>',
            msgArrow: '',
            msgClass: '',
            //msgStyle: null,
            //msgShow: null,
            //msgHide: null,
            //showOk: true,
            defaultMsg: '{0} is not valid.',
            loadingMsg: 'Validating...'
        },
        themes = {
            'default': {
                formClass: 'n-default',
                msgClass: 'n-right',
                showOk: ''
            }
        };

    /** jQuery Plugin
     * @param {Object} options
        debug         {Boolean}     false     Whether to enable debug mode
        timely        {Boolean}     true      Whether to enable timely verification
        theme         {String}     'default'  Using which theme
        stopOnError   {Boolean}     false     Whether to stop validate when found an error input
        focusCleanup  {Boolean}     false     Whether to clean up the field message when focus the field
        focusInvalid  {Boolean}     true      Whether to focus the field that is invalid
        ignore        {jqSelector}    ''      Ignored fields (Using jQuery selector)

        beforeSubmit  {Function}              Do something before submitting the form
        dataFilter    {Function}              Conversion ajax results
        valid         {Function}              Triggered when the form is valid
        invalid       {Function}              Triggered when the form is invalid
        validClass    {String}                Add the class name to a valid field
        invalidClass  {String}                Add the class name to a invalid field

        msgShow       {Function}    null      When show a message, will trigger this callback
        msgHide       {Function}    null      When hide a message, will trigger this callback
        msgWrapper    {String}     'span'     Message wrapper tag name
        msgMaker      {Function}              Message HTML maker
        msgIcon       {String}                Icon template
        msgArrow      {String}                Small arrow template
        msgStyle      {String}                Custom message style
        msgClass      {String}                Additional added to the message class names
        formClass     {String}                Additional added to the form class names

        defaultMsg    {String}                Default error message
        loadingMsg    {String}                Tips for asynchronous loading
        messages      {Object}      null      Custom messages for the current instance

        rules         {Object}      null      Custom rules for the current instance

        fields        {Object}                Field set to be verified
        {String} key    name|#id
        {String|Object} value                 Rule string, or an object is passed more arguments

        fields[key][rule]       {String}      Rule string
        fields[key][tip]        {String}      Custom friendly message when focus the input
        fields[key][ok]         {String}      Custom success message
        fields[key][msg]        {Object}      Custom error message
        fields[key][msgStyle]   {String}      Custom message style
        fields[key][msgClass]   {String}      Additional added to the message class names
        fields[key][msgWrapper] {String}      Message wrapper tag name
        fields[key][msgMaker]   {Function}    Custom message HTML maker
        fields[key][dataFilter] {Function}    Conversion ajax results
        fields[key][valid]      {Function}    Triggered when this field is valid
        fields[key][invalid]    {Function}    Triggered when this field is invalid
        fields[key][must]       {Boolean}     If set true, we always check the field even has remote checking
        fields[key][timely]     {Boolean}     Whether to enable timely verification
        fields[key][target]     {jqSelector}  Verify the current field, but the message can be displayed on target element
     */
    $.fn[NS] = function(options) {
        var that = this,
            args = arguments;

        if (that.is(':input')) return that;
        !that.is('form') && (that = this.find('form'));
        !that.length && (that = this);
        that.each(function() {
            var cache = $(this).data(NS);
            if (cache) {
                if (isString(options)) {
                    if (options.charAt(0) === '_') return;
                    cache[options].apply(cache, Array.prototype.slice.call(args, 1));
                } else if (options) {
                    cache._reset(true);
                    cache._init(this, options);
                }
            } else {
                new Validator(this, options);
            }
        });

        return this;
    };


    // Validate a field, or an area
    $.fn.isValid = function(callback, hideMsg) {
        var me = getInstance(this[0]),
            hasCallback = isFunction(callback),
            ret, opt;

        if (!me) return true;
        me.checkOnly = !!hideMsg;
        opt = me.options;

        ret = me._multiValidate(
            this.is(':input') ? this : this.find(INPUT_SELECTOR),
            function(isValid){
                if (!isValid && opt.focusInvalid && !me.checkOnly) {
                    // navigate to the error element
                    me.$el.find(':input[' + ARIA_INVALID + ']:first').focus();
                }
                hasCallback && callback.call(null, isValid);
                me.checkOnly = false;
            }
        );

        // If you pass a callback, we maintain the jQuery object chain
        return hasCallback ? this : ret;
    };


    // A faster selector than ":input:not(:submit,:button,:reset,:image,:disabled,[novalidate])"
    $.expr[":"].verifiable = function(elem) {
        var name = elem.nodeName.toLowerCase();

        return (name === 'input' && !({submit: 1, button: 1, reset: 1, image: 1})[elem.type] || name === 'select' || name === 'textarea') &&
               elem.disabled === false;
    };


    // Constructor for Validator
    function Validator(element, options) {
        var me = this;

        if (!me instanceof Validator) return new Validator(element, options);

        me.$el = $(element);
        me._init(element, options);
    }

    Validator.prototype = {
        _init: function(element, options) {
            var me = this,
                opt, themeOpt, dataOpt;

            // Initialization options
            if (isFunction(options)) {
                options = {
                    valid: options
                };
            }
            options = options || {};
            dataOpt = attr(element, 'data-'+ NS +'-option');
            dataOpt = dataOpt && dataOpt.charAt(0) === '{' ? (new Function("return " + dataOpt))() : {};
            themeOpt = themes[ options.theme || dataOpt.theme || defaults.theme ];
            opt = me.options = $.extend({}, defaults, themeOpt, dataOpt, me.options, options);

            me.rules = new Rules(opt.rules, true);
            me.messages = new Messages(opt.messages, true);
            me.elements = me.elements || {};
            me.deferred = {};
            me.errors = {};
            me.fields = {};

            // Initialization fields
            me._initFields(opt.fields);

            // Initialization group verification
            if (isArray(opt.groups)) {
                $.map(opt.groups, function(obj) {
                    if (!isString(obj.fields) || !isFunction(obj.callback)) return null;
                    obj.$elems = me.$el.find(keys2selector(obj.fields));
                    $.map(obj.fields.split(' '), function(k) {
                        me.fields[k] = me.fields[k] || {};
                        me.fields[k].group = obj;
                    });
                });
            }

            // Initialization message parameters
            me.msgOpt = {
                type: 'error',
                pos: getPos(opt.msgClass),
                wrapper: opt.msgWrapper,
                cls: opt.msgClass,
                style: opt.msgStyle,
                icon: opt.msgIcon,
                arrow: opt.msgArrow,
                show: opt.msgShow,
                hide: opt.msgHide
            };

            // Guess whether it use ajax submit
            me.isAjaxSubmit = false;
            if (opt.valid || !$.trim(attr(element, 'action'))) {
                me.isAjaxSubmit = true;
            } else {
                // if there is a "valid.form" event
                var events = $[ $._data ? '_data' : 'data' ](element, "events");
                if (events && events.valid &&
                    $.map(events.valid, function(e){
                        return e.namespace.indexOf('form') !== -1 ? 1 : null;
                    }).length
                ) {
                    me.isAjaxSubmit = true;
                }
            }

            // Initialization events and make a cache
            if (!me.$el.data(NS)) {
                me.$el.data(NS, me).addClass(CLS_WRAPPER +' '+ opt.formClass)
                      .on('submit'+ CLS_NS +' validate'+ CLS_NS, proxy(me, '_submit'))
                      .on('reset'+ CLS_NS, proxy(me, '_reset'))
                      .on('showtip'+ CLS_NS, proxy(me, '_showTip'))
                      .on('focusin'+ CLS_NS +' click'+ CLS_NS +' showtip'+ CLS_NS, INPUT_SELECTOR, proxy(me, '_focusin'))
                      .on('focusout'+ CLS_NS +' validate'+ CLS_NS, INPUT_SELECTOR, proxy(me, '_focusout'));

                if (opt.timely >= 2) {
                    me.$el.on('keyup'+ CLS_NS +' paste'+ CLS_NS, INPUT_SELECTOR, proxy(me, '_focusout'))
                          .on('click'+ CLS_NS, ':radio,:checkbox', proxy(me, '_focusout'))
                          .on('change'+ CLS_NS, 'select,input[type="file"]', proxy(me, '_focusout'));
                }

                // cache the novalidate attribute value
                me._novalidate = attr(element, NOVALIDATE);
                // Initialization is complete, stop off default HTML5 form validation
                // If use "jQuery.attr('novalidate')" in IE7 will complain: "SCRIPT3: Member not found."
                attr(element, NOVALIDATE, NOVALIDATE);
            }
        },

        _initFields: function(fields) {
            var me = this;

            // Processing field information
            if (isObject(fields)) {
                $.each(fields, function(k, v) {
                    // delete the field from settings
                    if (v === null) {
                        var el = me.elements[k];
                        if (el) me._resetElement(el, true);
                        delete me.fields[k];
                    } else {
                        me.fields[k] = isString(v) ? {
                            rule: v
                        } : v;
                    }
                });
            }

            // Parsing DOM rules
            me.$el.find(INPUT_SELECTOR).each(function() {
                me._parse(this);
            });
        },

        // Parsing a field
        _parse: function(el) {
            var me = this,
                field,
                key = el.name,
                dataRule = attr(el, DATA_RULE);

            dataRule && attr(el, DATA_RULE, null);

            // if the field has passed the key as id mode, or it doesn't has a name
            if (el.id && ('#' + el.id in me.fields) || !el.name) {
                key = '#' + el.id;
            }
            // doesn't verify a field that has neither id nor name
            if (!key) return;

            field = me.fields[key] || {};
            field.key = key;
            field.old = {};
            field.rule = field.rule || dataRule || '';
            if (!field.rule) return;

            if (field.rule.match(/match|checked/)) {
                field.must = true;
            }
            if (field.rule.indexOf('required') !== -1) {
                field.required = true;
                attr(el, ARIA_REQUIRED, true);
            }
            if ('timely' in field && !field.timely || !me.options.timely) {
                attr(el, 'notimely', true);
            }
            if (isString(field.target)) {
                attr(el, DATA_TARGET, field.target);
            }
            if (isString(field.tip)) {
                attr(el, DATA_TIP, field.tip);
            }

            me.fields[key] = me._parseRule(field);
        },

        // Parsing field rules
        _parseRule: function(field) {
            var arr = rDisplay.exec(field.rule),
                opt = this.options;

            if (!arr) return;
            // current rule index
            field._i = 0;
            if (arr[1]) {
                field.display = arr[1];
            }
            if (!field.display && opt.display) {
                field.display = opt.display;
            }
            if (arr[2]) {
                field.rules = [];
                arr[2].replace(rRules, function(){
                    var args = arguments;
                    args[3] = args[3] || args[4];
                    field.rules.push({
                        not: args[1] === "!",
                        method: args[2],
                        params: args[3] ? args[3].split(', ') : undefined,
                        or: args[5] === "|"
                    });
                });
            }

            return field;
        },

        // Verify a zone
        _multiValidate: function($inputs, doneCallbacks){
            var me = this,
                opt = me.options;

            me.verifying = true;
            me.isValid = true;
            if (opt.ignore) $inputs = $inputs.not(opt.ignore);

            $inputs.each(function(i, el) {
                var field = me.getField(el);
                if (field) {
                    me._validate(el, field);
                    if (!me.isValid && opt.stopOnError) {
                        // stop the verification
                        return false;
                    }
                }
            });

            // Need to wait for the completion of all field validation (especially asynchronous verification)
            $.when.apply(
                null,
                $.map(me.deferred, function(v){return v;})
            ).done(function(){
                doneCallbacks.call(me, me.isValid);
                me.verifying = false;
            });

            // If the form does not contain asynchronous validation, the return value is correct.
            // Otherwise, you should detect whether a form valid through "doneCallbacks".
            return !$.isEmptyObject(me.deferred) ? undefined : me.isValid;
        },

        // Verify the whole form
        _submit: function(e) {
            //console.log(e);
            var me = this,
                opt = me.options,
                form = e.target,
                autoSubmit = e.type === 'submit';

            e.preventDefault();
            if (
                novalidateonce && !!~(novalidateonce = false) ||
                // Prevent duplicate submission
                me.submiting ||
                // Receive the "validate" event only from the form.
                e.type === 'validate' && me.$el[0] !== form ||
                // trigger the beforeSubmit callback.
                opt.beforeSubmit.call(me, form) === false
            ) {
                return;
            }

            opt.debug && debug.log("\n" + e.type);

            me._reset();
            me.submiting = true;

            me._multiValidate(
                me.$el.find(INPUT_SELECTOR),
                function(isValid){
                    var ret = (isValid || opt.debug === 2) ? 'valid' : 'invalid',
                        errors;

                    if (!isValid) {
                        if (opt.focusInvalid) {
                            // navigate to the error element
                            me.$el.find(':input[' + ARIA_INVALID + '="true"]:first').focus();
                        }
                        errors = $.map(me.errors, function(err){
                            return err;
                        });
                    }

                    // releasing submit
                    me.submiting = false;

                    // trigger callback and event
                    isFunction(opt[ret]) && opt[ret].call(me, form, errors);
                    me.$el.trigger(ret + CLS_NS_FORM, [form, errors]);

                    if ((isValid && !me.isAjaxSubmit && autoSubmit) || (isValid && !!opt.isNormalSubmit && autoSubmit)) {
                        novalidateonce = true;
                        // For asp.NET controls
                        if (submitButton && submitButton.name) {
                            me.$el.append('<input type="hidden" name="'+ submitButton.name +'" value="'+ $(submitButton).val() +'">');
                        }
                        form.submit();
                    }
                }
            );
        },

        _reset: function(e) {
            var me = this;

            me.errors = {};
            if (e) {
                me.$el.find(INPUT_SELECTOR).each( function(i, el){
                    me._resetElement(el);
                });
            }
        },

        _resetElement: function(el, all) {
            var opt = this.options;
            $(el).removeClass(opt.validClass + ' ' + opt.invalidClass);
            this.hideMsg(el);
            if (all) {
                attr(el, ARIA_REQUIRED, null);
            }
        },

        _focusin: function(e) {
            var me = this,
                opt = me.options,
                el = e.target,
                msg;

            if (me.verifying) return;

            if (e.type !== 'showtip') {
                if ( attr(el, DATA_INPUT_STATUS) === 'error' ) {
                    if (opt.focusCleanup) {
                        $(el).removeClass(opt.invalidClass);
                        me.hideMsg(el);
                    }
                }
            }

            msg = attr(el, DATA_TIP);
            if (!msg) return;

            me.showMsg(el, {
                type: 'tip',
                msg: msg
            });
        },

        // Handle focusout/validate/keyup/click/change/paste events
        _focusout: function(e) {
            var me = this,
                opt = me.options,
                field,
                must,
                el = e.target,
                etype = e.type,
                ignoreType = {click:1, change:1, paste:1},
                timer = 0;

            if ( !ignoreType[etype] ) {
                // must be verified, if it is a manual trigger
                if (etype === 'validate') {
                    must = true;
                    //timer = 0;
                }
                // or doesn't require real-time verification, exit
                else if ( attr(el, 'notimely') ) return;
                // or it isn't a "keyup" event, exit
                else if (opt.timely >= 2 && etype !== 'keyup') return;

                // if the current field is ignored, exit
                if (opt.ignore && $(el).is(opt.ignore)) return;

                if (etype === 'keyup') {
                    var key = e.keyCode,
                        specialKey = {
                            8: 1,  // Backspace
                            9: 1,  // Tab
                            16: 1, // Shift
                            32: 1, // Space
                            46: 1  // Delete
                        };

                    // only gets focus, no verification
                    if (key === 9 && !el.value) return;

                    // do not validate, if triggered by these keys
                    if (key < 48 && !specialKey[key]) return;

                    // keyboard events, reducing the frequency of verification
                    timer = opt.timely >=100 ? opt.timely : 500;
                }
            }

            field = me.getField(el);
            if (!field) return;

            if (timer) {
                if (field._t) clearTimeout(field._t);
                field._t = setTimeout(function() {
                    me._validate(el, field, must);
                }, timer);
            } else {
                me._validate(el, field, must);
            }
        },

        _showTip: function(e){
            var me = this;

            if (me.$el[0] !== e.target) return;
            me.$el.find(INPUT_SELECTOR +"["+ DATA_TIP +"]").each(function(){
                me.showMsg(this, {
                    msg: attr(this, DATA_TIP),
                    type: 'tip'
                });
            });
        },

        // Validated a field
        _validatedField: function(el, field, ret) {
            var me = this,
                opt = me.options,
                isValid = ret.isValid = field.isValid = !!ret.isValid,
                callback = isValid ? 'valid' : 'invalid';

            ret.key = field.key;
            ret.rule = field._r;
            if (isValid) {
                ret.type = 'ok';
            } else {
                if (me.submiting) {
                    me.errors[field.key] = ret.msg;
                }
                me.isValid = false;
            }
            field.old.value = el.value;
            field.old.id = el.id;
            me.elements[field.key] = ret.element = el;
            me.$el[0].isValid = isValid ? me.isFormValid() : isValid;

            // trigger callback and event
            isFunction(field[callback]) && field[callback].call(me, el, ret);
            $(el).attr( ARIA_INVALID, isValid ? null : true )
                 .removeClass( isValid ? opt.invalidClass : opt.validClass )
                 .addClass( !ret.skip ? isValid ? opt.validClass : opt.invalidClass : "" )
                 .trigger( callback + CLS_NS_FIELD, [ret, me] );
            me.$el.triggerHandler('validation', [ret, me]);

            if (me.checkOnly) return;

            // show or hide the message
            if (field.msgMaker || opt.msgMaker) {
                me[ ret.showOk || ret.msg ? 'showMsg' : 'hideMsg' ](el, ret, field);
            }
        },

        // Validated a rule
        _validatedRule: function(el, field, ret, msgOpt) {
            field = field || me.getField(el);
            msgOpt = msgOpt || {};

            var me = this,
                opt = me.options,
                msg,
                rule,
                method = field._r,
                transfer,
                isValid = false,
                ajaxCode = null,
                ajaxMsg = null;

            // use null to break validation from a field
            if (ret === null) {
                me._validatedField(el, field, {isValid: true, skip: true});
                return;
            }
            else if (ret === true || ret === undefined || ret === '') {
                isValid = true;
            }
            else if (isString(ret)) {
                msg = ret;
            }
            else if (isObject(ret)) {
                ajaxCode = !!$(el).data("ajax-code") ? $(el).data("ajax-code") : opt.ajaxDataCode;
                ajaxMsg = !!$(el).data("ajax-msg") ? $(el).data("ajax-msg") : opt.ajaxDataMsg;
                msg = ret[ajaxMsg];
                if (ret[ajaxCode] === 0 || ret[ajaxCode] === '0') {
                    isValid = true;
                }
            }

            if (field.rules) {
                rule = field.rules[field._i];
                if (rule.not) {
                    msg = undefined;
                    isValid = method === "required" || !isValid;
                }
                if (rule.or) {
                    if (isValid) {
                        while ( field._i < field.rules.length && field.rules[field._i].or ) {
                            field._i++;
                        }
                    } else {
                        transfer = true;
                    }
                }
            }

            // message analysis, and throw rule level event
            if (!transfer) {
                if (isValid) {
                    msgOpt.isValid = isValid;
                    if (opt.showOk !== false) {
                        if (!isString(msg)) {
                            if (isString(field.ok)) {
                                msg = field.ok;
                            } else if (isString(attr(el, DATA_OK))) {
                                msg = attr(el, DATA_OK);
                            } else if (isString(opt.showOk)) {
                                msg = opt.showOk;
                            }
                        }
                        if (isString(msg)) {
                            msgOpt.showOk = isValid;
                            msgOpt.msg = msg;
                        }
                    }
                    $(el).trigger('valid'+CLS_NS_RULE, [method, msgOpt.msg]);
                } else {
                    /* rule message priority:
                        1. custom field message;
                        2. custom DOM message
                        3. global defined message;
                        4. rule returned message;
                        5. default message;
                    */
                    msgOpt.msg = (getDataMsg(el, field, msg, me.messages[method]) || defaults.defaultMsg).replace('{0}', me._getDisplay(el, field.display || ''));
                    $(el).trigger('invalid'+CLS_NS_RULE, [method, msgOpt.msg]);
                }
            }

            // output the debug message
            if (opt.debug) {
                debug.log('   ' + field._i + ': ' + method + ' => ' + (isValid || msgOpt.msg || isValid));
            }

            // the current rule has passed, continue to validate
            if (transfer || isValid && field._i < field.rules.length - 1) {
                field._i++;
                me._checkRule(el, field);
            }
            // field was invalid, or all fields was valid
            else {
                field._i = 0;
                me._validatedField(el, field, msgOpt);
            }
        },

        // Verify a rule form a field
        _checkRule: function(el, field) {
            var me = this,
                ret,
                old,
                key = field.key,
                rule = field.rules[field._i],
                method = rule.method,
                params = rule.params;

            // request has been sent, wait it
            if (me.submiting && me.deferred[key]) return;
            old = field.old;
            field._r = method;

            if ( !field.must && old.ret !== undefined &&
                 old.rule === rule && old.id === el.id &&
                 el.value && old.value === el.value )
            {
                // get result from cache
                ret = old.ret;
            }
            else {
                // get result from current rule
                ret = (getDataRule(el, method) || me.rules[method] || noop).call(me, el, params, field);
            }

            // asynchronous validation
            if (isObject(ret) && isFunction(ret.then)) {
                me.deferred[key] = ret;

                // show loading message
                !me.checkOnly && me.showMsg(el, {
                    type: 'loading',
                    msg: me.options.loadingMsg
                }, field);

                // waiting to parse the response data
                ret.then(
                    function(d, textStatus, jqXHR) {
                        var data = jqXHR.responseText,
                            result,
                            dataFilter = field.dataFilter || me.options.dataFilter;

                        // detect if it is json format
                        if (this.dataType === 'json') {
                            data = d;
                        } else if (data.charAt(0) === '{') {
                            data = $.parseJSON(data) || {};
                        }

                        if (!isFunction(dataFilter)) {
                            dataFilter = function(data) {
                                if (isString(data) || (isObject(data)) ) return data;
                            };
                        }

                        // filter data
                        result = dataFilter(data);
                        if (result === undefined) result = dataFilter(data.data);

                        old.rule = rule;
                        old.ret = result;
                        me._validatedRule(el, field, result);
                    },
                    function(jqXHR, textStatus){
                        me._validatedRule(el, field, textStatus);
                    }
                ).always(function(){
                    delete me.deferred[key];
                });
                // whether the field valid is unknown
                field.isValid = undefined;
            }
            // other result
            else {
                me._validatedRule(el, field, ret);
            }
        },

        // Processing the validation
        _validate: function(el, field) {
            // doesn't validate the element that has "disabled" or "novalidate" attribute
            if ( el.disabled || attr(el, NOVALIDATE) !== null ) return;

            var me = this,
                msgOpt = {},
                group = field.group,
                ret,
                isValid = field.isValid = true;

            if ( !field.rules ) me._parse(el);
            if (me.options.debug) debug.info(field.key);

            // group validation
            if (group) {
                ret = group.callback.call(me, group.$elems);
                if (ret !== undefined) {
                    me.hideMsg(group.target, {}, field);
                    if (ret === true) ret = undefined;
                    else {
                        field._i = 0;
                        field._r = 'group';
                        isValid = false;
                        me.hideMsg(el, {}, field);
                        $.extend(msgOpt, group);
                    }
                }
            }
            // if the field is not required and it has a blank value
            if (isValid && !field.required && !field.must && !el.value) {
                if ( attr(el, DATA_INPUT_STATUS) === 'tip' ) {
                    return;
                }
                if (!checkable(el)) {
                    me._validatedField(el, field, {isValid: true});
                    return;
                }
            }

            // if the results are out
            if (ret !== undefined) {
                me._validatedRule(el, field, ret, msgOpt);
            } else if (field.rule) {
                me._checkRule(el, field);
            }
        },

        /* Detecting whether the value of an element that matches a rule
         *
         * @interface: test
         */
        test: function(el, rule) {
            var me = this,
                ret,
                parts = rRule.exec(rule),
                method,
                params;

            if (parts) {
                method = parts[1];
                if (method in me.rules) {
                    params = parts[2] || parts[3];
                    params = params ? params.split(', ') : undefined;
                    ret = me.rules[method].call(me, el, params);
                }
            }

            return ret === true || ret === undefined || ret === null;
        },

        // Get a range of validation messages
        getRangeMsg: function(value, params, type, suffix) {
            if (!params) return;

            var me = this,
                msg = me.messages[type] || '',
                p = params[0].split('~'),
                a = p[0],
                b = p[1],
                c = 'rg',
                args = [''],
                isNumber = +value === +value;

            if (p.length === 2) {
                if (a && b) {
                    if (isNumber && value >= +a && value <= +b) return true;
                    args = args.concat(p);
                } else if (a && !b) {
                    if (isNumber && value >= +a) return true;
                    args.push(a);
                    c = 'gte';
                } else if (!a && b) {
                    if (isNumber && value <= +b) return true;
                    args.push(b);
                    c = 'lte';
                }
            } else {
                if (value === +a) return true;
                args.push(a);
                c = 'eq';
            }

            if (msg) {
                if (suffix && msg[c + suffix]) {
                    c += suffix;
                }
                args[0] = msg[c];
            }

            return me.renderMsg.apply(null, args);
        },

        /* @interface: renderMsg
         */
        renderMsg: function() {
            var args = arguments,
                tpl = args[0],
                i = args.length;

            if (!tpl) return;

            while (--i) {
                tpl = tpl.replace('{' + i + '}', args[i]);
            }

            return tpl;
        },

        _getDisplay: function(el, str) {
            return !isString(str) ? isFunction(str) ? str.call(this, el) : '' : str;
        },

        _getMsgOpt: function(obj) {
            return $.extend({}, this.msgOpt, isString(obj) ? {msg: obj} : obj);
        },

        _getMsgDOM: function(el, msgOpt) {
            var $el = $(el), $msgbox, datafor, tgt;

            if ($el.is(':input')) {
                tgt = msgOpt.target || attr(el, DATA_TARGET);
                if (tgt) {
                    tgt = isFunction(tgt) ? tgt.call(this, el) : this.$el.find(tgt);
                    if (tgt.length) {
                        if (tgt.is(':input')) {
                            el = tgt.get(0);
                        } else {
                            $msgbox = tgt;
                        }
                    }
                }
                if (!$msgbox) {
                    datafor = !checkable(el) && el.id ? el.id : el.name;
                    $msgbox = this.$el.find(msgOpt.wrapper + '.' + CLS_MSG_BOX + '[for="' + datafor + '"]');
                }
            } else {
                $msgbox = $el;
            }

            if (!$msgbox.length) {
                $el = this.$el.find(tgt || el);
                $msgbox = $('<'+ msgOpt.wrapper + '>').attr({
                    'class': CLS_MSG_BOX + (msgOpt.cls ? ' ' + msgOpt.cls : ''),
                    'style': msgOpt.style || '',
                    'for': datafor
                });
                if (checkable(el)) {
                    var $parent = $el.parent();
                    $msgbox.appendTo( $parent.is('label') ? $parent.parent() : $parent );
                } else {
                    $msgbox[!msgOpt.pos || msgOpt.pos === 'right' ? 'insertAfter' : 'insertBefore']($el);
                }
            }

            return $msgbox;
        },

        /* @interface: showMsg
         */
        showMsg: function(el, msgOpt, /*INTERNAL*/ field) {
            var me = this,
                opt = me.options,
                msgMaker;

            msgOpt = me._getMsgOpt(msgOpt);
            if (!msgOpt.msg && !msgOpt.showOk) return;
            el = $(el).get(0);

            if ($(el).is(INPUT_SELECTOR)) {
                // mark message status
                attr(el, DATA_INPUT_STATUS, msgOpt.type);
                field = field || me.getField(el);
                if (field) {
                    msgOpt.style = field.msgStyle || msgOpt.style;
                    msgOpt.cls = field.msgClass || msgOpt.cls;
                    msgOpt.wrapper = field.msgWrapper || msgOpt.wrapper;
                    msgOpt.target = field.target || opt.target;
                }
            }
            if (!(msgMaker = (field || {}).msgMaker || opt.msgMaker)) return;

            var $msgbox = me._getMsgDOM(el, msgOpt),
                cls = $msgbox[0].className;

            !rPos.test(cls) && $msgbox.addClass(msgOpt.cls);
            if ( isIE6 && msgOpt.pos === 'bottom' ) {
                $msgbox[0].style.marginTop = $(el).outerHeight() + 'px';
            }
            $msgbox.html( msgMaker.call(me, msgOpt) )[0].style.display = '';

            isFunction(msgOpt.show) && msgOpt.show.call(me, $msgbox, msgOpt.type);
        },

        /* @interface: hideMsg
         */
        hideMsg: function(el, msgOpt, /*INTERNAL*/ field) {
            var me = this,
                opt = me.options;

            el = $(el).get(0);
            msgOpt = me._getMsgOpt(msgOpt);
            if ($(el).is(INPUT_SELECTOR)) {
                attr(el, DATA_INPUT_STATUS, null);
                attr(el, ARIA_INVALID, null);
                field = field || me.getField(el);
                if (field) {
                    msgOpt.wrapper = field.msgWrapper || msgOpt.wrapper;
                    msgOpt.target = field.target || opt.target;
                }
            }

            var $msgbox = me._getMsgDOM(el, msgOpt);
            if (!$msgbox.length) return;

            if ( isFunction(msgOpt.hide) ) {
                msgOpt.hide.call(me, $msgbox, msgOpt.type);
            } else {
                $msgbox[0].style.display = 'none';
            }
        },

        /* @interface: mapMsg
         */
        mapMsg: function(obj) {
            var me = this;

            $.each(obj, function(name, msg) {
                var el = me.elements[name] || me.$el.find(':input[name="' + name + '"]')[0];
                me.showMsg(el, msg);
            });
        },

        /* @interface: setMsg
         */
        setMsg: function(obj) {
            new Messages(obj, this.messages);
        },

        /* @interface: setRule
         */
        setRule: function(obj) {
            new Rules(obj, this.rules);
            $.map(this.fields, function(field){
                field.old = {};
            });
        },

        // Get field information
        getField: function(el) {
            var me = this,
                key;

            if (el.id && '#' + el.id in me.fields || !el.name) {
                key = '#' + el.id;
            } else {
                key = el.name;
            }
            if (attr(el, DATA_RULE)) me._parse(el);

            return me.fields[key];
        },

        /* @interface: setField
         */
        setField: function(key, obj) {
            var fields = {};

            // update this field
            if (isString(key)) {
                fields[key] = obj;
            }
            // update fields
            else if (isObject(key)) {
                fields = key;
            }

            this._initFields(fields);
        },

        /* @interface: isFormValid
         */
        isFormValid: function() {
            var fields = this.fields;
            for (var k in fields) {
                if (!fields[k].isValid) {
                    return fields[k].isValid;
                }
            }
            return true;
        },

        /* @interface: holdSubmit
         */
        holdSubmit: function(hold) {
            this.submiting = hold === undefined || hold;
        },

        /* @interface: cleanUp
         */
        cleanUp: function() {
            this._reset(1);
        },

        /* @interface: destroy
         */
        destroy: function() {
            this._reset(1);
            this.$el.off(CLS_NS).removeData(NS);
            attr(this.$el[0], NOVALIDATE, this._novalidate);
        }
    };


    // Rule class
    function Rules(obj, context) {
        var that = context ? context === true ? this : context : Rules.prototype;

        if (!isObject(obj)) return;

        for (var k in obj) {
            that[k] = getRule(obj[k]);
        }
    }

    // Message class
    function Messages(obj, context) {
        var that = context ? context === true ? this : context : Messages.prototype;

        if (!isObject(obj)) return;

        for (var k in obj) {
            if (!obj[k]) return;
            that[k] = obj[k];
        }
    }

    // Rule converted factory
    function getRule(fn) {
        switch ($.type(fn)) {
            case 'function':
                return fn;
            case 'array':
                return function(el) {
                    return fn[0].test(el.value) || fn[1] || false;
                };
            case 'regexp':
                return function(el) {
                    return fn.test(el.value);
                };
        }
    }

    // Convert space-separated keys to jQuery selector
    function keys2selector(keys) {
        var selector = '';

        $.map(keys.split(' '), function(k) {
            selector += ',' + (k.charAt(0) === '#' ? k : '[name="' + k + '"]');
        });

        return selector.substring(1);
    }

    // Get instance by an element
    function getInstance(el) {
        var wrap;

        if (!el || !el.tagName) return;
        switch (el.tagName) {
            case 'INPUT':
            case 'SELECT':
            case 'TEXTAREA':
            case 'BUTTON':
            case 'FIELDSET':
                wrap = el.form || $(el).closest('.' + CLS_WRAPPER);
                break;
            case 'FORM':
                wrap = el;
                break;
            default:
                wrap = $(el).closest('.' + CLS_WRAPPER);
        }

        return $(wrap).data(NS) || $(wrap)[NS]().data(NS);
    }

    function initByInput(e) {

        var el = e.currentTarget, me;
        if (!el.form || attr(el.form, NOVALIDATE) !== null) return;

        me = getInstance(el);
        if (me) {
            me._parse(el);
            me['_'+e.type](e);
        } else {
            attr(el, DATA_RULE, null);
        }
    }

    // Get custom rules on the node
    function getDataRule(el, method) {
        var fn = $.trim(attr(el, DATA_RULE + '-' + method));

        if (!fn) return;
        fn = (new Function("return " + fn))();
        if (fn) return getRule(fn);
    }

    // Get custom messages on the node
    function getDataMsg(el, field, ret, m) {
        var msg = field.msg,
            item = field._r;

        if (isObject(msg)) msg = msg[item];
        if (!isString(msg)) {
            msg = attr(el, DATA_MSG + '-' + item) || attr(el, DATA_MSG) || ret || ( m ? isString(m) ? m : m[item] : '');
        }

        return msg;
    }

    // Get message position
    function getPos(str) {
        var pos;

        if (str) pos = rPos.exec(str);
        return pos ? pos[1] : '';
    }

    // Check whether the element is checkbox or radio
    function checkable(el) {
        return el.tagName === 'INPUT' && el.type === 'checkbox' || el.type === 'radio';
    }

    // parse date string to timestamp
    function parseDate(str) {
        return Date.parse(str.replace(/\.|\-/g, '/'));
    }


    // Global events
    $(document)
    .on('focusin', ':input['+DATA_RULE+']', function(e) {
        initByInput(e);
    })

    .on('click', 'input,button', function(e){
        var input = this, name = input.name;
        if (!input.form) return;

        if (input.type === 'submit') {
            submitButton = input;
            if (attr(input, NOVALIDATE) !== null) {
                novalidateonce = true;
            }
        }
        else if (name && checkable(input)) {
            var elem = input.form.elements[name];
            if (elem.length) elem = elem[0];
            if (attr(elem, DATA_RULE)) {
                initByInput(e);
            }
        }
    })

    .on('submit validate', 'form', function(e) {
        if (attr(this, NOVALIDATE) !== null) return;

        var $form = $(this), me;

        if (!$form.data(NS)) {
            me = $form[NS]().data(NS);
            if (!$.isEmptyObject(me.fields)) {
                me._submit(e);
            } else {
                attr(this, NOVALIDATE, NOVALIDATE);
                $form.off(CLS_NS).removeData(NS);
            }
        }
    });


    // Built-in rules (global)
    new Rules({

        /** required
         * @example:
            required
         */
        required: function(element, params) {
            var val = $.trim(element.value),
                isValid = true;

            if (params) {
                if (params.length === 1) {
                    if (!val && !this.test(element, params[0]) ) {
                        attr(element, ARIA_REQUIRED, null);
                        return null;
                    } else {
                        attr(element, ARIA_REQUIRED, true);
                    }
                } else if (params[0] === 'not') {
                    $.map(params.slice(1), function(v) {
                        if ( val === $.trim(v) ) {
                            isValid = false;
                        }
                    });
                }
            }

            return isValid && !!val;
        },

        /** integer
         * @example:
            integer
            integer[+]
            integer[+0]
            integer[-]
            integer[-0]
         */
        integer: function(element, params) {
            var re, z = '0|',
                p = '[1-9]\\d*',
                key = params ? params[0] : '*';

            switch (key) {
                case '+':
                    re = p;
                    break;
                case '-':
                    re = '-' + p;
                    break;
                case '+0':
                    re = z + p;
                    break;
                case '-0':
                    re = z + '-' + p;
                    break;
                default:
                    re = z + '-?' + p;
            }
            re = '^(?:' + re + ')$';

            return new RegExp(re).test(element.value) || this.messages.integer[key];
        },

        /** match another field
         * @example:
            match[password]    Match the password field (two values ??????must be the same)
            match[eq, password]  Ditto
            match[neq, count]  The value must be not equal to the value of the count field
            match[lt, count]   The value must be less than the value of the count field
            match[lte, count]  The value must be less than or equal to the value of the count field
            match[gt, count]   The value must be greater than the value of the count field
            match[gte, count]  The value must be greater than or equal to the value of the count field
         **/
        match: function(element, params, field) {
            if (!params) return;
            var me = this,
                a, b,
                key, msg, type = 'eq',
                selector2, elem2, field2;
            if(typeof params[0]  === 'string' && params[0].indexOf(",") > -1) {
            	params = params[0].split(",");
                type = params[0];
                key = params[1];
            }
            if (params.length === 1) {
                key = params[0];
            }  else {
                type = params[0];
                key = params[1];
            }

            selector2 = key.charAt(0) === '#' ? key : ':input[name="' + key + '"]';
            elem2 = me.$el.find(selector2)[0];
            // If the compared field is not exist
            if (!elem2) return;
            field2 = me.getField(elem2);
            a = element.value;
            b = elem2.value;

            if (!field._match) {
                me.$el.on('valid'+CLS_NS_FIELD+CLS_NS, selector2, function(){
                    $(element).trigger('validate');
                });
                field._match = field2._match = 1;
            }

            // If both fields are blank
            if (!field.required && a === "" && b === "") {
                return null;
            }

            if (params[2]) {
                if (params[2] === 'date') {
                    a = parseDate(a);
                    b = parseDate(b);
                } else if (params[2] === 'time') {
                    a = +a.replace(':', '');
                    b = +b.replace(':', '');
                }
            }

            // If the compared field is incorrect, we only ensure that this field is correct.
            if (type !== "eq" && !isNaN(+a) && isNaN(+b)) {
                return true;
            }

            if(typeof me.messages.match == 'string'){
            	msg = me.messages.match.replace('{1}', me._getDisplay(element, field2.display || key));
            }else{
            	msg = me.messages.match[type].replace('{1}', me._getDisplay(element, field2.display || key));
            }


            switch (type) {
                case 'lt':
                    return (+a < +b) || msg;
                case 'lte':
                    return (+a <= +b) || msg;
                case 'gte':
                    return (+a >= +b) || msg;
                case 'gt':
                    return (+a > +b) || msg;
                case 'neq':
                    return (a !== b) || msg;
                default:
                    return (a === b) || msg;
            }
        },

        /** range numbers
         * @example:
            range[0~99]    Number 0-99
            range[0~]      Number greater than or equal to 0
            range[~100]    Number less than or equal to 100
         **/
        range: function(element, params) {
            return this.getRangeMsg(+element.value, params, 'range');
        },

        /** how many checkbox or radio inputs that checked
         * @example:
            checked;       no empty, same to required
            checked[1~3]   1-3 items
            checked[1~]    greater than 1 item
            checked[~3]    less than 3 items
            checked[3]     3 items
         **/
        checked: function(element, params, field) {
            if (!checkable(element)) return;

            var me = this,
                elem, count;

            count = me.$el.find('input[name="' + element.name + '"]').filter(function() {
                var el = this;
                if (!elem && checkable(el)) elem = el;
                return !el.disabled && el.checked && $(el).is(':visible');
            }).length;

            if (params) {
                return me.getRangeMsg(count, params, 'checked');
            } else {
                return !!count || getDataMsg(elem, field, '') || me.messages.checked || me.messages.required;
            }
        },

        /** length of a characters (You can pass the second parameter "true", will calculate the length in bytes)
         * @example:
            length[6~16]        6-16 characters
            length[6~]          Greater than 6 characters
            length[~16]         Less than 16 characters
            length[~16, true]   Less than 16 characters, non-ASCII characters calculating two-character
         **/
        length: function(element, params) {
            var value = element.value,
                len = (params[1] ? value.replace(rDoubleBytes, 'xx') : value).length;

            return this.getRangeMsg(len, params, 'length', (params[1] ? '_2' : ''));
        },

        /** remote validation
         *  remote([get:]url [, name1, [name2 ...]]);
         *  Adaptation three kinds of results (Front for the successful, followed by a failure):
                1. text:
                    ''  'Error Message'
                2. json:
                    {"ok": ""}  {"error": "Error Message"}
                3. json wrapper:
                    {"status": 1, "data": {"ok": ""}}  {"status": 1, "data": {"error": "Error Message"}}
         * @example:
            The simplest:       remote(path/to/server.php);
            With parameters:    remote(path/to/server.php, name1, name2, ...);
            By GET:             remote(get:path/to/server.php, name1, name2, ...);
         */
        remote: function(element, params) {
            if (!params) return;

            var me = this,
                arr = rAjaxType.exec(params[0]),
                url = arr[2],
                type = (arr[1] || 'POST').toUpperCase(),
                search,
                data = {};

            data[element.name] = element.value;
            // There are extra fields
            if (params[1]) {
                $.map(params.slice(1), function(name) {
                    var arr = name.split(':'), selector;
                    name = $.trim(arr[0]);
                    selector = $.trim(arr[1] || '') || name;
                    data[ name ] = me.$el.find( selector.charAt(0)==='#' ? selector : ':input[name="' + selector + '"]').val();
                });
            }
            data = $.param(data);

            if (type === 'POST') {
                search = url.indexOf('?');
                if (search !== -1) {
                    data += '&' + url.substring(search + 1, url.length);
                    url = url.substring(0, search);
                }
            }

            // Asynchronous validation need to return jqXHR objects
            return $.ajax({
                url: url,
                type: type,
                data: data,
                cache: false
            });
        },

        /** filters, direct filtration without prompting error (support custom regular expressions)
         * @example:
         *  filter          filter "<>"
         *  filter(regexp)  filter the "regexp" matched characters
         */
        filter: function(element, params) {
            element.value = element.value.replace( params ? (new RegExp("[" + params[0] + "]", "gm")) : rUnsafe, '' );
        }
    });


    /** @interface: config
     *  @usage:
        .config( obj )
     */
    Validator.config = function(obj) {
        $.each(obj, function(k, o) {
            if (k === 'rules') {
                new Rules(o);
            } else if (k === 'messages') {
                new Messages(o);
            } else {
                defaults[k] = o;
            }
        });
    };

    /** @interface: setTheme
     *  @usage:
        .setTheme( name, obj )
        .setTheme( obj )
     */
    Validator.setTheme = function(name, obj) {
        if (isObject(name)) {
            $.each(name, function(i, o) {
                themes[i] = o;
            });
        } else if (isString(name) && isObject(obj)) {
            themes[name] = obj;
        }
    };

    $[NS] = Validator;

}(jQuery));


/*********************************
 * Themes, rules, and i18n support
 * Locale: Chinese; ??????
 *********************************/
(function(factory) {
    if (typeof define === 'function') {
        define(function(require, exports, module){
            var $ = require('jquery');
            $._VALIDATOR_URI = module.uri;
            require('../src/jquery.validator')($);
            factory($);
        });
    } else {
        factory(jQuery);
    }
}(function($) {
    /* Global configuration
     */
    $.validator.config({
        //stopOnError: false,
        //theme: 'yellow_right',
        defaultMsg: "{0}???????????????",
        loadingMsg: "????????????...",

        // Custom rules
        rules: {
            digits: [/^\d+$/, "???????????????"]
            ,letters: [/^[a-z]+$/i, "{0}??????????????????"]
            ,tel: [/^(?:(?:0\d{2,3}[\- ]?[1-9]\d{6,7})|(?:[48]00[\- ]?[1-9]\d{6}))$/, "?????????????????????"]
            ,mobile: [/^1[3456789]\d{9}$/, "????????????????????????"]
            ,email: [/^[\w\+\-]+(\.[\w\+\-]+)*@[a-z\d\-]+(\.[a-z\d\-]+)*\.([a-z]{2,4})$/i, "?????????????????????"]
            ,qq: [/^[1-9]\d{4,}$/, "QQ??????????????????"]
            ,date: [/^\d{4}-\d{1,2}-\d{1,2}$/, "????????????????????????,???:yyyy-mm-dd"]
            ,time: [/^([01]\d|2[0-3])(:[0-5]\d){1,2}$/, "????????????????????????,???:14:30???14:30:00"]
            ,ID_card: [/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/, "?????????????????????????????????"]
            ,url: [/^(https?|ftp):\/\/[^\s]+$/i, "?????????????????????"]
            ,postcode: [/^[1-9]\d{5}$/, "???????????????????????????"]
            ,chinese: [/^[\u0391-\uFFE5]+$/, "???????????????"]
            ,chineseName : [/^[\u0391-\uFFE5]{2,6}$/,"?????????2-6???????????????"]
            ,username2: [/^\w{4,16}$/, "?????????4-16??????????????????????????????"]
            ,password: [/^[0-9a-zA-Z]{6,16}$/, "?????????6-16????????????????????????"]
            ,password2: [/^[^\s]{6,20}$/, "?????????????????????????????????6-20?????????"]
            ,fileImage : [/\.jpg$|\.png$/,"??????????????????JPG,PNG"]
            ,address : [/^[\u0391-\uFFE5][\u0391-\uFFE5\d]+$/,"?????????????????????"]
            ,accept: function (element, params){
                if (!params) return true;
                var ext = params[0];
                return (ext === '*') ||
                       (new RegExp(".(?:" + (ext || "png|jpg|jpeg|gif") + ")$", "i")).test(element.value) ||
                       this.renderMsg("?????????{1}??????", ext.replace('|', ','));
            }
            ,username: function(el){
                var userNameRegEx = /^[a-zA-Z0-9]\w+$/,
                numRegEx = /^[_\d]+$/
                userName = $.trim($(el).val());
                if(!userName || userName.length < 4 || userName.length > 16 || !userNameRegEx.test(userName)) {
                     return '?????????4-16????????????????????????????????????????????????';
                } else if(numRegEx.test(userName)){
                     return '??????????????????????????????';
                } else if ( locationName && locationName[userName]) {
                     return '????????????????????????';
                }
            }
            ,judegRepeat: function(el){
                var elVal = $.trim($(el).val());
                return $.ajax({
                    url: '1.php',
                    type: 'post',
                    data: {"username": elVal},
                    dataType: 'json',
                    success: function(data){
                        if(typeof data == 'object' && !!data.error){
                            data.error = "????????????????????????";
                            locationName[elVal] = true;
                        }
                    }
                });
            }
        }
    });

    /* Default error messages
     */
    $.validator.config({
        messages: {
            required: "{0}????????????",
            remote: "{0}????????????",
            integer: {
                '*': "???????????????",
                '+': "??????????????????",
                '+0': "?????????????????????0",
                '-': "??????????????????",
                '-0': "?????????????????????0"
            },
            match: {
                eq: "{0}???{1}?????????",
                neq: "{0}???{1}????????????",
                lt: "{0}????????????{1}",
                gt: "{0}????????????{1}",
                lte: "{0}?????????????????????{1}",
                gte: "{0}?????????????????????{1}"
            },
            range: {
                rg: "?????????{1}???{2}??????",
                gte: "????????????????????????{1}??????",
                lte: "????????????????????????{1}??????"
            },
            checked: {
                eq: "?????????{1}???",
                rg: "?????????{1}???{2}???",
                gte: "???????????????{1}???",
                lte: "???????????????{1}???"
            },
            length: {
                eq: "?????????{1}?????????",
                rg: "?????????{1}???{2}?????????",
                gte: "???????????????{1}?????????",
                lte: "???????????????{1}?????????",
                eq_2: "",
                rg_2: "",
                gte_2: "",
                lte_2: ""
            }
        }
    });

    /* Themes
     */
    var TPL_ARROW = '<span class="n-arrow"><b>???</b><i>???</i></span>';
    $.validator.setTheme({
        'simple_right': {
            formClass: 'n-simple',
            msgClass: 'n-right'
        },
        'simple_bottom': {
            formClass: 'n-simple',
            msgClass: 'n-bottom'
        },
        'yellow_top': {
            formClass: 'n-yellow',
            msgClass: 'n-top',
            msgArrow: TPL_ARROW
        },
        'yellow_right': {
            formClass: 'n-yellow',
            msgClass: 'n-right',
            msgArrow: TPL_ARROW
        },
        'yellow_right_effect': {
            formClass: 'n-yellow',
            msgClass: 'n-right',
            msgArrow: TPL_ARROW,
            msgShow: function($msgbox, type){
                var $el = $msgbox.children();
                if ($el.is(':animated')) return;
                if (type === 'error') {
                    $el.css({
                        left: '20px',
                        opacity: 0
                    }).delay(100).show().stop().animate({
                        left: '-4px',
                        opacity: 1
                    }, 150).animate({
                        left: '3px'
                    }, 80).animate({
                        left: 0
                    }, 80);
                } else {
                    $el.css({
                        left: 0,
                        opacity: 1
                    }).fadeIn(200);
                }
            },
            msgHide: function($msgbox, type){
                var $el = $msgbox.children();
                $el.stop().delay(100).show().animate({
                    left: '20px',
                    opacity: 0
                }, 300, function(){
                    $msgbox.hide();
                });
            }
        }
    });
}));;
/*
????????????

mabaoyue 2013-09-03

 */
;
(function($) {

    //"use strict";

    var methods = {

        /**
         * Kind of the constructor, called before any action
         * @param {Map} user options
         */
        init: function(options) {
            var form = this;
            if (!form.data('jqv') || form.data('jqv') == null) {
                options = methods._saveOptions(form, options);
                // bind all formError elements to close on click
                $(document).on("click", ".formError", function() {
                    $(this).fadeOut(150, function() {
                        // remove prompt once invisible
                        $(this).parent('.formErrorOuter').remove();
                        $(this).remove();
                    });
                });
            }
            return this;
        },
        /**
         * Attachs jQuery.validationEngine to form.submit and field.blur events
         * Takes an optional params: a list of options
         * ie. jQuery("#formID1").validationEngine('attach', {promptPosition : "centerRight"});
         */
        attach: function(userOptions) {

            var form = this;
            var options;

            if (userOptions)
                options = methods._saveOptions(form, userOptions);
            else
                options = form.data('jqv');

            options.validateAttribute = (form.find("[data-validation-engine*=validate]").length) ? "data-validation-engine" : "class";
            if (options.binded) {

                // delegate fields
                form.on(options.validationEventTrigger, "[" + options.validateAttribute + "*=validate]:not([type=checkbox]):not([type=radio]):not(.datepicker)", methods._onFieldEvent);
                form.on("click", "[" + options.validateAttribute + "*=validate][type=checkbox],[" + options.validateAttribute + "*=validate][type=radio]", methods._onFieldEvent);
                form.on(options.validationEventTrigger, "[" + options.validateAttribute + "*=validate][class*=datepicker]", {
                    "delay": 300
                }, methods._onFieldEvent);
            }
            if (options.autoPositionUpdate) {
                $(window).bind("resize", {
                    "noAnimation": true,
                    "formElem": form
                }, methods.updatePromptsPosition);
            }
            form.on("click", "a[data-validation-engine-skip], a[class*='validate-skip'], button[data-validation-engine-skip], button[class*='validate-skip'], input[data-validation-engine-skip], input[class*='validate-skip']", methods._submitButtonClick);
            form.removeData('jqv_submitButton');

            // bind form.submit
            form.on("submit", methods._onSubmitEvent);
            return this;
        },
        /**
         * Unregisters any bindings that may point to jQuery.validaitonEngine
         */
        detach: function() {

            var form = this;
            var options = form.data('jqv');

            // unbind fields
            form.find("[" + options.validateAttribute + "*=validate]").not("[type=checkbox]").off(options.validationEventTrigger, methods._onFieldEvent);
            form.find("[" + options.validateAttribute + "*=validate][type=checkbox],[class*=validate][type=radio]").off("click", methods._onFieldEvent);

            // unbind form.submit
            form.off("submit", methods._onSubmitEvent);
            form.removeData('jqv');

            form.off("click", "a[data-validation-engine-skip], a[class*='validate-skip'], button[data-validation-engine-skip], button[class*='validate-skip'], input[data-validation-engine-skip], input[class*='validate-skip']", methods._submitButtonClick);
            form.removeData('jqv_submitButton');

            if (options.autoPositionUpdate)
                $(window).off("resize", methods.updatePromptsPosition);

            return this;
        },
        /**
         * Validates either a form or a list of fields, shows prompts accordingly.
         * Note: There is no ajax form validation with this method, only field ajax validation are evaluated
         *
         * @return true if the form validates, false if it fails
         */
        validate: function() {
            var element = $(this);
            var valid = null;

            if (element.is("form") || element.hasClass("validationEngineContainer")) {
                if (element.hasClass('validating')) {
                    // form is already validating.
                    // Should abort old validation and start new one. I don't know how to implement it.
                    return false;
                } else {
                    element.addClass('validating');
                    var options = element.data('jqv');
                    var valid = methods._validateFields(this);

                    // If the form doesn't validate, clear the 'validating' class before the user has a chance to submit again
                    setTimeout(function() {
                        element.removeClass('validating');
                    }, 100);
                    if (valid && options.onSuccess) {
                        options.onSuccess();
                    } else if (!valid && options.onFailure) {
                        options.onFailure();
                    }
                }
            } else if (element.is('form') || element.hasClass('validationEngineContainer')) {
                element.removeClass('validating');
            } else {
                // field validation
                var form = element.closest('form, .validationEngineContainer'),
                    options = (form.data('jqv')) ? form.data('jqv') : $.validationEngine.defaults,
                    valid = methods._validateField(element, options);

                if (valid && options.onFieldSuccess)
                    options.onFieldSuccess();
                else if (options.onFieldFailure && options.InvalidFields.length > 0) {
                    options.onFieldFailure();
                }
            }
            if (options.onValidationComplete) {
                // !! ensures that an undefined return is interpreted as return false but allows a onValidationComplete() to possibly return true and have form continue processing
                return !!options.onValidationComplete(form, valid);
            }
            return valid;
        },
        /**
         *  Redraw prompts position, useful when you change the DOM state when validating
         */
        updatePromptsPosition: function(event) {

            if (event && this == window) {
                var form = event.data.formElem;
                var noAnimation = event.data.noAnimation;
            } else
                var form = $(this.closest('form, .validationEngineContainer'));

            var options = form.data('jqv');
            // No option, take default one
            form.find('[' + options.validateAttribute + '*=validate]').not(":disabled").each(function() {
                var field = $(this);
                if (options.prettySelect && field.is(":hidden"))
                    field = form.find("#" + options.usePrefix + field.attr('id') + options.useSuffix);
                var prompt = methods._getPrompt(field);
                var promptText = $(prompt).find(".formErrorContent").html();

                if (prompt)
                    methods._updatePrompt(field, $(prompt), promptText, undefined, false, options, noAnimation);
            });
            return this;
        },
        /**
         * Displays a prompt on a element.
         * Note that the element needs an id!
         *
         * @param {String} promptText html text to display type
         * @param {String} type the type of bubble: 'pass' (green), 'load' (black) anything else (red)
         * @param {String} possible values topLeft, topRight, bottomLeft, centerRight, bottomRight
         */
        showPrompt: function(promptText, type, promptPosition, showArrow) {
            var form = this.closest('form, .validationEngineContainer');
            var options = form.data('jqv');
            // No option, take default one
            if (!options)
                options = methods._saveOptions(this, options);
            if (promptPosition)
                options.promptPosition = promptPosition;
            options.showArrow = showArrow == true;
            methods._showPrompt(this, promptText, type, false, options);

            return this;
        },
        /**
         * Closes form error prompts, CAN be invidual
         */
        hide: function() {
            var form = $(this).closest('form, .validationEngineContainer');
            var options = form.data('jqv');
            var fadeDuration = (options && options.fadeDuration) ? options.fadeDuration : 0.3;
            var closingtag;

            // if ($(this).is("form") || $(this).hasClass("validationEngineContainer")) {
            //     closingtag = "parentForm" + methods._getClassName($(this).attr("id"));
            // } else {
            //     closingtag = methods._getClassName($(this).attr("id")) + "formError";
            // }
            closingtag = methods._getClassName($(this).attr("id")) + "formError";
            $('.' + closingtag).fadeTo(fadeDuration, 0.3, function() {
                $(this).parent('.formErrorOuter').remove();
                $(this).remove();
            });
            return this;
        },
        /**
         * Closes all error prompts on the page
         */
        hideAll: function() {

            var form = this;
            var options = form.data('jqv');
            var duration = options ? options.fadeDuration : 300;
            $('.formError').fadeTo(duration, 300, function() {
                $(this).parent('.formErrorOuter').remove();
                $(this).remove();
            });
            return this;
        },
        /**
         * Typically called when user exists a field using tab or a mouse click, triggers a field
         * validation
         */
        _onFieldEvent: function(event) {
            var field = $(this);
            var form = field.closest('form, .validationEngineContainer');
            var options = form.data('jqv');
            options.eventTrigger = "field";
            // validate the current field
            window.setTimeout(function() {
                methods._validateField(field, options);
                if (options.InvalidFields.length == 0 && options.onFjieldSuccess) {
                    options.onFieldSuccess();
                } else if (options.InvalidFields.length > 0 && options.onFieldFailure) {
                    options.onFieldFailure();
                }
            }, (event.data) ? event.data.delay : 0);

        },
        /**
         * Called when the form is submited, shows prompts accordingly
         *
         * @param {jqObject}
         *            form
         * @return false if form submission needs to be cancelled
         */
        _onSubmitEvent: function() {
            var form = $(this);
            var options = form.data('jqv');

            //check if it is trigger from skipped button
            if (form.data("jqv_submitButton")) {
                var submitButton = $("#" + form.data("jqv_submitButton"));
                if (submitButton) {
                    if (submitButton.length > 0) {
                        if (submitButton.hasClass("validate-skip") || submitButton.attr("data-validation-engine-skip") == "true")
                            return true;
                    }
                }
            }

            options.eventTrigger = "submit";

            // validate each field 
            // (- skip field ajax validation, not necessary IF we will perform an ajax form validation)
            var r = methods._validateFields(form);

            if (r && options.ajaxFormValidation) {
                methods._validateFormWithAjax(form, options);
                // cancel form auto-submission - process with async call onAjaxFormComplete
                return false;
            }
            if(r && options.onSuccess){
                return !!options.onSuccess(form, options);
                 
            }else if(!r && options.onFailure){
                 options.onFailure(form , options);
                 
            }
            if (options.onValidationComplete) {
                // !! ensures that an undefined return is interpreted as return false but allows a onValidationComplete() to possibly return true and have form continue processing
                return !!options.onValidationComplete(form, r);
            }
            return r;
        },
        /**
         * Return true if the ajax field validations passed so far
         * @param {Object} options
         * @return true, is all ajax validation passed so far (remember ajax is async)
         */
        _checkAjaxStatus: function(options) {
            var status = true;
            $.each(options.ajaxValidCache, function(key, value) {
                if (!value) {
                    status = false;
                    // break the each
                    return false;
                }
            });
            return status;
        },

        /**
         * Return true if the ajax field is validated
         * @param {String} fieldid
         * @param {Object} options
         * @return true, if validation passed, false if false or doesn't exist
         */
        _checkAjaxFieldStatus: function(fieldid, options) {
            return options.ajaxValidCache[fieldid] == true;
        },
        /**
         * Validates form fields, shows prompts accordingly
         *
         * @param {jqObject}
         *            form
         * @param {skipAjaxFieldValidation}
         *            boolean - when set to true, ajax field validation is skipped, typically used when the submit button is clicked
         *
         * @return true if form is valid, false if not, undefined if ajax form validation is done
         */
        _validateFields: function(form) {
            var options = form.data('jqv');

            // this variable is set to true if an error is found
            var errorFound = false;

            // Trigger hook, start validation
            form.trigger("jqv.form.validating");
            // first, evaluate status of non ajax fields
            var first_err = null;
            form.find('[' + options.validateAttribute + '*=validate]').not(":disabled").each(function() {
                var field = $(this);
                var names = [];
                
                if ($.inArray(field.attr('name'), names) < 0) {
                    errorFound |= methods._validateField(field, options);
                    if (errorFound && first_err == null)
                        if (field.is(":hidden") && options.prettySelect)
                            first_err = field = form.find("#" + options.usePrefix + methods._jqSelector(field.attr('id')) + options.useSuffix);
                        else {

                            //Check if we need to adjust what element to show the prompt on
                            //and and such scroll to instead
                            if (field.data('jqv-prompt-at') instanceof jQuery) {
                                field = field.data('jqv-prompt-at');
                            } else if (field.data('jqv-prompt-at')) {
                                field = $(field.data('jqv-prompt-at'));
                            }
                            first_err = field;
                        }
                    if (options.doNotShowAllErrosOnSubmit)
                        return false;
                    names.push(field.attr('name'));

                    //if option set, stop checking validation rules after one error is found
                    if (options.showOneMessage == true && errorFound) {
                        return false;
                    }
                }
            });

            // second, check to see if all ajax calls completed ok
            // errorFound |= !methods._checkAjaxStatus(options);

            // third, check status and scroll the container accordingly
            form.trigger("jqv.form.result", [errorFound]);

            if (errorFound) {
                if (options.scroll) {
                    var destination = first_err.offset().top;
                    var fixleft = first_err.offset().left;

                    //prompt positioning adjustment support. Usage: positionType:Xshift,Yshift (for ex.: bottomLeft:+20 or bottomLeft:-20,+10)
                    var positionType = options.promptPosition;
                    if (typeof(positionType) == 'string' && positionType.indexOf(":") != -1)
                        positionType = positionType.substring(0, positionType.indexOf(":"));

                    if (positionType != "bottomRight" && positionType != "bottomLeft") {
                        var prompt_err = methods._getPrompt(first_err);
                        if (prompt_err) {
                            destination = prompt_err.offset().top;
                        }
                    }

                    // Offset the amount the page scrolls by an amount in px to accomodate fixed elements at top of page
                    if (options.scrollOffset) {
                        destination -= options.scrollOffset;
                    }

                    // get the position of the first error, there should be at least one, no need to check this
                    //var destination = form.find(".formError:not('.greenPopup'):first").offset().top;
                    if (options.isOverflown) {
                        var overflowDIV = $(options.overflownDIV);
                        if (!overflowDIV.length) return false;
                        var scrollContainerScroll = overflowDIV.scrollTop();
                        var scrollContainerPos = -parseInt(overflowDIV.offset().top);

                        destination += scrollContainerScroll + scrollContainerPos - 5;
                        var scrollContainer = $(options.overflownDIV + ":not(:animated)");

                        scrollContainer.animate({
                            scrollTop: destination
                        }, 1100, function() {
                            if (options.focusFirstField) first_err.focus();
                        });

                    } else {
                        $("html, body").animate({
                            scrollTop: destination
                        }, 1100, function() {
                            if (options.focusFirstField) first_err.focus();
                        });
                        $("html, body").animate({
                            scrollLeft: fixleft
                        }, 1100)
                    }

                } else if (options.focusFirstField)
                    first_err.focus();
                return false;
            }
            return true;
        },
        /**
         * This method is called to perform an ajax form validation.
         * During this process all the (field, value) pairs are sent to the server which returns a list of invalid fields or true
         *
         * @param {jqObject} form
         * @param {Map} options
         */
        _validateFormWithAjax: function(form, options) {
            !!options.modForm && options.modForm(form, options);
            var data = options.data || form.serialize();
            var type = (options.ajaxFormValidationMethod) ? options.ajaxFormValidationMethod : "GET";
            var url = (options.ajaxFormValidationURL) ? options.ajaxFormValidationURL : form.attr("action");
            var dataType = (options.dataType) ? options.dataType : "json";
            $.ajax({
                type: type,
                url: url,
                cache: false,
                dataType: dataType,
                data: data,
                form: form,
                methods: methods,
                options: options,
                beforeSend: function() {
                    return options.onBeforeAjaxFormValidation(form, options);
                },
                error: function(data, transport) {
                    methods._ajaxError(data, transport);
                },
                success: function(json) {

                    if ((dataType == "json") && (json !== true)) {
                        // getting to this case doesn't necessary means that the form is invalid
                        // the server may return green or closing prompt actions
                        // this flag helps figuring it out
                        var errorInForm = false;
                        for (var i = 0; i < json.length; i++) {
                            var value = json[i];

                            var errorFieldId = value[0];
                            var errorField = $($("#" + errorFieldId)[0]);

                            // make sure we found the element
                            if (errorField.length == 1) {

                                // promptText or selector
                                var msg = value[2];
                                // if the field is valid
                                if (value[1] == true) {

                                    if (msg == "" || !msg) {
                                        // if for some reason, status==true and error="", just close the prompt
                                        methods._closePrompt(errorField);
                                    } else {
                                        // the field is valid, but we are displaying a green prompt
                                        if (options.allrules[msg]) {
                                            var txt = options.allrules[msg].alertTextOk;
                                            if (txt)
                                                msg = txt;
                                        }
                                        if (options.showPrompts) methods._showPrompt(errorField, msg, "pass", false, options, true);
                                    }
                                } else {
                                    // the field is invalid, show the red error prompt
                                    errorInForm |= true;
                                    if (options.allrules[msg]) {
                                        var txt = options.allrules[msg].alertText;
                                        if (txt)
                                            msg = txt;
                                    }
                                    if (options.showPrompts) methods._showPrompt(errorField, msg, "", false, options, true);
                                }
                            }
                        }
                        
                        options.onAjaxFormComplete(!errorInForm, form, json, options);
                    } else{
                        //!!options.modForm && options.modForm(form, json, options);
                        options.onAjaxFormComplete(true, form, json, options);
                    }
                         

                }
            });

        },
        /**
         * Validates field, shows prompts accordingly
         *
         * @param {jqObject}
         *            field
         * @param {Array[String]}
         *            field's validation rules
         * @param {Map}
         *            user options
         * @return false if field is valid (It is inversed for *fields*, it return false on validate and true on errors.)
         */
        _validateField: function(field, options, skipAjaxValidation) {

            if (!field.attr("id")) {
                field.attr("id", "form-validation-field-" + $.validationEngine.fieldIdCounter);
                ++$.validationEngine.fieldIdCounter;
            }

            if (!options.validateNonVisibleFields && (field.is(":hidden") && !options.prettySelect || field.parent().is(":hidden")))
                return false;

            var rulesParsing = field.attr(options.validateAttribute);
            var getRules = /validate\[(.*)\]/.exec(rulesParsing);

            if (!getRules)
                return false;
            var str = getRules[1];
            var rules = str.split(/\[|,|\]/);

            // true if we ran the ajax validation, tells the logic to stop messing with prompts
            var isAjaxValidator = false;
            var fieldName = field.attr("name");
            var promptText = "";
            var promptType = "";
            var required = false;
            var limitErrors = false;
            options.isError = false;
            options.showArrow = true;

            // If the programmer wants to limit the amount of error messages per field,
            if (options.maxErrorsPerField > 0) {
                limitErrors = true;
            }

            var form = $(field.closest("form, .validationEngineContainer"));
            // Fix for adding spaces in the rules
            for (var i = 0; i < rules.length; i++) {
                rules[i] = rules[i].replace(" ", "");
                // Remove any parsing errors
                if (rules[i] === '') {
                    delete rules[i];
                }
            }

            for (var i = 0, field_errors = 0; i < rules.length; i++) {

                // If we are limiting errors, and have hit the max, break
                if (limitErrors && field_errors >= options.maxErrorsPerField) {
                    // If we haven't hit a required yet, check to see if there is one in the validation rules for this
                    // field and that it's index is greater or equal to our current index
                    if (!required) {
                        var have_required = $.inArray('required', rules);
                        required = (have_required != -1 && have_required >= i);
                    }
                    break;
                }


                var errorMsg = undefined;
                switch (rules[i]) {

                    case "required":
                        required = true;
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._required);
                        break;
                    case "custom":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._custom);
                        break;
                    case "groupRequired":
                        // Check is its the first of group, if not, reload validation with first field
                        // AND continue normal validation on present field
                        var classGroup = "[" + options.validateAttribute + "*=" + rules[i + 1] + "]";
                        var firstOfGroup = form.find(classGroup).eq(0);
                        if (firstOfGroup[0] != field[0]) {

                            methods._validateField(firstOfGroup, options, skipAjaxValidation);
                            options.showArrow = true;

                        }
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._groupRequired);
                        if (errorMsg) required = true;
                        options.showArrow = false;
                        break;
                    case "ajax":
                        // AJAX defaults to returning it's loading message
                        errorMsg = methods._ajax(field, rules, i, options);
                        if (errorMsg) {
                            promptType = "load";
                        }
                        break;
                    case "minSize":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._minSize);
                        break;
                    case "maxSize":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._maxSize);
                        break;
                    case "min":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._min);
                        break;
                    case "max":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._max);
                        break;
                    case "past":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._past);
                        break;
                    case "future":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._future);
                        break;
                    case "dateRange":
                        var classGroup = "[" + options.validateAttribute + "*=" + rules[i + 1] + "]";
                        options.firstOfGroup = form.find(classGroup).eq(0);
                        options.secondOfGroup = form.find(classGroup).eq(1);

                        //if one entry out of the pair has value then proceed to run through validation
                        if (options.firstOfGroup[0].value || options.secondOfGroup[0].value) {
                            errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._dateRange);
                        }
                        if (errorMsg) required = true;
                        options.showArrow = false;
                        break;

                    case "dateTimeRange":
                        var classGroup = "[" + options.validateAttribute + "*=" + rules[i + 1] + "]";
                        options.firstOfGroup = form.find(classGroup).eq(0);
                        options.secondOfGroup = form.find(classGroup).eq(1);

                        //if one entry out of the pair has value then proceed to run through validation
                        if (options.firstOfGroup[0].value || options.secondOfGroup[0].value) {
                            errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._dateTimeRange);
                        }
                        if (errorMsg) required = true;
                        options.showArrow = false;
                        break;
                    case "maxCheckbox":
                        field = $(form.find("input[name='" + fieldName + "']"));
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._maxCheckbox);
                        break;
                    case "minCheckbox":
                        field = $(form.find("input[name='" + fieldName + "']"));
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._minCheckbox);
                        break;
                    case "equals":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._equals);
                        break;
                    case "funcCall":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._funcCall);
                        break;
                    case "creditCard":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._creditCard);
                        break;
                    case "condRequired":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._condRequired);
                        if (errorMsg !== undefined) {
                            required = true;
                        }
                        break;
                        // add by mabaoyue 2013-7-12
                    case "less":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._less);
                        break;
                    case "greater":
                        errorMsg = methods._getErrorMessage(form, field, rules[i], rules, i, options, methods._greater);
                        break;

                    default:
                }

                var end_validation = false;

                // If we were passed back an message object, check what the status was to determine what to do
                if (typeof errorMsg == "object") {
                    switch (errorMsg.status) {
                        case "_break":
                            end_validation = true;
                            break;
                            // If we have an error message, set errorMsg to the error message
                        case "_error":
                            errorMsg = errorMsg.message;
                            break;
                            // If we want to throw an error, but not show a prompt, return early with true
                        case "_error_no_prompt":
                            return true;
                            break;
                            // Anything else we continue on
                        default:
                            break;
                    }
                }

                // If it has been specified that validation should end now, break
                if (end_validation) {
                    break;
                }

                // If we have a string, that means that we have an error, so add it to the error message.
                if (typeof errorMsg == 'string') {
                    //promptText += errorMsg + "<br/>";
                    //if(field.data("msg")) errorMsg = field.data("msg");
                    //var msg = field.data("msg");

                    promptText += errorMsg + "&nbsp;";
                    options.isError = true;
                    field_errors++;
                }
            }
            // If the rules required is not added, an empty field is not validated
            if (!required && !(field.val()) && field.val().length < 1) options.isError = false;

            // Hack for radio/checkbox group button, the validation go into the
            // first radio/checkbox of the group
            var fieldType = field.prop("type");
            var positionType = field.data("promptPosition") || options.promptPosition;

            if ((fieldType == "radio" || fieldType == "checkbox") && form.find("input[name='" + fieldName + "']").size() > 1) {
                if (positionType === 'inline') {
                    field = $(form.find("input[name='" + fieldName + "'][type!=hidden]:last"));
                } else {
                    field = $(form.find("input[name='" + fieldName + "'][type!=hidden]:first"));
                }
                options.showArrow = false;
            }

            if (field.is(":hidden") && options.prettySelect) {
                field = form.find("#" + options.usePrefix + methods._jqSelector(field.attr('id')) + options.useSuffix);
            }

            if (options.isError && options.showPrompts) {
                methods._showPrompt(field, promptText, promptType, false, options);
            } else {
                if (!isAjaxValidator) methods._closePrompt(field);
            }

            if (!isAjaxValidator) {
                field.trigger("jqv.field.result", [field, options.isError, promptText]);
            }

            /* Record error */
            var errindex = $.inArray(field[0], options.InvalidFields);
            if (errindex == -1) {
                if (options.isError)
                    options.InvalidFields.push(field[0]);
            } else if (!options.isError) {
                options.InvalidFields.splice(errindex, 1);
            }

            methods._handleStatusCssClasses(field, options);

            /* run callback function for each field */
            if (options.isError && options.onFieldFailure)
                options.onFieldFailure(field);

            if (!options.isError && options.onFieldSuccess)
                options.onFieldSuccess(field);

            return options.isError;
        },
        /**
         * Handling css classes of fields indicating result of validation
         *
         * @param {jqObject}
         *            field
         * @param {Array[String]}
         *            field's validation rules
         * @private
         */
        _handleStatusCssClasses: function(field, options) {
            /* remove all classes */
            if (options.addSuccessCssClassToField)
                field.removeClass(options.addSuccessCssClassToField);

            if (options.addFailureCssClassToField)
                field.removeClass(options.addFailureCssClassToField);

            /* Add classes */
            if (options.addSuccessCssClassToField && !options.isError)
                field.addClass(options.addSuccessCssClassToField);

            if (options.addFailureCssClassToField && options.isError)
                field.addClass(options.addFailureCssClassToField);
        },

        /********************
         * _getErrorMessage
         *
         * @param form
         * @param field
         * @param rule
         * @param rules
         * @param i
         * @param options
         * @param originalValidationMethod
         * @return {*}
         * @private
         */
        _getErrorMessage: function(form, field, rule, rules, i, options, originalValidationMethod) {
            // If we are using the custon validation type, build the index for the rule.
            // Otherwise if we are doing a function call, make the call and return the object
            // that is passed back.
            var rule_index = jQuery.inArray(rule, rules);
            if (rule === "custom" || rule === "funcCall") {
                var custom_validation_type = rules[rule_index + 1];
                rule = rule + "[" + custom_validation_type + "]";
                // Delete the rule from the rules array so that it doesn't try to call the
                // same rule over again
                delete(rules[rule_index]);
            }
            // Change the rule to the composite rule, if it was different from the original
            var alteredRule = rule;


            var element_classes = (field.attr("data-validation-engine")) ? field.attr("data-validation-engine") : field.attr("class");
            var element_classes_array = element_classes.split(" ");

            // Call the original validation method. If we are dealing with dates or checkboxes, also pass the form
            var errorMsg;
            if (rule == "future" || rule == "past" || rule == "maxCheckbox" || rule == "minCheckbox") {
                errorMsg = originalValidationMethod(form, field, rules, i, options);
            } else {
                errorMsg = originalValidationMethod(field, rules, i, options);
            }

            // If the original validation method returned an error and we have a custom error message,
            // return the custom message instead. Otherwise return the original error message.
            if (errorMsg != undefined) {
                var custom_message = methods._getCustomErrorMessage($(field), element_classes_array, alteredRule, options);
                if (custom_message) errorMsg = custom_message;
            }
            return errorMsg;

        },
        _getCustomErrorMessage: function(field, classes, rule, options) {
            var custom_message = false;
            var validityProp = /^custom\[.*\]$/.test(rule) ? methods._validityProp["custom"] : methods._validityProp[rule];
            // If there is a validityProp for this rule, check to see if the field has an attribute for it
            if (validityProp != undefined) {
                custom_message = field.attr("data-errormessage-" + validityProp);
                // If there was an error message for it, return the message
                if (custom_message != undefined)
                    return custom_message;
            }
            custom_message = field.attr("data-errormessage");
            // If there is an inline custom error message, return it
            if (custom_message != undefined)
                return custom_message;
            var id = '#' + field.attr("id");
            // If we have custom messages for the element's id, get the message for the rule from the id.
            // Otherwise, if we have custom messages for the element's classes, use the first class message we find instead.
            if (typeof options.custom_error_messages[id] != "undefined" &&
                typeof options.custom_error_messages[id][rule] != "undefined") {
                custom_message = options.custom_error_messages[id][rule]['message'];
            } else if (classes.length > 0) {
                for (var i = 0; i < classes.length && classes.length > 0; i++) {
                    var element_class = "." + classes[i];
                    if (typeof options.custom_error_messages[element_class] != "undefined" &&
                        typeof options.custom_error_messages[element_class][rule] != "undefined") {
                        custom_message = options.custom_error_messages[element_class][rule]['message'];
                        break;
                    }
                }
            }
            if (!custom_message &&
                typeof options.custom_error_messages[rule] != "undefined" &&
                typeof options.custom_error_messages[rule]['message'] != "undefined") {
                custom_message = options.custom_error_messages[rule]['message'];
            }
            return custom_message;
        },
        _validityProp: {
            "required": "value-missing",
            "custom": "custom-error",
            "groupRequired": "value-missing",
            "ajax": "custom-error",
            "minSize": "range-underflow",
            "maxSize": "range-overflow",
            "min": "range-underflow",
            "max": "range-overflow",
            "past": "type-mismatch",
            "future": "type-mismatch",
            "dateRange": "type-mismatch",
            "dateTimeRange": "type-mismatch",
            "maxCheckbox": "range-overflow",
            "minCheckbox": "range-underflow",
            "equals": "pattern-mismatch",
            "funcCall": "custom-error",
            "creditCard": "pattern-mismatch",
            "condRequired": "value-missing"
        },
        // add by mabaoyue 2013-7-12
        /**
         * ?????????input???????????????????????field?????
         * @author looping
         * @param  {[type]} form    [description]
         * @param  {[type]} field   [description]
         * @param  {[type]} rules   [description]
         * @param  {[type]} i       [description]
         * @param  {[type]} options [description]
         * @return {[type]}         [description]
         */
        _less: function(form, field, rules, i, options) {
            var p = rules[i + 1];
            var target = $(form.find("input[name='" + p.replace(/^#+/, '') + "']"));
            if (!target.length) {
                return;
            }
            var targetVal = parseFloat(target.val());
            var inputVal = parseFloat(field.val());

            if (isNaN(targetVal) || isNaN(inputVal)) {
                return;
            }

            if (inputVal > targetVal) {
                var rule = options.allrules.less;
                if (rule.alertText2) {
                    return rule.alertText + targetVal + rule.alertText2;
                }
                return rule.alertText + targetVal;
            }

        },
        /**
         * ?????????input???????????????????????field?????
         * @author looping
         * @param  {[type]} form    [description]
         * @param  {[type]} field   [description]
         * @param  {[type]} rules   [description]
         * @param  {[type]} i       [description]
         * @param  {[type]} options [description]
         * @return {[type]}         [description]
         */
        _greater: function(form, field, rules, i, options) {
            var p = rules[i + 1];
            var target = $(form.find("input[name='" + p.replace(/^#+/, '') + "']"));
            if (!target.length) {
                return;
            }
            var targetVal = parseFloat(target.val());
            var inputVal = parseFloat(field.val());

            if (isNaN(targetVal) || isNaN(inputVal)) {
                return;
            }

            if (inputVal < targetVal) {
                var rule = options.allrules.greater;
                if (rule.alertText2) {
                    return rule.alertText + targetVal + rule.alertText2;
                }
                return rule.alertText + targetVal;
            }

        },

        /**
         * Required validation
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @param {bool} condRequired flag when method is used for internal purpose in condRequired check
         * @return an error string if validation failed
         */
        _required: function(field, rules, i, options, condRequired) {
            switch (field.prop("type")) {
                case "text":
                case "password":
                case "textarea":
                case "file":
                case "select-one":
                case "select-multiple":
                default:
                    var field_val = $.trim(field.val());
                    var dv_placeholder = $.trim(field.attr("data-validation-placeholder"));
                    var placeholder = $.trim(field.attr("placeholder"));
                    if (
                        (!field_val) || (dv_placeholder && field_val == dv_placeholder) ) {
                        return options.allrules[rules[i]].alertText;
                    }
                    break;
                case "radio":
                case "checkbox":
                    // new validation style to only check dependent field
                    if (condRequired) {
                        if (!field.attr('checked')) {
                            return options.allrules[rules[i]].alertTextCheckboxMultiple;
                        }
                        break;
                    }
                    // old validation style
                    var form = field.closest("form, .validationEngineContainer");
                    var name = field.attr("name");
                    if (form.find("input[name='" + name + "']:checked").size() == 0) {
                        if (form.find("input[name='" + name + "']:visible").size() == 1)
                            return options.allrules[rules[i]].alertTextCheckboxe;
                        else
                            return options.allrules[rules[i]].alertTextCheckboxMultiple;
                    }
                    break;
            }
        },
        /**
         * Validate that 1 from the group field is required
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _groupRequired: function(field, rules, i, options) {
            var classGroup = "[" + options.validateAttribute + "*=" + rules[i + 1] + "]";
            var isValid = false;
            // Check all fields from the group
            field.closest("form, .validationEngineContainer").find(classGroup).each(function() {
                if (!methods._required($(this), rules, i, options)) {
                    isValid = true;
                    return false;
                }
            });

            if (!isValid) {
                return options.allrules[rules[i]].alertText;
            }
        },
        /**
         * Validate rules
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _custom: function(field, rules, i, options) {
            var customRule = rules[i + 1];
            var rule = options.allrules[customRule];
            var fn;
            if (!rule) {
                alert("jqv:custom rule not found - " + customRule);
                return;
            }

            if (rule["regex"]) {
                var ex = rule.regex;
                if (!ex) {
                    alert("jqv:custom regex not found - " + customRule);
                    return;
                }
                var pattern = new RegExp(ex);

                if (!pattern.test(field.val())) return options.allrules[customRule].alertText;

            } else if (rule["func"]) {
                fn = rule["func"];

                if (typeof(fn) !== "function") {
                    alert("jqv:custom parameter 'function' is no function - " + customRule);
                    return;
                }

                if (!fn(field, rules, i, options))
                    return options.allrules[customRule].alertText;
            } else {
                alert("jqv:custom type not allowed " + customRule);
                return;
            }
        },
        /**
         * Validate custom function outside of the engine scope
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _funcCall: function(field, rules, i, options) {
            var functionName = rules[i + 1];
            var fn;
            if (functionName.indexOf('.') > -1) {
                var namespaces = functionName.split('.');
                var scope = window;
                while (namespaces.length) {
                    scope = scope[namespaces.shift()];
                }
                fn = scope;
            } else
                fn = window[functionName] || options.customFunctions[functionName];
            if (typeof(fn) == 'function')
                return fn(field, rules, i, options);

        },
        /**
         * Field match
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _equals: function(field, rules, i, options) {
            var equalsField = rules[i + 1];

            if (field.val() != $("#" + equalsField).val())
                return options.allrules.equals.alertText + field.data("infor");
        },
        /**
         * Check the maximum size (in characters)
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _maxSize: function(field, rules, i, options) {
            var max = rules[i + 1];
            var len = field.val().length;

            if (len > max) {
                var rule = options.allrules.maxSize;
                return rule.alertText + max + rule.alertText2;
            }
        },
        /**
         * Check the minimum size (in characters)
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _minSize: function(field, rules, i, options) {
            var min = rules[i + 1];
            var len = field.val().length;

            if (len < min) {
                var rule = options.allrules.minSize;
                return rule.alertText + min + rule.alertText2;
            }
        },
        /**
         * Check number minimum value
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _min: function(field, rules, i, options) {
            var min = parseFloat(rules[i + 1]);
            var len = parseFloat(field.val());

            if (len < min) {
                var rule = options.allrules.min;
                if (rule.alertText2) return rule.alertText + min + rule.alertText2;
                return rule.alertText + min;
            }
        },
        /**
         * Check number maximum value
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _max: function(field, rules, i, options) {
            var max = parseFloat(rules[i + 1]);
            var len = parseFloat(field.val());

            if (len > max) {
                var rule = options.allrules.max;
                if (rule.alertText2) return rule.alertText + max + rule.alertText2;
                //orefalo: to review, also do the translations
                return rule.alertText + max;
            }
        },
        /**
         * Checks date is in the past
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _past: function(form, field, rules, i, options) {

            var p = rules[i + 1];
            var fieldAlt = $(form.find("input[name='" + p.replace(/^#+/, '') + "']"));
            var pdate;

            if (p.toLowerCase() == "now") {
                pdate = new Date();
            } else if (undefined != fieldAlt.val()) {
                if (fieldAlt.is(":disabled"))
                    return;
                pdate = methods._parseDate(fieldAlt.val());
            } else {
                pdate = methods._parseDate(p);
            }
            var vdate = methods._parseDate(field.val());

            if (vdate > pdate) {
                var rule = options.allrules.past;
                if (rule.alertText2) return rule.alertText + methods._dateToString(pdate) + rule.alertText2;
                return rule.alertText + methods._dateToString(pdate);
            }
        },
        /**
         * Checks date is in the future
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _future: function(form, field, rules, i, options) {

            var p = rules[i + 1];
            var fieldAlt = $(form.find("input[name='" + p.replace(/^#+/, '') + "']"));
            var pdate;

            if (p.toLowerCase() == "now") {
                pdate = new Date();
            } else if (undefined != fieldAlt.val()) {
                if (fieldAlt.is(":disabled"))
                    return;
                pdate = methods._parseDate(fieldAlt.val());
            } else {
                pdate = methods._parseDate(p);
            }
            var vdate = methods._parseDate(field.val());

            if (vdate < pdate) {
                var rule = options.allrules.future;
                if (rule.alertText2)
                    return rule.alertText + methods._dateToString(pdate) + rule.alertText2;
                return rule.alertText + methods._dateToString(pdate);
            }
        },
        /**
         * Checks if valid date
         *
         * @param {string} date string
         * @return a bool based on determination of valid date
         */
        _isDate: function(value) {
            var dateRegEx = new RegExp(/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(?:(?:0?[1-9]|1[0-2])(\/|-)(?:0?[1-9]|1\d|2[0-8]))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(0?2(\/|-)29)(\/|-)(?:(?:0[48]00|[13579][26]00|[2468][048]00)|(?:\d\d)?(?:0[48]|[2468][048]|[13579][26]))$/);
            return dateRegEx.test(value);
        },
        /**
         * Checks if valid date time
         *
         * @param {string} date string
         * @return a bool based on determination of valid date time
         */
        _isDateTime: function(value) {
            var dateTimeRegEx = new RegExp(/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1}$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^((1[012]|0?[1-9]){1}\/(0?[1-9]|[12][0-9]|3[01]){1}\/\d{2,4}\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1})$/);
            return dateTimeRegEx.test(value);
        },
        //Checks if the start date is before the end date
        //returns true if end is later than start
        _dateCompare: function(start, end) {
            return (new Date(start.toString()) < new Date(end.toString()));
        },
        /**
         * Checks date range
         *
         * @param {jqObject} first field name
         * @param {jqObject} second field name
         * @return an error string if validation failed
         */
        _dateRange: function(field, rules, i, options) {
            //are not both populated
            if ((!options.firstOfGroup[0].value && options.secondOfGroup[0].value) || (options.firstOfGroup[0].value && !options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }

            //are not both dates
            if (!methods._isDate(options.firstOfGroup[0].value) || !methods._isDate(options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }

            //are both dates but range is off
            if (!methods._dateCompare(options.firstOfGroup[0].value, options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }
        },
        /**
         * Checks date time range
         *
         * @param {jqObject} first field name
         * @param {jqObject} second field name
         * @return an error string if validation failed
         */
        _dateTimeRange: function(field, rules, i, options) {
            //are not both populated
            if ((!options.firstOfGroup[0].value && options.secondOfGroup[0].value) || (options.firstOfGroup[0].value && !options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }
            //are not both dates
            if (!methods._isDateTime(options.firstOfGroup[0].value) || !methods._isDateTime(options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }
            //are both dates but range is off
            if (!methods._dateCompare(options.firstOfGroup[0].value, options.secondOfGroup[0].value)) {
                return options.allrules[rules[i]].alertText + options.allrules[rules[i]].alertText2;
            }
        },
        /**
         * Max number of checkbox selected
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _maxCheckbox: function(form, field, rules, i, options) {

            var nbCheck = rules[i + 1];
            var groupname = field.attr("name");
            var groupSize = form.find("input[name='" + groupname + "']:checked").size();
            if (groupSize > nbCheck) {
                options.showArrow = false;
                if (options.allrules.maxCheckbox.alertText2)
                    return options.allrules.maxCheckbox.alertText + " " + nbCheck + " " + options.allrules.maxCheckbox.alertText2;
                return options.allrules.maxCheckbox.alertText;
            }
        },
        /**
         * Min number of checkbox selected
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _minCheckbox: function(form, field, rules, i, options) {

            var nbCheck = rules[i + 1];
            var groupname = field.attr("name");
            var groupSize = form.find("input[name='" + groupname + "']:checked").size();
            if (groupSize < nbCheck) {
                options.showArrow = false;
                var infor = field.data("infor");
                return !!infor ? infor : options.allrules.minCheckbox.alertText + " " + nbCheck + " " + options.allrules.minCheckbox.alertText2;
            }
        },
        /**
         * Checks that it is a valid credit card number according to the
         * Luhn checksum algorithm.
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return an error string if validation failed
         */
        _creditCard: function(field, rules, i, options) {
            //spaces and dashes may be valid characters, but must be stripped to calculate the checksum.
            var valid = false,
                cardNumber = field.val().replace(/ +/g, '').replace(/-+/g, '');

            var numDigits = cardNumber.length;
            if (numDigits >= 14 && numDigits <= 16 && parseInt(cardNumber) > 0) {

                var sum = 0,
                    i = numDigits - 1,
                    pos = 1,
                    digit, luhn = new String();
                do {
                    digit = parseInt(cardNumber.charAt(i));
                    luhn += (pos++ % 2 == 0) ? digit * 2 : digit;
                } while (--i >= 0)

                for (i = 0; i < luhn.length; i++) {
                    sum += parseInt(luhn.charAt(i));
                }
                valid = sum % 10 == 0;
            }
            if (!valid) return options.allrules.creditCard.alertText;
        },
        /**
         * Ajax field validation
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         *            user options
         * @return nothing! the ajax validator handles the prompts itself
         */
        _ajax: function(field, rules, i, options) {

            var errorSelector = rules[i + 1];
            var rule = options.allrules[errorSelector];
            var extraData = rule.extraData;
            var extraDataDynamic = rule.extraDataDynamic;
            //var name = field.attr("name") ;
            var data = {
                id: field.attr("id")
            };
            $.each(rule.extraData, function(i, v) {
                data[field.attr(i)] = field.val();
            });


            if (typeof extraData === "object") {
                $.extend(data, extraData);
            } else if (typeof extraData === "string") {
                var tempData = extraData.split("&");
                for (var i = 0; i < tempData.length; i++) {
                    var values = tempData[i].split("=");
                    if (values[0] && values[0]) {
                        data[values[0]] = values[1];
                    }
                }
            }

            if (extraDataDynamic) {
                var tmpData = [];
                var domIds = String(extraDataDynamic).split(",");
                for (var i = 0; i < domIds.length; i++) {
                    var id = domIds[i];
                    if ($(id).length) {
                        var inputValue = field.closest("form, .validationEngineContainer").find(id).val();
                        var keyValue = id.replace('#', '') + '=' + escape(inputValue);
                        data[id.replace('#', '')] = inputValue;
                    }
                }
            }

            // If a field change event triggered this we want to clear the cache for this ID
            if (options.eventTrigger == "field") {
                delete(options.ajaxValidCache[field.attr("id")]);
            }

            // If there is an error or if the the field is already validated, do not re-execute AJAX
            if (!options.isError && !methods._checkAjaxFieldStatus(field.attr("id"), options)) {
                $.ajax({
                    type: !! rule.type ? rule.type : "get",
                    url: rule.url,
                    cache: false,
                    dataType: "json",
                    data: data,
                    field: field,
                    rule: rule,
                    methods: methods,
                    options: options,
                    beforeSend: function() {},
                    error: function(data, transport) {
                        methods._ajaxError(data, transport);
                    },
                    success: function(data) {
                        var json = data;
                        // asynchronously called on success, data is the json answer from the server
                        var errorFieldId = json["id"];
                        //var errorField = $($("#" + errorFieldId)[0]);
                        var errorField = $("#" + errorFieldId).eq(0);

                        // make sure we found the element
                        if (errorField.length == 1) {
                            var status = data["status"];
                            // read the optional msg from the server
                            var msg = data["msg"];
                            if (!status) {
                                // Houston we got a problem - display an red prompt
                                options.ajaxValidCache[errorFieldId] = false;
                                options.isError = true;

                                // resolve the msg prompt
                                if (msg) {
                                    if (options.allrules[msg]) {
                                        var txt = options.allrules[msg].alertText;
                                        if (txt) {
                                            msg = txt;
                                        }
                                    }
                                } else
                                    msg = rule.alertText;

                                if (options.showPrompts) methods._showPrompt(errorField, msg, "", true, options);
                            } else {
                                options.ajaxValidCache[errorFieldId] = true;

                                // resolves the msg prompt
                                if (msg) {
                                    if (options.allrules[msg]) {
                                        var txt = options.allrules[msg].alertTextOk;
                                        if (txt) {
                                            msg = txt;
                                        }
                                    }
                                } else
                                    msg = rule.alertTextOk;

                                if (options.showPrompts) {
                                    // see if we should display a green prompt
                                    if (msg)
                                        methods._showPrompt(errorField, msg, "pass", true, options);
                                    else
                                        methods._closePrompt(errorField);
                                }

                                // If a submit form triggered this, we want to re-submit the form
                                if (options.eventTrigger == "submit")
                                    field.closest("form").submit();
                            }
                        }
                        errorField.trigger("jqv.field.result", [errorField, options.isError, msg]);
                    }
                });

                return rule.alertTextLoad;
            }
        },
        /**
         * Common method to handle ajax errors
         *
         * @param {Object} data
         * @param {Object} transport
         */
        _ajaxError: function(data, transport) {
            if (data.status == 0 && transport == null)
                alert("The page is not served from a server! ajax call failed");
            else if (typeof console != "undefined")
                console.log("Ajax error: " + data.status + " " + transport);
        },
        /**
         * date -> string
         *
         * @param {Object} date
         */
        _dateToString: function(date) {
            return date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
        },
        /**
         * Parses an ISO date
         * @param {String} d
         */
        _parseDate: function(d) {

            var dateParts = d.split("-");
            if (dateParts == d)
                dateParts = d.split("/");
            if (dateParts == d) {
                dateParts = d.split(".");
                return new Date(dateParts[2], (dateParts[1] - 1), dateParts[0]);
            }
            return new Date(dateParts[0], (dateParts[1] - 1), dateParts[2]);
        },
        /**
         * Builds or updates a prompt with the given information
         *
         * @param {jqObject} field
         * @param {String} promptText html text to display type
         * @param {String} type the type of bubble: 'pass' (green), 'load' (black) anything else (red)
         * @param {boolean} ajaxed - use to mark fields than being validated with ajax
         * @param {Map} options user options
         */
        _showPrompt: function(field, promptText, type, ajaxed, options, ajaxform) {
            var showClass = null;
            if ( !! options.useTooltip) {
                //Check if we need to adjust what element to show the prompt on
                if (field.data('jqv-prompt-at') instanceof jQuery) {
                    field = field.data('jqv-prompt-at');
                } else if (field.data('jqv-prompt-at')) {
                    field = $(field.data('jqv-prompt-at'));
                }

                var prompt = methods._getPrompt(field);
                // The ajax submit errors are not see has an error in the form,
                // When the form errors are returned, the engine see 2 bubbles, but those are ebing closed by the engine at the same time
                // Because no error was found befor submitting
                if (ajaxform) prompt = false;
                // Check that there is indded text
                if ($.trim(promptText)) {
                    if (prompt)
                        methods._updatePrompt(field, prompt, promptText, type, ajaxed, options);
                    else
                        methods._buildPrompt(field, promptText, type, ajaxed, options);
                }
            } else {

                var $con = $("." + methods._getClassName(field.attr("id")) + "formError");
                if (field.closest("form").find($con).length > 0) {
                    $con.html(promptText).css("display" , "inline-block").addClass("formvalidError").removeClass("successValid");
                } else {
                    var $span = $("<strong class='formvalidError'></strong>");
                    $span.addClass("parentForm" + methods._getClassName(field.closest('form, .validationEngineContainer').attr("id")));
                    $span.addClass(methods._getClassName(field.attr("id")) + "formError");
                    $span.html(promptText);
                    field.after($span);
                }



            }

        },
        /**
         * Builds and shades a prompt for the given field.
         *
         * @param {jqObject} field
         * @param {String} promptText html text to display type
         * @param {String} type the type of bubble: 'pass' (green), 'load' (black) anything else (red)
         * @param {boolean} ajaxed - use to mark fields than being validated with ajax
         * @param {Map} options user options
         */
        _buildPrompt: function(field, promptText, type, ajaxed, options) {

            // create the prompt
            var prompt = $('<div>');
            prompt.addClass(methods._getClassName(field.attr("id")) + "formError");
            // add a class name to identify the parent form of the prompt
            prompt.addClass("parentForm" + methods._getClassName(field.closest('form, .validationEngineContainer').attr("id")));
            prompt.addClass("formError");

            switch (type) {
                case "pass":
                    prompt.addClass("greenPopup");
                    break;
                case "load":
                    prompt.addClass("blackPopup");
                    break;
                default:
                    /* it has error  */
                    //alert("unknown popup type:"+type);
            }
            if (ajaxed)
                prompt.addClass("ajaxed");

            // create the prompt content
            var promptContent = $('<div>').addClass("formErrorContent").html(promptText).appendTo(prompt);

            // determine position type
            var positionType = field.data("promptPosition") || options.promptPosition;

            // create the css arrow pointing at the field
            // note that there is no triangle on max-checkbox and radio
            if (options.showArrow) {
                var arrow = $('<div>').addClass("formErrorArrow");

                //prompt positioning adjustment support. Usage: positionType:Xshift,Yshift (for ex.: bottomLeft:+20 or bottomLeft:-20,+10)
                if (typeof(positionType) == 'string') {
                    var pos = positionType.indexOf(":");
                    if (pos != -1)
                        positionType = positionType.substring(0, pos);
                }

                switch (positionType) {
                    case "bottomLeft":
                    case "bottomRight":
                        prompt.find(".formErrorContent").before(arrow);
                        arrow.addClass("formErrorArrowBottom").html('<div class="line1"><!-- --></div><div class="line2"><!-- --></div><div class="line3"><!-- --></div><div class="line4"><!-- --></div><div class="line5"><!-- --></div><div class="line6"><!-- --></div><div class="line7"><!-- --></div><div class="line8"><!-- --></div><div class="line9"><!-- --></div><div class="line10"><!-- --></div>');
                        break;
                    case "topLeft":
                    case "topRight":
                        arrow.html('<div class="line10"><!-- --></div><div class="line9"><!-- --></div><div class="line8"><!-- --></div><div class="line7"><!-- --></div><div class="line6"><!-- --></div><div class="line5"><!-- --></div><div class="line4"><!-- --></div><div class="line3"><!-- --></div><div class="line2"><!-- --></div><div class="line1"><!-- --></div>');
                        prompt.append(arrow);
                        break;
                }
            }
            // Add custom prompt class
            if (options.addPromptClass)
                prompt.addClass(options.addPromptClass);

            // Add custom prompt class defined in element
            var requiredOverride = field.attr('data-required-class');
            if (requiredOverride !== undefined) {
                prompt.addClass(requiredOverride);
            } else {
                if (options.prettySelect) {
                    if ($('#' + field.attr('id')).next().is('select')) {
                        var prettyOverrideClass = $('#' + field.attr('id').substr(options.usePrefix.length).substring(options.useSuffix.length)).attr('data-required-class');
                        if (prettyOverrideClass !== undefined) {
                            prompt.addClass(prettyOverrideClass);
                        }
                    }
                }
            }

            prompt.css({
                "opacity": 0
            });
            if (positionType === 'inline') {
                prompt.addClass("inline");
                if (typeof field.attr('data-prompt-target') !== 'undefined' && $('#' + field.attr('data-prompt-target')).length > 0) {
                    prompt.appendTo($('#' + field.attr('data-prompt-target')));
                } else {
                    field.after(prompt);
                }
            } else {
                field.before(prompt);
            }

            var pos = methods._calculatePosition(field, prompt, options);
            prompt.css({
                'position': positionType === 'inline' ? 'relative' : 'absolute',
                "top": pos.callerTopPosition,
                "left": pos.callerleftPosition,
                "marginTop": pos.marginTopSize,
                "opacity": 0
            }).data("callerField", field);


            if (options.autoHidePrompt) {
                setTimeout(function() {
                    prompt.animate({
                        "opacity": 0
                    }, function() {
                        prompt.closest('.formErrorOuter').remove();
                        prompt.remove();
                    });
                }, options.autoHideDelay);
            }
            return prompt.animate({
                "opacity": 0.87
            });
        },
        /**
         * Updates the prompt text field - the field for which the prompt
         * @param {jqObject} field
         * @param {String} promptText html text to display type
         * @param {String} type the type of bubble: 'pass' (green), 'load' (black) anything else (red)
         * @param {boolean} ajaxed - use to mark fields than being validated with ajax
         * @param {Map} options user options
         */
        _updatePrompt: function(field, prompt, promptText, type, ajaxed, options, noAnimation) {

            if (prompt) {
                if (typeof type !== "undefined") {
                    if (type == "pass")
                        prompt.addClass("greenPopup");
                    else
                        prompt.removeClass("greenPopup");

                    if (type == "load")
                        prompt.addClass("blackPopup");
                    else
                        prompt.removeClass("blackPopup");
                }
                if (ajaxed)
                    prompt.addClass("ajaxed");
                else
                    prompt.removeClass("ajaxed");

                prompt.find(".formErrorContent").html(promptText);

                var pos = methods._calculatePosition(field, prompt, options);
                var css = {
                    "top": pos.callerTopPosition,
                    "left": pos.callerleftPosition,
                    "marginTop": pos.marginTopSize
                };

                if (noAnimation)
                    prompt.css(css);
                else
                    prompt.animate(css);
            }
        },
        /**
         * Closes the prompt associated with the given field
         *
         * @param {jqObject}
         *            field
         */
        _closePrompt: function(field) {
            //var prompt = methods._getPrompt(field);
            var prompt = $("." + methods._getClassName(field.attr("id")) + "formError");
            if (prompt){
                 //prompt.addClass("formvalidError");
                 //prompt.css("display" , "none");
                 prompt.html("");
                 prompt.addClass("successValid").removeClass("formvalidError");
            }
                // prompt.fadeTo("fast", 0, function() {
                //     prompt.parent('.formErrorOuter').remove();
                //     prompt.hide();
                // });
               
        },
        closePrompt: function(field) {
            return methods._closePrompt(field);
        },
        /**
         * Returns the error prompt matching the field if any
         *
         * @param {jqObject}
         *            field
         * @return undefined or the error prompt (jqObject)
         */
        _getPrompt: function(field) {
            var formId = $(field).closest('form, .validationEngineContainer').attr('id');
            var className = methods._getClassName(field.attr("id")) + "formError";
            var match = $("." + methods._escapeExpression(className) + '.parentForm' + methods._getClassName(formId))[0];
            if (match)
                return $(match);
        },
        /**
         * Returns the escapade classname
         *
         * @param {selector}
         *            className
         */
        _escapeExpression: function(selector) {
            return selector.replace(/([#;&,\.\+\*\~':"\!\^$\[\]\(\)=>\|])/g, "\\$1");
        },
        /**
         * returns true if we are in a RTLed document
         *
         * @param {jqObject} field
         */
        isRTL: function(field) {
            var $document = $(document);
            var $body = $('body');
            var rtl =
                (field && field.hasClass('rtl')) ||
                (field && (field.attr('dir') || '').toLowerCase() === 'rtl') ||
                $document.hasClass('rtl') ||
                ($document.attr('dir') || '').toLowerCase() === 'rtl' ||
                $body.hasClass('rtl') ||
                ($body.attr('dir') || '').toLowerCase() === 'rtl';
            return Boolean(rtl);
        },
        /**
         * Calculates prompt position
         *
         * @param {jqObject}
         *            field
         * @param {jqObject}
         *            the prompt
         * @param {Map}
         *            options
         * @return positions
         */
        _calculatePosition: function(field, promptElmt, options) {

            var promptTopPosition, promptleftPosition, marginTopSize;
            var fieldWidth = field.width();
            var fieldLeft = field.position().left;
            var fieldTop = field.position().top;
            var fieldHeight = field.height();
            var promptHeight = promptElmt.height();


            // is the form contained in an overflown container?
            promptTopPosition = promptleftPosition = 0;
            // compensation for the arrow
            marginTopSize = -promptHeight;


            //prompt positioning adjustment support
            //now you can adjust prompt position
            //usage: positionType:Xshift,Yshift
            //for example:
            //   bottomLeft:+20 means bottomLeft position shifted by 20 pixels right horizontally
            //   topRight:20, -15 means topRight position shifted by 20 pixels to right and 15 pixels to top
            //You can use +pixels, - pixels. If no sign is provided than + is default.
            var positionType = field.data("promptPosition") || options.promptPosition;
            var shift1 = "";
            var shift2 = "";
            var shiftX = 0;
            var shiftY = 0;
            if (typeof(positionType) == 'string') {
                //do we have any position adjustments ?
                if (positionType.indexOf(":") != -1) {
                    shift1 = positionType.substring(positionType.indexOf(":") + 1);
                    positionType = positionType.substring(0, positionType.indexOf(":"));

                    //if any advanced positioning will be needed (percents or something else) - parser should be added here
                    //for now we use simple parseInt()

                    //do we have second parameter?
                    if (shift1.indexOf(",") != -1) {
                        shift2 = shift1.substring(shift1.indexOf(",") + 1);
                        shift1 = shift1.substring(0, shift1.indexOf(","));
                        shiftY = parseInt(shift2);
                        if (isNaN(shiftY)) shiftY = 0;
                    };

                    shiftX = parseInt(shift1);
                    if (isNaN(shift1)) shift1 = 0;

                };
            };


            switch (positionType) {
                default:
                case "topRight":
                    promptleftPosition += fieldLeft + fieldWidth - 30;
                    promptTopPosition += fieldTop;
                    break;

                case "topLeft":
                    promptTopPosition += fieldTop;
                    promptleftPosition += fieldLeft;
                    break;

                case "centerRight":
                    promptTopPosition = fieldTop + 4;
                    marginTopSize = 0;
                    promptleftPosition = fieldLeft + field.outerWidth(true) + 5;
                    break;
                case "centerLeft":
                    promptleftPosition = fieldLeft - (promptElmt.width() + 2);
                    promptTopPosition = fieldTop + 4;
                    marginTopSize = 0;

                    break;

                case "bottomLeft":
                    promptTopPosition = fieldTop + field.height() + 5;
                    marginTopSize = 0;
                    promptleftPosition = fieldLeft;
                    break;
                case "bottomRight":
                    promptleftPosition = fieldLeft + fieldWidth - 30;
                    promptTopPosition = fieldTop + field.height() + 5;
                    marginTopSize = 0;
                    break;
                case "inline":
                    promptleftPosition = 0;
                    promptTopPosition = 0;
                    marginTopSize = 0;
            };



            //apply adjusments if any
            promptleftPosition += shiftX;
            promptTopPosition += shiftY;

            return {
                "callerTopPosition": promptTopPosition + "px",
                "callerleftPosition": promptleftPosition + "px",
                "marginTopSize": marginTopSize + "px"
            };
        },
        /**
         * Saves the user options and variables in the form.data
         *
         * @param {jqObject}
         *            form - the form where the user option should be saved
         * @param {Map}
         *            options - the user options
         * @return the user options (extended from the defaults)
         */
        _saveOptions: function(form, options) {

            // is there a language localisation ?
            if ($.validationEngineLanguage)
                var allRules = $.validationEngineLanguage.allRules;
            else
                $.error("jQuery.validationEngine rules are not loaded, plz add localization files to the page");
            // --- Internals DO NOT TOUCH or OVERLOAD ---
            // validation rules and i18
            $.validationEngine.defaults.allrules = allRules;

            var userOptions = $.extend(true, {}, $.validationEngine.defaults, options);

            form.data('jqv', userOptions);
            return userOptions;
        },

        /**
         * Removes forbidden characters from class name
         * @param {String} className
         */
        _getClassName: function(className) {
            if (className)
                return className.replace(/:/g, "_").replace(/\./g, "_");
        },
        /**
         * Escape special character for jQuery selector
         * http://totaldev.com/content/escaping-characters-get-valid-jquery-id
         * @param {String} selector
         */
        _jqSelector: function(str) {
            return str.replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, '\\$1');
        },
        /**
         * Conditionally required field
         *
         * @param {jqObject} field
         * @param {Array[String]} rules
         * @param {int} i rules index
         * @param {Map}
         * user options
         * @return an error string if validation failed
         */
        _condRequired: function(field, rules, i, options) {
            var idx, dependingField;

            for (idx = (i + 1); idx < rules.length; idx++) {
                dependingField = jQuery("#" + rules[idx]).first();

                /* Use _required for determining wether dependingField has a value.
                 * There is logic there for handling all field types, and default value; so we won't replicate that here
                 * Indicate this special use by setting the last parameter to true so we only validate the dependingField on chackboxes and radio buttons (#462)
                 */
                if (dependingField.length && methods._required(dependingField, ["required"], 0, options, true) == undefined) {
                    /* We now know any of the depending fields has a value,
                     * so we can validate this field as per normal required code
                     */
                    return methods._required(field, ["required"], 0, options);
                }
            }
        },

        _submitButtonClick: function(event) {
            var button = $(this);
            var form = button.closest('form, .validationEngineContainer');
            form.data("jqv_submitButton", button.attr("id"));
        }
    };

    /**
     * Plugin entry point.
     * You may pass an action as a parameter or a list of options.
     * if none, the init and attach methods are being called.
     * Remember: if you pass options, the attached method is NOT called automatically
     *
     * @param {String}
     *            method (optional) action
     */
    $.fn.valid = $.fn.validationEngine = function(method) {

        var form = $(this);
        if (!form[0]) return form; // stop here if the form does not exist

        if (typeof(method) == 'string' && method.charAt(0) != '_' && methods[method]) {

            // make sure init is called once
            if (method != "showPrompt" && method != "hide" && method != "hideAll")
                methods.init.apply(form);

            return methods[method].apply(form, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method == 'object' || !method) {

            // default constructor with or without arguments
            methods.init.apply(form, arguments);
            return methods.attach.apply(form);
        } else {
            $.error('Method ' + method + ' does not exist in jQuery.validationEngine');
        }
    };



    // LEAK GLOBAL OPTIONS
    $.validationEngine = {
        fieldIdCounter: 0,
        defaults: {

            // Name of the event triggering field validation
            validationEventTrigger: "blur",
            // Automatically scroll viewport to the first error
            scroll: true,
            // Focus on the first input
            focusFirstField: true,
            // Show prompts, set to false to disable prompts
            showPrompts: true,
            // Should we attempt to validate non-visible input fields contained in the form? (Useful in cases of tabbed containers, e.g. jQuery-UI tabs)
            validateNonVisibleFields: false,
            // Opening box position, possible locations are: topLeft,
            // topRight, bottomLeft, centerRight, bottomRight, inline
            // inline gets inserted after the validated field or into an element specified in data-prompt-target
            promptPosition: "topRight",
            bindMethod: "bind",
            // internal, automatically set to true when it parse a _ajax rule
            inlineAjax: false,
            // if set to true, the form data is sent asynchronously via ajax to the form.action url (get)
            ajaxFormValidation: false,
            // The url to send the submit ajax validation (default to action)
            ajaxFormValidationURL: false,
            // HTTP method used for ajax validation
            ajaxFormValidationMethod: 'post',
            // Ajax form validation callback method: boolean onComplete(form, status, errors, options)
            // retuns false if the form.submit event needs to be canceled.
            onAjaxFormComplete: $.noop,
            // called right before the ajax call, may return false to cancel
            onBeforeAjaxFormValidation: $.noop,
            // Stops form from submitting and execute function assiciated with it
            onValidationComplete: false,

            // Used when you have a form fields too close and the errors messages are on top of other disturbing viewing messages
            doNotShowAllErrosOnSubmit: false,
            // Object where you store custom messages to override the default error messages
            custom_error_messages: {},
            // true if you want to vind the input fields
            binded: true,
            // set to true, when the prompt arrow needs to be displayed
            showArrow: true,
            // did one of the validation fail ? kept global to stop further ajax validations
            isError: false,
            // Limit how many displayed errors a field can have
            maxErrorsPerField: false,

            // Caches field validation status, typically only bad status are created.
            // the array is used during ajax form validation to detect issues early and prevent an expensive submit
            ajaxValidCache: {},
            // Auto update prompt position after window resize
            autoPositionUpdate: false,

            InvalidFields: [],
            onFieldSuccess: false,
            onFieldFailure: false,
            onSuccess: false,
            onFailure: false,
            validateAttribute: "class",
            addSuccessCssClassToField: "",
            addFailureCssClassToField: "",

            // Auto-hide prompt
            autoHidePrompt: false,
            // Delay before auto-hide
            autoHideDelay: 3000,
            // Fade out duration while hiding the validations
            fadeDuration: 0.3,
            // Use Prettify select library
            prettySelect: false,
            // Add css class on prompt
            addPromptClass: "",
            // Custom ID uses prefix
            usePrefix: "",
            // Custom ID uses suffix
            useSuffix: "",
            // Only show one message per error prompt
            showOneMessage: false,
            // is show tooltip?
            useTooltip: false
        }
    };
    // $(function() {
    //     $.validationEngine.defaults.promptPosition = methods.isRTL() ? 'topLeft' : "topRight"
    // });
})(jQuery);

(function($) {
    $.fn.validationEngineLanguage = function() {};
    $.validationEngineLanguage = {
        newLang: function() {
            $.validationEngineLanguage.allRules = {
                "required": { // Add your regex rules here, you can take telephone as an example
                    "regex": "none",
                    "alertText": " ??????????????????",
                    "alertTextCheckboxMultiple": "?????????????????????",
                    "alertTextCheckboxe": " ?????????????????????",
                    "alertTextDateRange": " ????????????????????????"
                },
                "requiredInFunction": {
                    "func": function(field, rules, i, options) {
                        return (field.val() == "test") ? true : false;
                    },
                    "alertText": "* Field must equal test"
                },
                "dateRange": {
                    "regex": "none",
                    "alertText": "* ????????? ",
                    "alertText2": " ????????????"
                },
                "dateTimeRange": {
                    "regex": "none",
                    "alertText": "* ????????? ",
                    "alertText2": " ????????????"
                },
                "minSize": {
                    "regex": "none",
                    "alertText": " ??????????????? ",
                    "alertText2": " ???"
                },
                "maxSize": {
                    "regex": "none",
                    "alertText": " ??????????????? ",
                    "alertText2": " ???"
                },
                "groupRequired": {
                    "regex": "none",
                    "alertText": " ?????????????????????????????????"
                },
                "min": {
                    "regex": "none",
                    "alertText": " ???????????? "
                },
                "max": {
                    "regex": "none",
                    "alertText": " ???????????? "
                },
                "past": {
                    "regex": "none",
                    "alertText": " ?????????????????? "
                },
                "future": {
                    "regex": "none",
                    "alertText": " ?????????????????? "
                },
                "maxCheckbox": {
                    "regex": "none",
                    "alertText": " ???????????? ",
                    "alertText2": " ?????????"
                },
                "minCheckbox": {
                    "regex": "none",
                    "alertText": " ????????? ",
                    "alertText2": " ?????????"
                },
                "equals": {
                    "regex": "none",
                    "alertText": " ???????????????????????????"
                },
                "creditCard": {
                    "regex": "none",
                    "alertText": "* ????????????????????????"
                },
                "phone": {
                    // credit: jquery.h5validate.js / orefalo
                    "regex": /^([\+][0-9]{1,3}[ \.\-])?([\(]{1}[0-9]{2,6}[\)])?([0-9 \.\-\/]{3,20})((x|ext|extension)[ ]?[0-9]{1,4})?$/,
                    "alertText": "* ?????????????????????"
                },
                "email": {
                    // Shamelessly lifted from Scott Gonzalez via the Bassistance Validation plugin http://projects.scottsplayground.com/email_address_validation/
                    "regex": /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i,
                    "alertText": " ??????????????????"
                },
                "integer": {
                    "regex": /^[\-\+]?\d+$/,
                    "alertText": " ?????????????????????"
                },
                "number": {
                    // Number, including positive, negative, and floating decimal. credit: orefalo
                    "regex": /^[\-\+]?((([0-9]{1,3})([,][0-9]{3})*)|([0-9]+))?([\.]([0-9]+))?$/,
                    "alertText": " ???????????????"
                },
                "date": {
                    "regex": /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/,
                    "alertText": "* ????????????????????????????????? YYYY-MM-DD",
                    "alertTextOk": "????????????"
                },
                "ipv4": {
                    "regex": /^((([01]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))[.]){3}(([0-1]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))$/,
                    "alertText": "* ????????? IP ??????"
                },
                "url": {
                    "regex": /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i,
                    "alertText": "* ???????????? URL??????"
                },
                "onlyNumberSp": {
                    "regex": /^[0-9\ ]+$/,
                    "alertText": "???????????????????????????"
                },
                "onlyLetterSp": {
                    "regex": /^[a-zA-Z\ \']+$/,
                    "alertText": " ??????????????????????????????"
                },
                "onlyLetterNumber": {
                    "regex": /^[0-9a-zA-Z]+$/,
                    "alertText": "* ????????????????????????"
                },
                // --- CUSTOM RULES -- Those are specific to the demos, they can be removed or changed to your likings
                "ajaxUserCall": {
                    "url": "ajaxValidateFieldUser.json",
                    "type": "post",
                    // you may want to pass extra data on the ajax call
                    "extraData": "",
                    "alertTextOk": "???????????????????????????",
                    "alertText": "* ??????????????????????????????",
                    "alertTextLoad": "* ?????????????????????????????????????????????????????????"
                },
                "ajaxUserCallPhp": {
                    "url": "",
                    // you may want to pass extra data on the ajax call
                    "extraData": {
                        name: ""
                    },
                    "type": "post",
                    // if you provide an "alertTextOk", it will show as a green prompt when the field validates
                    "alertTextOk": "* ???????????????????????????",
                    "alertText": "* ??????????????????????????????",
                    "alertTextLoad": "* ???????????????????????????????????????????????????????????????"
                },
                "ajaxNameCall": {
                    // remote json service location
                    "url": "/Img/checkCodes",
					"type": "post",
					"extraData" : {
					    image_code : $('#image_code').val(),
						id : "image_code"
					},
                    // error
                    "alertText": "????????????????????????????????????",
                    // if you provide an "alertTextOk", it will show as a green prompt when the field validates
                    "alertTextOk": "",
                    // speaks by itself
                    "alertTextLoad": "???????????????..."
                },
                "ajaxCardNo": {
                    // remote json service location
                    "url": "/Img/checkIds",
					"type": "post",
					"extraData" : {
					    cardNo : $('#cardNo').val(),
						id : "cardNo"
					},
                    // error
                    "alertText": "????????????????????????????????????",
                    // if you provide an "alertTextOk", it will show as a green prompt when the field validates
                    "alertTextOk": "",
                    // speaks by itself
                    "alertTextLoad": "???????????????..."
                },				
				
                "ajaxNameCallPhp": {
                    // remote json service location
                    "url": "phpajax/ajaxValidateFieldName.php",
                    // error
                    "alertText": "* ??????????????????????????????",
                    // speaks by itself
                    "alertTextLoad": "* ?????????????????????????????????????????????????????????"
                },
                "validate2fields": {
                    "alertText": "* ????????? HELLO"
                },
                //tls warning:homegrown not fielded 
                "dateFormat": {
                    "regex": /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(?:(?:0?[1-9]|1[0-2])(\/|-)(?:0?[1-9]|1\d|2[0-8]))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^(0?2(\/|-)29)(\/|-)(?:(?:0[48]00|[13579][26]00|[2468][048]00)|(?:\d\d)?(?:0[48]|[2468][048]|[13579][26]))$/,
                    "alertText": "* ?????????????????????"
                },
                //tls warning:homegrown not fielded 
                "dateTimeFormat": {
                    "regex": /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1}$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^((1[012]|0?[1-9]){1}\/(0?[1-9]|[12][0-9]|3[01]){1}\/\d{2,4}\s+(1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}\s+(am|pm|AM|PM){1})$/,
                    "alertText": "* ??????????????????????????????",
                    "alertText2": "????????????????????? ",
                    "alertText3": "mm/dd/yyyy hh:mm:ss AM|PM ??? ",
                    "alertText4": "yyyy-mm-dd hh:mm:ss AM|PM"
                },
                //add by mabaoyue 2013-7-13
                "less": {
                    "regex": "none",
                    "alertText": " ???????????? "
                },
                "greater": {
                    "regex": "none",
                    "alertText": " ???????????? "
                },
                "mobile": {
                    //?????????
                    "regex": /^(13|14|15|18){1}[0-9]{9}$/,
                    "alertText": " ??????????????????????????????"
                },
                "password": {
                    //8???16????????????????????????????????????
                    "regex": /^(?=.*[a-zA-Z]).{8,16}$/,
                    "alertText": " ??????????????????????????????"
                }
            };

        }
    };
    $.validationEngineLanguage.newLang();
})(jQuery);

X.V = {
    checkLength: function(o) {
        var len = o.attr('maxlength') || o.data('len'),
            t = o.data('lentext') || '????????????' + len + '?????????';
        if (o.val().length > parseInt(len)) {
            return t
        }

    },
    checkDigitl: function(o, arr) {
        var intLen = arr[2],
            floatLen = arr[3];
        var max = [];
        for (var i = 0; i < intLen; i++) {
            max.push(9);
        }
        max = parseInt(max.join('')) + 1;
        var text = '?????????' + max + '???????????????';
        var reg = "(([1-9]{1}\\d{0," + (intLen - 1) + "})|([0]{1}))";
        if (floatLen > 0) {
            reg = reg + "(\\.(\\d){1," + floatLen + "})";
            text = '?????????' + max + '????????????????????????' + floatLen + '???';
        }
        reg = "^" + reg + "?$";
        var r = new RegExp(reg);
        var text = arr[5] || text;
        if (!r.test(o.val())) {
            return text;
        }
    },
    checkEmail: function(field) {
        var fieldValue = field.val(),
            rcardno = /^([a-z\d]+)([\._a-z\d-]+)?@([a-z\d-])+(\.[a-z\d-]+)*\.([a-z]{2,4})$/i;
        if (!rcardno.test(fieldValue)) {
            return '????????????????????????';
        }
    },
    checkPhone: function(field) {
        var fieldValue = field.val(),
            rcardno = /^[\-\+]?(([0-9]+)([\.,]([0-9]+))?|([\.,]([0-9]+))?)$/;
        if (!rcardno.test(fieldValue)) {
            return '??????????????????????????????';
        }
    },
    checkUrl: function(field) {
        var fieldValue = field.val(),
            rcardno = /^(https?:\/\/)([\w-]+\.)+[a-z]{2,4}\b/i;
        if (!rcardno.test(fieldValue)) {
            return '????????????????????????';
        }
    },
    checkAddress: function(field) {
        var fieldValue = field.val(),
            rcardno = /^[A-Za-z0-9_\\u4e00-\\u9fa5]+$/;
        if (!rcardno.test(fieldValue)) {
            return '??????????????????????????????????????????????????????????????????';
        }
    },
    checkChinese: function(field, rules, i, options) {
        var fieldValue = field.val(),
            regexp = /^[\u2E80-\uFE4F]+$/;
        if (!regexp.test(fieldValue)) {
            return '?????????????????????';
        }
    },
    subTitle: function(field, rules, i, options) {
        var fieldValue = field.val(),
            regexp = /^[\u2E80-\uFE4F]+$/,
            len = 0,
            maxLen = field.data("length") || 12;
        $.each(field.val().split(""), function(i, v) {
            if (regexp.test(v)) {
                len += 2;
            } else {
                len += 1;
            }
        });
        if (len > maxLen) {
            return '??????????????????12????????????24?????????';
        }
    },
    checkString: function(field, rules, i, options) {
        var fieldValue = field.val(),
            regexp = /^[\u2E80-\uFE4Fa-zA-Z-_0-9\s]+$/;
        if (!regexp.test(fieldValue)) {
            return '??????????????????????????????,???????????????????????????????????????????????????';
        }
        if (fieldValue.indexOf("__") > -1) {
            return '????????????????????????';
        }


    },
    checkEnglish: function(field, rules, i, options) {
        var fieldValue = field.val(),
            regexp = /^[a-z]+$/;
        if (!regexp.test(fieldValue)) {
            return '???????????????????????????';
        }
    },
    checkSearchOne: function(field, rules, i, options) {
        var fieldValue1 = field.val(),
            fieldValue2 = $(field.attr("validone")).val(),
            regexp = /^\s*$/;
        if (regexp.test(fieldValue1) && regexp.test(fieldValue2)) {
            return '??????????????????????????????';
        }
    },
    checkRadio: function(a, b, c, d) {
        var l = $('#' + b[2] + ' input[type=radio][checked]').length;
        if (l == 0) {
            return '????????????????????????';
        }
    },
    checkName : function(field, rules, i, options){
        var fieldValue1 = field.val(),
            len1 = rules[2],
            len2 = !rules[3] ? "" : rules[3],
            regexp = !!field.data("only") ? new RegExp("^[A-Za-z\\u4e00-\\u9fa5]{" + len1 +"}$") : new RegExp("^[A-Za-z\\u4e00-\\u9fa5]{" + len1 + ","+ len2 +"}$");
        if (!regexp.test(fieldValue1)) {
            return field.attr("placeholder");
        }
    },
    checkNum : function(field, rules, i, options){
        var fieldValue1 = field.val(),
            len1 = rules[2],
            len2 = !rules[3] ? "" : rules[3],
            regexp = !!field.data("only") ? new RegExp("^\\d{" + len1 + "}$") : new RegExp("^\\d{"+ len1 +"," + len2 + "}$");
        if (!regexp.test(fieldValue1)) {
            return field.attr("placeholder");
        }
    },
    checkDS : function(field, rules, i, options){
        var fieldValue1 = field.val(),
            len1 = rules[2] ,
            len2 = !rules[3] ? "" : rules[3],
            regexp = !!field.data("only") ? new RegExp("^[\\da-zA-Z]{" + len1 + "}$") : new RegExp("^[\\da-zA-Z]{"+ len1 +"," + len2 + "}$");
        if (!regexp.test(fieldValue1)) {
            return field.attr("placeholder");
        }
    },
    checkIdentity : function(field, rules, i, options){
        var fieldValue1 = field.val(),
            regexp = /^[1-9]\d{16}[x\d]$/i;
        if (!regexp.test(fieldValue1)) {
            return field.attr("placeholder") || "??????????????????????????????";
        }
    },
    checkCrash : function(field, rules, i, options){
         var fieldValue1 = field.val(),
             int = field.data("int"),
             xiaoshu = field.data("xiaoshu"),
             len1 = (int - 1)<=0 ? 0 :  (int - 1),
             len2 = xiaoshu,
            regexp = new RegExp("^(?:[1-9]\\d{0," + (len1) + "}|0|[1-9]\\d{0," + (len1) + "}\\.\\d{0,"+ (len2) +"}|0\\.\\d{0,"+ (len2) +"})$");
         if (!regexp.test(fieldValue1) || fieldValue1 == '0' || fieldValue1 == '0.0' || fieldValue1 == '0.00') {
             return "??????????????????????????????";
         }
    },
    //????????????????????????
    checkBook : function(field, rules, i, options){
          var value = field.val(),
          unit = field.data("unit"),
          max = field.data("max"),
          min = field.data("min");
          if(value < min){
               return "??????????????????????????????" + min + "???";
          }else if(value > max){
                return "??????????????????????????????" + max + "???";
          }else if(value%unit){
                return "?????????"+ unit +"???????????????";
          }
    },
    //????????????????????????
    checkPhoneEmail : function(field, rules, i, options){
         var fieldValue = field.val(),
             rcardno = /^(([a-z\d]+)([\._a-z\d-]+)?@([a-z\d-])+(\.[a-z\d-]+)*\.([a-z]{2,4}))|(13|14|15|18){1}[0-9]{9}$/i;
         if (!rcardno.test(fieldValue)) {
             return field.data("msg") || "??????????????????????????????";
         }
    } ,
    //???????????????
    checkBankNum : function(field, rules, i, options){
         var fieldValue = field.val(),
             len1 = 15,
             len2 = 25,
             rcardno = new RegExp("^\\d{"+ len1 +"," + len2 + "}$");
         if (/[???????????????????????????]/ig.test(fieldValue)) {
             return "???????????????????????????";
         }else if(!rcardno.test(fieldValue)){
             return field.attr("placeholder");
         }
    }
};;/*
author : mabaoyue

tab click or hover;
 cur????????????????????????
 noneClass : ?????????class
 tagPos : ????????????????????????
 hideFirst : ???????????????????????????????????????????????????
 tab ??? ?????????????????????
 tabCon ??? ?????????????????????

*/
(function($) {
    $.fn.select = function(options) {
        var settings = {
            evt: "click",
            cur: "",
            noneClass: "none",
            tagPos: 0,
            hideFirst: false,
            hoverClass: 'hover',
            listLabel: "li",
            docClick: '',
            tab: ".j_select",
            tabCon: ".j_selectContent",
            onSelectChange: function() {},
            onItemClick: function() {},
            onSelectDropDown: function() {}
        };
        $.extend(settings, options);
        return this.each(function() {
            var $self = $(this),
                cur = settings.cur,
                tab = settings.tab,
                tabCon = settings.tabCon,
                $tab = $self.find(tab),
                evt = settings.evt,
                tagPos = settings.tagPos,
                noneClass = settings.noneClass,
                $tabCon = $self.find(tabCon),
                $allCon = $("body").find(tabCon).not($tabCon),
                $tabConList = $tabCon.find(settings.listLabel),
                hoverClass = settings.hoverClass,
                $input = $('<input type="hidden" name="' + $self.data("name") + '"  id="' + $self.data("name") + '"/>'),
                tabIsInput = false,
                data_select = !1;
            //$self.data("clickEvent" , 0); 

            if (!$tab.length || !$tab) return;
            if ((/input/i).test($tab[0].tagName)) {
                tabIsInput = true;
            }
            $self.prepend($input);
            $tabCon.addClass(noneClass);
            $tab.removeClass(cur);

            $tab.on(evt, function(e) {
                e.stopPropagation();
                $self.data("clickEvent", 1);
                $allCon.addClass(noneClass);
                $tabCon.toggleClass(noneClass); !! settings.onSelectDropDown && settings.onSelectDropDown();
                return false;
            });
            if ( !! tabIsInput) {
                str = $tab.val();
                $tab.val($tabConList.eq(0).text());
            } else {
                str = $tab.html();

                $tab.html($tabConList.eq(0).text());
            }
            $input.val($tabConList.eq(0).data("value"));
            $tabConList.each(function(){
                 if($(this).data("select")){
                     $tab.html($(this).text());
                     $input.val($(this).data("value"));
                 }
            });

            $tabConList.on(evt, function(e) {

                e.stopPropagation();
                var $t = $(this),
                    str = "",
                    index = $tabConList.index($t);
                if ( !! tabIsInput) {
                    str = $tab.val();
                    $tab.val($t.text());
                } else {
                    str = $tab.html();
                    $tab.html($t.text());
                }
                $input.val($t.data("value"));
                $tabCon.toggleClass(noneClass);
                if ($t.text() == str) {
                    return;
                }
                settings.onSelectChange($self, $input, index); !! settings.onItemClick && settings.onItemClick($self, $input, index);
                return false;
            }).hover(function() {
                $(this).addClass(hoverClass).siblings().removeClass(hoverClass);
            }, function() {
                $(this).removeClass(hoverClass);
            });

            $(document).on(evt, function(e) {
                $tabCon.addClass(noneClass);
            });
        });
    }
})(jQuery);;/*!
 * jQuery UI Core 1.10.3
 * http://jqueryui.com
 *
 * Copyright 2013 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/category/ui-core/
 */
(function( $, undefined ) {

var uuid = 0,
	runiqueId = /^ui-id-\d+$/;

// $.ui might exist from components with no dependencies, e.g., $.ui.position
$.ui = $.ui || {};

$.extend( $.ui, {
	version: "1.10.3",

	keyCode: {
		BACKSPACE: 8,
		COMMA: 188,
		DELETE: 46,
		DOWN: 40,
		END: 35,
		ENTER: 13,
		ESCAPE: 27,
		HOME: 36,
		LEFT: 37,
		NUMPAD_ADD: 107,
		NUMPAD_DECIMAL: 110,
		NUMPAD_DIVIDE: 111,
		NUMPAD_ENTER: 108,
		NUMPAD_MULTIPLY: 106,
		NUMPAD_SUBTRACT: 109,
		PAGE_DOWN: 34,
		PAGE_UP: 33,
		PERIOD: 190,
		RIGHT: 39,
		SPACE: 32,
		TAB: 9,
		UP: 38
	}
});

// plugins
$.fn.extend({
	focus: (function( orig ) {
		return function( delay, fn ) {
			return typeof delay === "number" ?
				this.each(function() {
					var elem = this;
					setTimeout(function() {
						$( elem ).focus();
						if ( fn ) {
							fn.call( elem );
						}
					}, delay );
				}) :
				orig.apply( this, arguments );
		};
	})( $.fn.focus ),

	scrollParent: function() {
		var scrollParent;
		if (($.ui.ie && (/(static|relative)/).test(this.css("position"))) || (/absolute/).test(this.css("position"))) {
			scrollParent = this.parents().filter(function() {
				return (/(relative|absolute|fixed)/).test($.css(this,"position")) && (/(auto|scroll)/).test($.css(this,"overflow")+$.css(this,"overflow-y")+$.css(this,"overflow-x"));
			}).eq(0);
		} else {
			scrollParent = this.parents().filter(function() {
				return (/(auto|scroll)/).test($.css(this,"overflow")+$.css(this,"overflow-y")+$.css(this,"overflow-x"));
			}).eq(0);
		}

		return (/fixed/).test(this.css("position")) || !scrollParent.length ? $(document) : scrollParent;
	},

	zIndex: function( zIndex ) {
		if ( zIndex !== undefined ) {
			return this.css( "zIndex", zIndex );
		}

		if ( this.length ) {
			var elem = $( this[ 0 ] ), position, value;
			while ( elem.length && elem[ 0 ] !== document ) {
				// Ignore z-index if position is set to a value where z-index is ignored by the browser
				// This makes behavior of this function consistent across browsers
				// WebKit always returns auto if the element is positioned
				position = elem.css( "position" );
				if ( position === "absolute" || position === "relative" || position === "fixed" ) {
					// IE returns 0 when zIndex is not specified
					// other browsers return a string
					// we ignore the case of nested elements with an explicit value of 0
					// <div style="z-index: -10;"><div style="z-index: 0;"></div></div>
					value = parseInt( elem.css( "zIndex" ), 10 );
					if ( !isNaN( value ) && value !== 0 ) {
						return value;
					}
				}
				elem = elem.parent();
			}
		}

		return 0;
	},

	uniqueId: function() {
		return this.each(function() {
			if ( !this.id ) {
				this.id = "ui-id-" + (++uuid);
			}
		});
	},

	removeUniqueId: function() {
		return this.each(function() {
			if ( runiqueId.test( this.id ) ) {
				$( this ).removeAttr( "id" );
			}
		});
	}
});

// selectors
function focusable( element, isTabIndexNotNaN ) {
	var map, mapName, img,
		nodeName = element.nodeName.toLowerCase();
	if ( "area" === nodeName ) {
		map = element.parentNode;
		mapName = map.name;
		if ( !element.href || !mapName || map.nodeName.toLowerCase() !== "map" ) {
			return false;
		}
		img = $( "img[usemap=#" + mapName + "]" )[0];
		return !!img && visible( img );
	}
	return ( /input|select|textarea|button|object/.test( nodeName ) ?
		!element.disabled :
		"a" === nodeName ?
			element.href || isTabIndexNotNaN :
			isTabIndexNotNaN) &&
		// the element and all of its ancestors must be visible
		visible( element );
}

function visible( element ) {
	return $.expr.filters.visible( element ) &&
		!$( element ).parents().addBack().filter(function() {
			return $.css( this, "visibility" ) === "hidden";
		}).length;
}

$.extend( $.expr[ ":" ], {
	data: $.expr.createPseudo ?
		$.expr.createPseudo(function( dataName ) {
			return function( elem ) {
				return !!$.data( elem, dataName );
			};
		}) :
		// support: jQuery <1.8
		function( elem, i, match ) {
			return !!$.data( elem, match[ 3 ] );
		},

	focusable: function( element ) {
		return focusable( element, !isNaN( $.attr( element, "tabindex" ) ) );
	},

	tabbable: function( element ) {
		var tabIndex = $.attr( element, "tabindex" ),
			isTabIndexNaN = isNaN( tabIndex );
		return ( isTabIndexNaN || tabIndex >= 0 ) && focusable( element, !isTabIndexNaN );
	}
});

// support: jQuery <1.8
if ( !$( "<a>" ).outerWidth( 1 ).jquery ) {
	$.each( [ "Width", "Height" ], function( i, name ) {
		var side = name === "Width" ? [ "Left", "Right" ] : [ "Top", "Bottom" ],
			type = name.toLowerCase(),
			orig = {
				innerWidth: $.fn.innerWidth,
				innerHeight: $.fn.innerHeight,
				outerWidth: $.fn.outerWidth,
				outerHeight: $.fn.outerHeight
			};

		function reduce( elem, size, border, margin ) {
			$.each( side, function() {
				size -= parseFloat( $.css( elem, "padding" + this ) ) || 0;
				if ( border ) {
					size -= parseFloat( $.css( elem, "border" + this + "Width" ) ) || 0;
				}
				if ( margin ) {
					size -= parseFloat( $.css( elem, "margin" + this ) ) || 0;
				}
			});
			return size;
		}

		$.fn[ "inner" + name ] = function( size ) {
			if ( size === undefined ) {
				return orig[ "inner" + name ].call( this );
			}

			return this.each(function() {
				$( this ).css( type, reduce( this, size ) + "px" );
			});
		};

		$.fn[ "outer" + name] = function( size, margin ) {
			if ( typeof size !== "number" ) {
				return orig[ "outer" + name ].call( this, size );
			}

			return this.each(function() {
				$( this).css( type, reduce( this, size, true, margin ) + "px" );
			});
		};
	});
}

// support: jQuery <1.8
if ( !$.fn.addBack ) {
	$.fn.addBack = function( selector ) {
		return this.add( selector == null ?
			this.prevObject : this.prevObject.filter( selector )
		);
	};
}

// support: jQuery 1.6.1, 1.6.2 (http://bugs.jquery.com/ticket/9413)
if ( $( "<a>" ).data( "a-b", "a" ).removeData( "a-b" ).data( "a-b" ) ) {
	$.fn.removeData = (function( removeData ) {
		return function( key ) {
			if ( arguments.length ) {
				return removeData.call( this, $.camelCase( key ) );
			} else {
				return removeData.call( this );
			}
		};
	})( $.fn.removeData );
}





// deprecated
$.ui.ie = !!/msie [\w.]+/.exec( navigator.userAgent.toLowerCase() );

$.support.selectstart = "onselectstart" in document.createElement( "div" );
$.fn.extend({
	disableSelection: function() {
		return this.bind( ( $.support.selectstart ? "selectstart" : "mousedown" ) +
			".ui-disableSelection", function( event ) {
				event.preventDefault();
			});
	},

	enableSelection: function() {
		return this.unbind( ".ui-disableSelection" );
	}
});

$.extend( $.ui, {
	// $.ui.plugin is deprecated. Use $.widget() extensions instead.
	plugin: {
		add: function( module, option, set ) {
			var i,
				proto = $.ui[ module ].prototype;
			for ( i in set ) {
				proto.plugins[ i ] = proto.plugins[ i ] || [];
				proto.plugins[ i ].push( [ option, set[ i ] ] );
			}
		},
		call: function( instance, name, args ) {
			var i,
				set = instance.plugins[ name ];
			if ( !set || !instance.element[ 0 ].parentNode || instance.element[ 0 ].parentNode.nodeType === 11 ) {
				return;
			}

			for ( i = 0; i < set.length; i++ ) {
				if ( instance.options[ set[ i ][ 0 ] ] ) {
					set[ i ][ 1 ].apply( instance.element, args );
				}
			}
		}
	},

	// only used by resizable
	hasScroll: function( el, a ) {

		//If overflow is hidden, the element might have extra content, but the user wants to hide it
		if ( $( el ).css( "overflow" ) === "hidden") {
			return false;
		}

		var scroll = ( a && a === "left" ) ? "scrollLeft" : "scrollTop",
			has = false;

		if ( el[ scroll ] > 0 ) {
			return true;
		}

		// TODO: determine which cases actually cause this to happen
		// if the element doesn't have the scroll set, see if it's possible to
		// set the scroll
		el[ scroll ] = 1;
		has = ( el[ scroll ] > 0 );
		el[ scroll ] = 0;
		return has;
	}
});

})( jQuery );
;/*!
 * jQuery UI Widget 1.10.3
 * http://jqueryui.com
 *
 * Copyright 2013 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/jQuery.widget/
 */
(function( $, undefined ) {

var uuid = 0,
	slice = Array.prototype.slice,
	_cleanData = $.cleanData;
$.cleanData = function( elems ) {
	for ( var i = 0, elem; (elem = elems[i]) != null; i++ ) {
		try {
			$( elem ).triggerHandler( "remove" );
		// http://bugs.jquery.com/ticket/8235
		} catch( e ) {}
	}
	_cleanData( elems );
};

$.widget = function( name, base, prototype ) {
	var fullName, existingConstructor, constructor, basePrototype,
		// proxiedPrototype allows the provided prototype to remain unmodified
		// so that it can be used as a mixin for multiple widgets (#8876)
		proxiedPrototype = {},
		namespace = name.split( "." )[ 0 ];

	name = name.split( "." )[ 1 ];
	fullName = namespace + "-" + name;

	if ( !prototype ) {
		prototype = base;
		base = $.Widget;
	}

	// create selector for plugin
	$.expr[ ":" ][ fullName.toLowerCase() ] = function( elem ) {
		return !!$.data( elem, fullName );
	};

	$[ namespace ] = $[ namespace ] || {};
	existingConstructor = $[ namespace ][ name ];
	constructor = $[ namespace ][ name ] = function( options, element ) {
		// allow instantiation without "new" keyword
		if ( !this._createWidget ) {
			return new constructor( options, element );
		}

		// allow instantiation without initializing for simple inheritance
		// must use "new" keyword (the code above always passes args)
		if ( arguments.length ) {
			this._createWidget( options, element );
		}
	};
	// extend with the existing constructor to carry over any static properties
	$.extend( constructor, existingConstructor, {
		version: prototype.version,
		// copy the object used to create the prototype in case we need to
		// redefine the widget later
		_proto: $.extend( {}, prototype ),
		// track widgets that inherit from this widget in case this widget is
		// redefined after a widget inherits from it
		_childConstructors: []
	});

	basePrototype = new base();
	// we need to make the options hash a property directly on the new instance
	// otherwise we'll modify the options hash on the prototype that we're
	// inheriting from
	basePrototype.options = $.widget.extend( {}, basePrototype.options );
	$.each( prototype, function( prop, value ) {
		if ( !$.isFunction( value ) ) {
			proxiedPrototype[ prop ] = value;
			return;
		}
		proxiedPrototype[ prop ] = (function() {
			var _super = function() {
					return base.prototype[ prop ].apply( this, arguments );
				},
				_superApply = function( args ) {
					return base.prototype[ prop ].apply( this, args );
				};
			return function() {
				var __super = this._super,
					__superApply = this._superApply,
					returnValue;

				this._super = _super;
				this._superApply = _superApply;

				returnValue = value.apply( this, arguments );

				this._super = __super;
				this._superApply = __superApply;

				return returnValue;
			};
		})();
	});
	constructor.prototype = $.widget.extend( basePrototype, {
		// TODO: remove support for widgetEventPrefix
		// always use the name + a colon as the prefix, e.g., draggable:start
		// don't prefix for widgets that aren't DOM-based
		widgetEventPrefix: existingConstructor ? basePrototype.widgetEventPrefix : name
	}, proxiedPrototype, {
		constructor: constructor,
		namespace: namespace,
		widgetName: name,
		widgetFullName: fullName
	});

	// If this widget is being redefined then we need to find all widgets that
	// are inheriting from it and redefine all of them so that they inherit from
	// the new version of this widget. We're essentially trying to replace one
	// level in the prototype chain.
	if ( existingConstructor ) {
		$.each( existingConstructor._childConstructors, function( i, child ) {
			var childPrototype = child.prototype;

			// redefine the child widget using the same prototype that was
			// originally used, but inherit from the new version of the base
			$.widget( childPrototype.namespace + "." + childPrototype.widgetName, constructor, child._proto );
		});
		// remove the list of existing child constructors from the old constructor
		// so the old child constructors can be garbage collected
		delete existingConstructor._childConstructors;
	} else {
		base._childConstructors.push( constructor );
	}

	$.widget.bridge( name, constructor );
};

$.widget.extend = function( target ) {
	var input = slice.call( arguments, 1 ),
		inputIndex = 0,
		inputLength = input.length,
		key,
		value;
	for ( ; inputIndex < inputLength; inputIndex++ ) {
		for ( key in input[ inputIndex ] ) {
			value = input[ inputIndex ][ key ];
			if ( input[ inputIndex ].hasOwnProperty( key ) && value !== undefined ) {
				// Clone objects
				if ( $.isPlainObject( value ) ) {
					target[ key ] = $.isPlainObject( target[ key ] ) ?
						$.widget.extend( {}, target[ key ], value ) :
						// Don't extend strings, arrays, etc. with objects
						$.widget.extend( {}, value );
				// Copy everything else by reference
				} else {
					target[ key ] = value;
				}
			}
		}
	}
	return target;
};

$.widget.bridge = function( name, object ) {
	var fullName = object.prototype.widgetFullName || name;
	$.fn[ name ] = function( options ) {
		var isMethodCall = typeof options === "string",
			args = slice.call( arguments, 1 ),
			returnValue = this;

		// allow multiple hashes to be passed on init
		options = !isMethodCall && args.length ?
			$.widget.extend.apply( null, [ options ].concat(args) ) :
			options;

		if ( isMethodCall ) {
			this.each(function() {
				var methodValue,
					instance = $.data( this, fullName );
				if ( !instance ) {
					return $.error( "cannot call methods on " + name + " prior to initialization; " +
						"attempted to call method '" + options + "'" );
				}
				if ( !$.isFunction( instance[options] ) || options.charAt( 0 ) === "_" ) {
					return $.error( "no such method '" + options + "' for " + name + " widget instance" );
				}
				methodValue = instance[ options ].apply( instance, args );
				if ( methodValue !== instance && methodValue !== undefined ) {
					returnValue = methodValue && methodValue.jquery ?
						returnValue.pushStack( methodValue.get() ) :
						methodValue;
					return false;
				}
			});
		} else {
			this.each(function() {
				var instance = $.data( this, fullName );
				if ( instance ) {
					instance.option( options || {} )._init();
				} else {
					$.data( this, fullName, new object( options, this ) );
				}
			});
		}

		return returnValue;
	};
};

$.Widget = function( /* options, element */ ) {};
$.Widget._childConstructors = [];

$.Widget.prototype = {
	widgetName: "widget",
	widgetEventPrefix: "",
	defaultElement: "<div>",
	options: {
		disabled: false,

		// callbacks
		create: null
	},
	_createWidget: function( options, element ) {
		element = $( element || this.defaultElement || this )[ 0 ];
		this.element = $( element );
		this.uuid = uuid++;
		this.eventNamespace = "." + this.widgetName + this.uuid;
		this.options = $.widget.extend( {},
			this.options,
			this._getCreateOptions(),
			options );

		this.bindings = $();
		this.hoverable = $();
		this.focusable = $();

		if ( element !== this ) {
			$.data( element, this.widgetFullName, this );
			this._on( true, this.element, {
				remove: function( event ) {
					if ( event.target === element ) {
						this.destroy();
					}
				}
			});
			this.document = $( element.style ?
				// element within the document
				element.ownerDocument :
				// element is window or document
				element.document || element );
			this.window = $( this.document[0].defaultView || this.document[0].parentWindow );
		}

		this._create();
		this._trigger( "create", null, this._getCreateEventData() );
		this._init();
	},
	_getCreateOptions: $.noop,
	_getCreateEventData: $.noop,
	_create: $.noop,
	_init: $.noop,

	destroy: function() {
		this._destroy();
		// we can probably remove the unbind calls in 2.0
		// all event bindings should go through this._on()
		this.element
			.unbind( this.eventNamespace )
			// 1.9 BC for #7810
			// TODO remove dual storage
			.removeData( this.widgetName )
			.removeData( this.widgetFullName )
			// support: jquery <1.6.3
			// http://bugs.jquery.com/ticket/9413
			.removeData( $.camelCase( this.widgetFullName ) );
		this.widget()
			.unbind( this.eventNamespace )
			.removeAttr( "aria-disabled" )
			.removeClass(
				this.widgetFullName + "-disabled " +
				"ui-state-disabled" );

		// clean up events and states
		this.bindings.unbind( this.eventNamespace );
		this.hoverable.removeClass( "ui-state-hover" );
		this.focusable.removeClass( "ui-state-focus" );
	},
	_destroy: $.noop,

	widget: function() {
		return this.element;
	},

	option: function( key, value ) {
		var options = key,
			parts,
			curOption,
			i;

		if ( arguments.length === 0 ) {
			// don't return a reference to the internal hash
			return $.widget.extend( {}, this.options );
		}

		if ( typeof key === "string" ) {
			// handle nested keys, e.g., "foo.bar" => { foo: { bar: ___ } }
			options = {};
			parts = key.split( "." );
			key = parts.shift();
			if ( parts.length ) {
				curOption = options[ key ] = $.widget.extend( {}, this.options[ key ] );
				for ( i = 0; i < parts.length - 1; i++ ) {
					curOption[ parts[ i ] ] = curOption[ parts[ i ] ] || {};
					curOption = curOption[ parts[ i ] ];
				}
				key = parts.pop();
				if ( value === undefined ) {
					return curOption[ key ] === undefined ? null : curOption[ key ];
				}
				curOption[ key ] = value;
			} else {
				if ( value === undefined ) {
					return this.options[ key ] === undefined ? null : this.options[ key ];
				}
				options[ key ] = value;
			}
		}

		this._setOptions( options );

		return this;
	},
	_setOptions: function( options ) {
		var key;

		for ( key in options ) {
			this._setOption( key, options[ key ] );
		}

		return this;
	},
	_setOption: function( key, value ) {
		this.options[ key ] = value;

		if ( key === "disabled" ) {
			this.widget()
				.toggleClass( this.widgetFullName + "-disabled ui-state-disabled", !!value )
				.attr( "aria-disabled", value );
			this.hoverable.removeClass( "ui-state-hover" );
			this.focusable.removeClass( "ui-state-focus" );
		}

		return this;
	},

	enable: function() {
		return this._setOption( "disabled", false );
	},
	disable: function() {
		return this._setOption( "disabled", true );
	},

	_on: function( suppressDisabledCheck, element, handlers ) {
		var delegateElement,
			instance = this;

		// no suppressDisabledCheck flag, shuffle arguments
		if ( typeof suppressDisabledCheck !== "boolean" ) {
			handlers = element;
			element = suppressDisabledCheck;
			suppressDisabledCheck = false;
		}

		// no element argument, shuffle and use this.element
		if ( !handlers ) {
			handlers = element;
			element = this.element;
			delegateElement = this.widget();
		} else {
			// accept selectors, DOM elements
			element = delegateElement = $( element );
			this.bindings = this.bindings.add( element );
		}

		$.each( handlers, function( event, handler ) {
			function handlerProxy() {
				// allow widgets to customize the disabled handling
				// - disabled as an array instead of boolean
				// - disabled class as method for disabling individual parts
				if ( !suppressDisabledCheck &&
						( instance.options.disabled === true ||
							$( this ).hasClass( "ui-state-disabled" ) ) ) {
					return;
				}
				return ( typeof handler === "string" ? instance[ handler ] : handler )
					.apply( instance, arguments );
			}

			// copy the guid so direct unbinding works
			if ( typeof handler !== "string" ) {
				handlerProxy.guid = handler.guid =
					handler.guid || handlerProxy.guid || $.guid++;
			}

			var match = event.match( /^(\w+)\s*(.*)$/ ),
				eventName = match[1] + instance.eventNamespace,
				selector = match[2];
			if ( selector ) {
				delegateElement.delegate( selector, eventName, handlerProxy );
			} else {
				element.bind( eventName, handlerProxy );
			}
		});
	},

	_off: function( element, eventName ) {
		eventName = (eventName || "").split( " " ).join( this.eventNamespace + " " ) + this.eventNamespace;
		element.unbind( eventName ).undelegate( eventName );
	},

	_delay: function( handler, delay ) {
		function handlerProxy() {
			return ( typeof handler === "string" ? instance[ handler ] : handler )
				.apply( instance, arguments );
		}
		var instance = this;
		return setTimeout( handlerProxy, delay || 0 );
	},

	_hoverable: function( element ) {
		this.hoverable = this.hoverable.add( element );
		this._on( element, {
			mouseenter: function( event ) {
				$( event.currentTarget ).addClass( "ui-state-hover" );
			},
			mouseleave: function( event ) {
				$( event.currentTarget ).removeClass( "ui-state-hover" );
			}
		});
	},

	_focusable: function( element ) {
		this.focusable = this.focusable.add( element );
		this._on( element, {
			focusin: function( event ) {
				$( event.currentTarget ).addClass( "ui-state-focus" );
			},
			focusout: function( event ) {
				$( event.currentTarget ).removeClass( "ui-state-focus" );
			}
		});
	},

	_trigger: function( type, event, data ) {
		var prop, orig,
			callback = this.options[ type ];

		data = data || {};
		event = $.Event( event );
		event.type = ( type === this.widgetEventPrefix ?
			type :
			this.widgetEventPrefix + type ).toLowerCase();
		// the original event may come from any element
		// so we need to reset the target on the new event
		event.target = this.element[ 0 ];

		// copy original event properties over to the new event
		orig = event.originalEvent;
		if ( orig ) {
			for ( prop in orig ) {
				if ( !( prop in event ) ) {
					event[ prop ] = orig[ prop ];
				}
			}
		}

		this.element.trigger( event, data );
		return !( $.isFunction( callback ) &&
			callback.apply( this.element[0], [ event ].concat( data ) ) === false ||
			event.isDefaultPrevented() );
	}
};

$.each( { show: "fadeIn", hide: "fadeOut" }, function( method, defaultEffect ) {
	$.Widget.prototype[ "_" + method ] = function( element, options, callback ) {
		if ( typeof options === "string" ) {
			options = { effect: options };
		}
		var hasOptions,
			effectName = !options ?
				method :
				options === true || typeof options === "number" ?
					defaultEffect :
					options.effect || defaultEffect;
		options = options || {};
		if ( typeof options === "number" ) {
			options = { duration: options };
		}
		hasOptions = !$.isEmptyObject( options );
		options.complete = callback;
		if ( options.delay ) {
			element.delay( options.delay );
		}
		if ( hasOptions && $.effects && $.effects.effect[ effectName ] ) {
			element[ method ]( options );
		} else if ( effectName !== method && element[ effectName ] ) {
			element[ effectName ]( options.duration, options.easing, callback );
		} else {
			element.queue(function( next ) {
				$( this )[ method ]();
				if ( callback ) {
					callback.call( element[ 0 ] );
				}
				next();
			});
		}
	};
});

})( jQuery );
;/*!
 * jQuery UI Datepicker 1.10.3
 * http://jqueryui.com
 *
 * Copyright 2013 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/datepicker/
 *
 * Depends:
 *	jquery.ui.core.js
 */
(function( $, undefined ) {

$.extend($.ui, { datepicker: { version: "1.10.3" } });

var PROP_NAME = "datepicker",
	instActive;

/* Date picker manager.
   Use the singleton instance of this class, $.datepicker, to interact with the date picker.
   Settings for (groups of) date pickers are maintained in an instance object,
   allowing multiple different settings on the same page. */

function Datepicker() {
	this._curInst = null; // The current instance in use
	this._keyEvent = false; // If the last event was a key event
	this._disabledInputs = []; // List of date picker inputs that have been disabled
	this._datepickerShowing = false; // True if the popup picker is showing , false if not
	this._inDialog = false; // True if showing within a "dialog", false if not
	this._mainDivId = "ui-datepicker-div"; // The ID of the main datepicker division
	this._inlineClass = "ui-datepicker-inline"; // The name of the inline marker class
	this._appendClass = "ui-datepicker-append"; // The name of the append marker class
	this._triggerClass = "ui-datepicker-trigger"; // The name of the trigger marker class
	this._dialogClass = "ui-datepicker-dialog"; // The name of the dialog marker class
	this._disableClass = "ui-datepicker-disabled"; // The name of the disabled covering marker class
	this._unselectableClass = "ui-datepicker-unselectable"; // The name of the unselectable cell marker class
	this._currentClass = "ui-datepicker-current-day"; // The name of the current day marker class
	this._dayOverClass = "ui-datepicker-days-cell-over"; // The name of the day hover marker class
	this.regional = []; // Available regional settings, indexed by language code
	this.regional[""] = { // Default regional settings
		closeText: "Done", // Display text for close link
		prevText: "?????????", // Display text for previous month link
		nextText: "?????????", // Display text for next month link
		currentText: "??????", // Display text for current month link
		monthNames: ["1???","2???","3???","4???","5???","6???",
			"7???","8???","9???","10???","11???","12???"], // Names of months for drop-down and formatting
		monthNamesShort: ["1???", "2???", "3???", "4???", "5???", "6???", "7???", "8???", "9???", "10???", "11???", "12???"], // For formatting
		dayNames: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"], // For formatting
		dayNamesShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"], // For formatting
		dayNamesMin: ["???","???","???","???","???","???","???"], // Column headings for days starting at Sunday
		weekHeader: "Wk", // Column header for week of the year
		dateFormat: "yy-mm-dd", // See format options on parseDate
		firstDay: 0, // The first day of the week, Sun = 0, Mon = 1, ...
		isRTL: false, // True if right-to-left language, false if left-to-right
		showMonthAfterYear: false, // True if the year select precedes month, false for month then year
		yearSuffix: "" // Additional text to append to the year in the month headers
	};
	this._defaults = { // Global defaults for all the date picker instances
		showOn: "focus", // "focus" for popup on focus,
			// "button" for trigger button, or "both" for either
		showAnim: "fadeIn", // Name of jQuery animation for popup
		showOptions: {}, // Options for enhanced animations
		defaultDate: null, // Used when field is blank: actual date,
			// +/-number for offset from today, null for today
		appendText: "", // Display text following the input box, e.g. showing the format
		buttonText: "...", // Text for trigger button
		buttonImage: "", // URL for trigger button image
		buttonImageOnly: false, // True if the image appears alone, false if it appears on a button
		hideIfNoPrevNext: false, // True to hide next/previous month links
			// if not applicable, false to just disable them
		navigationAsDateFormat: false, // True if date formatting applied to prev/today/next links
		gotoCurrent: false, // True if today link goes back to current selection instead
		changeMonth: true, // True if month can be selected directly, false if only prev/next
		changeYear: true, // True if year can be selected directly, false if only prev/next
		yearRange: "c-10:c+10", // Range of years to display in drop-down,
			// either relative to today's year (-nn:+nn), relative to currently displayed year
			// (c-nn:c+nn), absolute (nnnn:nnnn), or a combination of the above (nnnn:-n)
		showOtherMonths: false, // True to show dates in other months, false to leave blank
		selectOtherMonths: false, // True to allow selection of dates in other months, false for unselectable
		showWeek: false, // True to show week of the year, false to not show it
		calculateWeek: this.iso8601Week, // How to calculate the week of the year,
			// takes a Date and returns the number of the week for it
		shortYearCutoff: "+10", // Short year values < this are in the current century,
			// > this are in the previous century,
			// string value starting with "+" for current year + value
		minDate: null, // The earliest selectable date, or null for no limit
		maxDate: null, // The latest selectable date, or null for no limit
		duration: "fast", // Duration of display/closure
		beforeShowDay: null, // Function that takes a date and returns an array with
			// [0] = true if selectable, false if not, [1] = custom CSS class name(s) or "",
			// [2] = cell title (optional), e.g. $.datepicker.noWeekends
		beforeShow: null, // Function that takes an input field and
			// returns a set of custom settings for the date picker
		onSelect: null, // Define a callback function when a date is selected
		onChangeMonthYear: null, // Define a callback function when the month or year is changed
		onClose: null, // Define a callback function when the datepicker is closed
		numberOfMonths: 1, // Number of months to show at a time
		showCurrentAtPos: 0, // The position in multipe months at which to show the current month (starting at 0)
		stepMonths: 1, // Number of months to step back/forward
		stepBigMonths: 12, // Number of months to step back/forward for the big links
		altField: "", // Selector for an alternate field to store selected dates into
		altFormat: "", // The date format to use for the alternate field
		constrainInput: true, // The input is constrained by the current date format
		showButtonPanel: false, // True to show button panel, false to not show it
		autoSize: false, // True to size the input for the date format, false to leave as is
		disabled: false // The initial disabled state
	};
	$.extend(this._defaults, this.regional[""]);
	this.dpDiv = bindHover($("<div id='" + this._mainDivId + "' class='ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all'></div>"));
}

$.extend(Datepicker.prototype, {
	/* Class name added to elements to indicate already configured with a date picker. */
	markerClassName: "hasDatepicker",

	//Keep track of the maximum number of rows displayed (see #7043)
	maxRows: 4,

	// TODO rename to "widget" when switching to widget factory
	_widgetDatepicker: function() {
		return this.dpDiv;
	},

	/* Override the default settings for all instances of the date picker.
	 * @param  settings  object - the new settings to use as defaults (anonymous object)
	 * @return the manager object
	 */
	setDefaults: function(settings) {
		extendRemove(this._defaults, settings || {});
		return this;
	},

	/* Attach the date picker to a jQuery selection.
	 * @param  target	element - the target input field or division or span
	 * @param  settings  object - the new settings to use for this date picker instance (anonymous)
	 */
	_attachDatepicker: function(target, settings) {
		var nodeName, inline, inst;
		nodeName = target.nodeName.toLowerCase();
		inline = (nodeName === "div" || nodeName === "span");
		if (!target.id) {
			this.uuid += 1;
			target.id = "dp" + this.uuid;
		}
		inst = this._newInst($(target), inline);
		inst.settings = $.extend({}, settings || {});
		if (nodeName === "input") {
			this._connectDatepicker(target, inst);
		} else if (inline) {
			this._inlineDatepicker(target, inst);
		}
	},

	/* Create a new instance object. */
	_newInst: function(target, inline) {
		var id = target[0].id.replace(/([^A-Za-z0-9_\-])/g, "\\\\$1"); // escape jQuery meta chars
		return {id: id, input: target, // associated target
			selectedDay: 0, selectedMonth: 0, selectedYear: 0, // current selection
			drawMonth: 0, drawYear: 0, // month being drawn
			inline: inline, // is datepicker inline or not
			dpDiv: (!inline ? this.dpDiv : // presentation div
			bindHover($("<div class='" + this._inlineClass + " ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all'></div>")))};
	},

	/* Attach the date picker to an input field. */
	_connectDatepicker: function(target, inst) {
		var input = $(target);
		inst.append = $([]);
		inst.trigger = $([]);
		if (input.hasClass(this.markerClassName)) {
			return;
		}
		this._attachments(input, inst);
		input.addClass(this.markerClassName).keydown(this._doKeyDown).
			keypress(this._doKeyPress).keyup(this._doKeyUp);
		this._autoSize(inst);
		$.data(target, PROP_NAME, inst);
		//If disabled option is true, disable the datepicker once it has been attached to the input (see ticket #5665)
		if( inst.settings.disabled ) {
			this._disableDatepicker( target );
		}
	},

	/* Make attachments based on settings. */
	_attachments: function(input, inst) {
		var showOn, buttonText, buttonImage,
			appendText = this._get(inst, "appendText"),
			isRTL = this._get(inst, "isRTL");

		if (inst.append) {
			inst.append.remove();
		}
		if (appendText) {
			inst.append = $("<span class='" + this._appendClass + "'>" + appendText + "</span>");
			input[isRTL ? "before" : "after"](inst.append);
		}

		input.unbind("focus", this._showDatepicker);

		if (inst.trigger) {
			inst.trigger.remove();
		}

		showOn = this._get(inst, "showOn");
		if (showOn === "focus" || showOn === "both") { // pop-up date picker when in the marked field
			input.focus(this._showDatepicker);
		}
		if (showOn === "button" || showOn === "both") { // pop-up date picker when button clicked
			buttonText = this._get(inst, "buttonText");
			buttonImage = this._get(inst, "buttonImage");
			inst.trigger = $(this._get(inst, "buttonImageOnly") ?
				$("<img/>").addClass(this._triggerClass).
					attr({ src: buttonImage, alt: buttonText, title: buttonText }) :
				$("<button type='button'></button>").addClass(this._triggerClass).
					html(!buttonImage ? buttonText : $("<img/>").attr(
					{ src:buttonImage, alt:buttonText, title:buttonText })));
			input[isRTL ? "before" : "after"](inst.trigger);
			inst.trigger.click(function() {
				if ($.datepicker._datepickerShowing && $.datepicker._lastInput === input[0]) {
					$.datepicker._hideDatepicker();
				} else if ($.datepicker._datepickerShowing && $.datepicker._lastInput !== input[0]) {
					$.datepicker._hideDatepicker();
					$.datepicker._showDatepicker(input[0]);
				} else {
					$.datepicker._showDatepicker(input[0]);
				}
				return false;
			});
		}
	},

	/* Apply the maximum length for the date format. */
	_autoSize: function(inst) {
		if (this._get(inst, "autoSize") && !inst.inline) {
			var findMax, max, maxI, i,
				date = new Date(2009, 12 - 1, 20), // Ensure double digits
				dateFormat = this._get(inst, "dateFormat");

			if (dateFormat.match(/[DM]/)) {
				findMax = function(names) {
					max = 0;
					maxI = 0;
					for (i = 0; i < names.length; i++) {
						if (names[i].length > max) {
							max = names[i].length;
							maxI = i;
						}
					}
					return maxI;
				};
				date.setMonth(findMax(this._get(inst, (dateFormat.match(/MM/) ?
					"monthNames" : "monthNamesShort"))));
				date.setDate(findMax(this._get(inst, (dateFormat.match(/DD/) ?
					"dayNames" : "dayNamesShort"))) + 20 - date.getDay());
			}
			inst.input.attr("size", this._formatDate(inst, date).length);
		}
	},

	/* Attach an inline date picker to a div. */
	_inlineDatepicker: function(target, inst) {
		var divSpan = $(target);
		if (divSpan.hasClass(this.markerClassName)) {
			return;
		}
		divSpan.addClass(this.markerClassName).append(inst.dpDiv);
		$.data(target, PROP_NAME, inst);
		this._setDate(inst, this._getDefaultDate(inst), true);
		this._updateDatepicker(inst);
		this._updateAlternate(inst);
		//If disabled option is true, disable the datepicker before showing it (see ticket #5665)
		if( inst.settings.disabled ) {
			this._disableDatepicker( target );
		}
		// Set display:block in place of inst.dpDiv.show() which won't work on disconnected elements
		// http://bugs.jqueryui.com/ticket/7552 - A Datepicker created on a detached div has zero height
		inst.dpDiv.css( "display", "block" );
	},

	/* Pop-up the date picker in a "dialog" box.
	 * @param  input element - ignored
	 * @param  date	string or Date - the initial date to display
	 * @param  onSelect  function - the function to call when a date is selected
	 * @param  settings  object - update the dialog date picker instance's settings (anonymous object)
	 * @param  pos int[2] - coordinates for the dialog's position within the screen or
	 *					event - with x/y coordinates or
	 *					leave empty for default (screen centre)
	 * @return the manager object
	 */
	_dialogDatepicker: function(input, date, onSelect, settings, pos) {
		var id, browserWidth, browserHeight, scrollX, scrollY,
			inst = this._dialogInst; // internal instance

		if (!inst) {
			this.uuid += 1;
			id = "dp" + this.uuid;
			this._dialogInput = $("<input type='text' id='" + id +
				"' style='position: absolute; top: -100px; width: 0px;'/>");
			this._dialogInput.keydown(this._doKeyDown);
			$("body").append(this._dialogInput);
			inst = this._dialogInst = this._newInst(this._dialogInput, false);
			inst.settings = {};
			$.data(this._dialogInput[0], PROP_NAME, inst);
		}
		extendRemove(inst.settings, settings || {});
		date = (date && date.constructor === Date ? this._formatDate(inst, date) : date);
		this._dialogInput.val(date);

		this._pos = (pos ? (pos.length ? pos : [pos.pageX, pos.pageY]) : null);
		if (!this._pos) {
			browserWidth = document.documentElement.clientWidth;
			browserHeight = document.documentElement.clientHeight;
			scrollX = document.documentElement.scrollLeft || document.body.scrollLeft;
			scrollY = document.documentElement.scrollTop || document.body.scrollTop;
			this._pos = // should use actual width/height below
				[(browserWidth / 2) - 100 + scrollX, (browserHeight / 2) - 150 + scrollY];
		}

		// move input on screen for focus, but hidden behind dialog
		this._dialogInput.css("left", (this._pos[0] + 20) + "px").css("top", this._pos[1] + "px");
		inst.settings.onSelect = onSelect;
		this._inDialog = true;
		this.dpDiv.addClass(this._dialogClass);
		this._showDatepicker(this._dialogInput[0]);
		if ($.blockUI) {
			$.blockUI(this.dpDiv);
		}
		$.data(this._dialogInput[0], PROP_NAME, inst);
		return this;
	},

	/* Detach a datepicker from its control.
	 * @param  target	element - the target input field or division or span
	 */
	_destroyDatepicker: function(target) {
		var nodeName,
			$target = $(target),
			inst = $.data(target, PROP_NAME);

		if (!$target.hasClass(this.markerClassName)) {
			return;
		}

		nodeName = target.nodeName.toLowerCase();
		$.removeData(target, PROP_NAME);
		if (nodeName === "input") {
			inst.append.remove();
			inst.trigger.remove();
			$target.removeClass(this.markerClassName).
				unbind("focus", this._showDatepicker).
				unbind("keydown", this._doKeyDown).
				unbind("keypress", this._doKeyPress).
				unbind("keyup", this._doKeyUp);
		} else if (nodeName === "div" || nodeName === "span") {
			$target.removeClass(this.markerClassName).empty();
		}
	},

	/* Enable the date picker to a jQuery selection.
	 * @param  target	element - the target input field or division or span
	 */
	_enableDatepicker: function(target) {
		var nodeName, inline,
			$target = $(target),
			inst = $.data(target, PROP_NAME);

		if (!$target.hasClass(this.markerClassName)) {
			return;
		}

		nodeName = target.nodeName.toLowerCase();
		if (nodeName === "input") {
			target.disabled = false;
			inst.trigger.filter("button").
				each(function() { this.disabled = false; }).end().
				filter("img").css({opacity: "1.0", cursor: ""});
		} else if (nodeName === "div" || nodeName === "span") {
			inline = $target.children("." + this._inlineClass);
			inline.children().removeClass("ui-state-disabled");
			inline.find("select.ui-datepicker-month, select.ui-datepicker-year").
				prop("disabled", false);
		}
		this._disabledInputs = $.map(this._disabledInputs,
			function(value) { return (value === target ? null : value); }); // delete entry
	},

	/* Disable the date picker to a jQuery selection.
	 * @param  target	element - the target input field or division or span
	 */
	_disableDatepicker: function(target) {
		var nodeName, inline,
			$target = $(target),
			inst = $.data(target, PROP_NAME);

		if (!$target.hasClass(this.markerClassName)) {
			return;
		}

		nodeName = target.nodeName.toLowerCase();
		if (nodeName === "input") {
			target.disabled = true;
			inst.trigger.filter("button").
				each(function() { this.disabled = true; }).end().
				filter("img").css({opacity: "0.5", cursor: "default"});
		} else if (nodeName === "div" || nodeName === "span") {
			inline = $target.children("." + this._inlineClass);
			inline.children().addClass("ui-state-disabled");
			inline.find("select.ui-datepicker-month, select.ui-datepicker-year").
				prop("disabled", true);
		}
		this._disabledInputs = $.map(this._disabledInputs,
			function(value) { return (value === target ? null : value); }); // delete entry
		this._disabledInputs[this._disabledInputs.length] = target;
	},

	/* Is the first field in a jQuery collection disabled as a datepicker?
	 * @param  target	element - the target input field or division or span
	 * @return boolean - true if disabled, false if enabled
	 */
	_isDisabledDatepicker: function(target) {
		if (!target) {
			return false;
		}
		for (var i = 0; i < this._disabledInputs.length; i++) {
			if (this._disabledInputs[i] === target) {
				return true;
			}
		}
		return false;
	},

	/* Retrieve the instance data for the target control.
	 * @param  target  element - the target input field or division or span
	 * @return  object - the associated instance data
	 * @throws  error if a jQuery problem getting data
	 */
	_getInst: function(target) {
		try {
			return $.data(target, PROP_NAME);
		}
		catch (err) {
			throw "Missing instance data for this datepicker";
		}
	},

	/* Update or retrieve the settings for a date picker attached to an input field or division.
	 * @param  target  element - the target input field or division or span
	 * @param  name	object - the new settings to update or
	 *				string - the name of the setting to change or retrieve,
	 *				when retrieving also "all" for all instance settings or
	 *				"defaults" for all global defaults
	 * @param  value   any - the new value for the setting
	 *				(omit if above is an object or to retrieve a value)
	 */
	_optionDatepicker: function(target, name, value) {
		var settings, date, minDate, maxDate,
			inst = this._getInst(target);

		if (arguments.length === 2 && typeof name === "string") {
			return (name === "defaults" ? $.extend({}, $.datepicker._defaults) :
				(inst ? (name === "all" ? $.extend({}, inst.settings) :
				this._get(inst, name)) : null));
		}

		settings = name || {};
		if (typeof name === "string") {
			settings = {};
			settings[name] = value;
		}

		if (inst) {
			if (this._curInst === inst) {
				this._hideDatepicker();
			}

			date = this._getDateDatepicker(target, true);
			minDate = this._getMinMaxDate(inst, "min");
			maxDate = this._getMinMaxDate(inst, "max");
			extendRemove(inst.settings, settings);
			// reformat the old minDate/maxDate values if dateFormat changes and a new minDate/maxDate isn't provided
			if (minDate !== null && settings.dateFormat !== undefined && settings.minDate === undefined) {
				inst.settings.minDate = this._formatDate(inst, minDate);
			}
			if (maxDate !== null && settings.dateFormat !== undefined && settings.maxDate === undefined) {
				inst.settings.maxDate = this._formatDate(inst, maxDate);
			}
			if ( "disabled" in settings ) {
				if ( settings.disabled ) {
					this._disableDatepicker(target);
				} else {
					this._enableDatepicker(target);
				}
			}
			this._attachments($(target), inst);
			this._autoSize(inst);
			this._setDate(inst, date);
			this._updateAlternate(inst);
			this._updateDatepicker(inst);
		}
	},

	// change method deprecated
	_changeDatepicker: function(target, name, value) {
		this._optionDatepicker(target, name, value);
	},

	/* Redraw the date picker attached to an input field or division.
	 * @param  target  element - the target input field or division or span
	 */
	_refreshDatepicker: function(target) {
		var inst = this._getInst(target);
		if (inst) {
			this._updateDatepicker(inst);
		}
	},

	/* Set the dates for a jQuery selection.
	 * @param  target element - the target input field or division or span
	 * @param  date	Date - the new date
	 */
	_setDateDatepicker: function(target, date) {
		var inst = this._getInst(target);
		if (inst) {
			this._setDate(inst, date);
			this._updateDatepicker(inst);
			this._updateAlternate(inst);
		}
	},

	/* Get the date(s) for the first entry in a jQuery selection.
	 * @param  target element - the target input field or division or span
	 * @param  noDefault boolean - true if no default date is to be used
	 * @return Date - the current date
	 */
	_getDateDatepicker: function(target, noDefault) {
		var inst = this._getInst(target);
		if (inst && !inst.inline) {
			this._setDateFromField(inst, noDefault);
		}
		return (inst ? this._getDate(inst) : null);
	},

	/* Handle keystrokes. */
	_doKeyDown: function(event) {
		var onSelect, dateStr, sel,
			inst = $.datepicker._getInst(event.target),
			handled = true,
			isRTL = inst.dpDiv.is(".ui-datepicker-rtl");

		inst._keyEvent = true;
		if ($.datepicker._datepickerShowing) {
			switch (event.keyCode) {
				case 9: $.datepicker._hideDatepicker();
						handled = false;
						break; // hide on tab out
				case 13: sel = $("td." + $.datepicker._dayOverClass + ":not(." +
									$.datepicker._currentClass + ")", inst.dpDiv);
						if (sel[0]) {
							$.datepicker._selectDay(event.target, inst.selectedMonth, inst.selectedYear, sel[0]);
						}

						onSelect = $.datepicker._get(inst, "onSelect");
						if (onSelect) {
							dateStr = $.datepicker._formatDate(inst);

							// trigger custom callback
							onSelect.apply((inst.input ? inst.input[0] : null), [dateStr, inst]);
						} else {
							$.datepicker._hideDatepicker();
						}

						return false; // don't submit the form
				case 27: $.datepicker._hideDatepicker();
						break; // hide on escape
				case 33: $.datepicker._adjustDate(event.target, (event.ctrlKey ?
							-$.datepicker._get(inst, "stepBigMonths") :
							-$.datepicker._get(inst, "stepMonths")), "M");
						break; // previous month/year on page up/+ ctrl
				case 34: $.datepicker._adjustDate(event.target, (event.ctrlKey ?
							+$.datepicker._get(inst, "stepBigMonths") :
							+$.datepicker._get(inst, "stepMonths")), "M");
						break; // next month/year on page down/+ ctrl
				case 35: if (event.ctrlKey || event.metaKey) {
							$.datepicker._clearDate(event.target);
						}
						handled = event.ctrlKey || event.metaKey;
						break; // clear on ctrl or command +end
				case 36: if (event.ctrlKey || event.metaKey) {
							$.datepicker._gotoToday(event.target);
						}
						handled = event.ctrlKey || event.metaKey;
						break; // current on ctrl or command +home
				case 37: if (event.ctrlKey || event.metaKey) {
							$.datepicker._adjustDate(event.target, (isRTL ? +1 : -1), "D");
						}
						handled = event.ctrlKey || event.metaKey;
						// -1 day on ctrl or command +left
						if (event.originalEvent.altKey) {
							$.datepicker._adjustDate(event.target, (event.ctrlKey ?
								-$.datepicker._get(inst, "stepBigMonths") :
								-$.datepicker._get(inst, "stepMonths")), "M");
						}
						// next month/year on alt +left on Mac
						break;
				case 38: if (event.ctrlKey || event.metaKey) {
							$.datepicker._adjustDate(event.target, -7, "D");
						}
						handled = event.ctrlKey || event.metaKey;
						break; // -1 week on ctrl or command +up
				case 39: if (event.ctrlKey || event.metaKey) {
							$.datepicker._adjustDate(event.target, (isRTL ? -1 : +1), "D");
						}
						handled = event.ctrlKey || event.metaKey;
						// +1 day on ctrl or command +right
						if (event.originalEvent.altKey) {
							$.datepicker._adjustDate(event.target, (event.ctrlKey ?
								+$.datepicker._get(inst, "stepBigMonths") :
								+$.datepicker._get(inst, "stepMonths")), "M");
						}
						// next month/year on alt +right
						break;
				case 40: if (event.ctrlKey || event.metaKey) {
							$.datepicker._adjustDate(event.target, +7, "D");
						}
						handled = event.ctrlKey || event.metaKey;
						break; // +1 week on ctrl or command +down
				default: handled = false;
			}
		} else if (event.keyCode === 36 && event.ctrlKey) { // display the date picker on ctrl+home
			$.datepicker._showDatepicker(this);
		} else {
			handled = false;
		}

		if (handled) {
			event.preventDefault();
			event.stopPropagation();
		}
	},

	/* Filter entered characters - based on date format. */
	_doKeyPress: function(event) {
		var chars, chr,
			inst = $.datepicker._getInst(event.target);

		if ($.datepicker._get(inst, "constrainInput")) {
			chars = $.datepicker._possibleChars($.datepicker._get(inst, "dateFormat"));
			chr = String.fromCharCode(event.charCode == null ? event.keyCode : event.charCode);
			return event.ctrlKey || event.metaKey || (chr < " " || !chars || chars.indexOf(chr) > -1);
		}
	},

	/* Synchronise manual entry and field/alternate field. */
	_doKeyUp: function(event) {
		var date,
			inst = $.datepicker._getInst(event.target);

		if (inst.input.val() !== inst.lastVal) {
			try {
				date = $.datepicker.parseDate($.datepicker._get(inst, "dateFormat"),
					(inst.input ? inst.input.val() : null),
					$.datepicker._getFormatConfig(inst));

				if (date) { // only if valid
					$.datepicker._setDateFromField(inst);
					$.datepicker._updateAlternate(inst);
					$.datepicker._updateDatepicker(inst);
				}
			}
			catch (err) {
			}
		}
		return true;
	},

	/* Pop-up the date picker for a given input field.
	 * If false returned from beforeShow event handler do not show.
	 * @param  input  element - the input field attached to the date picker or
	 *					event - if triggered by focus
	 */
	_showDatepicker: function(input) {
		input = input.target || input;
		if (input.nodeName.toLowerCase() !== "input") { // find from button/image trigger
			input = $("input", input.parentNode)[0];
		}

		if ($.datepicker._isDisabledDatepicker(input) || $.datepicker._lastInput === input) { // already here
			return;
		}

		var inst, beforeShow, beforeShowSettings, isFixed,
			offset, showAnim, duration;

		inst = $.datepicker._getInst(input);
		if ($.datepicker._curInst && $.datepicker._curInst !== inst) {
			$.datepicker._curInst.dpDiv.stop(true, true);
			if ( inst && $.datepicker._datepickerShowing ) {
				$.datepicker._hideDatepicker( $.datepicker._curInst.input[0] );
			}
		}

		beforeShow = $.datepicker._get(inst, "beforeShow");
		beforeShowSettings = beforeShow ? beforeShow.apply(input, [input, inst]) : {};
		if(beforeShowSettings === false){
			return;
		}
		extendRemove(inst.settings, beforeShowSettings);

		inst.lastVal = null;
		$.datepicker._lastInput = input;
		$.datepicker._setDateFromField(inst);

		if ($.datepicker._inDialog) { // hide cursor
			input.value = "";
		}
		if (!$.datepicker._pos) { // position below input
			$.datepicker._pos = $.datepicker._findPos(input);
			$.datepicker._pos[1] += input.offsetHeight; // add the height
		}

		isFixed = false;
		$(input).parents().each(function() {
			isFixed |= $(this).css("position") === "fixed";
			return !isFixed;
		});

		offset = {left: $.datepicker._pos[0], top: $.datepicker._pos[1]};
		$.datepicker._pos = null;
		//to avoid flashes on Firefox
		inst.dpDiv.empty();
		// determine sizing offscreen
		inst.dpDiv.css({position: "absolute", display: "block", top: "-1000px"});
		$.datepicker._updateDatepicker(inst);
		// fix width for dynamic number of date pickers
		// and adjust position before showing
		offset = $.datepicker._checkOffset(inst, offset, isFixed);
		inst.dpDiv.css({position: ($.datepicker._inDialog && $.blockUI ?
			"static" : (isFixed ? "fixed" : "absolute")), display: "none",
			left: offset.left + "px", top: offset.top + "px"});

		if (!inst.inline) {
			showAnim = $.datepicker._get(inst, "showAnim");
			duration = $.datepicker._get(inst, "duration");
			inst.dpDiv.zIndex($(input).zIndex()+1);
			$.datepicker._datepickerShowing = true;

			if ( $.effects && $.effects.effect[ showAnim ] ) {
				inst.dpDiv.show(showAnim, $.datepicker._get(inst, "showOptions"), duration);
			} else {
				inst.dpDiv[showAnim || "show"](showAnim ? duration : null);
			}

			if ( $.datepicker._shouldFocusInput( inst ) ) {
				inst.input.focus();
			}

			$.datepicker._curInst = inst;
		}
	},

	/* Generate the date picker content. */
	_updateDatepicker: function(inst) {
		this.maxRows = 4; //Reset the max number of rows being displayed (see #7043)
		instActive = inst; // for delegate hover events
		inst.dpDiv.empty().append(this._generateHTML(inst));
		this._attachHandlers(inst);
		inst.dpDiv.find("." + this._dayOverClass + " a").mouseover();

		var origyearshtml,
			numMonths = this._getNumberOfMonths(inst),
			cols = numMonths[1],
			width = 17;

		inst.dpDiv.removeClass("ui-datepicker-multi-2 ui-datepicker-multi-3 ui-datepicker-multi-4").width("");
		if (cols > 1) {
			inst.dpDiv.addClass("ui-datepicker-multi-" + cols).css("width", (width * cols) + "em");
		}
		inst.dpDiv[(numMonths[0] !== 1 || numMonths[1] !== 1 ? "add" : "remove") +
			"Class"]("ui-datepicker-multi");
		inst.dpDiv[(this._get(inst, "isRTL") ? "add" : "remove") +
			"Class"]("ui-datepicker-rtl");

		if (inst === $.datepicker._curInst && $.datepicker._datepickerShowing && $.datepicker._shouldFocusInput( inst ) ) {
			inst.input.focus();
		}

		// deffered render of the years select (to avoid flashes on Firefox)
		if( inst.yearshtml ){
			origyearshtml = inst.yearshtml;
			setTimeout(function(){
				//assure that inst.yearshtml didn't change.
				if( origyearshtml === inst.yearshtml && inst.yearshtml ){
					inst.dpDiv.find("select.ui-datepicker-year:first").replaceWith(inst.yearshtml);
				}
				origyearshtml = inst.yearshtml = null;
			}, 0);
		}
	},

	// #6694 - don't focus the input if it's already focused
	// this breaks the change event in IE
	// Support: IE and jQuery <1.9
	_shouldFocusInput: function( inst ) {
		return inst.input && inst.input.is( ":visible" ) && !inst.input.is( ":disabled" ) && !inst.input.is( ":focus" );
	},

	/* Check positioning to remain on screen. */
	_checkOffset: function(inst, offset, isFixed) {
		var dpWidth = inst.dpDiv.outerWidth(),
			dpHeight = inst.dpDiv.outerHeight(),
			inputWidth = inst.input ? inst.input.outerWidth() : 0,
			inputHeight = inst.input ? inst.input.outerHeight() : 0,
			viewWidth = document.documentElement.clientWidth + (isFixed ? 0 : $(document).scrollLeft()),
			viewHeight = document.documentElement.clientHeight + (isFixed ? 0 : $(document).scrollTop());

		offset.left -= (this._get(inst, "isRTL") ? (dpWidth - inputWidth) : 0);
		offset.left -= (isFixed && offset.left === inst.input.offset().left) ? $(document).scrollLeft() : 0;
		offset.top -= (isFixed && offset.top === (inst.input.offset().top + inputHeight)) ? $(document).scrollTop() : 0;

		// now check if datepicker is showing outside window viewport - move to a better place if so.
		offset.left -= Math.min(offset.left, (offset.left + dpWidth > viewWidth && viewWidth > dpWidth) ?
			Math.abs(offset.left + dpWidth - viewWidth) : 0);
		offset.top -= Math.min(offset.top, (offset.top + dpHeight > viewHeight && viewHeight > dpHeight) ?
			Math.abs(dpHeight + inputHeight) : 0);

		return offset;
	},

	/* Find an object's position on the screen. */
	_findPos: function(obj) {
		var position,
			inst = this._getInst(obj),
			isRTL = this._get(inst, "isRTL");

		while (obj && (obj.type === "hidden" || obj.nodeType !== 1 || $.expr.filters.hidden(obj))) {
			obj = obj[isRTL ? "previousSibling" : "nextSibling"];
		}

		position = $(obj).offset();
		return [position.left, position.top];
	},

	/* Hide the date picker from view.
	 * @param  input  element - the input field attached to the date picker
	 */
	_hideDatepicker: function(input) {
		var showAnim, duration, postProcess, onClose,
			inst = this._curInst;

		if (!inst || (input && inst !== $.data(input, PROP_NAME))) {
			return;
		}

		if (this._datepickerShowing) {
			showAnim = this._get(inst, "showAnim");
			duration = this._get(inst, "duration");
			postProcess = function() {
				$.datepicker._tidyDialog(inst);
			};

			// DEPRECATED: after BC for 1.8.x $.effects[ showAnim ] is not needed
			if ( $.effects && ( $.effects.effect[ showAnim ] || $.effects[ showAnim ] ) ) {
				inst.dpDiv.hide(showAnim, $.datepicker._get(inst, "showOptions"), duration, postProcess);
			} else {
				inst.dpDiv[(showAnim === "slideDown" ? "slideUp" :
					(showAnim === "fadeIn" ? "fadeOut" : "hide"))]((showAnim ? duration : null), postProcess);
			}

			if (!showAnim) {
				postProcess();
			}
			this._datepickerShowing = false;

			onClose = this._get(inst, "onClose");
			if (onClose) {
				onClose.apply((inst.input ? inst.input[0] : null), [(inst.input ? inst.input.val() : ""), inst]);
			}

			this._lastInput = null;
			if (this._inDialog) {
				this._dialogInput.css({ position: "absolute", left: "0", top: "-100px" });
				if ($.blockUI) {
					$.unblockUI();
					$("body").append(this.dpDiv);
				}
			}
			this._inDialog = false;
		}
	},

	/* Tidy up after a dialog display. */
	_tidyDialog: function(inst) {
		inst.dpDiv.removeClass(this._dialogClass).unbind(".ui-datepicker-calendar");
	},

	/* Close date picker if clicked elsewhere. */
	_checkExternalClick: function(event) {
		if (!$.datepicker._curInst) {
			return;
		}

		var $target = $(event.target),
			inst = $.datepicker._getInst($target[0]);

		if ( ( ( $target[0].id !== $.datepicker._mainDivId &&
				$target.parents("#" + $.datepicker._mainDivId).length === 0 &&
				!$target.hasClass($.datepicker.markerClassName) &&
				!$target.closest("." + $.datepicker._triggerClass).length &&
				$.datepicker._datepickerShowing && !($.datepicker._inDialog && $.blockUI) ) ) ||
			( $target.hasClass($.datepicker.markerClassName) && $.datepicker._curInst !== inst ) ) {
				$.datepicker._hideDatepicker();
		}
	},

	/* Adjust one of the date sub-fields. */
	_adjustDate: function(id, offset, period) {
		var target = $(id),
			inst = this._getInst(target[0]);

		if (this._isDisabledDatepicker(target[0])) {
			return;
		}
		this._adjustInstDate(inst, offset +
			(period === "M" ? this._get(inst, "showCurrentAtPos") : 0), // undo positioning
			period);
		this._updateDatepicker(inst);
	},

	/* Action for current link. */
	_gotoToday: function(id) {
		var date,
			target = $(id),
			inst = this._getInst(target[0]);

		if (this._get(inst, "gotoCurrent") && inst.currentDay) {
			inst.selectedDay = inst.currentDay;
			inst.drawMonth = inst.selectedMonth = inst.currentMonth;
			inst.drawYear = inst.selectedYear = inst.currentYear;
		} else {
			date = new Date();
			inst.selectedDay = date.getDate();
			inst.drawMonth = inst.selectedMonth = date.getMonth();
			inst.drawYear = inst.selectedYear = date.getFullYear();
		}
		this._notifyChange(inst);
		this._adjustDate(target);
	},

	/* Action for selecting a new month/year. */
	_selectMonthYear: function(id, select, period) {
		var target = $(id),
			inst = this._getInst(target[0]);

		inst["selected" + (period === "M" ? "Month" : "Year")] =
		inst["draw" + (period === "M" ? "Month" : "Year")] =
			parseInt(select.options[select.selectedIndex].value,10);

		this._notifyChange(inst);
		this._adjustDate(target);
	},

	/* Action for selecting a day. */
	_selectDay: function(id, month, year, td) {
		var inst,
			target = $(id);

		if ($(td).hasClass(this._unselectableClass) || this._isDisabledDatepicker(target[0])) {
			return;
		}

		inst = this._getInst(target[0]);
		inst.selectedDay = inst.currentDay = $("a", td).html();
		inst.selectedMonth = inst.currentMonth = month;
		inst.selectedYear = inst.currentYear = year;
		this._selectDate(id, this._formatDate(inst,
			inst.currentDay, inst.currentMonth, inst.currentYear));
	},

	/* Erase the input field and hide the date picker. */
	_clearDate: function(id) {
		var target = $(id);
		this._selectDate(target, "");
	},

	/* Update the input field with the selected date. */
	_selectDate: function(id, dateStr) {
		var onSelect,
			target = $(id),
			inst = this._getInst(target[0]);

		dateStr = (dateStr != null ? dateStr : this._formatDate(inst));
		if (inst.input) {
			inst.input.val(dateStr);
		}
		this._updateAlternate(inst);

		onSelect = this._get(inst, "onSelect");
		if (onSelect) {
			onSelect.apply((inst.input ? inst.input[0] : null), [dateStr, inst]);  // trigger custom callback
		} else if (inst.input) {
			inst.input.trigger("change"); // fire the change event
		}

		if (inst.inline){
			this._updateDatepicker(inst);
		} else {
			this._hideDatepicker();
			this._lastInput = inst.input[0];
			if (typeof(inst.input[0]) !== "object") {
				inst.input.focus(); // restore focus
			}
			this._lastInput = null;
		}
	},

	/* Update any alternate field to synchronise with the main field. */
	_updateAlternate: function(inst) {
		var altFormat, date, dateStr,
			altField = this._get(inst, "altField");

		if (altField) { // update alternate field too
			altFormat = this._get(inst, "altFormat") || this._get(inst, "dateFormat");
			date = this._getDate(inst);
			dateStr = this.formatDate(altFormat, date, this._getFormatConfig(inst));
			$(altField).each(function() { $(this).val(dateStr); });
		}
	},

	/* Set as beforeShowDay function to prevent selection of weekends.
	 * @param  date  Date - the date to customise
	 * @return [boolean, string] - is this date selectable?, what is its CSS class?
	 */
	noWeekends: function(date) {
		var day = date.getDay();
		return [(day > 0 && day < 6), ""];
	},

	/* Set as calculateWeek to determine the week of the year based on the ISO 8601 definition.
	 * @param  date  Date - the date to get the week for
	 * @return  number - the number of the week within the year that contains this date
	 */
	iso8601Week: function(date) {
		var time,
			checkDate = new Date(date.getTime());

		// Find Thursday of this week starting on Monday
		checkDate.setDate(checkDate.getDate() + 4 - (checkDate.getDay() || 7));

		time = checkDate.getTime();
		checkDate.setMonth(0); // Compare with Jan 1
		checkDate.setDate(1);
		return Math.floor(Math.round((time - checkDate) / 86400000) / 7) + 1;
	},

	/* Parse a string value into a date object.
	 * See formatDate below for the possible formats.
	 *
	 * @param  format string - the expected format of the date
	 * @param  value string - the date in the above format
	 * @param  settings Object - attributes include:
	 *					shortYearCutoff  number - the cutoff year for determining the century (optional)
	 *					dayNamesShort	string[7] - abbreviated names of the days from Sunday (optional)
	 *					dayNames		string[7] - names of the days from Sunday (optional)
	 *					monthNamesShort string[12] - abbreviated names of the months (optional)
	 *					monthNames		string[12] - names of the months (optional)
	 * @return  Date - the extracted date value or null if value is blank
	 */
	parseDate: function (format, value, settings) {
		if (format == null || value == null) {
			throw "Invalid arguments";
		}

		value = (typeof value === "object" ? value.toString() : value + "");
		if (value === "") {
			return null;
		}

		var iFormat, dim, extra,
			iValue = 0,
			shortYearCutoffTemp = (settings ? settings.shortYearCutoff : null) || this._defaults.shortYearCutoff,
			shortYearCutoff = (typeof shortYearCutoffTemp !== "string" ? shortYearCutoffTemp :
				new Date().getFullYear() % 100 + parseInt(shortYearCutoffTemp, 10)),
			dayNamesShort = (settings ? settings.dayNamesShort : null) || this._defaults.dayNamesShort,
			dayNames = (settings ? settings.dayNames : null) || this._defaults.dayNames,
			monthNamesShort = (settings ? settings.monthNamesShort : null) || this._defaults.monthNamesShort,
			monthNames = (settings ? settings.monthNames : null) || this._defaults.monthNames,
			year = -1,
			month = -1,
			day = -1,
			doy = -1,
			literal = false,
			date,
			// Check whether a format character is doubled
			lookAhead = function(match) {
				var matches = (iFormat + 1 < format.length && format.charAt(iFormat + 1) === match);
				if (matches) {
					iFormat++;
				}
				return matches;
			},
			// Extract a number from the string value
			getNumber = function(match) {
				var isDoubled = lookAhead(match),
					size = (match === "@" ? 14 : (match === "!" ? 20 :
					(match === "y" && isDoubled ? 4 : (match === "o" ? 3 : 2)))),
					digits = new RegExp("^\\d{1," + size + "}"),
					num = value.substring(iValue).match(digits);
				if (!num) {
					throw "Missing number at position " + iValue;
				}
				iValue += num[0].length;
				return parseInt(num[0], 10);
			},
			// Extract a name from the string value and convert to an index
			getName = function(match, shortNames, longNames) {
				var index = -1,
					names = $.map(lookAhead(match) ? longNames : shortNames, function (v, k) {
						return [ [k, v] ];
					}).sort(function (a, b) {
						return -(a[1].length - b[1].length);
					});

				$.each(names, function (i, pair) {
					var name = pair[1];
					if (value.substr(iValue, name.length).toLowerCase() === name.toLowerCase()) {
						index = pair[0];
						iValue += name.length;
						return false;
					}
				});
				if (index !== -1) {
					return index + 1;
				} else {
					throw "Unknown name at position " + iValue;
				}
			},
			// Confirm that a literal character matches the string value
			checkLiteral = function() {
				if (value.charAt(iValue) !== format.charAt(iFormat)) {
					throw "Unexpected literal at position " + iValue;
				}
				iValue++;
			};

		for (iFormat = 0; iFormat < format.length; iFormat++) {
			if (literal) {
				if (format.charAt(iFormat) === "'" && !lookAhead("'")) {
					literal = false;
				} else {
					checkLiteral();
				}
			} else {
				switch (format.charAt(iFormat)) {
					case "d":
						day = getNumber("d");
						break;
					case "D":
						getName("D", dayNamesShort, dayNames);
						break;
					case "o":
						doy = getNumber("o");
						break;
					case "m":
						month = getNumber("m");
						break;
					case "M":
						month = getName("M", monthNamesShort, monthNames);
						break;
					case "y":
						year = getNumber("y");
						break;
					case "@":
						date = new Date(getNumber("@"));
						year = date.getFullYear();
						month = date.getMonth() + 1;
						day = date.getDate();
						break;
					case "!":
						date = new Date((getNumber("!") - this._ticksTo1970) / 10000);
						year = date.getFullYear();
						month = date.getMonth() + 1;
						day = date.getDate();
						break;
					case "'":
						if (lookAhead("'")){
							checkLiteral();
						} else {
							literal = true;
						}
						break;
					default:
						checkLiteral();
				}
			}
		}

		if (iValue < value.length){
			extra = value.substr(iValue);
			if (!/^\s+/.test(extra)) {
				throw "Extra/unparsed characters found in date: " + extra;
			}
		}

		if (year === -1) {
			year = new Date().getFullYear();
		} else if (year < 100) {
			year += new Date().getFullYear() - new Date().getFullYear() % 100 +
				(year <= shortYearCutoff ? 0 : -100);
		}

		if (doy > -1) {
			month = 1;
			day = doy;
			do {
				dim = this._getDaysInMonth(year, month - 1);
				if (day <= dim) {
					break;
				}
				month++;
				day -= dim;
			} while (true);
		}

		date = this._daylightSavingAdjust(new Date(year, month - 1, day));
		if (date.getFullYear() !== year || date.getMonth() + 1 !== month || date.getDate() !== day) {
			throw "Invalid date"; // E.g. 31/02/00
		}
		return date;
	},

	/* Standard date formats. */
	ATOM: "yy-mm-dd", // RFC 3339 (ISO 8601)
	COOKIE: "D, dd M yy",
	ISO_8601: "yy-mm-dd",
	RFC_822: "D, d M y",
	RFC_850: "DD, dd-M-y",
	RFC_1036: "D, d M y",
	RFC_1123: "D, d M yy",
	RFC_2822: "D, d M yy",
	RSS: "D, d M y", // RFC 822
	TICKS: "!",
	TIMESTAMP: "@",
	W3C: "yy-mm-dd", // ISO 8601

	_ticksTo1970: (((1970 - 1) * 365 + Math.floor(1970 / 4) - Math.floor(1970 / 100) +
		Math.floor(1970 / 400)) * 24 * 60 * 60 * 10000000),

	/* Format a date object into a string value.
	 * The format can be combinations of the following:
	 * d  - day of month (no leading zero)
	 * dd - day of month (two digit)
	 * o  - day of year (no leading zeros)
	 * oo - day of year (three digit)
	 * D  - day name short
	 * DD - day name long
	 * m  - month of year (no leading zero)
	 * mm - month of year (two digit)
	 * M  - month name short
	 * MM - month name long
	 * y  - year (two digit)
	 * yy - year (four digit)
	 * @ - Unix timestamp (ms since 01/01/1970)
	 * ! - Windows ticks (100ns since 01/01/0001)
	 * "..." - literal text
	 * '' - single quote
	 *
	 * @param  format string - the desired format of the date
	 * @param  date Date - the date value to format
	 * @param  settings Object - attributes include:
	 *					dayNamesShort	string[7] - abbreviated names of the days from Sunday (optional)
	 *					dayNames		string[7] - names of the days from Sunday (optional)
	 *					monthNamesShort string[12] - abbreviated names of the months (optional)
	 *					monthNames		string[12] - names of the months (optional)
	 * @return  string - the date in the above format
	 */
	formatDate: function (format, date, settings) {
		if (!date) {
			return "";
		}

		var iFormat,
			dayNamesShort = (settings ? settings.dayNamesShort : null) || this._defaults.dayNamesShort,
			dayNames = (settings ? settings.dayNames : null) || this._defaults.dayNames,
			monthNamesShort = (settings ? settings.monthNamesShort : null) || this._defaults.monthNamesShort,
			monthNames = (settings ? settings.monthNames : null) || this._defaults.monthNames,
			// Check whether a format character is doubled
			lookAhead = function(match) {
				var matches = (iFormat + 1 < format.length && format.charAt(iFormat + 1) === match);
				if (matches) {
					iFormat++;
				}
				return matches;
			},
			// Format a number, with leading zero if necessary
			formatNumber = function(match, value, len) {
				var num = "" + value;
				if (lookAhead(match)) {
					while (num.length < len) {
						num = "0" + num;
					}
				}
				return num;
			},
			// Format a name, short or long as requested
			formatName = function(match, value, shortNames, longNames) {
				return (lookAhead(match) ? longNames[value] : shortNames[value]);
			},
			output = "",
			literal = false;

		if (date) {
			for (iFormat = 0; iFormat < format.length; iFormat++) {
				if (literal) {
					if (format.charAt(iFormat) === "'" && !lookAhead("'")) {
						literal = false;
					} else {
						output += format.charAt(iFormat);
					}
				} else {
					switch (format.charAt(iFormat)) {
						case "d":
							output += formatNumber("d", date.getDate(), 2);
							break;
						case "D":
							output += formatName("D", date.getDay(), dayNamesShort, dayNames);
							break;
						case "o":
							output += formatNumber("o",
								Math.round((new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime() - new Date(date.getFullYear(), 0, 0).getTime()) / 86400000), 3);
							break;
						case "m":
							output += formatNumber("m", date.getMonth() + 1, 2);
							break;
						case "M":
							output += formatName("M", date.getMonth(), monthNamesShort, monthNames);
							break;
						case "y":
							output += (lookAhead("y") ? date.getFullYear() :
								(date.getYear() % 100 < 10 ? "0" : "") + date.getYear() % 100);
							break;
						case "@":
							output += date.getTime();
							break;
						case "!":
							output += date.getTime() * 10000 + this._ticksTo1970;
							break;
						case "'":
							if (lookAhead("'")) {
								output += "'";
							} else {
								literal = true;
							}
							break;
						default:
							output += format.charAt(iFormat);
					}
				}
			}
		}
		return output;
	},

	/* Extract all possible characters from the date format. */
	_possibleChars: function (format) {
		var iFormat,
			chars = "",
			literal = false,
			// Check whether a format character is doubled
			lookAhead = function(match) {
				var matches = (iFormat + 1 < format.length && format.charAt(iFormat + 1) === match);
				if (matches) {
					iFormat++;
				}
				return matches;
			};

		for (iFormat = 0; iFormat < format.length; iFormat++) {
			if (literal) {
				if (format.charAt(iFormat) === "'" && !lookAhead("'")) {
					literal = false;
				} else {
					chars += format.charAt(iFormat);
				}
			} else {
				switch (format.charAt(iFormat)) {
					case "d": case "m": case "y": case "@":
						chars += "0123456789";
						break;
					case "D": case "M":
						return null; // Accept anything
					case "'":
						if (lookAhead("'")) {
							chars += "'";
						} else {
							literal = true;
						}
						break;
					default:
						chars += format.charAt(iFormat);
				}
			}
		}
		return chars;
	},

	/* Get a setting value, defaulting if necessary. */
	_get: function(inst, name) {
		return inst.settings[name] !== undefined ?
			inst.settings[name] : this._defaults[name];
	},

	/* Parse existing date and initialise date picker. */
	_setDateFromField: function(inst, noDefault) {
		if (inst.input.val() === inst.lastVal) {
			return;
		}

		var dateFormat = this._get(inst, "dateFormat"),
			dates = inst.lastVal = inst.input ? inst.input.val() : null,
			defaultDate = this._getDefaultDate(inst),
			date = defaultDate,
			settings = this._getFormatConfig(inst);

		try {
			date = this.parseDate(dateFormat, dates, settings) || defaultDate;
		} catch (event) {
			dates = (noDefault ? "" : dates);
		}
		inst.selectedDay = date.getDate();
		inst.drawMonth = inst.selectedMonth = date.getMonth();
		inst.drawYear = inst.selectedYear = date.getFullYear();
		inst.currentDay = (dates ? date.getDate() : 0);
		inst.currentMonth = (dates ? date.getMonth() : 0);
		inst.currentYear = (dates ? date.getFullYear() : 0);
		this._adjustInstDate(inst);
	},

	/* Retrieve the default date shown on opening. */
	_getDefaultDate: function(inst) {
		return this._restrictMinMax(inst,
			this._determineDate(inst, this._get(inst, "defaultDate"), new Date()));
	},

	/* A date may be specified as an exact value or a relative one. */
	_determineDate: function(inst, date, defaultDate) {
		var offsetNumeric = function(offset) {
				var date = new Date();
				date.setDate(date.getDate() + offset);
				return date;
			},
			offsetString = function(offset) {
				try {
					return $.datepicker.parseDate($.datepicker._get(inst, "dateFormat"),
						offset, $.datepicker._getFormatConfig(inst));
				}
				catch (e) {
					// Ignore
				}

				var date = (offset.toLowerCase().match(/^c/) ?
					$.datepicker._getDate(inst) : null) || new Date(),
					year = date.getFullYear(),
					month = date.getMonth(),
					day = date.getDate(),
					pattern = /([+\-]?[0-9]+)\s*(d|D|w|W|m|M|y|Y)?/g,
					matches = pattern.exec(offset);

				while (matches) {
					switch (matches[2] || "d") {
						case "d" : case "D" :
							day += parseInt(matches[1],10); break;
						case "w" : case "W" :
							day += parseInt(matches[1],10) * 7; break;
						case "m" : case "M" :
							month += parseInt(matches[1],10);
							day = Math.min(day, $.datepicker._getDaysInMonth(year, month));
							break;
						case "y": case "Y" :
							year += parseInt(matches[1],10);
							day = Math.min(day, $.datepicker._getDaysInMonth(year, month));
							break;
					}
					matches = pattern.exec(offset);
				}
				return new Date(year, month, day);
			},
			newDate = (date == null || date === "" ? defaultDate : (typeof date === "string" ? offsetString(date) :
				(typeof date === "number" ? (isNaN(date) ? defaultDate : offsetNumeric(date)) : new Date(date.getTime()))));

		newDate = (newDate && newDate.toString() === "Invalid Date" ? defaultDate : newDate);
		if (newDate) {
			newDate.setHours(0);
			newDate.setMinutes(0);
			newDate.setSeconds(0);
			newDate.setMilliseconds(0);
		}
		return this._daylightSavingAdjust(newDate);
	},

	/* Handle switch to/from daylight saving.
	 * Hours may be non-zero on daylight saving cut-over:
	 * > 12 when midnight changeover, but then cannot generate
	 * midnight datetime, so jump to 1AM, otherwise reset.
	 * @param  date  (Date) the date to check
	 * @return  (Date) the corrected date
	 */
	_daylightSavingAdjust: function(date) {
		if (!date) {
			return null;
		}
		date.setHours(date.getHours() > 12 ? date.getHours() + 2 : 0);
		return date;
	},

	/* Set the date(s) directly. */
	_setDate: function(inst, date, noChange) {
		var clear = !date,
			origMonth = inst.selectedMonth,
			origYear = inst.selectedYear,
			newDate = this._restrictMinMax(inst, this._determineDate(inst, date, new Date()));

		inst.selectedDay = inst.currentDay = newDate.getDate();
		inst.drawMonth = inst.selectedMonth = inst.currentMonth = newDate.getMonth();
		inst.drawYear = inst.selectedYear = inst.currentYear = newDate.getFullYear();
		if ((origMonth !== inst.selectedMonth || origYear !== inst.selectedYear) && !noChange) {
			this._notifyChange(inst);
		}
		this._adjustInstDate(inst);
		if (inst.input) {
			inst.input.val(clear ? "" : this._formatDate(inst));
		}
	},

	/* Retrieve the date(s) directly. */
	_getDate: function(inst) {
		var startDate = (!inst.currentYear || (inst.input && inst.input.val() === "") ? null :
			this._daylightSavingAdjust(new Date(
			inst.currentYear, inst.currentMonth, inst.currentDay)));
			return startDate;
	},

	/* Attach the onxxx handlers.  These are declared statically so
	 * they work with static code transformers like Caja.
	 */
	_attachHandlers: function(inst) {
		var stepMonths = this._get(inst, "stepMonths"),
			id = "#" + inst.id.replace( /\\\\/g, "\\" );
		inst.dpDiv.find("[data-handler]").map(function () {
			var handler = {
				prev: function () {
					$.datepicker._adjustDate(id, -stepMonths, "M");
				},
				next: function () {
					$.datepicker._adjustDate(id, +stepMonths, "M");
				},
				hide: function () {
					$.datepicker._hideDatepicker();
				},
				today: function () {
					$.datepicker._gotoToday(id);
				},
				selectDay: function () {
					$.datepicker._selectDay(id, +this.getAttribute("data-month"), +this.getAttribute("data-year"), this);
					return false;
				},
				selectMonth: function () {
					$.datepicker._selectMonthYear(id, this, "M");
					return false;
				},
				selectYear: function () {
					$.datepicker._selectMonthYear(id, this, "Y");
					return false;
				}
			};
			$(this).bind(this.getAttribute("data-event"), handler[this.getAttribute("data-handler")]);
		});
	},

	/* Generate the HTML for the current state of the date picker. */
	_generateHTML: function(inst) {
		var maxDraw, prevText, prev, nextText, next, currentText, gotoDate,
			controls, buttonPanel, firstDay, showWeek, dayNames, dayNamesMin,
			monthNames, monthNamesShort, beforeShowDay, showOtherMonths,
			selectOtherMonths, defaultDate, html, dow, row, group, col, selectedDate,
			cornerClass, calender, thead, day, daysInMonth, leadDays, curRows, numRows,
			printDate, dRow, tbody, daySettings, otherMonth, unselectable,
			tempDate = new Date(),
			today = this._daylightSavingAdjust(
				new Date(tempDate.getFullYear(), tempDate.getMonth(), tempDate.getDate())), // clear time
			isRTL = this._get(inst, "isRTL"),
			showButtonPanel = this._get(inst, "showButtonPanel"),
			hideIfNoPrevNext = this._get(inst, "hideIfNoPrevNext"),
			navigationAsDateFormat = this._get(inst, "navigationAsDateFormat"),
			numMonths = this._getNumberOfMonths(inst),
			showCurrentAtPos = this._get(inst, "showCurrentAtPos"),
			stepMonths = this._get(inst, "stepMonths"),
			isMultiMonth = (numMonths[0] !== 1 || numMonths[1] !== 1),
			currentDate = this._daylightSavingAdjust((!inst.currentDay ? new Date(9999, 9, 9) :
				new Date(inst.currentYear, inst.currentMonth, inst.currentDay))),
			minDate = this._getMinMaxDate(inst, "min"),
			maxDate = this._getMinMaxDate(inst, "max"),
			drawMonth = inst.drawMonth - showCurrentAtPos,
			drawYear = inst.drawYear;

		if (drawMonth < 0) {
			drawMonth += 12;
			drawYear--;
		}
		if (maxDate) {
			maxDraw = this._daylightSavingAdjust(new Date(maxDate.getFullYear(),
				maxDate.getMonth() - (numMonths[0] * numMonths[1]) + 1, maxDate.getDate()));
			maxDraw = (minDate && maxDraw < minDate ? minDate : maxDraw);
			while (this._daylightSavingAdjust(new Date(drawYear, drawMonth, 1)) > maxDraw) {
				drawMonth--;
				if (drawMonth < 0) {
					drawMonth = 11;
					drawYear--;
				}
			}
		}
		inst.drawMonth = drawMonth;
		inst.drawYear = drawYear;

		prevText = this._get(inst, "prevText");
		prevText = (!navigationAsDateFormat ? prevText : this.formatDate(prevText,
			this._daylightSavingAdjust(new Date(drawYear, drawMonth - stepMonths, 1)),
			this._getFormatConfig(inst)));

		prev = (this._canAdjustMonth(inst, -1, drawYear, drawMonth) ?
			"<a class='ui-datepicker-prev ui-corner-all' data-handler='prev' data-event='click'" +
			" title='" + prevText + "'><span class='ui-icon ui-icon-circle-triangle-" + ( isRTL ? "e" : "w") + "'>" + prevText + "</span></a>" :
			(hideIfNoPrevNext ? "" : "<a class='ui-datepicker-prev ui-corner-all ui-state-disabled' title='"+ prevText +"'><span class='ui-icon ui-icon-circle-triangle-" + ( isRTL ? "e" : "w") + "'>" + prevText + "</span></a>"));

		nextText = this._get(inst, "nextText");
		nextText = (!navigationAsDateFormat ? nextText : this.formatDate(nextText,
			this._daylightSavingAdjust(new Date(drawYear, drawMonth + stepMonths, 1)),
			this._getFormatConfig(inst)));

		next = (this._canAdjustMonth(inst, +1, drawYear, drawMonth) ?
			"<a class='ui-datepicker-next ui-corner-all' data-handler='next' data-event='click'" +
			" title='" + nextText + "'><span class='ui-icon ui-icon-circle-triangle-" + ( isRTL ? "w" : "e") + "'>" + nextText + "</span></a>" :
			(hideIfNoPrevNext ? "" : "<a class='ui-datepicker-next ui-corner-all ui-state-disabled' title='"+ nextText + "'><span class='ui-icon ui-icon-circle-triangle-" + ( isRTL ? "w" : "e") + "'>" + nextText + "</span></a>"));

		currentText = this._get(inst, "currentText");
		gotoDate = (this._get(inst, "gotoCurrent") && inst.currentDay ? currentDate : today);
		currentText = (!navigationAsDateFormat ? currentText :
			this.formatDate(currentText, gotoDate, this._getFormatConfig(inst)));

		controls = (!inst.inline ? "<button type='button' class='ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all' data-handler='hide' data-event='click'>" +
			this._get(inst, "closeText") + "</button>" : "");

		buttonPanel = (showButtonPanel) ? "<div class='ui-datepicker-buttonpane ui-widget-content'>" + (isRTL ? controls : "") +
			(this._isInRange(inst, gotoDate) ? "<button type='button' class='ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all' data-handler='today' data-event='click'" +
			">" + currentText + "</button>" : "") + (isRTL ? "" : controls) + "</div>" : "";

		firstDay = parseInt(this._get(inst, "firstDay"),10);
		firstDay = (isNaN(firstDay) ? 0 : firstDay);

		showWeek = this._get(inst, "showWeek");
		dayNames = this._get(inst, "dayNames");
		dayNamesMin = this._get(inst, "dayNamesMin");
		monthNames = this._get(inst, "monthNames");
		monthNamesShort = this._get(inst, "monthNamesShort");
		beforeShowDay = this._get(inst, "beforeShowDay");
		showOtherMonths = this._get(inst, "showOtherMonths");
		selectOtherMonths = this._get(inst, "selectOtherMonths");
		defaultDate = this._getDefaultDate(inst);
		html = "";
		dow;
		for (row = 0; row < numMonths[0]; row++) {
			group = "";
			this.maxRows = 4;
			for (col = 0; col < numMonths[1]; col++) {
				selectedDate = this._daylightSavingAdjust(new Date(drawYear, drawMonth, inst.selectedDay));
				cornerClass = " ui-corner-all";
				calender = "";
				if (isMultiMonth) {
					calender += "<div class='ui-datepicker-group";
					if (numMonths[1] > 1) {
						switch (col) {
							case 0: calender += " ui-datepicker-group-first";
								cornerClass = " ui-corner-" + (isRTL ? "right" : "left"); break;
							case numMonths[1]-1: calender += " ui-datepicker-group-last";
								cornerClass = " ui-corner-" + (isRTL ? "left" : "right"); break;
							default: calender += " ui-datepicker-group-middle"; cornerClass = ""; break;
						}
					}
					calender += "'>";
				}
				calender += "<div class='ui-datepicker-header ui-widget-header ui-helper-clearfix" + cornerClass + "'>" +
					(/all|left/.test(cornerClass) && row === 0 ? (isRTL ? next : prev) : "") +
					(/all|right/.test(cornerClass) && row === 0 ? (isRTL ? prev : next) : "") +
					this._generateMonthYearHeader(inst, drawMonth, drawYear, minDate, maxDate,
					row > 0 || col > 0, monthNames, monthNamesShort) + // draw month headers
					"</div><table class='ui-datepicker-calendar'><thead>" +
					"<tr>";
				thead = (showWeek ? "<th class='ui-datepicker-week-col'>" + this._get(inst, "weekHeader") + "</th>" : "");
				for (dow = 0; dow < 7; dow++) { // days of the week
					day = (dow + firstDay) % 7;
					thead += "<th" + ((dow + firstDay + 6) % 7 >= 5 ? " class='ui-datepicker-week-end'" : "") + ">" +
						"<span title='" + dayNames[day] + "'>" + dayNamesMin[day] + "</span></th>";
				}
				calender += thead + "</tr></thead><tbody>";
				daysInMonth = this._getDaysInMonth(drawYear, drawMonth);
				if (drawYear === inst.selectedYear && drawMonth === inst.selectedMonth) {
					inst.selectedDay = Math.min(inst.selectedDay, daysInMonth);
				}
				leadDays = (this._getFirstDayOfMonth(drawYear, drawMonth) - firstDay + 7) % 7;
				curRows = Math.ceil((leadDays + daysInMonth) / 7); // calculate the number of rows to generate
				numRows = (isMultiMonth ? this.maxRows > curRows ? this.maxRows : curRows : curRows); //If multiple months, use the higher number of rows (see #7043)
				this.maxRows = numRows;
				printDate = this._daylightSavingAdjust(new Date(drawYear, drawMonth, 1 - leadDays));
				for (dRow = 0; dRow < numRows; dRow++) { // create date picker rows
					calender += "<tr>";
					tbody = (!showWeek ? "" : "<td class='ui-datepicker-week-col'>" +
						this._get(inst, "calculateWeek")(printDate) + "</td>");
					for (dow = 0; dow < 7; dow++) { // create date picker days
						daySettings = (beforeShowDay ?
							beforeShowDay.apply((inst.input ? inst.input[0] : null), [printDate]) : [true, ""]);
						otherMonth = (printDate.getMonth() !== drawMonth);
						unselectable = (otherMonth && !selectOtherMonths) || !daySettings[0] ||
							(minDate && printDate < minDate) || (maxDate && printDate > maxDate);
						tbody += "<td class='" +
							((dow + firstDay + 6) % 7 >= 5 ? " ui-datepicker-week-end" : "") + // highlight weekends
							(otherMonth ? " ui-datepicker-other-month" : "") + // highlight days from other months
							((printDate.getTime() === selectedDate.getTime() && drawMonth === inst.selectedMonth && inst._keyEvent) || // user pressed key
							(defaultDate.getTime() === printDate.getTime() && defaultDate.getTime() === selectedDate.getTime()) ?
							// or defaultDate is current printedDate and defaultDate is selectedDate
							" " + this._dayOverClass : "") + // highlight selected day
							(unselectable ? " " + this._unselectableClass + " ui-state-disabled": "") +  // highlight unselectable days
							(otherMonth && !showOtherMonths ? "" : " " + daySettings[1] + // highlight custom dates
							(printDate.getTime() === currentDate.getTime() ? " " + this._currentClass : "") + // highlight selected day
							(printDate.getTime() === today.getTime() ? " ui-datepicker-today" : "")) + "'" + // highlight today (if different)
							((!otherMonth || showOtherMonths) && daySettings[2] ? " title='" + daySettings[2].replace(/'/g, "&#39;") + "'" : "") + // cell title
							(unselectable ? "" : " data-handler='selectDay' data-event='click' data-month='" + printDate.getMonth() + "' data-year='" + printDate.getFullYear() + "'") + ">" + // actions
							(otherMonth && !showOtherMonths ? "&#xa0;" : // display for other months
							(unselectable ? "<span class='ui-state-default'>" + printDate.getDate() + "</span>" : "<a class='ui-state-default" +
							(printDate.getTime() === today.getTime() ? " ui-state-highlight" : "") +
							(printDate.getTime() === currentDate.getTime() ? " ui-state-active" : "") + // highlight selected day
							(otherMonth ? " ui-priority-secondary" : "") + // distinguish dates from other months
							"' href='#'>" + printDate.getDate() + "</a>")) + "</td>"; // display selectable date
						printDate.setDate(printDate.getDate() + 1);
						printDate = this._daylightSavingAdjust(printDate);
					}
					calender += tbody + "</tr>";
				}
				drawMonth++;
				if (drawMonth > 11) {
					drawMonth = 0;
					drawYear++;
				}
				calender += "</tbody></table>" + (isMultiMonth ? "</div>" +
							((numMonths[0] > 0 && col === numMonths[1]-1) ? "<div class='ui-datepicker-row-break'></div>" : "") : "");
				group += calender;
			}
			html += group;
		}
		html += buttonPanel;
		inst._keyEvent = false;
		return html;
	},

	/* Generate the month and year header. */
	_generateMonthYearHeader: function(inst, drawMonth, drawYear, minDate, maxDate,
			secondary, monthNames, monthNamesShort) {

		var inMinYear, inMaxYear, month, years, thisYear, determineYear, year, endYear,
			changeMonth = this._get(inst, "changeMonth"),
			changeYear = this._get(inst, "changeYear"),
			showMonthAfterYear = this._get(inst, "showMonthAfterYear"),
			html = "<div class='ui-datepicker-title'>",
			monthHtml = "";

		// month selection
		if (secondary || !changeMonth) {
			monthHtml += "<span class='ui-datepicker-month'>" + monthNames[drawMonth] + "</span>";
		} else {
			inMinYear = (minDate && minDate.getFullYear() === drawYear);
			inMaxYear = (maxDate && maxDate.getFullYear() === drawYear);
			monthHtml += "<select class='ui-datepicker-month' data-handler='selectMonth' data-event='change'>";
			for ( month = 0; month < 12; month++) {
				if ((!inMinYear || month >= minDate.getMonth()) && (!inMaxYear || month <= maxDate.getMonth())) {
					monthHtml += "<option value='" + month + "'" +
						(month === drawMonth ? " selected='selected'" : "") +
						">" + monthNamesShort[month] + "</option>";
				}
			}
			monthHtml += "</select>";
		}

		if (!showMonthAfterYear) {
			html += monthHtml + (secondary || !(changeMonth && changeYear) ? "&#xa0;" : "");
		}

		// year selection
		if ( !inst.yearshtml ) {
			inst.yearshtml = "";
			if (secondary || !changeYear) {
				html += "<span class='ui-datepicker-year'>" + drawYear + "</span>";
			} else {
				// determine range of years to display
				years = this._get(inst, "yearRange").split(":");
				thisYear = new Date().getFullYear();
				determineYear = function(value) {
					var year = (value.match(/c[+\-].*/) ? drawYear + parseInt(value.substring(1), 10) :
						(value.match(/[+\-].*/) ? thisYear + parseInt(value, 10) :
						parseInt(value, 10)));
					return (isNaN(year) ? thisYear : year);
				};
				year = determineYear(years[0]);
				endYear = Math.max(year, determineYear(years[1] || ""));
				year = (minDate ? Math.max(year, minDate.getFullYear()) : year);
				endYear = (maxDate ? Math.min(endYear, maxDate.getFullYear()) : endYear);
				inst.yearshtml += "<select class='ui-datepicker-year' data-handler='selectYear' data-event='change'>";
				for (; year <= endYear; year++) {
					inst.yearshtml += "<option value='" + year + "'" +
						(year === drawYear ? " selected='selected'" : "") +
						">" + year + "</option>";
				}
				inst.yearshtml += "</select>";

				html += inst.yearshtml;
				inst.yearshtml = null;
			}
		}

		html += this._get(inst, "yearSuffix");
		if (showMonthAfterYear) {
			html += (secondary || !(changeMonth && changeYear) ? "&#xa0;" : "") + monthHtml;
		}
		html += "</div>"; // Close datepicker_header
		return html;
	},

	/* Adjust one of the date sub-fields. */
	_adjustInstDate: function(inst, offset, period) {
		var year = inst.drawYear + (period === "Y" ? offset : 0),
			month = inst.drawMonth + (period === "M" ? offset : 0),
			day = Math.min(inst.selectedDay, this._getDaysInMonth(year, month)) + (period === "D" ? offset : 0),
			date = this._restrictMinMax(inst, this._daylightSavingAdjust(new Date(year, month, day)));

		inst.selectedDay = date.getDate();
		inst.drawMonth = inst.selectedMonth = date.getMonth();
		inst.drawYear = inst.selectedYear = date.getFullYear();
		if (period === "M" || period === "Y") {
			this._notifyChange(inst);
		}
	},

	/* Ensure a date is within any min/max bounds. */
	_restrictMinMax: function(inst, date) {
		var minDate = this._getMinMaxDate(inst, "min"),
			maxDate = this._getMinMaxDate(inst, "max"),
			newDate = (minDate && date < minDate ? minDate : date);
		return (maxDate && newDate > maxDate ? maxDate : newDate);
	},

	/* Notify change of month/year. */
	_notifyChange: function(inst) {
		var onChange = this._get(inst, "onChangeMonthYear");
		if (onChange) {
			onChange.apply((inst.input ? inst.input[0] : null),
				[inst.selectedYear, inst.selectedMonth + 1, inst]);
		}
	},

	/* Determine the number of months to show. */
	_getNumberOfMonths: function(inst) {
		var numMonths = this._get(inst, "numberOfMonths");
		return (numMonths == null ? [1, 1] : (typeof numMonths === "number" ? [1, numMonths] : numMonths));
	},

	/* Determine the current maximum date - ensure no time components are set. */
	_getMinMaxDate: function(inst, minMax) {
		return this._determineDate(inst, this._get(inst, minMax + "Date"), null);
	},

	/* Find the number of days in a given month. */
	_getDaysInMonth: function(year, month) {
		return 32 - this._daylightSavingAdjust(new Date(year, month, 32)).getDate();
	},

	/* Find the day of the week of the first of a month. */
	_getFirstDayOfMonth: function(year, month) {
		return new Date(year, month, 1).getDay();
	},

	/* Determines if we should allow a "next/prev" month display change. */
	_canAdjustMonth: function(inst, offset, curYear, curMonth) {
		var numMonths = this._getNumberOfMonths(inst),
			date = this._daylightSavingAdjust(new Date(curYear,
			curMonth + (offset < 0 ? offset : numMonths[0] * numMonths[1]), 1));

		if (offset < 0) {
			date.setDate(this._getDaysInMonth(date.getFullYear(), date.getMonth()));
		}
		return this._isInRange(inst, date);
	},

	/* Is the given date in the accepted range? */
	_isInRange: function(inst, date) {
		var yearSplit, currentYear,
			minDate = this._getMinMaxDate(inst, "min"),
			maxDate = this._getMinMaxDate(inst, "max"),
			minYear = null,
			maxYear = null,
			years = this._get(inst, "yearRange");
			if (years){
				yearSplit = years.split(":");
				currentYear = new Date().getFullYear();
				minYear = parseInt(yearSplit[0], 10);
				maxYear = parseInt(yearSplit[1], 10);
				if ( yearSplit[0].match(/[+\-].*/) ) {
					minYear += currentYear;
				}
				if ( yearSplit[1].match(/[+\-].*/) ) {
					maxYear += currentYear;
				}
			}

		return ((!minDate || date.getTime() >= minDate.getTime()) &&
			(!maxDate || date.getTime() <= maxDate.getTime()) &&
			(!minYear || date.getFullYear() >= minYear) &&
			(!maxYear || date.getFullYear() <= maxYear));
	},

	/* Provide the configuration settings for formatting/parsing. */
	_getFormatConfig: function(inst) {
		var shortYearCutoff = this._get(inst, "shortYearCutoff");
		shortYearCutoff = (typeof shortYearCutoff !== "string" ? shortYearCutoff :
			new Date().getFullYear() % 100 + parseInt(shortYearCutoff, 10));
		return {shortYearCutoff: shortYearCutoff,
			dayNamesShort: this._get(inst, "dayNamesShort"), dayNames: this._get(inst, "dayNames"),
			monthNamesShort: this._get(inst, "monthNamesShort"), monthNames: this._get(inst, "monthNames")};
	},

	/* Format the given date for display. */
	_formatDate: function(inst, day, month, year) {
		if (!day) {
			inst.currentDay = inst.selectedDay;
			inst.currentMonth = inst.selectedMonth;
			inst.currentYear = inst.selectedYear;
		}
		var date = (day ? (typeof day === "object" ? day :
			this._daylightSavingAdjust(new Date(year, month, day))) :
			this._daylightSavingAdjust(new Date(inst.currentYear, inst.currentMonth, inst.currentDay)));
		return this.formatDate(this._get(inst, "dateFormat"), date, this._getFormatConfig(inst));
	}
});

/*
 * Bind hover events for datepicker elements.
 * Done via delegate so the binding only occurs once in the lifetime of the parent div.
 * Global instActive, set by _updateDatepicker allows the handlers to find their way back to the active picker.
 */
function bindHover(dpDiv) {
	var selector = "button, .ui-datepicker-prev, .ui-datepicker-next, .ui-datepicker-calendar td a";
	return dpDiv.delegate(selector, "mouseout", function() {
			$(this).removeClass("ui-state-hover");
			if (this.className.indexOf("ui-datepicker-prev") !== -1) {
				$(this).removeClass("ui-datepicker-prev-hover");
			}
			if (this.className.indexOf("ui-datepicker-next") !== -1) {
				$(this).removeClass("ui-datepicker-next-hover");
			}
		})
		.delegate(selector, "mouseover", function(){
			if (!$.datepicker._isDisabledDatepicker( instActive.inline ? dpDiv.parent()[0] : instActive.input[0])) {
				$(this).parents(".ui-datepicker-calendar").find("a").removeClass("ui-state-hover");
				$(this).addClass("ui-state-hover");
				if (this.className.indexOf("ui-datepicker-prev") !== -1) {
					$(this).addClass("ui-datepicker-prev-hover");
				}
				if (this.className.indexOf("ui-datepicker-next") !== -1) {
					$(this).addClass("ui-datepicker-next-hover");
				}
			}
		});
}

/* jQuery extend now ignores nulls! */
function extendRemove(target, props) {
	$.extend(target, props);
	for (var name in props) {
		if (props[name] == null) {
			target[name] = props[name];
		}
	}
	return target;
}

/* Invoke the datepicker functionality.
   @param  options  string - a command, optionally followed by additional parameters or
					Object - settings for attaching new datepicker functionality
   @return  jQuery object */
$.fn.datepicker = function(options){

	/* Verify an empty collection wasn't passed - Fixes #6976 */
	if ( !this.length ) {
		return this;
	}

	/* Initialise the date picker. */
	if (!$.datepicker.initialized) {
		$(document).mousedown($.datepicker._checkExternalClick);
		$.datepicker.initialized = true;
	}

	/* Append datepicker main container to body if not exist. */
	if ($("#"+$.datepicker._mainDivId).length === 0) {
		$("body").append($.datepicker.dpDiv);
	}

	var otherArgs = Array.prototype.slice.call(arguments, 1);
	if (typeof options === "string" && (options === "isDisabled" || options === "getDate" || options === "widget")) {
		return $.datepicker["_" + options + "Datepicker"].
			apply($.datepicker, [this[0]].concat(otherArgs));
	}
	if (options === "option" && arguments.length === 2 && typeof arguments[1] === "string") {
		return $.datepicker["_" + options + "Datepicker"].
			apply($.datepicker, [this[0]].concat(otherArgs));
	}
	return this.each(function() {
		typeof options === "string" ?
			$.datepicker["_" + options + "Datepicker"].
				apply($.datepicker, [this].concat(otherArgs)) :
			$.datepicker._attachDatepicker(this, options);
	});
};

$.datepicker = new Datepicker(); // singleton instance
$.datepicker.initialized = false;
$.datepicker.uuid = new Date().getTime();
$.datepicker.version = "1.10.3";

})(jQuery);
;/*!
 * artTemplate - Template Engine
 * https://github.com/aui/artTemplate
 * Released under the MIT, BSD, and GPL Licenses
 */
 
!(function () {


/**
 * ????????????
 * @name    template
 * @param   {String}            ?????????
 * @param   {Object, String}    ?????????????????????????????????????????????????????????
 * @return  {String, Function}  ????????????HTML???????????????????????????
 */
var template = function (filename, content) {
    return typeof content === 'string'
    ?   compile(content, {
            filename: filename
        })
    :   renderFile(filename, content);
};


template.version = '3.0.0';


/**
 * ??????????????????
 * @name    template.config
 * @param   {String}    ??????
 * @param   {Any}       ???
 */
template.config = function (name, value) {
    defaults[name] = value;
};



var defaults = template.defaults = {
    openTag: '<%',    // ????????????????????????
    closeTag: '%>',   // ????????????????????????
    escape: true,     // ??????????????????????????? HTML ??????
    cache: true,      // ??????????????????????????? options ??? filename ?????????
    compress: false,  // ??????????????????
    parser: null      // ???????????????????????? @see: template-syntax.js
};


var cacheStore = template.cache = {};


/**
 * ????????????
 * @name    template.render
 * @param   {String}    ??????
 * @param   {Object}    ??????
 * @return  {String}    ?????????????????????
 */
template.render = function (source, options) {
    return compile(source, options);
};


/**
 * ????????????(???????????????)
 * @name    template.render
 * @param   {String}    ?????????
 * @param   {Object}    ??????
 * @return  {String}    ?????????????????????
 */
var renderFile = template.renderFile = function (filename, data) {
    var fn = template.get(filename) || showDebugInfo({
        filename: filename,
        name: 'Render Error',
        message: 'Template not found'
    });
    return data ? fn(data) : fn;
};


/**
 * ???????????????????????????????????????????????????
 * @param   {String}    ?????????
 * @param   {Function}  ??????????????????
 */
template.get = function (filename) {

    var cache;
    
    if (cacheStore[filename]) {
        // ??????????????????
        cache = cacheStore[filename];
    } else if (typeof document === 'object') {
        // ?????????????????????
        var elem = document.getElementById(filename);
        
        if (elem) {
            var source = (elem.value || elem.innerHTML)
            .replace(/^\s*|\s*$/g, '');
            cache = compile(source, {
                filename: filename
            });
        }
    }

    return cache;
};


var toString = function (value, type) {

    if (typeof value !== 'string') {

        type = typeof value;
        if (type === 'number') {
            value += '';
        } else if (type === 'function') {
            value = toString(value.call(value));
        } else {
            value = '';
        }
    }

    return value;

};


var escapeMap = {
    "<": "&#60;",
    ">": "&#62;",
    '"': "&#34;",
    "'": "&#39;",
    "&": "&#38;"
};


var escapeFn = function (s) {
    return escapeMap[s];
};

var escapeHTML = function (content) {
    return toString(content)
    .replace(/&(?![\w#]+;)|[<>"']/g, escapeFn);
};


var isArray = Array.isArray || function (obj) {
    return ({}).toString.call(obj) === '[object Array]';
};


var each = function (data, callback) {
    var i, len;        
    if (isArray(data)) {
        for (i = 0, len = data.length; i < len; i++) {
            callback.call(data, data[i], i, data);
        }
    } else {
        for (i in data) {
            callback.call(data, data[i], i);
        }
    }
};


var utils = template.utils = {

	$helpers: {},

    $include: renderFile,

    $string: toString,

    $escape: escapeHTML,

    $each: each
    
};/**
 * ????????????????????????
 * @name    template.helper
 * @param   {String}    ??????
 * @param   {Function}  ??????
 */
template.helper = function (name, helper) {
    helpers[name] = helper;
};

var helpers = template.helpers = utils.$helpers;




/**
 * ???????????????????????????????????????????????????
 * @name    template.onerror
 * @event
 */
template.onerror = function (e) {
    var message = 'Template Error\n\n';
    for (var name in e) {
        message += '<' + name + '>\n' + e[name] + '\n\n';
    }
    
    if (typeof console === 'object') {
        console.error(message);
    }
};


// ???????????????
var showDebugInfo = function (e) {

    template.onerror(e);
    
    return function () {
        return '{Template Error}';
    };
};


/**
 * ????????????
 * 2012-6-6 @TooBug: define ??????????????? compile?????? Node Express ????????????
 * @name    template.compile
 * @param   {String}    ???????????????
 * @param   {Object}    ????????????
 *
 *      - openTag       {String}
 *      - closeTag      {String}
 *      - filename      {String}
 *      - escape        {Boolean}
 *      - compress      {Boolean}
 *      - debug         {Boolean}
 *      - cache         {Boolean}
 *      - parser        {Function}
 *
 * @return  {Function}  ????????????
 */
var compile = template.compile = function (source, options) {
    
    // ??????????????????
    options = options || {};
    for (var name in defaults) {
        if (options[name] === undefined) {
            options[name] = defaults[name];
        }
    }


    var filename = options.filename;


    try {
        
        var Render = compiler(source, options);
        
    } catch (e) {
    
        e.filename = filename || 'anonymous';
        e.name = 'Syntax Error';

        return showDebugInfo(e);
        
    }
    
    
    // ?????????????????????????????????

    function render (data) {
        
        try {
            
            return new Render(data, filename) + '';
            
        } catch (e) {
            
            // ??????????????????????????????????????????????????????
            if (!options.debug) {
                options.debug = true;
                return compile(source, options)(data);
            }
            
            return showDebugInfo(e)();
            
        }
        
    }
    

    render.prototype = Render.prototype;
    render.toString = function () {
        return Render.toString();
    };


    if (filename && options.cache) {
        cacheStore[filename] = render;
    }

    
    return render;

};




// ????????????
var forEach = utils.$each;


// ????????????????????????
var KEYWORDS =
    // ?????????
    'break,case,catch,continue,debugger,default,delete,do,else,false'
    + ',finally,for,function,if,in,instanceof,new,null,return,switch,this'
    + ',throw,true,try,typeof,var,void,while,with'

    // ?????????
    + ',abstract,boolean,byte,char,class,const,double,enum,export,extends'
    + ',final,float,goto,implements,import,int,interface,long,native'
    + ',package,private,protected,public,short,static,super,synchronized'
    + ',throws,transient,volatile'

    // ECMA 5 - use strict
    + ',arguments,let,yield'

    + ',undefined';

var REMOVE_RE = /\/\*[\w\W]*?\*\/|\/\/[^\n]*\n|\/\/[^\n]*$|"(?:[^"\\]|\\[\w\W])*"|'(?:[^'\\]|\\[\w\W])*'|\s*\.\s*[$\w\.]+/g;
var SPLIT_RE = /[^\w$]+/g;
var KEYWORDS_RE = new RegExp(["\\b" + KEYWORDS.replace(/,/g, '\\b|\\b') + "\\b"].join('|'), 'g');
var NUMBER_RE = /^\d[^,]*|,\d[^,]*/g;
var BOUNDARY_RE = /^,+|,+$/g;
var SPLIT2_RE = /^$|,+/;


// ????????????
function getVariable (code) {
    return code
    .replace(REMOVE_RE, '')
    .replace(SPLIT_RE, ',')
    .replace(KEYWORDS_RE, '')
    .replace(NUMBER_RE, '')
    .replace(BOUNDARY_RE, '')
    .split(SPLIT2_RE);
};


// ???????????????
function stringify (code) {
    return "'" + code
    // ???????????????????????????
    .replace(/('|\\)/g, '\\$1')
    // ???????????????(windows + linux)
    .replace(/\r/g, '\\r')
    .replace(/\n/g, '\\n') + "'";
}


function compiler (source, options) {
    
    var debug = options.debug;
    var openTag = options.openTag;
    var closeTag = options.closeTag;
    var parser = options.parser;
    var compress = options.compress;
    var escape = options.escape;
    

    
    var line = 1;
    var uniq = {$data:1,$filename:1,$utils:1,$helpers:1,$out:1,$line:1};
    


    var isNewEngine = ''.trim;// '__proto__' in {}
    var replaces = isNewEngine
    ? ["$out='';", "$out+=", ";", "$out"]
    : ["$out=[];", "$out.push(", ");", "$out.join('')"];

    var concat = isNewEngine
        ? "$out+=text;return $out;"
        : "$out.push(text);";
          
    var print = "function(){"
    +      "var text=''.concat.apply('',arguments);"
    +       concat
    +  "}";

    var include = "function(filename,data){"
    +      "data=data||$data;"
    +      "var text=$utils.$include(filename,data,$filename);"
    +       concat
    +   "}";

    var headerCode = "'use strict';"
    + "var $utils=this,$helpers=$utils.$helpers,"
    + (debug ? "$line=0," : "");
    
    var mainCode = replaces[0];

    var footerCode = "return new String(" + replaces[3] + ");"
    
    // html?????????????????????
    forEach(source.split(openTag), function (code) {
        code = code.split(closeTag);
        
        var $0 = code[0];
        var $1 = code[1];
        
        // code: [html]
        if (code.length === 1) {
            
            mainCode += html($0);
         
        // code: [logic, html]
        } else {
            
            mainCode += logic($0);
            
            if ($1) {
                mainCode += html($1);
            }
        }
        

    });
    
    var code = headerCode + mainCode + footerCode;
    
    // ????????????
    if (debug) {
        code = "try{" + code + "}catch(e){"
        +       "throw {"
        +           "filename:$filename,"
        +           "name:'Render Error',"
        +           "message:e.message,"
        +           "line:$line,"
        +           "source:" + stringify(source)
        +           ".split(/\\n/)[$line-1].replace(/^\\s+/,'')"
        +       "};"
        + "}";
    }
    
    
    
    try {
        
        
        var Render = new Function("$data", "$filename", code);
        Render.prototype = utils;

        return Render;
        
    } catch (e) {
        e.temp = "function anonymous($data,$filename) {" + code + "}";
        throw e;
    }



    
    // ?????? HTML ??????
    function html (code) {
        
        // ????????????
        line += code.split(/\n/).length - 1;

        // ???????????????????????????
        if (compress) {
            code = code
            .replace(/\s+/g, ' ')
            .replace(/<!--[\w\W]*?-->/g, '');
        }
        
        if (code) {
            code = replaces[1] + stringify(code) + replaces[2] + "\n";
        }

        return code;
    }
    
    
    // ??????????????????
    function logic (code) {

        var thisLine = line;
       
        if (parser) {
        
             // ????????????????????????
            code = parser(code, options);
            
        } else if (debug) {
        
            // ????????????
            code = code.replace(/\n/g, function () {
                line ++;
                return "$line=" + line +  ";";
            });
            
        }
        
        
        // ????????????. ??????: <%=value%> ?????????:<%=#value%>
        // <%=#value%> ?????? v2.0.3 ????????? <%==value%>
        if (code.indexOf('=') === 0) {

            var escapeSyntax = escape && !/^=[=#]/.test(code);

            code = code.replace(/^=[=#]?|[\s;]*$/g, '');

            // ???????????????
            if (escapeSyntax) {

                var name = code.replace(/\s*\([^\)]+\)/, '');

                // ?????? utils.* | include | print
                
                if (!utils[name] && !/^(include|print)$/.test(name)) {
                    code = "$escape(" + code + ")";
                }

            // ?????????
            } else {
                code = "$string(" + code + ")";
            }
            

            code = replaces[1] + code + replaces[2];

        }
        
        if (debug) {
            code = "$line=" + thisLine + ";" + code;
        }
        
        // ???????????????????????????
        forEach(getVariable(code), function (name) {
            
            // name ????????????????????????????????????????????????
            if (!name || uniq[name]) {
                return;
            }

            var value;

            // ??????????????????
            // ???????????????:
            // [include, print] > utils > helpers > data
            if (name === 'print') {

                value = print;

            } else if (name === 'include') {
                
                value = include;
                
            } else if (utils[name]) {

                value = "$utils." + name;

            } else if (helpers[name]) {

                value = "$helpers." + name;

            } else {

                value = "$data." + name;
            }
            
            headerCode += name + "=" + value + ",";
            uniq[name] = true;
            
            
        });
        
        return code + "\n";
    }
    
    
};




// RequireJS && SeaJS
if (typeof define === 'function') {
    define(function() {
        return template;
    });

// NodeJS
} else if (typeof exports !== 'undefined') {
    module.exports = template;
} else {
    this.template = template;
}

})();;/*!
 * jQuery UI Position 1.10.3
 * http://jqueryui.com
 *
 * Copyright 2013 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/position/
 */
(function( $, undefined ) {

$.ui = $.ui || {};

var cachedScrollbarWidth,
	max = Math.max,
	abs = Math.abs,
	round = Math.round,
	rhorizontal = /left|center|right/,
	rvertical = /top|center|bottom/,
	roffset = /[\+\-]\d+(\.[\d]+)?%?/,
	rposition = /^\w+/,
	rpercent = /%$/,
	_position = $.fn.position;

function getOffsets( offsets, width, height ) {
	return [
		parseFloat( offsets[ 0 ] ) * ( rpercent.test( offsets[ 0 ] ) ? width / 100 : 1 ),
		parseFloat( offsets[ 1 ] ) * ( rpercent.test( offsets[ 1 ] ) ? height / 100 : 1 )
	];
}

function parseCss( element, property ) {
	return parseInt( $.css( element, property ), 10 ) || 0;
}

function getDimensions( elem ) {
	var raw = elem[0];
	if ( raw.nodeType === 9 ) {
		return {
			width: elem.width(),
			height: elem.height(),
			offset: { top: 0, left: 0 }
		};
	}
	if ( $.isWindow( raw ) ) {
		return {
			width: elem.width(),
			height: elem.height(),
			offset: { top: elem.scrollTop(), left: elem.scrollLeft() }
		};
	}
	if ( raw.preventDefault ) {
		return {
			width: 0,
			height: 0,
			offset: { top: raw.pageY, left: raw.pageX }
		};
	}
	return {
		width: elem.outerWidth(),
		height: elem.outerHeight(),
		offset: elem.offset()
	};
}

$.position = {
	scrollbarWidth: function() {
		if ( cachedScrollbarWidth !== undefined ) {
			return cachedScrollbarWidth;
		}
		var w1, w2,
			div = $( "<div style='display:block;width:50px;height:50px;overflow:hidden;'><div style='height:100px;width:auto;'></div></div>" ),
			innerDiv = div.children()[0];

		$( "body" ).append( div );
		w1 = innerDiv.offsetWidth;
		div.css( "overflow", "scroll" );

		w2 = innerDiv.offsetWidth;

		if ( w1 === w2 ) {
			w2 = div[0].clientWidth;
		}

		div.remove();

		return (cachedScrollbarWidth = w1 - w2);
	},
	getScrollInfo: function( within ) {
		var overflowX = within.isWindow ? "" : within.element.css( "overflow-x" ),
			overflowY = within.isWindow ? "" : within.element.css( "overflow-y" ),
			hasOverflowX = overflowX === "scroll" ||
				( overflowX === "auto" && within.width < within.element[0].scrollWidth ),
			hasOverflowY = overflowY === "scroll" ||
				( overflowY === "auto" && within.height < within.element[0].scrollHeight );
		return {
			width: hasOverflowY ? $.position.scrollbarWidth() : 0,
			height: hasOverflowX ? $.position.scrollbarWidth() : 0
		};
	},
	getWithinInfo: function( element ) {
		var withinElement = $( element || window ),
			isWindow = $.isWindow( withinElement[0] );
		return {
			element: withinElement,
			isWindow: isWindow,
			offset: withinElement.offset() || { left: 0, top: 0 },
			scrollLeft: withinElement.scrollLeft(),
			scrollTop: withinElement.scrollTop(),
			width: isWindow ? withinElement.width() : withinElement.outerWidth(),
			height: isWindow ? withinElement.height() : withinElement.outerHeight()
		};
	}
};

$.fn.position = function( options ) {
	if ( !options || !options.of ) {
		return _position.apply( this, arguments );
	}

	// make a copy, we don't want to modify arguments
	options = $.extend( {}, options );

	var atOffset, targetWidth, targetHeight, targetOffset, basePosition, dimensions,
		target = $( options.of ),
		within = $.position.getWithinInfo( options.within ),
		scrollInfo = $.position.getScrollInfo( within ),
		collision = ( options.collision || "flip" ).split( " " ),
		offsets = {};

	dimensions = getDimensions( target );
	if ( target[0].preventDefault ) {
		// force left top to allow flipping
		options.at = "left top";
	}
	targetWidth = dimensions.width;
	targetHeight = dimensions.height;
	targetOffset = dimensions.offset;
	// clone to reuse original targetOffset later
	basePosition = $.extend( {}, targetOffset );

	// force my and at to have valid horizontal and vertical positions
	// if a value is missing or invalid, it will be converted to center
	$.each( [ "my", "at" ], function() {
		var pos = ( options[ this ] || "" ).split( " " ),
			horizontalOffset,
			verticalOffset;

		if ( pos.length === 1) {
			pos = rhorizontal.test( pos[ 0 ] ) ?
				pos.concat( [ "center" ] ) :
				rvertical.test( pos[ 0 ] ) ?
					[ "center" ].concat( pos ) :
					[ "center", "center" ];
		}
		pos[ 0 ] = rhorizontal.test( pos[ 0 ] ) ? pos[ 0 ] : "center";
		pos[ 1 ] = rvertical.test( pos[ 1 ] ) ? pos[ 1 ] : "center";

		// calculate offsets
		horizontalOffset = roffset.exec( pos[ 0 ] );
		verticalOffset = roffset.exec( pos[ 1 ] );
		offsets[ this ] = [
			horizontalOffset ? horizontalOffset[ 0 ] : 0,
			verticalOffset ? verticalOffset[ 0 ] : 0
		];

		// reduce to just the positions without the offsets
		options[ this ] = [
			rposition.exec( pos[ 0 ] )[ 0 ],
			rposition.exec( pos[ 1 ] )[ 0 ]
		];
	});

	// normalize collision option
	if ( collision.length === 1 ) {
		collision[ 1 ] = collision[ 0 ];
	}

	if ( options.at[ 0 ] === "right" ) {
		basePosition.left += targetWidth;
	} else if ( options.at[ 0 ] === "center" ) {
		basePosition.left += targetWidth / 2;
	}

	if ( options.at[ 1 ] === "bottom" ) {
		basePosition.top += targetHeight;
	} else if ( options.at[ 1 ] === "center" ) {
		basePosition.top += targetHeight / 2;
	}

	atOffset = getOffsets( offsets.at, targetWidth, targetHeight );
	basePosition.left += atOffset[ 0 ];
	basePosition.top += atOffset[ 1 ];

	return this.each(function() {
		var collisionPosition, using,
			elem = $( this ),
			elemWidth = elem.outerWidth(),
			elemHeight = elem.outerHeight(),
			marginLeft = parseCss( this, "marginLeft" ),
			marginTop = parseCss( this, "marginTop" ),
			collisionWidth = elemWidth + marginLeft + parseCss( this, "marginRight" ) + scrollInfo.width,
			collisionHeight = elemHeight + marginTop + parseCss( this, "marginBottom" ) + scrollInfo.height,
			position = $.extend( {}, basePosition ),
			myOffset = getOffsets( offsets.my, elem.outerWidth(), elem.outerHeight() );

		if ( options.my[ 0 ] === "right" ) {
			position.left -= elemWidth;
		} else if ( options.my[ 0 ] === "center" ) {
			position.left -= elemWidth / 2;
		}

		if ( options.my[ 1 ] === "bottom" ) {
			position.top -= elemHeight;
		} else if ( options.my[ 1 ] === "center" ) {
			position.top -= elemHeight / 2;
		}

		position.left += myOffset[ 0 ];
		position.top += myOffset[ 1 ];

		// if the browser doesn't support fractions, then round for consistent results
		if ( !$.support.offsetFractions ) {
			position.left = round( position.left );
			position.top = round( position.top );
		}

		collisionPosition = {
			marginLeft: marginLeft,
			marginTop: marginTop
		};

		$.each( [ "left", "top" ], function( i, dir ) {
			if ( $.ui.position[ collision[ i ] ] ) {
				$.ui.position[ collision[ i ] ][ dir ]( position, {
					targetWidth: targetWidth,
					targetHeight: targetHeight,
					elemWidth: elemWidth,
					elemHeight: elemHeight,
					collisionPosition: collisionPosition,
					collisionWidth: collisionWidth,
					collisionHeight: collisionHeight,
					offset: [ atOffset[ 0 ] + myOffset[ 0 ], atOffset [ 1 ] + myOffset[ 1 ] ],
					my: options.my,
					at: options.at,
					within: within,
					elem : elem
				});
			}
		});

		if ( options.using ) {
			// adds feedback as second argument to using callback, if present
			using = function( props ) {
				var left = targetOffset.left - position.left,
					right = left + targetWidth - elemWidth,
					top = targetOffset.top - position.top,
					bottom = top + targetHeight - elemHeight,
					feedback = {
						target: {
							element: target,
							left: targetOffset.left,
							top: targetOffset.top,
							width: targetWidth,
							height: targetHeight
						},
						element: {
							element: elem,
							left: position.left,
							top: position.top,
							width: elemWidth,
							height: elemHeight
						},
						horizontal: right < 0 ? "left" : left > 0 ? "right" : "center",
						vertical: bottom < 0 ? "top" : top > 0 ? "bottom" : "middle"
					};
				if ( targetWidth < elemWidth && abs( left + right ) < targetWidth ) {
					feedback.horizontal = "center";
				}
				if ( targetHeight < elemHeight && abs( top + bottom ) < targetHeight ) {
					feedback.vertical = "middle";
				}
				if ( max( abs( left ), abs( right ) ) > max( abs( top ), abs( bottom ) ) ) {
					feedback.important = "horizontal";
				} else {
					feedback.important = "vertical";
				}
				options.using.call( this, props, feedback );
			};
		}

		elem.offset( $.extend( position, { using: using } ) );
	});
};

$.ui.position = {
	fit: {
		left: function( position, data ) {
			var within = data.within,
				withinOffset = within.isWindow ? within.scrollLeft : within.offset.left,
				outerWidth = within.width,
				collisionPosLeft = position.left - data.collisionPosition.marginLeft,
				overLeft = withinOffset - collisionPosLeft,
				overRight = collisionPosLeft + data.collisionWidth - outerWidth - withinOffset,
				newOverRight;

			// element is wider than within
			if ( data.collisionWidth > outerWidth ) {
				// element is initially over the left side of within
				if ( overLeft > 0 && overRight <= 0 ) {
					newOverRight = position.left + overLeft + data.collisionWidth - outerWidth - withinOffset;
					position.left += overLeft - newOverRight;
				// element is initially over right side of within
				} else if ( overRight > 0 && overLeft <= 0 ) {
					position.left = withinOffset;
				// element is initially over both left and right sides of within
				} else {
					if ( overLeft > overRight ) {
						position.left = withinOffset + outerWidth - data.collisionWidth;
					} else {
						position.left = withinOffset;
					}
				}
			// too far left -> align with left edge
			} else if ( overLeft > 0 ) {
				position.left += overLeft;
			// too far right -> align with right edge
			} else if ( overRight > 0 ) {
				position.left -= overRight;
			// adjust based on position and margin
			} else {
				position.left = max( position.left - collisionPosLeft, position.left );
			}
		},
		top: function( position, data ) {
			var within = data.within,
				withinOffset = within.isWindow ? within.scrollTop : within.offset.top,
				outerHeight = data.within.height,
				collisionPosTop = position.top - data.collisionPosition.marginTop,
				overTop = withinOffset - collisionPosTop,
				overBottom = collisionPosTop + data.collisionHeight - outerHeight - withinOffset,
				newOverBottom;

			// element is taller than within
			if ( data.collisionHeight > outerHeight ) {
				// element is initially over the top of within
				if ( overTop > 0 && overBottom <= 0 ) {
					newOverBottom = position.top + overTop + data.collisionHeight - outerHeight - withinOffset;
					position.top += overTop - newOverBottom;
				// element is initially over bottom of within
				} else if ( overBottom > 0 && overTop <= 0 ) {
					position.top = withinOffset;
				// element is initially over both top and bottom of within
				} else {
					if ( overTop > overBottom ) {
						position.top = withinOffset + outerHeight - data.collisionHeight;
					} else {
						position.top = withinOffset;
					}
				}
			// too far up -> align with top
			} else if ( overTop > 0 ) {
				position.top += overTop;
			// too far down -> align with bottom edge
			} else if ( overBottom > 0 ) {
				position.top -= overBottom;
			// adjust based on position and margin
			} else {
				position.top = max( position.top - collisionPosTop, position.top );
			}
		}
	},
	flip: {
		left: function( position, data ) {
			var within = data.within,
				withinOffset = within.offset.left + within.scrollLeft,
				outerWidth = within.width,
				offsetLeft = within.isWindow ? within.scrollLeft : within.offset.left,
				collisionPosLeft = position.left - data.collisionPosition.marginLeft,
				overLeft = collisionPosLeft - offsetLeft,
				overRight = collisionPosLeft + data.collisionWidth - outerWidth - offsetLeft,
				myOffset = data.my[ 0 ] === "left" ?
					-data.elemWidth :
					data.my[ 0 ] === "right" ?
						data.elemWidth :
						0,
				atOffset = data.at[ 0 ] === "left" ?
					data.targetWidth :
					data.at[ 0 ] === "right" ?
						-data.targetWidth :
						0,
				offset = -2 * data.offset[ 0 ],
				newOverRight,
				newOverLeft;

			if ( overLeft < 0 ) {
				newOverRight = position.left + myOffset + atOffset + offset + data.collisionWidth - outerWidth - withinOffset;
				if ( newOverRight < 0 || newOverRight < abs( overLeft ) ) {
					position.left += myOffset + atOffset + offset;
				}
			}
			else if ( overRight > 0 ) {
				newOverLeft = position.left - data.collisionPosition.marginLeft + myOffset + atOffset + offset - offsetLeft;
				if ( newOverLeft > 0 || abs( newOverLeft ) < overRight ) {
					position.left += myOffset + atOffset + offset;
				}
			}
		},
		top: function( position, data ) {
			var within = data.within,
				withinOffset = within.offset.top + within.scrollTop,
				outerHeight = within.height,
				offsetTop = within.isWindow ? within.scrollTop : within.offset.top,
				collisionPosTop = position.top - data.collisionPosition.marginTop,
				overTop = collisionPosTop - offsetTop,
				overBottom = collisionPosTop + data.collisionHeight - outerHeight - offsetTop,
				top = data.my[ 1 ] === "top",
				myOffset = top ?
					-data.elemHeight :
					data.my[ 1 ] === "bottom" ?
						data.elemHeight :
						0,
				atOffset = data.at[ 1 ] === "top" ?
					data.targetHeight :
					data.at[ 1 ] === "bottom" ?
						-data.targetHeight :
						0,
				offset = -2 * data.offset[ 1 ],
				newOverTop,
				newOverBottom;
			if ( overTop < 0 ) {
				newOverBottom = position.top + myOffset + atOffset + offset + data.collisionHeight - outerHeight - withinOffset;
				if ( ( position.top + myOffset + atOffset + offset) > overTop && ( newOverBottom < 0 || newOverBottom < abs( overTop ) ) ) {
					position.top += myOffset + atOffset + offset;
				}
			}
			else if ( overBottom > 0 ) {
				newOverTop = position.top -  data.collisionPosition.marginTop + myOffset + atOffset + offset - offsetTop;
				if ( ( position.top + myOffset + atOffset + offset) > overBottom && ( newOverTop > 0 || abs( newOverTop ) < overBottom ) ) {
					position.top += myOffset + atOffset + offset;
				}
			}
		}
	},
	flipfit: {
		left: function() {
			$.ui.position.flip.left.apply( this, arguments );
			$.ui.position.fit.left.apply( this, arguments );
		},
		top: function() {
			$.ui.position.flip.top.apply( this, arguments );
			$.ui.position.fit.top.apply( this, arguments );
		}
	}
};

// fraction support test
(function () {
	var testElement, testElementParent, testElementStyle, offsetLeft, i,
		body = document.getElementsByTagName( "body" )[ 0 ],
		div = document.createElement( "div" );

	//Create a "fake body" for testing based on method used in jQuery.support
	testElement = document.createElement( body ? "div" : "body" );
	testElementStyle = {
		visibility: "hidden",
		width: 0,
		height: 0,
		border: 0,
		margin: 0,
		background: "none"
	};
	if ( body ) {
		$.extend( testElementStyle, {
			position: "absolute",
			left: "-1000px",
			top: "-1000px"
		});
	}
	for ( i in testElementStyle ) {
		testElement.style[ i ] = testElementStyle[ i ];
	}
	testElement.appendChild( div );
	testElementParent = body || document.documentElement;
	testElementParent.insertBefore( testElement, testElementParent.firstChild );

	div.style.cssText = "position: absolute; left: 10.7432222px;";

	offsetLeft = $( div ).offset().left;
	$.support.offsetFractions = offsetLeft > 10 && offsetLeft < 11;

	testElement.innerHTML = "";
	testElementParent.removeChild( testElement );
})();

}( jQuery ) );
;/*!
 * jQuery UI Tooltip 1.10.3
 * http://jqueryui.com
 *
 * Copyright 2013 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/tooltip/
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.widget.js
 *	jquery.ui.position.js
 */
(function( $ ) {

var increments = 0;

function addDescribedBy( elem, id ) {
	var describedby = (elem.attr( "aria-describedby" ) || "").split( /\s+/ );
	describedby.push( id );
	elem
		.data( "ui-tooltip-id", id )
		.attr( "aria-describedby", $.trim( describedby.join( " " ) ) );
}

function removeDescribedBy( elem ) {
	var id = elem.data( "ui-tooltip-id" ),
		describedby = (elem.attr( "aria-describedby" ) || "").split( /\s+/ ),
		index = $.inArray( id, describedby );
	if ( index !== -1 ) {
		describedby.splice( index, 1 );
	}

	elem.removeData( "ui-tooltip-id" );
	describedby = $.trim( describedby.join( " " ) );
	if ( describedby ) {
		elem.attr( "aria-describedby", describedby );
	} else {
		elem.removeAttr( "aria-describedby" );
	}
}

$.widget( "ui.tooltip", {
	version: "1.10.3",
	options: {
		content: function() {
			// support: IE<9, Opera in jQuery <1.7
			// .text() can't accept undefined, so coerce to a string
			var title = $( this ).attr( "title" ) || "";
			// Escape title, since we're going from an attribute to raw HTML
			return $( "<a>" ).text( title ).html();
		},
		hide: true,
		// Disabled elements have inconsistent behavior across browsers (#8661)
		items: "[title]:not([disabled])",
		position: {
			my: "left top+15",
			at: "left bottom",
			collision: "flipfit flip"
		},
		show: true,
		tooltipClass: null,
		track: false,

		// callbacks
		close: null,
		open: null
	},

	_create: function() {
		this._on({
			mouseover: "open",
			focusin: "open"
		});

		// IDs of generated tooltips, needed for destroy
		this.tooltips = {};
		// IDs of parent tooltips where we removed the title attribute
		this.parents = {};

		if ( this.options.disabled ) {
			this._disable();
		}
	},

	_setOption: function( key, value ) {
		var that = this;

		if ( key === "disabled" ) {
			this[ value ? "_disable" : "_enable" ]();
			this.options[ key ] = value;
			// disable element style changes
			return;
		}

		this._super( key, value );

		if ( key === "content" ) {
			$.each( this.tooltips, function( id, element ) {
				that._updateContent( element );
			});
		}
	},

	_disable: function() {
		var that = this;

		// close open tooltips
		$.each( this.tooltips, function( id, element ) {
			var event = $.Event( "blur" );
			event.target = event.currentTarget = element[0];
			that.close( event, true );
		});

		// remove title attributes to prevent native tooltips
		this.element.find( this.options.items ).addBack().each(function() {
			var element = $( this );
			if ( element.is( "[title]" ) ) {
				element
					.data( "ui-tooltip-title", element.attr( "title" ) )
					.attr( "title", "" );
			}
		});
	},

	_enable: function() {
		// restore title attributes
		this.element.find( this.options.items ).addBack().each(function() {
			var element = $( this );
			if ( element.data( "ui-tooltip-title" ) ) {
				element.attr( "title", element.data( "ui-tooltip-title" ) );
			}
		});
	},

	open: function( event ) {
		var that = this,
			target = $( event ? event.target : this.element )
				// we need closest here due to mouseover bubbling,
				// but always pointing at the same event target
				.closest( this.options.items );

		// No element to show a tooltip for or the tooltip is already open
		if ( !target.length || target.data( "ui-tooltip-id" ) ) {
			return;
		}

		if ( target.attr( "title" ) ) {
			target.data( "ui-tooltip-title", target.attr( "title" ) );
		}

		target.data( "ui-tooltip-open", true );

		// kill parent tooltips, custom or native, for hover
		if ( event && event.type === "mouseover" ) {
			target.parents().each(function() {
				var parent = $( this ),
					blurEvent;
				if ( parent.data( "ui-tooltip-open" ) ) {
					blurEvent = $.Event( "blur" );
					blurEvent.target = blurEvent.currentTarget = this;
					that.close( blurEvent, true );
				}
				if ( parent.attr( "title" ) ) {
					parent.uniqueId();
					that.parents[ this.id ] = {
						element: this,
						title: parent.attr( "title" )
					};
					parent.attr( "title", "" );
				}
			});
		}

		this._updateContent( target, event );
	},

	_updateContent: function( target, event ) {
		var content,
			contentOption = this.options.content,
			that = this,
			eventType = event ? event.type : null;

		if ( typeof contentOption === "string" ) {
			return this._open( event, target, contentOption );
		}

		content = contentOption.call( target[0], function( response ) {
			// ignore async response if tooltip was closed already
			if ( !target.data( "ui-tooltip-open" ) ) {
				return;
			}
			// IE may instantly serve a cached response for ajax requests
			// delay this call to _open so the other call to _open runs first
			that._delay(function() {
				// jQuery creates a special event for focusin when it doesn't
				// exist natively. To improve performance, the native event
				// object is reused and the type is changed. Therefore, we can't
				// rely on the type being correct after the event finished
				// bubbling, so we set it back to the previous value. (#8740)
				if ( event ) {
					event.type = eventType;
				}
				this._open( event, target, response );
			});
		});
		if ( content ) {
			this._open( event, target, content );
		}
	},

	_open: function( event, target, content ) {
		var tooltip, events, delayedShow,
			positionOption = $.extend( {}, this.options.position );

		if ( !content ) {
			return;
		}

		// Content can be updated multiple times. If the tooltip already
		// exists, then just update the content and bail.
		tooltip = this._find( target );
		if ( tooltip.length ) {
			tooltip.find( ".ui-tooltip-content" ).html( content );
			return;
		}

		// if we have a title, clear it to prevent the native tooltip
		// we have to check first to avoid defining a title if none exists
		// (we don't want to cause an element to start matching [title])
		//
		// We use removeAttr only for key events, to allow IE to export the correct
		// accessible attributes. For mouse events, set to empty string to avoid
		// native tooltip showing up (happens only when removing inside mouseover).
		if ( target.is( "[title]" ) ) {
			if ( event && event.type === "mouseover" ) {
				target.attr( "title", "" );
			} else {
				target.removeAttr( "title" );
			}
		}

		tooltip = this._tooltip( target );
		addDescribedBy( target, tooltip.attr( "id" ) );
		tooltip.find( ".ui-tooltip-content" ).html( content );

		function position( event ) {
			positionOption.of = event;
			if ( tooltip.is( ":hidden" ) ) {
				return;
			}
			tooltip.position( positionOption );
		}
		if ( this.options.track && event && /^mouse/.test( event.type ) ) {
			this._on( this.document, {
				mousemove: position
			});
			// trigger once to override element-relative positioning
			position( event );
		} else {
			tooltip.position( $.extend({
				of: target
			}, this.options.position ) );
		}

		tooltip.hide();

		this._show( tooltip, this.options.show );
		// Handle tracking tooltips that are shown with a delay (#8644). As soon
		// as the tooltip is visible, position the tooltip using the most recent
		// event.
		if ( this.options.show && this.options.show.delay ) {
			delayedShow = this.delayedShow = setInterval(function() {
				if ( tooltip.is( ":visible" ) ) {
					position( positionOption.of );
					clearInterval( delayedShow );
				}
			}, $.fx.interval );
		}

		this._trigger( "open", event, { tooltip: tooltip } );

		events = {
			keyup: function( event ) {
				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					var fakeEvent = $.Event(event);
					fakeEvent.currentTarget = target[0];
					this.close( fakeEvent, true );
				}
			},
			remove: function() {
				this._removeTooltip( tooltip );
			}
		};
		if ( !event || event.type === "mouseover" ) {
			events.mouseleave = "close";
		}
		if ( !event || event.type === "focusin" ) {
			events.focusout = "close";
		}
		this._on( true, target, events );
	},

	close: function( event ) {
		var that = this,
			target = $( event ? event.currentTarget : this.element ),
			tooltip = this._find( target );

		// disabling closes the tooltip, so we need to track when we're closing
		// to avoid an infinite loop in case the tooltip becomes disabled on close
		if ( this.closing ) {
			return;
		}

		// Clear the interval for delayed tracking tooltips
		clearInterval( this.delayedShow );

		// only set title if we had one before (see comment in _open())
		if ( target.data( "ui-tooltip-title" ) ) {
			target.attr( "title", target.data( "ui-tooltip-title" ) );
		}

		removeDescribedBy( target );

		tooltip.stop( true );
		this._hide( tooltip, this.options.hide, function() {
			that._removeTooltip( $( this ) );
		});

		target.removeData( "ui-tooltip-open" );
		this._off( target, "mouseleave focusout keyup" );
		// Remove 'remove' binding only on delegated targets
		if ( target[0] !== this.element[0] ) {
			this._off( target, "remove" );
		}
		this._off( this.document, "mousemove" );

		if ( event && event.type === "mouseleave" ) {
			$.each( this.parents, function( id, parent ) {
				$( parent.element ).attr( "title", parent.title );
				delete that.parents[ id ];
			});
		}

		this.closing = true;
		this._trigger( "close", event, { tooltip: tooltip } );
		this.closing = false;
	},

	_tooltip: function( element ) {
		var id = "ui-tooltip-" + increments++,
			tooltip = $( "<div>" )
				.attr({
					id: id,
					role: "tooltip"
				})
				.addClass( "ui-tooltip ui-widget ui-corner-all ui-widget-content " +
					( this.options.tooltipClass || "" ) );
		$( "<div>" )
			.addClass( "ui-tooltip-content" )
			.appendTo( tooltip );
		tooltip.appendTo( this.document[0].body );
		this.tooltips[ id ] = element;
		return tooltip;
	},

	_find: function( target ) {
		var id = target.data( "ui-tooltip-id" );
		return id ? $( "#" + id ) : $();
	},

	_removeTooltip: function( tooltip ) {
		tooltip.remove();
		delete this.tooltips[ tooltip.attr( "id" ) ];
	},

	_destroy: function() {
		var that = this;

		// close open tooltips
		$.each( this.tooltips, function( id, element ) {
			// Delegate to close method to handle common cleanup
			var event = $.Event( "blur" );
			event.target = event.currentTarget = element[0];
			that.close( event, true );

			// Remove immediately; destroying an open tooltip doesn't use the
			// hide animation
			$( "#" + id ).remove();

			// Restore the title
			if ( element.data( "ui-tooltip-title" ) ) {
				element.attr( "title", element.data( "ui-tooltip-title" ) );
				element.removeData( "ui-tooltip-title" );
			}
		});
	}
});

}( jQuery ) );
;

/*
scroll move
*/
(function($) {
    $.fn.extend({
        scrollView: function(option) {
            var defaultOption = {
                displayNum: 6,
                scrollNum: 1,
                duration: 500,
                continuous: true,
                displayLabel: "li",
                labelParent: "ul",
                autoScroll: true,
                timer: 3000,
                duringScroll:function(){}
            };
            $.extend(defaultOption, option);
            return this.each(function() {
                var $this = $(this),
                    btWrap = defaultOption.btWrap,
                    displayNum = defaultOption.displayNum,
                    duration = defaultOption.duration,
                    timer = defaultOption.timer,
                    displayLabel = defaultOption.displayLabel,
                    labelParent = defaultOption.labelParent,
                    scrollContainer = $this.find(".scrollDiv"),
                    scrollList = scrollContainer.find(">" + labelParent),
                    scrollItem = scrollList.find(">" + displayLabel),
                    scrollItemLen = scrollItem.length,
                    isEnough = scrollItemLen > displayNum,
                    scrollItemWorH = defaultOption.vertical ? scrollItem.outerHeight(true) : scrollItem.outerWidth(true),
                    isMouseDown = false,
                    rightDirection = true,
                    scrollAxis = defaultOption.vertical ? 'top' : 'left',
                    cssWorH = defaultOption.vertical ? 'height' : 'width',
                    scrollNum = defaultOption.scrollNum || 1,
                    startCount = 0,
                    scrollCount = 0,
                    scrollMax = scrollItemLen - displayNum,
                    animateLock = false,
                    autoScroll = defaultOption.autoScroll,
                    lastScroll = scrollMax % scrollNum;
                if ( !! btWrap) {
                        var leftButton = btWrap.find(".scroll_up"),
                            rightButton = btWrap.find(".scroll_down");
                    } else {
                        var leftButton = $this.find(".scroll_up"),
                            rightButton = $this.find(".scroll_down");
                    }
                if (scrollAxis === 'left') {
                        scrollItem.css({
                            "float": "left",
                            "display": "block"
                        });
                        scrollList.css({
                           "left" : 0
                        });
                        scrollContainer.css("height", scrollItem.outerHeight() + 'px');
                }
                scrollContainer.css({
                        "position": "relative",
                        "overflow": "hidden",
                        "display": "block"
                    });
                scrollContainer.css(cssWorH, displayNum * scrollItemWorH + 'px');
                scrollList.css({
                           "position": "absolute"    
                        });
                scrollList.css({
                        cssWorH: scrollItem.length * scrollItemWorH + 'px'
                    });
                _autoScroll();
                if (isEnough) {
                    rightButton.unbind("mousedown mouseup");
                    leftButton.unbind("mousedown mouseup");
                        rightButton.bind('mousedown', function() {
                            clearInterval(autoScroll);
                            _scrollRight(true);
                        }).bind('mouseup', function() {
                            isMouseDown = false;
                            _autoScroll();
                        });
                        leftButton.bind('mousedown', function() {
                            clearInterval(autoScroll);
                            _scrollLeft(true);
                        }).bind('mouseup', function() {
                            isMouseDown = false;
                            _autoScroll();
                        });
                    }
                scrollItem.hover(function() {
                        clearInterval(autoScroll);
                    }, function() {
                        _autoScroll();
                    });

                function _scrollLeft(mouseDown) {
                        if (scrollCount <= 0 || animateLock) return;
                        rightDirection = false;
                        isMouseDown = mouseDown || false;
                        scrollCount -= scrollCount == lastScroll ? lastScroll : scrollNum;
                        _animate(scrollCount,-1);
                    }

                function _scrollRight(mouseDown) {
                        if (scrollCount >= scrollMax || animateLock) return;
                        isMouseDown = mouseDown || false;
                        rightDirection = true;
                        scrollCount += scrollMax - scrollCount == lastScroll ? lastScroll : scrollNum;
                        _animate(scrollCount,1);
                    };

                function _autoScroll() {
                        if (!autoScroll || !isEnough) return; //if the items is less than display item and no auto scroll
                        autoScroll = setInterval(function() {
                            _scrollRight()
                        }, timer);
                    }

                function _animate(count,dir) {
                        scrollCount = count;
                        animateLock = true;
                        var property = {};
                        property[scrollAxis] = '-' + scrollCount * scrollItemWorH + 'px';
                        scrollList.animate(property, duration, function() {
                            animateLock = false;
                            if (defaultOption.continuous) {
                                if (rightDirection && scrollCount >= scrollMax) {
                                    scrollList.css(scrollAxis, '-' + startCount * scrollItemWorH + 'px');
                                    scrollCount = startCount;
                                } else if (scrollCount <= 0) {
                                    scrollList.css(scrollAxis, '-' + scrollItemLen * scrollItemWorH + 'px');
                                    scrollCount = scrollItemLen;
                                }
                            }
                            if (isMouseDown) {
                                if (rightDirection) {
                                    if (scrollCount >= scrollMax) return;
                                    scrollCount += scrollMax - scrollCount == lastScroll ? lastScroll : scrollNum;
                                    _animate(scrollCount);
                                } else {
                                    if (scrollCount <= 0) return;
                                    scrollCount -= scrollCount == lastScroll ? lastScroll : scrollNum;
                                    _animate(scrollCount);
                                }
                            }
                        });
                        defaultOption.duringScroll(count,dir);
                    };
                if (defaultOption.continuous && isEnough) { //if the items is less than display item and do not need continuous
                        _buildHTML();
                    }

                function _buildHTML() {
                        var len = scrollItemLen;
                        var prepandNodes = scrollItem.slice(len - displayNum, len).clone().prependTo(scrollList);
                        var appendNodes = scrollItem.slice(0, displayNum).clone().appendTo(scrollList);
                        len += displayNum * 2;
                        scrollCount = startCount = displayNum;
                        scrollMax += displayNum * 2;
                        var property = {};
                        property[cssWorH] = len * scrollItemWorH + 'px';
                        property[scrollAxis] = '-' + startCount * scrollItemWorH + 'px';
                        scrollList.css(property);
                    };
                if (defaultOption.eventType) { //Determining if need to show the preview
                        scrollList.bind(defaultOption.eventType, function(e) {
                            defaultOption.eventHandler(e);
                        });
                    }
            });
        }
    })
})(jQuery);;/* Copyright (c) 2006 Brandon Aaron (http://brandonaaron.net)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) 
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * $LastChangedDate: 2007-07-22 01:45:56 +0200 (Son, 22 Jul 2007) $
 * $Rev: 2447 $
 *
 * Version 2.1.1
 */
(function($){$.fn.bgIframe=$.fn.bgiframe=function(s){if($.browser.msie&&/6.0/.test(navigator.userAgent)){s=$.extend({top:'auto',left:'auto',width:'auto',height:'auto',opacity:true,src:'javascript:false;'},s||{});var prop=function(n){return n&&n.constructor==Number?n+'px':n;},html='<iframe class="bgiframe"frameborder="0"tabindex="-1"src="'+s.src+'"'+'style="display:block;position:absolute;z-index:-1;'+(s.opacity!==false?'filter:Alpha(Opacity=\'0\');':'')+'top:'+(s.top=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderTopWidth)||0)*-1)+\'px\')':prop(s.top))+';'+'left:'+(s.left=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderLeftWidth)||0)*-1)+\'px\')':prop(s.left))+';'+'width:'+(s.width=='auto'?'expression(this.parentNode.offsetWidth+\'px\')':prop(s.width))+';'+'height:'+(s.height=='auto'?'expression(this.parentNode.offsetHeight+\'px\')':prop(s.height))+';'+'"/>';return this.each(function(){if($('> iframe.bgiframe',this).length==0)this.insertBefore(document.createElement('html'),this.firstChild);});}return this;};})(jQuery);;$(document).ready(function(){
	$("#vote").css("top",$(document).scrollTop()+200);


//	$(".lazy,.lazy img").lazyload({
//		placeholder : LOADER_IMG,
//		threshold : 0,
//		event:"scroll",
//		effect: "fadeIn",
//		failurelimit : 10
//	});

	// if($("#user_head_tip .tip_box").length > 0){
	// 	$("#user_head_tip  .msg_count").hover(function(){
	// 		$("#user_head_tip  .tip_box").show();
	// 	});
	// 	$("#user_head_tip  .tip_box a.close").bind("click",function(){
	// 		$("#user_head_tip  .tip_box").remove();
	// 		$("#user_head_tip  .pm").removeClass("new_pm");
	// 	});
	// }

	$(".J_reportGuy").bind("click",function(){
		var user_id = $(this).attr("dataid");
		if(parseInt(user_id)==0){
			return ;
		}
		$.weeboxs.open(APP_ROOT+"/index.php?ctl=ajax&act=reportguy&user_id="+user_id, {contentType:'ajax',showButton:false,title:"????????????",width:620,height:340,type:'wee'});
	});

	//$(".header,#ftw").pngFix();
	//????????????????????????
	$(window).scroll(function(){
		$("#vote").css("top",$(document).scrollTop()+200);
	});

	init_gotop();

	$('#submit-mail-image,#tip-submit-deal-mail').click(function(){
		submit_mail($(this));
	});

	//?????????????????????????????????
	$("#modify_bind").bind("click",function(){
		$(this).hide();
		$("#lottery_mobile_input").show();
		$("#lottery_mobile_word").hide();
	});
	//????????????????????????
	$(".flink").find("a").bind("click",function(){
		var ajaxurl = APP_ROOT+"/index.php?ctl=link&act=go&url="+$(this).attr("href");
		$.ajax({
			url: ajaxurl,
			success: function(html){

			}
		});
	});

	$('#verify_ecv').bind("click",function(){
		var ecvsn = $(this).parent().find("input[name='ecvsn']").val();
		var ecvpassword = $(this).parent().find("input[name='ecvpassword']").val();
		var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=verify_ecv&ecvsn="+ecvsn+"&ecvpassword="+ecvpassword;
		$.ajax({
			url: ajaxurl,
			success: function(text){
			$.showSuccess(text);
			},
			error:function(ajaxobj)
			{
//				if(ajaxobj.responseText!='')
//				alert(ajaxobj.responseText);
			}
		});
	});

	//??????????????????????????????????????????
	$("#order_done").click(function(){
		submit_buy();
	});

	$('#share-copy-button').click(function(){
		$.copyText('#share-copy-text');
	});

	//??????????????????????????????
	$(".main_nav").find("a").bind("focus",function(){
		$(this).blur();
	});

	submit_message();

	$("#J_autoBidEnable").click(function(){
		var is_effect = 1;
		if($(this).hasClass("open")){
			is_effect  = 0;
		}
		$.ajax({
			url : APP_ROOT + "/index.php?ctl=uc_autobid&act=autoopen&is_effect="+is_effect,
			dataType : "json",
			cache:false,
			success:function(result){
				if(result.status==1){
					window.location.href = window.location.href;
				}
				else{
					$.showErr(result.info);
				}
			}
		});
	});

	$("a.J_comment_reply").click(function(){
		var dealid = $(this).attr("dealid");
		var dataid = $(this).attr("dataid");
		var html='<div id="'+dataid+'_commentBox" class="clearfix">';
			html +='<div class="comment_edit">';
			html +='<textarea id="'+dataid+'_comment" class="f-textarea" rows="5" cols="60"></textarea>';
			html +='</div>';
			html +='<div class="blank5"></div>';
			html +='<p><input type="button" value="??????" id="loanCommentBtn" onclick="replyCommentSubmit(\''+dealid+'\',\''+dataid+'\',\''+dataid+'_comment\')">';
			html +='<input type="button" value="??????" onclick="cancelReply(\''+dataid+'_commentBox\')">';
			html +='</p></div>';
		if($("#"+dataid+"_commentBox").length == 0)
			$(this).parent().after(html);
	});

	$(".J_send_msg").bind("click",function(){
		var user_id = $(this).attr("dataid");
		if(parseInt(user_id)==0){
			return ;
		}
		$.weeboxs.open(APP_ROOT+"/index.php?ctl=ajax&act=send_msg&user_id="+user_id, {contentType:'ajax',showButton:false,title:"??????????????????",width:560,height:200,type:'wee'});
	});

	$("#J_biao_list .item").hover(function(){
		if(!$(this).hasClass("item_1")){
			$(this).addClass("item_cur");
		}
	},function(){
		$(this).removeClass("item_cur");
	});

	//??????
	$("#stepVerifyIdCardAndPhone").submit(function(){
		var obj = $(this);
		if(!obj.find("#idno").hasClass("readonly")){
			if($.trim(obj.find("#idno").val())==""){
				$.showErr(LANG.PLEASE_INPUT+LANG.IDNO,function(){
					obj.find("#idno").focus();
				});
				return false;
			}
			if($.trim(obj.find("#idno").val())!=$.trim(obj.find("#idno_re").val())){
				$.showErr(LANG.TWO_ENTER_IDNO_ERROR,function(){
					obj.find("#idno_re").focus();
				});
				return false;
			}
		}

		if(!$("#J_Vphone").hasClass("readonly")){
			if($.trim($("#J_Vphone").val())==""){
				$.showErr(LANG.MOBILE_EMPTY_TIP,function(){
					$("#J_Vphone").focus();
				});
				return false;
			}
			if(!$.checkMobilePhone($("#J_Vphone").val())){
				$.showErr(LANG.FILL_CORRECT_MOBILE_PHONE,function(){
					$("#J_Vphone").focus();
				});
				return false;
			}
			if($.trim(obj.find("#validateCode2").val())==""){
				$.showErr(LANG.PLEASE_INPUT+LANG.VERIFY_CODE,function(){
					obj.find("#validateCode2").focus();
				});
				return false;
			}
		}

		var query = obj.serialize();
		$.ajax({
			url:APP_ROOT + "/deal/dobidstepone",
			data:query,
                        type: "POST",
			dataType:"json",
			success:function(result){
				if(result.status==1)
				{
				    $.showSuccess(result.info);
				    setTimeout("window.location.href = window.location.href", 3000);
					//window.location.href=window.location.href;
				}
				else{
					$.showErr(result.info);
				}
			}
		});
		return false;
	});

	init_top_nav();
});






//??????????????????????????????????????????JS
$.showErr = function(str,func,title)
{
	var title = title || '??????';
	$.weeboxs.open(str, {boxid:'fanwe_error_box',contentType:'text',showButton:true, showCancel:false, showOk:true,title:title,width:266,type:'wee',onclose:func});
};

$.showSuccess = function(str,func)
{
	$.weeboxs.open(str, {boxid:'fanwe_success_box',contentType:'text',showButton:true, showCancel:false, showOk:true,title:'??????',width:266,type:'wee',onclose:func});
};

/*??????*/
$.minLength = function(value, length , isByte) {
	var strLength = $.trim(value).length;
	if(isByte)
		strLength = $.getStringLength(value);

	return strLength >= length;
};

$.maxLength = function(value, length , isByte) {
	var strLength = $.trim(value).length;
	if(isByte)
		strLength = $.getStringLength(value);

	return strLength <= length;
};
$.getStringLength=function(str)
{
	str = $.trim(str);

	if(str=="")
		return 0;

	var length=0;
	for(var i=0;i <str.length;i++)
	{
		if(str.charCodeAt(i)>255)
			length+=2;
		else
			length++;
	}

	return length;
};

$.checkNumber = function(value){
	if($.trim(value)!='')
		return !isNaN($.trim(value));
	else
		return true;
};

$.checkMobilePhone = function(value){
	if($.trim(value)!='')
		return /^\d{6,}$/i.test($.trim(value));
	else
		return true;
};
$.checkEmail = function(val){
	var reg = /^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/;
	return reg.test(val);
};


function formSuccess(obj,msg)
{
	if (msg != '') {
		$(obj).parent().find(".hint").css({"color":"#fff"});
		$(obj).parent().find(".f-input-tip").html("<span class='form_success'>" + msg + "</span>");
	}
	else {
		$(obj).parent().find(".hint").css({"color":"#989898"});
		$(obj).parent().find(".f-input-tip").html("");
	}
}
function formError(obj,msg)
{
		$(obj).parent().find(".hint").css({"color":"#fff"});
		$(obj).parent().find(".f-input-tip").html("<span class='form_err'>" + msg + "</span>");

}


//???????????????
function modify_cart(id,htmlobj)
{
	var number = $(htmlobj).val();
	var ajaxurl = APP_ROOT+"/index.php?ctl=cart&act=modifycart&id="+id+"&number="+number;
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			if(obj.status == 1)
			{
				$("#cart_list").html(obj.html);
			}
			else
			{
				var str = obj.info.split("|");
				var msg = str[0];
				$.showErr(msg);
				$(".deal_cart_row").removeClass("cart_warn");
				if(str[2])
					$("tr[rel*='cart_"+str[1]+"_"+str[2]+"']").addClass("cart_warn");
					else
					$(".deal_"+str[1]).addClass("cart_warn");
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}
//???????????????
function del_cart(id)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=cart&act=delcart&id="+id;
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			location.href = CART_URL;
			/*if(obj.status == 1)
			{
				$("#cart_list").html(obj.html);
			}
			else
			{
				location.href = CART_URL;
			}*/
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

//???????????????????????????
function submit_cart()
{
	var verify_code = $("input[name='verify_code']").val();
	var mobile = $("input[name='lottery_mobile']").val();
	var ajaxurl = APP_ROOT+"/index.php?ctl=cart&act=check&ajax=1&verify="+verify_code+"&mobile="+mobile;

	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			if(obj.status == 1)
			{
				location.href = CART_CHECK_URL;
			}
			else
			{
				if(obj.open_win == 1)
				{
					$.weeboxs.open(obj.html, {contentType:'text',showButton:false,title:LANG['PLEASE_LOGIN_FIRST'],width:570,type:'wee'});
				}
				else
				{
					var str = obj.info.split("|");
					var msg = str[0];
					$.showErr(msg);
					$(".deal_cart_row").removeClass("cart_warn");
					if(str[2])
						$("tr[rel*='cart_"+str[1]+"_"+str[2]+"']").addClass("cart_warn");
						else
						$(".deal_"+str[1]).addClass("cart_warn");
				}
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}



//????????????????????????????????????
//??????????????????
function load_consignee(consignee_id)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=load_consignee&id="+consignee_id;
	$.ajax({
		url: ajaxurl,
		success: function(html){
			$("#cart_consignee").html(html);
			load_delivery();
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(LANG['REFRESH_TOO_FAST']);
		}
	});
}
//??????????????????
function load_delivery()
{
	var select_last_node = $("#cart_consignee").find("select[value!='0']");
	if(select_last_node.length>0)
	{
		var region_id = $(select_last_node[select_last_node.length - 1]).val();
	}
	else
	{
		var region_id = 0;
	}
	return;

	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=load_delivery&id="+region_id+"&order_id="+($("input[name='id']")?$("input[name='id']").val():'');
	$.ajax({
		url: ajaxurl,
		success: function(html){
			$("#cart_delivery").html(html);
			count_buy_total();  //???????????????????????????????????????
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(LANG['REFRESH_TOO_FAST']);
		}
	});
}

//??????????????????
function count_buy_total()
{
	$("#order_done").attr("disabled",true);
	var query = new Object();

	//??????????????????
	var delivery_id = $("input[name='delivery']:checked").val();

	if(!delivery_id)
	{
		delivery_id = 0;
	}
	query.delivery_id = delivery_id;

	//????????????
	var select_last_node = $("#cart_consignee").find("select[value!='0']");
	if(select_last_node.length>0)
	{
		var region_id = $(select_last_node[select_last_node.length - 1]).val();
	}
	else
	{
		var region_id = 0;
	}
	query.region_id = region_id;

	//????????????
	var account_money = $("input[name='account_money']").val();
	if(!account_money||$.trim(account_money)=='')
	{
		account_money = 0;
	}
	query.account_money = account_money;

	//????????????
	if($("#check-all-money").attr("checked"))
	{
		query.all_account_money = 1;
	}
	else
	{
		query.all_account_money = 0;
	}

	//?????????
	var ecvsn = $("input[name='ecvsn']").val();
	if(!ecvsn)
	{
		ecvsn = '';
	}
	var ecvpassword = $("input[name='ecvpassword']").val();
	if(!ecvpassword)
	{
		ecvpassword = '';
	}
	query.ecvsn = ecvsn;
	query.ecvpassword = ecvpassword;

	//????????????
	var payment = $("input[name='payment']:checked").val();
	if(!payment)
	{
		payment = 0;
	}
	query.payment = payment;
	query.bank_id = $("input[name='payment']:checked").attr("rel");
	if(!isNaN(order_id)&&order_id>0)
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=count_order_total&id="+order_id;
	else
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=count_buy_total";
	$.ajax({
		url: ajaxurl,
		data:query,
		type: "POST",
		dataType: "json",
		success: function(data){
			$("#cart_total").html(data.html);
			$("input[name='account_money']").val(data.account_money);
			if(data.pay_price == 0)
			{
				$("input[name='payment']").attr("checked",false);
			}
			$("#order_done").attr("disabled",false);
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(LANG['REFRESH_TOO_FAST']);
		}
	});
}

//????????????
function submit_buy()
{
	$("#order_done").attr("disabled",true);
	var query = new Object();

	//??????????????????
	var delivery_id = $("input[name='delivery']:checked").val();

	if(!delivery_id)
	{
		delivery_id = 0;
	}
	query.delivery_id = delivery_id;

	//????????????
	var select_last_node = $("#cart_consignee").find("select[value!='0']");
	if(select_last_node.length>0)
	{
		var region_id = $(select_last_node[select_last_node.length - 1]).val();
	}
	else
	{
		var region_id = 0;
	}
	query.region_id = region_id;

	//????????????
	var account_money = $("input[name='account_money']").val();
	if(!account_money||$.trim(account_money)=='')
	{
		account_money = 0;
	}
	query.account_money = account_money;

	//????????????
	if($("#check-all-money").attr("checked"))
	{
		query.all_account_money = 1;
	}
	else
	{
		query.all_account_money = 0;
	}

	//?????????
	var ecvsn = $("input[name='ecvsn']").val();
	if(!ecvsn)
	{
		ecvsn = '';
	}
	var ecvpassword = $("input[name='ecvpassword']").val();
	if(!ecvpassword)
	{
		ecvpassword = '';
	}
	query.ecvsn = ecvsn;
	query.ecvpassword = ecvpassword;

	//????????????
	var payment = $("input[name='payment']:checked").val();
	if(!payment)
	{
		payment = 0;
	}
	query.payment = payment;

	if(!isNaN(order_id)&&order_id>0)
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=count_order_total&id="+order_id;
	else
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=count_buy_total";
	$.ajax({
		url: ajaxurl,
		data:query,
		type: "POST",
		dataType: "json",
		success: function(data){
			if(data.is_delivery == 1)
			{
				//????????????
				if(!data.region_info||data.region_info.region_level != 4)
				{
					$.showErr(LANG['FILL_CORRECT_CONSIGNEE_ADDRESS']);
					$("#order_done").attr("disabled",false);
					return;
				}
				if($.trim($("input[name='consignee']").val())=='')
				{
					$.showErr(LANG['FILL_CORRECT_CONSIGNEE']);
					$("#order_done").attr("disabled",false);
					return;
				}
				if($.trim($("input[name='address']").val())=='')
				{
					$.showErr(LANG['FILL_CORRECT_ADDRESS']);
					$("#order_done").attr("disabled",false);
					return;
				}
				if($.trim($("input[name='zip']").val())=='')
				{
					$.showErr(LANG['FILL_CORRECT_ZIP']);
					$("#order_done").attr("disabled",false);
					return;
				}
				if($.trim($("input[name='mobile']").val())=='')
				{
					$.showErr(LANG['FILL_MOBILE_PHONE']);
					$("#order_done").attr("disabled",false);
					return;
				}
				if(!$.checkMobilePhone($("input[name='mobile']").val()))
				{
					$.showErr(LANG['FILL_CORRECT_MOBILE_PHONE']);
					$("#order_done").attr("disabled",false);
					return;
				}
				if(!data.delivery_info)
				{
					$.showErr(LANG['PLEASE_SELECT_DELIVERY']);
					$("#order_done").attr("disabled",false);
					return;
				}
			}

			if(data.pay_price!=0&&!data.payment_info)
			{
				$.showErr(LANG['PLEASE_SELECT_PAYMENT']);
				$("#order_done").attr("disabled",false);
				return;
			}

			$("#cart_form").submit();
		},
		error:function(ajaxobj)
		{
//			alert("error: "+ajaxobj.responseText);
//			return false;
		}
	});
}
function sendPhoneCode(o, obj){
	if($.trim($(obj).val())==""){
		$.showErr(LANG.VERIFY_MOBILE_EMPTY);
		return false;
	}
	if(!$.checkMobilePhone($(obj).val())){
		$.showErr(LANG.FILL_CORRECT_MOBILE_PHONE);
		return false;
	}
	if (!$(o).hasClass('dis_rActcode')) {
		$(o).addClass('dis_rActcode');
		get_verify_code(obj,function(){
			ResetsendPhoneCode(60);
		});
	}
}
var resetSpcThread = null;
function ResetsendPhoneCode(times){
	clearTimeout(resetSpcThread);
	if(times > 0){
		times -- ;
		$("#reveiveActiveCode").addClass("dis_rActcode");
		$("#reveiveActiveCode").val(LANG.DO_GET+LANG.MOBILE_VERIFY_CODE +" "+ times);
		resetSpcThread = setTimeout("ResetsendPhoneCode("+times+")",1000);
	}
	else{
		$("#reveiveActiveCode").removeClass("dis_rActcode");
		$("#reveiveActiveCode").val(LANG.DO_GET+LANG.MOBILE_VERIFY_CODE);
	}
}

function get_verify_code(obj,func)
{
	var user_mobile = $(obj).val();
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=get_verify_code&user_mobile="+user_mobile;
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			if (obj.status) {
				if (func != null) {
					func.call(this);
				}
				$.showSuccess(obj.info);
			}
			else {
				$("#reveiveActiveCode").removeClass("dis_rActcode");
				$.showErr(obj.info);
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}


//??????????????????
$.copyText = function(id)
{
	var txt = $(id).val();
	if(window.clipboardData)
	{
		window.clipboardData.clearData();
		var judge = window.clipboardData.setData("Text", txt);
		if(judge === true)
			alert(LANG.JS_COPY_SUCCESS);
		else
			alert(LANG.JS_COPY_NOT_SUCCESS);
	}
	else if(navigator.userAgent.indexOf("Opera") != -1)
	{
		window.location = txt;
	}
	else if (window.netscape)
	{
		try
		{
			netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
		}
		catch(e)
		{
			alert(LANG.JS_NO_ALLOW);
		}
		var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
		if (!clip)
			return;
		var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
		if (!trans)
			return;
		trans.addDataFlavor('text/unicode');
		var str = new Object();
		var len = new Object();
		var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
		var copytext = txt;
		str.data = copytext;
		trans.setTransferData("text/unicode",str,copytext.length*2);
		var clipid = Components.interfaces.nsIClipboard;
		if (!clip)
			return false;
		clip.setData(trans,null,clipid.kGlobalClipboard);
		alert(LANG.JS_COPY_SUCCESS);
	}
};


function track_express(express_sn,express_id)
{
	$.ajax({
			url: APP_ROOT+"/express.php?express_sn="+express_sn+"&express_id="+express_id,
			data: "ajax=1",
			dataType: "json",
			success: function(obj){
				if(obj.status==2)
				{
					window.open(obj.msg);
				}
				if(obj.status==1)
				{
					$.weeboxs.open(obj.msg, {contentType:'html',showButton:false,title:LANG['TRACK_EXPRESS'],width:550,height:280,type:'wee'});
				}
				if(obj.status==0)
				{
					$.showErr(obj.msg);
				}
			}
	});
}

function set_sort(type)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=set_sort&type="+type;
	$.ajax({
		url: ajaxurl,
		success: function(text){
			location.reload();
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function set_event_sort(type)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=set_event_sort&type="+type;
	$.ajax({
		url: ajaxurl,
		success: function(text){
			location.reload();
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function switch_style(type)
{
	var ajaxurl = APP_ROOT+"/tuan.php?ctl=ajax&act=switch_style&type="+type;
	$.ajax({
		url: ajaxurl,
		success: function(text){
			location.reload();
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}


/**
 * ??????????????????JS
 */

function add_score(id)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=check_login_status";
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		type: "POST",
		success: function(ajaxobj){
			if(ajaxobj.status==0)
			{
				ajax_login();
			}
			else
			{
				add_cart(id);
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

/* ??????????????? */
function add_cart(id,attr)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=cart&act=addcart&id="+id;
	if(attr&&attr != '')
	{
		ajaxurl += attr;

	}
	else
	{
		attrs = $("select[name='attr[]']");
		for(i=0;i<attrs.length;i++)
		{
			ajaxurl += "&attr[]="+$(attrs[i]).val();
		}

	}
	var number = $("input[name='number']").val();
	if(number)
	ajaxurl+="&number="+number;

	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			if(obj.open_win == 1)
			{
				if($(".dialog-mask").css("display")=='block')
				{
					$(".dialog-mask,.dialog-box").remove();
				}

				if(obj.err == 1)
				$.weeboxs.open("<span class='cart-error'>"+obj.html+"</span>", {contentType:'text',showButton:false,title:LANG['ADD_CART_ERR'],width:570,type:'wee'});
				else
				$.weeboxs.open(obj.html, {contentType:'text',showButton:false,title:LANG['SELECT_AND_ADDCART'],width:570,type:'wee'});
			}
			else if(obj.open_win == 2)
			{
				$.showErr(obj.info);
			}
			else
			{
				if($(".dialog-mask").css("display")=='block')
				{
					$(".dialog-mask,.dialog-box").remove();
				}
				$("#cart_count").html(parseInt(obj.number));
				$.weeboxs.open(obj.html, {contentType:'text',showButton:false,title:LANG['ADDCART_SUCCESS'],width:570,type:'wee'});
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});

}

function collect_deal(o,deal_id,func)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=collect&id="+deal_id;

	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			if(obj.open_win == 1)
			{
				//$.weeboxs.open(obj.html, {contentType:'text',showButton:false,title:LANG['PLEASE_LOGIN_FIRST'],width:570,type:'wee'});
				window.location.href="/user-login?backurl="+window.location.href;
			}
			else
			{
				if(func!=null)
					func.call(this,o);
				else
					$.showSuccess(obj.info);
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function clear_cart()
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=cart&act=clear_cart";

	$.ajax({
					url: ajaxurl,
					success: function(obj){
						location.href= location.href;
					},
					error:function(ajaxobj)
					{
//						if(ajaxobj.responseText!='')
//						alert(ajaxobj.responseText);
					}
	});
}


function submit_sms()
{
	$.weeboxs.open(APP_ROOT+"/index.php?ctl=sms&act=subscribe", {contentType:'ajax',showButton:false,title:LANG['SMS_SUBSCRIBE'],width:420,height:200,type:'wee'});
}
function unsubmit_sms()
{
	$.weeboxs.open(APP_ROOT+"/index.php?ctl=sms&act=unsubscribe", {contentType:'ajax',showButton:false,title:LANG['SMS_UNSUBSCRIBE'],width:420,height:200,type:'wee'});
}


//?????????????????????js
function submit_mail(o)
{
	var email = $("#submit-mail-text").val();
	if(email == '')
	{
		$.showErr(LANG.EMAIL_EMPTY_TIP);
		return;
	}
	if(!$.checkEmail(email))
	{
		$.showErr(LANG.EMAIL_FORMAT_ERROR_TIP);
		return;
	}
	var ajaxurl = APP_ROOT+"/tuan.php?ctl=subscribe&act=addmail&email="+email+"&ajax=1";
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			if(obj.status == 1)
			{
				$.showSuccess(LANG.SUBSCRIBE_SUCCESS);
				return;
			}
			else
			{
				$.showErr(obj.info);
				return;
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function focus_user(uid)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=focus&uid="+uid;
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			if(obj.tag==1 || obj.tag==2)
			{
				$(".focus_info_card").removeClass("add_focus");
				$(".focus_info_card").removeClass("remove_focus");
				$(".focus_info_card").addClass("remove_focus");
				$(".focus_info_card").html(obj.html);
				$(".focus_info").html(obj.html);
			}
			if(obj.tag==2)
			{
				$(".focus_info_card").removeClass("add_focus");
				$(".focus_info_card").removeClass("remove_focus");
				$(".focus_info_card").addClass("add_focus");
				$(".focus_info_card").html(obj.html);
				$(".focus_info").html(obj.html);
			}
			if(obj.tag==3)
			{
				$.showSuccess(obj.html);
			}
			if(obj.tag==4)
			{
			        window.location.href="/user-login?backurl="+window.location.href;
				//ajax_login();
			}

		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function vote_topic(topic_id,tag,o)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=vote_topic&tag="+tag+"&topic_id="+topic_id;
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			if(obj.status)
			$(o).find("span").html(obj.data);
			else
				$.showErr(obj.data);
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function check_content(obj)
{

	if($(obj).find("*[name='content']").val()=='')
	{
		$.showErr(LANG.MESSAGE_CONTENT_EMPTY);
		$(obj).find("*[name='content']").focus();
		return false;
	}
	else
	{
		return true;
	}
}

function sms_download(id)
{
	var ajaxurl = APP_ROOT+"/store.php?ctl=fdetail&act=load_sms&id="+id;

	$.ajax({
		url: ajaxurl,
		dataType: "json",
		success: function(obj){
			if(obj.status == 1)
			{
				$.weeboxs.open(obj.html, {contentType:'text',showButton:false,title:'????????????',width:570,type:'wee'});
			}
			else
			{
				//????????????
				ajax_login();
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function send_sms(id)
{
	var query = new Object();
	query.id = id;
	query.date_time = $("input[name='date_time']").val();
	query.date_time_h = $("select[name='date_time_h']").val();
	query.date_time_m = $("select[name='date_time_m']").val();
	query.order_count = $("input[name='order_count']").val();
	query.is_private_room = $("input[name='is_private_room']:checked").val();
	query.mobile = $("input[name='mobile']").val();


	if(!$.checkMobilePhone(query.mobile)||query.mobile=='')
	{
		$.showErr(LANG['FILL_CORRECT_MOBILE_PHONE']);
		return;
	}

	var ajaxurl = APP_ROOT+"/store.php?ctl=fdetail&act=send_sms";

	$.ajax({
		url: ajaxurl,
		dataType: "json",
		data:query,
		type: "POST",
		success: function(obj){
			if(obj.status == 1)
			{
				close_pop();
				$.showSuccess(obj.info);
			}
			else
			{
				//????????????
				$(".dialog-title").html(LANG['PLEASE_LOGIN_FIRST']);
				$(".dialog-content").html(obj.html);
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function closeCouponSms()
{
	$(".dialog-close").click();
}

function relay_topic(id)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=check_login_status";
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		type: "POST",
		success: function(ajaxobj){
			if(ajaxobj.status==0)
			{
				ajax_login();
			}
			else
			{
				var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=relay_topic&id="+id;
				$.weeboxs.open(ajaxurl, {contentType:'ajax',showButton:false,title:LANG['RELAY_TOPIC'],width:570,type:'wee'});
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});

}
function do_relay_topic(id)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=do_relay_topic&id="+id;
	var query  = new Object();
	query.content = $("textarea[name='relay_content']").val();
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		data:query,
		type: "POST",
		success: function(obj){
			if(obj.status)
			{
				$("#topic_relay_"+id).html(parseInt($("#topic_relay_"+id).html())+1);
				close_pop();
				$.showSuccess(obj.info);
				var ajax_url = $("input[name='ajax_url']");
				if(ajax_url)
				{
					ajax_load_page($(ajax_url).val(),$("#col_list"));
				}
			}
			else
			{
				$.showErr(obj.info);
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}
function close_pop()
{
	$(".dialog-close").click();
}

function fav_topic(id)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=do_fav_topic&id="+id;
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		type: "POST",
		success: function(obj){
			if(obj.status)
			{
				$("#topic_fav_"+id).html(parseInt($("#topic_fav_"+id).html())+1);
				$.showSuccess(obj.info);
				var ajax_url = $("input[name='ajax_url']");
				if(ajax_url)
				{
					ajax_load_page($(ajax_url).val(),$("#col_list"));
				}
			}
			else
			{
				var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=check_login_status";
				$.ajax({
					url: ajaxurl,
					dataType: "json",
					type: "POST",
					success: function(ajaxobj){
						if(ajaxobj.status==0)
						{
							ajax_login();
						}
						else
						{
							$.showSuccess(obj.info);
						}
					},
					error:function(ajaxobj)
					{
//						if(ajaxobj.responseText!='')
//						alert(ajaxobj.responseText);
					}
				});

			}
//			location.reload();
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function zoom(obj)
{
//	var img_list = $(obj).parent().parent().find(".toogle_topic_image_box");
//	for(i=0;i<img_list.length;i++)
//	{
//		var box = img_list[i];
//		var o = $(box).find("img");
		var o = obj;
		var tag = $(o).attr('tag');
		var b_src = $(o).attr('b');
		var s_src = $(o).attr('s');
		var o_src = $(o).attr('o');
		var w = $(o).attr('w');
		var h = $(o).attr('h');

		if(tag == 's')
		{
			var img_width = 0;
			if(w>525)
			{
				img_width = 525;
			}
			$(o).attr('src',b_src);
			$(o).attr('tag','b');
			if(img_width>0)
			$(o).attr('width',img_width);
			else
			$(o).removeAttr('width');
			var html = '<div><a href=\"'+o_src+'\" target=\"_blank\">????????????</a></div>' + $(o).parent().html();
			$(o).parent().html(html);
		}
		else
		{
			$(o).attr('src',s_src);
			$(o).attr('tag','s');
			$(o).removeAttr('width');
			$(o).parent().find('div').remove();
		}
//	}
}


//??????????????????
function ajax_load_page(ajaxurl,dom)
{
	//$(dom).html("<span class='ajaxloading'>"+LANG.AJAX_LOADING+"</span>");
	$.ajax({
		url: ajaxurl,
		data:"ajax=1",
		type: "POST",
		success: function(html){
			//$(dom).hide();
			$(dom).html(html);
			//$(dom).fadeIn();
		},
		error:function(ajaxobj)
		{
//			$(dom).html("");
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function reply_topic(id,obj)
{
	if($(obj).parent().parent().find(".col_item_reply_box").html()=='')
	{
		$(".col_item_reply_box").html("");
		var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=load_reply_col_form&id="+id;
		$.ajax({
			url: ajaxurl,
			data:"ajax=1",
			type: "POST",
			success: function(html){
				$(obj).parent().parent().find(".col_item_reply_box").html(html);
			},
			error:function(ajaxobj)
			{
//				if(ajaxobj.responseText!='')
//				alert(ajaxobj.responseText);
			}
		});
	}
	else
	$(obj).parent().parent().find(".col_item_reply_box").html("");

}
function ajax_submit_form(obj)
{
	var form = $(obj).parent().parent().parent();
	var verify_img = $(obj).parent().find("img");
	var ajaxurl = $(form).attr("action");
	var img_box = $(form).find("#image_box");
	var textarea = $(form).find("textarea");
	if($.trim(textarea.val())=='')
	{
		$.showErr("?????????????????????");
		return;
	}
	var groupbox = $(form).find("input[name='group']");
	var groupdatabox = $(form).find("input[name='group_data']");
	var url = $(form).find("input[name='ajax_url']").val();
	var query = $(form).serialize()+"&ajax=1";
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		data:query,
		type: "POST",
		success: function(obj){
			if(obj.status==0)
			{
				$.showErr(obj.info);
				return;
			}
			$.showSuccess(obj.info);
			$(img_box).html("");
			$(verify_img).click();
			$(form).find("input[name='verify']").val("");
			$(textarea).val("");
			$(textarea).attr("position",0);
			$(groupbox).val("");
			$(groupdatabox).val("");
			$("input[name='other_tag']").attr("checked",false);
			$(".other_tag").hide();
			$(".tag_item").removeClass("tag_item_c");
			$("input[name='tag[]']").val("");
			if($("input[name='syn_weibo']").attr("checked"))
			{
				var syn_class = $(".syn_class");
				for(i=0;i<syn_class.length;i++)
				{
					syn_topic_to_weibo(obj.data,$(syn_class[i]).val());
				}
			}
			if(url)ajax_load_page(url,$("#col_list"));
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

//???????????????
function syn_topic_to_weibo(topic_id,class_name)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=syn_to_weibo&topic_id="+topic_id+"&class_name="+class_name;
	$.ajax({
		url: ajaxurl,
		type: "POST",
		success: function(data){

		},
		error:function(ajaxobj)
		{

		}
	});
}

function ajax_submit_reply_form(obj)
{
	var form = $(obj).parent().parent().parent();
	var ajaxurl = $(form).attr("action");
	var textarea = $(form).find("textarea");
	var topic_id = $(form).find("input[name='topic_id']").val();
	var url = APP_ROOT+"/index.php?ctl=ajax&act=load_reply_col_form&id="+topic_id;


	var query = $(form).serialize()+"&ajax=1&no_verify=1";
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		data:query,
		type: "POST",
		success: function(ajaxobj){
			if(ajaxobj.status)
			{
				$("#topic_reply_"+topic_id).html(parseInt($("#topic_reply_"+topic_id).html())+1);
				$.showSuccess(ajaxobj.info);
				ajax_load_page(url,$(obj).parent().parent().parent().parent());
			}
			else
				$.showErr(ajaxobj.info);
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function load_topic_replys(ajaxurl,checklogin)
{
	if(checklogin)
	{
		var ajaxurl_ck = APP_ROOT+"/index.php?ctl=ajax&act=check_login_status";
		$.ajax({
			url: ajaxurl_ck,
			dataType: "json",
			type: "POST",
			success: function(ajaxobj){
				if(ajaxobj.status==0)
				{
					ajax_login(function(){
						$("#topic_page_reply").html("??????????????????");
						$.ajax({
							url: ajaxurl,
							type: "POST",
							success: function(html){
								$("#topic_page_reply").html(html);
							},
							error:function(ajaxobj)
							{
					//			if(ajaxobj.responseText!='')
					//			alert(ajaxobj.responseText);
							}
						});
					});
				}
			},
			error:function(ajaxobj)
			{
//				if(ajaxobj.responseText!='')
//				alert(ajaxobj.responseText);
			}
		});
	}
	else
	{
		$("#topic_page_reply").html("??????????????????");
		$.ajax({
			url: ajaxurl,
			type: "POST",
			success: function(html){
				$("#topic_page_reply").html(html);
			},
			error:function(ajaxobj)
			{
	//			if(ajaxobj.responseText!='')
	//			alert(ajaxobj.responseText);
			}
		});
	}
}

function ajax_submit_reply_form_topic_page(obj)
{
	var form = $(obj).parent().parent().parent();
	var ajaxurl = $(form).attr("action");
	var textarea = $(form).find("textarea");
	var topic_id = $(form).find("input[name='topic_id']").val();
	var load_url = $("#load_url").val();

	var query = $(form).serialize()+"&ajax=1&no_verify=1";
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		data:query,
		type: "POST",
		success: function(ajaxobj){
			if(ajaxobj.status)
			{
				$("#reply_count").html(parseInt($("#reply_count").html())+1);
				$.showSuccess(ajaxobj.info);
				load_topic_replys(load_url);
			}
			else
				$.showErr(ajaxobj.info);
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function delete_topic(id,dom)
{
	if(confirm(LANG.CONFIRM_DELETE_TOPIC))
	{
		var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=delete_topic&id="+id;
		$.ajax({
			url: ajaxurl,
			dataType: "json",
			type: "POST",
			success: function(ajaxobj){
				if(ajaxobj.status)
				{
					$(dom).remove();
				}
				else
					$.showErr(ajaxobj.info);
			},
			error:function(ajaxobj)
			{
//				if(ajaxobj.responseText!='')
//				alert(ajaxobj.responseText);
			}
		});
	}

}
function delete_topic_reply(id,dom)
{
	if(confirm(LANG.CONFIRM_DELETE_RELAY))
	{
		var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=delete_topic_reply&id="+id;
		$.ajax({
			url: ajaxurl,
			dataType: "json",
			type: "POST",
			success: function(ajaxobj){
				if(ajaxobj.status)
				{
					$(dom).remove();
				}
				else
					$.showErr(ajaxobj.info);
			},
			error:function(ajaxobj)
			{
//				if(ajaxobj.responseText!='')
//				alert(ajaxobj.responseText);
			}
		});
	}
}
function init_index_store()
{
	$(".index_store_item").bind("mouseover",function(){
		$(".index_store_detail_box").hide();
		$(".index_store_item").removeClass("act");
		var id = $(this).attr("rel");
		$("#index_store_detail_box_"+id).show();
		$(this).addClass("act");
		$(this).parent().removeClass("r0");
		$(this).parent().removeClass("r1");
		$(this).parent().removeClass("r2");
		$(this).parent().removeClass("r3");
		$(this).parent().removeClass("r4");
		$(this).parent().removeClass("r5");
		$(this).parent().addClass("r"+$(this).attr("idx"));
	});
}

function event_submit(event_id)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=check_event&id="+event_id;
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		type: "POST",
		success: function(ajaxobj){
			if(ajaxobj.status==1)
			{
				//??????????????????
				show_event_submit(event_id);
			}
			else if(ajaxobj.status==2)
			{
				$.showSuccess(ajaxobj.info);
			}
			else
			{
				ajax_login();
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function ajax_login(func)
{
	$.weeboxs.open(APP_ROOT+"/index.php?ctl=ajax&act=ajax_login", {contentType:'ajax',showButton:false,title:LANG['PLEASE_LOGIN_FIRST'],width:570,type:'wee',onclose:func});
}
function show_event_submit(event_id)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=submit_event&id="+event_id;
	$.weeboxs.open(ajaxurl, {contentType:'ajax',showButton:false,title:LANG['EVENT_SUBMIT'],width:370,type:'wee'});
}

function do_event_submit()
{
	var submit_rows = $(".event_submit_row");
	for(var i=0;i<submit_rows.length;i++)
	{
		var row = $(submit_rows[i]);
		if($(row).find("input").val()=='')
		{
			$.showErr(LANG['PLEASE_INPUT']+$(row).find("span").html());
			$(row).find("input").focus();
			return;
		}
	}
	var query = $("form[name='event_submit_form']").serialize();
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=do_event_submit";
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		type: "POST",
		data:query,
		success: function(ajaxobj){
			if(ajaxobj.status==1)
			{
				$.showSuccess(ajaxobj.info);
			}
			else if(ajaxobj.status==2)
			{
				alert(ajaxobj.info);
				location.reload();
			}
			else
			{
				ajax_login();
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}



var timer; //?????????
userCard=(function(){
	var cardDiv;  //??????dom??????
	var userCardStr="userCard"; //??????dom??????ID??????
	var qObj,userId;	//????????????????????????ID
	var mout=function(){
		//????????????
		 timer = setTimeout(function(){
	          cardDiv.hide();
	      },500);
	};
	var mover=function(){
		//????????????
		clearTimeout(timer);
	};
	var createLoadDiv=function(){
		//????????????dom???????????????????????????
		cardDiv=$("<div id='"+userCardStr+userId+"' class='nameCard'><div class='load'>????????????????????????...</div></div>");
		$("body").append(cardDiv);
	};
	var resetXY=function(){
		//????????????dom????????????

		var offset = qObj.offset();
		var of_left = 0;
		if(offset.left+230+qObj.width()>$(document).width())
		{
			of_left = offset.left - 230;
		}
		else
		{
			of_left =  offset.left+qObj.width();
		}
		cardDiv.css( {
			top : offset.top,
			left : of_left
		});
	};
	var showUserCard = function(){
		//????????????
		resetXY();
		cardDiv.show();
	};

	var loadCard=function(){
		$(".nameCard").hide();
		cardDiv=$("#"+userCardStr+userId);
		if(!cardDiv.length){
			createLoadDiv();
			showUserCard();
			cardDiv.load(APP_ROOT+"/index.php?ctl=ajax&act=usercard&uid="+userId);
		}else{
			//?????????????????????
			showUserCard(); //????????????
		};
		//??????????????????????????????????????????
		cardDiv.hover(mover,mout);
		qObj.hover(mover,mout);
	};

	return {
		load : function(e,id){//??????id????????????e:??????DOM??????,?????????this; id:??????????????????ID

				clearTimeout(timer);
				if(e===undefined || id===undefined || isNaN(id) || id<1){
					return false;
				};
				qObj=$(e); //?????????????????????
				userId=id; //??????ID
				//????????????
				loadCard(); //????????????
			}
	  	};
})();


function set_syn(syn_field)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=set_syn&field="+syn_field;
	$.ajax({
		url: ajaxurl,
		type: "POST",
		dataType: "json",
		success: function(data){
			alert(data.info);
			location.reload();
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}


function load_api_url(class_name,type)
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=load_api_url&class_name="+class_name+"&type="+type;
	$.ajax({
		url: ajaxurl,
		type: "POST",
		success: function(data){
			$("#api_"+class_name+"_"+type).html(data);
		},
		error:function(ajaxobj)
		{

		}
	});
}

function daren_submit()
{
	if($.trim($("*[name='daren_title']").val())=='')
	{
		$.showErr("?????????????????????");
		return;
	}
	else if($.trim($("*[name='daren_title']").val()).length>10)
	{
		$.showErr("??????????????????");
		return;
	}
	if($.trim($("*[name='reason']").val())=='')
	{
		$.showErr("?????????????????????");
		return;
	}

	var query = new Object();
	query.daren_title = $.trim($("*[name='daren_title']").val());
	query.reason = $.trim($("*[name='reason']").val());


	var ajaxurl = APP_ROOT+"/index.php?ctl=daren&act=submit_daren";
	$.ajax({
		url: ajaxurl,
		dataType: "json",
		data:query,
		type: "POST",
		success: function(ajaxobj){
			if(ajaxobj.status==1)
			{
				$.showSuccess("???????????????????????????????????????");
				$("*[name='daren_title']").val("");
				$("*[name='reason']").val("");
			}
			else if(ajaxobj.status==2)  //????????????
			{
				$.showErr(ajaxobj.info);
			}
			else
			{
				ajax_login();
			}
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}

function submit_message()
{
	$("#consult-add-form").bind("submit",function(){
		var ajaxurl = $(this).attr("action");
		var query = $(this).serialize() ;

		$.ajax({
			url: ajaxurl,
			dataType: "json",
			data:query,
			type: "POST",
			success: function(ajaxobj){
				if(ajaxobj.status==1)
				{
					$("#consult-add-form").find("*[name='title']").val("");
					$("#consult-add-form").find("*[name='content']").val("");
					$("#consult-add-form").find("*[name='verify']").val("");
					alert(ajaxobj.info);
					location.reload();
				}
				else
				{
					$.showErr(ajaxobj.info);
				}
			},
			error:function(ajaxobj)
			{
//				if(ajaxobj.responseText!='')
//				alert(ajaxobj.responseText);
			}
		});
		return false;
	});
}


function init_gotop()
{

	$(window).scroll(function(){

		var s_top = $(document).scrollTop()+$(window).height()-70;
		if($.browser.msie && $.browser.version =="6.0")
		{
			$("#gotop").css("top",s_top);
			if($(document).scrollTop()>0)
			{
				$("#gotop").css("visibility","visible");
			}
			else
			{
				$("#gotop").css("visibility","hidden");
			}
		}
		else
		{
			if($(document).scrollTop()>0)
			{
				if($("#gotop").css("display")=="none")
				$("#gotop").fadeIn();
			}
			else
			{
				if($("#gotop").css("display")!="none")
				$("#gotop").fadeOut();
			}
		}


	});

	$("#gotop").bind("click",function(){
		$("html,body").animate({scrollTop:0},"fast","swing",function(){});
	});
	var top = $(document).scrollTop()+$(window).height()-70;
	if($.browser.msie && $.browser.version =="6.0")
	{
		$("#gotop").css("top",top);
		if($(document).scrollTop()>0)
		{
			$("#gotop").css("visibility","visible");
		}
		else
		{
			$("#gotop").css("visibility","hidden");
		}
	}
	else
	{
		if($(document).scrollTop()>0)
		{
			if($("#gotop").css("display")=="none")
			$("#gotop").show();
		}
		else
		{
			if($("#gotop").css("display")!="none")
			$("#gotop").hide();
		}
	}


}

/**
 * ????????????
 */
function init_top_nav(){
	$("#header .main_snav ul:gt(0)").hide();
	if ($("#header .main_snav a.current").length > 0) {
		$("#header .main_snav ul").hide();
		$("#header .main_snav a.current").each(function(){
			var o = $(this);
			o.parent().show();
			$("#header .main_bar li").removeClass('current');
			$("#header .main_bar li.n_"+o.parent().attr("rel")).addClass('current');
		});
	}
	else{
		if ($("#header .main_bar li.current").length > 0) {
			$("#header .main_bar li.current").each(function(){
				var o = $(this);
				$("#header .main_snav ul").hide();
				$("#header .main_snav ul.n_" + o.attr("rel")).show();
			});
		}
		else{
			$("#header .main_snav ul:eq(0)").show();
			$("#header .main_bar li.n_"+$("#header .main_snav ul:eq(0)").attr("rel")).addClass('current');
		}
	}
	var clearHideSnavAct = null;
	$("#header .main_bar li").hover(function(){
		clearTimeout(clearHideSnavAct);
		var o = $(this);
		o.addClass("jcur");
		$("#header .main_snav ul").hide();
		$("#header .main_snav ul.n_"+o.attr("rel")).show();
	},function(){
		var hide = function(){
			var o = $("#header .main_bar li.current");
			$("#header .main_snav ul").hide();
			$("#header .main_snav ul.n_"+o.attr("rel")).show();
		};
		 $(this).removeClass("jcur");
		clearHideSnavAct = setTimeout(hide,100);
	});
	$("#header .main_snav ul").hover(function(){
		clearTimeout(clearHideSnavAct);
		$("#header .main_bar li.n_"+$(this).attr('rel')).addClass("jcur");
	},function(){
		$("#header .main_bar li.n_"+$(this).attr('rel')).removeClass("jcur");
		var o = $("#header .main_bar li.current");
		$("#header .main_snav ul").hide();
		$("#header .main_snav ul.n_"+o.attr("rel")).show();
	});


}

function skip_user_profile()
{
	var ajaxurl = APP_ROOT+"/index.php?ctl=ajax&act=gopreview";
	$.ajax({
		url: ajaxurl,
		dataType: "text",
		type: "POST",
		success: function(jumpurl){
			if(jumpurl!="")
			location.href = jumpurl;
		},
		error:function(ajaxobj)
		{
//			if(ajaxobj.responseText!='')
//			alert(ajaxobj.responseText);
		}
	});
}
//???????????????
function foramtmoney(price, len)
{
   len = len > 0 && len <= 20 ? len : 2;
   price = parseFloat((price + "").replace(/[^\d\.-]/g, "")).toFixed(len) + "";
   var l = price.split(".")[0].split("").reverse(),
   r = price.split(".")[1];
   t = "";
   for(i = 0; i < l.length; i ++ )
   {
      t += l[i] + ((i + 1) % 3 == 0 && (i + 1) != l.length ? "," : "");
   }
   var re = t.split("").reverse().join("") + "." + r;
   return re.replace("-,","-");
}

function jiajian(type){
	switch(type){
		case "jia" :
			$("#ten_value").val(parseInt($("#ten_value").val())+50);
		break;
		case "jian" :
			if(parseInt($("#ten_value").val())-50 >= 200)
				$("#ten_value").val(parseInt($("#ten_value").val())-50);
		break;
	}
}
function cancelReply(obj){
	$("#"+obj).remove();
}
function replyCommentSubmit(dealid,dataid,obj){
	var query = new Object();
		query.ctl = "ajax";
		query.act = "msg_reply";
		query.content = $("#"+obj).val();
		query.rel_id = dealid;
		query.pid = dataid;
		query.rel_table = "deal";
	if($.trim(query.content)==""){
		$.showErr(LANG['MESSAGE_CONTENT_EMPTY']);
		return false;
	}
	$.ajax({
		url:APP_ROOT+"/index.php",
		data:query,
		type:"post",
		dataType:"json",
		success:function(result)
		{
			if(result.status==1){
				alert(result.info);
				location.reload();
			}
			else{
				$.showErr(result.info);
			}
		}
	});
}
/**
 * ???????????????
 * @param {Object} num
 */
function formatNum(num) {
	num = String(num.toFixed(2));
	var re = /(\d+)(\d{3})/;
	while (re.test(num)) {
		num = num.replace(re, "$1,$2");
	}
	return num;
}

function te(){
	//
}
;/*
 * Lazy Load - jQuery plugin for lazy loading images
 *
 * Copyright (c) 2007-2013 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.appelsiini.net/projects/lazyload
 *
 * Version:  1.9.3
 *
 */

(function($, window, document, undefined) {
    var $window = $(window);

    $.fn.lazyload = function(options) {
        var elements = this;
        var $container;
        var settings = {
            threshold       : 20,
            failure_limit   : 0,
            event           : "scroll",
            effect          : "show",
            container       : window,
            data_attribute  : "src",
            skip_invisible  : true,
            appear          : null,
            load            : null,
            placeholder     : "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAANSURBVBhXYzh8+PB/AAffA0nNPuCLAAAAAElFTkSuQmCC"
        };

        
        if(options) {
            /* Maintain BC for a couple of versions. */
            if (undefined !== options.failurelimit) {
                options.failure_limit = options.failurelimit;
                delete options.failurelimit;
            }
            if (undefined !== options.effectspeed) {
                options.effect_speed = options.effectspeed;
                delete options.effectspeed;
            }

            $.extend(settings, options);
        }

        /* Cache container as jQuery as object. */
        $container = (settings.container === undefined ||
                      settings.container === window) ? $window : $(settings.container);

        /* Fire one scroll event per scroll. Not one scroll event per image. */
        if (0 === settings.event.indexOf("scroll")) {
            $container.bind(settings.event, function() {
                return update();
            });
        }

        this.each(function() {
            var self = this;
            var $self = $(self);
            $self.data("update",update);
            self.loaded = false;

            /* If no src attribute given use data:uri. */
            if ($self.attr("src") === undefined || $self.attr("src") === false) {
                if ($self.is("img") && !$self.hasClass("j_focus_img")) {
                    $self.attr("src", settings.placeholder);
                }
            }

            /* When appear is triggered load original image. */
            $self.one("appear", function() {
                
                if (!this.loaded) {
                    if (settings.appear) {
                        var elements_left = elements.length;
                        settings.appear.call(self, elements_left, settings);
                    }
                    $("<img />")
                        .bind("load", function() {

                            var original = $self.data(settings.data_attribute);
                            $self.hide();
                            if ($self.is("img")) {
                                $self.attr("src", original);
                            } else {
                                $self.css("background-image", "url('" + original + "')");
                            }
                            $self[settings.effect](settings.effect_speed);

                            self.loaded = true;

                            /* Remove image from array so it is not looped next time. */
                            var temp = $.grep(elements, function(element) {
                                return !element.loaded;
                            });
                            elements = $(temp);

                            if (settings.load) {
                                var elements_left = elements.length;
                                settings.load.call(self, elements_left, settings);
                            }
                        })
                        .attr("src", $self.data(settings.data_attribute));
                }
            });

            /* When wanted event is triggered load original image */
            /* by triggering appear.                              */
            if (0 !== settings.event.indexOf("scroll")) {
                $self.bind(settings.event, function() {
                    if (!self.loaded) {
                        $self.trigger("appear");
                    }
                });
            }
        });

        //?????????????????????
        function update() {
            var counter = 0;

            elements.each(function() {
                var $this = $(this);
                if(!!this.loaded){
                    return;
                }
                if (settings.skip_invisible && !$this.is(":visible")) {
                    return;
                }
                //alert(!$.rightoffold(this, settings));
                if ($.abovethetop(this, settings) ||
                    $.leftofbegin(this, settings)) {
                        /* Nothing. */
                } else if (!$.belowthefold(this, settings) &&
                    !$.rightoffold(this, settings)) {
                        $this.trigger("appear");
                        /* if we found an image we'll load, reset the counter */
                        counter = 0;
                } else {
                    if (++counter > settings.failure_limit) {
                        //return false;
                    }
                }
            });

        }



        /* Check if something appears when window is resized. */
        $window.bind("resize", function() {
            update();
            //alert(111)
        });

        /* With IOS5 force loading images when navigating with back button. */
        /* Non optimal workaround. */
        if ((/(?:iphone|ipod|ipad).*os 5/gi).test(navigator.appVersion)) {
            $window.bind("pageshow", function(event) {
                if (event.originalEvent && event.originalEvent.persisted) {
                    elements.each(function() {
                        $(this).trigger("appear");
                    });
                }
            });
        }

        /* Force initial check if images should appear. */
        $(document).ready(function() {
            update();
        });

        return this;
    };

    /* Convenience methods in jQuery namespace.           */
    /* Use as  $.belowthefold(element, {threshold : 100, container : window}) */

    $.belowthefold = function(element, settings) {
        var fold;
        //alert(window.innerHeight);
        if (settings.container === undefined || settings.container === window) {
            fold = (window.innerHeight ? window.innerHeight : $window.height()) + $window.scrollTop();
            
            //fold = $window.height() + $window.scrollTop();
        } else {
            fold = $(settings.container).offset().top + $(settings.container).height();
        }

        return fold <= $(element).offset().top - settings.threshold;
    };

    $.rightoffold = function(element, settings) {
        var fold;

        if (settings.container === undefined || settings.container === window) {
            //fold = $window.width() + $window.scrollLeft();
            fold = $(document).width() + $window.scrollLeft();
        } else {
            fold = $(settings.container).offset().left + $(settings.container).width();
        }
        
        return fold <= $(element).offset().left - settings.threshold;
    };

    $.abovethetop = function(element, settings) {
        var fold;

        if (settings.container === undefined || settings.container === window) {
            fold = $window.scrollTop();
        } else {
            fold = $(settings.container).offset().top;
        }

        return fold >= $(element).offset().top + settings.threshold  + $(element).height();
    };

    $.leftofbegin = function(element, settings) {
        var fold;

        if (settings.container === undefined || settings.container === window) {
            fold = $window.scrollLeft();
        } else {
            fold = $(settings.container).offset().left;
        }

        return fold >= $(element).offset().left + settings.threshold + $(element).width();
    };

    $.inviewport = function(element, settings) {
         return !$.rightoffold(element, settings) && !$.leftofbegin(element, settings) &&
                !$.belowthefold(element, settings) && !$.abovethetop(element, settings);
     };

    /* Custom selectors for your convenience.   */
    /* Use as $("img:below-the-fold").something() or */
    /* $("img").filter(":below-the-fold").something() which is faster */

    $.extend($.expr[":"], {
        "below-the-fold" : function(a) { return $.belowthefold(a, {threshold : 0}); },
        "above-the-top"  : function(a) { return !$.belowthefold(a, {threshold : 0}); },
        "right-of-screen": function(a) { return $.rightoffold(a, {threshold : 0}); },
        "left-of-screen" : function(a) { return !$.rightoffold(a, {threshold : 0}); },
        "in-viewport"    : function(a) { return $.inviewport(a, {threshold : 0}); },
        /* Maintain BC for couple of versions. */
        "above-the-fold" : function(a) { return !$.belowthefold(a, {threshold : 0}); },
        "right-of-fold"  : function(a) { return $.rightoffold(a, {threshold : 0}); },
        "left-of-fold"   : function(a) { return !$.rightoffold(a, {threshold : 0}); }
    });

})(jQuery, window, document);
;/*
 HTML5 Shiv v3.6.2pre | @afarkas @jdalton @jon_neal @rem | MIT/GPL2 Licensed
*/
(function(l,f){function m(){var a=e.elements;return"string"==typeof a?a.split(" "):a}function i(a){var b=n[a[o]];b||(b={},h++,a[o]=h,n[h]=b);return b}function p(a,b,c){b||(b=f);if(g)return b.createElement(a);c||(c=i(b));b=c.cache[a]?c.cache[a].cloneNode():r.test(a)?(c.cache[a]=c.createElem(a)).cloneNode():c.createElem(a);return b.canHaveChildren&&!s.test(a)?c.frag.appendChild(b):b}function t(a,b){if(!b.cache)b.cache={},b.createElem=a.createElement,b.createFrag=a.createDocumentFragment,b.frag=b.createFrag();
a.createElement=function(c){return!e.shivMethods?b.createElem(c):p(c,a,b)};a.createDocumentFragment=Function("h,f","return function(){var n=f.cloneNode(),c=n.createElement;h.shivMethods&&("+m().join().replace(/\w+/g,function(a){b.createElem(a);b.frag.createElement(a);return'c("'+a+'")'})+");return n}")(e,b.frag)}function q(a){a||(a=f);var b=i(a);if(e.shivCSS&&!j&&!b.hasCSS){var c,d=a;c=d.createElement("p");d=d.getElementsByTagName("head")[0]||d.documentElement;c.innerHTML="x<style>article,aside,figcaption,figure,footer,header,hgroup,nav,section{display:block}mark{background:#FF0;color:#000}</style>";
c=d.insertBefore(c.lastChild,d.firstChild);b.hasCSS=!!c}g||t(a,b);return a}var k=l.html5||{},s=/^<|^(?:button|map|select|textarea|object|iframe|option|optgroup)$/i,r=/^(?:a|b|code|div|fieldset|h1|h2|h3|h4|h5|h6|i|label|li|ol|p|q|span|strong|style|table|tbody|td|th|tr|ul)$/i,j,o="_html5shiv",h=0,n={},g;(function(){try{var a=f.createElement("a");a.innerHTML="<xyz></xyz>";j="hidden"in a;var b;if(!(b=1==a.childNodes.length)){f.createElement("a");var c=f.createDocumentFragment();b="undefined"==typeof c.cloneNode||
"undefined"==typeof c.createDocumentFragment||"undefined"==typeof c.createElement}g=b}catch(d){g=j=!0}})();var e={elements:k.elements||"abbr article aside audio bdi canvas data datalist details figcaption figure footer header hgroup mark meter nav output progress section summary time video",version:"3.6.2pre",shivCSS:!1!==k.shivCSS,supportsUnknownElements:g,shivMethods:!1!==k.shivMethods,type:"default",shivDocument:q,createElement:p,createDocumentFragment:function(a,b){a||(a=f);if(g)return a.createDocumentFragment();
for(var b=b||i(a),c=b.frag.cloneNode(),d=0,e=m(),h=e.length;d<h;d++)c.createElement(e[d]);return c}};l.html5=e;q(f)})(this,document);
