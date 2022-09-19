(function($) {
    $(function() {
        (function() {
            //忘记密码身份证验证页
            var editForm = $('#passForm').validator({
                isNormalSubmit: false,
                messages: {
                    required: "身份证号不能为空",
                    format: "身份证号格式不正确"
                },
                fields: {
                    'idCard': 'required;format;checkAjax;'
                },
                rules: {
                    format: function(el) {
                        return /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/.test(el.value.trim());
                    },
                    checkAjax: function(element) {
                        var msgData = null,
                        $el = $(element);
                       
                        $.ajax({
                            type: "post",
                            data: {
                           "idno": element.value,
                           "ajax": 1 
                            },
                            url: $el.data("url"),
                            dataType: "json",
                            async: false,
                            success: function(data) {
                                msgData = data;
                            }
                        });

                        if (msgData.info.code == "1") {
                            return msgData.info.msg;
                        } else if(msgData.info.code == "-1"){
                            location.href = msgData.jump + '?error=error_jump';
                        }

                    }
                },
                valid: function(form) {
                    $.ajax({
                        type: "post",
                        dataType: "json",
                        data: {
                            "idno": $('#idCard').val(),
                            "token": $("#token").val(),
                            "token_id": $("#token_id").val()
                        },
                        url: $(form).attr("action"),
                        success: function(data) {
                            if (data.info.code == '0') {
                                location.href = data.jump;
                            } else if (data.info.code == '-1') {
                                location.href = data.jump + '?error=error_jump';
                            }
                        },
                        error: function() {}
                    });
                }
            }).data('validator');

        })();
    });
})(jQuery);