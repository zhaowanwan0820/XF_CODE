(function () {
    var scaleW=$(window).width()/320;
    var scaleH=$(window).height()/480;
    var wArr=["width","left","right","fontSize","paddingLeft"];
    var hArr=["height","top","bottom","lineHeight","paddingTop"];
    var pointDown=$('#pointDown');//向下指示箭头
    var staticPrefix=$('#staticPrefix').val()+'activity/fatherday/';//静态资源前缀
    var isMusicLoad=false;//音乐是否播放
    var preLoadArr=["images/bg01.jpg","images/bg02.jpg","images/pointDown.png","images/section01/t01.png","images/section01/t02.png","images/section01/t03.png","images/section01/t04.jpg","images/section01/t05.jpg","images/section01/t06.jpg","images/section01/t07.jpg","images/section01/t08.jpg","images/section01/t09.jpg","images/section02/t01.png","images/section02/t02.png","images/section02/t03.png","images/section02/t04.png","images/section03/t01.png","images/section03/t02.png","images/section03/t03.png","images/section03/t04.png","images/section04/t01.png","images/section04/t02.png","images/section04/t03.png","images/section04/t04.png","images/section05/t01.png","images/section05/t02.png","images/section05/t03.png","images/section05/t04.png","images/section05/t05.png","images/section05/t06.png","images/section06/t01.png","images/section06/t02.png","images/section06/t03.png","images/section06/t04.png"];
    var isAndroid=function () {
        var flag=navigator.userAgent.indexOf('Android')!=-1;
        if (flag){
            $('#musicBtn').hide();
        }
        return navigator.userAgent.indexOf('Android')!=-1;
    }();

    //所有带有adjustCss样式的标签，都要利用js重新生成css
    $('.adjustCss').each(function () {
        var _this=this;
        var csssMap=$(this).data('cssarr');
        if ($.type(csssMap)=="undefined"){
            return;
        }else{
            var cssObj=function(){
                var obj={};
                $.each(csssMap,function (key,val) {
                    if (wArr.indexOf(key) != -1) {
                        obj[key]=parseInt(val*scaleW);
                    } else if (hArr.indexOf(key) != -1) {
                        obj[key]=parseInt(val*scaleH);
                    }else{
                        obj[key]=val;
                    }
                });
                return obj;
            }();
            $(this).css(cssObj);
        }
    });

    var mySwiper = null;

    //导航
    $('.slideTo').on('click',function () {
        var slideIndex=$(this).data('slideIndex');
        mySwiper.slideTo(slideIndex);
    });

//    图片预加载
    (function () {
        var len=preLoadArr.length;
        var newImg=null;
        var count=0;
        var printProgress=function () {
            var canvas = document.getElementById('loadCanvas'),  //获取canvas元素
                context = canvas.getContext('2d'),  //获取画图环境，指明为2d
                centerX = canvas.width/2,   //Canvas中心点x轴坐标
                centerY = canvas.height/2,  //Canvas中心点y轴坐标
                rad = Math.PI*2/100; //将360度分成100份，那么每一份就是rad度
            //绘制蓝色外圈
            function outerCircle(n){
                context.save();
                context.strokeStyle = "#b39667"; //设置描边样式
                context.lineWidth = 5; //设置线宽
                context.beginPath(); //路径开始
                context.arc(centerX, centerY, 60 , -Math.PI/2, -Math.PI/2 +n*rad, false); //用于绘制圆弧context.arc(x坐标，y坐标，半径，起始角度，终止角度，顺时针/逆时针)
                context.stroke(); //绘制
                context.closePath(); //路径结束
                context.restore();
            }
            //绘制白色外圈
            function whiteCircle(){
                context.save();
                context.beginPath();
                context.strokeStyle = "#b39667";
                context.arc(centerX, centerY, 60 , 0, Math.PI*2, false);
                context.stroke();
                context.closePath();
                context.restore();
            }
            //百分比文字绘制
            function text(n){
                context.save(); //save和restore可以保证样式属性只运用于该段canvas元素
                context.strokeStyle = "#b39667"; //设置描边样式
                context.font = "20px Arial"; //设置字体大小和字体
                //绘制字体，并且指定位置
                context.textAlign="center";
                context.textBaseline="middle";
                context.strokeText(n.toFixed(0)+"%", centerX, centerY);
                context.stroke(); //执行绘制
                context.restore();
            }
            return function (num) {
                var speed=num/len*100;
                context.clearRect(0, 0, canvas.width, canvas.height);
                whiteCircle();
                text(speed);
                outerCircle(speed);
                if (speed>=100){
                    setTimeout(function () {
                        $('#loading').hide();
                        var pointDown=$('#pointDown');
                        mySwiper = new Swiper ('.swiper-container', {
                            direction : 'vertical',
                            paginationHide: true,
                            initialSlide:0,
                            onInit: function(swiper){
                                swiperAnimateCache(swiper);
                                swiperAnimate(swiper);
                            },
                            onSlideChangeEnd: function(swiper){
                                if (swiper.activeIndex==5){
                                    pointDown.hide();
                                }else{
                                    pointDown.show();
                                }
                            },
                            onSlideChangeStart:function (swiper) {
                                if (swiper.activeIndex==5){
                                    pointDown.hide();
                                }else{
                                    pointDown.show();
                                }
                            },
                            onTransitionEnd:function (swiper) {
                                swiperAnimate(swiper);
                            }
                        });
                        loadMusic();//开始播放音乐
                    },300);
                }
            }
        }();

        preLoadArr.forEach(function (val,index) {
            newImg=new Image();
            newImg.src = staticPrefix+val;
            if (newImg.complete){
                printProgress(++count);
            }else {
                newImg.onload=newImg.onerror=function () {
                    printProgress(++count);
                }
            }
        });

    })();

//    加载音乐

    function loadMusic(){

        if(isMusicLoad || isAndroid) return;
        isMusicLoad = true;
        var audio = new Audio();
        audio.src=staticPrefix+"music/father.mp3";
        audio.loop = true;
        audio.autoplay = true;
        audio.play();
        var isPlay = true;
        function touch(){
            audio.play();
        }
        document.body.addEventListener('touchstart',touch,false);
        document.addEventListener('webkitvisibilitychange',function()
        {
            if(document.webkitVisibilityState=='hidden' || document.hidden){
                audio.pause();
            }else{
                if(isPlay){
                    audio.play();
                }
            }
        })
        $("#musicBtn").click(function(){
            document.body.removeEventListener('touchstart', touch);
            if(isPlay){
                audio.pause();
                isPlay = false;
                $(this).addClass("paused");
            }else{
                audio.play();
                isPlay = true;
                $(this).removeClass("paused");
            }
        });
    }

    //微信分享
    (function () {
        var shareConf=function () {
            var shareConf=$('#shareConfTpl').html();
            var obj={};
            try{
                obj=JSON.parse(shareConf);
            }catch (ex){
                obj={};
            }
            return obj;
        }();
        var defaultShareConf={
            "imgUrl":'http://event.firstp2p.com/upload/image/20170615/16-32-share.jpg',
            "lineLink":location.href,
            "shareTitle":'心有父爱，让他知道！',
            "descContent":'这个节日，是爱的节日，给父亲一个电话或拥抱，把心底的爱大声说出来！'
        }
        var targetConf=$.extend({},defaultShareConf,shareConf);
        var imgUrl=targetConf.imgUrl;
        var lineLink=targetConf.lineLink;
        var descContent=targetConf.descContent;
        var shareTitle=targetConf.shareTitle;
        wx.ready(function() {
            loadMusic();//自动加载音乐
            wx.showOptionMenu();
            wx.onMenuShareTimeline({
                title: shareTitle, // 分享标题
                link: lineLink, // 分享链接
                imgUrl: imgUrl, // 分享图标
                success: function (res) {
                    // 用户确认分享后执行的回调函数
                },
                cancel: function (res) {
                    // 用户取消分享后执行的回调函数
                }
            });
            wx.onMenuShareAppMessage({
                title: shareTitle, // 分享标题
                desc: descContent, // 分享描述
                link: lineLink, // 分享链接
                imgUrl: imgUrl, // 分享图标
                success: function (res) {
                    // 用户确认分享后执行的回调函数
                },
                cancel: function (res) {
                    // 用户取消分享后执行的回调函数
                }
            });
        });
    })();

})();
