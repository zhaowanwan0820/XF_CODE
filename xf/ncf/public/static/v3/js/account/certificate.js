(function($) {
    $(function() {
        var investText = "优惠券";
        var tabsUl = $("#tab_Nav");
        // tab事件绑定
        // tabsUl.on("click", "li a", function() {
        //     var $t = $(this),
        //     cerType = $t.data('tab');
        //     if ($t.parent('li').hasClass('select')) return;
        //     tabsUl.find("li").removeClass('select');
        //     $t.parent('li').addClass('select');
        //     cerData["type"] = cerType;
        //     reqData(cerData);
        // });

        // ajax请求
        function reqData(data) {
            var pageText = '';
            var _lock = tabsUl.find("li.select a").data('lock');
            if (_lock) {
                return false;
            }
            _lock = true;
            $.ajax({
                url: '/account/discountList',
                type: 'GET',
                data: data,
                dataType: 'json',
                success: function(result) {
                    cerHtml(result);
                    $('.fxq_title .zs').empty().html(result.cashbackcount);
                    $('.jxq_title .zs').empty().html(result.raiseratescount);
                    // 添加黄金券数量
                    $('.hjq_title .zs').empty().html(result.goldCount);
                    if(!result.isWhite){
                        $(".hjq_title").hide();
                        $(".tab_cont").addClass("li_number2").removeClass("li_number3");
                    } else {
                        $(".hjq_title").show();
                        $(".tab_cont").addClass("li_number3").removeClass("li_number2");
                    }
                    if(result.pagecount == data.page){
                        var nomoreText = '仅显示最近30天内的'+investText;
                        //投资券不可用张数
                        if(result.usedCount > 0){
                            nomoreText = '<a href="/account/discountUsed" class="blue">没有更多'+investText+'了，查看不可用'+investText+'</a>';
                        }
                        $(".j_tips").css("display" , "block").html(nomoreText);
                    }
                    if (result.pagecount <= 1) {
                        $(data.pageSelector).hide();
                        return;
                    } else {
                        $(data.pageSelector).show();
                        Firstp2p.paginate($(data.pageSelector), {
                            pages: result.pagecount,
                            currentPage: result.page,
                            displayedPages:3,
                            onPageClick: function(pageNumber, $obj) {
                                var obj = {
                                    page: pageNumber,
                                    pageSelector: data.pageSelector,
                                    useStatus: 1
                                };
                                if(is_firstp2p == 1){
                                    obj.consume_type = 1;
                                }
                                reqData(obj);
                            }
                        });
                        //分页结构二次加工
                        if(result.page==1){
                            indexPage=$('<li><span class="index" title="首页">首页</span></li>');
                        }else{
                            indexPage=$('<li><a href="#page=1" class="page-link index" title="首页">首页</a></li>');
                        }
                        if(result.page==result.pagecount){
                            lastPage=$('<li><span class="last" title="尾页">尾页</span></li>');
                        }else{
                            lastPage=$('<li><a href="#page='+result.pagecount+'" class="page-link last" title="尾页">尾页</a></li>');
                        }
                        pageText = '<li><span class="total">共<i>'+result.pagecount+'</i>页</span></li>';
                        $(data.pageSelector).find("ul").append(lastPage,pageText).prepend(indexPage);
                        $(data.pageSelector).find('a.last,a.index').on('click',function (event) {
                            event.preventDefault();
                            var page_number=$(this).attr('href').match(/.*#page=(\d*)$/)[1];
                            var obj = {
                                page: page_number,
                                pageSelector: data.pageSelector,
                                useStatus: 1
                            };
                            if(is_firstp2p == 1){
                                obj.consume_type = 1;
                            }
                            reqData(obj);
                        });
                    }
                    _lock = false;
                },
                error: function() {
                    _lock = true;
                }
            })
        }
        // 数据拼接
        function cerHtml(data) {
            var html = template('cer_data', data);
            var spanText = '';
            $('#tabs_cont').html(html);
            if (data.list.length > 0) {
                for (var i = 0; i < data.list.length; i++) {
                    var curCerDec = data.list[i].note;
                    spanText = function() {
                        var returnStr;
                        if (curCerDec.length <= 11) {
                            returnStr = curCerDec;
                        } else {
                            returnStr = curCerDec.substr(0, 8) + '... <a href="javascript:void(0);"  class="more_link blue">更多</a>';
                        }
                        return returnStr;
                    }();
                    $(".span_text").eq(i).html(spanText);
                }
            }
        }
        // 初始数据
        var cerData = {
            page: 1,
            pageSelector: '#pagination_00',
            useStatus: 1
        };
        if(is_firstp2p == 1){
            cerData.consume_type = 1;
        }
        reqData(cerData);

        //弹出层
        $("#tabs_cont").on("click", ".more_link", function() {
            var $t = $(this);
            var promptStr = '';
            var moreText = '';
            promptStr = '<div class="pop-tit"><i></i><span class="description"></span></div>';
            Firstp2p.alert({
                title: "使用限制",
                text: '<div class="f15">' + promptStr + '</div>',
                ok: function(dialog) {
                    dialog.close();
                },
                width: 435,
                showButton: true,
                boxclass: "cer_popbox"
            });
            moreText = $t.parent().attr("title");
            $(".description").html(moreText);
        });
    });
})(jQuery);