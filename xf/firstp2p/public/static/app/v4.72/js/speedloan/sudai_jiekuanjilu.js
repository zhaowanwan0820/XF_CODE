
$(function () {
    
    template.helper("createLink",function (loanStatus,id) {
        loanStatus=Number(loanStatus);
        var html="";
        var url='/speedloan/loanDetail?token='+token+'&orderId='+id;
        switch (loanStatus){
            case 4:{
                html='<a href="javascript:;" class="noArrow">';
                break;
            }
            case 8:{
                html='<a href="'+url+'" class="noValid">';
                break;
            }
            default:{
                html='<a href="'+url+'">';
            }
        }
        return html;
    });

    var token=$('#tokenHidden').val();
    var loanListUl=$('#loanList');
    var loanList=$('#loanListHidden').val();
    loanList=JSON.parse(loanList);
    var ajaxPage=$('#ajaxPage');
    var jsonData={};
    // console.log(JSON.stringify(loanList));

    function initPanel() {
        if(loanList.totalNum==0){
            $('#defaultPH').show();
        }else{
            createItem(loanList.data);
        }
        ajaxPage.css('display',loanList.totalPage>1?'block':'none');
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
            html=template('loanItemTel',json);
            newNode=$(html).appendTo(loanListUl).find('.recordList');
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
            "url":'/speedloan/loanListApi',
            "method":'get',
            "data":{
                "page":curPage,
                "token":token
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
                    if (data.totalPage <= curPage) {
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

    triggerScheme('firstp2p://api?type=rightbtn&title=刷新&callback=refreshPage');
})
