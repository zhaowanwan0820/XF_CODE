
$(function() {
    // 智多新开通网贷P2P账户
    function finplanKaihu(data){
     var str = " <div class='p2pImg'></div><p class='openTips'>开通"+ account_p2p +"</p><p class='acDetail'>根据国家相关法律法规要求，需开立"+ account_p2p +"</p>";
     var settings = $.extend({
            title: "开通"+ account_p2p ,
            ok: $.noop,
            text: str,
            close: $.noop
        }, data),
        html = '',
        instance = null;
        html += '' + settings.text + '';
        instance = $.weeboxs.open(html, {
            boxid: 'cg_openP2pAccount',
            boxclass: 'p2pAccountDg auth_no_close',
            contentType: 'text',
            showButton: true,
            showOk: true,
            showCancel:true,
            title: settings.title,
            width: 300,
            type: 'wee',
            onok: function(settings) {
                instance.dh.remove();
                instance.mh.remove();
                window.open("/payment/transit?srv=register");
                Firstp2p.supervision.wancheng();
            },
            onclose : function(){
                window.location.href = "/";
            },
            oncancel : function(){
                window.location.href = "/";
            }
     });
    }
    //智多新授权
    function finplanAuth(data) {
        if (/^\s*$/.test(needGrantStr)){
            return;//不需要授权
        }
        var authArr=needGrantStr.split(',');
        var auth_invest=$.inArray('INVEST',authArr)!=-1;
        var auth_share_payment=$.inArray('SHARE_PAYMENT',authArr)!=-1;
        var str = '<div class="auth_weebox_inner"><div class="title">根据国家法律法规监管要求，办理该业务需在存管银行开通如下授权:</div><div class="authList">'+(!auth_share_payment?"":'<div class="item pay"><p class="first_p">免密缴费授权</p><p class="second_p">用于收取智多新转让/退出时可能产生的费用</p></div>')+(!auth_invest?"":'<div class="item loan"><p class="first_p">免密投标授权</p><p class="second_p">用于投标时自动匹配标的</p></div>')+'</div></div>';
        var settings = $.extend({
                title: "提示",
                ok: $.noop,
                text: str,
                close: $.noop
            }, data),
            html = '',
            instance = null;
        html += '' + settings.text + '';
        instance = $.weeboxs.open(html, {
            boxid: 'auth_weebox',
            boxclass: 'p2pAccountDg auth_no_close',
            contentType: 'text',
            showButton: true,
            showOk: true,
            showCancel:true,
            title: settings.title,
            width: 300,
            type: 'wee',
            onok: function(instance) {
                var preStr="/payment/transit?srv=authCreate&grant_list=";
                preStr+=needGrantStr;
                instance.dh.remove();
                instance.mh.remove();
                window.open(preStr);
                Firstp2p.supervision.finish();
                Firstp2p.supervision.lunxun({
                    sCallback : function(returnVal){
                        if (returnVal.code==0){
                            clearInterval(lunxunTimer);
                            location.reload();
                        }
                    },
                    url : "/account/privilegesCheck",
                    data : {
                        privilege : needGrantStr.replace(/\w+/g,function(match){
                            var map={
                                "INVEST":1,
                                "SHARE_PAYMENT":3
                            }
                            return map[match];
                        })
                    }
                });
            },
            onclose : function(){
                window.location.href = "/";
            },
            oncancel : function(){
                window.location.href = "/";
            }
        });
    }
    //余额划转-网信账户到网贷p2p账户
    function finplanZhuanwdp2p(data){
        var that = this;
        var str = "<p class='hz_less'></p>\
          <div style='display:inline-block;' class='p2pImg'></div><div style='display:inline-block;' class='fxImg'></div><div style='display:inline-block;' class='transferImg'></div><p class='openTips'>划转金额："+ data.data.transferMoney +"</p>\
          <div class='notipsCont'><input name='missTips' id='missTips' class='missTips' type='checkbox' value='不再提示'/><label class='hznotips' for='missTips'></label><span class='bztsTxt'>不再提示，下次自动划转</span></div>";
        var settings = $.extend({
              title: "余额划转",
              text : str,
              boxclass: '',
              close: $.noop
         }, data),
        html = '',
        instance = null;
        html += '' + settings.text + '';
        var dialog = null;
        instance = $.weeboxs.open(html, {
            boxid: null,
            boxclass: 'transfer_hz transferBl_notips',
            contentType: 'text',
            showButton: true,
            showOk: true,
            showCancel:false,
            title: settings.title,
            width: 300,
            type: 'wee',
            onok: function(settings) {
              $.ajax({
                  url: '/supervision/MoneyTransfer?money='+ data.data.transferMoney +'&direction='+ data.data.direction +'',
                  type: 'post',
                  data: {},
                  dataType: 'json',
                  success: function(data) {
                    Firstp2p.alert({
                        text : '<div class="tc">'+  data.info +'</div>',
                        ok : function(dialog){
                            dialog.close(dialog);
                        }
                    });
                  },
                  error: function() {
                    Firstp2p.alert({
                        text : '<div class="tc">网络错误，请稍后重试！</div>',
                        ok : function(dialog){
                            dialog.close(dialog);
                        }
                    });
                  }
              })
              settings.close(settings);
            },
            onclose : function(){
                $("#J_bid_submit").removeClass('ui-btn-disable').removeAttr('disabled').val('同意协议并加入');
            }
        });
    }
    //全投
    $(".qtou_btn").bind("click", function() {
        var max = parseFloat($("#J_BIDMONEY").data("max")); //单笔最高金额,如：10000
        var keyongVal = $("#J_BIDMONEY").data("keyong") + "";
        var keyong = parseFloat(keyongVal.replace(/,/g,'')); //可用余额
        $(".j_ipt_key").val(Math.min(keyong,max));
        // updateState();
        // $('#J_bid_submit').removeClass('ui-btn-disable').removeAttr('disabled').val('同意协议并加入');
    });

    //项目测评弹窗
    function projectRisk() {

        var returnVal=true;
        var projectRiskHidden=$('#projectRiskHidden');
        var num=0;
        var assessmen="";
        if (projectRiskHidden.val()==1){
            returnVal=false;
            num = projectRiskHidden.data('num');
            assessmen = projectRiskHidden.data('assessmen');
            // 点击“确认投资”后，如果个人会员投资人评级低于项目评级，弹窗提示
            var backurl = $("#backurl").html();
            var promptStr = '';
            promptStr = '当前您的风险承受能力为"'+assessmen +'"，<br/>'+
                '与项目要求不符<br/>'+
                '<span class="color-gray">本年度剩余评估'+ num +'次</span>';
            if(num > 0){
                // 个人会员投资风险承受能力评估”周期内有效答题次数不为0
                Firstp2p.alert({
                    text: '<div class="f16 tc">' + promptStr + '</div>',
                    ok: function(dialog) {
                        $.weeboxs.close();
                        location.href="/account/riskassessment?backurl="+encodeURIComponent(backurl);
                    },
                    width: 480,
                    okBtnName: '重新参与评估',
                    boxclass: "checkrisk_cn"
                });
            } else {
                // “个人会员投资风险承受能力评估”周期内有效答题次数为0
                Firstp2p.alert({
                    text: '<div class="no-okbtn f16 tc">' + promptStr + '</div>',
                    width: 480,
                    showButton:false,
                    boxclass: "checkrisk_cn"
                });
            }
        }
        return returnVal;
    }
    //普惠站点禁止余额划转
    function forbidTransfer(){
        var returnVal=true;
        var isForbidTransfer=!$('#isForbidTransferHidden').val();
        var bidmoney = Number($("#J_BIDMONEY").val());
        var totalMoney=Number($('#totalMoneyHidden').val().replace(',',""));
        if(isForbidTransfer){
            if (bidmoney>totalMoney){
                returnVal=false;
                $.showErr("余额不足，请充值");
            }
        }
        return returnVal;
    }
    $("#J_bid_submit").click(function(){
        var is_risk = $("#is_risk").html();
        var backurl = $("#backurl").html();
        if(is_risk==1){//需要做测评
            $.weeboxs.open('<div class="tc">请您先完成风险承受能力评估</div>', {
                boxid : null,
                contentType : 'text',
                showButton : true,
                showCancel : false,
                showOk : true,
                okBtnName: '立即参与评估',
                title : '提示',
                width : 430,
                type : 'wee',
                onclose : function() {
                    null
                },
                onok : function() {
                    location.href="/account/riskassessment?backurl="+encodeURIComponent(backurl);
                    $.weeboxs.close();
                }
            });
        }else{
            if (projectRisk()){
                $("#BidForm").submit();
            }
        }
    });
    $("#BidForm").submit(function() {
        var query = $(this).serialize();
        var postBtn = $('#J_bid_submit');
        var bidVal = parseFloat($("#J_BIDMONEY").val()); //用户输入金额
        var min = parseFloat($("#J_BIDMONEY").data("min")); //单笔最低金额,如：1000
        var max = parseFloat($("#J_BIDMONEY").data("max")); //单笔最高金额,如：10000
        var remain_money_day = $('#J_BIDMONEY').data("remainmoneyday")*1;
        var investCount = parseFloat($('#investCount').html());
        var loanCount = parseFloat($('#loanCount').html());
        var lock_day = $("#lock_day").val();
        var quickInvestCount = $('#quickInvestCount').html()*1 ;
        var quickLoanCount = $('#quickLoanCount').html()*1;
        if ($.trim($("#J_BIDMONEY").val()) == "" || !$.checkNumber($("#J_BIDMONEY").val()) || bidVal <= 0) {
            $.showErr(LANG.BID_MONEY_NOT_TRUE, function() {
                $("#J_BIDMONEY").focus();
            });
            return false;
        }
        if (investCount >= loanCount){
            $.showErr("超出个人加入笔数限制");
            return false;
        }
        if(lock_day == '1' && quickInvestCount >= quickLoanCount){
            $.showErr("超出1天可申请转让/退出加入笔数限制");
            return false;  
        }
        if (bidVal < min || !!isNaN(bidVal)) {
            $.showErr("您的加入金额须大于等于" + min + "元");
            return false;
        }
        if (bidVal > remain_money_day && bidVal != min) {
            if(remain_money_day > min){
                $.showErr('加入金额超出项目可加入金额，剩余可加入金额为'+ remain_money_day +'元');
                return false;
            }else{
                $.showErr('加入金额超出项目可加入金额，剩余可加入金额为' + min + '元');
                return false;
            }
        }
        if (bidVal > max || !!isNaN(bidVal)) {
            $.showErr("超出项目单笔加入限额");
            return false;
        }
        if (!forbidTransfer()){
            return false;
        }
        if(isSvOpen && isSvUser != 1){
            Firstp2p.supervision.kaihu();
            return false;
        }
        $.ajax({
            url: APP_ROOT + "/finplan/dobid",
            data: query,
            dataType: "json",
            beforeSend: function() {
                postBtn.addClass('ui-btn-disable').attr('disabled', 'disabled').val('正在提交中...');
            },

            success: function(result) {
                if (result.status == 1) {
                    window.location.href = result.jump;
                }else if(result.status == 1003){
                    Firstp2p.supervision.finish();
                    if(!!result.data){
                        window.open(result.data.url);
                        Firstp2p.supervision.lunxun({
                          sCallback : function(obj){
                              if(obj.status == 1  || obj.status == 3){
                                  $('.dialog-mask').remove();
                                  $('.done_Confirm').remove();
                                  postBtn.removeAttr('disabled').val('同意协议并加入');
                                  clearInterval(lunxunTimer);
                                  $.showErr(obj.msg);
                              }else if(obj.status == 2){
                                  clearInterval(lunxunTimer);
                                  window.location.href = obj.data.url;
                              }else if(obj.status == 0){}
                          },
                          url : "/deal/BidSecretCallBack",
                          data : {
                            orderId : result.data.orderId
                          }

                      });
                    }
                    postBtn.removeClass('ui-btn-disable');
                }else if(result.status == 1001){
                    var dialog = finplanZhuanwdp2p(result);
                    if(result.data.direction == "wx_to_bank"){
                        $('.transfer_hz').addClass('transferBl_top2p');
                        $('.hz_less').html(account_p2p +"余额不足，需进行余额划转");
                    }else if(result.data.direction == "bank_to_wx"){
                        $('.transfer_hz').addClass('transferBl');
                        $('.hz_less').html(account_wx + "余额不足，需进行余额划转");
                    }
                    postBtn.removeClass('ui-btn-disable');
                }else if(result.status == 1002 || result.status == 1004){
                    var dialog = Firstp2p.supervision.zhuanlicai(result);
                    postBtn.removeClass('ui-btn-disable');
                } else {
                    $.showErr(result.info);
                    postBtn.removeClass('ui-btn-disable').removeAttr('disabled').val('同意协议并加入');
                }
            },
            error: function(ajaxobj) {
                postBtn.removeClass('ui-btn-disable').removeAttr('disabled').val('同意协议并加入');
            }
        });
        return false;
    });
    //协议弹出层
    $('body').on('click', '#j_xieyi', function() {
        if($(this).data('locked')){
           return;
        }
        $(this).data('locked',1);
        var $t = $(this);
        var borrow_money =  $("#J_BIDMONEY").val();
        var promptStr = '';
        $.getJSON('/deal/async',
            {
                deal_id: $('#project_id').val(),
                deal_type: "duotou",
                principal: borrow_money
            },
            function(data) {
                promptStr = data.dtb;
                Firstp2p.alert({
                    text: promptStr,
                    title: '协议详情',
                    ok: function(dialog) {
                        dialog.close();
                        $t.data('locked',0);
                    },
                    close:function(){
                        $t.data('locked',0);
                    },
                    width: 660,
                    showButton: false,
                    boxclass: "xy-popbox"
                });
            });
        return false;
    });

    //智多新开户
    if(isSvOpen && isSvUser != 1){
        finplanKaihu();
        $('#cg_openP2pAccount .dialog-close').wrap("<a class='btn-base dialog-cancel'></a>");
    }else{
        //智多新授权
        finplanAuth();
    }

    //智多新投资券
    /************* 投资券 *************/
    // ajax请求
    var listData = null,tipMsg="";
    function reqAvaliableCount(data) {
        var pageText = null;
        $.ajax({
            url: "/deal/DiscountAvaliableCount",
            type: "GET",
            data: data,
            dataType: "json",
            beforeSend: function() {},
            success: function(result) {
                $("#discountAvaliableCount").val(result);
                $("#tzq_tit").html('<i></i>您有 <span class="color-yellow1">' + result + '</span> 张优惠券可用');
            },
            error: function() {}
        })
    };
    function reqData(data,type) {
        var pageText = null;
        $.ajax({
            url: "/deal/discountPickList?type="+type,
            type: "GET",
            data: data,
            dataType: "json",
            beforeSend: function() {},
            success: function(result) {
                result.type = type;
                listData = result;
                $("#tzq_choose").show();
                cerHtml(result,tipMsg);
                if (result.pagecount <= 1) {
                    $("#pagination").hide();
                    return;
                } else {
                    $("#pagination").show();
                    Firstp2p.paginate($("#pagination"), {
                        pages: result.pagecount,
                        currentPage: result.page,
                        onPageClick: function(pageNumber, $obj) {
                            cerData["page"] = pageNumber;
                            reqData(cerData,type);
                        }
                    });
                    pageText = '<li style="line-height:25px;">' + result.count + ' 条记录 ' + result.page + '/' + result.pagecount + ' 页</li>';
                    $("#pagination").find("ul").prepend(pageText);
                }
            },
            error: function() {}
        })
    };
    // 数据拼接
    var _radioselectdata_ = null;
    function _updateSelectedText_(data,tipMsg) {
        var spanTxt = "";
        if (_radioselectdata_ == null){
            $(".zjq_radio").removeAttr("checked");
            // $("#tzq_tit").html('<i></i>您有 <span class="color-yellow1">' + data.count + '</span> 张优惠券可用');
        }else{
             var type_text = data.type == 1 ? "返现券":"加息券";
            //正则匹配金额或加息数,进行高亮
            // var reg = /(\d*(\.)?\d*( ?)[元|%])/;
            // if(tipMsg){
            //     tipMsg = tipMsg.replace(reg, function(a, b){
            //         return '<span class="color-yellow1">'+b+'</span>';
            //     });
            // }
            spanTxt ='<i></i>已选择<span class="color-yellow1">1</span>张'+  type_text +'，' + tipMsg +'<a href="javascript:void(0)" class="blue pl15" id="cancel_choose">取消选择</a>';
            $("#tzq_tit").html(spanTxt);
            $("#tzq_tit").on("click", "#cancel_choose", function() {
                $(".zjq_radio").removeAttr("checked");
                var avaliableCount = $("#discountAvaliableCount").val();
                $("#tzq_tit").html('<i></i>您有 <span class="color-yellow1">' + avaliableCount + '</span> 张优惠券可用');
                _radioselectdata_ = null;
                $("#discountId").val("");
                $("#discountType").val("");
                $("#discountGroupId").val("");
                $("#discountSign").val("");
                $("#discountGoodsPrice").val("");
            });
        }
    };
    //用户输入/修改投资金额,触发该方法
    function getAjaxDetail(){
        var $_thisRadio = $("#tzq_cont input[name='fav']:checked");
        var obj = $_thisRadio.data("discount");
        var price = $_thisRadio.data("price");
        if(!!obj){
            var $p = $(this).closest("tr");
            var money = $("#J_BIDMONEY").val();
            $.ajax({
                url: "/deal/discountExpectedEarningInfo",
                type: "GET",
                data: {"dealId":$("#deal_id").val(),"money":money,"discountId":obj.discountId,"consumeType":2},
                dataType: "json",
                beforeSend: function() {},
                success: function(ajaxData) {
                    tipMsg = ajaxData.discountDetail;
                    _radioselectdata_ = {};
                    _radioselectdata_["fxMoney"] = $p.find(".fx_money").text();
                    _radioselectdata_["maxMoney"] = $p.find(".max_money").text();
                    _radioselectdata_["discountTypeDesp"] = obj.discountTypeDesp;
                    _updateSelectedText_(listData,tipMsg);
                    $("#discountId").val(obj.discountId);
                    $("#discountType").val(obj.discountType);
                    $("#discountGroupId").val(obj.discountGroupId);
                    $("#discountSign").val(obj.discountSign);
                    $("#discountGoodsPrice").val(ajaxData.discountGoodPrice);
                    $("#discountGoodsType").val(obj.discountGoodsType);
                },
                error: function() {}
            });
        }
    };
    function cerHtml(listData,tipMsg) {
        var html = template("cer_data", listData);
        $("#tzq_cont").html(html);
        _updateSelectedText_(listData,tipMsg);
        $("#discount_" + $("#discountSign").val()).attr("checked", true);
        $(".zjq_radio").bind("change",function() {
            var $p = $(this).closest("tr");
            var obj = $(this).data("discount");
            var price = $(this).data("price");
            var _val = $("#J_BIDMONEY").val();
            if(!isNaN( _val) &&  _val>= 0){
                getAjaxDetail(listData);
            }else{
                tipMsg = obj.discountDetail;
                _radioselectdata_ = {};
                _radioselectdata_["fxMoney"] = $p.find(".fx_money").text();
                _radioselectdata_["maxMoney"] = $p.find(".max_money").text();
                _radioselectdata_["discountTypeDesp"] = obj.discountTypeDesp;
                _updateSelectedText_(listData,tipMsg);
                $("#discountId").val(obj.discountId);
                $("#discountType").val(obj.discountType);
                $("#discountGroupId").val(obj.discountGroupId);
                $("#discountSign").val(obj.discountSign);
                $("#discountDetail").val(obj.discountDetail);
                $("#discountGoodsPrice").val(price);
                $("#discountGoodsType").val(obj.discountGoodsType);
            }
        });
    };

    // 初始化数据
    var cerData = {
        dealId: $("#deal_id").val(),
        money: $("#J_BIDMONEY").val(),
        page: 1,
        consumeType: 2
    };
    reqData(cerData, 1);
    reqAvaliableCount({'dealId': $("#deal_id").val(),'consumeType':2});
    //点击返现券/加息券
    $("#tzq_seclect span").on("click",function(){
        var _index = $(this).index(),type = _index+1;
        $(this).addClass("active").siblings("span").removeClass("active");
        cerData.page = 1;
        reqData(cerData,type);
        _radioselectdata_ = null;
    });
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
    $("#J_BIDMONEY").bind("input propertychange",throttle(500,getAjaxDetail));
});
