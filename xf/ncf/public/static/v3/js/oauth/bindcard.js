$(function () {

    //获取cookie 反填值
    $("#name").val(_getCookie("c_realName"));
    $("#input-cardNo").val(_getCookie("c_cardNo"));
    $('#submit_button').removeAttr("disabled");
    //placehoder
    $(function() {
        $(".int_placeholder").each(function() {
            var p_text = $(this).attr("data-placeholder");
            new Firstp2p.placeholder(this, {
                placeholder_text: p_text == null ? "请输入" : p_text
            });
        });
    });
    var util = new Util();

// 写程序开始
    var __formpage__ = formpage({
        //重构方法
        // rewrite: {
        // 	create: function() {
        // 		this._super.create.call(this);
        // 		console.log("rewrite", this);
        // 	}
        // },
        conf: {
            frm: document.getElementById("bindcard"),
            vld: validate_middleware.validate({
                "realName": ["姓名", true, null, /^.{2,20}$/, '姓名输入不正确'],
                "cardNo": ["身份证号码", true, null, /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/, '请输入正确的身份证号码']
            }),
            custom_vld: {
                realName: function(data) {
                    return commVld(data);
                },
                cardNo: function(data) {
                    return idcardVld(data);
                },
                agreement: function(data) {
                    return agreeVld(data);
                }
            },
            callback: function(data, els) {
                //提交 锁定提交按钮防止重复提交
                $('#submit_button').attr('disabled', 'disabled');
                //调用AIAX
                submitVld();
                return false;
            },
            focus: false,
            util: util
        }
    });

    /***************** 添加cookie *********************/
    function _addCookie(name, value, minutes) {
        var exdate = new Date();
        exdate.setMinutes(exdate.getMinutes() + minutes);
        //var exdate = new Date((new Date()).getTime() + second * 1000);
        document.cookie = name + "=" + escape(value) + ";path=/" +
            ((minutes == null) ? "" : ";expires=" + exdate.toGMTString());
    }

    /***************** 获取cookie *********************/

    function _getCookie(c_name) {
        if (document.cookie.length > 0) {
            var c_start = document.cookie.indexOf(c_name + "=");
            if (c_start != -1) {
                c_start = c_start + c_name.length + 1;
                var c_end = document.cookie.indexOf(";", c_start);
                if (c_end == -1) c_end = document.cookie.length;
                return unescape(document.cookie.substring(c_start, c_end));
            }
        }
        return "";
    }
    function _delCookie(name)//删除cookie
    {
        var exp = new Date();
        exp.setMinutes(exp.getMinutes() - 30);
        var cval = _getCookie(name);
        if (cval != null) document.cookie = name + "=" + cval + ";path=/" + ";expires=" + exp.toGMTString();
    }

    function submitVld() {
        var data = ajaxbindCard();
        //返回-1 错误
        if (data && 'errorCode' in data && data.errorCode == -1) {
            var pophei = 125;
            if(data.errorMsg == "身份认证失败"){
                data.errorMsg += '<p class="tl f14 mt10"><span style="color:#656565;">' + norealErr + '</span></p>';
                pophei = 176;
            }
            var html = '';
            html += '<div class="wee-send">';
            html += '<div class="send-input">';
            html += '<div class="error-box">';
            html += '<div class="edit-success"><i class="edit-fail"> </i><span class="font24">操作失败</span></div>';
            html += '<p class="error_text font16">' + data.errorMsg + '</p>';
            html += '</div>';
            html += '</div>';
            $.weeboxs.open(html, {
                boxid: null,
                boxclass: 'weebox_send_msg',
                contentType: 'text',
                showCancel: false,
                title: '错误提示',
                width: 463,
                height: pophei,
                type: 'wee',
                showButton: true,
                showOk: true, //是否显示确定按钮
                okBtnName: '返回重新填写',//"确定"按钮名称
                onclose: function () {
                    location.reload();
                }
            });
            // 神策 是否认证成功
            wxsa.track('CertificationResult', {
                isCertifiedSuccess: 0,
                FailReason: data.errorMsg
            });
            zhuge.track('CertificationResult', {
                isCertifiedSuccess: 0,
                FailReason: data.errorMsg
            });
        } else if (data && 'redirect' in data && data.redirect != null) {
            wxsa.track('CertificationResult', {
                isCertifiedSuccess: 1
            });
            zhuge.track('CertificationResult', {
                isCertifiedSuccess: 1
            });
            zhuge.track('身份认证成功');
            //删除cookie
            _delCookie("c_realName");
            _delCookie("c_cardNo");
            window.location.href = data.redirect;
        }
    }

    //and by ww  追加弹框提示(ajax)
    function ajaxbindCard() {
        var realName = $("#name").val();
        var token_id = $("#token_id").val();
        var token = $("#token").val();
        var cardNo = $("#input-cardNo").val();
        var agreement = $("#agree").val();
        //追加cookie
        _addCookie("c_realName", realName, 5);
        _addCookie("c_cardNo", cardNo, 5);
        var isAjax = 1;
        var hash = {};
        var addbindUrl = '/account/registerWithBank';
        var _addbindAjax = function (url) {
            $.ajax({
                type: "post",
                data: {
                    realName: realName,
                    cardNo: cardNo,
                    agreement: agreement,
                    isAjax:isAjax,
                    token: token,
                    token_id: token_id
                },
                url: url,
                async: false,
                dataType: "json",
                success: function (data) {
                    hash = data;
                }
            });
        };
        _addbindAjax(addbindUrl);
        return hash;
    }

    function commVld(data) {
	var el = data.el;
	var status = data.status;
	var msg = data.msg;
	var ele = $(el).parent();

	var _reset  = function(ele) {
		$(el).removeClass('err-shadow');
		ele.find(".er-icon").css('display', 'none');
		ele.find(".error-wrap").css('display', 'none');
	}

	var _error = function(ele, msg) {
		_reset(ele);
		$(el).addClass('err-shadow');
		ele.find(".error-wrap").css('display', 'inline-block');
		ele.find(".error-wrap .e-text").html(msg);
	}

	var _right = function(ele) {
		_reset(ele);
		ele.find(".er-icon").css('display', 'inline-block');
	}

	if (msg === '') {
		_right(ele);
	} else {
		_error(ele, msg);
	}
	return data;
}
// commVld


// 带有ajax的验证demo
// invite ajax
function idcardVld(data) {
	var el = data.el;
	var status = data.status;
	var msg = data.msg;
	var ele = $(el).parent();
	var val = $(el).val();
	ele.find(".gat").css('display', 'inline-block');
	var _reset  = function(ele) {
		$(el).removeClass('err-shadow');
		ele.find(".er-icon").css('display', 'none');
		ele.find(".error-wrap").css('display', 'none');
	}

	var _error = function(ele, msg) {
		_reset(ele);
		$(el).addClass('err-shadow');
		ele.find(".error-wrap").css('display', 'inline-block');
		ele.find(".gat").css('display', 'none');
		ele.find(".error-wrap .e-text").html(msg);
	}

	var _right = function(ele) {
		_reset(ele);
		ele.find(".er-icon").css('display', 'inline-block');
		ele.find(".gat").css('display', 'none');
	}

	if (!data.status) {
		_error(ele, data.msg);
		return data;
	}

	var hash = idcardAjax(val);

	if (hash.status) {
		_right(ele);
	} else {
		_error(ele, hash.msg);
	}
	data.status = hash.status;
	return data;
}

function idcardAjax(val) {
     var url = './IdcardExist';
     var ele = $('#input-cardNo');
     var hash = {status: true, msg:''};
     if(val != '' && !idcardAjax[val]) {
        $.ajax({
            type: "post",
            data: {idno: val, idType: 1},
            dataType: "json",
            url: url,
            async: false,
            success: function(data) {
				if (data.code == "0") {
                	hash.status = true;
                	hash.msg = "";
				} else {
                	hash.status = false;
                	hash.msg = data.msg;
				}
                idcardAjax[val] = {status:hash.status, msg: hash.msg};
             }
   		});
     } else if (idcardAjax[val]) {
     	hash = idcardAjax[val];
     }
     return hash;
}

// 带有ajax的验证demo



function agreeVld(data) {
	var el = data.el;
	var val = el.value;
	var agreement_msg = $('#agreement_msg');
	if (val == '1') {
		data.status = true;
		agreement_msg.css('display', 'none');
	} else {
		data.status = false;
		agreement_msg.css('display', 'block');
	}

	return data;
}

//agree

var elist = [

//update
function() {
	$(".p2p-ui-checkbox").p2pUiCheckbox();
	//agree
	var agree = $('#agree');
	agree.unbind('change').bind('change', function(e) {
		agreeVld({el: this});
	})
}
//p2p-ui-checkbox
]

for (var i = 0, len = elist.length; i < len; i++) {
	elist[i]();
}

//end
});