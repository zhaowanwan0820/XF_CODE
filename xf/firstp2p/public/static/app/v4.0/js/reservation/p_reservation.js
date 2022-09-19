$(function() {
    (function(){
        //全投
        function start(){
            var wait = 2;
            $("#quantou_all").bind("click", function() {
                time();
                var yuer = $("#ketou_money").val().trim();
                yuer = yuer.replace(/,/g,'');
                var max_amount = $("#max_amount").val().trim();
                // 判断是否后台配置了最高金额
                if(parseInt(max_amount) !==0){
                    $(".ui_input .btn_key").html(Math.min(max_amount, yuer));
                }else{
                    $(".ui_input .btn_key").html(yuer);
                }

                $(".inp_text").addClass("disnone");
                updateState();
            });

            function time() {
                if (wait == 0) {
                    $("#quantou_all")[0].removeAttribute("disabled");
                    wait = 2;
                } else {
                    $("#quantou_all")[0].setAttribute("disabled", true);
                    wait--;
                    setTimeout(function() {
                        time()
                    }, 1000)
                }
            }

        }
        start();
    })();
    // 无缝滚动
    (function(){
        var _box1 = document.getElementById("marquee1");
        var _box2 = document.getElementById("marquee2");
        // 兼容代码
        var _pop_yxq_list = document.getElementById("pop_yxq_list");
        var _btn = document.getElementById("btn");
        _box2.innerHTML=_box1.innerHTML
        var x = 0;
        var fun = function(){
            _box1.style.left = x + 'px';
            _box2.style.left = (x +550) + 'px';
            _pop_yxq_list.style.left = "0px";
            _btn.style.left ="0px";
            x--;
            if((x +550) == 0){
                x = 0;
            }
        }
        setInterval(fun,30);
    })();
    //关闭弹窗
    function cancleDefault(evt) {
      if(!evt._isScroller) {
        evt.preventDefault();
      }
    }
    $('.move_now').hide();
    $('.ui_title').hide();
    var close_pop = function(){
        $('.bg_cover').hide();
        $('.pop_yxq_list').removeClass('p_show');
        $('.move_now').hide();
        $('.ui_title').hide();
        document.body.removeEventListener('touchmove',cancleDefault);

    }
    $('.bg_cover ,.pop_yxq_list .ui_back').on('click',function(){
        close_pop();
    });
    //弹出 预约有效期选择列表
    $('.JS_to_yxq').on('click',function(){

        $('.bg_cover').show();
        $('.move_now').show();
        $('.ui_title').show();
        $('.pop_yxq_list').addClass('p_show');
         document.body.addEventListener('touchmove',cancleDefault);

    });
    function pop_yxq_list_active(){
        var active_yxq_text = $('.pop_yxq_list .active .qx_con').html();
        $('.active_yxq').html(active_yxq_text);
        if(active_yxq_text){
            $('.JS_yxq_selcet_text').hide();

        } else{
            $('.JS_yxq_selcet_text').show();
        }
    }
    $('.pop_yxq_list .JS_item').on('click' ,function(){
        $(this).addClass('active').siblings().removeClass('active');
        pop_yxq_list_active();
        close_pop();
        updateState();
    });
    $('.pop_yxq_list .JS_item').eq(0).addClass('active').siblings().removeClass('active');
    pop_yxq_list_active();
    //自动选中指定期限
    (function () {
        var arr01=location.search.match(/investLine=(\d+)(?:&|$)/);
        var arr02=location.search.match(/investUnit=(\d+)(?:&|$)/);
        var investLine;
        var investUnit;
        var tarStr="";
        if (arr01){
            investLine=arr01[1];
        }
        if (arr02){
            investUnit=arr02[1];
        }
        if(typeof investLine != "undefined" && typeof investUnit != "undefined"){
            tarStr=investLine+"_"+investUnit;
            $('.pop_qx_list .JS_item').each(function () {
                var mes=$(this).data('message');
                if (mes==tarStr){
                    $(this).addClass('active change_ht').css("display","block");
                    var active_qx_text = $('.pop_qx_list .active .qx_con').html();
                    $('.active_qx').val(active_qx_text);
                    updateState();
                    return false;
                }
            });
        }else{
            $('.active_qx').val("");
            $('.pop_qx_list .JS_item').each(function(index, el) {
                $(this).show();
                //不让默认选中第一个
                // if(index == 0){
                //     $(this).addClass('active change_ht').siblings().removeClass('active change_ht');
                // }
            }).click(function(){
                $(this).addClass('active change_ht').siblings().removeClass('active change_ht');
                var active_qx_text = $('.pop_qx_list .active .qx_con').html();
                $('.active_qx').val(active_qx_text);
                updateState();
            });

        }
    })();

    // 更新按钮状态和链接
    function updateState() {
        var int_merry = $(".ui_input .btn_key").html() * 1;
        var qx_text = $('.active_qx').val();
        var yxq_text = $('.active_yxq').html();
        var moneyValidate = int_merry == '' || qx_text == '' || yxq_text == '';
        if (moneyValidate) {
            $(".reservation_btn").addClass("disabled_btn gold_need").attr("disabled", 'disabled');
        } else {
            $(".reservation_btn").removeClass("disabled_btn").removeAttr('disabled');
        }
    };

    var overscroll = function(el) {
       el.addEventListener('touchstart', function() {
            var top = el.scrollTop
              , totalScroll = el.scrollHeight
              , currentScroll = top + el.offsetHeight
            if(top === 0) {
              el.scrollTop = 1
            } else if(currentScroll === totalScroll) {
              el.scrollTop = top - 1
            }
       })
        el.addEventListener('touchmove', function(evt) {
            if(el.offsetHeight < el.scrollHeight){
                evt._isScroller = true;
            }
        });
    }
    overscroll(document.querySelector('.pop_yxq_list'));
    var bg_cover = document.querySelector('.bg_cover');
    var move_now = document.querySelector('.move_now');
    var pop_yxq_list = document.querySelector('.pop_yxq_list');
    var ui_title = document.querySelector('.pop_yxq_list .ui_title');
    bg_cover.addEventListener('touchmove', cancleDefault);
    move_now.addEventListener('touchmove', cancleDefault);
    ui_title.addEventListener('touchmove', cancleDefault);
    pop_yxq_list.addEventListener('touchmove', cancleDefault);
    // 提交预约 逻辑
    $('#JS-pay_btn').bind("click", function(){
        var userClientKey = $('#userClientKey').val(); // 密钥
        var needForceAssess = $(".needForceAssess").val();
        var is_check_risk = $(".pop_qx_list .active").data('projectrisk');
        if(needForceAssess == 1){
            var needForceAssess_link = location.origin;
            var token = $("#token").val();
            needForceAssess_link = encodeURIComponent(needForceAssess_link+"/user/risk_assess?token=" + token + "&from_confirm=1")
            $(".needForceAssess_box").show();
            $('.JS_assess').show();
            $(".needForceAssess_link").attr("href","firstp2p://api?type=webview&url="+ needForceAssess_link).click(function() {
                $(".needForceAssess_box , .bg_cover , .JS_assess").hide();
                // location.reload();
            });
        }else if(is_check_risk == 1){
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('#token').val() + "&from_confirm=1";
            $("#ui_conf_risk").css('display','block');
            $("#JS-confirm").attr('href', 'firstp2p://api?type=webview&url=' + encodeURIComponent(urlencode));
            $("#JS-cancel,#JS-know,#JS-confirm").click(function(){
              $("#ui_conf_risk").hide();
            });
            return false;
        }else{
            var $this = $(this);
            var amount = $(".ui_input .btn_key").html() * 1; // 预约金额
            var asgn = $('#asgn').val(); // 验签参数
            var invest = $('.pop_qx_list .active').data('message'); // 预约期限
            var expire = $('.pop_yxq_list .active').data('message'); // 预约有效期
             moneyValidate = false;
             updateState();
             $this.attr("disabled", 'disabled');
            localStorage.clear();
             WXP2P.APP.request('/deal/reserve_commit', function(obj) {
                location.href = obj.url;
             }, function(msg, errorCode) {
                 moneyValidate = true;
                 updateState();
                 $this.removeAttr('disabled');
                 WXP2P.UI.showErrorTip(msg);
             }, 'post', {
                    'asgn':asgn,
                    'userClientKey':userClientKey,
                    'amount':amount,
                    'invest':invest,
                    'expire':expire
             });
         }
     });
    $(".needForceAssess_link_no").click(function(){
        $(".needForceAssess_box , .JS_assess").hide();
    })
    // 初始化键盘
    var vir_input = new virtualKey($(".ui_input"), {
        placeholder:$("#authorizeAmountString").val(),
        delayHiden: function() {
            updateState();
        },
        focusFn: function() {
            $(".reservation_btn").addClass("disabled_btn").attr("href", 'javascript:void(0);');
        }
    });
    updateState();

    $(".JS_assess").bind("touchmove",function(event){
        event.preventDefault();
    });
    $(".needForceAssess_box").on(' touchstart',function(){
        $(".needForceAssess_box").on('touchmove',function(event) {
        event.preventDefault();
        }, false);
    })
    $(".needForceAssess_box").on(' touchend',function(){
        $(".needForceAssess_box").unbind('touchmove');

    });
    $(".point_open").click(function() {
        $(".account_money").toggle();
        $(this).toggleClass('down_img');
    });
});
