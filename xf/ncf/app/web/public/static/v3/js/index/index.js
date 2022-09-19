$(function() {
    // banner焦点图
    if (typeof aImg == "undefined" || typeof aHref == "undefined" || aImg.length == 1 || aHref.length == 1) {
        $('.slide_pager').hide();
    } else {
        var imgLen = aImg.length;
        var curTab = 0;
        var autoFlag;
        // 最多16个
        if(imgLen > 16) imgLen = 16;
        // 添加元素
        for (var i = 0; i < imgLen; i++) {
            $('.banner_view').append('<li style="background-image:url(' + aImg[i] + ')"><a target="_blank" href="' + aHref[i] + '"></a></li>');
            if (i == 0) {
                $('.banner_view li').eq(0).show();
                $('.slide_pager ul').append('<li class="active"></li>')
            } else {
                $('.slide_pager ul').append('<li></li>')
            }
        }
        // 计算导航位置与图片容器宽度
        $('.slide_pager').css('margin-left', $('.slide_pager').width() / 2 * (-1) + 'px');
        // 移动函数
        function switchFoucs(idx) {
            $('.slide_pager ul li').removeClass('active').eq(idx).addClass('active');
            $('.banner_view li').stop().fadeOut(500).css('z-index','0').eq(idx).stop().fadeIn(500).css('z-index','1');
        }

        // 自动函数
        function autoAni() {
            curTab++;
            if (curTab >= imgLen) curTab = 0;
            switchFoucs(curTab);
        }

        function setAutoAni() {
            autoFlag = setInterval(function() {
                autoAni(curTab);
            }, 5000);
        }

        // 默认初始滚动
        setAutoAni();

        function clearAutoAni() {
            clearInterval(autoFlag);
        }


        // 绑定事件
        $('.slide_pager ul li').mouseover(function(event) {
            curTab = $(this).index();
            switchFoucs(curTab);
        });
        $('.banner_slide').mouseover(function() {
            clearAutoAni();
        });
        $('.banner_slide').mouseout(function() {
            setAutoAni();
        });
    }
    // 合作伙伴
    $("#scroll").scrollView({
            displayNum: 1,
            scrollNum: 1
        }).find("img").lazyload({
        effect: "fadeIn"
    });
    $("#scroll .scroll_up , #scroll .scroll_down").click(function() {
        $(this).parent().find("img").trigger("appear");
    });

    //项目进度
    progress_rate($('.progress_rate'));
    function progress_rate(ele) {
        ele.each(function(i, el) {
            var ele = $(el);
            var total = ele.attr('total');
            var has = ele.attr('has');
            var REG = /^[\d\.]+$/;
            var percent = 0;
            if (!(REG.test(total) && REG.test(has))) {
                return;
            }
            total = Math.floor(total.replace(/\..*/, ''));
            has = Math.floor(has.replace(/\..*/, ''));
            percent = (Math.floor((has / total) * 10000) / 100).toFixed(2) + "%";
            ele.find('.ico_yitou').css("width", percent);
            ele.find('.pl5').html(percent);
            // console.log(i, total, has, percent);
        });
    }

    // tab切换
    // $("#tabs").tabs();
    (function() {
        var obj = {};
        $('#tabs').goodTab({
            cur: "active",
            tabLab: ".j_index_tab",
            tabConLab: ".con",
            clickEvent: function($t, index) {
                var $tbody = $('#tabs .tabbd .con .conbd').eq(index),
                    id = $t.data("id");
                !!$("img").data("update") && $("img").data("update")();
                if (id === 0) {
                    return;
                }
                if (!obj[id]) {
                    $.ajax({
                        url: '/index/cate',
                        dataType: 'text',
                        data: {
                            cate: id
                        },
                        beforeSend: function() {
                            $tbody.html('<div class="tab_loading"></div>');
                        },
                        success: function(data) {
                            $tbody.html(data);
                            obj[id] = 1;
                            progress_rate($tbody.find('.progress_rate'));
                            //首页调用(ajax)
                            //removeHref($tbody);
                        }
                    });
                }
            }
        });
    })();

    //首页新闻版块js
    var JS_is_wxlc = $('.JS_is_wxlc').val();
    if(JS_is_wxlc == 1){
    (function(){
        var is_wxlc = window.location.hostname.indexOf('wangxinlicai')>=0;
        var is_ncfwx = window.location.hostname.indexOf('ncfwx')>=0;
        var pubUrl = "//news.wangxinlicai.com/p2pApi/";
        if(is_ncfwx){
            pubUrl = "//news.ncfwx.com/p2pApi/";
        }
        var urlOb = {
            mtbd : pubUrl + "mtbd/4",
            ptgg : pubUrl + "ptgg/4",
            hkgg : "/news/hklist?ajax=1&ps=11"
        },
        hideNews = function(url){
            $("#newsPart").hide();
            if(!!console){
                console.log("【"+ url + "】出问题了,请检查接口！");
            }
        },
        collectData = function(url ,callback){
            $.ajax({
                url : url ,
                dataType: "jsonp",
                success: function(data){
                    if(data.status == "20000"){
                        callback(data.data)
                    }else{
                        hideNews(url);
                    }
                },
                error:function(){
                    hideNews(url);
                }
            });
        },
        showList = function(){
            var succShow = function(data, url){
                var arr = [];
                //var data = {"status":1,"info":"","jump":"","data":[{"title":"2015-06-18 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150618"},{"title":"2015-06-17 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150617"},{"title":"2015-06-10 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150610"},{"title":"2015-06-06 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150606"},{"title":"2015-06-05 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150605"},{"title":"2015-06-04 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150604"},{"title":"2015-06-03 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150603"},{"title":"2015-06-02 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150602"},{"title":"2015-06-01 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150601"},{"title":"2015-05-29 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150529"},{"title":"2015-05-28 \u8fd8\u6b3e\u516c\u544a","url":"\/news\/hkgg\/20150529"}]};
                if(data.status == 1 && !!data.data && data.data.length > 0){
                    arr.push('<ul class="arc-list">');
                    $.each(data.data , function(i , v){
                        arr.push('<li>\
                        <a href="'+ v.url +'" target="_blank" title="'+ v.title +'"  class="tit">'+ v.title +'</a>\
                        </li>');
                    });
                    arr.push('</ul>');
                    $("#hkgg .con").html(arr.join(""));
                    arr = null;
                }else{
                    hideNews(url);
                }
            };
            $.ajax({
                url : urlOb.hkgg ,
                dataType: "json",
                success: function(data){
                    succShow(data,urlOb.hkgg);
                },
                error:function(){
                    hideNews(urlOb.hkgg);
                }
            });

        };
        //加载还款公告
        showList();


        //加载媒体报道4条
        collectData(urlOb.mtbd , function(data){
            var arr = [];
            if(is_ncfwx){
                data.more = data.more.replace(/wangxinlicai/,"ncfwx");
            }
            $("#mtbd .new_more").attr({
                "href" : data.more ,
                "target": "_blank"
            });
            var purl = $('#mtbd .new_more').attr('href');
            if(!purl){
                return;
            }
            var purl = purl.replace('http:',"");
            $('#mtbd .new_more').attr('href',purl);
            $.each(data.list , function(i , v){
                var str = "";
                if (v.url){
                    v.url = v.url.replace("http:","");

                    if(is_ncfwx){
                        v.url = v.url.replace(/wangxinlicai/,"ncfwx");
                    }
                }
                if(i === 0){
                    if(!v.cover_url){
                        str = "//www.ncfwx.com/static/v2/images/index/mtbd.jpg";
                    }else{
                        str = v.cover_url.replace("http:","");
                    }
                    arr.push(' <div class="new_img"><a href="'+ v.url +'" target="_blank"><img src="'+ str +'"></a><p><a href="'+ v.url +'" target="_blank">'+ v.title +'</a></p></div>');
                    arr.push('<ul class="arc-list">')
                }else{
                    arr.push('<li><a class="tit" href="'+ v.url +'" target="_blank" title="'+ v.title + '" rel="nofollow">' + v.title +'</a><span class="date">' + v.create_time +'</span></li>');
                }

            });
            arr.push("</ul>");
            $("#mtbd .con").html(arr.join(""));
            arr = null;
        });

        //加载平台公告4条
        collectData(urlOb.ptgg , function(data){
            var arr = [];
            if(is_ncfwx){
                data.more = data.more.replace(/wangxinlicai/,"ncfwx");
            }
            $("#ptgg .new_more").attr({
                "href" : data.more ,
                "target": "_blank"
            });
            var purl = $('#ptgg .new_more').attr('href');
            if(!purl){
                return;
            }
            var purl = purl.replace('http:',"")
            $('#ptgg .new_more').attr('href',purl);
            $.each(data.list , function(i , v){
                var str = "";
                if (v.url){
                    v.url = v.url.replace("http:","");
                    if(is_ncfwx){
                        v.url = v.url.replace(/wangxinlicai/,"ncfwx");
                    }
                }
                if(i === 0){
                    if(!v.cover_url){
                        str = "//www.ncfwx.com/static/v2/images/index/ptgg.jpg";
                    }else{
                        str = v.cover_url.replace("http:","");
                    }
                    arr.push(' <div class="new_img"><a href="'+ v.url +'" target="_blank"><img src="'+ str +'"></a><p><a href="'+ v.url +'" target="_blank">'+ v.title +'</a></p></div>');
                    arr.push('<ul class="arc-list">')
                }else{
                    arr.push('<li><a class="tit" href="'+ v.url +'" target="_blank" title="'+ v.title + '" rel="nofollow">' + v.title +'</a><span class="date">' + v.create_time +'</span></li>');
                }

            });
            arr.push("</ul>");
            $("#ptgg .con").html(arr.join(""));
            arr = null;
        });


    })();
    }

    // 首页勋章提示
    (function() {
        var nongdan_flag = $("#isNongdan_index").val();
        // 新手勋章开启提示
        if (medalBeginner == 1 && nongdan_flag != 1) {
            var promptStr = '';
            promptStr = '<div class="txt tc f18">欢迎加入<span class="f24 red">网信</span>，完成新手任务获得丰厚<span class="f24 red">奖励</span>！' +
                '</div>';
            Firstp2p.alert({
                text: promptStr,
                ok: function(dialog) {
                    dialog.close();
                    location.href = '/medal/wall';
                },
                width: 674,
                okBtnName: '查看任务',
                boxclass: "medal-popbox kq-popbox"
            });
        }
    })();
    (function() {
        //勋章新手任务进度提示
        var initBottom=170;
        if ($('#isNewUser_hidden').length && $('#isNewUser_hidden').val()==1){
            initBottom=initBottom+130;
        }
        $(window).bind("scroll",
            function() {
                var st = $(document).scrollTop();
                if (st > 300) {
                    $('.lay_medal_process').css({
                        "bottom": initBottom+71
                    });
                } else {
                    $('.lay_medal_process').css({
                        "bottom": initBottom
                    });
                }
            });

        $.ajax({
            url: '/medal/progress',
            type: 'GET',
            data: {},
            dataType: 'json',
            success: function(result) {
                // console.log(JSON.stringify(result));
                if (!!result.data.isBeginner && result.data.userBeginnerMedalCount < result.data.beginnerMedalCount) {
                    if ($(window).scrollTop()>300){
                        $('.lay_medal_process').css({
                            "bottom": initBottom+71
                        });
                    }else{
                        $('.lay_medal_process').css({
                            "bottom": initBottom
                        });
                    }
                    $("#j_lay_medal_process").show();
                    $("#j_userBeginnerMedalCount").html(result.data.userBeginnerMedalCount);
                    $("#j_beginnerMedalCount").html(result.data.beginnerMedalCount);
                    if(result.data.userBeginnerMedalCount == 0){
                        $(".lay_medal_process").css("background-position","0 -233px");
                    } else if(result.data.userBeginnerMedalCount == 1){
                        $(".lay_medal_process").css("background-position","0 -116px");
                    } else if(result.data.userBeginnerMedalCount == 2){
                        $(".lay_medal_process").css("background-position","0 -1px");
                    }
                }
            },
            error: function() { }
        });

        $("body").on("click","#j_pro_close",function(){
            $("#j_lay_medal_process").remove();
            $.get("/medal/circle");
        });
    })();

});



