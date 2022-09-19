var tmpData = {};

var Util = function(){};

Util.prototype = {
	getDomHeight: function() {
		//璁＄畻mask瀹介珮
		var me = this;
		return Math.floor(me.mGetDocumentRoot().scrollHeight);
		//Math.floor(me.mGetDocumentRoot().clientHeight)
	},
	mGetDocumentRoot:function(){
	return (document.documentElement) ? document.documentElement : document.body ;
	},
	mAddEvent: function(pObj, pType, pFn) {
		if (window.addEventListener) {
			pObj.addEventListener(pType, pFn, false);
		} else if (window.attachEvent) {
			pObj["e" + pType + pFn] = pFn;
			pObj[pType + pFn] = function() {
				pObj["e" + pType + pFn](window.event);
			}
			pObj.attachEvent("on" + pType, pObj[pType + pFn]);
		}
	},
	mRemoveEvent: function(pObj, pType, pFn) {
		if (window.removeEventListener) {
			pObj.removeEventListener(pType, pFn, false);
		} else if (pObj.detachEvent) {
			pObj.detachEvent("on" + pType, pObj[pType + pFn]);
			pObj[pType + pFn] = null;
			pObj["e" + pType + pFn] = null;
		}
	}
}

var util = new Util();

// function initFormModel() {
	var _inFormRequest = false;
	function _checkForm(opt_el) {
		var username = $("#input-username").val();
		var mobile = $("#input-mobile").val();
		var password = $("#input-password").val();
		var captcha = $("#input-captcha").val();
		_showFormError("");
		//前端验证
		if (username == "") {
			_showFormError("请填写用户名");
			return false;
		} else if (!WXLC.ValidateConf.userName[3].test(username)) {
			_showFormError(WXLC.ValidateConf.userName[4]);
			return false;
		} else if (mobile == "") {
			_showFormError("请填写手机号");
			return false;
		} else if (!WXLC.ValidateConf.mobile[3].test(mobile)) {
			_showFormError(WXLC.ValidateConf.mobile[4]);
			return false;
		}  else if (password == "") {
			_showFormError("请填写密码");
			return false;
		} else if (!WXLC.ValidateConf.password[3].test(password)) {
			_showFormError(WXLC.ValidateConf.password[4]);
			return false;
		}  else if (captcha == "") {
			_showFormError("请填写图片验证码");
			return false;
		} else if (!WXLC.ValidateConf.captcha[3].test(captcha)) {
			_showFormError(WXLC.ValidateConf.captcha[4]);
			return false;
		}
		return true;
	}
	function _showFormError(error) {
		$("span.single_msg").html(error);
	}
	function _enableFormSubmit(enable) {
		_inFormRequest = !enable;
		if (enable) {
			$("#cash-red-form input").removeAttr("disabled");
			$('#action-send-code').removeClass("gray").removeAttr('disabled');
		} else {
			$("#cash-red-form input").attr("disabled", "disabled");
			$('#action-send-code').addClass("gray").attr('disabled', 'disabled');
		}
	}
	function _sendFormVcodeStepOne() {
		if (_inFormRequest) return false;
		if (!_checkForm()) return false;
		var username = $("#input-username").val();
		var mobile = $("#input-mobile").val();
		var password = $("#input-password").val();
		var captcha = $("#input-captcha").val();
		var agree = $("#agree")[0].checked;
		var token_id = $("#token_id").val();
        var token = $("#token").val();
        var invite = $('#input-invite').val();
        if (agree != true) {
			_showFormError("请同意注册协议");
			return false;
		}
		tmpData = {username: username, mobile: mobile, password: password, captcha: captcha, token_id: token_id, token: token};
		if (invite) {
			tmpData.invite = invite;
		}
		_enableFormSubmit(false);
		_showFormError('');
		$.ajax({
			url: "/user/userExist",
			data: {"username": username},
			type: "get",
			dataType: "json",
			success: function(data) {
				_enableFormSubmit(true);
				if (!data) {
					tmpData = {};
					_showFormError('服务器错误');
				} else if (data.code != '0') {
					tmpData = {};
					_showFormError(data.msg ? data.msg : '服务器错误');
				} else {
					_sendFormCodeStepTwo();
				}
			},
			error: function(e) {
				_enableFormSubmit(true);
				_showFormError('服务器错误');
				tmpData = {};
			}
		});
		return false;
	}
	function _sendFormCodeStepTwo() {
		_enableFormSubmit(false);
		$.ajax({
			url: '/user/MCode',
			data: {mobile:tmpData.mobile,type:"1",captcha:tmpData.captcha,token:tmpData.token,token_id:tmpData.token_id,isrsms:1},
			type: "post",
			dataType: "json",
			success: function(data) {
				_enableFormSubmit(true);
				if (!data) {
					tmpData = {};
					_showFormError('服务器错误');
				} else if (data.code != 1) {
					tmpData = {};
					_showFormError(data.message ? data.message : '服务器错误');
				} else {
					_showFormError('');
					showRegsiterPopup();
				}
			},
			error: function() {
				_enableFormSubmit(true);
				_showFormError('服务器错误');
			}
		});
	}
	

// }

var timerInterval = null;
var timerLeft = 180;
function setTimerSendBtn() {
	if (timerInterval != null) {
		clearInterval(timerInterval);
	}
	timerLeft = 180;
	$('#action-send-code').attr('disabled', 'disabled').addClass("gray");
	$('#action-send-code').val(timerLeft + '秒后重新发送');
	timerInterval = setInterval(function(){
		$('#action-send-code').val(timerLeft + '秒后重新发送');
		timerLeft--;
		if (timerLeft < 1) {
			clearInterval(timerInterval);
			timerInterval = null;
			$('#action-send-code').val('点击发送').removeAttr('disabled').removeClass("gray");
		}
	}, 1000);
}

function showRegsiterPopup() {
	setTimerSendBtn();
	$("#dialog-mask").css("height", util.getDomHeight() + "px" ).css("display", "block").animate({"opacity":0.5}, 100);
	$("#labels-mobile").html(tmpData.mobile);
	$("#validNum").val("");
	$("#weedialog").show(100);
}

function closeRegsiterPopup() {
	if (timerInterval != null) {
		clearInterval(timerInterval);
		timerInterval = null;
		$('#action-send-code').val('点击发送').removeAttr('disabled').removeClass("gray");
	}
	$("#dialog-mask").hide(100);
	$("#weedialog").hide(100);
}

// function initPopupModel() {
	var _inPopupRequest = false;
	function _showPopupError(msg) {
		$(".validNumformError").css("display", "block");
		$(".validNumformError").html(msg);
	}
	function _enablePopupSubmit(enable) {
		_inPopupRequest = !enable;
	}
	function _sendPopupCode() {
		_enablePopupSubmit(false);
		$('#action-send-code').val('发送中......').attr('disabled', 'disabled').addClass("gray")
		$.ajax({
			url: '/user/MCode',
			data: {mobile:tmpData.mobile,type:"1",captcha:tmpData.captcha,token:tmpData.token,token_id:tmpData.token_id,isrsms:0},
			type: "post",
			dataType: "json",
			success: function(data) {
				$('#action-send-code').val('点击发送').removeAttr('disabled').removeClass("gray");
				_enablePopupSubmit(true);
				if (!data) {
					_showPopupError('服务器错误');
				} else if (data.code != 1) {
					_showPopupError(data.message ? data.message : '服务器错误');
				} else {
					setTimerSendBtn();
				}
			},
			error: function() {
				$('#action-send-code').val('点击发送').removeAttr('disabled').removeClass("gray");
				_enablePopupSubmit(true);
			}
		});
	}
	function _popupRegister() {
		if (_inPopupRequest) return false;
		var code = $("#validNum").val();
		if (!WXLC.ValidateConf.code[3].test(code)) {
			_showPopupError(WXLC.ValidateConf.code[4]);
			return false;
		}
		tmpData.code = code;
		//TODO add some data
		_enablePopupSubmit(false);
		_showPopupError("&nbsp;");
		$.ajax({
			url: "/user/DoH5RegisterAndLogin",
			data: tmpData,
			type: "post",
			dataType: "json",
			success: function(data) {
				_enablePopupSubmit(true);
				if (!data) {
					_showPopupError('服务器错误');
				} else if (data.errorCode != 0) {
					_showPopupError(data.errorMsg ? data.errorMsg : '服务器错误');
				} else {
					window.location.href = "/account/addbank";
				}
			},
			error: function() {
				_enablePopupSubmit(true);
				_showPopupError('服务器错误');
			}
		});
	}

// }

$(function(){
	$("#cash-red-form .btn-sub").bind("click", function(){
		_sendFormVcodeStepOne();
		return false;
	});
	$("#action-send-code").bind("click", function(){
		_sendPopupCode();
		return false;
	});
	$("#weedialog .dialog-ok").bind("click", function(){
		_popupRegister();
		return false;
	});
	$("#weedialog .dialog-close").bind("click", function(){
		closeRegsiterPopup();
		return false;
	});
	$("#cash-red-form input").bind("blur", function(){
		_checkForm();
	});
	//var ele_pwd =  $('#input-password');
	var password_wapper =  $('#password_wapper');
	var ele_pwd_btn =  $('.pwd-show');
	ele_pwd_btn.css("cursor", "pointer");
	ele_pwd_btn.bind('mousedown', function() {
		//ie8下报错 变为html写入形式
		// ele_pwd.attr("type", ele_pwd.attr("type") == 'text' ? "password" : 'text' );
		var ele = password_wapper.find("input")
		var val = ele.val();
		ele.attr("type") == 'password' ? ele_pwd_btn.addClass("pwd-hide") : ele_pwd_btn.removeClass("pwd-hide");
		ele.attr("type") == 'password' ? 
			password_wapper.html('<input class="txt-input  padd-l45" maxlength="25" type="text" id="input-password" value="' + val + '" name="password" placeholder="密码">')
		: 
		password_wapper.html('<input class="txt-input  padd-l45" maxlength="25" type="password" id="input-password" value="' + val + '" name="password" placeholder="密码">');

		//removeClass();
	});
	var ele_f_captcha = $(".f_captcha");
	ele_f_captcha.click(function() {
		document.getElementById("f_captcha").src = "/verify.php?w=104&h=50&rb=0&rand=" + new Date().valueOf();
	});
});