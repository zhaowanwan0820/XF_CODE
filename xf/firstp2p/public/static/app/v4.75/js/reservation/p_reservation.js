
var valDiscountInfo;
var valDiscountId;
var valDiscountbidAmount = 0;
var valDiscountType;
var valDiscountPrice;
var intMerry;
var reserveInfoObj = $('.pop_qx_list .active').data('reserveinfo');
var dealMin;
var reserveMinLoan;
var userClientKey = $('#userClientKey').val();
var moneyValidate = false;
var deal_type;
var iptBotToTop = $(".input_deal").offset().top + 67;
var rate = ''
var yuyue_qx;
window.onload = function(){
    zhuge.track('进入随心约提交预约页',{
        "类型": "尊享"
    })    
}
$('a.to_rules_instruction').attr('href', 'firstp2p://api?type=webview&gobackrefresh=false' + '&url=' + encodeURIComponent(location.origin + window['reserveRuleUrl']));
function gobackjsfunc() {
    // 开通存管账户弹窗不显示
    valDiscountInfo = JSON.parse(WXP2P.APP.getCookie('choosed_discount_info'));
    if (valDiscountInfo) {
        valDiscountId = valDiscountInfo.choosed_discount_id;
        valDiscountbidAmount = valDiscountInfo.choosed_discount_bidamount;
        valDiscountType = valDiscountInfo.choosed_discount_type;
        valDiscountPrice = valDiscountInfo.choosed_discount_price;
        intMerry = $(".ui_input .btn_key").html() * 1;
        getPrice(intMerry, valDiscountId);
    }else{
        window.location.reload();
    }
}
// title右上角增加帮助中心
location.href = 'firstp2p://api?type=rightbtn&image=help&callback=gobackjsfuncReserve';
function gobackjsfuncReserve(){
    location.href = 'firstp2p://api?type=webview&gobackrefresh=false&url='+ encodeURIComponent(location.origin + '/help/faq_list/?cid=227');
}
// 判断选择优惠券，调接口计算实时收益
function getPrice(intMerry, valDiscountId) {
    var showEarningInfo = function(discountDetail){
        $(".can_use").hide();
        // $(".no_choose").setAttribute("src", tsrc);
        $(".JS-selected_discount").show();
        $(".coupon_detail .con").html(discountDetail);
        updateState();
    }
    if(valDiscountType == 2) {
        showEarningInfo('可获'+ valDiscountPrice + '%加息，金额满' + valDiscountbidAmount + '元可用');       
    } else {
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/discount/AjaxExpectedEarningInfo?token=" + $("#token").val() + '&id=1' + '&money=' + intMerry + "&discount_id=" + valDiscountId,
            success: function (json) {
                if (!!json.data) {
                    showEarningInfo(json.data.discountDetail);
                }
            }
        });
    }
}
// 获取起投
var getStartInvestMoney = function(){
    intMerry = $(".ui_input .btn_key").html() * 1;
    dealMin = Math.max(reserveInfoObj.min_amount * 1, valDiscountbidAmount);
    var dealMinXS = dealMin.toString().split(".").length > 1 ? dealMin : dealMin + '.00'
    $(".inp_text").html("<span class='dealMin din_alternate'>" + dealMinXS + "</span>" + " 元起投");
    // 金额判断
    if (intMerry == 0) {
        if (reserveMinLoan > valDiscountbidAmount && reserveMinLoan > reserveInfoObj.min_amount * 1 && (deal_type == 3 || deal_type == 2)) {
            $(".dit_yq").html("为提高预约成功率信仔建议您预约" + reserveMinLoan + "元");
        } else {
            $(".dit_yq").html("");
        }
        moneyValidate = false;
    } else if (intMerry < dealMin) {
        $(".dit_yq").html('起投金额为' + dealMin + '元');
        moneyValidate = false;
    } else if (intMerry >= dealMin && intMerry < reserveMinLoan && reserveMinLoan > valDiscountbidAmount) {
        $(".dit_yq").html("为提高预约成功率信仔建议您预约" + reserveMinLoan + "元");
        moneyValidate = true;
    } else {
        $(".dit_yq").html("");
        moneyValidate = true;
    }
}
// 获取专享标最小预约金额
var getReserveMinLoan = function(){
    deal_type = reserveInfoObj.deal_type;
    rate = $('.pop_qx_list .active').data('rate');
    var loantype = $('#loantype').val()
    if (deal_type == 3 || deal_type == 2) {
        var invest = $('.pop_qx_list .active').data('message');//预约期限
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/deal/reserveMinLoanMoney?invest=" + invest + '&userClientKey=' + userClientKey + '&deal_type=' + deal_type +'&loantype=' + loantype + '&rate=' + rate,
            success: function (json) {
                if (!!json.data) {
                    reserveMinLoan = json.data.money * 1;
                    getStartInvestMoney();
                }
            }
        });
    } else {
        reserveMinLoan = null;
        getStartInvestMoney();
    }
}
getReserveMinLoan();
// 更新按钮状态和链接
function updateState() {
    var yxq_text = $('.active_yxq').html();
    var site_id = $('#site_id').val();
    var iptVal = $(".ui_input .btn_key").html();
    var invest = ''
    yuyue_qx = $('.pop_qx_list .active').data('q') + $('.pop_qx_list .active').data('x')
    if($('.pop_qx_list .active').is('.active')){
        invest = $('.pop_qx_list .active').data('message'); // 预约期限
        rate = $('.pop_qx_list .active').data('rate');
    }
    var contract_zx_url = encodeURIComponent(location.origin + '/deal/reserveContractList?userClientKey=' + userClientKey + '&type=zx' + "&site_id=" + site_id);
    var contract_jys_url = encodeURIComponent(location.origin + '/deal/reserveContractList?userClientKey=' + userClientKey + '&type=jys' + "&site_id=" + site_id);
    var contract_res_url = encodeURIComponent(location.origin + '/deal/reserveProtocol?userClientKey=' + userClientKey + '&amount='+ iptVal +'&invest='+ invest +'&rate='+ rate +'&advid=reserve_contract&advtitle='+ encodeURIComponent('预约协议'));
    $('a.to_contractpre_zx').attr('href', 'firstp2p://api?type=webview&gobackrefresh=false' + '&url=' + contract_zx_url);
    $('a.to_contractpre_jys').attr('href', 'firstp2p://api?type=webview&gobackrefresh=false' + '&url=' + contract_jys_url);
    $('a.to_contractpre_reserve').attr('href', 'firstp2p://api?type=webview&gobackrefresh=false' + '&url=' + contract_res_url);
    
    getStartInvestMoney();
    if (yxq_text == '') {
        moneyValidate = false;
    }
    if (moneyValidate) {
        $(".reservation_btn").removeClass("disabled_btn").removeAttr('disabled');
    } else {
        $(".reservation_btn").addClass("disabled_btn gold_need").attr("disabled", 'disabled');
    }
    var discount_url = encodeURIComponent(location.origin + '/discount/CommonPickList?token=' + $('#token').val() + '&page=1&deal_id=1&bid_day_limit=' + reserveInfoObj.deadline_days + '&money=' + intMerry + '&discount_id=' + valDiscountId + '&consume_type=7');
    $('a.to_youhuiquanList').attr('href', 'firstp2p://api?type=webview&url=' + discount_url);
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
//全投
function start() {
    var wait = 2;
    $("#quantou_all").bind("click", function () {
        time();
        // var yuer = $("#ketou_money").val().trim();
        var yuer = $(".JS_remain_money").html().trim();
        yuer = yuer.replace(/,/g, '');
        reserveInfoObj = $('.pop_qx_list .active').data('reserveinfo');
        var max_amount = reserveInfoObj.max_amount * 1;
        $(".ui_input .btn_key").html(max_amount != 0 ? Math.min(max_amount, yuer) : yuer);
        $(".inp_text").addClass("disnone");
        iptChangeFn();
        updateState();
    });
    function time() {
        if (wait == 0) {
            $("#quantou_all")[0].removeAttribute("disabled");
            wait = 2;
        } else {
            $("#quantou_all")[0].setAttribute("disabled", true);
            wait--;
            setTimeout(function () {
                time()
            }, 1000)
        }
    }
}
start();
//关闭预约有效期弹窗
var close_pop = function () {
    $('.bg_cover').hide();
    $('.pop_yxq_list').removeClass('p_show');
    $('.move_now').hide();
    $('.ui_title').hide();
    document.body.removeEventListener('touchmove', cancleDefault);
}
function cancleDefault(evt) {
    if (!evt._isScroller) {
        evt.preventDefault();
    }
}
$('.move_now').hide();
$('.ui_title').hide();
$('.bg_cover, .pop_yxq_list .ui_back').on('click', function () {
    close_pop();
});
//弹出 预约有效期选择列表
var bg_cover = document.querySelector('.bg_cover');
var move_now = document.querySelector('.move_now');
var pop_yxq_list = document.querySelector('.pop_yxq_list');
var ui_title = document.querySelector('.pop_yxq_list .ui_title');
var overscroll = function (el) {
    el.addEventListener('touchstart', function () {
        var top = el.scrollTop
            , totalScroll = el.scrollHeight
            , currentScroll = top + el.offsetHeight
        if (top === 0) {
            el.scrollTop = 1
        } else if (currentScroll === totalScroll) {
            el.scrollTop = top - 1
        }
    })
    el.addEventListener('touchmove', function (evt) {
        if (el.offsetHeight < el.scrollHeight) {
            evt._isScroller = true;
        }
    });
}
$('.JS_to_yxq').on('click', function () {
    $('.bg_cover').show();
    $('.move_now').show();
    $('.ui_title').show();
    $('.pop_yxq_list').addClass('p_show');
    document.body.addEventListener('touchmove', cancleDefault);
});
function pop_yxq_list_active() {
    var active_yxq_text = $('.pop_yxq_list .active .qx_con').html();
    $('.active_yxq').html(active_yxq_text);
    // if (active_yxq_text) {
    //     $('.JS_yxq_selcet_text').hide();

    // } else {
    //     $('.JS_yxq_selcet_text').show();
    // }
}
$('.pop_yxq_list .JS_item').on('click', function () {
    $(this).addClass('active').siblings().removeClass('active');
    pop_yxq_list_active();
    close_pop();
    updateState();
});
$('.pop_yxq_list .JS_item').eq(0).addClass('active').siblings().removeClass('active');
pop_yxq_list_active();

overscroll(document.querySelector('.pop_yxq_list'));
bg_cover.addEventListener('touchmove', cancleDefault);
move_now.addEventListener('touchmove', cancleDefault);
ui_title.addEventListener('touchmove', cancleDefault);
pop_yxq_list.addEventListener('touchmove', cancleDefault);

function judge_deal_type(){
    var bonus_money;
    if($(".JS_bonus").html()){
        bonus_money = $(".JS_bonus").html().trim().replace(/,/g, '') * 1;
    }else{
        bonus_money = 0;
    }
    if(deal_type == 3 || deal_type == 2){//专享
        $(".JS_wx_nouse").hide();
        $(".JS_wd_nouse").show();
        var wxMoney;
        if($(".val_wxMoney").html()){
            wxMoney = $(".val_wxMoney").html().trim().replace(/,/g, '') * 1;
        }else{
            wxMoney = 0;
        }
        var money = showDou((bonus_money + wxMoney).toFixed(2));
        $(".JS_remain_money").html(money);
    }else if(deal_type == 0){//p2p
        $(".JS_wx_nouse").show();
        $(".JS_wd_nouse").hide();
        var svBalance;
        if($(".val_wxMoney").html()){
            svBalance = $(".val_svBalance").html().trim().replace(/,/g, '') * 1;
        }else{
            svBalance = 0;
        }
        var money = showDou((bonus_money + svBalance).toFixed(2));
        $(".JS_remain_money").html(money);
    }
}
judge_deal_type();
// 提交预约 逻辑
$('#JS-pay_btn').bind("click", function () {
    var amount;
    var invest;
    var needForceAssess = $(".needForceAssess").val();
    var is_check_risk = $(".pop_qx_list .active").data('projectrisk');
    if (needForceAssess == 1) {
        var needForceAssess_link = location.origin;
        var token = $("#token").val();
        needForceAssess_link = encodeURIComponent(needForceAssess_link + "/user/risk_assess?token=" + token + "&from_confirm=1")
        $(".needForceAssess_box").show();
        $('.JS_assess').show();
        $(".needForceAssess_link").attr("href", "firstp2p://api?type=webview&gobackrefresh=true&url=" + needForceAssess_link).click(function () {
            $(".needForceAssess_box , .bg_cover , .JS_assess").hide();
            // location.reload();
        });
    } else if (is_check_risk == 1) {
        var l_origin = location.origin;
        var urlencode = l_origin + "/user/risk_assess?token=" + $('#token').val() + "&from_confirm=1";
        $("#ui_conf_risk").css('display', 'block');
        $("#JS-confirm").attr('href', 'firstp2p://api?type=webview&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
        $("#JS-cancel,#JS-know,#JS-confirm").click(function () {
            $("#ui_conf_risk").hide();
        });
        return false;
    } else if (window['_isQuickBidAuth_'] == 0 && window['_isSupervisionReserve_'] == 1) {
        $("body").append('<a href="storemanager://api?type=webview&gobackrefresh=true&url=' + encodeURIComponent(location.origin + '/deal/openFree?showNav=true&userClientKey=' + window['_userClientKey_']) + '" class="JS_openfree_btn"></a>');
        $(".JS_openfree_btn").click();
    } else {
        var $this = $(this);
        var asgn = $("#asgn").val();
        var $rate = $("#rate").val();
        var loantype = $("#loantype").val();
        amount = $(".ui_input .btn_key").html() * 1; // 预约金额
        invest = $('.pop_qx_list .active').data('message'); // 预约期限
        var expire = $('.pop_yxq_list .active').data('message'); // 预约有效期
        var site_id = $('#site_id').val();
        moneyValidate = false;
        updateState();
        $this.attr("disabled", 'disabled');
        localStorage.clear();
        WXP2P.APP.request('/deal/reserve_commit', function (obj) {
            location.href = obj.url;
        }, function (msg, errorCode) {
            moneyValidate = true;
            updateState();
            $this.removeAttr('disabled');
            WXP2P.UI.showErrorTip(msg);
        }, 'post', {
                'asgn': asgn,// 验签参数
                'userClientKey': userClientKey,
                'amount': amount,
                'invest': invest,
                'expire': expire,
                'discount_id': valDiscountId,
                'deal_type': reserveInfoObj.deal_type,
                'site_id':site_id,
                'loantype': loantype,
                'rate' : $rate
            });
    }
    zhuge.track('随心约提交预约页_点击提交预约',{
        '类型':'尊享',
        '授权金额':amount,
        '期限': yuyue_qx,
        '利率': rate
    });
});
$(".needForceAssess_link_no").click(function () {
    $(".needForceAssess_box , .JS_assess").hide();
})
// 初始化键盘
$(window).scroll(function() {
    iptBotToTop = $(".input_deal").offset().top + 67 - $(window).scrollTop();
})
var vir_input = new virtualKey($(".ui_input"), {
    placeholder: Math.max(reserveInfoObj.min_amount, valDiscountbidAmount) + '元起投',
    delayHiden: function () {   
        updateState();
        var iptVal = $(".ui_input .btn_key").html();
        if (iptVal == '') {
            $('.input_deal').removeClass('borer_yellow');
        }
    },
    focusFn: function () {
        updateState();
        // 200表示键盘弹起时的高度,84表示输入框的高度,iptBotToTop表示输入框底边离页面页面顶端的距离
        var fold = $(window).height() - 200;
        iptBotToTop > fold && $(window).scrollTop(iptBotToTop - fold);
        $(".reservation_btn").addClass("disabled_btn").attr("href", 'javascript:void(0);');
    },
    changeFn: function () {
        iptChangeFn();
    }
});
function iptChangeFn() {
    var iptVal = $(".ui_input .btn_key").html();
    $(".show_daxie").empty().append($.getformatMoney(iptVal, "show_money_ul", "active"));
}
iptChangeFn();
updateState();

$(".JS_assess ,.cunguan_bg").bind("touchmove", function (event) {
    event.preventDefault();
});
$(".needForceAssess_box ,.alert_evaluate").on(' touchstart', function () {
    $(".needForceAssess_box ,.alert_evaluate").on('touchmove', function (event) {
        event.preventDefault();
    }, false);
})
$(".needForceAssess_box ,.alert_evaluate").on(' touchend', function () {
    $(".needForceAssess_box ,.alert_evaluate").unbind('touchmove');

});

if(window['isBankcard'] == 1){//已绑卡
    $(".JS_open_p2p_btn").attr("href", 'storemanager://api?type=webview&gobackrefresh=false&url=' + encodeURIComponent(location.origin + "/payment/transit?params=" + encodeURIComponent('{"return_url":"storemanager://api?type=closecgpages","srv":"register"}')));
}else{//未绑卡
    $(".JS_open_p2p_btn").attr("href", 'storemanager://api?type=webview&gobackrefresh=false&url=' + encodeURIComponent(location.origin + "/payment/transit?params=" + encodeURIComponent('{"return_url":"storemanager://api?type=closecgpages","srv":"registerStandard"}')));
}
//开通免密缴费免密出借url
var _is_open_authorize_param = '{"srv":"authCreate" , "grant_list":"' + window['needGrantStr'] + '" , "return_url":"storemanager://api?type=closecgpages"}';
var _openauthorizeUrl = location.origin + "/payment/Transit?params=" + encodeURIComponent(_is_open_authorize_param);
$(".JS_open_free_btn").attr({ "href": 'storemanager://api?type=webview&gobackrefresh=false&url=' + encodeURIComponent(_openauthorizeUrl) });


$(".point_open").click(function () {
    $(".account_money").toggle();
    $(this).toggleClass('down_img');
});

// 刷新页面等情况删除cookie
var deleteDiscountCookie = function(){
    WXP2P.APP.setCookie('choosed_discount_info', WXP2P.APP.getCookie('choosed_discount_info'), -1);
}
/******* 获取优惠券可使用数目 *******/
// 最外层弹框禁止滚动
$('.ui_mask_white').bind('touchmove',function(event){
    event.preventDefault();
});
function getDiscountNum() {
    deleteDiscountCookie();
    reserveInfoObj = $('.pop_qx_list .active').data('reserveinfo');
    $.ajax({
        type: "post",
        dataType: "json",
        url: "/discount/AjaxAvaliableCount?token=" + $("#token").val() + '&bid_day_limit=' + reserveInfoObj.deadline_days + '&deal_id=1&consume_type=7',
        success: function (json) {
            $(".can_use").show();
            $(".JS_coupon_num").text(json.data);
            if (json.data < 1) {
                $(".JS_red").removeClass('color_red');
            }
            if (json.data > 0) {
                $(".JS_red").addClass('color_red');
                var _TOUZIQUAN_RESERVE_GUIDE_COOKIE_NAME_ = '_touziquan_reserve_guide_';
                function tryShowTouziQuanGuide() {
                    var fold = $(window).height() + $(window).scrollTop();
                    //180大于选择入口上边至我知道了下边的距离120
                    var offsetTop = $(".JS-youhuiquanpannel").offset().top + 180;
                    var guidecokkiestr = WXP2P.APP.getCookie(_TOUZIQUAN_RESERVE_GUIDE_COOKIE_NAME_);
                    var guideList = guidecokkiestr != null && guidecokkiestr != "" ? guidecokkiestr.split(",") : [];
                    if (guideList[0] == $('#user_id').val()) return;
                    if (fold < offsetTop) {
                        $(window).scrollTop(offsetTop - $(window).height());
                    }
                    $('.new_guide_box').css({'transform': 'translateZ(1px)','z-index': '2000000'});
                    $('.sub_btn').addClass('index0')
                    $('.JS-touziyindao').show();
                    $('.JS-touziyindao').click(function () {
                        $('.JS-touziyindao').hide();
                        $('.sub_btn').removeClass('index0').addClass('index9')
                    $('.new_guide_box').css({'transform': 'translateZ(0px)','z-index': '0' });
                    });
                    guideList.push($('#user_id').val());
                    WXP2P.APP.setCookie(_TOUZIQUAN_RESERVE_GUIDE_COOKIE_NAME_, guideList, 365);
                }
                tryShowTouziQuanGuide();
            }
        }
    })
}
if (valDiscountId) {
    getPrice(intMerry, valDiscountId);
} else {
    getDiscountNum();
}
//删除优惠券
$('.JS-selected_discount .JS_close').bind('click', function () {
    $('.JS-selected_discount').hide();
    valDiscountId = '';
    valDiscountbidAmount = '';
    updateState();
    getDiscountNum();
});
//阻止弹窗滚动
$(".mianmi_bg , .not_bid_bg").bind("touchmove",function(event){
    event.preventDefault();
});
