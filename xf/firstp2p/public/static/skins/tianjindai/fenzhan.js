
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
			autoClose: true, 
			duration: 100,
			fixed: false,
			delay: 50,
			mask: false,
			
			effect: 'slide',
			maskcss: {},
			align: {
				node: null,
				offset: [0, 0],
				points: ['bl', 'tl']
			}
		}, $config || {});
		s.triggerType = (s.triggerType == 'click') ? 'click' : 'mouseover';
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
	
	J_Accordion: function(o, $config) {
		var s = $.extend({
				id: "kcdns" + Math.random(),
				activeTriggerCls: 'ks-active', 
				triggerType: 'click',
				triggerCls: '.ks-switchable-trigger', 
				panelCls: '.ks-switchable-panel', 
				
				callback: '', 
				multiple: false, 
				easing: 'easeOutCirc', 
				effect: 'slidey', 
				duration: '3000', 
			}, $config || {}),
			active = s.activeTriggerCls;
		triggerType = ((s.triggerType == 'click') ? 'click' : 'mouseover');
		o.children(s.triggerCls).bind(triggerType, function(event) {
			event.preventDefault();
			s.ex.check($(this));
		});
		var fn = { 
			
			setMult: function(mult) {
				s.multiple = mult;
				s.ex.check($(this));
			},
			
			setEasing: function(easing) {
				s.easing = easing;
				s.ex.check($(this));
			},
			
			setDuration: function(dur) {
				s.duration = dur;
				s.ex.check($(this));
			},
			
			setEffect: function(eff) {
				s.effect = eff;
				s.ex.check($(this));
			}
		};
		s.fn = fn;
		
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
				if (triggerType == 'mouseover') { /*·ÀÖ¹ÔÚactive×´Ì¬Ê±£¬Êó±ê¾­¹ýÊ±»áÒþ²Ø»ò»Î¶¯*/
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
					if (s.fixed) s.fn.fixed.init(); 
					if (s.png && s.png_bg) s.fn.png.bg(); 
					if (s.png && s.png_tag) s.fn.png.tag(); 
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
					}); /*ÓÃä¯ÀÀÆ÷¿Õ°×Ò³Ãæ×÷Îª±³¾°*/
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
	
	J_Slidy: function(o, $config) {
		this.J_Carousel(o, $config);
	},
	
	J_Carousel: function(o, $config) {
		var s = $.extend({
					id: "kcdns" + Math.random(), 
					effect: 'none', 
					navCls: '.ks-switchable-nav', 
					contentCls: '.ks-switchable-content', 
					triggerType: 'click', 
					hasTriggers: true, 
					steps: 1, 
					viewSize: '', 
					activeIndex: 0, 
					activeTriggerCls: 'ks-active', 
					circular: false, 
					prevBtnCls: '', 
					nextBtnCls: '', 
					disableBtnCls: '', 
					duration: 1, 
					interval: 3, 
					autoplay: false, 
					countdown: false, 
					countdownFromStyle: '', 
					countdownCls: '.ks-switchable-trigger-mask', 
					callback: '', 
					placeholder: '/templets/qlgy/images/blank.gif', 
					imgload: false, 
					touch: true, 
					easing: 'easeOutCirc', 
					hoverStop: true, 
					reverse: false, 
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
				
				
				
				
				
				
				
				
				
				
				
				
				
			},
			T = false,
			$$ = this;
		s.fn = { 
			
			setDuration: function(dur) {
				s.duration = dur;
				s.ex.init();
			},
			
			setViewSize: function(size) {
				s.viewSize = size;
				s.ex.init();
			},
			
			setReverse: function(resverse) {
				s.reverse = resverse;
				s.ex.init();
			},
			
			setInter: function(inter) {
				s.interval = inter;
				s.ex.init();
			},
			
			setEasing: function(easing) {
				s.easing = easing;
				s.ex.init();
			},
			
			setEffect: function(effect, viewSize) {
				this.unbind();
				P.c.css({
					marginLeft: '0px',
					marginTop: '0px'
				});
				s.viewSize = viewSize;
				s.effect = effect;
				
				s.ex.init()
			},
			
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
			
			resize: function(sizex, sizey, viewSize) {
				P.c.parent().css({
					width: sizex,
					height: sizey
				});
				
				P.c.css({
					width: sizex,
					height: sizey
				});
				s.viewSize = viewSize ? viewSize : s.viewSize;
				s.ex.init();
			},
			
			unbind: function() {
				s.m.status = false;
				clearInterval(T);
				o.unbind();
				s.ex.unloadBtn();
				P.c.off();
				if (P.nli[0]) P.nli.unbind();
				if (s.circular) P.c.find("hr.hr_" + s.id).nextAll().add("hr.hr_" + s.id).remove();
			},
			
			bind: function() {
				if (!s.m.status) s.ex.init();
			},
			
			stop: function() {
				if (T) {
					s.autoplay = false;
					clearInterval(T);
				}
			},
			
			start: function() {
				s.autoplay = true;
				s.ex.autoplay();
			},
			
			to: function(i) {
				s.ex.myShow(i);
			},
			
			next: function() {
				s.m.gonext = true;
				s.ex.myShow(s.activeIndex + 1);
			},
			
			pre: function() {
				s.m.gonext = false;
				s.ex.myShow(s.activeIndex - 1);
			},
			
			getPointer: function() {
				if (s.m.direction()) {
					var p = parseInt(P.c.css("margin-" + s.m.direction()));

					return (Math.ceil(p / P.u) % P.page * (-1));
				} else {
					return s.activeIndex;
				}
			},
			
			relen: function() {
				P.cli = P.c.children().not("hr.hr_" + s.id);
				P.len = P.cli.length;
				P.page = Math.ceil(P.len / s.steps);
				P.bind = P.len > s.steps;
				if (P.bind) {
					P.n = (!P.n[0]) ? $("<ul></ul>").appendTo(o).addClass(s.navCls.substr(1)) : P.n; 
					P.nli = s.ex.resetNav(P.n.children());
				}
				$(P.nli).bind(s.triggerType, function() {
					var t = P.nli.index(this);
					if (!Touch.action && t != s.activeIndex) s.ex.myShow(t);
				}); 
				return P.bind;
			}
		};
		s.m = {
			status: false, 
			gonext: true, 
			
			direction: function() {
				var type = s.effect;
				return type ? (type == 'scrollx' ? 'left' : (type == 'scrolly') ? 'top' : 'left') : false;
			}
		};
		
		s.ex = {
			
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
				}); 
				else {

					if (s.effect == 'scrollx') {
						P.c.css({
							width: 999999+ "px"
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
				
				if (typeof(li[0]) != 'object' || P.n.html() == '') {
					for (var i = 0; i < P.page; i++) P.n.append("<li>" + (i + 1) + "</li>");
				}
				return P.n.children();
			},
			
			init: function() {

				
				P = {
					c: $(s.contentCls, o),
					n: $(s.navCls, o),
					hr: "<hr class=\"hr_" + s.id + "\" style=\"display:none;\">",
					s: s.duration * 1000,
					hover: false,
					k: false
				};
				
				if (P.c.children(".hr_" + s.id).size() > 0) {
					P.c.children().slice(P.c.children(".hr_" + s.id).index()).remove();
				}
				s.triggerType = (s.triggerType == 'click') ? 'click' : 'mouseenter';
				if (s.hasTriggers) {
					s.fn.relen()
				}
				
				if (s.imgload) {
					P.c.children().not("hr.hr_" + s.id).slice(2 * Math.ceil(P.c.children().size() / P.page)).find('img').one('load', function() {
						$(this).attr('data-src', $(this).attr('src')).attr('src', s.placeholder)
					})
				} 

				if (!s.fn.relen()) return false; 
				
				
				P.u = (s.viewSize) ? s.viewSize : (s.effect == 'scrollx') ? (P.cli.eq(0).outerWidth(true) * s.steps) : (P.cli.eq(0).outerHeight(true));
				P.px = P.u * P.page;

				this.setCom();
				this.loadBtn(); 
				Touch.init();
				
				if (s.circular && s.m.direction()) P.c.append(P.hr).append(P.cli.clone(true));
				s.m.status = true; 
				
				this.myShow(0, true)
				this.autoplay(); 
			},
			
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
			
			setInterval: function() {
				if (T) window.clearInterval(T);
				T = setInterval(function() {
					if (P.hover || !$$['inline'](o)) return;
					var type = s.m.direction();
					if (s.seamless && type) {
						var marginPx = parseInt(P.c.css("margin-" + type)); 
						if (marginPx % P.u == 0) {
							li = Math.abs(marginPx / P.u); 
							li = (li == P.page ? 0 : li) 
							s.activeIndex = li;
							s.ex.setForLiandBtn(li);
						}
						P.c.css("margin-" + type, (Math.abs(marginPx) == P.px ? (s.circular ? 0 : P.u - 1) : (marginPx - 1)) + "px"); 
					} else {
						s.ex.myShow((s.reverse) ? s.activeIndex - 1 : s.activeIndex + 1);
					}
				}, s.interval * 1000); 
			},
			myShow: function(toIndex, isFirst) { 

				var modfi = toIndex % P.page;
				modfi = (modfi < 0) ? modfi + P.page : modfi; 
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
			
			line: function(type, modfi, toIndex) {
				var marginPx = parseInt(P.c.css(type));
				if (s.circular) { 
					var css = (toIndex < 0) ? -(Math.abs(marginPx % P.px) + P.px) + "px" : (Math.abs(marginPx) >= P.px) ? (marginPx % P.px) + "px" : false;
					if (css) P.c.css(type, css); 
				}
				return -P.u * ((toIndex < 0 || (!s.circular)) ? modfi : (toIndex));
			},
			setForLiandBtn: function(index) {
				$(P.nli).eq(index).addClass(s.activeTriggerCls).siblings().removeClass(s.activeTriggerCls); 
				
				if (s.imgload) {
					
					$.fn.J_KCWidget_imgload(P.c.children().not("hr.hr_" + s.id).slice(0, (index + 2) * s.steps).find('img'));
					
				}
				
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
				var countdownCls = n_l_li.find(s.countdownCls); 
				if (!countdownCls[0]) countdownCls = $("<div class='ks-switchable-trigger-mask'></div>").prependTo(n_l_li);
				s.countdownFromStyle = (s.countdownFromStyle) ? s.countdownFromStyle : n_l_li.width(); 
				countdownCls.css({
					'width': s.countdownFromStyle + 'px'
				}).show().animate({
					width: '0'
				}, s.interval * 1000); 
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
					if (s.m.direction()) { 

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
				minDigit: 1, 
				utc: +8
			}, $config || {}),
			T_D = $(c.timeUnitCls.d, o), 
			T_H = $(c.timeUnitCls.h, o), 
			T_M = $(c.timeUnitCls.m, o), 
			T_S = $(c.timeUnitCls.s, o), 
			e = ct(gt(new Date((new Date(c.endTime).valueOf() + c.count)))), 
			b = ct(c.beginTime), 
			obj = [$(c.timebeginCls, o), $(c.timeRunCls, o), $(c.timeEndCls, o)], 
			obt = [T_D.length > 0, T_H.length > 0, T_M.length > 0], 
			ft = parseInt((new Date(e).getTime() - new Date(b).getTime()) / 1000), 
			d = new Date(d.getTimezoneOffset() * 60 * 1000 + d.getTime() + c.utc * 60 * 60 * 1000), 
			isstart = new Date(b).getTime() - d.getTime(), 
			isend = (new Date(e).getTime() - d.getTime()) / 1000, 
			css = new Array('none', 'inline');
		$(T_D).add(T_H).add(T_M).add(T_S).html(0);
		SetRemainTime();
		var InterValObj = window.setInterval(SetRemainTime, c.interval); 
		function SetRemainTime() {
			if (isstart > 0) { 
				set([1, 0, 0]);
			} else if (isend < 0) { 
				set([0, 0, 1]);
			} else if (ft > 0 && isend > 0) {
				set([0, 1, 0]);
				isend--;
				var d = Math.floor((isend / 3600) / 24), 
					h = f(Math.floor((isend / 3600) % 24)), 
					m = f(Math.floor((isend / 60) % 60)), 
					s = f(Math.floor(isend % 60)); 
				T_D.html(f(d));
				h = (obt[0]) ? h : d * 24 + h;
				T_H.html(f(h));
				m = (obt[1]) ? m : h * 60 + m;
				T_M.html(f(m));
				s = (obt[2]) ? s : m * 60 + s;
				T_S.html(f(s));
			} else { 
				set([0, 0, 1]);
				window.clearInterval(InterValObj); 
			}
		}

		function f(str) { 
			var seats = c.minDigit * 1 - String(str).length;
			for (var i = 0; i < seats; i++) str = "0" + String(str);
			return str < 0 ? 0 : str;
		}

		function set(s) {
			for (i in obj) obj[i].css('display', css[s[i]]);
		} 
		function ct(str) {
			return str.replace(/-/g, "/").replace(/ /g, ",");
		} 
		function gt(t) {
			return t.getFullYear() + "-" + (t.getMonth() + 1) + "-" + t.getDate() + " " + t.getHours() + ":" + t.getMinutes() + ":" + t.getSeconds();
		} 
	},
	
	init: function(o) {
		this.J_Slide = this.J_Slidy;
		
		this["J_" + o.attr("data-widget-type")](o, (new Function("return " + o.attr("data-widget-config")))() || {});
		
		
	},
	
	$: [],
	
	
	inline: function(o) {
		var d = o.offset(),
			t = $(window).scrollTop(),
			l = $(window).scrollLeft(),
			w = $(window).width(),
			h = $(window).height(),
			ow = o.width(),
			oh = o.height();

		if (
			(t + h) <= d.top || t >= (d.top + oh) || l >= (d.left + ow) || (l + w) <= d.left
		) return false;
		else return true;
	},
	
	f: {
		getScriptPath: function(a) {
			for (var b = document.getElementsByTagName("script"), d = 0; d < b.length; d++) {
				var c = b[d].src;
				if (c && 0 <= c.indexOf(a)) return c.substr(0, c.indexOf(a))
			}
			return ""
		}
	}
	
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


function png_bg_fixed(obj){
	var bg = obj.currentStyle.backgroundImage.toUpperCase();
	if (!bg) return;
	if (bg.match(/.PNG/i) != null){
		obj.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+bg.substring(5,bg.length-2)+"', sizingMethod='scale')";
		obj.style.backgroundImage = "url('')";
	}
}
function png_tag_fixed(obj){
	var imgName = obj.src.toUpperCase();
	if (imgName.substring(imgName.length-3, imgName.length) != 'PNG') return ;
	var imgStyle =  'width:' + obj.width + 'px; height:' + obj.height + 'px;'
		+((obj.align == 'left')?'float:left;':'')
		+((obj.align == 'right')?"float:right;":'')
		+((obj.parentElement.href)?'cursor:hand;':'')
		+'display:inline-block;' + obj.style.cssText
		+'filter:progid:DXImageTransform.Microsoft.AlphaImageLoader'
		+"(src=\'" + obj.src + "\', sizingMethod='scale');";
	$("<span "
		+ ((obj.id) ? "id='" + obj.id + "' " : "")
		+ ((obj.className) ? "class='" + obj.className + "' " : "")
		+ ((obj.title) ? "title='" + obj.title + "' " : "title='" + obj.alt + "' ")
		+ " style=\"" + imgStyle + "\"></span>").replaceAll(o);
}

$(document).ready(function() {
	$(".J_KCWidget").J_KCWidget();
	if(!window.XMLHttpRequest){
		$(".pngbg").each(function(){
				png_bg_fixed($(this)[0]);
		});
		$(".pngtag").each(function(){
				png_tag_fixed($(this)[0]);
		});
	}
});


function getClassMenu(t,o){
	$(o).bind("mouseleave",function(){
		J_KCWidget.$['pop1'].fn.close()
	})
}

//小圆圈按钮居中显示调整
function close_adv(){

	$(".top_adv").slideUp(2000);
	$("a.close").fadeOut(1000);
}