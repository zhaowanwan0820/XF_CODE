;(function($) {
    var mobileRegEx;

    function mbsetpro() {
        var mobileReg = Firstp2p.mobileReg;
        mobileReg["cn"] = '';
        mobileRegEx = mobileReg[$('#country_code').val()];
    }
    var isEnterpriseSite = $('#isEnterpriseSite').val();
    function mbValid() {
        mbsetpro();
        var $user = $("#user"),
            usernameVal = $.trim($user.val());
        $('.login_err').hide();
        //isEnterpriseSite = 1是企业站
        if (mobileRegEx == '') {
            if (!usernameVal || usernameVal == '用户名') {
                $user.focus();
                if(isEnterpriseSite == 1){
                    $user.addClass('err-shadow');
                }else{
                    $user.parent().addClass('err-shadow'); 
                }
                if(isEnterpriseSite == 1){
                    $('.login_err').html('请输入用户名').show();
                }else{
                    $('.login_err').html('请输入手机号或用户名').show();
                }
                return false;
            }
        } else {
            if (usernameVal == '') {
                $user.focus();
                if(isEnterpriseSite == 1){
                    $user.addClass('err-shadow');
                }else{
                    $user.parent().addClass('err-shadow'); 
                }
                
                if(isEnterpriseSite == 1){
                    $('.login_err').html('用户名不能为空').show();
                }else{
                    $('.login_err').html('手机号不能为空').show();
                }
                return false;
            } else if (!mobileRegEx.test(usernameVal)) {
                $user.focus();
                if(isEnterpriseSite == 1){
                    $user.addClass('err-shadow');
                }else{
                    $user.parent().addClass('err-shadow'); 
                }
                if(isEnterpriseSite == 1){
                    $('.login_err').html('用户名格式不正确').show();
                }else{
                    $('.login_err').html('手机号格式不正确').show();
                }
                return false;
            }
        }
        return true;
    }
    $(function() {
        $(".int_placeholder").each(function(i , v) {
            var p_text = $(this).attr("data-placeholder"),
            zIndex = 20;
            if(i === 0){
                zIndex = 21
            }
            new Firstp2p.placeholder(this, {
                placeholder_text: p_text == null ? "请输入" : p_text ,
                placeholder_paddingLeft : 32 ,
                isIE7Show : false ,
                placeholder_zIndex : zIndex
            });
        });

        //海外手机号下拉框样式修改
        (function(){
            $(".select_box").select({
                onSelectChange: function($t, $input, index, $li) {
                    if (index === 0) {
                        $("#user").attr("placeholder", "手机号");
                        if(!!$.browser.msie){
                            $(".user_placeholder").html("手机号");
                        }
                    } else {
                        $("#user").attr("placeholder", "手机号");
                        if(!!$.browser.msie){
                            $(".user_placeholder").html("手机号");
                        }
                    }
                    $t.find('.j_select').html('<span style="color:#555353;font-size: 14px;">+'+$li.data('value')+'</span>');
                    mbsetpro();
                }
            });
            if(isEnterpriseSite != 1){
                $('#user').css('width',196);
            }
            $(".j_select").css({
                'width':65,
                'padding':'0px 0px 0px 13px',
                'background-position':'58px 16px',
                'color': '#333',
            }).html('<span style="color:#555353;font-size: 14px;">+86</span>');
            $(".select_box").next('div.ipt-wrap').css('width',209);
            $('.select_ul').css({
                'padding':'5px 3px 5px 3px',
                'width':'131'
            }).find('li').css({
                'padding':'0px 9px 0px 6px'
            }).html(function(){
                var curIconText=$(this).data('name');
                var areaName="";
                var areaCode=$(this).data('value');
                switch (curIconText){
                    case 'cn':areaName="中国";break;
                    case 'hk':areaName="中国香港";break;
                    case 'mo':areaName="中国澳门";break;
                    case 'tw':areaName="中国台湾";break;
                    case 'us':areaName="美国";break;
                    case 'ca':areaName="加拿大";break;
                    case 'uk':areaName="英国";break;
                    default :areaName="中国";
                }
                return $('<span style="float:left;">'+areaName+'</span><span style="float:right;">+'+areaCode+'</span>')
            });
        })();


        $("#user").bind("focus", function() {
            var $p = $(this).parent();
            $p.removeClass('err-shadow');
            if(isEnterpriseSite != 1){
               $p.addClass('ipt-focus'); 
            }            
        }).bind("blur", function() {
            $(this).parent().removeClass('ipt-focus').removeClass('err-shadow');
        });
    });


    var today = new Date();
    var expireDay = new Date();
    var msPerWeek = 24 * 60 * 60 * 1000 * 7;

    expireDay.setTime(today.getTime() + msPerWeek);

    function setCookie(Key, Value) {
        document.cookie = Key + "=" + Value + ";expires=" + expireDay.toGMTString();
    }

    function getCookie(Key) {
        var search = Key + "=";
        begin = document.cookie.indexOf(search);
        if (begin != -1) {
            begin += search.length;
            end = document.cookie.indexOf(";", begin);
            if (end == -1) end = document.cookie.length;
            return document.cookie.substring(begin, end);
        }
    }


    $(function() {
        var remeber = getCookie('PHPREMEMBER');
        var username = getCookie('username');
        var usertype = getCookie('PHPUSERTYPE');
        if (remeber == 'true') {
            $('#user').val(username);
            $('input[name="remember_name"][type="checkbox"]').attr('checked', true);
        }
        $("#loginForm").submit(function() {
            var $pass = $("#input-password"),
                $code = $("#input-captcha"),
                $dom_id = $("#dom_id"),
                pass = $pass.val(),
                csessionid = $('#csessionid').val(),
                sig = $('#sig').val(),
                risk_token = $('#risk_token').val(),
                $t = $(this);
            if (!mbValid()) {
                //如果手机号验证不合法，返回false
                return false;
            }
            if (!pass) {
                $pass.focus();
                $('.login_err').html('请输入密码').show();
                return false;
            }
            if ($code.length > 0 && !$.trim($code.val())) {
                $code.focus();
                $('.login_err').html('请输入验证码').show();
                return false;
            }
            // 取隐藏域中的值，当值为1时加滑块验证，否则不加
            var test_switch = $("#test_switch").html();
            if(test_switch==1){
                if($dom_id.length > 0 && !sig){
                    $('.login_err').html('请滑动滑块完成验证').show();
                    return false;
                }
            }
            $.ajax({
                url : '/user/LoginRestrict',
                dataType : 'json',
                data : {
                    'username' : $.trim($('#user').val()),
                    'country_code' : $('#country_code').val(),
                    'password' : $("#input-password").val(),
                    'csessionid' : csessionid,
                    'sig' : sig,
                    'risk_token' : risk_token,
                    'scene' : $('#scene').val()
                },
                type : "post",
                success : function(data){
                    if(data.errorCode === 0){
                        if(!!data.mobile){
                            $("#valid_phone").val(data.mobile);
                        }else{
                            $("#valid_phone").val("您的手机号");
                        }
                        Firstp2p.getMessage(data);
                    }else{
                        $t.unbind("submit").submit();
                    }
                }
            });
            return false;
        });
    });
})(jQuery);
