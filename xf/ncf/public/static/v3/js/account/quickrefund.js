(function($) {
    $(function() {
        //弹出层
        var promptStr = '';

        var showRepayFailBox = function(data) {
            promptStr = '<div class="f18 tc"><i class="icon-pop-fail"></i>还款失败</div><p class="pop-fail-info">' +
                data.info +'</p>';
            Firstp2p.confirm({
                text: promptStr,
                ok: function(dialog) {
                    dialog.close();
                    location.href = "/account/charge";
                },
                cancel: function(dialog) {
                    dialog.close();
                },
                width: 436,
                okBtnName: "充值",
                title: "提前还款失败",
                boxclass: "schedule-popbox"
            });

        };
        var showPromptMsgBox = function(data) {
            promptStr = '<div  class="pop-ts"><i class="icon-pop-ts"></i><span>' + data.info + '</span></div>';
            Firstp2p.alert({
                text: promptStr,
                ok: function(dialog) {
                    dialog.close();
                },
                cancel: function(dialog) {
                    dialog.close();
                },
                width: 436,
                okBtnName: "返回",
                title: "提示信息",
                boxclass: "msg-popbox"
            });

        };

        var showRepaySucBox = function() {
            promptStr = '<div class="f18 tc"><i class="icon-pop-suc"></i>还款成功</div><div class="mt28 mb13 tc"><span class="color-green mr5" id="second">3</span>秒后退出</div>';
            Firstp2p.alert({
                text: promptStr,
                ok: function(dialog) {
                    dialog.close();
                    location.href = "/account/refund";
                },
                cancel: function(dialog) {
                    dialog.close();
                },
                onopen: function() {
                    Firstp2p.goPay({
                        number: 3,
                        callback: function() {
                            $.weeboxs.close();
                            location.href = "/account/refund";
                        }
                    });
                },
                width: 436,
                title: "提前还款成功",
                boxclass: "suc-popbox"
            });

        };

        var showRepayScheduleBox = function(data,deal_type) {
            var type = ""
            deal_type == 3 ? type = "收益" : type = "利息";
            promptStr = '<div class="color-red2  f16 mb30 color-yellow1">申请提前还款时，如实际借款天数未超过提前还款/回购限制天数，将按提前还款/回购限制天数来计算提前还款金额。</div>' +
                '<div class="f16 schedule-div"><ul class="schedule-list">' +
                '<li>提前还款日期：<span class="color-red2">' + data.info.prepay_date + '</span></li>' +
                '<li>提前还款本金（元）：' + data.info.remain_principal + '</li>' +
                '<li>提前还款' + type + '（元）：' + data.info.prepay_interest + '</li>' +
                '<li>提前还款违约金（元）：' + data.info.prepay_compensation + '</li>' +
                '<li>咨询费（元）：' + data.info.consult_fee + '</li>' +
                '<li>担保费（元）：' + data.info.guarantee_fee + '</li>' +
                '<li>手续费（元）：' + data.info.loan_fee + '</li>' +
                '<li>支付费（元）：' + data.info.pay_fee + '</li>' +
                '<li>还款总额（元）：<span class="color-red2">' + data.info.prepay_money + '</span></li>' +
                '</ul></div>';
            Firstp2p.confirm({
                text: promptStr,
                ok: function(dialog) {
                    dialog.close();
                    $.ajax({
                        url: '/account/Doprepay',
                        type: 'GET',
                        data: {
                            id: data.info.deal_id,
                            date: data.info.prepay_date,
                            money: data.info.prepay_money.replace(new RegExp(/,/g),'')
                        },
                        dataType: 'json',
                        success: function(result) {
                            console.log(JSON.stringify(result));
                            if (result.status == 1) {
                                showRepaySucBox();
                            } else if (result.status == 1003) {
                                showRepayFailBox(result);
                            } else {
                                showPromptMsgBox(result);
                            }

                        },
                        error: function() {}

                    });
                },
                cancel: function(dialog) {
                    dialog.close();
                },
                width: 627,
                okBtnName: "还款",
                title: "提前还款明细",
                boxclass: "schedule-popbox"
            });
        }


        $("body").on("click", "#j_repay_btn", function() {
            var obj = $(this).data("info");
            var lock = obj.lock;
            var dealid = obj.dealid;
            var deal_type = obj.deal_type;
            if (lock == "1") return;
            lock = "1";
            $.ajax({
                url: '/account/Prepaycalc',
                type: 'GET',
                data: {
                    id: dealid
                },
                dataType: 'json',
                success: function(result) {
                    if (result.status == 1) {
                        showRepayScheduleBox(result,deal_type);
                    } else {
                        showPromptMsgBox(result);
                    }
                    lock = "0";
                },
                error: function() {
                    lock = "0";
                }
            });
            return false;
        });

    });
})(jQuery);