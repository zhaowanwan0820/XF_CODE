var app_close_expires = 3;
if(localStorage.getItem('app_close')){
    var timeGap = (new Date()).getTime() - localStorage.getItem('app_close_timestamp');
    if(timeGap > app_close_expires*3600*24*1000){
        document.querySelector('.m-head').classList.remove('down_app_none');
        localStorage.setItem('app_close',false);
    }else{
        document.querySelector('.m-head').classList.add('down_app_none');
    }
}
document.querySelector('.head_app .close').onclick = function(e) {
    document.querySelector('.m-head').classList.add('down_app_none');
    localStorage.setItem('app_close',true);
    localStorage.setItem('app_close_timestamp',(new Date()).getTime());
};