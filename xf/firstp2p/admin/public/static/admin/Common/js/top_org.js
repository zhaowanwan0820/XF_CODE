$(document).ready(function(){
        //绑定菜单按钮
        $("#orgnavs").find("li a").bind("click",function(){
            $("#orgnavs").find("li a").removeClass("current");
            parent.menu.location.href = $(this).attr("href");
            $(this).addClass("current");
            return false;
            });
        $("#orgnavs").find("li a").bind("focus",function(){$(this).blur();});
        $("#orgnavs").find("li a").first().click();
    });
