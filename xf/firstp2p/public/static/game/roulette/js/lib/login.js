$(function(){
    $('#codeImg').on('click',refresh);
    function refresh(){
        var srcStr="/verify.php?w=91&h=36&rb=0&rand="+new Date().getTime();
        $(this).attr('src',srcStr);
    }
    function verifyForm() {
        var flag=true;
        var account=$('#accountInput');
        var pas=$('#pasInput');
        var code=$('#codeInput');

        if (emptyReg.test(account.val())){
            showToast('帐号不能为空');
            flag=false;
            return flag;
        }
        if (emptyReg.test(pas.val())){
            showToast('密码不能为空');
            flag=false;
            return flag;
        }
        if (code.length && emptyReg.test(code.val())){
            showToast('验证码不能为空');
            flag=false;
            return flag;
        }
        return flag;
    }
    $('#submitBtn').on('click',function (event) {
        if (!verifyForm()){
            event.preventDefault();
        }
    });
});