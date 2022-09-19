;(function() {
    Array.prototype.remove = function(w) {
        var n = this.indexOf(w);
        if (n != -1) this.splice(n, 1);
    };

    Array.prototype.append = function(aAny) {
        for (var i = 0, len = aAny.length; i < len; i++)
            this.push(aAny[i]);

        return this;
    };

    window.Modernizr = function(a, b, c) {
        function C(a) {
            j.cssText = a
        }

        function D(a, b) {
            return C(n.join(a + ";") + (b || ""))
        }

        function E(a, b) {
            return typeof a === b
        }

        function F(a, b) {
            return !!~("" + a).indexOf(b)
        }

        function G(a, b) {
            for (var d in a)
                if (j[a[d]] !== c) return b == "pfx" ? a[d] : !0;
            return !1
        }

        function H(a, b, d) {
            for (var e in a) {
                var f = b[a[e]];
                if (f !== c) return d === !1 ? a[e] : E(f, "function") ? f.bind(d || b) : f
            }
            return !1
        }

        function I(a, b, c) {
            var d = a.charAt(0).toUpperCase() + a.substr(1),
                e = (a + " " + p.join(d + " ") + d).split(" ");
            return E(b, "string") || E(b, "undefined") ? G(e, b) : (e = (a + " " + q.join(d + " ") + d).split(" "), H(e, b, c))
        }

        function K() {
            e.input = function(c) {
                for (var d = 0, e = c.length; d < e; d++) u[c[d]] = c[d] in k;
                return u.list && (u.list = !! b.createElement("datalist") && !! a.HTMLDataListElement), u
            }("autocomplete autofocus list placeholder max min multiple pattern required step".split(" ")), e.inputtypes = function(a) {
                for (var d = 0, e, f, h, i = a.length; d < i; d++) k.setAttribute("type", f = a[d]), e = k.type !== "text", e && (k.value = l, k.style.cssText = "position:absolute;visibility:hidden;", /^range$/.test(f) && k.style.WebkitAppearance !== c ? (g.appendChild(k), h = b.defaultView, e = h.getComputedStyle && h.getComputedStyle(k, null).WebkitAppearance !== "textfield" && k.offsetHeight !== 0, g.removeChild(k)) : /^(search|tel)$/.test(f) || (/^(url|email)$/.test(f) ? e = k.checkValidity && k.checkValidity() === !1 : /^color$/.test(f) ? (g.appendChild(k), g.offsetWidth, e = k.value != l, g.removeChild(k)) : e = k.value != l)), t[a[d]] = !! e;
                return t
            }("search tel url email datetime date month week time datetime-local number range color".split(" "))
        }
        var d = "2.5.3",
            e = {}, f = !0,
            g = b.documentElement,
            h = "modernizr",
            i = b.createElement(h),
            j = i.style,
            k = b.createElement("input"),
            l = ":)",
            m = {}.toString,
            n = " -webkit- -moz- -o- -ms- ".split(" "),
            o = "Webkit Moz O ms",
            p = o.split(" "),
            q = o.toLowerCase().split(" "),
            r = {
                svg: "http://www.w3.org/2000/svg"
            }, s = {}, t = {}, u = {}, v = [],
            w = v.slice,
            x, y = function(a, c, d, e) {
                var f, i, j, k = b.createElement("div"),
                    l = b.body,
                    m = l ? l : b.createElement("body");
                if (parseInt(d, 10))
                    while (d--) j = b.createElement("div"), j.id = e ? e[d] : h + (d + 1), k.appendChild(j);
                return f = ["&#173;", "<style>", a, "</style>"].join(""), k.id = h, (l ? k : m).innerHTML += f, m.appendChild(k), l || (m.style.background = "", g.appendChild(m)), i = c(k, a), l ? k.parentNode.removeChild(k) : m.parentNode.removeChild(m), !! i
            }, z = function() {
                function d(d, e) {
                    e = e || b.createElement(a[d] || "div"), d = "on" + d;
                    var f = d in e;
                    return f || (e.setAttribute || (e = b.createElement("div")), e.setAttribute && e.removeAttribute && (e.setAttribute(d, ""), f = E(e[d], "function"), E(e[d], "undefined") || (e[d] = c), e.removeAttribute(d))), e = null, f
                }
                var a = {
                    select: "input",
                    change: "input",
                    submit: "form",
                    reset: "form",
                    error: "img",
                    load: "img",
                    abort: "img"
                };
                return d
            }(),
            A = {}.hasOwnProperty,
            B;
        !E(A, "undefined") && !E(A.call, "undefined") ? B = function(a, b) {
            return A.call(a, b)
        } : B = function(a, b) {
            return b in a && E(a.constructor.prototype[b], "undefined")
        }, Function.prototype.bind || (Function.prototype.bind = function(b) {
            var c = this;
            if (typeof c != "function") throw new TypeError;
            var d = w.call(arguments, 1),
                e = function() {
                    if (this instanceof e) {
                        var a = function() {};
                        a.prototype = c.prototype;
                        var f = new a,
                            g = c.apply(f, d.concat(w.call(arguments)));
                        return Object(g) === g ? g : f
                    }
                    return c.apply(b, d.concat(w.call(arguments)))
                };
            return e
        });
        var J = function(c, d) {
            var f = c.join(""),
                g = d.length;
            y(f, function(c, d) {
                var f = b.styleSheets[b.styleSheets.length - 1],
                    h = f ? f.cssRules && f.cssRules[0] ? f.cssRules[0].cssText : f.cssText || "" : "",
                    i = c.childNodes,
                    j = {};
                while (g--) j[i[g].id] = i[g];
                e.touch = "ontouchstart" in a || a.DocumentTouch && b instanceof DocumentTouch || (j.touch && j.touch.offsetTop) === 9, e.csstransforms3d = (j.csstransforms3d && j.csstransforms3d.offsetLeft) === 9 && j.csstransforms3d.offsetHeight === 3, e.generatedcontent = (j.generatedcontent && j.generatedcontent.offsetHeight) >= 1, e.fontface = /src/i.test(h) && h.indexOf(d.split(" ")[0]) === 0
            }, g, d)
        }(['@font-face {font-family:"font";src:url("https://")}', ["@media (", n.join("touch-enabled),("), h, ")", "{#touch{top:9px;position:absolute}}"].join(""), ["@media (", n.join("transform-3d),("), h, ")", "{#csstransforms3d{left:9px;position:absolute;height:3px;}}"].join(""), ['#generatedcontent:after{content:"', l, '";visibility:hidden}'].join("")], ["fontface", "touch", "csstransforms3d", "generatedcontent"]);
        s.flexbox = function() {
            return I("flexOrder")
        }, s["flexbox-legacy"] = function() {
            return I("boxDirection")
        }, s.canvas = function() {
            var a = b.createElement("canvas");
            return !!a.getContext && !! a.getContext("2d")
        }, s.canvastext = function() {
            return !!e.canvas && !! E(b.createElement("canvas").getContext("2d").fillText, "function")
        }, s.webgl = function() {
            try {
                var d = b.createElement("canvas"),
                    e;
                e = !(!a.WebGLRenderingContext || !d.getContext("experimental-webgl") && !d.getContext("webgl")), d = c
            } catch (f) {
                e = !1
            }
            return e
        }, s.touch = function() {
            return e.touch
        }, s.geolocation = function() {
            return !!navigator.geolocation
        }, s.postmessage = function() {
            return !!a.postMessage
        }, s.websqldatabase = function() {
            return !!a.openDatabase
        }, s.indexedDB = function() {
            return !!I("indexedDB", a)
        }, s.hashchange = function() {
            return z("hashchange", a) && (b.documentMode === c || b.documentMode > 7)
        }, s.history = function() {
            return !!a.history && !! history.pushState
        }, s.draganddrop = function() {
            var a = b.createElement("div");
            return "draggable" in a || "ondragstart" in a && "ondrop" in a
        }, s.websockets = function() {
            for (var b = -1, c = p.length; ++b < c;)
                if (a[p[b] + "WebSocket"]) return !0;
            return "WebSocket" in a
        }, s.rgba = function() {
            return C("background-color:rgba(150,255,150,.5)"), F(j.backgroundColor, "rgba")
        }, s.hsla = function() {
            return C("background-color:hsla(120,40%,100%,.5)"), F(j.backgroundColor, "rgba") || F(j.backgroundColor, "hsla")
        }, s.multiplebgs = function() {
            return C("background:url(https://),url(https://),red url(https://)"), /(url\s*\(.*?){3}/.test(j.background)
        }, s.backgroundsize = function() {
            return I("backgroundSize")
        }, s.borderimage = function() {
            return I("borderImage")
        }, s.borderradius = function() {
            return I("borderRadius")
        }, s.boxshadow = function() {
            return I("boxShadow")
        }, s.textshadow = function() {
            return b.createElement("div").style.textShadow === ""
        }, s.opacity = function() {
            return D("opacity:.55"), /^0.55$/.test(j.opacity)
        }, s.cssanimations = function() {
            return I("animationName")
        }, s.csscolumns = function() {
            return I("columnCount")
        }, s.cssgradients = function() {
            var a = "background-image:",
                b = "gradient(linear,left top,right bottom,from(#9f9),to(white));",
                c = "linear-gradient(left top,#9f9, white);";
            return C((a + "-webkit- ".split(" ").join(b + a) + n.join(c + a)).slice(0, -a.length)), F(j.backgroundImage, "gradient")
        }, s.cssreflections = function() {
            return I("boxReflect")
        }, s.csstransforms = function() {
            return !!I("transform")
        }, s.csstransforms3d = function() {
            var a = !! I("perspective");
            return a && "webkitPerspective" in g.style && (a = e.csstransforms3d), a
        }, s.csstransitions = function() {
            return I("transition")
        }, s.fontface = function() {
            return e.fontface
        }, s.generatedcontent = function() {
            return e.generatedcontent
        }, s.video = function() {
            var a = b.createElement("video"),
                c = !1;
            try {
                if (c = !! a.canPlayType) c = new Boolean(c), c.ogg = a.canPlayType('video/ogg; codecs="theora"').replace(/^no$/, ""), c.h264 = a.canPlayType('video/mp4; codecs="avc1.42E01E"').replace(/^no$/, ""), c.webm = a.canPlayType('video/webm; codecs="vp8, vorbis"').replace(/^no$/, "")
            } catch (d) {}
            return c
        }, s.audio = function() {
            var a = b.createElement("audio"),
                c = !1;
            try {
                if (c = !! a.canPlayType) c = new Boolean(c), c.ogg = a.canPlayType('audio/ogg; codecs="vorbis"').replace(/^no$/, ""), c.mp3 = a.canPlayType("audio/mpeg;").replace(/^no$/, ""), c.wav = a.canPlayType('audio/wav; codecs="1"').replace(/^no$/, ""), c.m4a = (a.canPlayType("audio/x-m4a;") || a.canPlayType("audio/aac;")).replace(/^no$/, "")
            } catch (d) {}
            return c
        }, s.localstorage = function() {
            try {
                return localStorage.setItem(h, h), localStorage.removeItem(h), !0
            } catch (a) {
                return !1
            }
        }, s.sessionstorage = function() {
            try {
                return sessionStorage.setItem(h, h), sessionStorage.removeItem(h), !0
            } catch (a) {
                return !1
            }
        }, s.webworkers = function() {
            return !!a.Worker
        }, s.applicationcache = function() {
            return !!a.applicationCache
        }, s.svg = function() {
            return !!b.createElementNS && !! b.createElementNS(r.svg, "svg").createSVGRect
        }, s.inlinesvg = function() {
            var a = b.createElement("div");
            return a.innerHTML = "<svg/>", (a.firstChild && a.firstChild.namespaceURI) == r.svg
        }, s.smil = function() {
            return !!b.createElementNS && /SVGAnimate/.test(m.call(b.createElementNS(r.svg, "animate")))
        }, s.svgclippaths = function() {
            return !!b.createElementNS && /SVGClipPath/.test(m.call(b.createElementNS(r.svg, "clipPath")))
        };
        for (var L in s) B(s, L) && (x = L.toLowerCase(), e[x] = s[L](), v.push((e[x] ? "" : "no-") + x));
        return e.input || K(), C(""), i = k = null,

        function(a, b) {
            function g(a, b) {
                var c = a.createElement("p"),
                    d = a.getElementsByTagName("head")[0] || a.documentElement;
                return c.innerHTML = "x<style>" + b + "</style>", d.insertBefore(c.lastChild, d.firstChild)
            }

            function h() {
                var a = k.elements;
                return typeof a == "string" ? a.split(" ") : a
            }

            function i(a) {
                var b = {}, c = a.createElement,
                    e = a.createDocumentFragment,
                    f = e();
                a.createElement = function(a) {
                    var e = (b[a] || (b[a] = c(a))).cloneNode();
                    return k.shivMethods && e.canHaveChildren && !d.test(a) ? f.appendChild(e) : e
                }, a.createDocumentFragment = Function("h,f", "return function(){var n=f.cloneNode(),c=n.createElement;h.shivMethods&&(" + h().join().replace(/\w+/g, function(a) {
                    return b[a] = c(a), f.createElement(a), 'c("' + a + '")'
                }) + ");return n}")(k, f)
            }

            function j(a) {
                var b;
                return a.documentShived ? a : (k.shivCSS && !e && (b = !! g(a, "article,aside,details,figcaption,figure,footer,header,hgroup,nav,section{display:block}audio{display:none}canvas,video{display:inline-block;*display:inline;*zoom:1}[hidden]{display:none}audio[controls]{display:inline-block;*display:inline;*zoom:1}mark{background:#FF0;color:#000}")), f || (b = !i(a)), b && (a.documentShived = b), a)
            }
            var c = a.html5 || {}, d = /^<|^(?:button|form|map|select|textarea)$/i,
                e, f;
            (function() {
                var a = b.createElement("a");
                a.innerHTML = "<xyz></xyz>", e = "hidden" in a, f = a.childNodes.length == 1 || function() {
                    try {
                        b.createElement("a")
                    } catch (a) {
                        return !0
                    }
                    var c = b.createDocumentFragment();
                    return typeof c.cloneNode == "undefined" || typeof c.createDocumentFragment == "undefined" || typeof c.createElement == "undefined"
                }()
            })();
            var k = {
                elements: c.elements || "abbr article aside audio bdi canvas data datalist details figcaption figure footer header hgroup mark meter nav output progress section summary time video",
                shivCSS: c.shivCSS !== !1,
                shivMethods: c.shivMethods !== !1,
                type: "default",
                shivDocument: j
            };
            a.html5 = k, j(b)
        }(this, b), e._version = d, e._prefixes = n, e._domPrefixes = q, e._cssomPrefixes = p, e.hasEvent = z, e.testProp = function(a) {
            return G([a])
        }, e.testAllProps = I, e.testStyles = y,  e
    }(this, this.document);

    window.zns = {};
    zns.site = {};
    zns.site.fx = {};
    //摆动运动
    zns.site.fx.swing = function(obj, cur, target, fnDo, fnEnd, acc) {
        if (zns.site.fx.browser_test.IE6) {
            fnDo && fnDo.call(obj, target);
            fnEnd && fnEnd.call(obj, target);
            return;
        }
        if (!acc) acc = 0.1;
        var now = {};
        var x = 0; //0-100

        if (!obj.__swing_v) obj.__swing_v = 0;

        if (!obj.__last_timer) obj.__last_timer = 0;
        var t = new Date().getTime();
        if (t - obj.__last_timer > 20) {
            fnMove();
            obj.__last_timer = t;
        }

        clearInterval(obj.timer);
        obj.timer = setInterval(fnMove, 20);

        function fnMove() {
            if (x < 50) {
                obj.__swing_v += acc;
            } else {
                obj.__swing_v -= acc;
            }

            //if(Math.abs(obj.__flex_v)>MAX_SPEED)obj.__flex_v=obj.__flex_v>0?MAX_SPEED:-MAX_SPEED;

            x += obj.__swing_v;

            //alert(x+','+obj.__swing_v);

            for (var i in cur) {
                now[i] = (target[i] - cur[i]) * x / 100 + cur[i];
            }


            if (fnDo) fnDo.call(obj, now);

            if ( /*Math.abs(obj.__swing_v)<1 || */ Math.abs(100 - x) < 1) {
                clearInterval(obj.timer);
                if (fnEnd) fnEnd.call(obj, target);
                obj.__swing_v = 0;
            }
        }
    };

    //弹性运动
    zns.site.fx.flex = function(obj, cur, target, fnDo, fnEnd, fs, ms) {
        if (zns.site.fx.browser_test.IE6) {
            fnDo && fnDo.call(obj, target);
            fnEnd && fnEnd.call(obj, target);
            return;
        }
        var MAX_SPEED = 16;

        if (!fs) fs = 6;
        if (!ms) ms = 0.75;
        var now = {};
        var x = 0; //0-100

        if (!obj.__flex_v) obj.__flex_v = 0;

        if (!obj.__last_timer) obj.__last_timer = 0;
        var t = new Date().getTime();
        if (t - obj.__last_timer > 20) {
            fnMove();
            obj.__last_timer = t;
        }

        clearInterval(obj.timer);
        obj.timer = setInterval(fnMove, 20);

        function fnMove() {
            obj.__flex_v += (100 - x) / fs;
            obj.__flex_v *= ms;

            if (Math.abs(obj.__flex_v) > MAX_SPEED) obj.__flex_v = obj.__flex_v > 0 ? MAX_SPEED : -MAX_SPEED;

            x += obj.__flex_v;

            for (var i in cur) {
                now[i] = (target[i] - cur[i]) * x / 100 + cur[i];
            }


            if (fnDo) fnDo.call(obj, now);

            if (Math.abs(obj.__flex_v) < 1 && Math.abs(100 - x) < 1) {
                clearInterval(obj.timer);
                if (fnEnd) fnEnd.call(obj, target);
                obj.__flex_v = 0;
            }
        }
    };

    zns.site.fx.buffer = function(obj, cur, target, fnDo, fnEnd, fs) {


        if (!fs) fs = 6;
        var now = {};
        var x = 0;
        var v = 0;

        if (!obj.__last_timer) obj.__last_timer = 0;
        var t = new Date().getTime();
        if (t - obj.__last_timer > 20) {
            fnMove();
            obj.__last_timer = t;
        }

        clearInterval(obj.timer);
        obj.timer = setInterval(fnMove, 20);

        function fnMove() {
            v = Math.ceil((100 - x) / fs);

            x += v;

            for (var i in cur) {
                now[i] = (target[i] - cur[i]) * x / 100 + cur[i];
            }


            if (fnDo) fnDo.call(obj, now);

            if (Math.abs(v) < 1 && Math.abs(100 - x) < 1) {
                clearInterval(obj.timer);
                if (fnEnd) fnEnd.call(obj, target);
            }
        }
    };

    zns.site.fx.linear = function(obj, cur, target, fnDo, fnEnd, fs) {
        if (zns.site.fx.browser_test.IE6) {
            fnDo && fnDo.call(obj, target);
            fnEnd && fnEnd.call(obj, target);
            return;
        }
        if (!fs) fs = 50;
        var now = {};
        var x = 0;
        var v = 0;

        if (!obj.__last_timer) obj.__last_timer = 0;
        var t = new Date().getTime();
        if (t - obj.__last_timer > 20) {
            fnMove();
            obj.__last_timer = t;
        }

        clearInterval(obj.timer);
        obj.timer = setInterval(fnMove, 20);

        v = 100 / fs;

        function fnMove() {
            x += v;

            for (var i in cur) {
                now[i] = (target[i] - cur[i]) * x / 100 + cur[i];
            }

            if (fnDo) fnDo.call(obj, now);

            if (Math.abs(100 - x) < 1) {
                clearInterval(obj.timer);
                if (fnEnd) fnEnd.call(obj, target);
            }
        }
    };

    zns.site.fx.stop = function(obj) {
        clearInterval(obj.timer);
    };

    //css3运动
    zns.site.fx.move3 = function(obj, json, fnEnd, fTime, sType) {
        var addEnd = zns.site.fx.addEnd;

        fTime || (fTime = 1);
        sType || (sType = 'ease');

        setTimeout(function() {
            setStyle3(obj, 'transition', sprintf('%1s all %2', fTime, sType));
            addEnd(obj, function() {
                setStyle3(obj, 'transition', 'none');
                if (fnEnd) fnEnd.apply(obj, arguments);
            }, json);

            setTimeout(function() {
                if (typeof json == 'function')
                    json.call(obj);
                else
                    setStyle(obj, json);
            }, 0);
        }, 0);
    };

    //监听css3运动终止
    (function() {
        var aListener = []; //{obj, fn, arg}
        if (!Modernizr.csstransitions) return;

        if (window.navigator.userAgent.toLowerCase().search('webkit') != -1) {
            document.addEventListener('webkitTransitionEnd', endListrner, false);
        } else {
            document.addEventListener('transitionend', endListrner, false);
        }

        function endListrner(ev) {
            var oEvObj = ev.srcElement || ev.target;
            //alert(aListener.length);
            for (var i = 0; i < aListener.length; i++) {
                if (oEvObj == aListener[i].obj) {
                    aListener[i].fn.call(aListener[i].obj, aListener[i].arg);
                    aListener.remove(aListener[i--]);
                }
            }
        }

        zns.site.fx.addEnd = function(obj, fn, arg) {
            if (!obj || !fn) return;
            aListener.push({
                obj: obj,
                fn: fn,
                arg: arg
            });
        }
    })();
    zns.site.fx.index_ppt = {};;
    (function() {
        var buffer = zns.site.fx.buffer;
        var flex = zns.site.fx.flex;
        var linear = zns.site.fx.linear;

        var pause = false;
        var fTime = 5000;

        zns.site.fx.index_ppt.pause = function() {
            pause = true;
        };
        zns.site.fx.index_ppt.gotoPlay = function() {
            pause = false;
        };

        zns.site.fx.index_ppt.create = function(aImgPath, aHref) {
            var oA = getEle('.slide a')[0];

            oA.href = aHref[0];

            var oDiv = getEle('.slide .slide_img')[0];
            //$(oDiv).css("background",'url('+ aImgPath[0] +') no-repeat center');
            //alert(aImgPath[0]);
            //生成number/number2的a
            // (function() {
            //     var oDiv1 = getEle('.slide .number')[0];
            //     var oDiv2 = getEle('.slide .number2')[0];

            //     var arr = [];

            //     for (var i = 0; i < aImgPath.length; i++) arr.push('<a href="javascript:;"></a>');

            //     oDiv1.innerHTML = '<div>' + arr.join('<span></span>') + '</div>';
            //     oDiv2.innerHTML = '<div>' + arr.join('<span></span>') + '</div>';

            //     oDiv2.children[0].children[0].className = 'active';
            // })();

            //正式开始
            var oPrev = getEle('.slide .leftBt')[0];
            var oNext = getEle('.slide .rightBt')[0];
            var aBtn = getEle('.slide .imgNum li');

            var now = 0;
            var ready = true;
            var W = oDiv.offsetWidth;
            var H = oDiv.offsetHeight;

            var autoTimer = null;

            // //左右按钮移入移出
            // oPrev.opacity = oNext.opacity = 0;
            // oPrev.onmouseover = oPrev.onmouseout = oNext.onmouseover = oNext.onmouseout = function(ev) {
            //     var oEvent = ev || event;
            //     moveBtnOpacity(this, oEvent.type.toLowerCase() == 'mouseover' ? 100 : 0);
            // };

            //小圆点移入移出
            // map(aBtn, function(i) {
            //     this.opacity = 0;
            //     this.onmouseover = this.onmouseout = function(ev) {
            //         var oEvent = ev || event;
            //         if (this.className != 'active')
            //             moveBtnOpacity(this, oEvent.type.toLowerCase() == 'mouseover' ? 100 : 0);
            //     }
            // });

            oDiv.onmouseover = function() {
                clearInterval(autoTimer);
            };
            oDiv.onmouseout = function() {
                autoTimer = setInterval(function() {
                    oNext.onclick && oNext.onclick();
                }, fTime);
            };
            oDiv.onmouseout();

            function tabABtn(index) {
                for (var i = 0; i < aBtn.length; i++) {
                    aBtn[i].className = '';
                    //moveBtnOpacity(aBtn[i], 0);
                }
                !!aBtn.length && (aBtn[index].className = 'active');
                //moveBtnOpacity(aBtn[index], 100);
            }

            
                //滑动
                (function() {
                    //修正结构
                    var oAImg = getEle('.slide .slide_img')[0];
                    
                    var oUl = document.createElement('ul');
                    oUl.style.cssText = sprintf('position:absolute; left:0; top:0; width:%1px; height:%2px;', W, H);

                    for (var i = 0; i < aImgPath.length; i++) {
                        var oLi = document.createElement('li');
                        oLi.innerHTML = '<a href="'+ aHref[i] +'"><img class="j_focus_img" data-src="'+ aImgPath[i] +'"></a>';
                        //$(oLi).html('<a href="'+ aHref[i] +'"><img src="'+ aImgPath[i] +'"</a>');
                        oLi.style.cssText = sprintf('float:left; width:%1px; height:%2px; ', W, H, aImgPath[i]);
                        oUl.appendChild(oLi);
                    }

                    oAImg.appendChild(oUl);
                    oAImg.style.overflow = 'hidden';
                   
                    var aLi = oUl.getElementsByTagName('li');

                    oUl.style.width = aLi.length * aLi[0].offsetWidth + 'px';
                    $.each(aHref , function(i , v){
                        
                        var $a = $(aLi).eq(i).find("a");
                        
                        if (v.indexOf('javascript:') == 0) {
                            $a.attr("target", "_self");
                            $a.attr("href", v.split("javascript:")[1]);
                            
                        }else if(v.indexOf('###') > -1){
                            $a.css("cursor" , 'default');
                            $a.removeAttr("target");
                        }  

                        else {
                            
                            $a.attr("target", "_blank");
                        }
                        
                    });
                    function tab(index) {
                        
                        $(".j_focus_img").eq(index).trigger("appear");
                        buffer(oUl, {
                            left: oUl.offsetLeft
                        }, {
                            left: -index * aLi[0].offsetWidth
                        }, function(now) {
                            this.style.left = now.left + 'px';
                        }, function() {
                            this.style.left = -index * aLi[0].offsetWidth + 'px';
                            
                        });

                        tabABtn(index);

                        now = index;
                    }

                    //添加事件
                    map(aImgPath, function(i) {
                        this.onmouseover = function() {
                            
                            tab(i);
                        };
                    });

                    oPrev.onclick = function() {
                        tab((now + aImgPath.length - 1) % aImgPath.length);
                    };
                    oNext.onclick = function() {
                        tab((now + 1) % aImgPath.length);
                    };
                })();
           
        }

        function moveBtnOpacity(obj, opacity) {
            buffer(obj, {
                opacity: obj.opacity
            }, {
                opacity: opacity
            }, function(now) {
                this.opacity = now.opacity;
                this.style.opacity = now.opacity / 100;
                this.style.filter = 'alpha(opacity:' + now.opacity + ')';
            }, null, 12);
        }
    })();

    function getEle(sExp, oParent) {
        var aResult = [];
        var i = 0;

        oParent || (oParent = document);

        if (oParent instanceof Array) {
            for (i = 0; i < oParent.length; i++) aResult = aResult.concat(getEle(sExp, oParent[i]));
        } else if (typeof sExp == 'object') {
            if (sExp instanceof Array) {
                return sExp;
            } else {
                return [sExp];
            }
        } else {
            //xxx, xxx, xxx
            if (/,/.test(sExp)) {
                var arr = sExp.split(/,+/);
                for (i = 0; i < arr.length; i++) aResult = aResult.concat(getEle(arr[i], oParent));
            }
            //xxx xxx xxx 或者 xxx>xxx>xxx
            else if (/[ >]/.test(sExp)) {
                var aParent = [];
                var aChild = [];

                var arr = sExp.split(/[ >]+/);

                aChild = [oParent];

                for (i = 0; i < arr.length; i++) {
                    aParent = aChild;
                    aChild = [];
                    for (j = 0; j < aParent.length; j++) {
                        aChild = aChild.concat(getEle(arr[i], aParent[j]));
                    }
                }

                aResult = aChild;
            }
            //#xxx .xxx xxx
            else {
                switch (sExp.charAt(0)) {
                    case '#':
                        return [document.getElementById(sExp.substring(1))];
                    case '.':
                        return getByClass(oParent, sExp.substring(1));
                    default:
                        return [].append(oParent.getElementsByTagName(sExp));
                }
            }
        }

        return aResult;
    }

    function map(arr, fn) {
        for (var i = 0; i < arr.length; i++) {
            fn.call(arr[i], i);
        }
    }



    function setStyle(obj, json) {
        if (obj.length)
            for (var i = 0; i < obj.length; i++) setStyle(obj[i], json);
        else {
            if (arguments.length == 2) //json
                for (var i in json) setStyle(obj, i, json[i]);
            else //name, value
            {
                switch (arguments[1].toLowerCase()) {
                    case 'opacity':
                        obj.style.filter = 'alpha(opacity:' + arguments[2] + ')';
                        obj.style.opacity = arguments[2] / 100;
                        break;
                    default:
                        if (typeof arguments[2] == 'number') {
                            obj.style[arguments[1]] = arguments[2] + 'px';
                        } else {
                            obj.style[arguments[1]] = arguments[2];
                        }
                        break;
                }
            }
        }
    }

    function getByClass(oParent, sClass) {
        var aEle = oParent.getElementsByTagName('*');
        var re = new RegExp('\\b' + sClass + '\\b', 'i');
        var aResult = [];

        for (var i = 0; i < aEle.length; i++) {
            if (re.test(aEle[i].className)) {
                aResult.push(aEle[i]);
            }
        }

        return aResult;
    }

    function setStyle3(obj, name, value) {
        obj.style['Webkit' + name.charAt(0).toUpperCase() + name.substring(1)] = value;
        obj.style['Moz' + name.charAt(0).toUpperCase() + name.substring(1)] = value;
        obj.style['ms' + name.charAt(0).toUpperCase() + name.substring(1)] = value;
        obj.style['O' + name.charAt(0).toUpperCase() + name.substring(1)] = value;
        obj.style[name] = value;
    }

    function rnd(n, m) {
        return Math.random() * (m - n) + n;
    }


    function sprintf(format) {
        var _arguments = arguments;

        return format.replace(/%\d+/g, function(str) {
            return _arguments[parseInt(str.substring(1))];
        });
    }

})();