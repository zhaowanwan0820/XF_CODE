$(function() {
    var validator = new FormValidator('register_form', [{
        name: 'code',
        rules: 'required|code'
    }, {
        name: 'username',
        rules: 'required|username'
    }, {
        name: 'password',
        rules: 'required|password'
    }], function(errors, evt) {
        var el = evt.target;
        var name = el.getAttribute("name");
        if (typeof validatorFn[name] == "function") {
            return validatorFn[name](el,errors);
        }
    });

    var validatorFn = {
        'username': function(el) {
            var token_id = $("#token_id").val();
            var token = $("#token").val();
            var val = $(el).val();
            var numRegEx = /^[_\d]+$/;
            if (val.length == 0) {
                showError('username', '用户名不能为空');
            }else if (val.length < 4) {
                showError('username', '用户名至少需要 4 个字符');
            } else if (val.length > 16) {
                showError('username', '用户名最多 16 个字符');
            } else if (!WXLC.ValidateConf.userName[3].test(val)) {
                showError('username', '用户名请输入4-16位字母、数字、下划线、横线，首位只能为字母');
            } else if (numRegEx.test(val)) {
                showError('username', '不能只有数字或下划线');
            }
        },
        'register_form': function(el,errors) {
            if(errors.length > 0) return;
            var token_id = $("#token_id").val();
            var token = $("#token").val();
            var code = $('#code').val();
            var username = $('#username').val();
            var password = $('#password').val();
            var mobile = $('#mobile').val();
            var cn = $('#cn').val();
            var site_id = $('#site_id').val();
            lock();
            $.ajax({
                type: "post",
                data: {
                    token: token,
                    token_id: token_id,
                    code: code,
                    username: username,
                    password: password,
                    mobile: mobile,
                    invite: cn
                },
                dataType: "json",
                url: '/user/DoH5Register',
                success: function(data) {
                    unlock();
                    if (data.errorCode != '0') {
                         $('.ui-all-error').html(data.errorMsg).show();
                    } else {
                        window.location.href= "/hongbao/CashRegisterSuccess?cn="+cn+"&site_id="+site_id;
                    }
                },
                error: function() {
                    unlock();
                    alert('系统发生错误');
                }
            })
        }
    };

    // 手机验证码
    $('#action-send-code').click(function() {
        lock();
        var $self = $(this);
        var token_id = $("#token_id").val();
        var token = $("#token").val();
        var phone = $("#mobile").val();

        $('#action-send-code').val('发送中...').addClass('ui-btn-subing').attr('disabled', 'disabled');
        $.ajax({
            type: 'POST',
            url: '/user/H5MCode',
            data: {
                type: '1',
                isrsms: '0',
                t: new Date().getTime(),
                mobile: phone,
                token: token,
                token_id: token_id,
            },
            dataType: 'json',
            success: function(data) {
                unlock();
                if (data.code == 1) {
                    updateTimeLabel(60);
                } else {
                    $self.val('重新发送').removeClass('ui-btn-subing').removeAttr('disabled');
                    showError('code', data.message);
                }
            },
            error: function() {
                alert('发送失败，请重试!');
                $self.val('获取验证码').removeClass('ui-btn-subing').removeAttr('disabled');
            }
        });

        // 倒计时
        function updateTimeLabel(duration) {
            var timeRemained = duration;
            var timer = setInterval(function() {
                $self.val(timeRemained + '秒重送');
                timeRemained -= 1;
                if (timeRemained == -1) {
                    clearInterval(timer);
                    $self.val('重新发送').removeClass('ui-btn-subing').removeAttr('disabled');
                }
            }, 1000);
        }
    });
});
