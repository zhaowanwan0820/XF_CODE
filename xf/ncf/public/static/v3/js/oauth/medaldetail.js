(function($) {
    $(function() {

        if (typeof Firstp2p == 'undefined') {
            Firstp2p = {};
        }
        Firstp2p.getData = function(options) {
            var defaultSettings = {
                    scriptId: 'detail_list',
                    container: $('#detail_container')
                },
                settings = $.extend(true, defaultSettings, options),
                cerHtml = function(data) {
                    template.helper('turnToDate', function (content) {
                       return new Date(content).getTime();
                    });
                    var jsondata = {};
                    jsondata.list = data;
                    var html = template(settings.scriptId, jsondata);
                    settings.container.html(html);
                }
            cerHtml(jsondetail);
        };
        //勋章详情
        Firstp2p.getData();
        //选择奖励弹出层
        Firstp2p.getData({
            scriptId: 'prizes_list',
            container: $('#prizes_container')
        });

        //选择奖励弹出层
        $("body").on("click", "#lingqu", function() {
            $.weeboxs.close();
            var $t = $(this);
            var promptStr = '';
            var awardsData = $t.data("awards");
            var checkedPrizeid;
            var popSuc = function() {
                promptStr = '<div class="tc desc"><div class="f24 red mb10">恭喜获得勋章奖励！</div><div class="f16">奖励已发送至“礼券”</div></div>';
                Firstp2p.alert({
                    title: "领取成功",
                    text: promptStr,
                    ok: function(dialog) {
                        dialog.close();
                        location.reload();
                    },
                    width: 440,
                    showButton: true,
                    okBtnName: '我知道了',
                    boxclass: "ling_popbox",
                });
            };
            var popFail = function() {
                promptStr = '<div class="tc desc"><div class="f24 mb10">勋章奖励领取失败！</div><div class="f16">请稍后再试</div></div>';
                Firstp2p.alert({
                    title: "领取失败",
                    text: promptStr,
                    ok: function(dialog) {
                        dialog.close();
                    },
                    width: 440,
                    showButton: true,
                    okBtnName: '我知道了',
                    boxclass: "ling_popbox",
                });
            };
            var reqAjax = function() {
                var lock = false;
                if (!!lock) return;
                lock = true;
                $.ajax({
                    type: "post",
                    dataType: "json",
                    data: {
                        "medal_id": awardsData["medal_id"],
                        "prize_id": checkedPrizeid
                    },
                    url: "/medal/award",
                    success: function(data) {
                        if (data.status == 0) {
                            popSuc();
                        } else {
                            popFail(data.msg);
                        }
                        lock = false;
                    },
                    error: function() {
                        lock = false;
                    }
                });
            };
            if (awardsData["prizesLenth"] == awardsData["prizeSelectCount"]) {
                checkedPrizeid = awardsData["prize_id"];
                reqAjax();
            } else {
                promptStr += $("#prizes_container").html();
                Firstp2p.alert({
                    title: "选择奖励",
                    text: promptStr,
                    ok: function(dialog) {
                        var pidStr = '';
                        for (i = 0; i < $(".check-select").length; i++) {
                            pidStr += $('.check-select').eq(i).data("prizeid") + ',';
                        }
                        pidStr = pidStr.replace(/,$/g, "");
                        checkedPrizeid = pidStr;
                        if (checkedNum != selectNum) {
                            return false;
                        }
                        reqAjax();
                        dialog.close();
                    },
                    width: 402,
                    showButton: true,
                    okBtnName: '确认领取',
                    boxclass: "choice_popbox",
                    onopen: function() {
                        $(".p2p-ui-checkbox").p2pUiCheckbox();
                    }
                });
            }
        });
        //点击复选框按钮已选奖励数目变化
        var checkedNum = '';
        var selectNum = '';
        $('body').on("click", ".j_check_normal", function() {
            checkedNum = $(".check-select").length;
            selectNum = $(".choice_popbox .j_selectCount").html();
            $(".choice_popbox .j_checkednum").html(checkedNum);
            if(checkedNum == selectNum){
                $(".j_check_normal").addClass("check-disable");
                $(".check-select").removeClass("check-disable");
                $(".dialog-ok").removeClass("dialog-disable");
            }
            if(checkedNum < selectNum){
                $(".j_check_normal").removeClass("check-disable");
                $(".dialog-ok").addClass("dialog-disable");
            }
        });
    });
})(jQuery);