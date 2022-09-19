//资金记录调用JS

;(function($) {
    $(function() {

    	 $(".JS_select_box").select();
    	 //回款计划日历JS
        $("#dateInput1").datepicker({
            onClose: function(selectedDate) {
                $("#dateInput2").datepicker("option", "minDate", selectedDate);
                var dateInput1V = $("#dateInput1").val();
                if(dateInput1V !=''){
                    $("#ui_select_box_list2").find("li").removeClass('user_selected_type');
                    $("input[name='lately']").val("");
                }
            }
       });
        $("#dateInput2").datepicker({
            onClose: function(selectedDate) {
                $("#dateInput1").datepicker("option", "maxDate", selectedDate);
                var dateInput2V = $("#dateInput2").val();
                if(dateInput2V !=''){
                    $("#ui_select_box_list2").find("li").removeClass('user_selected_type');
                    $("input[name='lately']").val("");
                }
            }
        });
        $('.j_tooltip_top').tooltip({
            position: {
                my: "left-80 top+10"
            }
        });

        $(".j_zj_date").each(function(index,data){
            var currentDate = $(this).data("time");
            var oldDate = parseInt(new Date("2014-03-01 00:00:00").getTime()/1000);
            var $p = $(this).closest('tr');
            if(currentDate < oldDate){
                $p.find(".j_zj_change").html("--");
            }
        });

        $(".list_more").click(function(){
            $("#ui_select_box_list").toggleClass('heightauto');
            $(".more_arrow").toggleClass('more_arrowT');
        });
        $("#ui_select_box_list li").click(function(){
            $(this).addClass('user_selected_type').siblings().removeClass('user_selected_type');
            $("input[name='log_info']").val($(this).attr("data-value"));
        });
        $("#ui_select_box_list2 li").click(function(){
            $(this).addClass('user_selected_type').siblings().removeClass('user_selected_type');
            $("#dateInput1").val("");
            $("#dateInput2").val("");
            $("input[name='lately']").val($(this).attr("data-value"));
        });
    })

})(jQuery);