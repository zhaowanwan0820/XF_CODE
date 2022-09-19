$(function(){
    /*var bodyHtml=$('body').html();
    var regObj=/src="images\/(.*\.(png|jpg))"/g;
    console.log(bodyHtml.replace(regObj,'src="<?php echo $this->asset->makeUrl('+"'/v2/growth/images/$1'"+')?>"'));*/
    //没有成长轨迹时
    if(_isInvestor==0){
        $('body>div').hide();
        $('#noTrackBox').find('.noTrackText p span').text(_userGrowth.real_name);
        $('#noTrackBox').show();
        return;
    }

    var relPathObj=function(){//相对地址
        var url="";
        var isRelative=false;
        url=$('#loadingPage').data('relativePath');
        if(url.charAt(url.length-1)=='/'){
            url=url.substr(0,url.length-1);
        }
        //判断是相对地址还是绝对地址
        if(/\/\//.test('url')){
            isRelative=true;
        }
        return {
            'url':url,
            'isRelative':isRelative
        }
    }();
    var fullPage=null;//滑动外盒子
    var pageItemS=null;//滑动页面集合
    var loadCont_templet=$('#loadCont_templet');//异步追加的html内容的script标签
    var loadContHtml=loadCont_templet.html();//异步追加的html文本内容
    //TODO loading页面
    (function(){
        var loadingPage=$('#loadingPage');//loading外盒子
        var loadText=$('#loadText');//加载比例文案
        var loadContBox=$('#loadContBox');//加载完成后呈现内容

        /**
         * 显示比例函数
         * @param {Number} num 比例数值
         */
        function changeText(num){
            loadText.text(num+'%');
        }


        function resetContFn(dataObj,loadContHtml){
            var regObj=null,
                xz_suffix="",
                tmpReg=/^\s*$/,
                endNum=parseInt(dataObj.num)%4+1;//由Num取模求出1-4随机值
            $.each(dataObj,function(key,val){
                regObj=new RegExp('{{'+key+'}}','g');
                loadContHtml=loadContHtml.replace(regObj,val);
            });
            switch(dataObj.constellation){
                case '白羊座':{
                    xz_suffix="by";
                    break;
                }
                case '处女座':{
                    xz_suffix="cn";
                    break;
                }
                case '金牛座':{
                    xz_suffix="jn";
                    break;
                }
                case '巨蟹座':{
                    xz_suffix="jx";
                    break;
                }
                case '摩羯座':{
                    xz_suffix="mj";
                    break;
                }
                case '双子座':{
                    xz_suffix="shuangzi";
                    break;
                }
                case '水瓶座':{
                    xz_suffix="sp";
                    break;
                }
                case '射手座':{
                    xz_suffix="ss";
                    break;
                }
                case '双鱼座':{
                    xz_suffix="sy";
                    break;
                }
                case '狮子座':{
                    xz_suffix="sz";
                    break;
                }
                case '天秤座':{
                    xz_suffix="tp";
                    break;
                }
                case '天蝎座':{
                    xz_suffix="tx";
                    break;
                }
            }
            loadContBox.html(loadContHtml);
            $('#constellation_img').attr('src',relPathObj.url+'/v2/growth/images/xingzuo/'+'page01_img01_'+xz_suffix+'.png');
            //没有第一笔收益
            if(tmpReg.test(dataObj.freturn_time)){
                $('#freturnTime').text('不久的将来');
            }
            //没有邀请好友时
            if(tmpReg.test(dataObj.frefer_time)){
                $('.page05 .textInfor').html('<p>我尚未邀请过小伙伴</p><p>听说邀请好友来投资，有更多福利拿</p>');
                $('.page05').find('.timeLine,.bln').hide();
            }
            //没有投过公益标时
            if(tmpReg.test(dataObj.fgybid_time)){
                $('.page06 .textInfor').html('<p>我尚未捐赠过公益标</p><p>听说有爱心的人运气也不会差</p>');
                $('.page06').find('.timeLine,.bln').hide();
            }
            $('#endImg01').attr('src',relPathObj.url+'/v2/growth/images/endpage/'+'page09_img01_0'+endNum+'.png');
            $('#endImg02').attr('src',relPathObj.url+'/v2/growth/images/endpage/'+'page09_img02_0'+endNum+'.png');
            var inviteBtn = $('#inviteBtn');
            if(_isApp!=0){
                inviteBtn.attr('href','bonus://api?title=' + encodeURIComponent(shareOpt.title) + '&content='+encodeURIComponent(shareOpt.content)+'&face='+encodeURIComponent(shareOpt.img)+'&url='+encodeURIComponent(shareOpt.url));
            }else{
                inviteBtn.on('click',function(){
                    //title, content, url, img
                    P2PWAP.ui.showShareView(shareOpt.title,shareOpt.content,shareOpt.url,shareOpt.img,1);
                });
            }
            if(_isHideShare==1){
                inviteBtn.parents('.imgWrap:first').hide();
            }
        }
        resetContFn(_userGrowth,loadContHtml);

        fullPage=$('#fullPage');//滑动外盒子
        pageItemS=fullPage.find('.section');//滑动页面集合

        //待加载图片集合
        var loadImgArr=function(){
            var imgArr=[];
            loadContBox.find('img').each(function(){
                var curSrc=$(this).attr('src');
                imgArr.push(curSrc);
            });
            pageItemS.find('.con').each(function(){
                var bgImg=$(this).css('backgroundImage');
                var imageName=bgImg.match(/\/(\w*\.(jpg|png))\"?\)$/)[1];
                imgArr.push(relPathObj.url+'/v2/growth/images/'+imageName);
            });
            return imgArr;
        }();

        /**
         * 加载图片函数
         * @param imgArr 包含图片src的数组
         * @param callBack 加载完成回调函数
         */
        function loadImgFn(imgArr,callBack){
            var newImg,initCount=0;
            var arrLen=imgArr.length;
            for(var i= 0;i<arrLen;i++){
                newImg=new Image();
                newImg.src=imgArr[i];
                //debugger;
                if(newImg.complete){
                    initCount=callBack(initCount,arrLen);
                }else{
                    newImg.onload=newImg.onerror=function(){
                        initCount=callBack(initCount,arrLen);
                    }
                }
            }
        }

        //加载图片函数调用
        loadImgFn(loadImgArr,function(initCount,arrLen){
            initCount++;
            changeText(Math.floor(initCount/arrLen*100));
            if(initCount==arrLen){
                loadingPage.hide();
                $('#indexPage').show();
                return;
            }else{
                return initCount;
            }
        });
    })();
    //TODO 引导页效果
    (function(){
        var spaceman=$('#index_spaceman');
        var recursionFlag=false;//递归标识
        spaceman.transition({
            x:'59.7%',
            y:'38.85%',
            opacity:1,
            rotate:'-24deg'
        },800,function(){
            $(this).addClass('shake');
        });

        function meteorAniFn(){
            var _this=this;
            setTimeout(function(){
                    meteorAni.call(_this)
                },$(this).data('delayTime'));
        }
        function meteorAni(){
            $(this).transition({
                'x':'-437%',
                'y':'-336%'
            },1500,function(){
                $(this).css({
                    'x':0,
                    'y':0
                });
                if(recursionFlag){
                    return;
                }else{
                    meteorAni.call(this);
                }
            });
        }
        var meteorS=$('.index_meteor');
        meteorS.each(function(){
            meteorAniFn.call(this);
        });
        /**
         * 滑动效果函数
         */
        function fullPageFn(){
            var itemLen=pageItemS.length;
            var navLiS=$('#navUl li');
            var iconArrow=$('#icon_arrow');
            fullPage.fullpage({
                'navigation': false,
                afterLoad: function (anchorLink, index) {
                    //pageItemS.find('.con').find('*').removeClass('ani');
                    //pageItemS.eq(index-1).find('.con').find('*:not(".textInfor p,.textInfor")').addClass('ani');
                    pageItemS.find('.con').find('.runAni').removeClass('ani');
                    pageItemS.eq(index-1).find('.con').find('.runAni').addClass('ani');
                    if(index!=itemLen){
                        iconArrow.show();
                    }
                },
                onLeave: function (index, nextindex, direction) {
                    navLiS.removeClass('active');
                    navLiS.eq(nextindex-1).addClass('active');
                    iconArrow.hide();
                }
            });
            pageItemS.eq(0).find('.con').find('.runAni').removeClass('ani');
        }
        $('#index_btnA').one('click',function(){
            recursionFlag=true;
            fullPageFn();
            $('#indexPage').hide();
            fullPage.show(1,function(){
                pageItemS.eq(0).find('.con').find('.runAni').addClass('ani');
            });
            $('#navWrap').show();
            $('#landscape').addClass('open');
        });
    })();
});