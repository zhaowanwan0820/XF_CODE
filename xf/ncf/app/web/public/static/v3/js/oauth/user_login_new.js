$(function() {
    //placehoder
    $(function () {
        $("#input-mobile").bind("focus" , function(){
            var $p = $(this).parent();
            $p.removeClass('err-shadow');
            $p.addClass('ipt-focus');
        }).bind("blur" , function(){
            $(this).parent().removeClass('ipt-focus');
        });
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
        /***************** 删除cookie *********************/
        function _delCookie(name)//删除cookie
        {
            var exp = new Date();
            exp.setMinutes(exp.getMinutes() - 30);
            var cval = _getCookie(name);
            if (cval != null) document.cookie = name + "=" + cval + ";path=/" + ";expires=" + exp.toGMTString();
        }

        //删除第二步绑卡时的Cookie
        //删除cookie
        _delCookie("c_realName");
        _delCookie("c_cardNo");

        $(".int_placeholder").each(function () {
            var p_text = $(this).attr("data-placeholder");
            new Firstp2p.placeholder(this, {
                placeholder_text: p_text == null ? "请输入" : p_text,
                isIE7Show : false
            });
        });


        //海外手机号下拉框样式修改
        
        // (function(){
        //     $(".select_box").select({
        //         onSelectChange : function($t , $input , index , $li){
        //             __formpage__.setRule = {};
        //             __formpage__.setRule.mobile = Firstp2p.mobileReg[$('#country_code').val()];
        //             if($.trim($("#input-mobile").val()) != ''){
        //                 __formpage__._blur($("#input-mobile")[0] , "blur");
        //             }
        //             $t.find('.j_select').html('<span style="color:#555353;font-size: 14px;">+'+$li.data('value')+'</span>');
        //         }
        //     });

        //     $(".j_select").css({
        //         'width':65,
        //         'padding':'0px 0px 0px 13px',
        //         'background-position':'58px 13px',
        //         'color':'#333'
        //     }).html('<span style="color:#555353;font-size: 14px;">+86</span>');
        //     $(".select_box").next('div.ipt-wrap').css('width',209);
        //     $('.select_ul').css({
        //         'padding':'5px 3px 5px 3px',
        //         'width':'131'
        //     }).find('li').css({
        //         'padding':'0px 9px 0px 6px'
        //     }).html(function(){
        //         var curIconText=$(this).data('name');
        //         var areaName="";
        //         var areaCode=$(this).data('value');
        //         switch (curIconText){
        //             case 'cn':areaName="中国";break;
        //             case 'hk':areaName="中国香港";break;
        //             case 'mo':areaName="中国澳门";break;
        //             case 'tw':areaName="中国台湾";break;
        //             case 'us':areaName="美国";break;
        //             case 'ca':areaName="加拿大";break;
        //             case 'uk':areaName="英国";break;
        //             default :areaName="中国";
        //         }
        //         return $('<span style="float:left;">'+areaName+'</span><span style="float:right;">+'+areaCode+'</span>')
        //     });
        // })();

    });

var util = new Util();
var __formpage__ = formpage({
    conf: {
        frm: document.getElementById("login_v2"),
        vld: validate_middleware.validate({
            "password": ["密码", true, null, /^.{5,25}$/, '请输入5-25位数字、字母及常用符号']
        }),
        custom_vld: {
            mobile: function(data) {
                return commVld(data);
            },
            captcha: function(data) {
                return verifyVld(data);
            }
        },
        callback: function(data, els) {
            //发送短信 或 提交
            // if (/\d{6}/.test($('#input-code').val())) {
            //     //submit_button 防止表单重复提交
            //     $("#submit_button").attr('disabled', 'disabled');
            //     return true;
            // }
            submitVld();
            return false;
        },
        focus: false,
        util: util
    }
});


    /**
     * 判断密码合法性
     * @param data
     * @returns {*}
     */
    function passwordVld(data){
        var el=data.el;
        if(pas_strength_reg.blurFn(true)){
            $(el).removeClass('err-shadow');
            data.status=true;
        }else{
            data.status=false;
            $(el).addClass('err-shadow');
        }
        return data;
    }

function commVld(data) {
    //debugger;
    var el = data.el;
    var status = data.status;
    var msg = data.msg;
    var ele = $(el).parent();
    if (data.key == 'password') {
        ele = ele.parent();
    }else if(data.key == 'mobile'){
        ele = $(el).parent().parent();
    }
    var _reset  = function(ele) {
        if(data.key == 'mobile'){
            $(el).parent().removeClass('err-shadow');
        }else{
            $(el).removeClass('err-shadow');
        }

        ele.find(".er-icon").css('display', 'none');
        ele.find(".error-wrap").css('display', 'none');
    }

    var _error = function(ele, msg) {
        _reset(ele);
        if(data.key == 'mobile'){
            $(el).parent().addClass('err-shadow');
        }else{
            $(el).addClass('err-shadow');
        }
        ele.find(".error-wrap").css('display', 'block');
        ele.find(".error-wrap .e-text").html(msg);
    }

    var _right = function(ele) {
        _reset(ele);
        ele.find(".er-icon").css('display', 'block');
    }

    if (msg === '') {
        _right(ele);
    } else {
        _error(ele, msg);
    }
    return data;
}



// commVld

function inviteVld(data) {
    var el = data.el;
    var status = data.status;
    var msg = data.msg;
    var ele = $(el).parent();
    var val = $(el).val();
    if (data.key == 'password') {
        ele = ele.parent();
    }
    var _reset  = function(ele) {
        $(el).removeClass('err-shadow');
        ele.find(".er-icon").css('display', 'none');
        ele.find(".error-wrap").css('display', 'none');
    }

    var _error = function(ele, msg) {
        _reset(ele);
        $(el).addClass('err-shadow');
        ele.find(".error-wrap").css('display', 'block');
        ele.find(".error-wrap .e-text").html(msg);
    }
    var _right = function(ele, msg) {
        _reset(ele);
        ele.find(".er-icon").css('display', 'block');
        ele.find(".er-icon span").html(msg);

    }

    if (val === '') {
        _reset(ele);
        return data;
    }

    var hash = inviteAjax(val);

    if (hash.status) {
        _right(ele, hash.msg);
    } else {
        _error(ele, hash.msg);
    }
    data.status = hash.status;
    return data;
}

// invite

function inviteAjax(val) {

     var url = '/user/CheckInvitecode';
     var invite_code = val;
     var ele = $('#input-invite');
     var hasError = false;
     var hash = {status: true, msg:''};
     if(invite_code != '' && !inviteAjax[val]) {
        $.ajax({
            type: "get",
            data: {code:invite_code},
            dataType: "json",
            url: url,
            async: false,
            success: function(data) {
                if(data.errno == '0')
                {
                    hash.status = true;
                    if (data.data.userName != '' && data.data.userName.toLowerCase() != 'null') {
                        hash.msg = data.data.userName + '&nbsp;邀请您来到网信';
                    } else {
                        hash.msg = '';
                    }
                }
                else
                {
                    hash.status = false;
                    hash.msg = "推荐人邀请码不正确";
                }
                inviteAjax[val] = {status:hash.status, msg: hash.msg};
             }
           });
     } else if (inviteAjax[val]) {
         hash = inviteAjax[val];
     }
     return hash;
}
// invite ajax
function verifyVld(data) {
    var el = data.el;
    var status = data.status;
    var msg = data.msg;
    var ele = $(el).parent();
    var val = $(el).val();

    if (data.key == 'password') {
        ele = ele.parent();
    }
    var _reset  = function(ele) {
        $(el).removeClass('err-shadow');
        ele.find(".er-icon").css('display', 'none');
        ele.find(".error-wrap").css('display', 'none');
    }

    var _error = function(ele, msg) {
        _reset(ele);
        $(el).addClass('err-shadow');
        ele.find(".error-wrap").css('display', 'block');
        ele.find(".error-wrap .e-text").html(msg);
    }

    var _right = function(ele) {
        _reset(ele);
        ele.find(".er-icon").css('display', 'block');
    }

    if (!data.status) {
        _error(ele, data.msg);
        if(data.key == 'captcha'){
            $("#captcha_message").attr('src', '/verify.php?w=50&h=36&rb=0' + new Date().valueOf());
        }
        return data;
    }

    var hash = verifyAjax(val);

    if (hash.status) {
        _right(ele);
    } else {
        _error(ele, hash.msg);
    }
    data.status = hash.status;
    return data;
}
//verifyVld

function verifyAjax(val) {

     var url = '/user/captchaCheck';
     var captcha = val;
     var ele = $('#input-invite');
     var hasError = false;
     var hash = {status: true, msg:''};

        $.ajax({
            type: "get",
            data: {captcha: captcha},
            dataType: "json",
            url: url,
            async: false,
            success: function(data) {

                if (data && data.code != '0') {
                    if(data.code == '-1')
                    {
                            //本地存储
                            hash.status = false;
                            hash.msg = data.msg;
                            $("#captcha_message").attr('src', '/verify.php?w=50&h=36&rb=0' + new Date().valueOf());
                    }
                    else
                    {
                            hash.status = false;
                            hash.msg = data.msg;
                    }
                } else if (data == null) {
                            hash.status = false;
                            hash.msg = "系统发生错误";

                } else {
                            hash.status = true;
                            hash.msg = "";
                }

                verifyAjax[val] = {status:hash.status, msg: hash.msg};


             }
           });

     //console.log(hash);
     return hash;
}

function submitVld() {
    //debugger;
    var data = phoneVld();
    //15.10.15 ztx 添加
    if (data && 'code' in data && data.code == 1) {
        popup();
        sendMsg(data);
        $("#action-send-code").unbind('click').bind('click', function() {
            sendMsg();
        });
    } else if (data && 'code' in data && data.code == -15){
        verifyVld({el: $('#input-captcha-message'), status: false,key: 'captcha' , msg: data.message}); 
    } else if (data && 'message' in data) {
        commVld({el: document.getElementById('input-mobile'), status: false,key: 'mobile' , msg: data.message});
    }
}

//判断是否第三方注册推广页
var showThirdPage = function($obj){
    if($obj[0] && $obj.val() == '1'){
        return 1;
    }else{
        return 0;
    }
};

// 点击短信验证码弹窗中确认按钮置灰并不可点击
var _request = false;
function updateBtn(){
    if (_request){
        $('.weebox_send_msg .dialog-ok').addClass('btn-gray');
    } else {
        $('.weebox_send_msg .dialog-ok').removeClass('btn-gray');
    }
}
// 验证码逻辑
function popup() {
            var quhao = $('.j_select span').text().replace(/\D*/g,'');
            quhao = quhao.replace(/[^0-9]/, "");
            quhao = quhao == "86" ? "" : (quhao + "-");
            var phone = $("#input-mobile").val();
            var phonelabel = phone;
            phonelabel = phonelabel.replace(/([0-9\-]*)([0-9]{4})([0-9]{4})/, function(_0, _1, _2, _3) {return _1 + "****" + _3 });
            var html ='';
                html += '<div class="wee-send">';
                html += '<div class="send-input">';
                html += '<div class="error-box">';
                html += '<div class="error-wrap" id="error-wrap">';
//                html += '<div class="e-arrow"></div>';
                html += '<div class="e-text" style="width: 267px;"></div>';
                html += '</div>';
                html += '</div>';
                html += '<p>已向&nbsp<span class="color_green">' + phonelabel + '</span>&nbsp发送验证短信</p>';

                html += '<input type="text" class="ipt-txt" id="pop_code" placeholder="短信验证码" maxlength="10">';
                html += '<input type="button" id="action-send-code" class="reg-sprite btn-blue-h34 btn-gray-h34" value="发送">';
                html += '</div>';
                html += '</div>';
            $.weeboxs.open(html, {
                boxid: null,
                boxclass: 'weebox_send_msg',
                contentType: 'text',
                showButton: true,
                showOk: true,
                okBtnName: '确定',
                showCancel: false,
                title: '填写短信验证码',
                width: 436,
                height: 125,
                type: 'wee',
                onclose: function() {
                    //location.reload();
                },
                onok: function() {
                    var code = $("#pop_code").val(),
                    $form = $(__formpage__.frm),
                    url = $form.attr("action"),
                    $text = $(".error-box").find('.e-text'),
                    showError = function(){
                        $(".error-box").css({
                            'display': 'block' ,
                            'visibility' : 'visible'
                        });
                        showNormal();
                    };
                    if (!/^\d{6}$/.test(code)) {
                        showError();
                        $text.html("请填写6位数字验证码");
                        return;
                    }
                    $('#input-code').val(code);
                    //判断如果是第三方推广页，则进行表单同步提交；否则走异步提交
                    if (showThirdPage($("#thirdPage")) == 1) {
                        showError();
                        $text.html("正在提交中，请稍候");
                        $form.unbind("submit").submit();
                        $("#submit_button").attr("disabled" , "disabled");
                       
                    } else {
                        if (_request) return;
                        _request = true;
                        updateBtn();
                        $.ajax({
                            url: '/user/webDoLogin',
                            type: "post",
                            data: $form.serialize() + "&isAjax=1&type=16&country_code=" + $("#country_code").val() ,
                            dataType: "json",
                            beforeSend: function() {
                                //$text.html("正在提交，请稍候...");
                            },
                            success: function(data) {
                                if (data.errorCode === 0) {
                                    $("#login_v2").append('<input type="hidden" name="code" value="'+ $("#pop_code").val() +'" >').unbind("submit").submit();
                                    $.weeboxs.close();
                                    // var sourceObj = $('#input-source');
                                    // if (sourceObj.val()) {
                                    //     location.href = sourceObj.attr('jump-url');
                                    // } else {
                                    //     location.href = "/user/SmDoLogin" + $form.serialize() + "&isAjax=1&country_code=" + $("#country_code").val();
                                    // }
                                } else {
                                    showError();
                                    $text.html(data.errorMsg);
                                }
                                _request = false;
                                updateBtn();
                            },
                            error: function() {
                                showError();
                                $text.html("服务器错误，请重新再试！");
                                _request = false;
                                updateBtn();
                            }
                            
                        });
                    }
                    //$.weeboxs.close();
                }
            });
         
          //判断第三方推广注册页则不允许关闭
          if (showThirdPage($("#thirdPage")) == 1) {
                $(".dialog-close").remove();
          }
}
function showNormal(){
    $("#error-wrap").find('.e-text').css("width", "267px");
    $("#error-wrap").removeClass("error-wrap2");
    $(".dialog-content").css("height", "125px");
}

//popup

function sendMsg(_data) {
    //电话号码 正则去掉 必然正确才这里
    var phone = $("#input-mobile").val();
    var button = $("#action-send-code");
    // token_id  token 后台传递的    captcha 手输验证码
    var token_id = $("#token_id").val();
    var token = $("#token").val();
    var captcha = $("#input-captcha-message").val();
    var errorSpan = $(".error-box");
    var sendcodeUrl = '/user/MCode';
    sendMsg.sendNum = sendMsg.sendNum || 1;
    var btGray = function(){
        button.addClass("btn-gray-h34");
        button.val("正在获取中...");
        button.attr('disabled', 'disabled');
    };

    var _set = function(msg, status) {
        errorSpan.css('visibility', 'visible');
        if (status == 0) {
            errorSpan.find('.e-text').html(msg);
            errorSpan.css('visibility', 'visible');
            errorSpan.find('.e-text').html(msg);
            showNormal();
        }
        if (status == 1) {
            errorSpan.css('visibility', 'visible');
            errorSpan.find('.e-text').html(msg);
            $("#error-wrap").find('.e-text').css("width", "372px");
            $("#error-wrap").addClass("error-wrap2");
            $(".dialog-content").css("height", "148px");
        }
    }
    var _reset = function() {
    errorSpan.css('visibility', 'hidden');
    errorSpan.find('.e-text').html('');
    }
    _reset();
    if (phone == '') {
     _set('手机号不能为空', 0);
     return;
    }
    btGray();
    function updateTimeLabel(duration) {
     var timeRemained = duration;
     var timer = setInterval(function() {
         button.val(timeRemained + '秒后重新发送');
         timeRemained -= 1;
         if (timeRemained == -1) {
             clearInterval(timer);
             button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
         }
     }, 1000);
    }
    var hash;
    var callback = function(data) {
           hash = data;
       if (data.code == 1) {
           updateTimeLabel(60, 'action-send-code');
           return;
       } else {
        _set(data.message,0);
       }
       button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
    }
    var _sendMsg = function(url, isrsms){
       $.ajax({
           type: "post",
           data: {
              type : '16',
              isrsms : isrsms,
              t : new Date().getTime(),
              mobile : phone,
              token : token,
              token_id : token_id,
              captcha : captcha,
              country_code : $("#country_code").val(),
              smLoginToken : $("#smLoginToken").val(),
              resend : 1
           },
           url: url,
           async: false,
           dataType: "json",
           success: function(data) {
               callback(data);
           }
       });
    };

    if (_data && 'code' in _data) {
        callback(_data);
        return hash;
    }

    if(sendMsg.sendNum == 1){
       _sendMsg(sendcodeUrl, 0);
    }else{
       _sendMsg(sendcodeUrl, 0);
    }
    sendMsg.sendNum = 2;
    return hash;
}

//sendMsg
function phoneVld() {
    var phone = $("#input-mobile").val();
    var token_id = $("#token_id").val();
    var token = $("#token").val();
    var captcha = $("#input-captcha-message").val();
    var hash = {};
    var sendcodeUrl = '/user/MCode';
    var _sendMsg = function(url, isrsms){
       $.ajax({
           type: "post",
           data: {
              type : '16',
              isrsms : isrsms,
              t : new Date().getTime(),
              mobile : phone,
              token : token,
              token_id : token_id,
              captcha : captcha,
              country_code : $("#country_code").val(),
              smLoginToken : $("#smLoginToken").val()
           },
           url: url,
           async: false,
           dataType: "json",
           success: function(data) {
                hash = data;
                if(data.code != 1){
                    if($('.graph-ver').length <= 0){
                        $('.logintips').after('<li class="graph-ver clearfix">\
                            <input type="text" value="" data-con="require" placeholder="右侧数字" name="captcha" id="input-captcha-message" class="txt int_placeholder" data-placeholder="右侧数字">\
                            <div class="img-Veri">\
                                <img title="点击刷新" src="/verify.php?w=50&h=36&rb=0" id="captcha_message" width="100" height="38">\
                            </div>\
                            <div class="changeCap"><a href="javascript:void(0);" class="refresh_message">换一张</a></div>\
                            <div class="ui-form">\
                                <div class="error-wrap">\
                                    <div class="ew-icon"><i class="form-sprite icon-wrong"></i></div>\
                                    <div class="e-text"></div>\
                                </div>\
                            </div>\
                        </li>');
                        $('#captcha-message').attr('src',"/verify.php?w=50&h=36&rb=0" + new Date().valueOf());
                        new FormPage().check($("#input-captcha-message")[0]);
                    }
                }
           }
       });
    };
    _sendMsg(sendcodeUrl, 0);
    return hash;
}

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
    function() {
        var el = $("#captcha_message");
        var refreshCaptcha = $(".refresh_message");
        var ele_img = $(el);
        var _right = function(ele) {
            //_reset(ele);
            ele.find(".er-icon").css('display', 'block');
        };
        var _reset  = function(ele) {
            $(el).removeClass('err-shadow');
            ele.find(".er-icon").css('display', 'none');
            ele.find(".error-wrap").css('display', 'none');
        };
        var fn = function() {
            // el.src = "/verify.php?w=50&h=36&rb=0" + new Date().valueOf();
            el.attr('src',"/verify.php?w=50&h=36&rb=0" + new Date().valueOf());
        };
        refreshCaptcha.css('cursor', 'pointer');
        $('#login_v2').on('click','#captcha_message',function() {
            $(this).attr('src',"/verify.php?w=50&h=36&rb=0" + new Date().valueOf());
            new FormPage().check($("#input-captcha-message")[0]);
        });
        $('#login_v2').on('click','.refresh_message',function() {
            $("#captcha_message").attr('src',"/verify.php?w=50&h=36&rb=0" + new Date().valueOf());
            new FormPage().check($("#input-captcha-message")[0]);
        });
    },
    //update
    function() {
        // $(".p2p-ui-checkbox").p2pUiCheckbox();
        //agree
        var agree = $('#agree');
        agree.unbind('change').bind('change', function(e) {
            agreeVld({el: this});
        })
    }
]
for (var i = 0, len = elist.length; i < len; i++) {
    elist[i]();
}
$('.j_transLogin').bind('click',function(event) {
    $(this).closest('form').hide();
    var fmId = $(this).closest('form').prop('id');
    if(fmId == "loginForm"){
        $('.p_user_dologin .height_placeholder .login_err').hide();
    }
    $(this).closest('form').siblings('form').show();
});
$('#login_v2').on('blur', '#input-captcha-message', function(event) {
    $('#captcha-message').attr('src',"/verify.php?w=50&h=36&rb=0" + new Date().valueOf());
    new FormPage().check($("#input-captcha-message")[0]);
});
});
