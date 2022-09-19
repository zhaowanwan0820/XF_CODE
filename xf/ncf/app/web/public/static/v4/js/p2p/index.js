$(function() {
    // banner焦点图
    if (typeof aImg == "undefined" || typeof aHref == "undefined") {
        $('.slide_pager').hide();

    } else {
        var imgLen = aImg.length;
        var curTab = 0;
        var autoFlag;
        // 最多16个
        if (imgLen > 16) imgLen = 16;
        // 添加元素
        for (var i = 0; i < imgLen; i++) {
            $('.banner_view').append('<li style="background-image:url(' + aImg[i] + ')"><a target="_blank" href="' + aHref[i] + '"></a></li>');

            if (imgLen == 1 || aHref.length == 1) {
                $('.banner_view li').eq(0).show();
                $('.slide_pager').hide();

            } else {
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
            $('.banner_view li').stop().fadeOut(500).css('z-index', '0').eq(idx).stop().fadeIn(500).css('z-index', '1');
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

    //通知关闭
    $("#qiye_gg").on("click" , ".j_close" , function(){
        $(this).parent().parent().remove();
    });

    //下载二维码
    $(".j_app_down").hover(function(){
        $(this).find("div").removeClass('none');
    }, function(){
        $(this).find("div").addClass('none');
    });

    //顶部滚动菜单
    $(window).on('scroll' , function(){
        var $t = $(this),
        $menu = $("#menu_fixed");
        if($t.scrollTop() > 125){
            $menu.addClass('menu_fixed');
        }else{
            $menu.removeClass('menu_fixed');
        }
    });

    //启动我的账户逻辑
    try {
        if (typeof(USER_INFO) != 'undefined' && USER_INFO == 1) {
            myAccount();
        }
    } catch (e) {

    }

    //我的账户
    function myAccount() {
        var ele = $('.my_account');
        var ele_msg = $('.j_showMenu2');
        if (ele.find("ul").length > 0) {
            ele.hover(
                function() {
                    ele.addClass("select");
                },
                function() {
                    ele.removeClass("select");
                }
            );
        }

        ele_msg.hover(
            function() {
                ele_msg.addClass("select");
            },
            function() {
                ele_msg.removeClass("select");
            }
        )

    }

    //合作伙伴
    // $('#focus').autoslide({
    //     effect: "x",
    //     autoPlay: false
    // });

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
            cur: "current",
            tabLab: ".j_index_tab",
            tabConLab: ".ui_product_tab",
            tagPos: 1
        });
    })();





});