//初始化
(function($) {
    $(function() {
        var mobileRegEx,
            mbsetpro = function() {
                var mobileReg = Firstp2p.mobileReg;
                mobileRegEx = mobileReg[$('#country_code').val()];
            };
        //下拉菜单
        $(".j_select").css("color", "#333");
        /*$(".select_box").select({
            onSelectChange: function($t, $input, index, $li) {
                $("#areacode").html('+' + $li.data("value"));
                mbsetpro();
                $('#password-form').validator('setRule', {
                    mobile: [mobileRegEx, '手机号格式不正确']
                });
                if($.trim($("#mobile").val()) != ''){
                    $('#mobile').trigger("validate");
                }
            }
        });*/

        //海外手机号下拉框样式修改
        (function(){
            $(".select_box").select({
                onSelectChange: function($t, $input, index, $li) {
                    mbsetpro();
                    $('#password-form').validator('setRule', {
                        mobile: [mobileRegEx, '手机号格式不正确']
                    });
                    if($.trim($("#mobile").val()) != ''){
                        $('#mobile').trigger("validate");
                    }
                    $t.find('.j_select').html('<span style="color:#555353;font-size: 14px;">+'+$li.data('value')+'</span>');
                }
            });

            $(".j_select").css({
                'width':65,
                'padding':'0px 0px 0px 13px',
                'background-position':'58px 13px'
            }).html('<span style="color:#555353;font-size: 14px;">+86</span>');
            $(".select_box").css('position','relative').next('div.ipt-wrap').css('width',209);
            $('.select_ul').css({
                'padding':'5px 3px 5px 3px',
                'width':'131',
                'top':35
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

        mbsetpro();
        // 表单验证         
        $('#password-form').validator({
            rules: {
                mobile: [mobileRegEx, '手机号格式不正确'],
                validN: [/^\d{6}$/, '请填写6位数字验证码']
            },
            fields: {
                captcha: "验证码:required;",
                mobile: "手机号: required;mobile;",
                code: "验证码: required;validN;"
            }
        });


        $("#mobile").bind("focus", function() {
            var $p = $(this).parent();
            $p.removeClass('err-shadow');
            $p.addClass('ipt-focus');
        }).bind("blur", function() {
            var $p = $(this).parent();
            $p.removeClass('ipt-focus');
            if ($(this).attr('data-inputstatus') == 'error') {
                $p.addClass('err-shadow');
            }
            $(this).removeClass('n-invalid');
        });

        // 刷新图形验证码
        var el = document.getElementById("captcha"),
            ele_f_captcha = $(".refresh"),
            ele_img = $(el),
            fn = function() {
                el.src = "/verify.php?w=50&h=36&rb=0&rand=" + new Date().valueOf();
            };
        ele_f_captcha.bind("click", function() {
            fn();
        });
        ele_img.bind("click", function() {
            fn();
        });

        //控制获取验证码
        var clearTime = false;
        $("#bt").click(function() {
            var button = $(this),
                url = '/user/MCode',
                mobile = $("#mobile").val(),
                token = $("#token").val(),
                token_id = $("#token_id").val(),
                captcha = $("#input-captcha").val(),
                
                data = {
                    mobile: mobile,
                    type: 1,
                    isrsms: 0,
                    sms_type: 1,
                    token: token,
                    token_id: token_id,
                    captcha: captcha,
                    country_code: $("#country_code").val()
                };


            button.attr('disabled', 'disabled');
            updateTimeLabel(180);

            function updateTimeLabel(duration) {
                var timeRemained = duration;
                var timer = setInterval(function() {
                    button.val('重新发送(' + timeRemained + ')').attr('disabled', 'disabled').addClass("btn-send-gray");;
                    timeRemained -= 1;
                    if (timeRemained == -1 || clearTime == true) {
                        clearInterval(timer);
                        button.val('获取手机验证码').removeAttr('disabled').removeClass("btn-send-gray");
                        clearTime = false;
                    }

                }, 1000);
            }
            //获取验证码
            $.post(url, data, function(rs) {
                var rs = $.parseJSON(rs);
                if (rs.code == 1) {
                    updateTimeLabel(180);
                    return;
                } else {
                    $.showErr(rs.message, function() {}, "提示");
                    clearTime = true;
                }
            }); //post
            return false;
        });
    });
})(jQuery);