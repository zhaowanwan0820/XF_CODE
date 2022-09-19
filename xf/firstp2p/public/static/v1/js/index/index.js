//firstp2p首页调用JS
;(function($) {
    $(function() {
        

        //首页银行列表调用
        $("#scroll").scrollView({
            autoScroll: false,
            scrollNum: 6
        }).find("img").lazyload({
            effect: "fadeIn"
        });

       
        //首页tab切换
        (function(){
              var obj = {};
              $('#index_list_tab').goodTab({
                  cur : "active" ,
                  tabLab : ".j_index_tab" ,
                  clickEvent : function($t , index){
                          var $tbody = $('#index_list_tab').find(".tabContent:eq("+ index +") .j_index_tbody"),
                          id = $t.data("id");
                          !!$("img").data("update") && $("img").data("update")();
                          if(id === 0){
                                return;
                          }
                          if(!obj[id]){
                                $.ajax({
                                    url: '/index/cate',
                                    dataType: 'text',
                                    data: {
                                        cate: id
                                    },
                                    beforeSend: function() {
                                        $tbody.html('<tr>\
                                            <td style="width:100%;">\
                                            <div class="loading_bg"></div>\
                                            </td>\
                                             </tr>');
                                    },
                                    success: function(data) {
                                        $tbody.html(data);
                                         obj[id] = 1;
                                          //首页调用(ajax)
                                         //removeHref($tbody);
                                    }
                                });
                          }
                  }
              });
        })();
        
        

        $("#scroll>.scroll_up , #scroll>.scroll_down").click(function(){
               $(this).parent().find("img").trigger("appear");
        });

        $("#scroll").on("mouseover" , "li" ,function(){
              $(this).find("img").trigger("appear");
        });


        
    })
})(jQuery);