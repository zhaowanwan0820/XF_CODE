$(function () {

    var token=$('#tokenHidden').val();
    var loanId=$('#loanIdHidden').val();
    var repayListUl=$('#repayListUl');
    var repayList=$('#repayListHidden').val();
    var ajaxPage=$('#ajaxPage');
    repayList=JSON.parse(repayList);
    var jsonData={};

    // console.log(JSON.stringify(repayList));
    function initPanel() {
        if(repayList.totalNum==0){
            $('#defaultPH').show();
        }else{
            createItem(repayList.data);
        }
        ajaxPage.css('display',repayList.totalPage>1?'block':'none');
    }
    initPanel();
    function createItem(data) {
        var newData={};
        $.each(data, function (index,item) {
            switchData(item,newData);
        });
        Object.keys(newData).reverse().forEach(function (key) {
            createHtml(key,newData[key]);
        });
    }
    function createHtml(key,json){
        json.title=getMonthRecord(key);
        var html="";
        var newNode=null;
        if(!jsonData[key]){
            html=template('repayItemTel',json);
            newNode=$(html).appendTo(repayListUl).find('.recordList');
            jsonData[key]=newNode;
        }else{
            html=template('recordItemTel',json);
            jsonData[key].append(html);
        }
    }
    function switchData(data,newData){
        var dateStr=getYearMonth(data.createTime);
        if (!newData[dateStr]){
            newData[dateStr]={
                "list":[]
            };
        }
        newData[dateStr].list.push(data);
    }
    function getYearMonth(createTime) {
        var date=new Date(createTime*1000);
        var dateStr=date.getFullYear()+''+addZeroPrefix(date.getMonth()+1);
        return dateStr;
    }
    ajaxPage.on('click',function () {
        if($(this).hasClass('text') || $(this).hasClass('loading')){
            return;
        }
        var curPage=$(this).data('page');
        var _this=$(this);
        changeBtnStatus('loading','正在加载中……');
        $.ajax({
            "url":'/speedloan/RepayDetailApi',
            "method":'get',
            "data":{
                "page":curPage,
                "token":token,
                "loanId":loanId
            },
            "success":function (resultVal) {
                var data=resultVal.data;
                // console.log(data);
                if (resultVal.errno!=0){
                    new ToastPop({
                        "content":resultVal.error,
                        "clickHide":true,
                        "delayHideTime":2500
                    });
                    changeBtnStatus('loadMore','点击加载更多');
                }else{
                    createItem(data.data);
                    if (data.totalPage <= curPage ) {
                        changeBtnStatus('text', '没有更多');
                    } else {
                        changeBtnStatus('loadMore', '点击加载更多');
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
                })
            }
        });
    })
    function changeBtnStatus(className,text) {
        ajaxPage.removeClass('loadMore text loading').addClass(className).text(text);
    }
})