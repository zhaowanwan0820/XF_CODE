var $Ul = document.getElementsByTagName('ul')[0];
var $LeftBtn = document.getElementById('leftBtn');
var $RightBtn = document.getElementById('rightBtn');
var moveWidth = $Ul.children[0].offsetWidth;
var pageNum = $Ul.children.length - 1;
var pageTimer = null;
var sliderIndex = 0;
var locked = false;

var zhufu_video = document.getElementsByClassName("zhufu_video")[0];
var daibiao_video = document.getElementsByClassName("daibiao_video")[0];
zhufu_video.addEventListener('play',function(){  
    if(daibiao_video.play){
        daibiao_video.pause();
    }
});  

daibiao_video.addEventListener('play',function(){
    if( zhufu_video.play){
        zhufu_video.pause();
    }
});

$LeftBtn.onclick = function(){
    autoMove(-1);
}
$RightBtn.onclick = function(){
    autoMove(1);
}

function autoMove (direction){
    if(!locked){
    locked = true;
    if(direction === 1  ){
        if($Ul.offsetLeft === -pageNum*moveWidth){
            $Ul.style.left = '0px';                     
        }
        startMove($Ul,{left:$Ul.offsetLeft-moveWidth},function(){ 
            locked = false;
        });
    }else if(direction === -1  ){
        if($Ul.offsetLeft === 0){
            $Ul.style.left = -pageNum * moveWidth + 'px';
        }
        startMove($Ul,{left:$Ul.offsetLeft + moveWidth},function(){  
            locked = false;             
        });
    }
    }
}

function getStyle( obj , attr ){
    if(obj.currentStyle){
         return obj.currentStyle[attr];
    }else{
        return window.getComputedStyle(obj,false)[attr];
    }
}

function startMove( obj , data , func ){
     clearInterval(obj.timer);
     var iSpeed;
     var iCur;
     var name;
     startTimer = obj.timer = setInterval(function(){
         var bStop = true;
         for(var attr in data){
            if( attr === 'opacity'){
                name = attr;
                icur = parseFloat(getStyle(obj,attr))*100;
            }else{
                iCur = parseInt(getStyle(obj,attr));
            }
            iSpeed = ( data[attr] - iCur ) / 8;
            if(iSpeed > 0){
                iSpeed = Math.ceil(iSpeed);
            }else{
                iSpeed = Math.floor(iSpeed);
            }
            if( attr ==='opacity' ){
                obj.style.opacity = ( iCur + iSpeed )/100;
            }else{
                obj.style[attr] = iCur + iSpeed + 'px';
            }
            if( Math.floor(Math.abs(data[attr] - iCur)) != 0){
                bStop = false;
            }
         }
         if( bStop ){
            clearInterval(obj.timer);
            if( name = 'opacity' ){
                obj.style.opacity = data[name] / 100;
            }
            func();
         }
     },30);
}