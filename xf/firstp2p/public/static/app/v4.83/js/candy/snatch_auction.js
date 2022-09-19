$(function(){
    //跳转链接时隐藏原生头
    $('.JS_todetail').attr('href',function () {
        return $(this).attr('href')+encodeURIComponent(location.origin + $(this).data('pram'));
    })
    $('.bg_wrapper').click(function(){
        return false;
    })
    //自动滚动
    function autoMove() {
        var li_len = $('.prize_ul li').length;
        var big_timer, small_timer;
        var liHeight = parseInt($(".prize_ul li").height());
        $(".prize_ul li").height(liHeight);
        if(li_len == 0){
            $('.ul_wrap').hide();
            return false;
        }else if(li_len == 1){
            return false;
        }else if(li_len > 1){
            var i = 1;
            var speed = 3;
            var res = 0;
            var str;
            big_timer = setInterval(function(){
                clearInterval(small_timer);
                small_timer = setInterval(function(){
                    res = res + speed;
                    if(res > liHeight*i){
                        res = liHeight*i;
                        str = "-"+res+"px";
                        $('.prize_ul').css('top', str);
                        i++;
                        clearInterval(small_timer);
                    }else{
                        str = "-"+res+"px";
                        $('.prize_ul').css('top', str);
                    }
                    if(i == li_len){
                        i = 0;
                        // $('.prize_ul').css('top', 0);
                    }
                }, 20)
            }, 2500)
        }
    }
    autoMove();
    if($('.clearCookie').val() == 1){
        WXP2P.APP.setCookie("scroll_top",0,-1);
        WXP2P.APP.setCookie("snatch_tap_right",0,-1);
    }
    if(WXP2P.APP.getCookie('snatch_tap_right') == 1){
        $('.snatch_tap_left').removeClass('snatch_activity');
        $('.snatch_tap_right').addClass('snatch_activity');
        $('.JS_product_now').hide();
        $('.JS_product_prev').show();
    }
    // 跳转页面存cookie返回原位置
    if(WXP2P.APP.getCookie('scroll_top') && !WXP2P.APP.getCookie('snatch_tap_right')){   
        $(window).scrollTop(WXP2P.APP.getCookie('scroll_top'));
    }
    
    // $(window).bind("touchmove",function(){
    //     WXP2P.APP.setCookie("scroll_top",$(window).scrollTop(),1);
    // })
    window.onscroll = function(){
        WXP2P.APP.setCookie("scroll_top",$(window).scrollTop(),1);
    }

    function timeSta(num){
        var time = new Date(num);
        return time.getFullYear() + "." + ((time.getMonth()+1 > 9) ? (time.getMonth()+1) : '0'+(time.getMonth()+1)) + "." + (time.getDate() > 9 ? time.getDate() : '0'+time.getDate()) + "  " + (time.getHours() > 9 ? time.getHours() : '0'+time.getHours()) + ":" + (time.getMinutes() > 9 ? time.getMinutes() : '0'+time.getMinutes()) + ":" + (time.getSeconds() > 9 ? time.getSeconds() : '0'+time.getSeconds());
    };
    var offset = 0, flag = true;
    var more_dom = document.createElement('div'), no_more = document.createElement('div');
    no_more.innerHTML = "<div class='no_more_wrap'><span class='no_more'>没有更多了</span></div>";
    more_dom.innerHTML = "<div class='cli_more_wrap'><span class='cli_more'>点击加载更多</span></div>";
    //一进入夺宝首页加载往期记录中的最多30条记录
    $.ajax({
        type: "post",
        dataType:"json",
        url: "/candysnatch/SnatchPastPeriod",
        data: {
            offset: offset,
            token: $('.token').val()
        },
        success: function (json) {
            if (!!json.data) {
                // console.log(json);
                var len = json.data[0].length;
                var data = json.data[0];
                var html = "";
                if (len == 0) {
                    html += "<div class='list_null_wrap'><div class='list_null'><p>小手一投，幸运儿就是您！</p></div><div class='prize_btn_wrap'><a class='cli_jump_snatch_now' href='javascript:;'>立即夺宝</a></div></div>"
                    $('.JS_product_prev').html(html);
                }else if(len == 30){
                    for (var i = 0; i < len; i++) {
                        html+="<a class='snatch_jump_productDetail' href='storemanager://api?type=webview&gobackrefresh=true&url="
                        html+=encodeURIComponent(location.origin+"/candysnatch/SnatchProduct/?token="+$('.token').val()+"&periodId="+data[i].id+"&sign=3")
                        html+="'><li><div class='product_left'><img src="
                        html+=data[i].image_main
                        html+=" class='product_img'>"
                        if(data[i].productInfo.type == 2){
                            html+="<img class='wqjl_inviter_icon' src='https://event.ncfwx.com/upload/image/20190102/14-59-only_inviter_icon.png' alt='only_inviter_icon'>"
                        }
                        html+="</div><div class='product_right'><p class='product_detail'><span class='product_name'>"
                        html+=data[i].productInfo.title
                        html+="</span></p><p class='prize_info'>恭喜"
                        html+=(data[i].userInfo ? data[i].userInfo.real_name : "")
                        html+=((data[i].userInfo ? data[i].userInfo.sex : "") == 1) ? "先生" : "女士"
                        html+=" ("
                        html+=(data[i].userInfo ? data[i].userInfo.mobile : "")
                        html+=") 获奖</p><p class='prize_time'>开奖时间："
                        html+=timeSta(data[i].prize_time*1000)
                        html+="</p><p class='product_issue'><span>第"
                        html+=data[i].id
                        html+="期</span></p><p class='prize_code'>开奖码:<span>"
                        html+=data[i].prize_code
                        html+="</span></p></div></li></a>"
                    }
                    $('.JS_product_prev').html(html);
                    $('.JS_product_prev').append(more_dom);
                }else{
                    for (var i = 0; i < len; i++) {
                        html+="<a class='snatch_jump_productDetail' href='storemanager://api?type=webview&gobackrefresh=true&url="
                        html+=encodeURIComponent(location.origin+"/candysnatch/SnatchProduct/?token="+$('.token').val()+"&periodId="+data[i].id+"&sign=3")
                        html+="'><li><div class='product_left'><img src="
                        html+=data[i].image_main
                        html+=" class='product_img'>"
                        if(data[i].productInfo.type == 2){
                            html+="<img class='wqjl_inviter_icon' src='https://event.ncfwx.com/upload/image/20190102/14-59-only_inviter_icon.png' alt='only_inviter_icon'>"
                        }
                        html+="</div><div class='product_right'><p class='product_detail'><span class='product_name'>"
                        html+=data[i].productInfo.title
                        html+="</span></p><p class='prize_info'>恭喜"
                        html+=(data[i].userInfo ? data[i].userInfo.real_name : "")
                        html+=((data[i].userInfo ? data[i].userInfo.sex : "") == 1) ? "先生" : "女士"
                        html+=" ("
                        html+=(data[i].userInfo ? data[i].userInfo.mobile : "")
                        html+=") 获奖</p><p class='prize_time'>开奖时间："
                        html+=timeSta(data[i].prize_time*1000)
                        html+="</p><p class='product_issue'><span>第"
                        html+=data[i].id
                        html+="期</span></p><p class='prize_code'>开奖码:<span>"
                        html+=data[i].prize_code
                        html+="</span></p></div></li></a>";
                    }
                    $('.JS_product_prev').html(html);
                    $('.JS_product_prev').append(no_more);
                }
                //在往期记录tap时点击商品进入商品详情页时保持原来状态
                if(WXP2P.APP.getCookie('snatch_tap_right') == 1){
                    $(window).scrollTop(WXP2P.APP.getCookie('scroll_top'));
                }
            }
        },
        error: function(){
            WXP2P.UI.showErrorTip("没网络了，请稍后重试！");
            console.log("没网络了，请稍后重试！");
        }
    });

    //点击显示更多重新请求接口
    $('.JS_product_prev').on('click','.cli_more', function(){
        if (flag) {
            flag = false;
            offset++;
            $.ajax({
                type: "post",
                dataType: "json",
                url: "/candysnatch/SnatchPastPeriod",
                data: {
                    offset: offset,
                    token: $('.token').val()
                },
                success: function (json) {
                    if (!!json.data) {
                        var html = "";
                        var len = json.data[0].length;
                        var data = json.data[0];
                        if(len == 30){
                            for (var i = 0; i < len; i++) {
                                html+="<a class='snatch_jump_productDetail' href='storemanager://api?type=webview&gobackrefresh=true&url="
                                html+=encodeURIComponent(location.origin+"/candysnatch/SnatchProduct/?token="+$('.token').val()+"&periodId="+data[i].id+"&sign=3")
                                html+="'><li><div class='product_left'><img src="
                                html+=data[i].image_main
                                html+=" class='product_img'>"
                                if(data[i].productInfo.type == 2){
                                    html+="<img class='wqjl_inviter_icon' src='https://event.ncfwx.com/upload/image/20190102/14-59-only_inviter_icon.png' alt='only_inviter_icon'>"
                                }
                                html+="</div><div class='product_right'><p class='product_detail'><span class='product_name'>"
                                html+=data[i].productInfo.title
                                html+="</span></p><p class='prize_info'>恭喜"
                                html+=(data[i].userInfo ? data[i].userInfo.real_name : "")
                                html+=((data[i].userInfo ? data[i].userInfo.sex : "") == 1) ? "先生" : "女士"
                                html+=" ("
                                html+=(data[i].userInfo ? data[i].userInfo.mobile : "")
                                html+=") 获奖</p><p class='prize_time'>开奖时间："
                                html+=timeSta(data[i].prize_time*1000)
                                html+="</p><p class='product_issue'><span>第"
                                html+=data[i].id
                                html+="期</span></p><p class='prize_code'>开奖码:<span>"
                                html+=data[i].prize_code
                                html+="</span></p></div></li></a>"
                            }
                            var div_dom = document.createElement('div');
                            div_dom.innerHTML = html;
                            $(div_dom).insertBefore(more_dom);
                            // $(more_dom).replaceWith(no_more);
                        }else{
                            for (var i = 0; i < len; i++) {
                                html+="<a class='snatch_jump_productDetail' href='storemanager://api?type=webview&gobackrefresh=true&url="
                                html+=encodeURIComponent(location.origin+"/candysnatch/SnatchProduct/?token="+$('.token').val()+"&periodId="+data[i].id+"&sign=3")
                                html+="'><li><div class='product_left'><img src="
                                html+=data[i].image_main
                                html+=" class='product_img'></div>"
                                if(data[i].productInfo.type == 2){
                                    html+="<img class='wqjl_inviter_icon' src='https://event.ncfwx.com/upload/image/20190102/14-59-only_inviter_icon.png' alt='only_inviter_icon'>"
                                }
                                html+="<div class='product_right'><p class='product_detail'><span class='product_name'>"
                                html+=data[i].productInfo.title
                                html+="</span></p><p class='prize_info'>恭喜"
                                html+=(data[i].userInfo ? data[i].userInfo.real_name : "")
                                html+=((data[i].userInfo ? data[i].userInfo.sex : "") == 1) ? "先生" : "女士"
                                html+=" ("
                                html+=(data[i].userInfo ? data[i].userInfo.mobile : "")
                                html+=") 获奖</p><p class='prize_time'>开奖时间："
                                html+=timeSta(data[i].prize_time*1000)
                                html+="</p><p class='product_issue'><span>第"
                                html+=data[i].id
                                html+="期</span></p><p class='prize_code'>开奖码:<span>"
                                html+=data[i].prize_code
                                html+="</span></p></div></li></a>";
                            }
                            var div_dom = document.createElement('div');
                            div_dom.innerHTML = html;
                            $(more_dom).replaceWith(no_more);
                            $(div_dom).insertBefore(no_more);
                        }
                        flag = true;
                    }
                }
            })
        }
    })
    //往期记录为空时，点击立即夺宝按钮
    $('.JS_product_prev').on('click','.prize_btn_wrap', function(){
        $('.snatch_tap_right').removeClass('snatch_activity');
        $('.snatch_tap_left').addClass('snatch_activity');
        $('.JS_product_prev').hide();
        $('.JS_product_now').show();
        return false;
    })
    $('.snatch_tap_left').tap(function(){
        WXP2P.APP.setCookie("snatch_tap_right",0,-1);
        var tap_class = $('.snatch_tap_left').attr('class');
        if(tap_class.indexOf('snatch_activity') != -1){
            return;
        }else{
            $('.snatch_tap_right').removeClass('snatch_activity');
            $('.snatch_tap_left').addClass('snatch_activity');
            $('.JS_product_prev').hide();
            $('.JS_product_now').show();
        }
        zhuge.track("信宝夺宝-往期记录");
    })

    $('.snatch_tap_right').tap(function(){
        WXP2P.APP.setCookie("snatch_tap_right",1,1);
        var tap_class = $('.snatch_tap_right').attr('class');
        if(tap_class.indexOf('snatch_activity') != -1){
            return;
        }else{
            $('.snatch_tap_left').removeClass('snatch_activity');
            $('.snatch_tap_right').addClass('snatch_activity');
            $('.JS_product_prev').show();
            $('.JS_product_now').hide();
        }
    })
})