(function($){
    $(function(){

    
    //域名
    var _wapHost = P2PWAP.util.getWapHost('redirect_uri', 'http:\/\/(.*)\/account');
    // 验证码逻辑
    $('#JS-regpanel .JS-verifyimg').click(function() {
        $('#JS-regpanel .JS-verifyimg').attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());
    });
    $('#JS-regpanel .JS-verifyimg').attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());

    // 点击注册时发送验证码逻辑
    var _tmpRegData = {};
    var _inMcodeRequest = false;

    var _mcoderegbtnenable = false;
    var isAllowRegister=function () {
        var hiddenInput=$('#isAllowRegister');
        if (hiddenInput.length!=0){
            P2PWAP.ui.alert({
                'text':hiddenInput.val()
            });
            return false;
        }else{
            return true;
        }
    }();
    // 更新点击注册领红包按钮状态
    function _updateMcodeRegbtn() {
        var enable =isAllowRegister && !_inMcodeRequest &&
            $("#JS-regpanel .JS-input_mobile").val().trim() != "" &&
            $("#JS-regpanel .JS-input_pwd").val() != "" &&
            $("#JS-regpanel .JS-input_captcha").val().trim() != "";
        if (enable) {
            $("#JS-regpanel .JS-regbtn").removeClass("reg_finish_btn_dis");
        } else {
            $("#JS-regpanel .JS-regbtn").addClass("reg_finish_btn_dis");
        }
        _mcoderegbtnenable = enable;
    }
    $("#JS-regpanel .JS-input_mobile").bind("input", _updateMcodeRegbtn);
    $("#JS-regpanel .JS-input_pwd").bind("input", _updateMcodeRegbtn);
    $("#JS-regpanel .JS-input_captcha").bind("input", _updateMcodeRegbtn);

    $("#JS-regpanel .JS-regbtn").bind("click", function(){
        if (_inMcodeRequest == true || !_mcoderegbtnenable) return;
        var mobile = $("#JS-regpanel .JS-input_mobile").val().trim();
        var password = $("#JS-regpanel .JS-input_pwd").val();
        var oapi_sign = $("#JS-regpanel #oapi_sign").val();
        var oapi_uri = $("#JS-regpanel #oapi_uri").val();
        var captcha = $("#JS-regpanel .JS-input_captcha").val().trim();
        if (!P2PWAP.util.checkMobile(mobile)) {
            P2PWAP.ui.showErrorTip('手机号格式不正确');
            return;
        }
        if (!P2PWAP.util.checkPassword(password)) {
            P2PWAP.ui.showErrorTip('密码格式不正确，请输入6-20个字符');
            return;
        }
        if (!P2PWAP.util.checkCaptcha(captcha)) {
            P2PWAP.ui.showErrorTip('图形验证码不正确');
            return;
        }

        _tmpRegData['mobile'] = mobile;
        _tmpRegData['oapi_sign'] = oapi_sign;
        _tmpRegData['oapi_uri'] = oapi_uri;
        _tmpRegData['password'] = password;
        _tmpRegData['captcha'] = captcha;
        _tmpRegData['type'] = 1;
        var _invite = $(".JS-input_invite").val();
        if (_invite != undefined && _invite != "") {
            _tmpRegData['invite'] = _invite;
        }
        //_tmpRegData['invite'] = $(".JS-input_invite").val();
        if ($("#token_id").length > 0) {
            _tmpRegData['token_id'] = $("#token_id").val();
            _tmpRegData['token'] = $("#token").val();
        } else {
            _tmpRegData['active'] = 1;
        }
        _inMcodeRequest = true;
        $("#JS-regpanel input").attr("readonly", "true");
        _updateMcodeRegbtn();
        P2PWAP.util.ajax('/user/MCode', 'post', function(json) {
            _inMcodeRequest = false;
            _updateMcodeRegbtn();
            $("#JS-regpanel input").removeAttr("readonly");
            if (json['code'] == 1) {
                _showVerifyMobileDialog();
            } else {
                _tmpRegData = {};
                P2PWAP.ui.showErrorTip(json['message']);
            }
        }, function(msg) {
            _tmpRegData = {};
            _inMcodeRequest = false;
            _updateMcodeRegbtn();
            $("#JS-regpanel input").removeAttr("readonly");
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
            if (obj.code == 1){
                _updateMcodeBtn();
            } else {
               P2PWAP.ui.showErrorTip(obj.message);
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
    });

    //注册逻辑
    $("#JS-regverifypanel .JS-regbtn").bind("click", function(){
        if (_inRegRequest || !_finishRegbtnenable || !isAllowRegister) return;
        if (_reMcodeAjax != null) return;
        var vcode = $("#JS-regverifypanel .JS-input_vcode").val().trim();
        if (!P2PWAP.util.checkMcode(vcode)) {
            P2PWAP.ui.showErrorTip('请填写6位数字验证码');
            return;
        }
        _inRegRequest = true;
        _updateFinishRegBtn();

        var regdata = {};
        //添加活动附带参数
        if (window['_eventRegisterAddParams']) {
            regdata = window['_eventRegisterAddParams'];
        } else {
            regdata['cn'] = '';
        }
        regdata['mobile'] = _tmpRegData['mobile'];
        regdata['oapi_sign'] = _tmpRegData['oapi_sign'];
        regdata['oapi_uri'] = _tmpRegData['oapi_uri'];
        regdata['password'] = _tmpRegData['password'];
        regdata['captcha'] = _tmpRegData['captcha'];
        regdata['code'] = vcode;
        regdata['isAjax'] = 1;
        if ($(".JS-input_invite").length > 0) regdata['cn'] = $(".JS-input_invite").val().trim();
        if (typeof regdata['event_id'] == 'undefined') regdata['event_id'] = '';
        if (typeof regdata['event_data'] == 'undefined') regdata['event_data'] = '';
        var api = '';
        if (window['isMaster'] != undefined && (isMaster == false || isMaster == 0)) {
             // 分站登录逻辑，参数整理
            api = '/user/DoRegister';
            regdata['invite'] = regdata['cn'];
            regdata['agreement'] = 1;
            regdata['type'] = 'h5';
            // url参数传递
            var params = window.location.search.substr(1).split('&');
            $.each(params, function(k, v) {
                var kv = v.split('=');
                if (typeof regdata[kv[0]] == 'undefined') {
                    regdata[kv[0]] = kv[1];
                }
            })
        } else {
            regdata['type'] = 'h5';
            regdata['redirect_uri'] = $('#redirect_uri').val();
            api = '/user/DoH5RegisterAndLogin?client_id=db6c30dddd42e4343c82713e&response_type=code'
        }
        P2PWAP.util.ajax(api, 'post', function(json) {
            _inRegRequest = false;
            _updateFinishRegBtn();
            if (json['errorCode'] == 0) {
                //活动附带响应
                if (window['_eventRegisterCallback']) {
                   $("#JS-regverifypanel").hide();
                   window['_eventRegisterCallback'].call(null, json['data']);
                } else {
                    if(json['data']['oapi_sign'] == 1){
                        var temp  = json['data']['oapi_status'] == 1 ? 0 : 1;
                        window.location.href= json['data']['oapi_uri'] + '?bak_code=' + temp;
                        return;
                    }
                    if (!!regdata['from_platform']) {
                            var url = "http://" + _wapHost + "/oauth?code=" + json['data']['oauth_code'] + "&from_platform=" + encodeURIComponent(regdata['from_platform']);
                    } else {

                        var url = "http://" + _wapHost + "/oauth?code=" + json['data']['oauth_code'];
                    }

                    if (undefined != window['_REDIRECT_URI_'] && '' != window['_REDIRECT_URI_']) {
                        url += ('&from_platform=authorize&redirect_uri=' + window['_REDIRECT_URI_']);
                    }

                    if(json['data']['ticket'] != undefined && json['data']['ticket'] == 1){
                        $("#JS-regverifypanel").hide();
                        P2PWAP.ui.alert({
                            'text':"恭喜您注册成功，0元购活动的投资返现券（有效期" + json['data']['couponExpire'] + "天）会于" + json['data']['ticketSendTimeText'] + "发放到您的账户内，请届时注意查收。",
                            okFn:function(){
                                window.location.href = url;
                            }
                        });
                    }else{
                        window.location.href = url;
                    }
                }
            } else {
                P2PWAP.ui.showErrorTip(json['errorMsg']);
            }
        }, function(msg) {
            _inRegRequest = false;
            _updateFinishRegBtn();
            P2PWAP.ui.showErrorTip(msg);
        }, regdata);
    });

    //注册协议
    var ui_mask_height = $(window).height();
    $('#JS-regpanel .JS-regterm').click(function (event) {
        var _rootDomain = 'firstp2p.com';
        if (window['rootDomain'] && window['rootDomain'] != undefined && window['rootDomain'] != "") {
            _rootDomain = window['rootDomain'];
        }
        var _explain = '备案号为：京ICP证130046号，以下简称网信';
        if (window['explain'] && window['explain'] != undefined && window['explain'] != "") {
            _explain = window['explain'];
        }
        if(isWapApp == 1){
            setTimeout(function(){
                P2PWAP.ui.showNoticeDialog('注册协议', $("#register_protocol").val(),ui_mask_height);

            },500)
        }else{
            P2PWAP.ui.showNoticeDialog('注册协议', $("#register_protocol").val());
        }

        $("#domain_name").html(rootDomain);
        $("#explain").html(explain);

    });

    $("body").on("touchmove" , ".ui_mask,.ui_max_width" , function(event){
            event.preventDefault();
    });



    //lazyload懒加载
    function isShow($el){//判断图片的位置，看是不是该加载了
        var winHeight = $(window).height();//窗口高度
        var scrollHeight = $(window).scrollTop();//滚动的高度
        var top = $el.offset().top;//被检查图片距顶部的高度
        if(top < winHeight + scrollHeight + 100){//代表图片还未到窗口位置
            return true;
        }else{//表明图片已经显示出来了
            return false;
        }
    }
    $(window).on("scroll" , function(){
        checkShow();
    });
    checkShow();
    function checkShow(){//检查元素是否在可视范围内
        $("img.lazy , .lazy_load , .lazy_xz").each(function(){
            var $cur = $(this);
            if(isShow($cur)){
                setTimeout(function(){
                    showImg($cur);
                },0);
            }
        })
    }
    function showImg($el){//该加载的时候，替换图片路径，使显示
        if($el.hasClass('lazy')){
            $el.attr('src', $el.data('src'));
            $el.css({'width':'100%','margin':'0'});
        }else if($el.hasClass('lazy_load')){
            var data_src = $el.data('src');
            var bg = 'url('+ data_src +')';
            $el.css('background',bg+' no-repeat center');
            $el.addClass('bg_cover');
            $el.css({'width':'100%','margin':'0'});
        }else if($el.hasClass('lazy_xz')){
            var data_src = $el.data('src');
            var bg = 'url('+ data_src +')';
            $el.css('background',bg+' no-repeat center');
            $el.addClass('late_add');
        }
    }
   });
})(Zepto);
