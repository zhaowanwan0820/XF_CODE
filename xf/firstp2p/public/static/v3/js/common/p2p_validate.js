//验证中间件
var validate_middleware = (function() {
	var __exports__ = {};
	var validate = {};
	var conf = {
		"email": ["邮箱", true, [1, 100], /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i, '邮箱地址无效'],
		"captcha": ["验证码", true, undefined, /^\d{4,10}$/, '验证码不正确'],
		"mobile": ["手机号", true, null, /^1[3456789]\d{9}$/, '手机号格式不正确'],
		"username": ["用户名", true, [4, 16], /^[a-zA-Z][\w\-]+$/, '用户名只允许字母、数字、下划线、横线组成，首位只能为字母, 且至少需要 4 个字符'],
		"password": ["密码", true, [5, 26], /^.{5,25}$/, '请输入5-25位数字、字母及常用符号'],
		"code": ["短信验证码", true, null, /^\d{6}$/, '请填写6位数字验证码'],
		"ID_card": ["身份证号码", true, null, /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/, '请输入正确的身份证号码'],
		"time": ["时间", true, null, /^([01]\d|2[0-3])(:[0-5]\d){1,2}$/, '请输入正确的时间,例:14:30或14:30:00'],
		"url": ["网址", true, null, /^(https?|ftp):\/\/[^\s]+$/i, '网址格式不正确'],
		"postcode": ["邮政编码", true, null, /^(https?|ftp):\/\/[^\s]+$/i, '邮政编码格式不正确'],
		"chinese": ["中文", true, null, /^[\u0391-\uFFE5]+$/, '请输入中文'],
		"chineseName": ["中文名", true, null, /^[\u0391-\uFFE5]{2,6}$/, '请输入2-6个汉字中文'],
		"address": ["地址", true, null, /^[\u0391-\uFFE5][\u0391-\uFFE5\d]+$/, '填写正确的地址'],
		"agreement": ["注册协议", true, null, /^.{1,10}$/, '填阅读注册协议'],
		"date": ["日期", true, null, /^[\u0391-\uFFE5][\u0391-\uFFE5\d]+$/, '日期格式:yyyy-mm-dd']
		//"custom": function(v) { return "自定义";},
		//"_": ["未知", false, null, null, '']
		//"描述": ["key 字符串", "名称", "是否检查为空 boolean true false" , "长度限定 数组 如 [1,100] 空 null", "正则 空 null", "正则错误信息"]
	}
	__exports__.REG = conf;
	__exports__.validate = function(options) {
		options = options || {};
		//给validate绑定方法
		return exe(validate, extend(conf, options));
	}
	//声明
	function exe(validate, hash) {
		var map = items2map(hash);
		for (var k in map) {
			var hash = map[k];
			validate[k] = currying(k, hash);
		}
		return validate;
	}
	//exe
	function currying(k, hash) {
		return function(v , that) {
			//兼容特例情况
			if (typeof hash == 'function') {
				return hash(v);
			}
			return checkField(k, v, hash ,that);
		}
	}
	function items2map(hash) {
		var map = {};
		for (var k in hash) {
			var item = hash[k];
			//排除非数组的情况
			if (!isArray(item)) {
				map[k] = item;
				continue;
			}
			map[k] = {
				"min"  : item[2] ? item[2][0] : false,
				"max"  : item[2] ? item[2][1] : false,
				"reg"  : item[3] || null,
				"exist": item[1],
				"name" : item[0] || '',
				"reg_msg": item[4] || ''
			};
		}
		return map;
	};
	//items2map
	function checkField(k, v, hash , that) {
		//增加动态验证规则
		if(!!that.setRule && !!that.setRule[k]){
			hash.reg = that.setRule[k];
		}

		//默认状态通过
		var ret = '', status = true;
		//v值为字符串或者是数字
		if (!(typeof v === 'string' || typeof v === 'number')) {
			return false;
		}
		var name = '';
		name = hash.name;
		v = v.toString();
		var len = v.length;
		if (hash.exist && v === '') {
			ret = name + '不能为空';
			status = false;
		} else if (hash.min && len < hash.min) {
			ret = name + '不能小于'+ hash.min +'位,'+ name +'长度为'+ hash.min +' ~ ' + hash.max +'位';
			status = false;
		} else if (hash.max && len > hash.max) {
			ret = name + '不能大于'+ hash.max +'位,'+ name +'长度为'+ hash.min +' ~ ' + hash.max +'位';
			status = false;
		} else if (hash.reg && !hash.reg.test(v)) {
			ret = hash.reg_msg;
			status = false;
		}
		return {
			"status"  : status,
			"msg"   : ret,
			"key"  : k
		};
	}
	//checkField
	function isArray(max) {
		return Object.prototype.toString.call(max) === "[object Array]";
	};
	//isArray
	//浅复制
	function extend(c, p) {
		for (var k in p) {
		//if (!(k in c)) {
		c[k] = p[k];
		//}
		}
		return c;
	}
	//extend
	return __exports__;
})()

function Util() {
	this.isObject = this.isType("Object")
	this.isString = this.isType("String")
	this.isArray = Array.isArray || this.isType("Array")
	this.isFunction = this.isType("Function")
}

Util.prototype = {
	//唯一标识
	guid: -1,
	isNumeric:function(input)
	{
		input = input.toString();
		return (input - 0).toString() == input && input.length > 0;
	},
	event: [],
	isType: function(type) {
		return function(obj) {
			return {}.toString.call(obj) == "[object " + type + "]"
		}
	},
	get_ajax: function() {
		this.ajax_singleton = this.ajax_singleton || this.ajax_case();
		return this.ajax_singleton;
	},
	ajax_case: function() {
		var xhr = false;
		try {
			xhr = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (trymicrosoft) {
			try {
				xhr = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (othermicrosoft) {
				try {
					xhr = new XMLHttpRequest();
				} catch (failed) {
					xhr = false;
				}
			}
		}
		return xhr;
	},
	//跨域用代理的问题,不同端口视为不同的域
	ajax: function(url, obj, fn) {
		if (!obj) {
			obj = {};
		}
		var timer = new Date();
		var get_str = '';
		if (obj.get) {
			get_str = url.indexOf('?') == -1 ? '?' : '&' + obj.get + '&tmp=' + timer.getTime();
		}
		url = url + get_str;
		req = this.get_ajax();
		if (!req) {alert("not support ajax"); return false;}
		if (!obj.method) {obj.method = "get";}
		if (!obj.data) {
			obj.data = null;
		} else {
			var cache = [];
			for (k in obj.data) {
				cache.push(k + '=' + obj.data[k]);
			}
			obj.data = cache.join('&');
		}
		var async = typeof obj.async === 'undefined' ? true : false;
		req.open(obj.method, url, async);
		req.onreadystatechange = function() {
			if (req.readyState == 4) {
				if(req.status == 200) {
					fn(req.responseText);
				}
			}
		};
		req.send(obj.data);
	},
	extend: function(target, source) {
		if (!this.isObject(source)) {
			return;
		}
		for (var k in source) {
			target[k] = source[k];
		}
		return target;
	},
	on: function(el, type, func, capture) {
		if (!el.getAttribute('data-guid')) {
			el.setAttribute('data-guid', ++this.guid);
		}
		this.event[this.guid] = this.event[this.guid] || {};
		this.event[this.guid][type] = this.event[this.guid][type] || [];
		this.event[this.guid][type].push(func);
		this.addEvent(el, type, func, capture);
	},
	off: function(el, type, func, capture) {
		var guid = el.getAttribute('data-guid');
		if (!guid) {
			return;
		}
		var list = this.event[guid] && this.event[guid][type];
		var len = list.length;
		if (typeof func == "undefined") {
			if (!list) {
				return;
			}
			//全部清除
			var fn = undefined;
			while( fn = list.shift() ) {
				this.removeEvent(el, type, fn, capture);
			}
		} else if (typeof func == "number" && func >= 0 && func < len) {
			this.removeEvent(el, type, list[func], capture);
		}else {
			this.removeEvent(el, type, func, capture);
		}
	},
	addEvent: function(el, type, func, capture) {
		if (el.attachEvent) {
			el.attachEvent("on" + type, func);
		} else {
			el.addEventListener(type, func, capture);
		}
	},
	removeEvent: function(el, type, func, capture) {
		if (el.detachEvent) {
			el.detachEvent("on" + type, func);
		} else {
			el.removeEventListener(type, func, capture);
		}
	},
	each: Array.prototype.forEach ?
		function(arr, fn) {
		arr.forEach(fn)
		}
		:
		function(arr, fn) {
		for (var i = 0; i < arr.length; i++) {
		fn(arr[i], i, arr)
		}
	}
}


function FormPage(conf) {
	//depend Util
	this.util = null;
	this.vld = null; //表单验证对象
	this.frm = null; //form 或者其他 dom wapper
	this.items = []; //内容列表
	this.conf = conf;
	this.hash_ajax = {}; //ajax队列
}

FormPage.prototype = {
	create: function() {
		this.util = this.conf.util;
		this.options = this.util.extend(this.options, this.conf);
		var frm = this.frm = this.options.frm;
		this.vld = this.options.vld;
		if (this.options.check) {
			this.check = this.options.check;
		}
		if (!this.frm || !this.vld) {
			//返回
			return;
		}
		//取得 items
		this.set_items();
		this.bind();
	},
	bind: function() {
		this.options.blur && this.bind_agent("blur");
		this.options.focus && this.bind_agent("focus");
		this.options.submit && this.bind_agent("submit");
	},
	bind_agent: function(type){
		var self = this;
		var hash = {
			"blur": function() {
				self.util.each(self.items, function(el, index) {
					self.util.off(el, "blur");
					self.util.on(el, "blur", function(ev) {
						return self._blur(el, ev);
					})
				})
			},
			"focus": function() {
				self.util.each(self.items, function(el, index) {
					self.util.off(el, "focus");
					self.util.on(el, "focus", function(ev) {
						return self._focus(el, ev);
					})
				})
			},
			"submit": function() {
				//分form 提交和其他形式提交 事件 form submit  Dom click
				if (self.frm.tagName == "FORM") {
					jQuery(self.frm).unbind().bind("submit" ,function(ev) {
						return self._submit(ev);
					});
				} else if(self.options.subtn) {
					self.util.off("click").on(self.options.subtn, "click", function(ev) {
						return self._submit(ev);
					})
				}
			}
		};
		(type in hash) && hash[type]();
	},
	_blur: function(el, ev) {
		if (this.ISSUBMITING) {
			return;
		}
		var ret = this.check(el, ev);
		var name = el.getAttribute('name');
		if (ret && ret.status === false && this.options.ajax && this.options.ajax[name]) {
			var fn = this.options.ajax[name];
			var hash = fn();
			if (!("data" in hash)) {
				hash.data = {};
				hash.data[name] = this.get_val(el);
			}
			this.ajax( hash );
		} else {
			return ret;
		}
	},
	_focus: function(el, ev) {
		if (this.ISSUBMITING) {
			return;
		}
		var ret = this.check(el, ev);
		var name = el.getAttribute('name');
		if (ret.status === false && this.options.ajax && this.options.ajax[name]) {
			var fn = this.options.ajax[name];
			var hash = fn();
			if (!("data" in hash)) {
				hash.data = {};
				hash.data[name] = this.get_val(el);
			}
			this.ajax( hash );
		} else {
			return ret;
		}
	},
	_submit: function(ev) {
		var me = this;
		if (this.ISSUBMITING) {
			return false;
		}
		this.ISSUBMITING = true;

		var items = this.get_items();
		var isError = false;
		var list = [];
		for (var i = 0, len = items.length; i < len; i++) {
			var el = items[i];
			var ret = this.check(el, ev, 'submit');
			if (this.options.stepone && ret.status === false) {
				this.ISSUBMITING = false;
				return false;
			}
			if (isError !== true && ret.status == false) {
				isError = true;
			}
		}

		if (isError) {
			this.ISSUBMITING = false;
			return false;
		}

		if (this.options.ajax) {
			var hash = this.options.ajax;
			for (var key in hash) {
				var fn = hash[key];
				var hash = fn();
				if (!("data" in hash)) {
					hash.data = {};
					hash.data[name] = this.get_val(name);
				}
				list.push(hash);
			}
			return this.ajax_queue(list, function() {
				me.ISSUBMITING = false;
			});
		}

		if (this.options.callback) {
			this.ISSUBMITING = false;
			return this.options.callback(this.get_vals(), this.items);
		}

		if (this.options.DEBUG) {
			this.ISSUBMITING = false;
			return false;
		}
		this.ISSUBMITING = false;
		return true;
	},
	ajax: function(hash /*url, data, method, cb*/) {
		var me = this;
		var data = hash.data || {};
		var method = hash.method || "get";
		var url = hash.url;
		var cb = hash.cb;
		//防止重复提交
		if (url in this.hash_ajax) {
			return;
		}
		this.hq_push(url, cb);
		this.util.ajax(url, {"method": method, "data": data, "async": hash.async}, function(data) {
			me.hq_unpush(url)(data);
		});
	},
	ajax_queue: function(list) {
		// {url, data, method, cb}
		for (var i = 0, len = list.length; i < len; i++) {
			var hash = list[i];
			this.ajax( hash );
		}
		while(this.hash_ajax) {
			console.log('1');
		}
	},
	//hq == hash queue
	hq_push: function(key, val) {
		if (key in this.hash_ajax) {
			return;
		}
		this.hash_ajax[key] = val;
		this.hash_ajax.length = this.hash_ajax.length || 0;
		this.hash_ajax.length++;
	},
	//hq == hash queue
	hq_unpush: function(key) {
		if (!(key in this.hash_ajax)) {
			return;
		}
		var ret = this.hash_ajax[key];
		delete this.hash_ajax[key];
		this.hash_ajax.length = this.hash_ajax.length || 0;
		this.hash_ajax.length--;
		return ret;
	},
	set_items: function() {
		this.frm.tagName == "FORM" ? this.set_items_form() : this.set_items_dom();
	},
	get_items: function(swt){
		return this.items || this.set_items();
	},
	fresh: function(wapper) {
		//动态添加dom的情况
		//更新this.frm
		if (wapper && wapper.tagName) {
			this.frm = wapper;
		}
		//更新items
		this.set_items();
		this.bind();

	},
	set_items_form: function() {
		var frm = this.frm;
		var items = [];
		for (var i = 0, len = frm.length; i < len; i++) {
			var item = frm[i];
			if (item.getAttribute("name") && item.getAttribute(this.options.property)) {
				items.push(item);
			}
		}
		this.items = items;
		return items;
	},
	set_items_dom: function(dom) {
		var self = this;
		var action = dom.getAttribute('action');
		var method = dom.getAttribute('method');
		var inputs = dom.getElementsByTagName('input');
		var selects = dom.getElementsByTagName('select');
		var textareas = dom.getElementsByTagName('textarea');
		var items = [];
		var cache = [];
		inputs = Array.prototype.slice.call(inputs);
		selects = Array.prototype.slice.call(selects);
		textareas = Array.prototype.slice.call(textareas);
		cache = cache.concat(inputs);
		cache = cache.concat(selects);
		cache = cache.concat(textareas);
		this.util.each(cache, function (el, index) {
			var name = el.getAttribute("name");
			if (el.getAttribute("name") && el.getAttribute(self.options.property)) {
				items.push(el);
			}
		})
		this.items = items;
		return items;
	},
	// 打印表单值表 测试用
	obj2str: function() {
		var hash = this.get_vals();
		var cache = [];
		for(var k in hash) {
			cache.push(k + "='" + hash[k] + "'");
		}
		return cache.join(",");
	},
	get_val: function(el) {
		var name = undefined;
		var type = undefined;
		//radio 和 checkbox 用 get_vals(name)获取值
		if (typeof el == 'string') {
			name = el;
			return this.get_vals(name);
		}
		name = el.getAttribute("name");
		type = el.type.toLowerCase() || "";
		return (type == 'radio' || type == 'checkbox') ? (this.get_vals(name)) : el.value;
	},
	get_vals: function(key) {
		var items = this.items;
		var cnames = {};
		var obj = {};
		for (var i = 0, len = items.length; i < len; ++i) {
			var item = items[i];
			if (item.disabled) {
				continue;
			}
			var name = item.name;
			var type = item.type.toLowerCase() || "";
			var init = item.getAttribute(this.property);
			if (init && init == item.value) {
				item.value = "";
			}
			var val = item.value;
			if (type == "radio") {
				if (item.checked) {
					obj[name] = val;
				}
			} else if (type == "checkbox") {
				if (item.checked) {
					obj[name] = obj[name] || [];
					obj[name].push(val);
					cnames[name] = name;
				}
			} else {
				obj[name] = val;
			}
		}
		for (var k in cnames) {
			obj[cnames[k]] = obj[cnames[k]].join(",");
		}
		if (key) {
			return obj[key];
		}
		return obj;
	},
	check: function(el, ev, tag/*表单项验证*/) {
		//返回true  默认通过 有错误 false
		var vld = this.vld;
		var name = el.getAttribute("name");
		var type = el.type.toLowerCase() || "";
		var val = this.get_val(el);
		var ret = {
			"status": true,
			"msg": "",
			"key"  : name
		};

		if (vld && (name in vld)) {
			var hash = vld[name](val , this);
			ret = hash;
		}
		ret.el = el;
		ret.ev = ev;
		ret.val = val;
		//自定义验证
		if (this.options.custom_vld && this.options.custom_vld[name]) {
			return this.options.custom_vld[name](ret);
		}
		return ret;
	},
	/*默认配置*/
	options: {
		frm: null,
		property: "data-con",
		submit: true,
		blur: true,
		focus: true,
		DEBUG: false,
		ajax: false,
		vld: null, //表单验证
		cb: null,
		subtn: null, // 非form形式提交的button
		util: null,
		custom_vld: null, //自定义验证
		stepone: false //提交时产生错误时立刻返回
	}
}

function formpage(options) {
	var form = new FormPage(options.conf);
	if (options.rewrite) {
		var med = options.rewrite;
		for (var k in med) {
			form[k] = med[k];
		}
	}
	var _super = FormPage.prototype;
	form._super = _super;
	form.create();
	return form;
}
