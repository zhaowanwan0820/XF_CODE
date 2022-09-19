/**
 * 命名空间Firstp2p
 * @nampespace Firstp2p
 */

if (typeof Firstp2p == "undefined") {
	Firstp2p = {};
}

//闭包
(function() {

	if (Firstp2p.share) {
		return false;
	}
	//当前实例
	var currItem = null;
	//被实例化的次数
	var times = 0;
	/**
    * @module 组件名称，Share
    * @author looping

    * @param  {String} 可供jquery直接获取的id，例如 "#foo"(必填)
    * @param  {Object} Widget的配置参数(可选)
    * @constructor
    */
	var Share = function(ele, opts) {

		//是否为jQuery对象
		ele = !(ele instanceof $) ? $(ele) : ele;
		if (!ele.length) {
			return;
		}

		/**
		 * 组件被应用的元素，供其他方法使用
		 * @private
		 * @type {jQueryDom}
		 */
		this._ele = ele;
		/**
		 * 组件的配置项，供其他方法使用
		 * @private
		 * @type {Object}
		 */
		var defaults = {
			"static": null, //静态资源数组
			"id": "firstp2p_share", //组件加载dom ID
			"prefix": "bds_", //分享样式前缀
			"pop_id": "firstp2p_share_pop", //弹出dom ID
			"more_class" : "bds_more", //更多dom 样式
			"qrcode_class" : "bds_qrcode", //二维码点击button样式
			"pop_qrcode_id" : "firstp2p-share-qrcode",//二维码弹出dom ID
			"type" : "bds_tools", //共享图标类型 三种bds_tools_32 bds_tools_24 bds_tools 实际为样式名称
			"encode": true, //是否自动编码
			"share_con": { //自定义共享内容
				"url": (document.location.href),
				"title": (document.title),
				"content": "",
				"pic": ""
			},
			//共享图标html代码
			"tpl": '<div id="firstp2p_share" class="bdshare_t get-codes-bdshare"> <span class="bds_qrcode" title="分享到微信">&nbsp;</span></div>',
			//所有共享公用的弹出
			"pop_tpl": '<div id="firstp2p_share_pop">' + '<p>分享到：</p>' + '<ul>'
			+ '<li><a href="javascript:void(0);"  class="bds_qzone" data-cmd="qzone">QQ空间</a></li>'
			+ '<li><a href="javascript:void(0);"  class="bds_tsina" data-cmd="tsina">新浪微博</a></li>'
			//+ '<li><a href="javascript:void(0);"  class="bds_bdysc" data-cmd="bdysc">百度云收藏</a></li>'
			+ '<li><a href="javascript:void(0);"  class="bds_renren" data-cmd="renren">人人网</a></li>'
			+ '<li><a href="javascript:void(0);"  class="bds_tqq" data-cmd="tqq">腾讯微博</a></li>'
			//+ '<li><a href="javascript:void(0);"  class="bds_bdxc" data-cmd="bdxc">百度相册</a></li>'
			+ '<li><a href="javascript:void(0);"  class="bds_kaixin001" data-cmd="kaixin001">开心网</a></li>'
			//+ '<li><a href="javascript:void(0);"  class="bds_tqf" data-cmd="tqf">腾讯朋友</a></li>'
			//+ '<li><a href="javascript:void(0);"  class="bds_tieba" data-cmd="tieba">百度贴吧</a></li>'
			+ '<li><a href="javascript:void(0);"  class="bds_douban" data-cmd="douban">豆瓣网</a></li>'
			+ '<li><a href="javascript:void(0);"  class="bds_tsohu" data-cmd="tsohu">搜狐微博</a></li>'
			//+ '<li><a href="javascript:void(0);"  class="bds_sqq" data-cmd="sqq">QQ好友</a></li>'
			//+ '<li><a href="javascript:void(0);"  class="bds_thx" data-cmd="thx">和讯微博</a></li>'
			+ '</ul>' + '<p style="text-align:right; padding-right:20px;"><a href="http://www.firstp2p.com" >firstp2p.com</a></p><div style="clear:both;"></div></div>',
			"qrcode_tpl": '<div>'
			+ '<p><strong>分享到微信朋友圈</strong></p><a class="close" href="#" onclick="return false;">×</a>'
			+ '<div></div>'
			+ '<p>打开微信，点击底部的“发现”，使用“扫一扫”即可将网页分享至朋友圈。</p>'
			+ '</div>'

		};
		/**
		 * @property {String} name 名称
		 */
		this.name = "共享组件";
		/**
		 * 组件的配置项，供其他方法使用
		 * @private
		 * @type {Object}
		 */
		this._opts = $.extend(true, defaults, opts || {});
		/**
		 * 调用组件的初始化
		 */
		this._init();
	}

	$.extend(Share.prototype, {
		/**
		 * 组件初始化方法
		 * @method _init
		 * @return none
		 */
		_init: function() {
			var me = this;
			if (this._opts.static) {
				this._queue(this._opts.static, function(){
					times++;
					me._render();
				});
			} else {
				times++;
				me._render();
			}
		},
        /**
         * 异步加载队列
         * @method _queue
         * @param [list] 队列数组  callback() 回调
         */
        _queue: function(list, callback) {
            var self = this;
            if (!list.length) {
                if (typeof callback == 'function') {
                   callback();
                } else {
                    console.log('queue is finish');
                }
                return false;
            }
            var val = list.shift();
            Firstp2p.preLoad(val, function(){
                self._queue(list, callback);
            });
        },
		/**
		 * 组件ui的展现
		 *
		 * @method _render
		 * @return none
		 */
		_render: function() {
			this._ele.html(this._opts.tpl);
			if ($("#" + this._opts.pop_id).length == 0) {
				$(this._opts.pop_tpl).appendTo('body');
			}
			//二维码
			if ($("#" + this._opts.pop_qrcode_id).length == 0) {
				$(this._opts.qrcode_tpl).appendTo('body').attr({"id": this._opts.pop_qrcode_id}).css("display","none");

			}


			this._bind();
		},
		/**
		 * 私有方法，组件销毁，清理内存
		 * @private
		 * @method _destory
		 * @required
		 * @return none
		 */
		_destroy: function() {
			this._ele = null;
		},
		/**
		 * 私有属性，共享网站url模版
		 * @private
		 */
		_url_hash: {
			//新浪微博 无content
			"tsina": "http://v.t.sina.com.cn/share/share.php?url={$url}&title={$title}&content={$content}&pic={$pic}",
			//腾讯微博 无content
			"tqq": "http://v.t.qq.com/share/share.php?url={$url}&title={$title}&site={$content}&pic={$pic}",
			//开心网 无图片
			"kaixin001": "http://www.kaixin001.com/repaste/share.php?rurl={$url}&rcontent={$url}&rtitle={$title}",
			//豆瓣网 无图片
			"douban": "http://www.douban.com/recommend/?url={$url}&title={$title}",
			//人人 ok
			"renren": "http://widget.renren.com/dialog/share?resourceUrl={$url}&srcUrl=&title={$title}&content={$content}&pic={$pic}",
			//百度贴吧 ok
			"tieba": "http://tieba.baidu.com/f/commit/share/openShareApi?url={$url}&title={$title}&desc={$content}&pic={$pic}",
			//QQ好友 ok
			"sqq": "http://connect.qq.com/widget/shareqq/index.html?title={$title}&url={$url}&desc=&pics=&site=",
			//QQ空间 ok
			//http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?desc title  pics
			"qzone": "http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url={$url}&title={$title}&desc={$content}&pics={$pic}&summary=&site=",
			//优酷空间
			"youku": "http://i.youku.com/u/share/?url={$url}&content={$title}",
			//搜狐微博 ok 未登录验证图片功能
			"tsohu": "http://t.sohu.com/third/post.jsp?content=utf-8&url={$url}&title={$title}&content={$content}&pic={$pic}",
			//MSN
			"msn": "http://profile.live.com/badge/?url={$url}&title={$title}",
			//网易微博 默认显示来自站外的分享
			// 如需在来源处显示自己网站的信息，请发邮件到openapi@yeah.net申请；申请材料包括网站名称，网站域名。
			//http://open.t.163.com/components/onekey
			"t163": "http://t.163.com/article/user/checkLogin.do?link={$url}&source={$content}&info={$content}&images={$pic}"
		},
		/**
		 * 公有方法,解析模版 {$name}
		 * @private
		 */
		parse: function(str, hash) {
			if (typeof str != 'string') {
				return false;
			}
			var _str = str.replace(/\{\$(\w+)\}/g, function(_0, _1) {
				return (_1 in hash) ? hash[_1] : _1;
			});
			return _str;
		},
		_exec: function(el) {
			//console.log("_exec", this._opts);
			var tag = el.className.replace(/\w+_/, "");
			if (this._url_hash.hasOwnProperty(tag)) {
				var new_con ={};
				if (this._opts.encode) {
					for ( var k in this._opts.share_con) {
						var v = this._opts.share_con[k];
						new_con[k] = encodeURIComponent(v);
					}
				} else {
					new_con = this._opts.share_con;
				}
				var share_str = this.parse(this._url_hash[tag], new_con);
				if (share_str) {
					window.open(share_str);
				} else {
					//console.error("share error!");
				}
			} else {
				//console.log('not find ' + tag);
			}
		},
		/**
		 * @method _qr_code
		 * @param {Element} el DOM元素
		 * @desc 字符串生成二维码
		 */
		_qr_code: function() {
			//this._opts.share_con
			var pop_qrcode = $("#" + this._opts.pop_qrcode_id);
			//alert(this._opts.share_con.url);
			qr.table({
				value: this._opts.share_con.url,
				el: pop_qrcode.find("div").get(0), //dom
				size: 5 //单位像素
			});
		},
		/**
		 * @method _one
		 * @param
		 * @desc 构造函数被实例化时只在第一次实例化的时候执行一次的方法
		 */
		_one: function() {
			var pop = $("#" + this._opts.pop_id);
			var pop_qrcode = $("#" + this._opts.pop_qrcode_id);
			pop.find("a").on("click", function() {
				currItem._exec(this);
				//me._exec(this);
			});
			pop_qrcode.find("a").on("click", function() {
				pop_qrcode.hide();
			});
		},
		/**
		 * @method _bind
		 * @param
		 * @desc 绑定事件
		 */
		_bind: function() {
			var ele = $("#" + this._opts.id, this._ele);
			var more = ele.find("span." + this._opts.more_class);
			var timer = null;
			var pop = $("#" + this._opts.pop_id);
			var qr_code = $("." + this._opts.qrcode_class, ele);
			var me = this;
			var pop_qrcode = $("#" + me._opts.pop_qrcode_id);
			var out = function() {
				pop.hide();
			};
			ele.addClass(this._opts.type);
			ele.find("a").on("click", function() {
				me._exec(this);
			});
			//+ new Date().getTime()
			//有几个实例就绑定了多少次click事件，需要解决, 已解决
			if (times<=1) {
				this._one();
			}
			qr_code.on("click", function() {
				var h = ele.height();
				var pos = me._getPos(this);
				currItem = me;
				me._qr_code();
				pop_qrcode.css({
					top: (pos.y + h),
					left: pos.x
				}).show();
			});
			more.on("mouseover", function() {
				var h = ele.height();
				var pos = me._getPos(this);
				pop.find("ul").removeClass();
				pop.find("ul").addClass(me._opts.type);

				currItem = me;
				pop.css({
					top: (pos.y + h),
					left: pos.x
				}).show().hover(function() {
					clearTimeout(timer);
				}, function() {
					out();
				});
			});
			more.on("mouseout", function() {
				timer = setTimeout(function() {
					out();
				}, 10);
			});
		},
		/**
		 * @method _parseNum
		 * @param "v"
		 * @return number
		 * @desc 判断是否为数组，不是数组返回0
		 */
		_parseNum: function(v) {
			var a = parseInt(v, 10);
			return isNaN(a) ? 0 : a;
		},
		/**
		 * @method _getStyle
		 * @param {Element} el
		 * @return {Object}
		 * @desc 获取 el 元素的所有样式
		 */
		_getStyle: function(el) {
			var style, view = document.defaultView;
			if (view && view.getComputedStyle) {
				style = view.getComputedStyle(el, null);
			} else if (el.currentStyle) {
				style = el.currentStyle;
			} else {
				throw "无法动态获取DOM的实际样式属性";
			}
			return style;
		},
		/**
		 * @method _getBorderLeftTop
		 * @param {Element} el DOM元素
		 * @desc 获取 el 的四个border值
		 */
		_getBorderLeftTop: function(el) {
			var style = this._getStyle(el),
				top = this._parseNum(style["borderTopWidth"]),
				left = this._parseNum(style["borderLeftWidth"]);
			return {
				"top": top,
				"left": left
			};
		},
		/**
		 * @method _getPos
		 * @param  el DOM元素 refElement DOM元素
		 * @desc 获取 el 相对于 refElement的坐标
		 */
		_getPos: function(el, refElement) {
			var pos = {
				"x": 0,
				"y": 0
			};
			try {
				for (var o = el; o; o = o.offsetParent) {
					var s = "tagName=" + o.tagName + ",className=" + o.className;
					var x = 0,
						y = 0,
						a, b;
					if (o != el && o != refElement) {
						var border = this._getBorderLeftTop(o);
						a = border.left;
						b = border.top;
						x += a;
						y += b;
					}
					if (o != refElement) {
						pos.x += o.offsetLeft + (o != el ? x : 0);
						pos.y += o.offsetTop + (o != el ? y : 0);
					} else {
						var border = this._getBorderLeftTop(o);
						a = border.left;
						b = border.top;
						pos.x += a;
						pos.y += b;
						break;
					}
					if (o.tagName == "BODY" || o.tagName == "HTML") break;
				}
			} catch (ex) {
				//console.error(ex.message);
			}
			return pos;
		}
	});
	Firstp2p.share = function(ele, opts) {
		return new Share(ele, opts);
	}
})()