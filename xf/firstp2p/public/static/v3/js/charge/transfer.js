//余额划转js文件
$(function() {

    var submitBtn=$('#submitBtn');//确认按钮
    var form=$('#transferForm');
    var moneyInput=$('#moneyInput');
    var errorTip=$('#errorTip');
    var orderSn = $('#orderSnHidden').val();
    var switchUl=$('#switchUl');
    var transferService=$('#transferService');
    
    function checkMoney() {
        var val=moneyInput.val();
        var flag={
            valid:true,
            mes:""
        }
        if (/^\s*$/.test(val)){
            flag.valid=false;
            flag.mes="此处不可空白 请输入有效的数字金额";
        }else if (isNaN(val) || val<=0 || val*100>parseInt(val*100)){
            flag.valid=false;
            flag.mes="请输入有效的数字金额 ";
        }
        if (!flag.valid){
            errorTip.text(flag.mes).show();
        }else{
            errorTip.hide();
        }
        return flag;
    }

    moneyInput.on('input blur focus',function () {
        checkMoney();
    });

    submitBtn.on('click',function () {
        if (checkMoney().valid){
            Firstp2p.supervision.wancheng();
            transferService.val(switchUl.find('li.active').data('val'));
            form.submit();
        }
    });

    switchUl.on('click','li',function () {
        $(this).siblings().removeClass('active');
        $(this).addClass('active');
        transferService.val($(this).data('val'));
    });

});
