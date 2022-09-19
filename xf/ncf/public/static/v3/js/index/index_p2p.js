$(function() {
    // banner焦点图
    if (typeof aImg == "undefined" || typeof aHref == "undefined") {
        // $('.slide_pager').hide();
        $('.slide_pager_r,.slide_pager_l').hide();
    } else {
        var imgLen = aImg.length;
        var curTab = 0;
        var autoFlag;
        // 最多16个
        if (imgLen > 16) imgLen = 16;
        if (imgLen == 1) {
            $('.slide_pager_r,.slide_pager_l').hide();
        }
        // 添加元素
        for (var i = 0; i < imgLen; i++) {
            $('.banner_view').append('<li style="background-image:url(' + aImg[i] + ')"><a data-index= "' + (i + 1) + '" target="_blank" href="' + aHref[i] + '"></a></li>');
            if (i == 0) {
                $('.banner_view li').eq(0).show();
                // $('.slide_pager ul').append('<li class="active"></li>')
            } else {
                // $('.slide_pager ul').append('<li></li>')
            }
        }
        // 计算导航位置与图片容器宽度
        // $('.slide_pager').css('margin-left', $('.slide_pager').width() / 2 * (-1) + 'px');
        // 移动函数
        function switchFoucs(idx) {
            // $('.slide_pager ul li').removeClass('active').eq(idx).addClass('active');
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
        $('.slide_pager_r').click(function(event) {
            clearAutoAni();
            autoAni();
        });
        $('.slide_pager_l').click(function(event) {
            curTab--;
            if (curTab < 0) curTab = imgLen - 1;
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
    ;
    (function($) {
        $(function() {
            var  $con = $("#conbd");
            var get_search = function(data) {
                var html = '';
                if (!data) {
                    return html;
                }
                $.each(data, function(index, value) {
                    html += '<div class="p2p_product p5">\
                    <div class="clearfix bg_whtie">\
                    <div class="con_l">\
                    <h3 class="f16">';
                    if (!!value.deal_tag_name) {
                        html += '<i class="deal_tips bg_blue" title="{$deal.deal_tag_desc}">' + value.deal_tag_name + '</i>';
                    }
                    if (!!value.product_name) {
                        html += '<span class="product_name">' + value.product_name + '</span>';
                    }
                    if (value.bid_flag == 1) {
                        html += '<a title="' + value.old_name + '" alt="' + value.old_name + '"  href="' + value.url + '" target="_blank" >' + value.name + '</a>';
                    } else {
                        html += '<span title="' + value.old_name + '" alt="' + value.old_name + '" class="deal_tag_name">' + value.name + '</span>';
                    }
                    html += '</h3>\
                    <div class="fl w360">\
                    <p>\
                    <span>';
                    if (value.deal_type == 0) {
                        html += '年化借款利率';
                    } else {
                        html += '预期年化收益率';
                    }
                    html += '：<i class="f20">' + value.income_base_rate + '</i><ins class="percent">%</ins>';
                    if (value.type_id == 27) {
                        html += '起';
                    }
                    html += '</span>\
                    </p>\
                    <p>\
                    <span>总额：</span>';
                    html += value.borrow_amount_format_detail + '万\
                    </p>\
                    </div>\
                    <div class="fl w265">\
                    <p>\
                    <span>';
                    if (value.deal_type == 0) {
                        html += '借款期限';
                    } else {
                        html += '投资期限';
                    }
                    html += '：</span>';
                    if (value.loantype == 5) {
                        html += '<em><i class="f18">';
                        if (value.deal_type == 1) {
                            html += (value.lock_period + value.redemption_period) + '~';
                        }
                        html += value.repay_time + '</i>天</em>';
                    } else {
                        html += '<em><i class="f18">' + value.repay_time + '</i>个月</em>';
                    }
                    html += '</p>\
                    <p>\
                    <span>';
                    if (value.deal_type == 0) {
                        html += '还款方式';
                    } else {
                        html += '收益方式';
                    }
                    html += '</span>';
                    if (value.deal_type == 1) {
                        html += '提前' + value.redemption_period + '天申请赎回';
                    } else {
                        html += value.loantype_name;
                    }
                    html += ' </p>\
                    </div>\
                    <div class="fl w265 progress_rate" total="' + value.borrow_amount + '" has="' + value.load_money + '">\
                     <p>\
                        <span>投资进度：</span><span class="progress">\
                            <i class="ico_bace"></i>\
                            <i class="ico_yitou">进度条</i>\
                        </span><ins class="f12 pl5"></ins>\
                     </p>\
                     <p><span>剩余可投：</span>' + value.need_money_detail + '元</p>\
                        </div>\
                     </div>\
                     <div class="product_btn">';
                    if (value.deal_type == 0 || value.deal_type == 2 || value.deal_type == 3 || value.deal_type == 5) {
                        if (value.is_crowdfunding == 0) {
                            if (value.is_update == 1) {
                                html += '<a href="#" class="btn_touzi">查看</a>';
                            } else if (value.deal_status == 4) {
                                html += '<span class="btn_manbiao">还款中</span>';
                            } else if (value.deal_status == 0) {
                                html += '<a href="' + value.url + '" class="btn_touzi">查看</a>';
                            } else if (value.deal_status == 2) {
                                html += '<span class="btn_manbiao">满标</span>';
                            } else if (value.deal_status == 5) {
                                html += '<span class="btn_manbiao">已还清</span>';
                            } else {
                                html += ' <a href="' + value.url + '" class="btn_touzi">投资</a>';
                            }
                        } else if (value.is_crowdfunding == 1) {
                            if (value.is_update == 1) {
                                html += '<a href="' + value.url + '" class="btn_touzi">查看</a>';
                            } else if (value.deal_status == 4) {
                                html += '<span class="btn_manbiao">已成功</span>';
                            } else if (value.deal_status == 0) {
                                html += '<a href="' + value.url + '" class="btn_touzi">查看</a>';
                            } else if (value.deal_status == 2) {
                                html += '<span class="btn_manbiao">已成功</span>';
                            } else if (value.deal_status == 5) {
                                html += '<span class="btn_manbiao">已成功</span>';
                            } else {
                                html += ' <a href="' + value.url + '" class="btn_touzi">捐赠</a>';
                            }
                        }
                    } else {
                        if ($value.deal_status == 0) {
                            html += '<a href="' + value.url + '" class="btn_touzi">查看</a>';
                        }
                        if (value.deal_status == 1) {
                            if (value.deal_compound_status == 1) {
                                html += '<a href="' + value.url + '" class="btn_touzi">已投</a>';
                            } else {
                                html += '<a href="' + value.url + '" class="btn_touzi">投资</a>';
                            }
                        }
                        if (value.deal_status == 2) {
                            html += '<span class="btn_manbiao">满标</span>';
                        }
                        if (value.deal_status == 4) {
                            if (value.deal_compound_status == 2) {
                                html += '<span class="btn_manbiao">待赎回</span>';
                            }
                            if (value.deal_compound_status == 3) {
                                html += '<span class="btn_manbiao">还款中</span>';
                            }
                            if (value.deal_compound_status == 4) {
                                html += '<span class="btn_manbiao">已还清</span>';
                            }
                            if (value.deal_compound_status == 0) {
                                html += '<span class="btn_manbiao">还款中</span>';
                            }
                        }
                        if (value.deal_status == 5) {
                            html += '<span class="btn_manbiao">已还清</span>';
                        }
                    }
                    html += '</div>\
                    </div>\
                    </div>';

                });
            };

            //首页调用(ajax)
            var get_ajax = function(){
                $.ajax({
                    url: '/index/cate',
                    dataType: 'json',
                    data: {
                        cate: id
                    },
                    beforeSend: function() {
                        $con.html('<div class="tab_loading"></div>');
                    },
                    success: function(data) {

                        if(!!data && data.errorCode == 1){
                            $con.html(get_search(data.list));
                            $(".deal_more").show();
                        }else{
                            $con.html(' <div class="main">\
                               <div class="empty-box"><i></i>暂无可投项目</div>\
                           </div>');
                            $(".deal_more").hide();
                        }
                    }
                });
            }

            var getUrlParam = function(name) {
                var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
                var r = window.location.search.substr(1).match(reg);
                if (r != null) return unescape(r[2]);
                return null;
            }

            $(".p2p_subject_type li").each(function(){
                var $t = $(this),
                id = $t.data("id");
                if(getUrlParam("product_class_type") == id){
                    $t.addClass('active');
                }

            });

            $(".p2p_subject li").each(function(i , v){
                var $t = $(this),
                id = $t.data("id");
                if(!getUrlParam("loan_user_customer_type")){
                    if(i == 0){
                       $t.addClass('active');
                    }else{
                       $t.removeClass('active');
                    }
                }else{
                    $t.removeClass('active');
                }
                if(getUrlParam("loan_user_customer_type") == id){
                    $t.addClass('active');
                }

            });

            $('#tabs').on("click", ".p2p_subject a", function() {
                var $t = $(this),
                href = $t.attr("href");

                $(".p2p_subject_type li").removeClass('active');
            });

            $('#tabs').on("click", ".p2p_subject_type a", function() {
                var $t = $(this),
                href = $t.attr("href"),
                $p = $t.parent(),
                $local = location.href;
                if($p.hasClass('active')){
                    $p.removeClass('active');
                    $t.attr("href" , $local.replace(/product_class_type[^&]+&*/ig , ""))
                }else{
                    if(location.search.indexOf("loan_user_customer_type") > -1){
                       $t.attr("href" , href + "&loan_user_customer_type=" + getUrlParam("loan_user_customer_type"));
                    };
                }

            });

            $("#pagination").on("click" , "a" , function(){
                var $t = $(this),
                href = $t.attr("href");
                if(location.search.indexOf("product_class_type") > -1 || location.search.indexOf("loan_user_customer_type") > -1){
                    if(location.search.indexOf("p=") > -1){
                        $t.attr("href" , href + "&" + location.search.replace(/(\?|&)p=[^&][&\?]+/gi,""));
                    }else{
                        $t.attr("href" , href + "&" + location.search.replace("?",""));
                    }

                }
            });


        });

    })(jQuery);


});