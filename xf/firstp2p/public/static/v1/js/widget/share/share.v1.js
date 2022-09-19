;(function($) {
    $.extend({
        shareFunc:function(option){
               var settings = {
                        "sina": $(".sina a"),
                        "qq"    : $(".qq a"),
                        "renren" : $(".renren a"),
                        "qzone" : $(".qzone a")
                   };
               $.extend(true,settings, option || {});
                $.each(settings,function(index,value){
                    switch (index){
                       case "sina" :
                         share(value,"http://service.weibo.com/share/share.php?");
                         break;
                       case "qq":
                         share(value,"http://v.t.qq.com/share/share.php?");
                         break;
                        case "renren":
                         share(value,"http://widget.renren.com/dialog/share?");
                         break; 
                        case "qzone":
                         share(value,"http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?");
                         break; 
                        case "douban":
                         share(value,"http://www.douban.com/recommend/?");
                         break; 
                       
                    }
                       
                });
                function share(jqObj,sUrl){
                    if(!jqObj.length) return;
                    jqObj.on("click",function(){
                             
                             var $t = $(this),
                             title = $t.attr("title") || document.title ,
                             url =  $t.data("url")  || location.href,
                             pic =  $t.attr("pic") || "";
                             if(sUrl.indexOf("sns.qzone") > -1){
                                  window.open(sUrl + 'title=' + encodeURIComponent(title) +'&url='+ encodeURIComponent(url) +'&pics='+ (!!pic ? encodeURIComponent(pic) : ""),'_blank','scrollbars=no,width=600,height=450,left=75,top=20,status=no,resizable=yes');
                             }else if(sUrl.indexOf("www.douban.com") > -1){
                                 
                                 window.open(sUrl + 'url='+ encodeURIComponent(url) +'&title=' + encodeURIComponent(title)  + '&image='+ (!!pic ? encodeURIComponent(pic) : "") + '&sel=&v=1','_blank','scrollbars=no,width=600,height=450,left=75,top=20,status=no,resizable=yes');
                             }else{
                                 window.open(sUrl + 'title=' + encodeURIComponent(title) +'&url='+ encodeURIComponent(url) +'&pic='+ (!!pic ? encodeURIComponent(pic) : ""),'_blank','scrollbars=no,width=600,height=450,left=75,top=20,status=no,resizable=yes');
                             }
                            
                             return false;
                       });
                    
                }
            
      }    
        
    }) 
})(jQuery);
