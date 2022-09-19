$(function() {
    //(NOTE!!!!!!)如果线上图片资源跟这个地址不一样请主动修改
    var imageRoot = location.protocol + '//' + location.hostname + '/static/v3/images/fouryears/';
    //此处记录所有引入的图片的尺寸,除了大背景。大背景要预先加载
    var sourceFiles = [{
        "url": "index_top.jpg",
        "size": 137
    }, {
        "url": "textzone.png",
        "size": 10
    }, {
        "url": "button.png",
        "size": 1.36
    }, {
        "url": "moreActivity.png",
        "size": 1.39
    }, {
        "url": "qrCode.png",
        "size": 1
    }, {
        "url": "share.jpeg",
        "size": 17.2
    },{
        "url":"suc/blessSucHeader.jpg",
        "size": 107
    },{
        "url":"suc/blessInfo.png",
        "size": 15
    },{
        "url":"suc/btnLittle.png",
        "size": 2
    },{
        "url":"suc/btnLittle2.png",
        "size": 3.29
    }];
    //预加载逻辑，就是把主要的大图size拿出来，计算单个加载的比例算出当前进度
    function preloadLogic() {
        var totalSize = 0;
        var loadedSize = 0;
        for (var i = 0; i < sourceFiles.length; i++) {
            totalSize += sourceFiles[i].size;
        }
        var numAnim = new CountUp($("#JS-loading font")[0], 0, 0);
        for (var i = 0; i < sourceFiles.length; i++) {
            var item = sourceFiles[i];
            var img = new Image();
            img.size = item.size;
            img.onload = function() {
                loadedSize = loadedSize + this.size;
                numAnim.update(parseInt(loadedSize * 100 / totalSize));
                // load ready
                if (parseInt(loadedSize * 100 / totalSize) > 98) {
                    setTimeout(function() {
                        $("#JS-loading").remove();
                        $("body").addClass('load-ready');
                    }, 1000);
                }
            }
            img.src = imageRoot + item.url;
        }
    }
    preloadLogic();

    $("#userWords").blur(function(){
        var _val = $(this).val();
        if(_val == ""){
            $(".changeLine").show();
            $(this).removeClass("normal").addClass("italic").val("祝网信四周年生日快乐!")
        }
    });
    $("#userWords").focus(function(){
        var _val = $(this).val();
        if(_val == "祝网信四周年生日快乐!"){
            $(this).val("").removeClass('italic').addClass('normal');
        }
    });
    $("#userWords").on("click",function(){
        $(".changeLine").hide();
    });
    $('#userPhone').focus(function(){
        $(".changeLine").hide();
    })
    var mobileRegEx = /^0?(13[0-9]|15[0-9]|18[0-9]|17[03678]|14[457])[0-9]{8}$/;
    var _boolean1,_boolean2,_tips1="",_tips2="";
    function testPhone(tel){
    	if(!tel || tel==null){
            _tips1 = "请输入手机号";
    	    // $('.tips').html("请输入手机号").show();
    	    _boolean1 = false;
    	}else if(!mobileRegEx.test(tel)){
            _tips1 = "请输入正确的手机号";
    		// $('.tips').html("请输入正确的手机号").show();
    	    _boolean1 = false;
    	}else{
            _tips1 ="";
    		// $('.tips').hide();
    	    _boolean1 = true;
    	}
    }
    function testWords(content){
        var pattern_char = /[a-zA-Z]/g;
        var pattern_chin = /[\u4e00-\u9fa5]/g;
        var count_char = content.match(pattern_char);
        var count_chin = content.match(pattern_chin);
        var count_char_length = 0;
        var count_chin_length = 0;
        if(count_char != null) {
            count_char_length = count_char.length;
        }
        if(count_chin != null) {
            count_chin_length = count_chin.length *2 ;
        }

        if(count_char_length + count_chin_length>80){
            _tips2 = "祝福语最多80字符";
            _boolean2 = false;
        }else{
            _tips2 = "";
            _boolean2 = true;
        }
    }

    $('#userPhone').blur(function() {

        var tel= $('#userPhone').val();
        var _true = testPhone(tel);
        if(!_true){
            _boolean1 = false;
            $('.tips').html(_tips1).show();
        }else{
            _boolean1 = true;
            $('.tips').html("").hide();
        }
    });
    $('#sendBless').on('click', function() {
    	$(this).addClass('hasSend');
        var info = $("#userWords").val();
        var mobile = $("#userPhone").val();
        testWords(info);
        testPhone(mobile);

        var token = $("#token").val();
        var token_id = $("#token_id").val();
        setTimeout(function(){
            $("#sendBless").removeClass('hasSend');
         },2000);
        if(_boolean1 && _boolean2){
            $('.tips').hide();
        	$.post("blessSave",{info:info,mobile:mobile,token:token,token_id:token_id},function(result){
        		if(result.error==0){
        			window.location.href ="?sn=" + result.data.sn;
        		}else if(result.error>0){
        			$('.tips').html(result.msg).show();
        		}
        	}, 'json');
        }else{
            if(_tips1 !=''){
                $('.tips').html(_tips1).show();
            }else if(_tips2 !=''){
                $('.tips').html(_tips2).show();
            }
        }

    });

});