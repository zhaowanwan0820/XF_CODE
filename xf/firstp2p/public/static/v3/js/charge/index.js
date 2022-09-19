//firstp2p充值调用JS

;
(function($) {
    $(function() {
        //选中银行
        var showCheckbox = function() {
            $("#chargeSelect").on("click", "li:not('.nobor')", function() {
                var $t = $(this);
                if (!$t.hasClass("select")) {
                	getauxiliary($t.attr('id'));
                    $("#chargeSelect li").removeClass("select");
                    $t.addClass("select");
                    $t.find("input[type='radio']").prop("checked", "checked");
                }

            });
        };
        showCheckbox();
        $('.JS_select_box').select();
        //更多银行下拉框
        $('.j_showBank').click(function() {
            $(this).hide();
            $('.j_hideBank').show();
            $('.bank_more_li').toggle()
        });
        $('.j_hideBank').click(function() {
            $(this).hide();
            $('.bank_more_li').hide()
            $('.j_showBank').show()
        });
		if($(".offlineChargeOpen").val() == 0){
			$('.JS_big_money_url').attr('href',location.origin + '/payment/transit?srv=offlineCharge&return_url=' + encodeURIComponent(location.href));
		}else if($(".offlineChargeOpen").val() == 1){//开关打开,显示新的大额充值
			$('.JS_big_money_url').attr('href',location.origin + '/account/chargeOfflineWX');
		} 
        

        //充值表单验证
        $('#incharge_done').removeAttr('disabled');
        $("#chargeForm").valid({
        	onValidationComplete : function($form , status){
        		if (status) {
					$('.user_button').css({"background-color": "#ccc", "cursor": "default"});
					$('.user_button').attr('disabled',true);
					//需要添加return true否则不提交
					return true;
        		}
        	}
        });
        $("#chargeFormCg").valid({
        	onValidationComplete : function($form , status){
        		if (status) {
					$('.user_button').css({"background-color": "#ccc", "cursor": "default"});
					$('.user_button').attr('disabled',true);
					//需要添加return true否则不提交
					Firstp2p.supervision.wancheng();
					return true;
        		}
        	}
        });
      var $payTypeSelect = $('.select_paytype');
        if ($payTypeSelect.find('.select_ul li').length <= 1) {
            $payTypeSelect.addClass('single_paytype');
        }
    })

})(jQuery);

//获取银行附属额度信息
function getauxiliary(id) {
	if(id){
		$.ajax({
			   type: "POST",
			   url: '/uc_money-getBankAuxiliary',
			   data: "charge_id="+id,
			   dataType:"json",
			   success: function(data){
				   if(data.code == '0000') {
					   var htmlContent = '';
					   $.each(data.message.list,function(index,element){
						   var td_name = '';
						   if(index == 0){
							    td_name = '<td rowspan="'+data.message.total+'">'+data.message.name+'</td>';
						   }
						   htmlContent += '\
							   <tr>\
							   '+td_name+'\
		                       <td>'+element.category+'</td>\
		                       <td>'+element.card_type+'</td>\
		                       <td>'+element.one_money+'</td>\
		                       <td>'+element.date_norm+'</td>\
			                   </tr>';
					   });
					   $('#payment_auxiliary').html(htmlContent);
				   }
			   }
		});
	}
}
