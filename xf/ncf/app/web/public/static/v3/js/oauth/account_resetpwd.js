var pas_strength_mod;;
(function($) {
    $(function() {
        var pasInput = $('#new_password');
        var jQJson = {
            input: pasInput,
            strengthWrap: pasInput.nextAll('.pass-item-tip-password')
        };
        pas_strength_mod = new Pas_strength(jQJson);

        $("#new_password").on("blur", function() {
            $("#tips-pass").hide();
        });
        $("#new_password").on("focus", function() {
            $(".msg-box:first").hide();
        });
        var editForm = $('#setnew_form').validator({
            isNormalSubmit: true,
            ajaxDataCode : "errorCode" ,
            ajaxDataMsg : "errorMsg" ,
            messages: {
                match: "确认密码和新密码不一致"
            },
            fields: {
                'new_password': '新密码:required;checkPass;',
                'confirmPassword': '确认密码:required;match[eq,new_password];'
            },
            rules: {
                checkPass: function(element) {
                    var dataJson = {
                            pwd: element.value,
                            flag: "1"
                        },
                        $textShow = $(element).parent().find(".color-gray2");
                    return $.ajax({
                        type: "post",
                        data: dataJson,
                        url: '/user/PasswordCheck',
                        dataType: "json",
                        success: function(msgData) {
                            var $p = $(element).parent();
                            if(msgData.errorCode != 0){
                                $textShow.hide();
                            }else{
                                $textShow.show().find(".color-low").text(msgData.errorMsg);
                            }
                        },
                        error: function(){
                            return "繁忙中，请重新再试"
                        }
                    });
                }
            },
            valid: function(form) {
                return true;
            }
        }).data('validator');        
    });
})(jQuery);
