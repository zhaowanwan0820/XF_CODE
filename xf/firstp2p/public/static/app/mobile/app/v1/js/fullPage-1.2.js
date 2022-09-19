/**
 * fullPage 1.1
 * https://www.crowdlite.cn
 * zhangxiaoyang@ucfgroup.com
 * Copyright (C) 2014 crowdlite.cn - A project by SNB team
 */
;(function(window,undefined){
  /**
   * fullPage
   * @name    FullPage
   * @param   {String}     滚动内容ID
   * @param   {Object}     配置选项 
   *			index      {0 、number}起始位置
   *			autoPlay   {true 、 false}自动播放
   *			showDot    {true 、 false}展示页码
   *			slideEnd   ---
  */	
	 var fullpage = function(id, opt) {
	 	var wrapper = this;
 		opt = opt || {};	 		
 		wrapper.data = {
 			dom: document.getElementById(id.replace(/^#/,'')),
 			index: opt.index || 0,  //初始位置
 			autoPlay: opt.autoPlay || false,  //自动播放
 			showDot: opt.showDot !== undefined ? opt.showDot : true,  //展示页码
 			slideEnd: opt.slideEnd || null,		//滑动完成执行的函数
 			_inPlay: false,  //动画过程标记
 			_direction: 1  //方向标记
 		};
 		//获取页面的超链接 方便定位到指定page
 		var value =  window.location.hash.replace('#', '').split('/');
		var section = value[0];
		//var anchor = section ? section.replace(/[^0-9]/ig,"") : 1;
		var anchor = 1;
		if(this.data.index == 0){this.data.index = anchor-1;}
 		wrapper.init();
 		wrapper.initEvent();
 		wrapper.start();
 		//添加时间 滚动到下一屏
 		document.getElementById('JS-scroll-to').addEventListener('mouseup' ,function (){
 			wrapper._move(1);
 		});
 		document.getElementById('JS-scroll-to').addEventListener('touchend' ,function (){
 			wrapper._move(1);
 		});

		var back2top = document.getElementById("back2top");
		back2top.addEventListener('touchend', function(e){
			wrapper._move(0);
		});


/* 		document.getElementsByClassName('btn-get-linsk')[0].addEventListener('mouseup' , function (){
 			wrapper._move(0);
 		});
 		document.getElementsByClassName('btn-get-linsk')[0].addEventListener('touchend' , function (){
 			wrapper._move(0);
 		});
*/

	 };
 	fullpage.prototype = {
 		constructor: fullpage,
 		//默认参数配置
 		init: function(){
 			var wrapper = this,
 				o = wrapper.data;
 			var width = o.dom.offsetWidth,
 				height = o.dom.offsetHeight,
 				items = o.dom.children,
 				wheel = document.createElement('div'), //创建动画执行box
 				dotContainer = wheel.cloneNode(), //创建screen选择box
 				mark = document.createElement('span'),
 				i = 0, j, len = items.length; 				
 			for(; j = items[0]; i++){
 				j.style.cssText += 'height:'+ height + 'px;position:absolute;-webkit-transform:translate3d(0,' + i * height + 'px,0);z-index:' + (60 - i);
 				wheel.appendChild(j); 
 				dotContainer.appendChild(mark.cloneNode()); 				
 			}
 			wheel.style.height = height * len + 'px';
 			wheel.style.width = width + 'px';
 			dotContainer.className = 'int-sc-mark';
 			o.showDot || (dotContainer.style.display = 'none');
 			o.dom.appendChild(wheel);
 			o.dom.appendChild(dotContainer);
 			o.wheel = wheel;
 			o.items = wheel.children;
 			o.length = o.items.length;
 			//设置自定义连接情况下 页面不超过总页面数量
 			if(o.index > o.length -1){o.index = 0}
 			o.intSelect = dotContainer.children;
 			o.intSelect[o.index].id = 'int-sc-select';
 			o.items[o.index].id = 'JS-animation';
 			o.width = width;
 			o.height = height;
 			wrapper._move(o.index);
 		},
 		initEvent: function(){
 			var wrapper =this,
	 			o       = wrapper.data,
	 			isMove  = false,
	 			wheel   = o.wheel;
 			var spillMark = false;
			var isSupportTouch = "ontouchend" in document ? true : false;
 			var handlerSatrt = function (e) {
 				isMove = true;
 				var type = e.type;
 				//e.preventDefault();//取消事件的默认动作
 				if(isSupportTouch){//touch 设备
 					o.pageX = e.changedTouches[0].pageX; 
 					o.pageY = e.touches[0].pageY;
 				}else{//PC 设备
 					o.clientX = e.clientX;
 					o.clientY = e.clientY; 
 				} 				
 				o.S = false; //isScrolling 
 				o.T = false; //isTested 
 				o.Y = 0; //horizontal moved
 				//origY = e.originalEvent.changedTouches[0].clientY;//阻止IOS的弹簧效果
 				o.wheel.style.webkitTransitionDuration = '0ms';
 				document.onmousemove = document.ontouchmove = handlerMove;
 				document.onmouseup  = document.ontouchend  = handlerEnd;
 			}
 			var handlerMove = function (e) {
 				if(!isMove) return false;
 				// e.stopPropagation();//终止事件在传播过程的捕获、目标处理或起泡阶段进一步传播
 				// e.preventDefault(); //取消事件的默认动作
 				if(isSupportTouch){//touch 设备
 					var Y = o.Y = e.touches[0].pageY - o.pageY;
 					if(!o.T) { var S = Math.abs(Y) < Math.abs(e.touches[0].pageX - o.pageX);}
 				}else{//PC 设备
 					var Y = o.Y = e.clientY - o.clientY;
 					if(!o.T) { var S = Math.abs(Y) < Math.abs(e.clientX - o.clientX);}
 				}
 				//滚动到达最顶端				
 				if(o.index == 0 && Y>0){return false;}
 				//滚动到达最低端
 				if(o.index == o.length-1 && Y<0){/*wrapper.spill(1);*/return false;}
 				if(!o.T) { 
 					S || clearTimeout(o.play);
 					o.T = true;
 					o.S = S;
 				}
 				if(!o.S) {
 					e.stopPropagation();//终止事件在传播过程的捕获、目标处理或起泡阶段进一步传播
 					e.preventDefault(); //取消事件的默认动作
 					o.wheel.style.webkitTransform = 'translate3d(0,' + (Y - o.index * o.height) + 'px,0)';
 				};
 			}	
 			var handlerEnd = function() {
 				o.S || wrapper._slide(o.index + (o.Y <= -20 ? Math.ceil(-o.Y / o.height) : (o.Y > 20) ? -Math.ceil(o.Y / o.height) : 0));
 				isMove  = false;
 			}; 			
 			wheel.addEventListener('touchstart', handlerSatrt); wheel.addEventListener('mousedown', handlerSatrt , true);
 			wheel.addEventListener('touchcancel', handlerEnd);
 			wheel.addEventListener('webkitTransitionEnd', function(){
 				if(o.showDot) {
 					document.getElementById('int-sc-select').id = '';
 					o.intSelect[o.index].id = 'int-sc-select';

 					document.getElementById('JS-animation').id = '';
 					o.items[o.index].id = 'JS-animation';
 				}
 				wrapper._setTimeout();
 				wrapper.slideEnd && wrapper.slideEnd.apply(wrapper);
 			});
 		},
 		// 轮播位置判断
 		_slide:function(index, auto){
 			var wrapper = this,
 			o = wrapper.data,
 			length = o.length;
 			if(-1 < index && index < length) {
 				wrapper._move(index);
 			} else if(index >= length) {//超出length
 				wrapper._move(length - (auto ? 2 : 1));
 				o._direction = -1;  //向上轮播
 			} else { //已达到最顶端
 				wrapper._move(auto ? 1 : 0);
 				o._direction = 1;//向下轮播
 			}
 			return wrapper; 
 		},
 		// 轮播方法
 		_move:function(index) {
 			var o = this.data; 			
 			o.index = index;
 			o.wheel.style.cssText += '-webkit-transition:.7s;-webkit-transform:translate3d(0,-' + index * o.height + 'px,0);'; 
 			window.location.href = '#page'+(o.index*1+1);
 		},
 		/**
 		* 设置自动播放
 		*/
 		_setTimeout:function() {
 			var wrapper = this,
 			o = wrapper.data;
 			if(!o._inPlay || !o.autoPlay) return wrapper;
 			clearTimeout(o.play);
 			o.play = setTimeout(function() {
 				wrapper._slide.call(wrapper, o.index + o._direction, true);
 			}, 4000);
 			return wrapper;
 		},
 		_mouseWheel:function() {
 			var wrapper = this,
 			o = wrapper.data;
 			var setAnimation = false;
 			var wheel = function (event){
				var delta = 0;
				if (!event) event = window.event;
				if (event.ctrlKey){return false;};  //ctrl 的缩放事件  搞不明白问什么 会无效  不过还好 不会影响到布局
				 if (event.wheelDelta) {
					delta = event.wheelDelta/120; 
					if (window.opera) delta = -delta;
					} else if (event.detail) {
						delta = -event.detail/3;
				}
				if (delta){		
					var wheelIndex = o.index + delta;	
					if(0 < wheelIndex  < o.length){
						//wrapper._move(o.index - delta);
					}
				}
				//handle(delta);
			}; 
			if (window.addEventListener){
				window.addEventListener('DOMMouseScroll', wheel, false);
				window.onmousewheel = document.onmousewheel = wheel;
			};
 		},
 		// 重设容器及子元素宽度
 		_resize:function(){
 			var wrapper = this, 
 			o = wrapper.data,
 			width = o.dom.offsetWidth,
 			height = o.dom.offsetHeight,
 			length = o.length,
 			items = o.items;
 			if(!height) return wrapper;
 			o.height = height;
 			clearTimeout(o.play);
 			for(var i = 0; i < length; i++){
 				items[i].style.cssText += 'width:' + width + 'px;height:' + height + 'px;-webkit-transform:translate3d(0,' + i * height + 'px,0);';
 			};
 			o.wheel.style.removeProperty('-webkit-transition');
 			o.wheel.style.cssText += 'width:' + width + 'px;height:' + height * length + 'px;-webkit-transform:translate3d(0,-' + o.index * height + 'px,0);';
 			o._direction = 1;//向下轮播
 			wrapper._setTimeout();  			
 			return wrapper;
 		},
 		start: function() {
 			var wrapper = this;
 			wrapper.data._direction = 1; 
 			wrapper.data._inPlay = true;
 			wrapper._setTimeout();
 			wrapper._mouseWheel();
 			window.addEventListener('resize', function(){
 				wrapper._resize();
 			});
 			return wrapper;
 		},
 		stop: function() {
 			var wrapper = this;
 			clearTimeout(wrapper.data.play);
 			wrapper.data._inPlay = false;
 			return wrapper;
 		},
 		prev: function() {
 			return this._slide(this.data.index - 1);
 		},
 		next: function() {
 			return this._slide(this.data.index + 1);
 		},
 		spill: function(show){
 			if(!show || document.getElementById('JS-spill')) return;
 			var wrapper = this,
 				o = wrapper.data;
 			var spill = document.createElement('div'); //创建动画执行box
 			spill.id = 'JS-spill';
 			spill.innerHTML = '没有更多内容了,请向上滑动';
 			o.dom.appendChild(spill);
 			setTimeout(function(){o.dom.removeChild(spill);} , 3500);
 		}
 	};
	window.Fullpage = fullpage;
}(window)); 


