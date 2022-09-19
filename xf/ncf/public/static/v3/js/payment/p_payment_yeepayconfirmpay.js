$(function() {
    function paymentAlert(title, txt, cls, okfn) {
        return Firstp2p.alert({
            title: title,
            text: '<div class="f16">' + txt + '</div>',
            ok: function(dialog) {
                dialog.close();
                okfn && okfn.call(this);
            },
            width: 435,
            showButton: true,
            boxclass: cls || ''
        });
    }

    $('#confirmInfoBtn').click(function() {
        $.ajax({
            url: '/payment/yeepayRequest',
            type: "post",
            dataType: "json",
            success: function(res) {
                console.log(res);
                if (res.status == 0) {
                    paymentAlert('提示', '支付请求已受理，可能会有5-10分钟的延迟，请耐心等待，谢谢', '', function() {
                        window.location.href = '/account/';
                    });
                } else {
                    paymentAlert('提示', res.msg, '', function() {
                        window.location.href = '/account/charge';
                    });
                }
            },
            error: function() {
                paymentAlert('提示', '网络错误！', '', function() {
                    window.location.href = '/account/charge';
                });
            }
        });
    });
});