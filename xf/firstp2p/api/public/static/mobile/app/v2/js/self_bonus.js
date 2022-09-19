$(function() {
	var showFlag = false;
	self_wating();

	function self_wating() {
		if(showFlag) return;
		setTimeout(function() {
			$.ajax({
				type: "GET",
				url: 'url',
				data: {
					code: bonusCode
				},
				success: function(data) {
					data = $.parseJSON(data);
					if (data.state != 0) {
						showFlag = true;
						$('.ui_mask,.o2o_pop').show();
					} else {
						self_wating();
					}
				},
				error: function() {
					self_wating();
				}
			});
		}, 1000);
	};

	$('.o2o_pop .btn a').click(function(){
		var url = $(this).data('url');
		$.ajax({
			type: "GET",
			url: 'url' + url,
			data: {
				code: bonusCode
			},
			success: function(data) {
				data = $.parseJSON(data);
				if (data.state != 0) {
					$('.ui_mask,.o2o_pop').hide();
				} else {
					alert(data.msg);
				}
			},
			error: function() {
				alert('网络错误！');
			}
		});
	});
});