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

        var editForm = $('#setnew_form').validator({
            isNormalSubmit: true,
            messages: {
                match: "确认密码和新密码不一致"
            },
            fields: {
                'new_password': '新密码:required;checkPass;checkAjax;',
                'confirmPassword': '确认密码:required;match[eq,new_password];'
            },
            rules: {
                checkPass: function(element) {
                    var dataJson = {
                            pwd: element.value,
                            flag: "1"
                        },
                        msgData = null,
                        $textShow = $(element).parent().find(".color-gray2");

                    $.ajax({
                        type: "post",
                        data: dataJson,
                        url: '/user/PasswordCheck',
                        dataType: "json",
                        async: false,
                        success: function(data) {
                            msgData = data;
                        }
                    });
                    if (msgData.errorCode != 0) {
                        $textShow.hide();
                        return msgData.errorMsg;
                    } else {
                        $textShow.show().find(".color-low").text(msgData.errorMsg);
                    }
                },
                checkAjax: function(element) {
                    var dataJson = {
                        "new_password": element.value,
                        "ajax": 1
                    },
                    msgData = null,
                    $textShow = $(element).parent().find(".color-gray2"),
                    $el = $(element);
                    $.ajax({
                        type: "post",
                        data: dataJson,
                        url: $el.data("url"),
                        dataType: "json",
                        async: false,
                        success: function(data) {
                            msgData = data;

                        }
                    });
                    if (msgData.info.code == '0') {
                        $textShow.hide();
                        return msgData.info.msg;
                    } else if (msgData.info.code == '-1'){
                        location.href = msgData.jump + '?error=error_jump';
                    } else {
                        $textShow.show().find(".color-low").text(msgData.errorMsg);  
                    }
                }
            },
            valid: function(form) {
                return true;
            }

        }).data('validator');
    });
})(jQuery);
