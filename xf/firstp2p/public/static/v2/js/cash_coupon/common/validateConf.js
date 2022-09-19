if(typeof WXLC == "undefined"){
	WXLC = {};
}

WXLC.ValidateConf = {
	"email": ["邮箱", true, [1, 100], /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i, '邮箱地址无效'],
	"captcha": ["验证码", true, [4, 10], /^\d{4,10}$/, '验证码不正确'],
	// "mobile": ["移动电话", true, [4, 10], /^0?(13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[57])[0-9]{8}$/, '手机号格式不正确'],
	"mobile": ["移动电话", true, [4, 10], /^1[3456789]\d{9}$/, '手机号格式不正确'],
	"userName": ["用户名", true, [4, 16], /^[a-zA-Z][\w\-]{3,15}$/, '用户名请输入4-16位字母、数字、下划线、横线，首位只能为字母'],
	"password": ["密码", true, [5, 25], /^.{5,25}$/, '密码只能为 5 - 25 位数字，字母及常用符号组成'],
	"code": ["短信验证码", true, null, /^\d{6}$/, '请填写6位数字验证码'],
	"ID_card": ["身份证号码", true, null, /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/, '请输入正确的身份证号码'],
	"time": ["时间", true, null, /^([01]\d|2[0-3])(:[0-5]\d){1,2}$/, '请输入正确的时间,例:14:30或14:30:00'],
	"url": ["网址", true, null, /^(https?|ftp):\/\/[^\s]+$/i, '网址格式不正确'],
	"postcode": ["邮政编码", true, null, /^(https?|ftp):\/\/[^\s]+$/i, '邮政编码格式不正确'],
	"chinese": ["中文", true, null, /^[\u0391-\uFFE5]+$/, '请输入中文'],
	"chineseName": ["中文名", true, null, /^[\u0391-\uFFE5]{2,6}$/, '请输入2-6个汉字中文'],
	"address": ["地址", true, null, /^[\u0391-\uFFE5][\u0391-\uFFE5\d]+$/, '填写正确的地址'],
	"date": ["日期", true, null, /^[\u0391-\uFFE5][\u0391-\uFFE5\d]+$/, '日期格式:yyyy-mm-dd']
	//"custom": function(v) { return "自定义";},
	//"_": ["未知", false, null, null, '']
	//"描述": ["key 字符串", "名称", "是否检查为空 boolean true false" , "长度限定 数组 如 [1,100] 空 null", "正则 空 null", "正则错误信息"]
}
