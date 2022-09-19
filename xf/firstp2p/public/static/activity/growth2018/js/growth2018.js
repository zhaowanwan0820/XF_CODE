(function () {
    var preLoadArr=["../images/0.jpg","../images/1.jpg","../images/2.jpg","../images/3.jpg","../images/4.jpg","../images/5.jpg","../images/6.jpg","../images/18bill_0.jpg","../images/18bill_1.jpg","../images/18bill_2.jpg","../images/18bill_3.jpg","../images/18bill_4.jpg","../images/18bill_5.jpg","../images/18bill_6.jpg","../images/erweima.png","../images/inp.png","../images/loading.png","../images/login_bg.png","../images/not_user.png","../images/progressBar.jpg","../images/save_btn.png","../images/see_onmore.png","../images/share_btn.png","../images/slide.jpg","../images/slide1_bg.png","../images/slide2_bg.png","../images/slide3_bg.png","../images/slide4_bg.png","../images/slide5_bg.png","../images/slide6_bg.png","../images/slide7_bg.png","../images/slide8_bg.png","../images/slide1_star.png","../images/slide1_coin1.png","../images/slide1_coin2.png","../images/slide1_coin3.png","../images/slide1_text1.png","../images/slide_data.png","../images/slide7_money1.png","../images/slide7_money2.png","../images/slide7_money3.png","../images/slide7_money4.png","../images/slide7_money5.png","../images/slide7_money6.png","../images/view.png"];
    //图片预加载
    
    (function () {
        var len=preLoadArr.length;
        var newImg=null;
        var count=0;
        var img=null;
        var printProgress=function () {
            var canvas = document.getElementById('loadCanvas');
            if(canvas){
                var context = canvas.getContext('2d');
            }
            function background(){
                context.beginPath(); //路径开始
                bg = document.getElementById('bg');
                context.drawImage(bg, 31, 93.6, 273, 35.6);
            }
            function progressBar(w){
                // context.save();
                img = document.getElementById('img');
                context.fillStyle = context.createPattern(img,'repeat');
                context.beginPath();
                context.moveTo(31,107.8);
                context.arcTo(w*2.31+31,107.8,w*2.31+31,126.5,10);
                context.arcTo(w*2.31+31,126.5,31,126.5,10);
                context.arcTo(31,126.5,31,107.8,10);
                context.arcTo(31,107.8,w*2.31+31,107.8,10);
                context.fill();
                context.restore();
            }
            function text(w){
                context.save(); //save和restore可以保证样式属性只运用于该段canvas元素
                context.beginPath();
                context.strokeStyle = "#fff"; //设置描边样式
                context.font = "10px Arial"; //设置字体大小和字体
                //绘制字体，并且指定位置
                context.textAlign="center";
                context.textBaseline="middle";
                context.strokeText(w.toFixed(0)+"%", w*2+30, 118);
                context.stroke(); //执行绘制
                context.restore();
            }
            return function (num) {
                var speed=num/len*100;
                context.clearRect(0, 0, canvas.width, canvas.height);
                background();
                progressBar(speed);
                text(speed);
                if (speed>=100){
                    setTimeout(function () {
                        $('#loading').hide();
                        var toDown=$('.top_btn');
                        mySwiper = new Swiper ('.swiper-container', {
                            direction : 'vertical',
                            // paginationHide: true,
                            initialSlide:0,
                            on:{
                                init: function(x){
                                    swiperAnimateCache(this); //隐藏动画元素 
                                    swiperAnimate(this); //初始化完成开始动画
                                }, 
                                slideChangeTransitionEnd: function(){ 
                                    swiperAnimate(this); //每个slide切换结束时也运行当前slide动画
                                    //this.slides.eq(this.activeIndex).find('.ani').removeClass('ani'); 动画只展现一次，去除ani类名
                                    if(this.activeIndex == 1){
                                        $(".show1").css("display","block");
                                    }else if(this.activeIndex == 2){
                                        $(".show2").css("display","block");
                                    }else if(this.activeIndex == 3){
                                        $(".show3").css("display","block");
                                    }
                                    if(this.activeIndex == 8){
                                        toDown.hide();
                                    }else{
                                        toDown.show();
                                    }
                                }
                            }
                        });
                    },300);
                }
            }
        }();

        preLoadArr.forEach(function (val,index) {
            newImg=new Image();
            newImg.src = val;
            if (newImg.complete){
                printProgress(++count);
            }else {
                newImg.onload=newImg.onerror=function () {
                    printProgress(++count);
                }
            }
        });

    })();
})();


var NUMBER_OF_LEAVES = 15;
var width = window.screen.width;
function init() {
    var container = document.getElementById('leafContainer');
    if(container){
        for (var i = 0; i < NUMBER_OF_LEAVES; i++) {
            container.appendChild(createALeaf());
        }
    }
}
function randomInteger(low, high){
    return low + Math.floor(Math.random() * (high - low));
}
function randomFloat(low, high){
    return low + Math.random() * (high - low);
}
function pixelValue(value){
    return value + 'px';
}
function durationValue(value){
    return value + 's';
}
function createALeaf(){
    var leafDiv = document.createElement('div');
    var image = document.createElement('img');
    image.src = 'https://event.ncfwx.com/upload/image/20181222/11-31-slide7_money' + randomInteger(1, 7) + '.png';
    leafDiv.style.top = "-100px";
    leafDiv.style.left = pixelValue(randomInteger(0, width));
    var spinAnimationName = (Math.random() < 0.5) ? 'clockwiseSpin' : 'counterclockwiseSpinAndFlip';
    leafDiv.style.webkitAnimationName = 'fade, drop';
    image.style.webkitAnimationName = spinAnimationName;
    var fadeAndDropDuration = durationValue(randomFloat(5, 15));
    var spinDuration = durationValue(randomFloat(5, 15));
    leafDiv.style.webkitAnimationDuration = fadeAndDropDuration + ', ' + fadeAndDropDuration;
    var leafDelay = durationValue(randomFloat(0, 0));
    leafDiv.style.webkitAnimationDelay = leafDelay + ', ' + leafDelay;
    image.style.webkitAnimationDuration = spinDuration;
    leafDiv.appendChild(image);
    return leafDiv;
}
window.addEventListener('load', init, false);