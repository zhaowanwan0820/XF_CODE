(function($) {
    $(function() {
        var tabsUl = $("#tab_Nav");
        // tab事件绑定
        tabsUl.on("click", "li a", function() {
            var $t = $(this),
                status = $t.data('tab');
            if ($t.parent('li').hasClass('select')) return;
            tabsUl.find("li").removeClass('select');
            $t.parent('li').addClass('select');
            cerData["type"] = status;
            reqData(cerData);
        });
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
                    if (result.pagecount <= 0) {
                        $(data.pageSelector).hide();
                        return;
                    } else {
                        $(data.pageSelector).show();
                        Firstp2p.paginate($(data.pageSelector), {
                            pages: result.pagecount,
                            currentPage: result.page,
                            onPageClick: function(pageNumber, $obj) {
                                reqData({
                                    type: data.type,
                                    page: pageNumber,
                                    pageSelector: data.pageSelector
                                });
                            }
                        });
                        pageText = '<li style="line-height:25px;">' + result.count + ' 条记录 ' + result.page + '/' + result.pagecount + ' 页</li>';
                        $(data.pageSelector).find("ul").prepend(pageText);
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
                        if (curCerDec.length <= 29) {
                            returnStr = curCerDec;
                        } else {
                            returnStr = curCerDec.substr(0, 29) + '... <a href="javacript:void(0)"  class="more_link blue">更多</a>';
                        }
                        return returnStr;
                    }();
                    $(".span_text").eq(i).html(spanText);
                }
            }
        }
        // 初始数据
        var cerData = {
            type: "toBeUsed",
            page: 1,
            pageSelector: '#pagination_00'
        };
        reqData(cerData);

        //弹出层
        $("#tabs_cont").on("click", ".more_link", function() {
            var $t = $(this);
            var promptStr = '';
            var moreText = '';
            promptStr = '<div class="pop-tit"><i></i><span class="description"></span></div>';
            Firstp2p.alert({
                title: "使用限制",
                text: '<div class="f16">' + promptStr + '</div>',
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