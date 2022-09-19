Number.prototype.toFixed = function(len) {
    var s, temp;
    temp = Math.pow(10, len);
    s = parseInt(this * temp);
    return s / temp;
};

(function($) {

    //合同展开收起
    $(function() {
        // 详情页样式
        $(".ico_sigh").tooltip({
            position: {
                my: "center top+10",
                at: "center bottom"
            }
        });
        // 确认页样式
        $(".p_finplan_bid .ico_sigh").tooltip({
            position: {
                my: "center-85 top+10",
                at: "center bottom"
            }
        });
        //担保方介绍 展开收起
        $('.j_showMore').click(function() {
            $(this).hide();
            $('.more_con').slideDown(500);
            $('.j_hideMore').show().addClass('on');
        });
        $('.j_hideMore').click(function() {
            $(this).hide();
            $('.more_con').slideUp(500);
            $('.j_showMore').show();
        });

        try {
            $('.wrap').tooltip();
            $('.j_tooltip_top').tooltip({
                position: {
                    my: "left top-60"
                }
            });

        } catch (e) {

        }


        $('.more_hetong a').click(function() {
            var nIndex = $('.more_hetong a').index(this);
            $('.hetong').eq(nIndex).slideToggle(500);
            $('.more_hetong').eq(nIndex).hide();
        });
        $('.but_shouqi').click(function() {
            var sIndex = $('.but_shouqi').index(this);
            $('.hetong').eq(sIndex).slideToggle();
            $('.more_hetong').eq(sIndex).show();
        });

        $(".j_scroll").click(function() {
            var scroll_offset = $('body').offset();
            $("body,html").animate({
                scrollTop: scroll_offset.top
            }, 400);
        });

        (function() {
            var flag = false;

            /*
            数字序列化
            exp: showDou(2888888);
            输出：2,888,888
            */
            var showDou = function(val) {
                if(typeof val != 'string'){
                    val = val.toString();
                }
                if(val.indexOf(".") == -1){
                    return val + ".00";
                }

                var arr = val.split("."),
                    arrInt = arr[0].split("").reverse(),
                    temp = 0,
                    j = arrInt.length / 3;

                for (var i = 1; i < j; i++) {
                    arrInt.splice(i * 3 + temp, 0, ",");
                    temp++;
                }
                if(arr[1].length == 1){
                    arr[1] = arr[1] + "0";
                }
                return arrInt.reverse().concat(".", arr[1]).join("");

            };

            //初始化计算净收益
            var showForm = function($t) {
                if (!$t || !$t.length || !$t.data("max") || !!$t.data("disabled")) {
                    return false;
                }
                var val = parseFloat($t.val()),
                    $tip = $("#invest_tip"),
                    max = parseFloat($t.data("max")),
                    min = parseFloat($t.data("min")),
                    per = parseFloat($t.data("perpent")),
                    money = parseFloat($t.data("money")),
                    accMul = X.accMul;
                flag = false;
                $("#income").html(showDou((accMul(val, per) / 36000).toFixed(2)));
            };
            showForm($("#invest_input"));

            // 投资确认页初始化计算每日收益
            var showFormBid = function($t) {
                var val = parseFloat($t.val()),
                    per = parseFloat($t.data("perpent")),
                    accMul = X.accMul;
                var everryText = (accMul(val, per) / 36000).toFixed(2);
                if (isNaN(everryText)) {
                    everryText = 0.00;
                } else {
                    everryText = showDou(everryText);
                }

                $("#income_confirm").html(everryText);
            };
            showFormBid($("#J_BIDMONEY"));

            //多投宝投资确认页实时计算每日收益
            $("#J_BIDMONEY").on("input propertychange", function() {
                showFormBid($(this));
            });


            var computeForm = function($t) {
                    if (!$t || !$t.length || !!$t.data("disabled")) {
                        return false;
                    }
                    var val = parseFloat($t.val()), //用户输入金额
                        $tip = $("#invest_tip"), //错误提示
                        max = parseFloat($t.data("max")), //标的最大募集金额
                        min = parseFloat($t.data("min")), //最低起投金额,如：1000
                        per = parseFloat($t.data("perpent")), //日利率
                        money = parseFloat($t.data("money")), //账户余额
                        accmax = parseFloat($t.data("accountmax")), //单账户投资金额累计最高限额，如500000
                        accMul = X.accMul;
                    flag = false;
                    //console.log(accDiv(accMul(val, per), 100).toFixed(2));

                    $("#income").html(showDou((accMul(val, per) / 36000).toFixed(2)));
                    if ($t.data("age_check") == 0) {
                        $tip.html('仅限' + $t.data("age_min") + '岁及以上用户可投');
                        $tip.removeClass("none");
                    } else if (val < min || !!isNaN(val)) {
                        $("#income").html("");
                        $tip.html("您的投资金额须大于等于" + min + "元");
                        $tip.removeClass("none");
                    } else if (!/^\d+$/.test($t.val())) {
                        $("#income").html("");
                        $tip.html("您的投资金额须为数字");
                        $tip.removeClass("none");
                    } else if (val > money || min > money) { //账户余额低于投资金额或者账户余额低于最低起投金额1000元时
                        $tip.html("余额不足，请充值");
                        $tip.removeClass("none");
                    } else {
                        flag = true;
                        $tip.addClass("none");
                    }

                }
                //computeForm($("#invest_input"));

            //计算投资金额
            if (typeof(userInfo) != "undefined" && !!userInfo) {
                $("#invest_input").on("input propertychange", function() {
                    computeForm($(this));
                });
            } else {
                $("#invest_input").on("input focus", function() {
                    var $t = $(this);
                    if (!$t || !$t.length || !$t.data("max") || !!$t.data("disabled")) {
                        return false;
                    }
                    var val = parseFloat($t.val()), //用户输入金额
                        $tip = $("#invest_tip"), //错误提示
                        accmax = parseFloat($t.data("accountmax")); //单账户投资金额累计最高限额，如500000
                    $tip.html('单账户投资金额累计最高限额为' + accmax + '元');
                    $tip.removeClass("none");
                });
            }

            $("#computeForm").submit(function() {
                var $t = $(this);
                $.cookie('investInput', $("#invest_input").val(), {
                    expires: 7,
                    path: '/'
                });
                if (!$t.data("login")) {
                    return true;
                }
                computeForm($("#invest_input"));
                if (!flag) {
                    return false;
                }
            });
            $(document).on("click", function() {
                $("#invest_tip").addClass("none");
            });
        })();

        $("#chk").click(function() {
            var $t = $(this),
                $tbody = $t.closest("thead").next();
            if (!!$t.prop("checked")) {
                $tbody.removeClass("none");
                $("#coupon_tr_input").removeClass('none');
//                $("#coupon_tr_desc").removeClass('none');
//                $('#coupon_html_desc').html('项目计息后获 %年化返利，15个工作日内发放至平台账户。');
                $('.icon_yes').addClass('none');
            } else {
                $tbody.addClass("none");
                $("#coupon_id").val('');
                $("#coupon_tr_input").addClass('none');
                $("#coupon_tr_error").addClass('none');
                $("#coupon_tr_cancel").addClass('none');
                $("#coupon_tr_date").addClass('none');
                $("#coupon_tr_desc").addClass('none');
                $('#coupon_def').removeClass('none');
                $("#coupon_input").val('');
                //$('.icon_yes').addClass('none');
                $('.coupon_ren').addClass('none');
            }
        });
        $("#coupon_input").blur(function() {
            var coupon_id = $("#coupon_input").val();
            var deal_id = $("#deal_id").val();
            if ($.trim(coupon_id) == '') {
                $('.icon_yes').addClass('none');
                $('.coupon_ren').addClass('none');
                $("#coupon_tr_desc").addClass('none');
                //$.showErr("请输入优惠码","","提示");
                return false;
            }
            check(coupon_id, deal_id);
        });
        $("#btn_coupon_error").click(function() {
            $("#coupon_id").val('');
            $("#coupon_input").val('');
            $("#coupon_tr_input").removeClass('none');
            $("#coupon_tr_error").addClass('none');
            $("#coupon_tr_cancel").addClass('none');
            $("#coupon_tr_date").addClass('none');
            $("#coupon_tr_desc").addClass('none');
            $('#coupon_def').removeClass('none');
        });
        $("#btn_coupon_cancel").click(function() {
            if ($("#coupon_is_fixed").val()) {
                return;
            }
            $("#coupon_id").val('');
            $("#coupon_input").val('');
            $("#coupon_tr_input").removeClass('none');
            $("#coupon_tr_error").addClass('none');
            $("#coupon_tr_cancel").addClass('none');
            $("#coupon_tr_date").addClass('none');
            $("#coupon_tr_desc").addClass('none');
            $('#coupon_def').removeClass('none');
        });

        /* 不作ajax异步加载，不可靠，随详情页同步加载
        if (document.getElementById("coupon_input")) {
            get_latest_fix_coupon();
        }
        */


    });
})(jQuery);

var has_fix_short_alias = false;

function check(coupon_id, deal_id) {
    var ajaxurl = APP_ROOT + "/finplan/couponcheck";
    var query = new Object();
    query.coupon_id = coupon_id;
    $.ajax({
        url: ajaxurl,
        data: query,
        type: "POST",
        dataType: "json",
        success: function(data) {

            if (data.errno == 0) {
                $("#chk").attr("checked", "checked");
                $("#coupon_tbody").removeClass("none");
                //$("#coupon_input").val(data.data.short_alias);
                if (data.data.recommend_type == 'mobile') {
                    $('.coupon_ren').html("推荐人" + data.data.recommend_user + "，优惠码" + data.data.short_alias).removeClass('none');
                } else {
                    $('.coupon_ren').addClass('none');
                }
                $("#coupon_id").val(data.data.short_alias);
                //$("#coupon_tr_input").addClass('none');
                $("#coupon_tr_error").addClass('none');
                $("#coupon_tr_cancel").removeClass('none');
                $("#coupon_html_cancel").html(data.data.short_alias);
                $("#coupon_tr_date").removeClass('none');
                $("#coupon_html_date").html(data.data.valid_date);
                $("#coupon_tr_desc").removeClass('none');
                $("#coupon_html_desc").html(data.data.remark);
                $('.icon_yes').removeClass('none');
                //$('.coupon_ren').removeClass('none').html(data.data.recommend_user);
            } else if (data.errno == -1) {
                $("#coupon_id").val('');
            } else {
                $("#chk").attr("checked", "checked");
                $("#coupon_tbody").removeClass("none");
                //$("#coupon_input").val(data.data.short_alias);

                $("#coupon_id").val('');
                //$("#coupon_tr_input").addClass('none');
                //$("#coupon_html_error").html(coupon_id);
                $("#coupon_tr_error").removeClass('none');
                //$('#coupon_html_desc').html('项目计息后获 %年化返利，15个工作日内发放至平台账户。');
                $('.icon_yes').addClass('none');
                //$("#coupon_tr_error_msg").text(data.error);
                $('#coupon_def').removeClass('none');
                $('#coupon_tr_desc').addClass('none');
                $('.coupon_ren').addClass('none');
            }
        }
    });
}

function get_latest_fix_coupon() {
    return; //不作ajax异步加载，不可靠，随详情页同步加载
    var ajaxurl = APP_ROOT + "/coupon/latest_fix";
    var query = new Object();
    var deal_coupon_prefix = $("#deal_coupon_prefix").val();
    $.ajax({
        url: ajaxurl,
        data: query,
        type: "POST",
        dataType: "json",
        success: function(data) {

            if (data.errno == 0) {
                if (deal_coupon_prefix && !data.data.is_fixed) {
                    return;
                }
                $("#chk").click();
                $("#coupon_tbody").removeClass('none');
                if (data.data.is_fixed) {
                    has_fix_short_alias = true;
                    $("#chk").attr("disabled", true);
                }
                $("#coupon_id").val(data.data.short_alias);
                //$("#coupon_tr_input").addClass('none');
                $("#coupon_tr_error").addClass('none');
                $("#coupon_tr_cancel").removeClass('none');
                $("#coupon_html_cancel").html(data.data.short_alias);
                $("#coupon_tr_date").removeClass('none');
                $("#coupon_html_date").html(data.data.valid_date);
                $("#coupon_tr_desc").removeClass('none');
                //$('#coupon_def').addClass('none');
                $("#coupon_html_desc").html(data.data.remark);
                $('.icon_yes').removeClass('none');
            }
        }
    });
}