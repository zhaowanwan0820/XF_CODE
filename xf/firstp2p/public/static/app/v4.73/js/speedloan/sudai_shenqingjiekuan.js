var submitFn=null;
$(function(){
    var token=$('#tokenHidden').val();//token值
    var submitBtn=$('#submitBtn');
    var codeInput=$('#codeInput');
    var phone=$('#phoneHidden').val();

    function updateBtnStatus(moneyValid,codeValid) {
        var flag=moneyValid && codeValid;
        if (flag){
            submitBtn.removeClass('noValid');
            submitBtn.data('linkHref','firstp2p://api?type=transactionpwd&callback=submitFn');
        }else{
            submitBtn.addClass('noValid');
            submitBtn.data('linkHref','javascript:;');
        }
    }

    submitBtn.on('click', function () {
        if (!submitBtn.hasClass('noValid') && !submitBtn.hasClass('pending')) {
            triggerScheme(submitBtn.data('linkHref'));
        }
    });
    
    var submitBtnStatus=observeData({},{
        'moneyValid':{
            "initVal":false,
            "setCallBack":function (value,oldValue) {
                if (value==oldValue){
                    return;
                }
                updateBtnStatus(value,this.codeValid);
            }
        },
        "codeValid":{
            "initVal":false,
            "setCallBack":function (value,oldValue) {
                if (value==oldValue){
                    return;
                }
                updateBtnStatus(this.moneyValid,value);
            }
        }
    });

    //获取数值单位
    function getNumUnit(num) {
        var unitArr=['个', '十', '百', '千', '万', '十万', '百万', '千万','亿','十亿','百亿','千亿','兆'];
        if(!isNum(num)){//如果不是数字，立即返回
            return {
                isValid:false,
                arr:[]
            }
        }
        var len=parseInt(num).toString().length;
        return {
            isValid:true,
            arr:unitArr.slice(0,len).reverse()
        }
    }
//    更新数值单位
    function updateNumUnit(num) {
        var showUnitBox=$('#show_unit');
        showUnitBox.empty();
        var unitArr=getNumUnit(num).arr;
        var ulHtml="";
        unitArr.forEach(function (unit) {
            ulHtml+='<li>'+unit+'</li>';
        });
        showUnitBox.html(ulHtml);
    }
    function updateInputTip(text,className) {
        var tipSpan=$('#input_tip span').removeClass();
        tipSpan.text(text);
        if(typeof className != "undefined"){
            tipSpan.addClass(className);
        }
    }
    var checkNumRange=function checkNumRange() {
        var limitMin=$('#limitMinHidden').val();
        var limitMax=$('#limitMaxHidden').val();
        var usableAmount=$('#usableAmountHidden').val();
        limitMin=Number(limitMin);
        limitMax=Number(limitMax);
        usableAmount=Number(usableAmount);
        var checkRes={
            'valid':true,
            "tip":''
        }
        function setCheckRes(obj) {
            checkRes.valid=obj.valid;
            checkRes.tip = obj.tip;
            return checkRes;
        }
        var resetCheckRes=setCheckRes.bind(null,{
            'valid':true,
            "tip":''
        });
        function checkMin(num) {
            num=Number(num);
            resetCheckRes();
            if (num<limitMin){
                setCheckRes({
                    'valid':false,
                    "tip":"单笔借款金额最低 "+limitMin+" 元"
                });
            }
            this.checkHook && this.checkHook(checkRes);
            return checkRes;
        }
        function checkUsableAmount(num) {
            num=Number(num);
            resetCheckRes();
            if (num>usableAmount){
                setCheckRes({
                    'valid':false,
                    "tip":"超过可用额度"
                });
            }
            this.checkHook && this.checkHook(checkRes);
            return checkRes;
        }
        function checkMax(num) {
            num=Number(num);
            resetCheckRes();
            if (num>limitMax){
                setCheckRes({
                    'valid':false,
                    "tip":"单笔最高可借 "+limitMax+" 元"
                });
            }
            this.checkHook && this.checkHook(checkRes);
            return checkRes;
        }
        function checkFormat(num) {
            num=Number(num);
            resetCheckRes();
            if (num % 100 != 0) {
                setCheckRes({
                    'valid':false,
                    "tip":"借款金额须为 100 的整数倍"
                });
            }
            this.checkHook && this.checkHook(checkRes);
            return checkRes;
        }
        function checkAll(num) {
            num=Number(num);
            var ruleArr=['checkMin','checkUsableAmount','checkMax','checkFormat'];
            var checkRes=null;this.checkMin(num);
            for (var i=0;i<ruleArr.length;i++){
                checkRes=this[ruleArr[i]](num);
                if (!checkRes.valid){
                    return checkRes;
                }
            }
            return checkRes;
        }
        return {
            checkMin:checkMin,
            checkMax:checkMax,
            checkUsableAmount:checkUsableAmount,
            checkFormat:checkFormat,
            checkAll:checkAll
        }
    }();
    checkNumRange.checkHook=function (checkRes) {
        submitBtnStatus.moneyValid=checkRes.valid;
    };
    function keyBoard_checkRange(num) {
        var checkRes=checkNumRange.checkAll(num);
        if (!checkRes.valid && !emptyReg.test(num)){
            updateInputTip(checkRes.tip,'error');
        }else{
            updateInputTip('');
        }
    }
    var keyBoard=new NumkeyBoard();
    keyBoard.on('keyBoard:set',function (instObj,num) {
            NumkeyInput.focus();
        NumkeyInput.setVal(num);
        updateNumUnit(num);
        keyBoard_checkRange(num);
    });
    keyBoard.on('keyBoard:hide',function (instObj,num) {
        NumkeyInput.blur();
        keyBoard_checkRange(num);
    });
    keyBoard.on('keyBoard:output',function(instObj,curNum){
        var inputText=NumkeyInput.valText+curNum;
        var limitMax=$('#limitMaxHidden').val();
        var limitLen=Math.max(6,String(parseInt(limitMax)).length+1);
        var regObj=new RegExp('^(0|[^0.]\\d{0,'+(limitLen-1)+'})(\\.\\d{0,2})?$');
        if (!regObj.test(inputText)){
            return "";
        }
        return curNum;
    });

    var NumkeyInput=function () {
        var ui_input=$('#ui_input');
        var inputVal=$('.inputVal',ui_input);
        var inputCursor=$('.inputCursor',ui_input);
        var inputUnitText=$('.inputUnitText',ui_input);
        var inputPlaceHolder=$('.inputPlaceHolder',ui_input);
        var valText="";
        ui_input.on('click',function () {
            NumkeyInput.focus();
            keyBoard.show();
        });
        function hideAll() {
            inputVal.add(inputUnitText).add(inputPlaceHolder).hide();
            inputCursor.css({
                "visibility":'hidden'
            })
        }
        function updateDom(isWriting,hasVal) {
            var isWriting=typeof isWriting!="undefined"?isWriting:statusMap.isWriting;
            var hasVal=typeof hasVal!="undefined"?hasVal:statusMap.hasVal;
            hideAll();
            if (isWriting){
                inputVal.show();
                inputCursor.css({
                    "visibility":'visible'
                });
                if (hasVal==true){
                    inputUnitText.show();
                }
            }else{
                if (hasVal==true){
                    inputVal.add(inputUnitText).show();
                }else{
                    inputPlaceHolder.show();
                }
            }
        }
        var statusMap=observeData({},{
            hasVal:{
                initVal:false,
                set:function (value,oldValue) {
                    updateDom(undefined,value);
                }
            },
            isWriting:{
                initVal:false,
                set:function (value,oldValue) {
                    updateDom(value,undefined);
                }
            }
        });
        return{
            blur:function () {
                statusMap.isWriting=false;
            },
            focus:function () {
                statusMap.isWriting=true;
            },
            setVal:function (val) {
                inputVal.text(val);
                this.valText=val;
                if (emptyReg.test(val)){
                    statusMap.hasVal=false;
                }else{
                    statusMap.hasVal=true;
                }
            },
            initPH:function(text){
                inputPlaceHolder.text(text);
            },
            statusMap:statusMap,
            valText:valText,
        }
    }();

    /*keyBoard.initValText('1000.00',function (num) {
        NumkeyInput.setVal(num);
        updateNumUnit(num);
        keyBoard_checkRange(num);
    },['1000.00']);
    NumkeyInput.initPH('嘿嘿');*/

//    获取验证码
    new VerifyCode({
        dom:$('#codeBtn'),
        startCallBack:function () {
            var _this=this;
            $.ajax({
                "url":'/user/sendVerifyCode',
                "method":'post',
                "data":{
                    "type":16,
                    'token':token,
                    "phone":phone
                },
                "success":function (returnVal) {
                    if(returnVal.errno!=0){
                        new ToastPop({
                            "content":returnVal.error,
                            "clickHide":true,
                            "delayHideTime":2500
                        });
                        _this.reset();
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
        }
    });

    codeInput.on('input',function () {
        if (emptyReg.test($(this).val())){
            submitBtnStatus.codeValid=false;
        }else{
            submitBtnStatus.codeValid=true;
        }
    });

    submitFn=function(dealPasRes) {
        if( dealPasRes.errorCode!=0 ){
            new ToastPop({
                "content":dealPasRes.errorMsg,
                "clickHide":true,
                "delayHideTime":2500
            })
        }else{
            lockBtn(true);
            $.ajax({
                "url":'/speedloan/loanConfirm',
                "method":'post',
                "data":{
                    "loanAmt":keyBoard.getVal(),
                    "verifyCode":codeInput.val(),
                    'token':token,
                },
                "success":function (resultVal) {
                    var data=resultVal.data;
                    if (resultVal.errno!=0){
                        new ToastPop({
                            "content":resultVal.error,
                            "clickHide":true,
                            "delayHideTime":2500
                        })
                    }else{
                        location.assign('/speedloan/loanResult?token='+data.token+'&orderId='+data.orderId)
                    }
                    lockBtn(false);
                },
                "error":function () {
                    new ToastPop({
                        "content":'服务器端异常，请稍后重试',
                        "clickHide":true,
                        "delayHideTime":2500
                    });
                    lockBtn(false);
                }
            });
        }
    }
    function lockBtn(flag) {
        if(flag){
            submitBtn.addClass('pending').text('请求发送中……');
        }else{
            submitBtn.removeClass('pending').text('申请借款');
        }
    }
});