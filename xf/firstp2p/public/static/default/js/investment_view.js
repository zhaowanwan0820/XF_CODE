// JavaScript Document
$(function(){
	$('.more_zk a').click(function(){
		$('.more_con').slideToggle(500);
		$(this).toggleClass('on');
		})
	})
	
//合同展开收起	
$(function(){
	$('.more_hetong a').click(function(){
		var nIndex=$('.more_hetong a').index(this)
		$('.hetong').eq(nIndex).slideToggle(500);
		$('.more_hetong').eq(nIndex).hide()
		})
	$('.but_shouqi').click(function(){
		var sIndex=$('.but_shouqi').index(this)
		$('.hetong').eq(sIndex).slideToggle();
		$('.more_hetong').eq(sIndex).show();
		})
	})
	