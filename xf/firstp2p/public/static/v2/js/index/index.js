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
            displayNum: 4,
            scrollNum: 4
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
    (function(){
        var pubUrl = "//news.firstp2p.com/p2pApi/",
            urlOb = {
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
            $("#mtbd .new_more").attr({
                "href" : data.more ,
                "target": "_blank"
            });
            $.each(data.list , function(i , v){
                var str = "";
                if(i === 0){
                    if(!v.cover_url){
                        str = "//www.firstp2p.com/static/v2/images/index/mtbd.jpg";
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
            $("#ptgg .new_more").attr({
                "href" : data.more ,
                "target": "_blank"
            });
            $.each(data.list , function(i , v){
                var str = "";
                if(i === 0){
                    if(!v.cover_url){
                        str = "//www.firstp2p.com/static/v2/images/index/ptgg.jpg";
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

    
});
