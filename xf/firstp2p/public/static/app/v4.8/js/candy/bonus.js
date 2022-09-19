$(function () {

    var token=$('#token_hidden').val();
    var title=$('#title_hidden').val();
    var face=$('#face_hidden').val();
    var mainBox=$('#mainBox');
    var nextPage=$('#nextPage');
    var listDom=null;
    var pageInfor=null;
    var nextPage=null;
    var page=0;
    var pageSize=10;
    var isLoading=false;
    
    window.onload = function(){
        zhuge.track('信力任务_进入分享列表页');
    }
    $('.JS_bonus').click(function(e){
        zhuge.track('信力任务_分享列表_点击去分享按钮',{
            "位置":"分享按钮"
        });
    })
    template.helper('shareHref', function (shareContent,url) {
        return 'bonus://api?title='+title+'&content='+shareContent+'&face='+face+'&url='+url;
    });

    /**
     * 分页请求
     */
    function pageAjax(initRequest) {
        if (isLoading){
            return;
        }
        isLoading=true;
        $.ajax({
            url: '/candy/BonusAjax',
            type: 'get',
            dataType: 'json',
            data:{
                page:++page,
                pageSize:pageSize,
                token:token
            },
            success: function (returnVal) {
                var data=returnVal.data;
                var initPage=Number(data.page);
                var initTotalPage=Number(data.totalPage);
                if (returnVal.errno!=0){
                    mainBox.append('<div class="no_coupon"><p>这里空空如也</p></div>');
                    P2PWAP.ui.toast(returnVal.error);
                }else{
                    if(initRequest){
                        if (data.totalPage==0){
                            mainBox.append('<div class="no_coupon"><p>这里空空如也</p></div>');
                        }else{
                            listDom=$('<div class="list"></div>').appendTo(mainBox);
                            pageInfor=$('<div class="pageInfor"></div>').appendTo(mainBox);
                            if (initPage<initTotalPage){
                                nextPage=$('<a href="javascript:;" class="nextPage" id="nextPage">点击加载更多</a>')
                                    .appendTo(pageInfor)
                                    .on('click',function () {
                                        pageAjax(false);
                                    });
                            }else{
                                $('<span class="noMore">没有更多了</span>')
                                    .appendTo(pageInfor)
                            }
                        }
                    }else{
                        if (initPage>=initTotalPage){
                            pageInfor.html('<span class="noMore">没有更多了</span>');
                        }
                    }
                    data.list.forEach(function (item,index) {
                        listDom.append(createBonusRecord(item));
                    });
                }
                page=initPage;
            },
            error:function () {
                mainBox.append('<div class="no_coupon"><p>这里空空如也</p></div>');
                P2PWAP.ui.toast('服务器端异常，请稍候重试！');
            },
            complete:function () {
                isLoading=false;
            }
        });
    }

    function createBonusRecord(data){
        var htmlStr=template('bonus_record_tpl',data);
        return htmlStr;
    }

    pageAjax(true);

    triggerScheme("firstp2p://api?type=rightbtn&title=");

});