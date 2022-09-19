;(function($) {
    $(function() {
    	var leftConArr = [],
    	winH = $(window).height();
    	$(".j-part").each(function(i , v){
    		leftConArr.push($(v).offset().top);
    	});
    	// console.log(leftConArr);
		$("#rightMenu li").click(function(evt){
			var $t = $(this),
			index = $t.parent().find("li").index($t),
			top = 0;
			if($(this).is("li")){
				$t.addClass('active').siblings().removeClass('active');
			}
			
			//console.log(index);
			if(!!index){
				top = $(".j-part:eq("+ (index) +")").offset().top;
			}else{
				top = 0;
			}
			//console.log(top);
			$(window).scrollTop(top);
		});
		
        var testPos = function(pos){
        	var index = 0,
        	len = leftConArr.length;
        	$.each(leftConArr , function(i , v){
        		var k = ((i+1) >= len ? i : i+1);
        		if(pos > v && pos <= leftConArr[k]){
        			index = k;
        		}
        	});


        	return index;
        };
        var showWin = function($obj){
        	$("#rightMenu li").eq(testPos($obj.scrollTop())).addClass('active').siblings().removeClass('active');
        };
        showWin($(window));
        $(window).bind("load scroll" , function(){
        	showWin($(this));
        	
        });



    });
})(jQuery);