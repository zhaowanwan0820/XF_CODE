var emptyReg=/^\s*$/;
//toast提示
function showToast(tip) {
    var toastTip=$('#site_toastTip');
    if (toastTip.size()==0){
        toastTip=$('<div class="site_toastTip" id="site_toastTip"><div class="textTip"></div></div>').appendTo(document.body);
    }
    var textTip=toastTip.find('.textTip');
    textTip.text(tip);
    toastTip.show();
    setTimeout(function () {
        toastTip.hide();
    },1000);
}
function hideToast(){
    var toastTip=$('#site_toastTip');
    toastTip.hide();
}