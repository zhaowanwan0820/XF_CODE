function p2pBrowser(){
    var u = navigator.userAgent
    return {
        wx: /MicroMessenger/i.test(u),
        webkit: /AppleWebKit/i.test(u),
        gecko: /gecko/i.test(u),
        ios: /\(i[^;]+;( U;)? CPU.+Mac OS X/.test(u),
        android: /android/i.test(u),
        iPhone: /iPhone/i.test(u),
        iPad: /iPad/i.test(u),
        app: /wx/i.test(u),
        androidApp: /wxAndroid/i.test(u),
        iosApp: /wxiOS/i.test(u)
    }
}
function gobackjsfunc() {
    // 开通存管账户弹窗不显示
    if(p2pBrowser().ios){
        setTimeout(function(){
            var val_discount_id = $(".val_discount_id").html();
            if (val_discount_id) {
                var int_merry = $(".ui_input .btn_key").html() * 1
                getPrice(int_merry, val_discount_id);
            } else {
                window.location.reload();
            }
        },1000);
    }else{
        var val_discount_id = $(".val_discount_id").html();
        if (val_discount_id) {
            var int_merry = $(".ui_input .btn_key").html() * 1
            getPrice(int_merry, val_discount_id);
        } else {
            window.location.reload();
        }
    }
}
$(function () {
    // tofixed
    Number.prototype.toFixed = function (len) {
        if (len <= 0) {
            return parseInt(Number(this));
        }
        var tmpNum1 = Number(this) * Math.pow(10, len);
        var tmpNum2 = parseInt(tmpNum1) / Math.pow(10, len);
        if (tmpNum2.toString().indexOf('.') == '-1') {
            tmpNum2 = tmpNum2.toString() + '.';
        }
        var dotLen = tmpNum2.toString().split('.')[1].length;
        if (dotLen < len) {
            for (var i = 0; i < len - dotLen; i++) {
                tmpNum2 = tmpNum2.toString() + '0';
            }
        }
        return tmpNum2;
    };
    // 三位显示逗号
    function showDou(val) {
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

    var investmentID, addParams,deal_min;
    var moneyValidate = false;
    var val_discount_id = $(".val_discount_id").html();
    var activityId = $('.activity_id').html();
    var discountbidAmount = $(".val_discount_bidAmount").html() * 1;
    var isOpen = $('.isOpen').html();
    var isFull = $('.is_full').html();
    // 更新按钮状态和链接
    function updateState() {
        var int_merry = $(".ui_input .btn_key").html() * 1;
        var deal_max = parseFloat($(".maxLoanMoney").html());
        investmentID = $(".investmentID").html();
        var is_new_user = $(".isNewUser").html();
        var new_user_min_money = $('.newUserMinLoanMoney').html();
        var new_min_loan_money = (is_new_user==1 && new_user_min_money>0) ? parseInt(new_user_min_money) : parseInt($('.minLoanMoney').html());
        deal_min = Math.max(new_min_loan_money, discountbidAmount);
        var lock_day = $(".lock_day").html();
        var discount_type_zdx = lock_day <= 1 ? 1 : 0;
        $(".inp_text").html(deal_min + '元起');
        // 金额判断
        if (int_merry == '') {
            $(".j_dit_yq").css("display", "none");
            moneyValidate = false;
        } else {
            $(".j_dit_yq").css("display", "block");
            moneyValidate = true;
        }
        addParams = '&money=' + int_merry + '&is_allow_access=1'
            + "&discount_id=" + $(".val_discount_id").html()
            + "&discount_group_id=" + $(".val_discount_group_id").html()
            + "&discount_type=" + $(".val_discount_type").html()
            + "&discount_sign=" + $(".val_discount_sign").html()
            + "&discount_bidAmount=" + $(".val_discount_bidAmount").html();
        var contract_web_url = encodeURIComponent(location.origin + '/duotou/Contractpre?project_id=' + investmentID + addParams);
        var ques_web_url = encodeURIComponent(location.origin + '/help/faq_list/?cid=216');
        $('a.to_charge').attr('href', 'invest://api?type=recharge' + addParams);
        $('a.to_contractpre').attr('href', 'firstp2p://api?type=webview&gobackrefresh=false&money=' + int_merry + '&url=' + contract_web_url);
        $('a.to_common_ques').attr('href', 'firstp2p://api?type=webview&gobackrefresh=false&money=' + int_merry + '&url=' + ques_web_url);
        $('a.to_youhuiquanList').attr('href', 'invest://api?type=selectCoupon&deal_id=' + activityId + addParams + '&discount_type='+ discount_type_zdx +'&consume_type=2');
        $('a.JS-youhuiquanList').click(function () {
            WXP2P.UI.showErrorTip('想使用投资券吗？快更新版本吧！');
        })
        if (moneyValidate) {
            //投资按钮
            $(".submit_but").removeClass("btn_gray").removeAttr("disabled");
        } else {
            //投资按钮
            $(".submit_but").addClass("btn_gray").attr("disabled", "disabled");
        }

        //拼接开通存管账户url
        if(window['isBankcard'] == 1){//已绑卡
            var _is_open_p2p_param = '{"srv":"register" ,"money":"' + $(".ui_input .btn_key").html() + '" ,"is_allow_access":1, "return_url":"storemanager://api?type=closecgpages"}';
        }else{//未绑卡
            var _is_open_p2p_param = '{"srv":"registerStandard" ,"money":"' + $(".ui_input .btn_key").html() + '" ,"is_allow_access":1, "return_url":"storemanager://api?type=closecgpages"}';
        }
        var _openp2pUlr = location.origin + "/payment/Transit?params=" + encodeURIComponent(_is_open_p2p_param);
        //开通存管账户url
        $(".JS_open_p2p_btn").attr({ "href": 'storemanager://api?type=webview&gobackrefresh=false&url=' + encodeURIComponent(_openp2pUlr) });
        //开通免密缴费免密出借url
        var _is_open_authorize_param = '{"srv":"authCreate" , "grant_list":"' + window['needGrantStr'] + '" , "return_url":"storemanager://api?type=closecgpages"}';
        var _openauthorizeUrl = location.origin + "/payment/Transit?params=" + encodeURIComponent(_is_open_authorize_param);
        $(".JS_open_free_btn").attr({ "href": 'storemanager://api?type=webview&gobackrefresh=false&url=' + encodeURIComponent(_openauthorizeUrl) });
    };
    // 初始化键盘
    var vir_input = new virtualKey($(".ui_input"), {
        placeholder: Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, $('.val_bidAmount').html() * 1) + '起',
        delayHiden: function () {
            updateState();
            var ipt_val = $(".ui_input .btn_key").html();
            if (ipt_val == '') {
                $('.input_deal').removeClass('borer_yellow');
            }
        },
        focusFn: function () {
            updateState();
            $('.input_deal').addClass('borer_yellow');
            $(".submit_but").addClass("btn_gray").attr("disabled", 'disabled');
        },
        changeFn: function () {
            iptChangeFn();
            var int_merry = $(".ui_input .btn_key").html();
            if(val_discount_id){
                // getPrice(int_merry,val_discount_id);
                getPrice_debounce(int_merry,val_discount_id);//由原来的直接调用，改为调用去抖函数
            }
        }
    });
    function iptChangeFn() {
        var ipt_val = $(".ui_input .btn_key").html();
        $(".show_daxie").empty().append($.getformatMoney(ipt_val, "show_money_ul", "active"));
    }
    // 初始化金额
    var val_money = $(".val_money").html();
    if (val_money > 0) {
        $(".ui_input .btn_key").html(val_money);
        $(".inp_text").addClass("disnone");
    }
    iptChangeFn();
    updateState();
    //全投
    $("#quantou_all").bind("tap", function () {
        var max = parseFloat($(".maxLoanMoney").html());
        // 可用余额
        var keyongVal = $(".deal_money").html();
        var keyong = parseFloat(keyongVal.replace(/,/g, '')); //可用余额
        $(".ui_input .btn_key").html(Math.min(keyong, max));
        $(".inp_text").addClass("disnone");
        iptChangeFn();
        updateState();
    });
    // 点击投资按钮
    $(".submit_but").bind("click", function () {
        var $t = $(this);
        if (!moneyValidate) return true;
        if(window['allowBid'] != 1){//非投资户不可投资
            WXP2P.UI.showErrorTip("非投资账户不允许投资");
            return false;
        }else if (window['_needForceAssess_'] == 1) { //强制风险测评弹窗
            $(".is_eval").show();
            $("#JS-is-evaluate").show();
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $(".eval_btn").attr('href', 'firstp2p://api?type=webview' + addParams + '&url=' + encodeURIComponent(urlencode));
            $(".no_eval,.eval_btn").click(function () {
                $(".is_eval").hide();
                $("#JS-is-evaluate").hide();
            });
            return false;
        }else if(window['needReAssess']==1){
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $("#ui_conf_risk").css('display','block');
            $("#JS-confirm").attr('href', 'firstp2p://api?type=webview&money=&discount_id=&discount_bidAmount=&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
            $("#JS-cancel,#JS-know,#JS-confirm").click(function(){
              $("#ui_conf_risk").hide();
            });
            $t.removeAttr("disabled");
            return false;
        } else {
            supervision();
            return false;
        }
        return true;
    });
    // 判断选择加息券，调接口计算实时收益
    function getPrice(int_merry, val_discount_id) {
        $.ajax({
            type: "post",
            dataType: "json",
            async: false,
            url: "/discount/AjaxExpectedEarningInfo?token=" + $('.token').html() + '&id=' + activityId + '&money=' + int_merry + "&discount_id=" + val_discount_id + '&consume_type=2',
            success: function (json) {
                if (!!json.data) {
                    $(".can_use").hide();
                    $(".JS-couponnum_label").html("已选择");
                    $(".JS-selected_discount").show();
                    $(".coupon_detail .con").html(json.data.discountDetail);
                    updateState();
                }
            }
        });
    }
    // 获取优惠券可使用数目
    function getDiscountNum() {
        var lock_day = $(".lock_day").html();
        var discount_type_zdx = lock_day <= 1 ? 1 : 0;
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/discount/AjaxAvaliableCount?token=" + $('.token').html() + '&deal_id=' + activityId + '&discount_type=' + discount_type_zdx + '&consume_type=2',
            success: function (json) {
                $(".JS-couponnum_label").html("未选择");
                $(".can_use").show();
                $(".JS_coupon_num").text(json.data);
                if (json.data < 1) {
                    $(".JS_coupon_num").removeClass('num_canuse');
                    $(".can_use").removeClass('color_red');
                }
                if (json.data > 0) {
                    var _TOUZIQUAN_ZDX_GUIDE_COOKIE_NAME_ = '_touziquan_zdx_guide_';
                    function tryShowTouziQuanGuide() {
                        var guidecokkiestr = WXP2P.APP.getCookie(_TOUZIQUAN_ZDX_GUIDE_COOKIE_NAME_);
                        var guideList = guidecokkiestr != null && guidecokkiestr != "" ? guidecokkiestr.split(",") : [];
                        if (guideList[0] == $('.user_id').html()) return;
                        $('.JS-touziyindao').show();
                        $('.ui_mask_white').click(function () {
                            $('.JS-touziyindao').hide();
                        });
                        guideList.push($('.user_id').html());
                        WXP2P.APP.setCookie(_TOUZIQUAN_ZDX_GUIDE_COOKIE_NAME_, guideList, 365);
                    }
                    tryShowTouziQuanGuide();
                }
            }
        })
    }
    if(isFull = '0' && isOpen == '1'){
        if (val_discount_id) {
            var int_merry = $(".ui_input .btn_key").html() * 1
            getPrice(int_merry, val_discount_id);
        } else {
            getDiscountNum();
        }
    }
    //删除优惠券
    $('.JS-selected_discount .JS_close').bind('click', function () {
        $('.JS-selected_discount').hide();
        $('.val_discount_id').html('');
        $('.val_discount_group_id').html('');
        $('.val_discount_type').html('');
        $('.val_discount_sign').html('');
        $('.val_discount_bidAmount').html('');
        val_discount_id = '';
        discountbidAmount = '';
        updateState();
        getDiscountNum();
    });
    //去抖函数，相邻操作500ms内禁止发请求,防止频繁发送请求
    var getPrice_debounce=function(idle,action){
        var last=null;
        return function(){
            var ctx = this, args = arguments;
            clearTimeout(last);
            last = setTimeout(function(){
                action.apply(ctx, args);
            }, idle);
        }
    }(500,function (int_merry,val_discount_id) {
        getPrice(int_merry,val_discount_id);
    });
    /***************** 存管逻辑 *****************/
    //可用余额展开收缩
    $(".point_open").click(function () {
        $(".account_money").toggle();
        $(this).toggleClass('down_img');
    });

    // 点击投资按钮调用存管接口
    function supervision() {
        var int_merry = $(".ui_input .btn_key").html() * 1;
        var is_new_user = $(".isNewUser").html();
        var new_user_min_money = $('.newUserMinLoanMoney').html();
        var new_min_loan_money = (is_new_user==1 && new_user_min_money>0) ? parseInt(new_user_min_money) : parseInt($('.minLoanMoney').html());
        var remain_money_day = $(".remain_money_day").html() * 1;
        var loanCount = parseFloat($(".loanCount").html());
        var investCount = parseFloat($(".investCount").html());
        var maxLoanMoney = parseFloat($(".maxLoanMoney").html());
        if (investCount >= loanCount){
            WXP2P.UI.showErrorTip("超出个人加入笔数限制");
            return false;
        }
        if (int_merry < deal_min) {
            WXP2P.UI.showErrorTip('您的加入金额须大于等于' + deal_min + '元');
            return false;
        }
        if (int_merry > remain_money_day && int_merry != new_min_loan_money) {
            if(remain_money_day > new_min_loan_money){
                WXP2P.UI.showErrorTip('加入金额超出项目可加入金额，剩余可加入金额为' + remain_money_day + '元');
                return false;
            }else{
                WXP2P.UI.showErrorTip('加入金额超出项目可加入金额，剩余可加入金额为' + new_min_loan_money + '元');
                return false;
            }
        }
        if (int_merry > maxLoanMoney){
            WXP2P.UI.showErrorTip('超出项目单笔加入限额');
            return false;
        }
        $(".submit_but").addClass("btn_gray").attr("disabled", "disabled");
        setTimeout(updateState, 2000);
        $.ajax({
            url: '/duotou/preBid?token=' + $('.token').html() + addParams,
            type: 'post',
            dataType: 'json',
            success: function (json) {
                //开户url
                $(".JS_bid_btn").remove();
                if (json.data.status == 2) {
                    // 2余额足够，去银行验密
                    var _return_url = encodeURIComponent(location.origin + '/duotou/bid_result?is_allow_access=1');
                    var _is_bid_param = '{"srv":"dtbid" , "money": "' + int_merry + '","discount_id": "' + $(".val_discount_id").html() + '","discount_type": "' + $(".val_discount_type").html() + '","activityId": "' + activityId + '","dealId": "' + investmentID + '","return_url":"firstp2p://api?type=webview&url=' + _return_url + '"}';
                    var _en_bid_param = encodeURIComponent(_is_bid_param);
                    var _bidUlr = location.origin + "/payment/Transit?params=" + _en_bid_param;
                    var p2pbid_href = 'storemanager://api?type=webview&gobackrefresh=true&url=' + encodeURIComponent(_bidUlr);
                    $("body").append('<a href="'+p2pbid_href+'" class="JS_bid_btn"></a>');
                    $(".JS_bid_btn").click();
                } else if (json.data.status == 3 || json.data.status == 6) {
                    // 3验密划转，需要去银行验密页面划转 网贷-网信 专享标
                    $(".JS_is_transfer").show();
                    $(".JS_trans_money").html(json.data.data.transfer + "元");
                    $(".remain_m").html(json.data.data.remain);
                    //拼接划转url
                    if(json.data.status == 3){//网贷-网信 专享标
                        var _is_transfer_param = '{"srv":"transfer" , "amount":"'+json.data.data.transfer+'","return_url":"storemanager://api?type=closecgpages"}';
                    }else if(json.data.status == 6){ //网信-网贷 p2p
                        var _is_transfer_param = '{"srv":"transferWx","amount":"'+json.data.data.transfer+'","return_url":"storemanager://api?type=closecgpages"}';
                    }
                    //开户参数
                    var _en_is_transfer_param = encodeURIComponent(_is_transfer_param);
                    var _istransferUlr = location.origin + "/payment/Transit?params=" + _en_is_transfer_param;
                    $(".JS_transfer_btn").attr({ "href": 'storemanager://api?type=webview' + addParams + '&gobackrefresh=true&url=' + encodeURIComponent(_istransferUlr) });
                }else if (json.data.status == 4 || json.data.status == 5) {
                    // 4需要提示划转，网信->存管 5需要提示划转，存管->网信
                    $(".JS_is_transfer_tips").show();
                    $(".JS_is_transfer_tips .JS_trans_money").html(json.data.data.transfer + "元");
                    $(".JS_is_transfer_tips .remain_m").html(json.data.data.remain);
                    var transfer_type = "";
                    if (json.data.status == 4) {
                        transfer_type = 1;
                    } else {
                        transfer_type = 2;
                    }
                    // 加埋点
                    $(".JS_close_transfer_tips").wrap('<a href="javascript:void(0);" class="MD_trans_to_super_cancel"></a>');
                    $(".JS_transfer_btn").wrap('<a href="javascript:void(0);" class="MD_trans_to_super_ok"></a>');
                    $(".JS_is_transfer_tips .JS_transfer_btn").unbind("click");
                    $(".JS_is_transfer_tips .JS_transfer_btn").bind("click", function () {
                        $.ajax({
                            url: "/payment/Transfer?money=" + json.data.data.transfer + "&type=" + transfer_type + "&dontTip=" + $(".no_tip_checkbox").val() + "&token=" + $('.token').html(),
                            type: 'post',
                            dataType: 'json',
                            beforeSend: function () {
                                $(".JS_is_transfer_tips .JS_transfer_btn").attr("disabled", "disabled");
                            },
                            success: function (subjosn) {
                                if (subjosn.errno == 0) {
                                    WXP2P.UI.showErrorTip("余额划转成功");
                                    var val_svBalance = $(".val_svBalance").html();
                                    var val_wxMoney = $(".val_wxMoney").html();
                                    val_svBalance = val_svBalance.replace(/,/g, '');
                                    val_wxMoney = val_wxMoney.replace(/,/g, '');
                                    if (json.data.status == 4) {
                                        val_svBalance = (val_svBalance * 1 + json.data.data.transfer * 1);
                                        $(".val_svBalance").html(showDou((val_svBalance).toFixed(2)));
                                        $(".val_wxMoney").html(showDou(json.data.data.remain));
                                    } else {
                                        val_wxMoney = (val_wxMoney * 1 + json.data.data.transfer * 1);
                                        $(".val_wxMoney").html(showDou((val_wxMoney).toFixed(2)));
                                        $(".val_svBalance").html(showDou(json.data.data.remain));
                                    }
                                } else {
                                    WXP2P.UI.showErrorTip(subjosn.error);
                                }
                                $(".JS_is_transfer_tips").hide();
                                $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
                            },
                            error: function () {
                                WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
                                $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
                            }
                        })
                    })
                } else if (json.data.status == 1) {
                    // 1余额足够，调用投资接口,跳转至投资成功页
                    var href = "invest://api?type=invest&activity_id=" + activityId + "&project_id=" + investmentID + '&site_id='+ $('.site_id').html() + addParams;
                    $("body").append('<a href="' + href + '" class="JS_bid_btn"></a>');
                    $(".JS_bid_btn").click();
                } else {
                    WXP2P.UI.showErrorTip(json.data.data);
                }
            },
            error: function () {
                WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
            }
        })
    }
    //不在提示划转弹窗
    $(".JS_is_transfer_tips .tips_icon").removeClass('JS_active');
    $(".no_tip_checkbox").val(0);
    $(".JS_is_transfer_tips").on("click", ".tips_icon", function (event) {
        $(".tips_icon").toggleClass('JS_active');
        if ($(".no_tip_checkbox").is(':checked')) {
            $(".no_tip_checkbox").val(1);
        } else {
            $(".no_tip_checkbox").val(0);
        }
    });

    //关闭划转
    $(".JS_close_transfer").click(function (event) {
        $(".JS_is_transfer").hide();
    });

    $(".JS_close_transfer_tips").click(function () {
        $(".JS_is_transfer_tips").hide();
    });
    //阻止弹窗滚动
    $(".mianmi_bg , .not_bid_bg , .opacity").bind("touchmove",function(event){
        event.preventDefault();
    });
});
