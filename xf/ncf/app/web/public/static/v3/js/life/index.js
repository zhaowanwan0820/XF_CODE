var gotoValidate = function() {
    $('#bankcardValidateForm').submit();
};

;(function($) {
    $(function() {
        var confirm_dialog = Firstp2p.confirm;
        if($("#haslicai").length == 0){
            var $add_btn = $(".add_bank_card");
            $(".add_list_con").append($add_btn);
            $add_btn.show();
        }

        //添加理财卡弹窗
        $(".add_bank_card").on("click", function() {
            var $t = $(this);
            confirm_dialog({
                width: 340,
                text: '<div class="card_list_con"><i></i>当前仅支持绑定理财卡<br>如需添加消费卡，请前往APP操作</div>',
                ok: function(dialog) {
                    var url = $t.data("href");
                    if (!!url) {
                        location.href = url;
                    } else {
                        $('#bindCardForm').submit();
                    }
                    dialog.close();
                }
            });
            return false;
        });


        // 已绑卡未验证跳转
        $("#yanzheng").click(function() {
            $('#bindCardForm').submit();
        });
        // 已实名未绑卡设置跳转
        // $("#shezhi").click(function() {
        //     $('#bindCardForm').submit();
        // });

        //存管降级开关打开时个人设置页提示
        $(".j_isSvDown").click(function() {
            Firstp2p.alert({
                text: '<div class="tc">' + svMaintainMessage + '</div>',
                ok: function(dialog) {
                    dialog.close();
                }
            });
        });
    });
})(jQuery);