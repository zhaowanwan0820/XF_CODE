
(function ($) {
    $(function () {
        (function () {
            // 点击每笔投资的取消投资按钮
            $('body').on('click', '.JS_cancel_invest', function () {
                var cancelTypeObj = $(this).data('cancelinfo');
                var cType = cancelTypeObj.cancelType;
                var cLoanId = cancelTypeObj.cancelLoanId;
                if (cType == "2") {
                    $('#cancel_cont_02').hide();
                    $('#cancel_cont_03').hide();
                    $('#cancel_cont_01').show();
                } else if (cType == "0") {
                    $('#cancel_cont_01').hide();
                    $('#cancel_cont_03').hide();
                    $('#cancel_cont_02').show();
                } else if (cType == "1") {
                    $('.j_bjin').html(cancelTypeObj.money);
                    $('.j_fwf').html(cancelTypeObj.manageFee);
                    $('#cancel_cont_01').hide();
                    $('#cancel_cont_02').hide();
                    $('#cancel_cont_03').show();
                }
                $('#JS_cancel_invest_popup').show();
                $('.JS_ok').on('click', function () {
                    $(this).unbind();
                    $('#JS_cancel_invest_popup').hide();
                    $.ajax({
                        url: '/duotou/CancelLoad',
                        data: {
                            loan_id: cLoanId,
                            is_allow_access: 1,
                            'token': token
                        },
                        type: 'get',
                        dataType: 'json',
                        success: function (result) {
                            if (result.data.data.errCode == 0) {
                                WXP2P.UI.toast('<div class="ui_cancel_suc">取消成功</div>');
                                setTimeout(function(){
                                    location.reload()
                                },2000);
                            } else {
                                WXP2P.UI.toast(result.data.data.errMsg);
                            }
                        },
                        error: function () { }
                    })
                });
                $('.JS_cancel').click(function () {
                    $('#JS_cancel_invest_popup').hide();
                });
            });



        })();
    });
})(Zepto);
