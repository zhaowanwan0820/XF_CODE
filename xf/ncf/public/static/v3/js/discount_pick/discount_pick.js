$(function(){
    var _tmpRegData = {};
    var _inMcodeRequest = false;
    var _mcoderegbtnenable = false;

    function DiscountShareGet(){
        if (_inMcodeRequest == true || _mcoderegbtnenable) return;
        var event_id = $("#event_id").val();
        var cn=$("#cn").val();
        var code=$("#code").val();
        var token=$("#token").val();
        var token_id=$("#token_id").val();
        _tmpRegData['id'] = event_id;
        _tmpRegData['cn'] = cn;
        _tmpRegData['code'] = code;
        _tmpRegData['token'] = token;
        _tmpRegData['token_id'] = token_id;
        P2PWAP.util.ajax('/marketing/DiscountShareGet', 'post', function(json) {
        _inMcodeRequest = false;
        if (json['code'] == 0) {
            window.location.href = json['jumpUrl'];
        } else {
            Update_identifycode();
            if(json['jumpUrl']){
                window.location.href = json['jumpUrl'];
            }else{
                _tmpRegData = {};
                P2PWAP.ui.showErrorTip(json['msg']);
            }
        }
        }, function(msg) {
            Update_identifycode();
            _tmpRegData = {};
            _inMcodeRequest = false;
            P2PWAP.ui.showErrorTip(msg);
        }, _tmpRegData);
    }
    $("#JS-submit_btn").click(function(event) {
        var tel=$("#phone_number").val();
        var reg=/^1[3456789]\d{9}$/;
        var identify_code=$("#identify_code");
        _tmpRegData['mobile'] = tel;
        _tmpRegData['captcha'] = identify_code.val();
        if(!tel){
            P2PWAP.ui.showErrorTip("手机号码不能为空");
            return;
        } if (!reg.test(tel)){
            P2PWAP.ui.showErrorTip("手机号码格式不正确");
            return;
        } if (!identify_code.val() && identify_code.length>0){
            P2PWAP.ui.showErrorTip("验证码不能为空");
            return;
        }
        DiscountShareGet()
    });
    $("#identify_img").click(function() {
        Update_identifycode();
    });
    function Update_identifycode(){
        $('#identify_img').attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());
    }
    var mobileRegEx = /^1[3456789]\d{9}$/;
    $('#register-form').submit(function(){

        var tel= $('.JS_tel_input').val()
        if(!tel || tel==null){
            P2PWAP.ui.showErrorTip("手机号码不能为空");
            return false;
        }else if(!mobileRegEx.test(tel)){
            P2PWAP.ui.showErrorTip("手机号码格式不正确");
            return false;
        }else{
            $('.JS_tel_btn').attr('disabled','disabled');
            return true;
        }
    });

    $('#JS-submit_change_btn').click(function(){
        var tel= $('.JS_tel_input').val()
        if(!tel || tel==null){
            P2PWAP.ui.showErrorTip("手机号码不能为空");
            $('.JS_tel_btn').attr('disabled','disabled');
            return false;
        }else if(!mobileRegEx.test(tel)){
            P2PWAP.ui.showErrorTip("手机号码格式不正确");
            $('.JS_tel_btn').attr('disabled','disabled');
            return false;
        }else{
            $('.JS_tel_btn').removeAttr('disabled');
            return true;
        }
    });
    $("#JS-submit_change_btn").click(function(event) {
        if (_inMcodeRequest == true || _mcoderegbtnenable) return;
        var tel=$("#phone_number").val();
        var reg=/^1[3456789]\d{9}$/;
        _tmpRegData['mobile'] = tel;
        _tmpRegData['id'] = $("#id").val();
        _tmpRegData['m'] = $("#m").val();
        _tmpRegData['token_id'] = $("#token_id").val();
        _tmpRegData['token'] = $("#token").val();
        if(!tel){
            P2PWAP.ui.showErrorTip("手机号码不能为空");
            return false;
        } if (!reg.test(tel)){
            P2PWAP.ui.showErrorTip("手机号码格式不正确");
            return;
        }
        P2PWAP.util.ajax('/marketing/DiscountShareChangeMobile', 'post', function(json) {
            _inMcodeRequest = false;
            if (json['code'] == 0) {
                P2PWAP.ui.showErrorTip('<span class="give_suc_icon"></span><p>修改成功</p>');
                $('.change_tel').html(json['tel']);
                setTimeout(function(){
                    window.location.href = json['jumpUrl'];
                },2000);
            } else {
                if(json['jumpUrl']){
                    window.location.href = json['jumpUrl'];
                }else{
                    _tmpRegData = {};
                    P2PWAP.ui.showErrorTip(json['msg']);
                }
            }
        }, function(msg) {
            _tmpRegData = {};
            _inMcodeRequest = false;
            P2PWAP.ui.showErrorTip(msg);
        }, _tmpRegData);
    });
})