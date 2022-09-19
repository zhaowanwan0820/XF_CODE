/*document.onreadystatechange = subSomething;
function subSomething(){ 
    if(document.readyState == "complete"){
        document.getElementsByTagName("body")[0].removeChild(document.getElementById("JS-loading"));
    };
}
*/
window.onload = function() {
	document.getElementById("JS-loading").style.display = "none";
	//设置 pageScroll
	var ASslider = new Fullpage('JS-wraper');
	//tip
	var tip = document.getElementsByClassName("tip")[0];
	//屏幕控制
	function orient() {
		 //竖屏
	    if (window.orientation == 0 || window.orientation == 180) {
	    	document.getElementsByTagName('body')[0].id = '';
	        orientation = 'portrait';
	        return false;
	    }
	    //横屏
	    else if (window.orientation == 90 || window.orientation == -90) {
	    	document.getElementsByTagName('body')[0].id = 'JS-portrait';
	        orientation = 'landscape';
	        window.scrollTo(0,0);
	        return false;
	    }
	};
	/* 在页面加载的时候调用 */
	orient();
	/* 在用户变化屏幕显示方向的时候调用*/
	document.addEventListener('orientationchange', function(e){
	    orient();
	});
	//判断设备终端
	var browser={
		versions:function(){
		var u = navigator.userAgent, app = navigator.appVersion;
		return{
			ios: !!u.match(/(i[^;]+\;(U;)? CPU.+Mac OS X)/) //ios终端
		};
		}(),
		language:(navigator.browserLanguage || navigator.language).toLowerCase()
	};
	//判断IOS设备
	if(browser.versions.ios){
		document.getElementsByTagName('body')[0].className += 'JS-ios';
	};
	//微信
	var wxEl = document.getElementsByClassName('JS-equipment')[0];
	function weixinFacility(){
		var userAgentString = window.navigator ? window.navigator.userAgent : "";
    	var weixinreg = /MicroMessenger/i;
    	var androidreg = /android/i;
	    if (!weixinreg.test(userAgentString) ) {
	      return true;
	    }
	    var iosreg = /(iphone)|(ipod)/i;
	    
	    if (iosreg.test(userAgentString) || androidreg.test(userAgentString)){
			var addId = (androidreg.test(userAgentString) ? "tips-dowloand-android" : "tips-dowloand-ios");
			wxEl.id = addId;
	    }
	    return false;
	};

	var wxBtnItem = document.getElementsByClassName('int-down');
	var intDownIos = document.getElementsByClassName('int-down-ios');
	wxBtnItem = Array.prototype.slice.apply(wxBtnItem);
	intDownIos = Array.prototype.slice.apply(intDownIos);
	wxBtnItem.forEach(function(v){
		v.addEventListener('touchstart' , function(){
			if(!weixinFacility()) {
				tip.style.display = "block";
				return false;
			}
		});
	})
	intDownIos.forEach(function(v){
		v.addEventListener('touchstart' , function(){
			if(!weixinFacility()) {
				tip.style.display = "block";
				return false;
			}
		});
	})

	tip.addEventListener('touchstart' , function(){
		this.style.display = "none";
	});
	wxEl.addEventListener('touchstart', function(e){
		wxEl.id = '';
	});
	var back2top = document.getElementById("back2top");
	back2top.addEventListener('touchstart', function(e){
		ASslider._move(0);
	});
/*	
	第三屏 效果
	var fucus = function(obj , focusBox , prev , next){
		var focusWrapper = document.getElementsByClassName(obj)[0];
		var prevBtn = focusWrapper.getElementsByClassName(prev)[0];
		var nextBtn = focusWrapper.getElementsByClassName(next)[0];
		var focusBox = focusWrapper.getElementsByClassName(focusBox);
		var focusSize= focusBox.length;
		var focusIndex = 0;
		var focusAnimate = false;
		var indexFn = 0;
		//pren next function
		var prevNextSlidecon = function(prevBtn , nextBtn){
			//上一页按钮
			prevBtn.addEventListener('touchstart' , function(){
				if(indexFn < 2 && indexFn != 0){
					indexFn = focusSize -1;
				}else{
					if(indexFn == 0){
						indexFn = focusSize -2;
					}else{
						indexFn -= 2;
					}
				}
				showBox(indexFn);
			});
			nextBtn.addEventListener('touchstart' , function(){
				showBox(indexFn);
			});
		};
		prevNextSlidecon(prevBtn , nextBtn);
		//
		var showBox = function(focusIndex){
			if (focusAnimate) return;
			inAnimate = false;
			var othFocus = document.getElementById('JS-phont-select');
			if(othFocus){othFocus.id = ''; othFocus.style.cssText = "opacity:.4;z-index:1;";}	
			focusBox[focusIndex].id = 'JS-phont-select';
			focusBox[focusIndex].style.cssText = "opacity:1;z-index:2;";
			newIndex = focusIndex;
			indexFn++;
			if(indexFn == focusSize){indexFn = 0};
		};
		//
		var start = function(){
			picTimer = setInterval(function() {				
				
			},3E3);
		};
		showBox(0);
	};
	fucus('third-sc' , 'phone-sc' , 'phone-left-btn', 'phone-right-btn');	*/
};