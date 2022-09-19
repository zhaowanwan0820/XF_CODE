// 提交锁
function lock() {
	$('#register-btn').addClass('ui-btn-subing').attr('disabled', 'disabled');
}

// 提交解锁
function unlock() {
	$('#register-btn').removeClass('ui-btn-subing').removeAttr('disabled', 'disabled');
}

// 显示错误
function showError(cls, msg) {
	$('.no-' + cls).removeClass('msg-hide').addClass('msg-show').find('span.msg').html(msg);
}

// 隐藏错误
function hideError(cls) {
	$('.no-' + cls).removeClass('msg-show').addClass('msg-hide');
}

function tabAjax(url,idx){
	$.ajax({
		type: "post",
		url: '/hongbao/' + url,
		success: function(data) {
			if (data.length > 0) {
				var str = data;
				$('.tab_box .con').eq(idx).html(str).show().siblings().hide();
			}
		},
		error: function() {
			alert('请求错误！');
		}
	});
}

$(function() {
	// 图形验证码
	$('.form-Verification').click(function() {
		$(this).find('img').attr('src', '/verify.php?w=73&h=29&rb=0&rand=' + new Date().valueOf());
	});

	var url = $('.tabs_nav_box li').eq(0).data('url');
	if(!!url) tabAjax(url,0);

	// tab切换
	(function() {
		var curIdx = 0;
		$('.tabs_nav_box li').click(function(event) {
			var idx = $(this).index();
			var url = $(this).data('url');
			if (curIdx == idx) return;
			$(this).addClass('select').siblings().removeClass('select');
			curIdx = idx;
			if (typeof url != 'undefined') {
				tabAjax(url,idx);
			} else {
				$('.tab_box .con').eq(idx).show().siblings().hide();
			}
		});
	})();
});
