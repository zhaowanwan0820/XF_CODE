var validErrorArr=['登录密码长度必须为6~20位','登录密码不允许包含特殊符号','登录密码不允许包含空格','登录密码过于简单，试试数字、大小写字母、标点符号组合'];
;var checkPasStrenth=(function(){
    var regObj={//构造器静态属性
        'num':'[0-9]',
        'lowerLetter':'[a-z]',
        'upperLetter':'[A-Z]',
        'letter':'[a-zA-Z]',
        'symbol':'[`~!@#\$%\^&\*\(\)\\-_\+=<>\?:\"{},\.\/;\'\[\\]|\\\\]'
    }
    //纯前端合法性验证文案提示
    /**
     * 判断是否是弱密码,true表示是,false表示不是
     * @param  {String} pasStr 密码输入框内容
     * @returns {boolean}
     */
    function isRuo(pasStr){
        var regStr="";
        var returnVal=false;
        regStr='(^'+regObj.num+'+$)'+'|(^'+regObj.letter+'+$)'+'|(^'+regObj.symbol+'+$)';
        var newReg=new RegExp(regStr);
        if(pasStr.length<6){
            returnVal=true;
        }
        if(newReg.test(pasStr) && pasStr.length<9){
            returnVal=true;
        }
        return returnVal;
    }
    /**
     * 是否是强密码,true表示是,false表示不是
     * @param  {String} pasStr 密码输入框内容
     * @returns {boolean}
     */
    function isQi(pasStr){//是否是强密码
        var returnVal=false;
        var regArr=[regObj.num,regObj.lowerLetter,regObj.upperLetter,regObj.symbol];
        var validCount=0;
        for(var i= 0,max=regArr.length;i<max;i++){
            if(new RegExp(regArr[i]+'+').test(pasStr)){
                validCount++;
            }
        }
        if(validCount==4 && pasStr.length>=9 && pasStr.length<=20){
            returnVal=true;
        }
        if(validCount==3 && pasStr.length>=12 && pasStr.length<=20){
            returnVal=true;
        }
        return returnVal;
    }
    /**
     * 判断密码强度
     * @param  {String} pasStr 密码输入框内容
     * @returns {Number} 返回密码强度，0表示弱，1表示中，2表示强
     */
    function pasLevel(pasStr){
        if(isRuo(pasStr)){
            return 0;
        }
        if(isQi(pasStr)){
            return 2;
        }
        return 1;
    }
    /**
     * 密码是否合法，纯前端验证
     * ①6-20位
     * ②支持数字、大小写字母、标点符号
     * ③不允许有空格
     * ④密码和手机号不能相同
     * @param ｛String} pasStr
     * @returns {number} 0表示合法，返回数组表示不合法,1,2,3,4分别表示第一个，第二个，第三个错误,第四个错误
     */
    function pasValid(pasStr){
        var regStr="";
        var returnArr=[];
        var flag=true;
        //验证是不是有特殊字符的正则字符串
        regStr=function(){
            var regArr=[regObj.num,regObj.letter,regObj.symbol];
            var regStr="[^";
            for(var i= 0,max=regArr.length;i<max;i++){
                regStr=regStr+regArr[i].substr(1,regArr[i].length-2);
            }
            regStr=regStr+']';
            return regStr;
        }();
        if(pasStr.length<6||pasStr.length>20){
            returnArr.push(1);
            flag=false;
        }
        if(new RegExp(regStr).test(pasStr.replace(/\s*/g,''))){
            returnArr.push(2);
            flag=false;
        }
        if(/\s+/.test(pasStr)){
            returnArr.push(3);
            flag=false;
        }
        if(flag){
            return 0;
        }else{
            return returnArr;
        }
    }
    function checkPasStrenth(pasStr){
        var returnVal={
            'isValid':false,
            'textTip':""
        }
        var validResult=pasValid(pasStr);
        var strengthLevel=0;
        var strenthText="";
        if(validResult!=0){
            returnVal.textTip=validErrorArr[validResult[0]-1];
        }else{
            strengthLevel=pasLevel(pasStr);
            switch (strengthLevel){
                case 0:
                    strenthText="弱";
                    break;
                case 1:
                    strenthText="中";
                    break;
                case 2:
                    strenthText="高";
                    break;
                default :
                    strenthText="弱";
            }
            returnVal.isValid=true;
            returnVal.textTip='密码安全程度：'+strenthText;
        }
        return returnVal;
    }
    return checkPasStrenth;
})();