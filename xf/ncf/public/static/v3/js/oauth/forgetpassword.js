;
(function($) {
    $(function() {
        var mobileRegEx,
            mbsetpro = function() {
                var mobileReg = Firstp2p.mobileReg;
                mobileRegEx = mobileReg[$('#country_code').val()];
            },
            mbValid = function() {
                mbsetpro();
                var inputMobile = $("#input-mobile").val(),
                    codeBtn = $('#action-send-mobile-code');
                codeBtn.prev().html('');
                if (inputMobile == '') {
                    codeBtn.prev().html('手机号不能为空');
                    return false;
                } else if (!mobileRegEx.test(inputMobile)) {
                    codeBtn.prev().html('手机号格式不正确');
                    return false;
                }
                return true;
            };

        //海外手机号下拉框样式修改
        (function(){
            $(".select_box").select({
                onSelectChange: function($t, $input, index, $li) {
                    mbsetpro();
                    if($.trim($("#input-mobile").val()) != ''){
                        mbValid();
                    }
                    $t.find('.j_select').html('<span style="color:#555353;font-size: 14px;">+'+$li.data('value')+'</span>');
                }
            });

            $(".j_select").css({
                'width':65,
                'padding':'0px 0px 0px 13px',
                'background-position':'58px 13px',
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

        $("#input-mobile").bind("focus", function() {
            var $p = $(this).parent();
            $p.removeClass('err-shadow');
            $p.addClass('ipt-focus');
        }).bind("blur", function() {
            $(this).parent().removeClass('ipt-focus').removeClass('err-shadow');
        });
        mbsetpro();
        var type = 1;
        $('.tab li a').click(function(e) {
            e.preventDefault();
            $('.tab li').removeClass('active');
            $(this).parent().addClass('active');
            $('.tab-content').hide();
            type = $(this).attr('rel');
            $('.tab-content-' + type).show();
            $('#input_type').val(type);
        });

        $('#action-send-mobile-code').on('click', function(ev) {
            var inputMobile = $("#input-mobile").val();
            var button = $(this);
            var token_id = $("#token_id").val();
            var token = $("#token").val();
            if (!mbValid()) {
                //如果前端验证不合法，立即返回
                return;
            }
            button.attr('disabled', 'disabled');

            function updateTimeLabel(duration) {
                var timeRemained = duration;
                var timer = setInterval(function() {
                    button.val('重新发送(' + timeRemained + ')').attr(
                        'disabled', 'disabled').removeClass('btn-send-blue').addClass('btn-send-gray');
                    timeRemained -= 1;
                    if (timeRemained == -1) {
                        clearInterval(timer);
                        button.val('获取手机验证码').removeAttr('disabled').removeClass('btn-send-gray').addClass('btn-send-blue');
                    }
                }, 1000);
            }

            $.ajax({
                type: "post",
                data: {
                    type: '2',
                    mobile: inputMobile,
                    token: token,
                    token_id: token_id,
                    country_code: $("#country_code").val()
                },
                url: button.data("url"),
                async: false,
                dataType: "json",
                success: function(data) {
                    if (data.code == 1) {
                        updateTimeLabel(180);
                        return;
                    } else {
                        button.val('获取手机验证码').removeAttr('disabled');
                        $.showErr(data.message, function() {}, "提示");

                    }
                }
            });
        });
        var valid = function(ev,flag) {
            var errors = [];
            var inputMobile = $.trim($('#input-mobile').val()),
                inputMobileVc = $.trim($('#input-mobile-vc').val());
            var mobileTip = $('#input-mobile').parents('.pwd-item:first').next();
            if (inputMobile == '') {
                mobileTip.html("手机号不能为空");
                return false;
            } else if (!mobileRegEx.test(inputMobile)) {
                mobileTip.html("手机号格式不正确");
                return false;

            } else {
                mobileTip.html("");
            }
            if (inputMobileVc == '') {
                $('#input-mobile-vc').next().html("手机验证码不能为空");
                return false;

            } else {
                $('#input-mobile-vc').next().html("");
            }

            var inputPassword = $('#input-password').val(),
                inputRetypePassword = $('#input-retype-password').val();
            if(typeof flag=='undefined'){
                if(!pas_strength_forget.blurFn(true)){
                    return false;
                }
            }else if(!flag){
                return false;
            }

            if (inputRetypePassword == '') {
                $('#input-retype-password').next().html("确认密码不能为空");
                return false;

            } else if (inputRetypePassword != inputPassword) {
                $('#input-retype-password').next().html("两次填写的密码不一致");
                return false;

            } else {
                $('#input-retype-password').next().html("");
            }
            return true;
        };

        $('#password-form').submit(function(ev) {
            if (!valid(ev)) {
                return false;
            } else {
                return true;
            }
        });

        $('#password-form').on("blur", ".text,.txt-tel", function(ev) {
            var flag=false;
            if ($(this).attr('id') == 'input-password') {
                flag = pas_strength_forget.blurFn(true);
                valid(ev, flag);
            } else {
                valid(ev);
            }
        });
    })
})(jQuery);

window.onload = function() {
    $("#password-form .text").val("");
};

//密码强度
//TODO 验证密码强度和合法性
var pas_strength_forget;
(function(){
    $(function(){
        var pasInput=$('#input-password');
        var jQJson={
            input:pasInput,
            strengthWrap:pasInput.nextAll('.pass-item-tip-password')
        }
        pas_strength_forget=new Pas_strength(jQJson);
    });
})();