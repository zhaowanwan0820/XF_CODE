/*
by hayden 2013.10.9
修正 Carousel fn接口，绑定、解除、向前、向后、取当前索引  等方法
by hayden 2013.10.9
更新Carousel 滚动逻辑
倒计时BUG修正，增加时区修正
by hayden 2013.10.7
增加Carousel的touch响应
by hayden 2012.12.25
切换无效果时自适应高度
by hayden 2012.12.18
修正 fade效果手动最终为消失的BUG
by hayden 2012.12.16
POP callback执行顺序修正
ie6 Compatible  组件增加外部事件  J_KCWidget.$[].fn.start();unbind();
by hayden 2012.12.10
修正png处理后图片路径会变换大小写导致LINUX服务器不显示图片问题
增加滚动效果、弹出层效果的外部接口 J_KCWidget.$[id].fn
增加pop组件 mask效果 （mask:true）
增加pop组件 fixed 浮动固定，并修正固定位置偏移问题
by hayden 2012.11.30
Widget 库
不再向老版本兼容
修改delay参数，不再用作播放停顿时间了，而做为播放延时时间，默认值为0
关于滚动切换效果中的 “标签  navCls、内容块 contentCls”的两个参数，不再兼容 triggerCls，panelCls
增加播放停顿时间参数：interval
POP弹出层组件更新：
增加参数：
autoClose:true/false  是否自动关闭()
delay:50   延时关闭
*/
var J_KCWidget = (J_KCWidget || {}).prototype = {
    J_Tabs: function(o, $config) {
        var s = $.extend({
            delay: 3,
            triggerType: 'click',
            autoplay: false
        }, $config || {});
        this.J_Carousel(o, s);
    },
    J_Drop: function(o, $config) {
        var s = $.extend({
                dmenu: 'ul'
            }, $config || {}),
            cls = this;
        $(o).children().each(function() {
            var li = $(this);
            cls.J_Popup(li.find(s.dmenu), s, li);
        });
    },
    J_Popup: function(o, $config, z) {
        cls = this;
        var s = $.extend({
            id: "kcdns_Popup_" + Math.random(),
            activeTriggerCls: 'ks-active',
            closeTrigger: '.trigger',
            closeTriggerType: 'mouseleave',
            triggerType: 'mouseover',
            trigger: '.trigger',
            callback: '',
            autoClose: false, //设置true 没有关闭按钮(关闭触发器为自己), false必须有关闭按钮(触发器非自己)
            duration: 100,
            fixed: false,
            delay: 50,
            mask: false,
            //parent:null,
            effect: 'slide', //fade slide none
            maskcss: {},
            align: {
                node: null,
                offset: [0, 0],
                points: ['cc', 'cc']
            }
        }, $config || {});
        s.triggerType = s.triggerType||'mouseover';
        s.triggerCls = ((z) ? z : $(s.trigger));
        s.tri = null;
        var flag = false;
        var odrop = false;
        var huang = 0;
        var fn = {
            setDelay: function(time) {
                s.delay = delay;
                s.start(z);
            },
            setFixed: function(isFixed) {
                s.fixed = isFixed;
                s.start(z);
            },
            setMask: function(mask) {
                s.mask = mask;
                s.start(z);
            },
            setAlign: function(align) {
                s.align = align;
                s.start(z);
            },
            setAutoClose: function(autoClose) {
                s.autoClose = autoClose;
                s.start(z)
            },
            setTriggerType: function(type) {
                s.triggerType = type;
                s.start(z)
            },
            setCloseTriggerType: function(type) {
                s.closeTriggerType = type;
                s.start(z)
            },
            setEffect: function(effect) {
                s.effect = effect;
                this.load(z);
            },
            setDuration: function(dur) {
                s.duration = dur;
                this.load(z);
            },
            load: function(t) {
                //if(s.boxFixed){$(z).css({'position':'fixed'})}
                odrop = true;
                if (flag) {
                    clearTimeout(flag);
                }
                if (s.tri != t && t) {
                    if (s.mask) this.mask_load();
                    s.tri = t;
                    t.addClass(s.activeTriggerCls);
                    this.reset_position();
                    s.effect == 'none' ? o.stop(true, true).show() : s.effect == 'fade' ? o.stop(true, true).fadeIn(s.duration) : s.effect == 'slide' ? o.stop(true, true).slideDown(s.duration) : o.stop.show();
                    if (s.fixed) {
                        J_KCWidget.J_Compatible(o.css({
                            'position': 'fixed',
                            'top': (parseInt(o.css('top')) - $(document).scrollTop()) + 'px'
                        }), {
                            'fixed': true
                        });
                    }
                    s.tri.add(o).bind('mouseenter', function(event) {
                        odrop = true;
                        if (flag) {
                            clearTimeout(flag);
                        }
                    });
                    if (typeof(s.callback) === "function") s.callback(t, o, 'show');
                }
                return false;
            },
            autohide: function() {
                if (flag) {
                    clearTimeout(flag);
                }
                flag = window.setTimeout(function() {
                    s.fn.close()
                }, s.delay);
            },
            reset_position: function() {
                var node = s.align.node;
                var tt = $((node) ? node : (s.tri) ? s.tri : s.triggerCls);
                var xy = (node == window) ? {
                    top: 0,
                    left: 0
                } : tt.offset();
                var u = s.align.offset,
                    p = s.align.points;
                var top = position(p[0], xy.top, tt.height(), 0, 1, true);
                top = position(p[1], top, o.height(), 0, 1, false);
                var left = position(p[0], xy.left, tt.width(), 1, 2, true);
                left = position(p[1], left, o.width(), 1, 2, false);
                var op = this.sumxy();
                top -= op.top;
                left -= op.left;
                if (node == window) {
                    top += $(window).scrollTop();
                    left -= $(window).scrollLeft();
                }
                o.css({
                    'position': 'absolute',
                    'z-index': '10000',
                    'top': (top + u[1]) + 'px',
                    'left': (left + u[0]) + 'px',
                    'clear': 'both'
                });
            },
            close: function() {
                if (flag) {
                    clearTimeout(flag);
                }
                odrop = false;
                var t = (s.tri) ? s.tri : s.triggerCls;
                t.removeClass(s.activeTriggerCls);
                o.css("display", "none").unbind('mouseenter');
                if (s.mask) this.mask_hide();
                if (typeof(s.callback) === "function") s.callback(t, o, 'close');
                s.tri = null;
            },
            maskCls: 'J_KCWidget_mask',
            mask_load: function() {
                
                $("<div class='" + this.maskCls + " J_KCWidget' data-widget-type='Compatible' data-widget-config=\"{fixed:true}\" style=\"position:absolute;left:0;top:0;width:100%;height:"+ $("body").height() + 'px'+";z-index:9999;background:#000;\"></div>")
                    .appendTo("body").css($.isEmptyObject(s.maskcss) ? {
                        opacity: 0.5
                    } : s.maskcss)
                    .hide().fadeIn();
            },
            mask_hide: function() {
                $("." + this.maskCls).fadeOut('normal', function() {
                    $(this).remove();
                })
            },
            sumxy: function() {
                var oo = o,
                    p = {
                        top: 0,
                        left: 0
                    };
                while (oo.parent()[0] != $(document.documentElement)[0]) {
                    oo = oo.parent();
                    if (oo.css("position") != "static") {
                        p.top += oo.offset().top;
                        p.left += oo.offset().left;
                    }
                }
                return p;
            }
        };
        s.fn = fn;
        s.close = function() {
            if (flag) {
                clearTimeout(flag);
            }
            odrop = false;
            var t = (s.tri) ? s.tri : s.triggerCls;
            t.removeClass(s.activeTriggerCls);
            o.hide().unbind('mouseenter');
            if (typeof(s.callback) === "function") s.callback(t, o, 'close');
            s.tri = null;
        };
        s.start = function() {
            $(s.closeTrigger).css({
                'display': 'none'
            })
            if (s.mask) s.autoClose = false;
            $(s.triggerCls).add(o).unbind();
            $(s.triggerCls).each(function() {
                var $this = $(this);
                $this.unbind().bind(s.triggerType, function(event) {
                    s.fn.load($this);
                });
                if (s.autoClose) {
                    $this.add(o).bind(s.closeTriggerType, function(event) {
                        s.fn.autohide();
                    });
                } else {

                    $(s.closeTrigger).css({
                        'display': 'block',
                        'position': 'absolute',
                        'z-index': '10000'
                    }).bind(s.closeTriggerType, function(event) {
                        s.fn.autohide();
                    });
                }
            });
        };
        s.unbind = function() {
            $(s.triggerCls).each(function() {
                $this.add(o).unbind();
                s.fn.close();
            });
        };
        s.start();
        $(window).resize(function() {
            if (odrop) {
                s.fn.reset_position();
            }
        });
        //$(window).scroll(function() {if (odrop&&s.fixed){s.fn.reset_position(); }});
        J_KCWidget.$[s.id] = s;

        function position(str, pi, pi2, a, b, iso) {
            pi2 = (iso) ? pi2 : 0 - pi2;
            switch (str.substring(a, b)) {
                case 't':
                    pi = pi;
                    break;
                case 'c':
                    pi = pi + (pi2 / 2);
                    break;
                case 'b':
                    pi = pi + pi2;
                    break;
                case 'l':
                    pi = pi;
                    break;
                case 'r':
                    pi = pi + pi2;
                    break;
            }
            return pi;
        }
    },
    ///////////////////////////////////////////////////////////////////
    J_Accordion: function(o, $config) {
        var s = $.extend({
                id: "kcdns" + Math.random(),
                activeTriggerCls: 'ks-active', //激活的按钮
                triggerType: 'click',
                triggerCls: '.ks-switchable-trigger', //按钮类
                panelCls: '.ks-switchable-panel', //图片(内容)类
                //activeIndex: 1,//默认第一个为激活
                callback: '', //回调
                multiple: false, //支持多开
                easing: 'easeOutCirc', //缓动函数
                effect: 'slidey', //动画方式
                duration: '3000', //动画时间
            }, $config || {}),
            active = s.activeTriggerCls;
        triggerType = ((s.triggerType == 'click') ? 'click' : 'mouseover');
        o.children(s.triggerCls).bind(triggerType, function(event) {
            event.preventDefault();
            s.ex.check($(this));
        });
        var fn = { //全局控制
            //设置是否支持多开
            setMult: function(mult) {
                s.multiple = mult;
                s.ex.check($(this));
            },
            //设置缓动曲线
            setEasing: function(easing) {
                s.easing = easing;
                s.ex.check($(this));
            },
            //设置动画时间
            setDuration: function(dur) {
                s.duration = dur;
                s.ex.check($(this));
            },
            //设置动画方式
            setEffect: function(eff) {
                s.effect = eff;
                s.ex.check($(this));
            }
        };
        s.fn = fn;
        //执行函数
        s.ex = {
            check: function(n) {
                var anim = (s.effect == "slidey") ? {
                    height: 'toggle'
                } : (s.effect == "slidex") ? {
                    width: 'toggle'
                } : (s.effect == "fade") ? {
                    opacity: 'toggle'
                } : {
                    width: 'toggle',
                    height: 'toggle'
                };
                var p = n.next(s.panelCls);
                if (triggerType == 'mouseover') { /*防止在active状态时，鼠标经过时会隐藏或晃动*/
                    n.addClass(active);
                    p.show(1000, "");
                } else {
                    n.toggleClass(active);
                    p.stop().animate(anim, s.duration, s.easing);
                }
                if (s.multiple == false) {
                    $(n).siblings("." + active).next(s.panelCls).stop().animate(anim)
                    $(n).siblings("." + active).removeClass(active)
                }
                if (typeof(s.callback) === "function") s.callback(n, p);
            }
        };
        J_KCWidget.$[s.id] = s;
        s.ex.check(o.children(s.triggerCls).eq(s.activeIndex - 1));
    },
    ///////////////////////////////////////////////////////////////////
    J_Compatible: function(o, $config) {
        var s = $.extend({
            id: "kcdns" + Math.random(),
            png: false,
            png_bg: false,
            png_tag: false,
            fixed: false,
            animate: false,
            duration: '50'
        }, $config || {});
        if ($config) $.extend(s, $config);
        s.fn = {
            init: function() {
                if (!window.XMLHttpRequest) {
                    if (s.fixed) s.fn.fixed.init(); //开启悬浮定位
                    if (s.png && s.png_bg) s.fn.png.bg(); //开启背景PNG透明
                    if (s.png && s.png_tag) s.fn.png.tag(); //开启IMG PNG透明
                }
            },
            fixed: {
                init: function() {
                    this.start();
                    this.top = parseInt(o.css('top'));
                    this.bottom = parseInt(o.css('bottom'));
                    this.setpos();
                    if (s.animate) {
                        var $$ = this;
                        $(window).scroll(function() {
                            $$.windowfn();
                        }).resize(function() {
                            $$.windowfn();
                        });
                    }
                },
                windowfn: function() {
                    if (s.animate) {
                        this.setpos();
                    }
                },
                start: function() {
                    $("html").css({
                        'background-image': 'url(about:blank)',
                        'background-attachment': 'fixed'
                    }); /*用浏览器空白页面作为背景*/
                    o.css('position', ((!window.XMLHttpRequest) ? 'absolute' : 'fixed'));
                    if (this.top) o.css('top', this.top + "px;");
                    if (this.bottom) o.css('bottom', this.bottom + "px;");
                },
                unbind: function() {
                    o.css('position', 'static');
                },
                setpos: function() {
                    if (s.animate) {
                        o.stop().animate({
                            'top': this.pos()
                        }, s.duration);
                    } else {
                        o[0].style.setExpression('top', 'eval(((documentElement.scrollTop + documentElement.clientHeight)>(documentElement.scrollTop+' + this.pos(true) + "+this.offsetHeight)) ? (documentElement.scrollTop+" + this.pos(true) + ') : (documentElement.scrollTop + documentElement.clientHeight-this.offsetHeight))');
                        $(window).scrollTop($(window).scrollTop());
                    }
                },
                pos: function(t) {
                    var n = '0';
                    if (t) {
                        n = (!isNaN(this.top)) ? this.top : ((!isNaN(this.bottom)) ? 'documentElement.clientHeight-this.offsetHeight-' + this.bottom : n);
                    } else {
                        n = $(window).scrollTop() + ((this.top) ? this.top : ((this.bottom) ? $(window).height() - o.height() - this.bottom : n));
                    }
                    return n;
                }
            },
            png: {
                bg: function() {
                    var obj = o[0],
                        bg = obj.currentStyle.backgroundImage;
                    if (!bg) return;
                    if (bg.toUpperCase().match(/.PNG/i) != null) {
                        obj.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + bg.substring(5, bg.length - 2) + "', sizingMethod='scale')";
                        obj.style.backgroundImage = "url('')";
                    }
                },
                tag: function() {
                    var obj = o[0],
                        imgName = obj.src.toUpperCase();
                    if (imgName.substring(imgName.length - 3, imgName.length) != 'PNG') return;
                    var imgStyle = 'width:' + obj.width + 'px; height:' + obj.height + 'px;' + ((obj.align == 'left') ? 'float:left;' : '') + ((obj.align == 'right') ? "float:right;" : '') + ((obj.parentElement.href) ? 'cursor:hand;' : '') + 'display:inline-block;' + obj.style.cssText + 'filter:progid:DXImageTransform.Microsoft.AlphaImageLoader' + "(src=\'" + obj.src + "\', sizingMethod='scale');";
                    $("<span " + ((obj.id) ? "id='" + obj.id + "' " : "") + ((obj.className) ? "class='" + obj.className + "' " : "") + ((obj.title) ? "title='" + obj.title + "' " : "title='" + obj.alt + "' ") + " style=\"" + imgStyle + "\"></span>").replaceAll(o);
                }
            }
        };
        s.fn.init();
        J_KCWidget.$[s.id] = s;
        return o;
    },
    ///////////////////////////////////////////////////////////////////
    J_Slidy: function(o, $config) {
        this.J_Carousel(o, $config);
    },
    ///////////////////////////////////////////////////////////////////
    J_Carousel: function(o, $config) {
        var s = $.extend({
                    id: "kcdns" + Math.random(), //id
                    effect: 'none', //效果横/纵
                    navCls: '.ks-switchable-nav', //下标按钮容器类名ul
                    contentCls: '.ks-switchable-content', //图片容器类名div
                    triggerType: 'click', //下标事件
                    hasTriggers: true, //是否需要下标
                    steps: 1, //一次滚动个数
                    viewSize: '', //对margin的手动调整//一屏多图时使用
                    activeIndex: 0, //当前下标单位ul
                    activeTriggerCls: 'ks-active', //当前下标按钮的li容器类名
                    circular: false, //是否不变方向循环播放
                    prevBtnCls: '', //上一个按钮类名
                    nextBtnCls: '', //下一个按钮类名
                    disableBtnCls: '', //灰色按钮类名
                    duration: 1, //动画速度
                    interval: 3, //停顿时间
                    autoplay: false, //自动播放
                    countdown: false, //计时器
                    countdownFromStyle: '', //
                    countdownCls: '.ks-switchable-trigger-mask', //下标每个按钮的标题div类名
                    callback: '', //回调方法
                    placeholder: '/templets/qlgy/images/blank.gif', //占位图地址
                    imgload: false, //是否启用延迟加载
                    touch: true, //是否支持移动端触屏
                    easing: 'easeOutCirc', //动画曲线
                    hoverStop: true, //鼠标悬停暂停播放
                    reverse: false, //反向播放
                    seamless: false
                },
                $config || {}),
            P = {
                c: null,
                n: null,
                hr: null,
                s: null,
                hover: null,
                k: null,
                u: null,
                px: null,
                cli: null,
                len: null,
                page: null,
                bind: null,
                nli: null,
                //c装载内容的容器
                //n装载按钮的容器
                //hr隐藏,记录hr_s.id
                //s一次动画执行时间
                //hover 悬停(辅助手势)
                //k动画开始/结束
                //u一屏尺寸
                //px总共尺寸
                //cli P.c.children()  img标题div
                //len P.cli.length  li个数(如果设置circular 包含隐藏的hr)
                //page 页数
                //bind li个数是否 大于 steps
                //nli  s.ex.resetNav(P.n.children());   li
            },
            T = false,
            $$ = this;
        s.fn = { //全局控制
            //设置动画时间
            setDuration: function(dur) {
                s.duration = dur;
                s.ex.init();
            },
            //设置一屏尺寸
            setViewSize: function(size) {
                s.viewSize = size;
                s.ex.init();
            },
            //设置反向
            setReverse: function(resverse) {
                s.reverse = resverse;
                s.ex.init();
            },
            //设置延时
            setInter: function(inter) {
                s.interval = inter;
                s.ex.init();
            },
            //设置动画曲线
            setEasing: function(easing) {
                s.easing = easing;
                s.ex.init();
            },
            //设置横/纵动画
            setEffect: function(effect, viewSize) {
                this.unbind();
                P.c.css({
                    marginLeft: '0px',
                    marginTop: '0px'
                });
                s.viewSize = viewSize;
                s.effect = effect;
                //if(s.effect=="scrolly"){s.steps=1;this.relen();}
                s.ex.init()
            },
            //设置无缝滚动,一个参数是是否滚动,其他默认
            setSeamless: function(seam, interval, duration, steps) {
                clearInterval(T);
                if (!seam) {
                    s.interval = interval;
                    s.steps = steps;
                    s.duration = duration;
                } else {
                    s.interval = 0.01;
                }
                s.seamless = seam;
                s.ex.init();
            },
            setTriggers: function(hasTrig, triggerType) {
                s.hasTriggers = hasTrig;
                if (hasTrig)
                    s.triggerType = triggerType;
                s.ex.init();
            },
            setSteps: function(step) {
                if (typeof(step) === 'number') {
                    s.steps = step;
                    s.ex.init()
                }
            },
            setCircular: function(cir) {
                s.circular = cir;
                s.ex.init();
            },
            setHoverStop: function(stop) {
                this.unbind();
                s.hoverStop = stop;
                s.ex.init()
            },
            //根据横纵尺寸设置容器大小
            resize: function(sizex, sizey, viewSize) {
                P.c.parent().css({
                    width: sizex,
                    height: sizey
                });
                //P.c.css({width:(s.effect=='scrollx')?(P.cli.eq(0).outerWidth(true)*(P.page+1)+"px"):'100%',height:'100%'});
                P.c.css({
                    width: sizex,
                    height: sizey
                });
                s.viewSize = viewSize ? viewSize : s.viewSize;
                s.ex.init();
            },
            //解绑
            unbind: function() {
                s.m.status = false;
                clearInterval(T);
                o.unbind();
                s.ex.unloadBtn();
                P.c.off();
                if (P.nli[0]) P.nli.unbind();
                if (s.circular) P.c.find("hr.hr_" + s.id).nextAll().add("hr.hr_" + s.id).remove();
            },
            //绑定
            bind: function() {
                if (!s.m.status) s.ex.init();
            },
            //取消滚动循环,暂停
            stop: function() {
                if (T) {
                    s.autoplay = false;
                    clearInterval(T);
                }
            },
            //继续滚动循环
            start: function() {
                s.autoplay = true;
                s.ex.autoplay();
            },
            //跳到第几帧动画
            to: function(i) {
                s.ex.myShow(i);
            },
            //跳到下一帧动画
            next: function() {
                s.m.gonext = true;
                s.ex.myShow(s.activeIndex + 1);
            },
            //跳到上一帧动画
            pre: function() {
                s.m.gonext = false;
                s.ex.myShow(s.activeIndex - 1);
            },
            //获取可能手势改变得到的当前下标
            getPointer: function() {
                if (s.m.direction()) {
                    var p = parseInt(P.c.css("margin-" + s.m.direction()));

                    return (Math.ceil(p / P.u) % P.page * (-1));
                } else {
                    return s.activeIndex;
                }
            },
            //生成按钮
            relen: function() {
                P.cli = P.c.children().not("hr.hr_" + s.id);
                P.len = P.cli.length;
                P.page = Math.ceil(P.len / s.steps);
                P.bind = P.len > s.steps;
                if (P.bind) {
                    P.n = (!P.n[0]) ? $("<ul></ul>").appendTo(o).addClass(s.navCls.substr(1)) : P.n; //避免navCls 不存在的情况
                    P.nli = s.ex.resetNav(P.n.children());
                }
                $(P.nli).bind(s.triggerType, function() {
                    var t = P.nli.index(this);
                    if (!Touch.action && t != s.activeIndex) s.ex.myShow(t);
                }); //unbind(s.triggerType).
                return P.bind;
            }
        };
        s.m = {
            status: false, //是否已经绑定动画
            gonext: true, //向右为真   左假
            //确定横滚竖滚
            direction: function() {
                var type = s.effect;
                return type ? (type == 'scrollx' ? 'left' : (type == 'scrolly') ? 'top' : 'left') : false;
            }
        };
        //行为过程
        s.ex = {
            //setDataSrc:function(datasrc){
            //////////////////
            //},
            //setBtnStyle:function(){//设置前后按钮样式
            //},
            //countdown:function(){//倒计时效果
            //  return false;
            //},
            //针对横滚竖滚设置图片和容器的css
            setCom: function() {
                P.cli.css({
                    float: "left",
                    display: "inline",
                    overflow: "hidden"
                });
                if (!s.m.direction()) P.cli.hide().css({
                    position: "absolute",
                    top: "0",
                    left: "0"
                }); //隐藏图片
                else {

                    if (s.effect == 'scrollx') {
                        P.c.css({
                            width: P.cli.eq(0).outerWidth(true) * (P.page + 1) + "px"
                        });
                        P.cli.css({
                            clear: "none"
                        })
                    } else {
                        P.cli.css({
                            clear: "none"
                        });
                        P.c.css({
                            width: P.cli.eq(0).outerWidth(true) * s.steps + "px"
                        });
                    };
                }
            },
            resetNav: function(li) {
                //部署nav
                if (typeof(li[0]) != 'object' || P.n.html() == '') {
                    for (var i = 0; i < P.page; i++) P.n.append("<li>" + (i + 1) + "</li>");
                }
                return P.n.children();
            },
            //初始化
            init: function() {

                //给c图片  ,n按钮 封装jq函数,          重置P.hr分割线 P.s动画时间  hover是否悬停鼠标 k停止
                P = {
                    c: $(s.contentCls, o),
                    n: $(s.navCls, o),
                    hr: "<hr class=\"hr_" + s.id + "\" style=\"display:none;\">",
                    s: s.duration * 1000,
                    hover: false,
                    k: false
                };
                //重置分割线
                if (P.c.children(".hr_" + s.id).size() > 0) {
                    P.c.children().slice(P.c.children(".hr_" + s.id).index()).remove();
                }
                s.triggerType = (s.triggerType == 'click') ? 'click' : 'mouseenter';
                if (s.hasTriggers) {
                    s.fn.relen()
                }
                //图片懒加载
                if (s.imgload) {
                    P.c.children().not("hr.hr_" + s.id).slice(2 * Math.ceil(P.c.children().size() / P.page)).find('img').one('load', function() {
                        $(this).attr('data-src', $(this).attr('src')).attr('src', s.placeholder)
                    })
                } //按需 (前两屏之外)图片lazyload

                if (!s.fn.relen()) return false; //不需要滚动 则返回
                //如果设置了viewSize,设置一屏尺寸
                //如果无缝滚动重新nli设置格式
                P.u = (s.viewSize) ? s.viewSize : (s.effect == 'scrollx') ? (P.cli.eq(0).outerWidth(true) * s.steps) : (P.cli.eq(0).outerHeight(true));
                P.px = P.u * P.page;

                this.setCom();
                this.loadBtn(); //设置绑定前后按钮
                //Touch.init();
                //给图片容器插入镜像图片div用hr分割
                if (s.circular && s.m.direction()) P.c.append(P.hr).append(P.cli.clone(true));
                s.m.status = true; //已绑定动画
                //this.myShow(s.activeIndex, true);//还原到播放序号
                this.myShow(0, true)
                this.autoplay(); //如果为自动播放，则响应鼠标滑入测出控制播放行为
            },
            //自动播放
            autoplay: function() {
                if (!s.autoplay) return;
                if (s.hoverStop) {
                    o.hover(function() {
                        P.hover = true;
                    }, function() {
                        P.hover = false;
                    });
                }
                this.setInterval();
            },
            //设置定时器
            setInterval: function() {
                if (T) window.clearInterval(T);
                T = setInterval(function() {
                    if (P.hover || !$$['inline'](o)) return;
                    var type = s.m.direction();
                    if (s.seamless && type) {
                        var marginPx = parseInt(P.c.css("margin-" + type)); //浮动距离
                        if (marginPx % P.u == 0) {
                            li = Math.abs(marginPx / P.u); //获取下标
                            li = (li == P.page ? 0 : li) //到头清零
                            s.activeIndex = li;
                            s.ex.setForLiandBtn(li);
                        }
                        P.c.css("margin-" + type, (Math.abs(marginPx) == P.px ? (s.circular ? 0 : P.u - 1) : (marginPx - 1)) + "px"); //无缝专用
                    } else {
                        s.ex.myShow((s.reverse) ? s.activeIndex - 1 : s.activeIndex + 1);
                    }
                }, s.interval * 1000); //设置定时器
            },
            myShow: function(toIndex, isFirst) { //动画执行

                var modfi = toIndex % P.page;
                modfi = (modfi < 0) ? modfi + P.page : modfi; //真实索引
                if (s.autoplay) this.setInterval();
                switch (s.effect) {
                    case "scrolly":
                    case "scrollx":
                        var type = "margin-" + s.m.direction(),
                            topx = this.line(type, modfi, toIndex),
                            steppx = Math.abs(parseInt(topx) - parseInt(P.c.css(type))),
                            css = {};
                        css[type] = topx + "px";
                        P.c.stop().animate(css, steppx > P.u ? (steppx / P.u / 2 + 0.5) * P.s : steppx / P.u * P.s, s.easing, function() {
                            P.k = false;
                            s.fn.getPointer();

                        });
                        break;
                    case "fade":
                        if (modfi == s.activeIndex && !isFirst) return;
                        else {
                            var thisli = P.cli;
                            thisli.removeClass("fade_activeIndex").fadeOut('fast');
                            thisli.eq(modfi).addClass("fade_activeIndex").fadeIn(P.s, function() {
                                P.k = false;
                            });
                        }
                        break;
                    case "none":
                        P.cli.hide().eq(modfi).show();
                        P.k = false;
                        break;
                    default:
                }
                this.setForLiandBtn(modfi);
                if (typeof(s.callback) === "function") s.callback(modfi, P.nli, P.cli);
            },
            /*
            type：滚动方式 top,left
            t_m:真实索引
            i:当前指针
            返回，目标位置 top || left
            */
            line: function(type, modfi, toIndex) {
                var marginPx = parseInt(P.c.css(type));
                if (s.circular) { //向上翻过0点，重置到镜像   否则 向下翻过末尾 回到原像原位置
                    var css = (toIndex < 0) ? -(Math.abs(marginPx % P.px) + P.px) + "px" : (Math.abs(marginPx) >= P.px) ? (marginPx % P.px) + "px" : false;
                    if (css) P.c.css(type, css); //动作前初始化当前位置
                }
                return -P.u * ((toIndex < 0 || (!s.circular)) ? modfi : (toIndex));
            },
            setForLiandBtn: function(index) {
                $(P.nli).eq(index).addClass(s.activeTriggerCls).siblings().removeClass(s.activeTriggerCls); //把该篇加上激活类样式,其他的去掉激活类样式
                //当懒加载时每次跳转预读两屏
                if (s.imgload) {
                    //if(!s.reverse)$.fn.J_KCWidget_imgload(P.c.children().not("hr.hr_"+s.id).slice(index-2,index+2).find('img'));
                    $.fn.J_KCWidget_imgload(P.c.children().not("hr.hr_" + s.id).slice(0, (index + 2) * s.steps).find('img'));
                    //else{$.fn.J_KCWidget_imgload(P.c.children().not("hr.hr_"+s.id).slice(P.page-2,P.page).find('img'));}
                }
                //上/下一张  按钮的绑定解绑
                s.activeIndex = index;
                if (!s.circular && s.disableBtnCls != "") {
                    this.loadBtn();
                    $(s.nextBtnCls + "," + s.prevBtnCls, o).removeClass(s.disableBtnCls);
                    if (s.activeIndex == P.page - 1) $(s.nextBtnCls, o).addClass(s.disableBtnCls).unbind();
                    if (s.activeIndex == 0) $(s.prevBtnCls, o).addClass(s.disableBtnCls).unbind();
                }
                if (!(s.countdown && s.autoplay)) return true;
                P.nli.find(s.countdownCls).stop(true, true).hide();
                var n_l_li = P.nli.eq(index);
                var countdownCls = n_l_li.find(s.countdownCls); //取trigger-mask对象
                if (!countdownCls[0]) countdownCls = $("<div class='ks-switchable-trigger-mask'></div>").prependTo(n_l_li);
                s.countdownFromStyle = (s.countdownFromStyle) ? s.countdownFromStyle : n_l_li.width(); //计算初始样式
                countdownCls.css({
                    'width': s.countdownFromStyle + 'px'
                }).show().animate({
                    width: '0'
                }, s.interval * 1000); //启动效果
            },
            loadBtn: function() {
                o.find(s.prevBtnCls).bind("click", function() {
                    if (!P.k) s.fn.pre();
                });
                o.find(s.nextBtnCls).bind("click", function() {
                    if (!P.k) s.fn.next();
                });
            },
            unloadBtn: function() {
                o.find(s.prevBtnCls + "," + s.nextBtnCls).unbind();
            }
        }
        var Touch = {
            getMousePoint: function(e) {
                var x = y = 0;
                if ("createTouch" in document) {
                    var evt = e.touches.item(0);
                    x = evt.pageX;
                    y = evt.pageY;
                } else {
                    x = e.clientX;
                    y = e.clientY;
                }
                return {
                    'left': x,
                    'top': y
                };
            },
            init: function() {
                Touch.action = false;
                var type = (s.m.direction()) ? s.m.direction() : "left";
                P.c.touchHandler(function(e) {
                    e.preventDefault();
                    if (Touch.action) return false;
                    Touch.action = P.hover = true;
                    Touch.sP = Touch.getMousePoint(e);
                    Touch.x = parseInt(P.c.stop().css("margin-" + type));
                    $("#show span").html(Touch.x);
                }, function(e) {
                    if (!Touch.action) return;
                    var nP = Touch.getMousePoint(e);
                    Touch.poor = Touch.sP[type] - nP[type];
                    P.hover = true;
                    if (!s.m.direction()) return true;
                    var m = Touch.x - Touch.poor;
                    if (m > 0 && s.circular) {
                        Touch.x -= P.px;
                        m -= P.px;
                    }
                    P.c.css("margin-" + type, m + 'px');
                    $("#show2 span").html((Touch.x - Touch.poor) + 'px');
                    $("#show3 span").html((Touch.poor) + 'px');
                }, function(e) {
                    s.m.gonext = (Touch.poor / Math.abs(Touch.poor)) < 0 ? false : true;
                    if (s.m.direction()) { //移动30像素距离内，回位

                        var pointer = s.fn.getPointer();
                        s.ex.myShow(pointer + ((s.m.gonext) ? 1 : 0) + ((!Touch.poor || Math.abs(Touch.poor) < 30) ? (s.m.gonext ? -1 : 1) : 0));
                    } else {
                        if (Touch.poor && Math.abs(Touch.poor) > 30) s.fn[s.m.gonext ? "next" : "pre"]();
                    }
                    Touch.action = P.hover = Touch.poor = false;
                }, window.XMLHttpRequest);
            }
        };
        J_KCWidget.$[s.id] = s;

        s.ex.init();
    },
    ///////////////////////////////////////////////////////////////////
    J_Countdown: function(o, $config) {
        var d = new Date();
        var c = $.extend({
                interval: 1000,
                count: 0,
                beginTime: gt(d),
                endTime: gt(new Date(d.valueOf())),
                timebeginCls: '.ks-countdown-start',
                timeRunCls: '.ks-countdown-run',
                timeEndCls: '.ks-countdown-end',
                timeUnitCls: {
                    d: '.ks-d',
                    h: '.ks-h',
                    m: '.ks-m',
                    s: '.ks-s'
                },
                minDigit: 1, //每个时间单位值显示的最小位数
                utc: +8
            }, $config || {}),
            T_D = $(c.timeUnitCls.d, o), //天数
            T_H = $(c.timeUnitCls.h, o), //小时
            T_M = $(c.timeUnitCls.m, o), //分钟
            T_S = $(c.timeUnitCls.s, o), //秒
            e = ct(gt(new Date((new Date(c.endTime).valueOf() + c.count)))), //格式化倒计时终止时间
            b = ct(c.beginTime), //格式化倒计时开始时间
            obj = [$(c.timebeginCls, o), $(c.timeRunCls, o), $(c.timeEndCls, o)], //开始前内容
            obt = [T_D.length > 0, T_H.length > 0, T_M.length > 0], //天分时秒表单存在否
            ft = parseInt((new Date(e).getTime() - new Date(b).getTime()) / 1000), //计算时间差,以秒为单位
            d = new Date(d.getTimezoneOffset() * 60 * 1000 + d.getTime() + c.utc * 60 * 60 * 1000), //将当前时间转换成指定时区时间
            isstart = new Date(b).getTime() - d.getTime(), //开始时间与当前时间的差值
            isend = (new Date(e).getTime() - d.getTime()) / 1000, //终止时间与当前时间的差值
            css = new Array('none', 'inline');
        $(T_D).add(T_H).add(T_M).add(T_S).html(0);
        SetRemainTime();
        var InterValObj = window.setInterval(SetRemainTime, c.interval); //间隔函数，1秒执行
        function SetRemainTime() {
            if (isstart > 0) { //如果开始时间晚于当前时间，则只显示“倒计时还未开始”层
                set([1, 0, 0]);
            } else if (isend < 0) { //如果终止时间早于当前时间，则只显示“倒计时结束了”层
                set([0, 0, 1]);
            } else if (ft > 0 && isend > 0) {
                set([0, 1, 0]);
                isend--;
                var d = Math.floor((isend / 3600) / 24), //计算天
                    h = f(Math.floor((isend / 3600) % 24)), //计算小时
                    m = f(Math.floor((isend / 60) % 60)), //计算分
                    s = f(Math.floor(isend % 60)); // 计算秒
                T_D.html(f(d));
                h = (obt[0]) ? h : d * 24 + h;
                T_H.html(f(h));
                m = (obt[1]) ? m : h * 60 + m;
                T_M.html(f(m));
                s = (obt[2]) ? s : m * 60 + s;
                T_S.html(f(s));
            } else { //剩余时间小于或等于0的时候，就停止间隔函数
                set([0, 0, 1]);
                window.clearInterval(InterValObj); //这里可以添加倒计时时间为0后需要执行的事件
            }
        }

        function f(str) { //补位
            var seats = c.minDigit * 1 - String(str).length;
            for (var i = 0; i < seats; i++) str = "0" + String(str);
            return str < 0 ? 0 : str;
        }

        function set(s) {
            for (i in obj) obj[i].css('display', css[s[i]]);
        } //各状态显示
        function ct(str) {
            return str.replace(/-/g, "/").replace(/ /g, ",");
        } //格式化时间
        function gt(t) {
            return t.getFullYear() + "-" + (t.getMonth() + 1) + "-" + t.getDate() + " " + t.getHours() + ":" + t.getMinutes() + ":" + t.getSeconds();
        } //取时间
    },
    ///////////////////////////////////////////////////////////////////
    init: function(o) {
        this.J_Slide = this.J_Slidy;
        //try {
        this["J_" + o.attr("data-widget-type")](o, (new Function("return " + o.attr("data-widget-config")))() || {});
        //}
        //catch (e){$("body").append('class=['+o.attr("class")+']  id=['+o.attr("id")+']  :  ' + e.description);};
    },
    ///////////////////////////////////////////////////////////////////
    $: [],
    ///////////////////////////////////////////////////////////////////
    //判断是否在浏览器可视范围中
    inline: function(o) {
        var d = o.offset(),
            t = $(window).scrollTop(),
            l = $(window).scrollLeft(),
            w = $(window).width(),
            h = $(window).height(),
            ow = o.width(),
            oh = o.height();

        if (
            (t + h) < d.top || t > (d.top + oh) || l > (d.left + ow) || (l + w) < d.left
        ) return false;
        else return true;
    },
    ///////////////////////////////////////////////////////////////////
    f: {
        getScriptPath: function(a) {
            for (var b = document.getElementsByTagName("script"), d = 0; d < b.length; d++) {
                var c = b[d].src;
                if (c && 0 <= c.indexOf(a)) return c.substr(0, c.indexOf(a))
            }
            return ""
        }
    }
    ///////////////////////////////////////////////////////////////////
};
(function($) {
    $.fn.J_KCWidget = function() {
        J_KCWidget.b = {
            me: "jquery.J_KCWidget_new.js",
            touch: "jquery.touchy.js"
        };
        J_KCWidget.b.base = J_KCWidget.f.getScriptPath(J_KCWidget.b.me);
        this.each(function() {
            J_KCWidget.init($(this));
        });
        return this;
    };
    $.fn.J_KCWidget_imgload = function(imgs) {

        imgs.each(function() {
            var th = $(this);
            if (th.attr('data-src') == '') return true;
            th.attr('src', th.attr('data-src')).attr('data-src', '')
        })
        return this;
    };
    $.fn.touchHandler = function(down, move, up, ismouse) {
        $(this).on("touchstart touchend" + ((ismouse) ? " mousedown mouseup" : ""), function(e) {
            e = e.originalEvent || e;
            switch (e.type) {
                case "mousedown":
                case "touchstart":
                    $(document).on('touchmove' + ((ismouse) ? " mousemove" : ""), function(e) {
                        e = e.originalEvent || e;
                        if (typeof(move) === "function") move(e);
                    }).on('touchend' + ((ismouse) ? " mouseup" : ""), function(e) {
                        e = e.originalEvent || e;
                        $(this).off('touchmove touchend ' + ((ismouse) ? " mousemove mouseup" : ""));
                        if (typeof(up) === "function") up(e);
                    });
                    if (typeof(down) === "function") down(e);
                    break;
            }
        });
    };
    jQuery.extend(jQuery.easing, {
        def: 'easeOutBounce',
        easeOutCirc: function(x, t, b, c, d) {
            return c * Math.sqrt(1 - (t = t / d - 1) * t) + b;
        },
        easeInExpo: function(x, t, b, c, d) {
            return (t == 0) ? b : c * Math.pow(2, 10 * (t / d - 1)) + b;
        }
    });
})(jQuery);
$(document).ready(function() {
    $(".J_KCWidget").J_KCWidget();
});

