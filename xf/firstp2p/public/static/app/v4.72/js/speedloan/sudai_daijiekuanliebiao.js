$(function () {

    var ajaxPage=$('#ajaxPage');
    var token=$('#tokenHidden').val();
    var loadLi=ajaxPage.closest('li');

    ajaxPage.on('click',function () {

        if($(this).hasClass('text') || $(this).hasClass('loading')){
            return;
        }
        var curPage=$(this).data('page');
        var _this=$(this);
        changeBtnStatus('loading','正在加载中……');
        $.ajax({
            "url":'/speedloan/repayListApi',
            "method":'get',
            "data":{
                "page":curPage,
                "token":token
            },
            "success":function (resultVal) {
                var data=resultVal.data;
                if (resultVal.errno!=0){
                    new ToastPop({
                        "content":resultVal.error,
                        "clickHide":true,
                        "delayHideTime":2500
                    });
                    changeBtnStatus('loadMore','点击加载更多');
                }else{
                    appendNewLi(data.list);
                    if (data.totalPage<=curPage){
                        changeBtnStatus('text','没有更多');
                    }else{
                        changeBtnStatus('loadMore','点击加载更多');
                        curPage++;
                        _this.data('page',curPage);
                    }
                }
            },
            "error":function () {
                new ToastPop({
                    "content":'服务器端异常，请稍后重试',
                    "clickHide":true,
                    "delayHideTime":2500
                });
                changeBtnStatus('loadMore','点击加载更多');
            }
        });

    });

    function changeBtnStatus(className,text) {
        ajaxPage.removeClass('loadMore text loading').addClass(className).text(text);
    }

    function appendNewLi(list) {
        var liHtml="";
        list.forEach(function (data) {
            data.token=token;
            liHtml=template('itemTel',data);
            $(liHtml).insertBefore(loadLi);
        });
    }

    //服务时间判断
    $('#awaitRepayList').on('click','.btnBox .valid',function () {
        var _this=this;
        if ($(this).data('serveTimeLock')){
            return;
        }
        $(this).data('serveTimeLock',true);
        checkServeTime('normal',token,function (resultVal) {
            var hrefText=$(_this).data('hrefText');
            location.assign(hrefText);
        }).always(function () {
            $(_this).data('serveTimeLock',false);
        });
    });

});