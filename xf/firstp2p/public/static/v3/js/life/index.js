var gotoValidate = function() {
    if($(".face_recognition").val() == 1){//人脸识别开关打开,点击弹窗
        Firstp2p.alert({
            title: "提示",
            text: '<div class="face_box"><div class="face_img"></div><div class="face_type"><p class="text">人脸识别换卡 ( 推荐 ) : 请到网信APP操作</p><div class="line_text">其他方式</div></div><p class="tips">上传本人手持身份证和银行卡照片 (3个工作日内审核)</p></div>',
            okBtnName: "人工审核",
            ok: function(dialog) {
                dialog.close();
                $('#bankcardValidateForm').submit();
            },
            width: 435,
            showButton: true,
            boxclass: "face_popbox"
        });
    }else{//否则走原流程
        $('#bankcardValidateForm').submit();
    }
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