
$(function () {

    var submitBtn=$('#submitBtn');
    var photoArr=["frontPhoto","backPhoto","handHoldPhoto"];
    var photoBtn=$('#photoBtn');
    var photoPop=$('#photoPop').hide();
    var fileInputs=$('#frontPhotoHidden,#backPhotoHidden,#handHoldPhotoHidden');

    $('.mask',photoPop).add($('.photoPopUl li:eq(1)',photoPop)).on('click',function () {
        photoPop.hide();
    });
    var photoCont=$('#photoCont');
    photoCont.on('click','.photoDl',function () {
        var curKey=$(this).data('photoKey');
        photoPop.css('display',flexDisplay);
        fileInputs.hide();
        $('#'+curKey+'Hidden').show();
    });
    photoBtn.on('click',function () {
        photoPop.hide();
    });
    fileInputs.on('change',function () {
        updateImg.apply(this,[]);
    });

    //阅读同意协议
    var protocolBox=$('#protocolBox');
    protocolBox.on('click','p',function () {
        var checked=protocolBox.data('checked');
        protocolBox[checked?"removeClass":"addClass"]('checked');
        protocolBox.data('checked',!checked);
        checkValid();
    });

    var imgMap=observeData({},function(){
        var obj={};
        photoArr.forEach(function(val){
            obj[val]={
                initVal:"",
                setCallBack:function (value) {
                    checkValid();
                }
            }
        });
        return obj;
    }());

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
        // console.log('frontPhotoHidden',$('#frontPhotoHidden').val());
        // console.log('backPhotoHidden',$('#backPhotoHidden').val());
        // console.log('handHoldPhotoHidden',$('#handHoldPhotoHidden').val());
        if ($(this).hasClass('noValid')){
            return;
        }else{
            $('#formTag').submit();
        }
    });

    function setPhotoUrl() {
        var curKey=$(this).attr('name');
        imgMap[curKey]=$(this).val();
    }
    function updateImg(){
        setPhotoUrl.apply(this,[]);
        previewPhoto.apply(this,[]);
    }
    function previewPhoto(){
        var file = this.files[0];
        var reader = new FileReader();
        var curKey=$(this).attr('name');
        reader.onload  = function () {
            $('#'+curKey+'Img').attr('src',this.result);
        };
        reader.readAsDataURL(file);
    }
});