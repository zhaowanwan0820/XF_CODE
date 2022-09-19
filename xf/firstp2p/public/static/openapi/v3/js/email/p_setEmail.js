$(function(){
    var _tmpRegData = {};
    var _inMcodeRequest = false;
    var _mcoderegbtnenable = false;
    function _updateMcodeRegbtn() {
        var disabled = $('.js_email').val() == ''||false;
        if (disabled) {
            $(".js_button").attr('disabled', 'disabled');
        } else {
            $(".js_button").removeAttr('disabled');
        }
        _mcoderegbtnenable = disabled;
    }
    $(".js_email").bind("input", _updateMcodeRegbtn);
    $(".js_button").bind("click", function(){
        if (_inMcodeRequest == true || _mcoderegbtnenable) return;
        var email = $(".js_email").val().trim();
        var token  =  $('#token').val();
        var token_id  =  $('#token_id').val();
        if (!P2PWAP.util.checkEmail(email)) {
            P2PWAP.ui.showErrorTip('请填写有效的邮箱地址');
            return;
        }
        _tmpRegData['email'] = email;
        _tmpRegData['token'] = token;
        _tmpRegData['token_id'] = token_id;
        _inMcodeRequest = true;
        $(".p_setEmail input").attr("readonly", "true");
        $(".js_button").attr('disabled', 'disabled');
        // _updateMcodeRegbtn();
        P2PWAP.util.ajax('/user/DoSetEmail', 'post', function(json) {
            _inMcodeRequest = false;
            // _updateMcodeRegbtn();
            $(".p_setEmail input").removeAttr("readonly");
            if (json['errorCode'] == 0) {
                P2PWAP.ui.showErrorTip('<span class="ui_reg_suc_icon"></span><p>设置成功</p>');
                setTimeout(function() {
                    window.location.href = json['data']['redirect_uri'];
                }, 2000);
            }else if(json['errorCode'] == 41037) {
                P2PWAP.ui.showErrorTip('<span class="ui_reg_suc_icon"></span><p>设置失败</p>');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }else if(json['errorCode'] == 41033) {
                _tmpRegData = {};
                P2PWAP.ui.showErrorTip('该邮箱已被使用');
            } else  {
                _tmpRegData = {};
                P2PWAP.ui.showErrorTip(json['errorMsg']);
            }
        }, function(msg) {
            _tmpRegData = {};
            _inMcodeRequest = false;
            _updateMcodeRegbtn();
            $(".p_setEmail input").removeAttr("readonly");
            P2PWAP.ui.showErrorTip(msg);
        }, _tmpRegData);
    });
   _updateMcodeRegbtn();
});