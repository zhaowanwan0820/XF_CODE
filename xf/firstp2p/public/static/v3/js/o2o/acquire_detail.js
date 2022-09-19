;(function($) {
    $(function() {
        (function() {
            var showDate = function(time){
                if(time <= 0){
                    return "";
                }
                var DateStr = new Date(parseInt(time)*1000),
                DateYear = DateStr.getFullYear(),
                DateMonth = DateStr.getMonth()+1,
                DateDay = DateStr.getDate(),
                DateHour = DateStr.getHours(),
                DateMin = DateStr.getMinutes(),
                DateSec = DateStr.getSeconds(),
                turnTwo = function(str){
                    var s = str.toString();
                    if(s.length <= 1){
                        return "0" + s;
                    }else{
                        return s;
                    }
                };
                return  DateYear + "-" + turnTwo(DateMonth) + "-" + turnTwo(DateDay)  + " " + turnTwo(DateHour) + ":" + turnTwo(DateMin) + ":" + turnTwo(DateSec) ;
            };
            if(typeof Firstp2p == 'undefined'){
                Firstp2p = {};
            }

            Firstp2p.ajaxPaginate = function(options){
                var defaultSettings = {
                      url: '/gift/unpickList',
                      data : {
                        page: 1,
                        hasCount: 1
                      },
                      pageContainer : $("#unpickListPage") ,
                      scriptId : 'o2o_unpicklist' ,
                      container : $('#unpicklist_coupon')
                },
                settings = $.extend(true, defaultSettings ,options),
                cerData = settings.data,
                cerHtml = function(data) {
                        var html = template(settings.scriptId, data);
                        settings.container.html(html);
                        if(data.container.find(".j_time_format").length > 0){
                            data.container.find(".j_time_format").each(function(){
                                $(this).html(showDate($(this).html()));
                            });
                        }
                         if(data.container.find(".j_table_tr_changeColor").length > 0){
                            data.container.find(".j_table_tr_changeColor tr:nth-child(even)").addClass('gray_bg');
                        }
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
                                if(settings.url == '/gift/mine'){
                                    if(result.count <= 0){
                                        $("#no_list2").show();
                                    }else{
                                        if(cerData.page == result.pageNum){
                                            $("#status2_last").show();
                                        } else {
                                           $("#status2_last").hide();    
                                        }
                                    }
                                }
                                
                                var pageContainer = settings.pageContainer;
                                if (result.pageNum <= 1) {
                                    pageContainer.hide();
                                    return;
                                } else {
                                    pageContainer.show();
                                    Firstp2p.paginate(pageContainer, {
                                        pages: result.pageNum,
                                        currentPage: cerData.page,
                                        onPageClick: function(pageNumber, $obj) {
                                            cerData["page"] = pageNumber;
                                            reqData(cerData);
                                        }
                                    });
                                    pageText = '<li style="line-height:25px;">' + result.count + ' 条记录 ' + cerData.page + '/' + result.pageNum + ' 页</li>';
                                    pageContainer.find("ul").prepend(pageText);
                                }
                            },
                            error: function() {}
                        })
                    };
                    reqData(cerData);
            };

            //获得的领券资格
            Firstp2p.ajaxPaginate();

            //已领取的礼券
            Firstp2p.ajaxPaginate({
                url : '/gift/mine' ,
                scriptId : 'o2o_picklist' ,
                container : $("#picklist_coupon") ,
                pageContainer : $("#pickListPage")
            });

            function firstComeIn() {
                //礼券数量调取接口
                $.ajax({
                    url: 'gift/UnpickCount',
                    type: 'GET',
                    dataType: 'json',
                    success: function(result) {
                        if(result.giftType == 1) {
                            $('.j_sub_nav li').eq(1).addClass('select').siblings().removeClass('select');
                        }else{
                            $('.j_sub_nav li').eq(0).addClass('select').siblings().removeClass('select');
                        }
                        var indexLi = $(".j_sub_nav").find('li.select').index();
                        var $con = $('.cunpon_tab .cunpon_tab_con').eq(indexLi);
                        $con.show().siblings().hide();
                    },
                    error: function(error) {
                        console.log("礼券gift/UnpickCount接口出错了，Error："+error)
                    }
                });
            }
            firstComeIn();

            $(".j_sub_nav").on("click" , "li" , function(){
                if($(this).hasClass('select')) return;
                var idx = $(this).index();
                var $con = $('.cunpon_tab .cunpon_tab_con').eq(idx);
                $(this).addClass('select').siblings().removeClass('select');
                $con.show().siblings().hide();
            });

            
        })();
    });
})(jQuery);