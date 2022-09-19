// 依赖jQuery 
var p2p_form = (function() {

	var exports = {};

	if (typeof console == "undefined") {
		console = function() {
			var f = function() {};
			return {
				log: f,
				warn: f,
				error: f
			};
		}();
	}

	//config
	function FormPage(conf) {
		this.frm = null;
		this.conf = _mixin(this.default, conf || {});
		this.property = this.conf.property;
		this.msgEl = null;
		this.items = null;
	}

	FormPage.prototype = {
		create: function(name/*字符串 或者 el*/) {
			var frm = this.frm = typeof name == "string" ? document.forms[name] : name;
			if (!frm) {
				console.error("form not found(" + name + ")");
				return;
			}
			this.msgEl = $(this.conf.msgEl, frm);
			var input = $("input[name]", frm);
			var select = $("select[name]", frm);
			var textarea = $("textarea[name]", frm);
			var items = [];
			var el_init = "_init";
			items = items.concat(input.get());
			items = items.concat(select.get());
			items = items.concat(textarea.get());
			this.items = items;
			//收集原始值
			for (var i = 0, len = input.length; i < len; ++i) {
				var item = input[i];
				if (item.type == "text") {
					if (item.value === "") {
						item.value = item.getAttribute(this.property) || item.value;
					}
				}
			}
			var me = this;
			$(items).blur(function() {
				me.check(this);
				var init = this.getAttribute(me.property);
				if (init && this.value === "") {
					this.value = init;
				}
			});
			$(items).focus(function() {
				var init = this.getAttribute(me.property);
				if (init && this.value == init) {
					this.value = "";
				}
			});
			$(frm).submit(function() {
				return me.do_submit(items);
			});
			this.init();
		},
		init: function() {},
		check: function() {},
		cb: function() {},
		showmsg: function(msg) {
			this.msgEl.html(msg);
		},
		queue_list: [],
		push_queue: function(fn) {
			this.queue_list.push(fn);
		},
		unpush_queue: function() {
			if (this.queue_list.length == 0) {
				return;
			}
			var item = this.queue_list.shift();
			item(this.queue_list);
			if (this.queue_list.length == 0) {
				this.submitting = false;
				//this.frm.submit();
			}

		},
		queue: function() {
			var cache = [];
			for(var i = 0, len = this.queue_list.length; i < len ; i++) {
				cache.push(this.queue_list[i]());
			}
			this.queue_list = [];
			return cache;
		},
		//获取url的get变量 name 变量名称，def_val没有找到变量时的默认值
		get: function(name, def_val) {
			var search_str = window.location.search;
			var re = "";
			if (typeof def_val != "undefined") {
				re = def_val;
			}
			var reg = new RegExp('(' + name + '=[^&]+)', "g");
			var str = search_str.toString().toLowerCase().match(reg);
			if (!str) {
				return re.toString();
			}
			var ret = str.toString().replace(name + "=", "");
			return ret;
		},
		manual_submit: function() {
			this.do_submit(this.items);
		},
		do_submit: function(items) {
			if (this.submitting) return false;
			var ren = true;
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
				if (!this.check(item, "submit")) {
					// console.log("item", item);
					ren = false;
					if (this.frm.getAttribute("data-single")) {
						return false;
					}
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
			// console.log("ren", ren);
			//在check的时候如果有队列产生(异步过程) 则停止提交过程
			if (this.queue_list.length) {
				//锁定提交过程
				this.submitting = true;
				return false;
			}
			if (ren) {
				if (!this.frm.getAttribute("action")) {
					this.cb('no action', this.frm);
					return false;
				}
				var curr_time = new Date();
				this.submitting = true;
				for (var k in cnames) {
					obj[cnames[k]] = obj[cnames[k]].join(",");
				}
				var url = this.frm.action;
				//自定义处理数据接口
				if (this.cook_data) {
					this.cook_data(obj);
				}
				var me = this;
				if (this.frm.getAttribute("data-ajax")) {
					$[this.frm.method.toLowerCase()](url, obj, function(data) {
						me.submitting = false;
						//me.cb.call(me, data, me.frm)
						me.cb(data, me.frm);
					});
				} else {
					return ren;
				}
			}
			return false;
		},
		"default": {
			property: "",
			msgEl: ""
		},
		"REG": {}
	};

	exports.doit = function(name, pageImp) {
		var page = new FormPage();
		_mixin(page, pageImp);
		page.create(name);
		return page;
	};

	// exports.do_ext = function(page, frm) {
	// 	_do(frm, page);
	// };

	exports.form2obj = function(frm, selector) {
		var $ = ("noConflict" in selector)
		? function(xpath, el) {
			return selector(xpath, el).get();
		}
		: function(xpath, el) {
			return selector(xpath, el);
		};
		return form2obj(frm, $);
	};

	exports.obj2form = function(frm, data) {
		return obj2form(frm, data);
	};

	//工具函数
	function _mixin(c, p) {
		for (var k in p) {
			//if (!(k in c)) {
			c[k] = p[k];
			//}
		}
		return c;
	}

	//其他进制到10进制的转换 最高支持到36进制
	function str2ten(str, ary) {
		if (typeof str !== "string" || str === "") {
			return "";
		}
		var map = {
			"0": 0,"1": 1,"2": 2,"3": 3,"4": 4,"5": 5,"6": 6,"7": 7,"8": 8,"9": 9,"a": 10,
			"b": 11,"c": 12,"d": 13,"e": 14,"f": 15,"g": 16,"h": 17,"i": 18,"j": 19,"k": 20,
			"l": 21,"m": 22,"n": 23,"o": 24,"p": 25,"q": 26,"r": 27,"s": 28,"t": 29,"u": 30,
			"v": 31,"w": 32,"x": 33,"y": 34,"z": 35
		};
		var bits = str.split("").reverse();
		var val = 0;
		var pow = 1;
		for (var i = 0, len = bits.length; i < len; i++) {
			if (i) {
				pow = pow * ary;
			}
			val = val + (map[bits[i]] || bits[i]) * pow;
		}
		return val;
	}
	//编码
	function encode(str) {
		if (typeof str !== "string" || str === "") {
			return "";
		}
		var bit1 = [], bit2 = [], val;
		for (var i = 0, len = str.length; i < len; i++) {
			val = str.charAt(i).charCodeAt();
			val = (Math.floor(val) < 36 ? "0" : "") + val.toString(36);
			val = val.split("");
			bit1.push(val[0]);
			bit2.push(val[1]);
		}
		return bit2.join("") + bit1.join("");
	}
	//解码
	function decode(str) {
		if (typeof str !== "string" || str === "") {
			return "";
		}
		var len = str.length;
		var bit = str.split("");
		var len = bit.length;
		var harf = Math.floor(len / 2);
		var cache = [];
		for (var i = 0; i < harf; i++) {
			var v = bit[harf + i];
			var _bit = (v === "0" ? "" : v) + bit[i];
			var code = str2ten(_bit, 36);
			cache.push(String.fromCharCode(code));
		}
		return cache.join("");
	}

	function form2obj(frm) {
		var obj ={}, _len = 0, cnames = {};
		for (var i = 0, len = frm.length; i < len; ++i) {
			var item = frm[i];
			var t, type = item.getAttribute("type");
			if (item.nodeName.toLowerCase() == "input"
				&& type && (t = type.toLowerCase(), t == "buttom" || t == "image" || t == "file")
			) {
				continue;
			}
			if (item.disabled || !item.getAttribute('name')) {
				continue;
			}
			_len++;
			var name = item.name;
			var type = "";
			if (item.getAttribute("type")) {
				type = item.getAttribute("type").toLowerCase();
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
			obj[cnames[k]] = obj[cnames[k]].join(',');
		}
		if (_len === 0) {
			obj = null;
		}
		return obj;
	}

	function obj2form(frm, data) {
		for (var k in data) {
			var item = frm[k];
			var val = data[k];
			if (item) {
				if (item.nodeName) {
					//看节点是否有type 属性，input(非radio, 非checkbox)select textarea 无,直接用item.value赋值
					var type = "";
					if (item.getAttribute("type")) {
						type = item.getAttribute("type").toLowerCase();
					}
					if (type != "radio" && type != "checkbox") {
						item.value = val;
					}
					if ((type == "radio" && item.value == val)
						|| (type == "checkbox" && ("," + val + ",").indexOf("," + item.value + ",") != -1)
					) {
						item.checked = true;
					} else if (type == "radio" || type == "checkbox") {
						item.checked = false;
					}
				} else if (item.length) {
					for (var j = 0, len = item.length; j < len; j++) {
						var node = item[j];
						var type = node.getAttribute("type").toLowerCase() || "";
						if ((type == "radio" && node.value == val)
							||(type == "checkbox" && ("," + val + ",").indexOf("," + node.value + ",") != -1)
						) {
							node.checked = true;
						} else if ("checked" in node) {
							node.checked = false;
						}
					}
				}
			}
		}
	}

	return exports;

})();