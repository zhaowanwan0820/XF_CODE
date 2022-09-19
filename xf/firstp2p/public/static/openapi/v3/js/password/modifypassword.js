$(function(){
    // 图片验证码逻辑
    function updateCaptchaImg() {
        $('.verificationCode img').attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());
    }
    $('.verificationCode').click(function() {
        updateCaptchaImg();
    });
    updateCaptchaImg();

    // 点击下一步时发送验证码逻辑
    var _tmpRegData = {};
    var _inMcodeRequest = false;

    var _mcoderegbtnenable = false;
    // 更新下一步按钮状态
    function _updateMcodeRegbtn() {
        var disabled = $('#JS-old_password').val() == ''  || $('#JS-mobile').val() == ''  || ($('#JS-verify').length != 0 && $('#JS-verify').val() == '');
        if (disabled) {
            $(".JS-submit_btn").attr('disabled', 'disabled');
        } else {
            $(".JS-submit_btn").removeAttr('disabled');
        }
        _mcoderegbtnenable = disabled;
    }

    var openId = $("#JS-openId").val().trim();

	$("#JS-old_password").bind("input", _updateMcodeRegbtn);
    $("#JS-mobile").bind("input", _updateMcodeRegbtn);
    $("#JS-verify").bind("input", _updateMcodeRegbtn);

    $(".JS-submit_btn").bind("click", function(){
        if (_inMcodeRequest == true || _mcoderegbtnenable) return;
        var old_password = $("#JS-old_password").val().trim();
		var mobile = $("#JS-mobile").val().trim();
        var verify = $("#JS-verify").val().trim();
        var token  = $('#token').val();
        var openId = $("#JS-openId").val().trim();
        if (!P2PWAP.util.checkMobile(mobile)) {
            P2PWAP.ui.showErrorTip('手机号格式不正确');
            return;
        }
        if (!P2PWAP.util.checkCaptcha(verify)) {
            P2PWAP.ui.showErrorTip('图形验证码不正确');
            return;
        }
		_tmpRegData['old_password'] = old_password;
        _tmpRegData['mobile'] = mobile;
        _tmpRegData['verify'] = verify;
        _tmpRegData['token'] = token;
        _tmpRegData['openId'] = openId;
        _tmpRegData['type'] = 12;
        _tmpRegData['active'] = 1;
        _tmpRegData['isPc'] = 0;

        _inMcodeRequest = true;
        $("#forget_form input").attr("readonly", "true");
        _updateMcodeRegbtn();
        P2PWAP.util.ajax('/user/DoModifyPwd', 'post', function(json) {
            _inMcodeRequest = false;
            _updateMcodeRegbtn();
            $("#forget_form input").removeAttr("readonly");
            if (json['errorCode'] == 0) {
                _showVerifyMobileDialog();
            } else {
                updateCaptchaImg();
                _tmpRegData = {};
                P2PWAP.ui.showErrorTip(json['errorMsg']);
            }
        }, function(msg) {
            updateCaptchaImg();
            _tmpRegData = {};
            _inMcodeRequest = false;
            _updateMcodeRegbtn();
            $("#forget_form input").removeAttr("readonly");
            P2PWAP.ui.showErrorTip(msg);
        }, _tmpRegData);
    });

    // 验证手机框逻辑
    var _inRegRequest = false;
    var _reMcodeAjax = null;
    var _reMcodeTimer = null;
    var _finishRegbtnenable = false;
    function _updateFinishRegBtn() {
        var enable = !_inRegRequest && $("#JS-regverifypanel .JS-input_vcode").val().trim() != "";
        if (enable) {
            $("#JS-regverifypanel .JS-regbtn").removeClass("reg_finish_btn_dis");
        } else {
            $("#JS-regverifypanel .JS-regbtn").addClass("reg_finish_btn_dis");
        }
        _finishRegbtnenable = enable;
    }
    $("#JS-regverifypanel .JS-input_vcode").bind("input", _updateFinishRegBtn);

    // 重新获取验证码逻辑
    var _reMcodeBtn = $("#JS-regverifypanel .JS-mcodebtn");
    function _cleanMcodeBtn() {
        if (_reMcodeTimer == null) return;
        clearInterval(_reMcodeTimer);
        _reMcodeTimer = null;
        _reMcodeBtn.removeClass('dis_reset').text('重新发送');
    }
    function _updateMcodeBtn() {
        var timeRemained = 60;
        _reMcodeBtn.addClass('dis_reset').text(timeRemained + '秒后可重发');
        _reMcodeTimer = setInterval(function() {
            timeRemained--;
            if (timeRemained < 1) {
                _cleanMcodeBtn();
            } else {
                _reMcodeBtn.text(timeRemained + '秒后可重发');
            }
        }, 1000);
    }
    _reMcodeBtn.bind("click", function() {
        if (_reMcodeAjax != null || _reMcodeTimer != null) return;
        _reMcodeBtn.addClass('dis_reset').text('正在发送');
        _reMcodeAjax = P2PWAP.util.ajax('/user/MCode', 'post', function(obj) {
            _reMcodeAjax = null;
            if (obj['code'] == 1){
                _updateMcodeBtn();
            } else {
               P2PWAP.ui.showErrorTip(obj['message']);
               _reMcodeBtn.removeClass('dis_reset').text('重新发送');
            }
        }, function(msg) {
            _reMcodeAjax = null;
            P2PWAP.ui.showErrorTip(msg);
            _reMcodeBtn.removeClass('dis_reset').text('重新发送');
        }, _tmpRegData); 
    });
    function _showVerifyMobileDialog() {
        $("#JS-regverifypanel .JS-mobilelabel").text("已向" + _tmpRegData['mobile'] + "发送短信验证码");
        $("#JS-regverifypanel .JS-input_vcode").val("");
        _updateMcodeBtn();
        P2PWAP.ui.addModalView($("#JS-regverifypanel")[0]);
        $("#JS-regverifypanel").show();
    }
    $("#JS-regverifypanel .JS-closebtn").bind("click", function() {
        if (_inRegRequest) return;
        if (_reMcodeAjax) {
            _reMcodeAjax.abort();
            _reMcodeAjax = null;
        }
        _cleanMcodeBtn();
        P2PWAP.ui.removeModalView($("#JS-regverifypanel")[0]);
        $("#JS-regverifypanel").hide();
        updateCaptchaImg();
    });

    //注册逻辑
    $("#JS-regverifypanel .JS-regbtn").bind("click", function(){
        if (_inRegRequest || !_finishRegbtnenable) return;
        if (_reMcodeAjax != null) return;
        var vcode = $("#JS-regverifypanel .JS-input_vcode").val().trim();
        if (!P2PWAP.util.checkMcode(vcode)) {
            P2PWAP.ui.showErrorTip('请填写6位数字验证码');
            return;
        }
        _inRegRequest = true;
        _updateFinishRegBtn();

        var regdata = {};
        regdata['mobile'] = _tmpRegData['mobile'];
        regdata['code'] = vcode;
        regdata['token'] = _tmpRegData['token'];
        regdata['isAjax'] = 1;

        P2PWAP.util.ajax('/user/CheckMCode', 'post', function(json) {
            _inRegRequest = false;
            _updateFinishRegBtn();
            if (json['errorCode'] == 0) {
                window.location.href = '/user/RenewPwd?openId=' + encodeURIComponent(openId);
            } else {
                P2PWAP.ui.showErrorTip(json['errorMsg']);
            }
        }, function(msg) {
            _inRegRequest = false;
            _updateFinishRegBtn();
            P2PWAP.ui.showErrorTip(msg);
        }, regdata);
    });
});
