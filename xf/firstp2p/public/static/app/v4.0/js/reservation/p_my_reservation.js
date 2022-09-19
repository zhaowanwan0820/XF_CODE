// $(function(){
    var _thisParentId;
    $(function(){
        zhuge.track('进入我的预约页',{
            "类型": "尊享"
        })
    })
    $('.reservation_btn').click(function() {
        zhuge.track('我的预约页_点击再约一笔',{
            "类型": "尊享"
        })
    });
    $('.yuyue').on('click', '.j_yy', function(){
        zhuge.track('我的预约页_点击取消预约',{
            "类型": "尊享"
        })
        $(this).attr('disabled', 'disabled');
        _thisParentId = $(this).data("parent");
        $(".dialog").fadeIn();
        var targetId = $(this).data('message');
        $(".j_confirm").data('id', targetId);
    });
    $(".dialog").on('click', '.j_cancel', function(){
        $("."+_thisParentId).find('.j_yy').removeAttr('disabled');
        $(".dialog").fadeOut();
    });
    $(".dialog").on('click', '.j_confirm', function(){
        var userClientKey = $('#userKey').val(),
            $this = $(this),
            target = $this.data('id'),
            asgn = $("#asgn").val();
        $.ajax({
            url: "/deal/reserve_cancel",
            type: "post",
            dataType: "json",
            data: {'id': target,
                   'userClientKey':userClientKey,
                   'asgn':asgn,
                  },
            success: function(data) {
                if (data.data.code == 0) {
                    WXP2P.UI.showErrorTip('<span class="ui_reg_suc_icon"></span><p style="padding-top:8px;">取消成功</p>');
                    $(".dialog").fadeOut("fast");
                    $this.data('id', '');
                    setTimeout(function() {
                      location.reload();
                    },1000);
                }else{
                    WXP2P.UI.showErrorTip((data.error ? data.error : "取消预约失败，请刷新"));
                    $("."+_thisParentId).find('.j_yy').removeAttr('disabled');
                    $(".dialog").fadeOut("fast");
                }
            },
            error: function(data) {
                $("."+_thisParentId).find('.j_yy').removeAttr('disabled');
                WXP2P.UI.showErrorTip("网络错误，请稍后重试");
            }
        });
    });
// });