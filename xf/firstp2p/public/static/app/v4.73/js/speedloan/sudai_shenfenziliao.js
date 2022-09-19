//获取图片URL地址
function getPhotoUrl(json){
    setPhotoUrl(json);
}
var setPhotoUrl=null;

$(function () {
    var submitBtn=$('#submitBtn');
    var photoArr=["frontPhoto","backPhoto","handHoldPhoto"];
    var photoBtn=$('#photoBtn');
    var photoPop=$('#photoPop');
    $('.mask',photoPop).add($('.photoPopUl li:eq(1)',photoPop)).on('click',function () {
       photoPop.hide();
    });
    var photoCont=$('#photoCont');
    photoCont.on('click','.photoDl',function () {
        photoPop.css('display',flexDisplay);
        photoBtn.data('photoKey',$(this).data('photoKey'));
    });
    photoBtn.on('click',function () {
        photoPop.hide();
    });
    //阅读同意协议
    var protocolBox=$('#protocolBox');
    protocolBox.on('click','p',function () {
        var checked=protocolBox.data('checked');
        protocolBox[checked?"removeClass":"addClass"]('checked');
        protocolBox.data('checked',!checked);
        checkValid();
    });

    //拍照功能
    function setVal(key,json) {
        $('#'+key+'Img').attr('src',json.url.replace(/https?:/,''));
        $('#'+key+'Hidden').val(json.image_id);
    }

    var imgMap=observeData({},function(){
        var obj={};
        photoArr.forEach(function(val){
            obj[val]={
                initVal:"",
                setCallBack:function (value,oldValue,key) {
                    setVal(key,value);
                    checkValid();
                }
            }
        });
        return obj;
    }());

    setPhotoUrl=function(json) {
        var curKey=photoBtn.data('photoKey');
        imgMap[curKey]=json;
    }
    
    function checkValid() {
        var flag=true;
        for (var i=0,max=photoArr.length;i<max;i++){
            if (emptyReg.test(imgMap[photoArr[i]])){
                flag=false;
                break;
            }
        }
        if (!protocolBox.data('checked')){
            flag=false;
        }
        submitBtn[flag?'removeClass':'addClass']('noValid');
    }
    submitBtn.on('click',function () {
        if ($(this).hasClass('noValid')){
            return;
        }else{
            $('#formTag').submit();
        }
    });

    /*photoBtn.data('photoKey','frontPhoto');
    getPhotoUrl('111');
    photoBtn.data('photoKey','backPhoto');
    getPhotoUrl('222');
    photoBtn.data('photoKey','handHoldPhoto');
    getPhotoUrl('333');*/
});