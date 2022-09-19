$(function () {
    var token=$('#tokenHidden').val();
    $('#onlineRepay').on('click',function () {
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