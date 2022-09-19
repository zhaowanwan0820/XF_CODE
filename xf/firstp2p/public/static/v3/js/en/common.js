function IsPC() {
  var flag = true;
  var winW = document.documentElement.clientWidth
  if(winW < 1200) flag = false;
  return flag;
}
var flag = IsPC();
$(window).resize(function(){
  var winW = document.documentElement.clientWidth
  if(winW < 1200){
    var winW = document.documentElement.clientWidth
    document.documentElement.style.fontSize = winW / 375 * 100 + "px"
  }else{
    var winW = document.documentElement.clientWidth
    document.documentElement.style.fontSize = winW / 1920 * 100 + "px"
    $(".show_more").on("mouseenter", function() {
        $(this).children(".member").fadeIn(400)
    }), $(".nav-item").on("mouseleave", function() {
        $(this).children(".member").fadeOut(400)
    })
    var li2_member = ($(".li2").width() - $(".li2 .member").width()) / 2
    li2_member = Math.abs(li2_member);
      $(".li2 .member").css("left",-li2_member);
    }
    flag = IsPC();
})

if(flag){//pc
    var winW = document.documentElement.clientWidth
    document.documentElement.style.fontSize = winW / 1920 * 100 + "px"
    $(".show_more").on("mouseenter", function() {
        $(this).children(".member").fadeIn(400)
    }), $(".nav-item").on("mouseleave", function() {
        $(this).children(".member").fadeOut(400)
    })
    var li2_member = ($(".li2").width() - $(".li2 .member").width()) / 2
    li2_member = Math.abs(li2_member);
    $(".li2 .member").css("left",-li2_member);
}else{
    var winW = document.documentElement.clientWidth
    document.documentElement.style.fontSize = winW / 375 * 100 + "px"
}
var wHeight=$(window).height()
$(".clickMask").height(wHeight);
$(".nav_btn").click(function(){
    $(".nav_ul_h5").toggleClass("nav_ul_h5_close");
    $(".nav_h5").toggleClass("nav_btn_open");
    $(".clickMask").toggleClass("show");
})
$(".clickMask").click(function () {
    $(".nav_ul_h5").removeClass("nav_ul_h5_close");
    $(".nav_h5").removeClass("nav_btn_open");
    $(this).removeClass("show");
    $(".nav_ul_h5 li i").removeClass("active");
    $(".nav_ul_h5 li .member_h5").addClass("disnone");
})
$(".nav_ul_h5 .show_more").on("click", function() {
    $(this).find('i').toggleClass("active");
    $(this).children(".member_h5").toggleClass("disnone");
    $(this).siblings('.show_more').children(".member_h5").addClass("disnone");
})




var pathname = location.pathname
if(pathname == '/companyProfile' || pathname == '/financeProcess' || pathname == '/historyTimeline' || pathname == '/honor' || pathname == '/socialResponse'){
    pathname = '/aboutNcf'
}
if(pathname == '/coreBusiness' || pathname == '/riskControl' || pathname == '/competitiveAdvantages'){
    pathname = '/core'
}
if(pathname == '/articleDetail'){
    pathname = '/mediaCoverage'
}
$(".nav-item").each(function(){
    if($(this).data('url') == pathname){
        $(this).addClass('active').siblings().removeClass('active')
    }
})
var html_height = document.documentElement?document.documentElement.offsetHeight:document.body.offsetHeight
var window_height = window.innerHeight
if(html_height < window_height){
    $(".slide6").addClass('full_screen');
}

;(function($) {
    $(function() {
        var wid = $(this).width();
        if (wid>480){
            $backToTopEle = $('<a class="backToTop" href="javascript:void(0)"></a>').appendTo($(".p_ensite")).click(function () {
                $("html, body").animate({
                        scrollTop: 0
                    },
                    500);
                $(".slide_title_pc").removeClass("an-end");
                $(".float_ul_pc").removeClass("an-end");
            }).hide();

            $(window).bind("scroll resize",
                function () {
                    var st = $(document).scrollTop(),
                        winh = $(window).height();
                    (st > 0) ? $backToTopEle.show(): $backToTopEle.hide();
                });
        }
    })
})(jQuery);;