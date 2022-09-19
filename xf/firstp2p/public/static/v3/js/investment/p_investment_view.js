;
(function($) {

    //合同展开收起
    $(function() {
        var investText=$('#isFirstp2pHidden').val()==1?"出借":"投资";

        $(".ico_sigh").tooltip({
            position: {
                my: "right bottom+25",
                at: "right bottom+25"
            }
        });


        //担保方介绍 展开收起
        $('.j_showMore').click(function() {
            $(this).hide()
            $('.more_con').slideDown(500);
            $('.j_hideMore').show().addClass('on')
        });
        $('.j_hideMore').click(function() {
            $(this).hide()
            $('.more_con').slideUp(500);
            $('.j_showMore').show()
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


        $('body').on('click','.more_hetong a', function() {
            var nIndex = $('.more_hetong a').index(this);
            $('.hetong').eq(nIndex).slideToggle(500);
            $('.more_hetong').eq(nIndex).hide()
        });
        $('body').on('click','.but_shouqi', function() {
            var sIndex = $('.but_shouqi').index(this)
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
                var arr = val.toString().split("."),
                    arrInt = arr[0].split("").reverse(),
                    temp = 0,
                    j = arrInt.length / 3;

                for (var i = 1; i < j; i++) {
                    arrInt.splice(i * 3 + temp, 0, ",");
                    temp++;
                }
                return arrInt.reverse().concat(".", arr[1]).join("");

            };

            //初始化计算净收益
            var showForm = function($t) {
                if (!$t || !$t.length || !$t.data("max") || !!$t.data("disabled")) {
                    return false;
                }
                var val = $t.val(),
                    $tip = $("#invest_tip"),
                    max = parseFloat($t.data("max")),
                    min = parseFloat($t.data("min")),
                    per = parseFloat($t.data("perpent")),
                    money = parseFloat($t.data("money")),
                    accMul = X.accMul;
                flag = false;
                if($("#income").length > 0){
                    $("#income").html(showDou((accMul(val, per) / 100).toFixed(2)));
                }
            };
            showForm($("#invest_input"));

            //普惠站点禁止余额划转
            function forbidTransfer(){
                var returnVal=true;
                var isFromWxlc=$('#isFromWxlcHidden').val();
                var bidmoney = Number($("#invest_input").val());
                var totalMoney=Number($('#totalMoneyHidden').val().replace(',',""));
                var bonusMoney=Number($('#bonusMoneyHidden').val().replace(',',""));
                if(!isFromWxlc){
                    if (bidmoney>totalMoney){
                        returnVal=false;
                    }
                }
                return returnVal;
            }

            var computeForm = function($t) {
                    if (!$t || !$t.length || !$t.data("max") || !!$t.data("disabled")) {
                        return false;
                    }
                    var val = $t.val(),
                        $tip = $("#invest_tip"),
                        max = parseFloat($t.data("max")),
                        min = parseFloat($t.data("min")),
                        per = parseFloat($t.data("perpent")),
                        money = parseFloat($t.data("money")),
                        accMul = X.accMul;
                    flag = false;
                    //console.log(showDou(accMul(val,per)/100));
                    if($("#income").length > 0){
                        $("#income").html(showDou((accMul(val, per) / 100).toFixed(2)));
                    }
                    if (!forbidTransfer()){
                        $tip.html('余额不足，请充值');
                        $tip.removeClass("none");
                    }else if ($t.data("age_check") == 0) {
                        $tip.html('仅限' + $t.data("age_min") + '岁及以上用户可投');
                        $tip.removeClass("none");
                    } else if (!/^(\d+|\d+\.|\d+\.\d{1,2})$/.test(val)) {
                        if($("#income").length > 0){
                            $("#income").html("");
                        }
                        $tip.html("您的"+investText+"金额须大于等于" + min + "元，且仅包含两位小数");
                        $tip.removeClass("none");
                    } else if (val > money) {
                        $tip.html("余额不足，请充值");
                        $tip.removeClass("none");

                    } else if (val > max) {
                        $tip.html("抱歉，当前标的最高累计" + investText + max + "元");
                        $tip.removeClass("none");
                    } else if (val < min) {
                        $tip.html("您的"+investText+"金额须大于等于" + min + "元，且仅包含两位小数");
                        $tip.removeClass("none");
                    }else {
                        flag = true;
                        $tip.addClass("none");
                    }

            }
            var computeFormSv = function($t) {
                    if (!$t || !$t.length || !$t.data("max") || !!$t.data("disabled")) {
                        return false;
                    }
                    var val = $t.val(),
                        $tip = $("#invest_tip"),
                        max = parseFloat($t.data("max")),
                        min = parseFloat($t.data("min")),
                        per = parseFloat($t.data("perpent")),
                        money = parseFloat($t.data("money")),
                        accMul = X.accMul;
                    flag = false;
                    //console.log(showDou(accMul(val,per)/100));
                    if($("#income").length > 0){
                        $("#income").html(showDou((accMul(val, per) / 100).toFixed(2)));
                    }
                    if (!forbidTransfer()){
                        $tip.html('余额不足，请充值');
                        $tip.removeClass("none");
                    }else if ($t.data("age_check") == 0) {
                        $tip.html('仅限' + $t.data("age_min") + '岁及以上用户可投');
                        $tip.removeClass("none");
                    } else if (!/^(\d+|\d+\.|\d+\.\d{1,2})$/.test(val)) {
                        if($("#income").length > 0){
                            $("#income").html("");
                        }
                        $tip.html("您的"+investText+"金额须大于等于" + min + "元，且仅包含两位小数");
                        $tip.removeClass("none");
                    } else if (val > money) {
                        $tip.html("余额不足，请充值");
                        $tip.removeClass("none");

                    } else if (val > max) {
                        $tip.html("抱歉，当前标的最高累计"+ investText + max + "元");
                        $tip.removeClass("none");
                    } else if (val < min) {
                        $tip.html("您的"+investText+"金额须大于等于" + min + "元，且仅包含两位小数");
                        $tip.removeClass("none");
                    } else if(window["_isfirstp2p_"]&&isSvOpen == 1&&isSvUser != 1){
                        $.ajax({
                           url: '/deal/isDealOpenAccount?id='+$(".ecid").val(),
                           type: "get",
                           dataType: "json",
                           async: false,
                           success: function(json) {
                                if(json.errno == 0){
                                    if(json.data.status == 0){
                                        Firstp2p.supervision.kaihu();
                                        $('#cg_openP2pAccount .dialog-close').wrap("<a class='btn-base dialog-cancel'></a>");
                                        if(typeof window["_openSvButton_"] !== 'undefined' && window["_openSvButton_"] == 1){
                                            $('.p2pAccountDg .dialog-title').html("升级"+account_p2p);
                                            $('.p2pAccountDg .openTips').html("升级"+account_p2p);
                                        }
                                        flag = false;
                                    }else{
                                        flag = true;
                                    }
                                }else{
                                    Firstp2p.alert({
                                        text : '<div class="tc">'+  json.error +'</div>',
                                        ok : function(dialog){
                                            dialog.close();
                                        }
                                    });
                                    return false;
                                }
                           },
                           error: function(){
                                alert("接口出错啦");
                           }
                        });
                    }else {
                        flag = true;
                        $tip.addClass("none");
                    }

            }
                //computeForm($("#invest_input"));

            //计算投资金额
            $("#invest_input").on("input propertychange", function() {
                computeForm($(this));
            });

            $("#computeForm").submit(function() {
                var $t = $(this);
                $.cookie('investInput', $("#invest_input").val(), {
                    expires: 7,
                    path: '/'
                });
                if (!$t.data("login")) {
                    return true;
                }
                computeFormSv($("#invest_input"));
                if (!flag) {
                    return false;
                }

            });


            $(document).on("click", function() {
                $("#invest_tip").addClass("none");
            })


        })();

        $("#chk").click(function() {
            var $t = $(this),
                $tbody = $t.closest("thead").next();
            if (!!$t.prop("checked")) {
                $tbody.removeClass("none");
                $("#coupon_tr_input").removeClass('none');
                $("#coupon_tr_desc").removeClass('none');
                //$('#coupon_html_desc').html('项目计息后获 %年化返利，15个工作日内发放至平台账户。');
                $('#coupon_html_desc').html('');
                $("#coupon_tr_desc").addClass('none');
                $('.icon_yes').addClass('none');
            } else {
                $tbody.addClass("none");
                $("#coupon_id").val('');
                $("#coupon_tr_input").addClass('none');
                $("#coupon_tr_error").addClass('none');
                $("#coupon_tr_cancel").addClass('none');
                $("#coupon_tr_date").addClass('none');
                //$("#coupon_tr_desc").addClass('none');
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
                $('#coupon_html_desc').html('');
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
    //详情页借款合同
    $(function() {
        //用于缓存合同数据创建
        var contractData=null;
        //延迟触发方法
        var throttle =  function(idle, action){
          var last;
          return function(){
            var ctx = this, args = arguments;
            clearTimeout(last);
            last = setTimeout(function(){
                action.apply(ctx, args);
            }, idle)
          }
        };
        //取借款合同
        var getContractContent = function(){
            var contract_link ='<a href="javascript:;" class="loan_protocal"></a>';

            var investAmount = $('#invest_input').val(),
                deal_id = $('#deal_id').val();
            if(!isNaN(investAmount) && investAmount>= 0){
                $.getJSON("/deal/async",
                {
                    'deal_id': deal_id,
                    'principal': investAmount
                },
                function(data) {
                    contractData=data;
                    for(i = 0; i < contractData.contract.length;i++){
                        $(contract_link).appendTo('.j_seeProtocal');
                        $('.loan_protocal').eq(i).html('《' + contractData.contract[i].title + '》');

                        $('.loan_protocal').each(function() {
                            var idx = $(this).index();
                            // alert(idx);
                            $(this).click(function(){
                                $('#articleBox').html(contractData?contractData.contract[idx].content:'');
                                $('#contractPopMask,#contractPop').show();
                            });
                        });
                        $('#contractPopMask,#contractPop .closeA').on('click',function () {
                            $('#contractPopMask,#contractPop').hide();
                        });
                    }
                });
            }
        };
        getContractContent();
        $("#invest_input").bind("input propertychange",throttle(100,getContractContent));





    });
})(jQuery);

var has_fix_short_alias = false;

function check(coupon_id, deal_id) {
    var ajaxurl = APP_ROOT + "/coupon/check";
    var query = new Object();
    query.coupon_id = coupon_id;
    query.deal_id = deal_id;
    $.ajax({
        url: ajaxurl,
        data: query,
        type: "POST",
        dataType: "json",
        success: function(data) {

            if (data.errno == 0) {
                $("#chk").attr("checked", "checked");
                if(data.data.remark != 0){
                    $("#coupon_tbody").removeClass("none");
                }
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

                if(data.data.remark != 0){
                    $("#coupon_tr_desc").removeClass('none');
                    $("#coupon_html_desc").html(data.data.remark);
                }
                $('.icon_yes').removeClass('none');
                //$('.coupon_ren').removeClass('none').html(data.data.recommend_user);
            } else if (data.errno == -1) {
                $("#coupon_id").val('');
                $("#coupon_tr_desc").addClass('none');
            } else {
                $("#chk").attr("checked", "checked");
                $("#coupon_tbody").removeClass("none");
                //$("#coupon_input").val(data.data.short_alias);


                $("#coupon_id").val('');
                //$("#coupon_tr_input").addClass('none');
                //$("#coupon_html_error").html(coupon_id);
                $("#coupon_tr_error").removeClass('none');
                //$('#coupon_html_desc').html('项目计息后获 %年化返利，15个工作日内发放至平台账户。');
                $('#coupon_html_desc').html('');
                $("#coupon_tr_desc").addClass('none');
                $('.icon_yes').addClass('none');
                //$("#coupon_tr_error_msg").text(data.error);
                $('#coupon_def').removeClass('none');
                //$('#coupon_tr_desc').addClass('none');
                $('.coupon_ren').addClass('none')
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
                $
                //$('#coupon_def').addClass('none');
                if(data.data.remark != 0){
                    ("#coupon_tr_desc").removeClass('none');
                    $("#coupon_html_desc").html(data.data.remark);
                }
                $('.icon_yes').removeClass('none');
            }
        }
    });
}
