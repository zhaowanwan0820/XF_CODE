(function($) {
    $(function() {

        if (typeof Firstp2p == 'undefined') {
            Firstp2p = {};
        }
        Firstp2p.ajaxPaginate = function(options) {
            var defaultSettings = {
                    url: '../json/pc/0.json',
                    data: {
                        status: 1
                    },
                    scriptId: 'medal_list',
                    container: $('#ml_container')
                },
                settings = $.extend(true, defaultSettings, options),
                cerData = settings.data,
                cerHtml = function(data) {
                    var html = template(settings.scriptId, data);
                    settings.container.html(html);
                },
                reqData = function(data) {
                    var pageText = '';
                    $.ajax({
                        url: settings.url,
                        type: 'GET',
                        data: settings.data,
                        dataType: 'json',
                        beforeSend: function() {

                        },
                        success: function(result) {
                            result["container"] = settings.container;
                            cerHtml(result);
                        },
                        error: function() {}
                    })
                };
            reqData(cerData);
        };

        //勋章墙 及 我的勋章
        Firstp2p.ajaxPaginate();
        $(".j_sub_nav").on("click" , "li" , function(){
            var $t = $(this),
            $p = $t.parent();
            if($p.data("clicked") == $t.data("status")){
                return;
            }
            $p.data("clicked" , $t.data("status"));
            $t.addClass('select').siblings().removeClass('select');
            Firstp2p.ajaxPaginate({
                data : {
                    status : $t.data("status")
                }
            });

            // tab切换到我的勋章时过往的勋章隐藏

            if($t.data("status") == 0){
                    $("#past_list_box").show();
            } else {
                $("#past_list_box").hide();
            }

            // 有接口后下面if要删除

            if($t.data("status") == 1){
                Firstp2p.ajaxPaginate({
                    url: '../json/pc/mymedal_list.json'
                });
            } 


        });

        //过往的勋章
        Firstp2p.ajaxPaginate({
            // 有接口后url要删除
            url: '../json/pc/past_medallist.json',
            scriptId: 'past_list',
            container: $("#past_container"),
            data: {
                status: 1,
                type: 1
            }
        })


    });
})(jQuery);