;(function($) {
    $(function() {

        //顶部导航下拉菜单
        $('.j_showMenu').hover(function() {
            $(this).find('ul').stop().toggle();
        });

        

       (function(){
            //临时任务，去掉满标、流标、还款中a标签链接
            var  removeHref = function($tbody){
                 if(typeof(status_switch) == 'undefined' || !status_switch){
                     return;
                 } 
                 $tbody.find("tr").each(function(){
                     var $t = $(this),
                     text = $.trim($t.find(".table_cell>a").text());
                     if( encodeURI(text) == "%E6%BB%A1%E6%A0%87" || encodeURI(text) == "%E8%BF%98%E6%AC%BE%E4%B8%AD" || encodeURI(text) == "%E6%B5%81%E6%A0%87"){
                           $t.find("a").attr({
                                "href" : "###" 
                           }).removeAttr("target");
                     }   
                 });
            };
            //首页、列表页调用(非ajax)
            removeHref($(".tabContent table"));

            try{
                
                //首页焦点图调用
                (function() {
                    if (typeof aImg == "undefined" || typeof aHref == "undefined" || aImg.length == 1 || aHref.length == 1) {
                        $(".slide .rightBt,.slide .leftBt").hide();
                        return;
                    }
                    zns.site.fx.index_ppt.create(aImg, aHref);
                })();

                //公共底部lazyload调用
                $("img").lazyload({
                    effect: "fadeIn"
                });


                
                //添加的邮箱表单验证|
                $("#formValid").valid();
                
                 //回款计划、投资的项目 下拉框JS调用
                $(".select_box").select();

                //回款计划日历JS
                $("#dateInput1").datepicker({
                    onClose: function(selectedDate) {
                        $("#dateInput2").datepicker("option", "minDate", selectedDate);
                    }
               });
                $("#dateInput2").datepicker({
                    onClose: function(selectedDate) {
                        $("#dateInput1").datepicker("option", "maxDate", selectedDate);
                    }
                });

                //投资的项目tab切换
                $(".j_table_tab").goodTab();

                
                


            }catch(e){


            }
            
            

       })();

        

    });



    $(function() {
        $('.j_por_Show').click(function() {
            var nIndex = $('.j_por_Show').index(this);
            $(this).hide();
            $('.j_por_Hide').eq(nIndex).show();
            $('.pro_detailed').eq(nIndex).slideDown();
        });
        $('.j_por_Hide').click(function() {
            var cIndex = $('.j_por_Hide').index(this);
            $(this).hide();
            $('.j_por_Show').eq(cIndex).show();
            $('.pro_detailed').eq(cIndex).slideUp();
        });
    })




//首页列表页tab

})(jQuery);