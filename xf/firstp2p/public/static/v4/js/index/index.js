$(function() {


    // banner焦点图
    if (typeof aImg == "undefined" || typeof aHref == "undefined") {
        $('.slide_pager').hide();

    } else {
        var imgLen = aImg.length;
        var curTab = 0;
        var autoFlag;
        // 最多16个
        if(imgLen > 16) imgLen = 16;
        // 添加元素
        for (var i = 0; i < imgLen; i++) {
            $('.banner_view').append('<li class="JS_banner" data-index='+ (i + 1) +' style="background-image:url(' + aImg[i] + ')"><a target="_blank" href="' + aHref[i] + '"></a></li>');

            if(imgLen == 1 || aHref.length == 1){
                $('.banner_view li').eq(0).show();
                $('.slide_pager').hide();

            }else{
                if (i == 0) {
                    $('.banner_view li').eq(0).show();
                    $('.slide_pager ul').append('<li class="active"></li>')
                } else {
                    $('.slide_pager ul').append('<li></li>')
                }
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



    // tab切换
    // $("#tabs").tabs();
    (function() {
        var obj = {};
        $('#tabs').goodTab({
            cur: "current",
            tabLab: ".j_index_tab",
            tabConLab: ".ui_product_tab"

        });
    })();

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

    // 埋点
    $('.JS_banner').click(function() {
        var index = this.getAttribute("data-index")
        zhuge.track('首页_点击banner',{
            "位置" : index
        })
    });
});



