;
(function($) {
    $(function() {
        var validator = new FormValidator('login_form', [{
            name: 'account',
            rules: 'required'
        }, {
            name: 'password',
            rules: 'required'
        }, {
            name: 'verify',
            rules: 'required'
        }], function(errors, evt) {       

        });

        // 显示密码
        var pwdFlag = false;
        $('#pwd_show_btn').click(function(event) {
            if(!pwdFlag){
                $('#password').attr('type', 'text');
                pwdFlag = true;
            }else{
                $('#password').attr('type', 'password');
                pwdFlag = false
            }
        });

        // 图形验证码
        $('.dl_yanzhengma img').attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());
        $('.dl_yanzhengma img').click(function() {
            $(this).attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());
        });
    });
})(Zepto);
