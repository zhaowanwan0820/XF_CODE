$(function () {
    var phone, captcha, token_id, token, cn,site_id;
	var validator = new FormValidator('register_form', [{
        name: 'mobile',
        rules: 'required|phone'
    }, {
        name: 'captcha',
        rules: 'required|captcha'
    }], function(errors, evt) {
    	var el = evt.target;
        var name = el.getAttribute("name");
        if (typeof validatorFn[name] == "function") {
            return validatorFn[name](el,errors);
        }
    });

	var validatorFn = {
		'register_form': function(el,errors) {
            if(errors.length > 0) return;

			 phone = $('#mobile').val();
    		 captcha = $('#captcha').val();
    		 token_id = $("#token_id").val();
    		 token = $("#token").val();
             cn = $('#cn').val();
             site_id = $('#site_id').val();
    		hideError('form');
    		lock();
    		$.ajax({
    		    type: "GET",
    		    url: "/hongbao/CheckMobile",
    		    data: { mobile: phone, fromPlatform: "xslb3.1", cn: $("#cn").val() },
    		    dataType: "json",
    		    async: false,
    		    success: function (data) {
		            if (data.errCode == "4002") {
		                $(".ts_money").html(data.data.replaceMoney + "元");
		                $(".ts_date").html(data.data.replaceDate);
		                $(".ts_name").html(data.data.replaceUser);
		                $(".ts_title").html(data.data.replaceName);
		                $(".xs_money").html(data.data.activityMoney + "元");
		                $(".xs_date").html(data.data.activityDate);
		                $(".xs_name").html(data.data.activityUser);
		                $(".xs_title").html(data.data.activityName);
		                $(".ui_mask").show();
		            } else {
		                getAjax("0");
		            }
		        },
    		    error: function () {
    		        alert('请求错误！');
    		    }
    		});
		}
	};

	$(".btn-cancel").bind("click", function () {
	    $(".ui_mask").hide();
	    unlock();
	});
	$(".btn-ok").bind("click", function () {
	    $(".ui_mask").hide();
	    getAjax("1");
	});

    function getAjax(state) {
        $.ajax({
            type: "post",
            data: {
                mobile: phone,
                captcha: captcha,
                token: token,
                token_id: token_id
            },
            dataType: "json",
            url: '/user/DoH5RegStepOne',
            success: function (data) {
                unlock();
                if (data.errorCode != '0') {
                    $('.ui-all-error').html(data.errorMsg).show();
                } else {
                    window.location.href = "/hongbao/CashGet?cn=" + cn + "&site_id=" + site_id + "&replace="+state;
                }
            },
            error: function () {
                alert('请求错误！');
            }
        });
    }

});
