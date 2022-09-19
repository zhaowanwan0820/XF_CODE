
//判断是否在微信打开
var p2pBrowser = (function () {
var u = navigator.userAgent
return {
  app: /wx/i.test(u),
  androidApp: /wxAndroid/i.test(u),
  iosApp: /wxiOS/i.test(u)
}
})()
$(function(){
  $(".JS_useDayLimit").each(function() {
    var useDayLimit = $(this).html() * 1;
    useDayLimit = (useDayLimit / 86400);
    $(this).html(parseInt(useDayLimit));
  })
})