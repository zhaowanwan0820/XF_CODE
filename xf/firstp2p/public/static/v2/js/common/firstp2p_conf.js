//test
var firstp2p_conf = (function() {
	var __exports__ = {};
	var validate = {};
	var conf = {
		"email": ["邮箱", true, [1, 100], /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i, '邮箱地址无效'],
		"captcha": ["验证码", true, [4, 10], /^\d{4,10}$/, '验证码不正确'],
		"mobile": ["移动电话", true, null, /^1[3456789]\d{9}$/, '手机号格式不正确'],
		"username": ["用户名", true, [4, 16], /^[a-zA-Z][\w\-]+$/, '用户名只允许字母、数字、下划线、横线组成，首位只能为字母, 且至少需要 4 个字符'],
		"password": ["密码", true, [6, 26], /^.{6,26}$/, '密码只能为 6 - 26 位数字，字母及常用符号组成'],
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
		return function(v) {
			//兼容特例情况
			if (typeof hash == 'function') {
				return hash(v);
			}
			return checkField(k, v, hash);
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
	function checkField(k, v, hash) {
		var ret = '', status = false;
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
			status = true;
		} else if (hash.min && len < hash.min) {
			ret = name + '不能小于'+ hash.min +'位,'+ name +'长度为'+ hash.min +' ~ ' + hash.max +'位';
			status = true;
		} else if (hash.max && len > hash.max) {
			ret = name + '不能大于'+ hash.max +'位,'+ name +'长度为'+ hash.min +' ~ ' + hash.max +'位';
			status = true;
		} else if (hash.reg && !hash.reg.test(v)) {
			ret = hash.reg_msg;
			status = true;
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