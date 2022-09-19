$(function() {
	var flagObj = {
		0: false,
		1: false,
		2: false
	};

	load_more(0,$('.tab_box .tab_con').eq(0));

	$('.tab_header li').click(function() {
		var idx = $(this).index();
		var $con = $('.tab_box .tab_con').eq(idx);
		$(this).addClass('active').siblings().removeClass('active');
		$con.show().siblings().hide();

		if (flagObj[idx]) return;

		load_more(idx, $con);
	});

	function load_more(idx, $el) {
		var idex = idx;
		var $ele = $el;
		$.ajax({
			type: "GET",
			url: 'url.php',
			data: {
				cate: idx
			},
			success: function(data) {
				data = $.parseJSON(data);
				if (data.state != 0) {
					if (data.data.length != 0) {
						flagObj[idx] = true;
						$el.find('.tab_bd').append((function() {
							var html = '';
							for (var i = 0; i < data.data.length; i++) {
								html += '';
							}
							return html;
						})());
						if(data.data.length < 10){
							$el.find('.tab_more').addClass('no_more').html('没有更多了').unbind('click');
						}else{
							$el.find('.tab_more').removeClass('no_more').html('点击加载更多').unbind('click').bind('click', function(){
								load_more(idx, $el)	
							});
						}
					} else {
						$el.find('.tab_more').addClass('no_more').html('没有更多了').unbind('click');
					}
				} else {
					alert(data.msg);
				}
				// flagObj[idx] = true;
				// $el.find('.tab_bd').append(idx);
				// $el.find('.tab_more').removeClass('no_more').html('点击加载更多').unbind('click').bind('click', function(){
				// 	load_more(idx, $el);	
				// });
			}
		});
	}
});