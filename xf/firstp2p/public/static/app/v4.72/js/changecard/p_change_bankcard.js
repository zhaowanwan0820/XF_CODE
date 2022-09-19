;
(function($) {

    $(function() {
        //在ios10系统中meta设置失效了：
        //为了提高Safari中网站的辅助功能，即使网站在视口中设置了user-scalable = no，用户也可以手动缩放。
        window.onload = function() {
            document.addEventListener('touchstart', function(event) {
                if (event.touches.length > 1) {
                    event.preventDefault();
                }
            })
            var lastTouchEnd = 0;
            document.addEventListener('touchend', function(event) {
                var now = (new Date()).getTime();
                if (now - lastTouchEnd <= 300) {
                    event.preventDefault();
                }
                lastTouchEnd = now;
            }, false);
        }

        // 上传
        var btn_disable = false;
        var updatebtn = function() {
            if (btn_disable) {
                $('.JS_submit_change').attr('disabled', 'disabled').addClass('ui_btn_disabled');
            } else {
                $('.JS_submit_change').removeAttr('disabled', 'disabled').removeClass('ui_btn_disabled');
            }
        }

        // Firstp2p.upload($('.content_h5'), {
        //     _switch: "html5", //上传方式选择 html5 flash normal
        //     datatype: "json", //返回值类型
        //     template: {
        //         "html5": '<div class="upfile_root">' + '<div class="upfile_c">' + '<a class="upfile_word" href="#">拍摄</a>' + '<input id="Filedata" class="ui_file" type="file" name="Filedata" multiple="multiple" accept="image/*" capture="camera"/>' + '' + '</div>' +
        //             '</div>'
        //     },
        //     onsuccess: function(ele, data) { //上传成功回调
        //         console.log('data, ele', data, ele);
        //         $('.j_photograph').hide();
        //         $('.j_pzhao_btn').hide();
        //         $(".myimg_h5").html('<img src="' + data.imgUrl + '" width="80px;" height="80px" class="j_small_img">').show();
        //         $('.JS_del_img').show();
        //         btn_disable = false;
        //         updatebtn();
        //         // $('.JS_submit_change').removeAttr('disabled','disabled').removeClass('ui_btn_disabled');
        //     },
        //     onerror: function(e) { //产生错误回调
        //         console.log('error', e);
        //     },
        //     progress: function(per, ele) { //上传进度条回调, 参数 per 代表百分比
        //         var pele = $(".progress");
        //         var rate = pele.find('.j_rate');
        //         // pele.css({"display": "block"});
        //         pele.show();
        //         rate.html(per);
        //         circleProcess();
        //         btn_disable = true;
        //         updatebtn();
        //         if (per >= 100) {
        //             pele.hide(100);
        //             btn_disable = false;
        //             updatebtn();
        //         }
        //     },
        //     upload_url: "./ww-upload.php", //上传地址
        //     //文件类型限制
        //     type: "'jpeg|jpg|png|gif'", //允许上传类型
        //     // size : 1 * 1024 * 1024, // 1M 单个文件大小限制
        //     size: 10 * 1024 * 1024, // 10M 单个文件大小限制
        //     post_params: {
        //         "test": "html5"
        //     }, //post参数
        //     //begin处理样式
        //     begin: function(ele) {
        //         var word = ele.find('.upfile_c');
        //         var link = word.find('.upfile_word')[0];
        //         if (!link.getAttribute('_init')) {
        //             link.setAttribute("_init", link.innerHTML);
        //             link.innerHTML = '';
        //         }
        //         word.addClass('upfiling');
        //         ele.find('input[type=file]').css('display', 'none');
        //     }
        // });

        $('.content_h5').on("change", "#Filedata", function() {
            var file = this.files[0];
            if (file.size > 1024 * 1024 * 3) {
                P2PWAP.ui.toast('上传文件大小超了，请重新上传！');
                return false;
            }
            var reader = new FileReader();
            //console.log(this.files);
            $('.j_photograph').hide();
            $('.j_pzhao_btn').hide();
            reader.onload = function(e) {
                var str = this.result;
                $(".myimg_h5").html('<img src="' + str + '" width="80px;" height="80px" class="j_small_img">').show();
                $('.JS_del_img').show();
                btn_disable = false;
                //str = str.replace(/(data:image\/(jpeg|png|jpg|bmp|gif);base64,)/ig,"");//转成base64编码传给后端
                //$("#file").val(str);
                updatebtn();
                // $('.JS_submit_change').removeAttr('disabled','disabled').removeClass('ui_btn_disabled');
            }
            reader.readAsDataURL(file);
        });

        //console.log(("data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBw").replace(/(data:image\/(jpeg|png|jpg|bmp|gif);base64,)/ig,""));
        // 点击删除图片
        $('.JS_del_img').on('click', function(event) {
            $(this).hide();
            $('.myimg_h5').empty();
            btn_disable = true;
            updatebtn();
            // $('.JS_submit_change').attr('disabled','disabled').addClass('ui_btn_disabled');
            // event.stopPropagation(); //或者采用此方法阻止冒泡
            return false;
        });
        // 点击小图看大图
        $('.j_uploadbox').on('click', '.j_small_img', function(event) { //注意绑定到j_uploadbox
            var myimage = document.getElementById("myimage");
            var imgUrl = $(this).attr('src');
            //获取图片的原始尺寸
            var smallimg_realwidth = event.target.naturalWidth;
            var smallimg_realheight = event.target.naturalHeight;
            $('.j_big_img').attr('src', imgUrl);
            var bigimg_width = ($(window).width()) * 0.8;
            var bigimg_height = (smallimg_realheight * bigimg_width) / smallimg_realwidth;
            $('.j_big_img').css({
                "margin-top": -(bigimg_height / 2),
                "height": bigimg_height
            });
            $('.bigimg_wrap').css({
                "margin-top": -(bigimg_height / 2),
                "height": bigimg_height
            });
            $('.bigimg_bg').show();
            $('.bigimg_wrap').show();
            // alert($('.bigimg_wrap').width());
            $('.close').on('click', function() {
                $('.bigimg_bg').hide();
                $('.bigimg_wrap').hide();
            });
            // event.stopPropagation();//或者采用此方法阻止冒泡
            return false;
        });



        // 进度条
        function circleProcess() {
                $('.circle').each(function(index, el) {
                    var num = $(this).find('span').text() * 3.6;
                    if (num <= 180) {
                        $(this).find('.right').css('transform', "rotate(" + num + "deg)");
                    } else {
                        $(this).find('.right').css('transform', "rotate(180deg)");
                        $(this).find('.left').css('transform', "rotate(" + (num - 180) + "deg)");
                    };
                });
            }
            // 点击照相机图片弹出拍摄页面
        $('.j_user_info').on('click', '.j_uploadbox', function(event) { //注意绑定到j_user_info
            $('.j_pzhao_btn').show();
            $('.j_photograph').show();
        });



        // 提交审核
        $('.p_change_bankcard').on('submit', '#form', function() {

            var formData = new FormData(this);
            $(".circle_wrap").css("display" , "block");
            $('.JS_submit_change').attr('disabled', 'disabled').addClass('ui_btn_disabled');
            //alert($("#Filedata").val());
            $.ajax({
                url: '/payment/SaveBank',
                type: 'post',
                async: true,
                data: formData,
                //data : $(this).serialize(),
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function(json) {
                    $(".circle_wrap").css("display" , "none");
                    if (json.errno === 0) {
                        location.href = '/payment/EditbankSuccess?token=' + $("#token").val();
                    } else {
                        P2PWAP.ui.toast('<p class="sub_err">'+ json.error +'</p>');
                    }
                    $('.JS_submit_change').removeAttr('disabled', 'disabled').removeClass('ui_btn_disabled');
                },
                error: function() {
                    $(".circle_wrap").css("display" , "none");
                    $('.JS_submit_change').removeAttr('disabled', 'disabled').removeClass('ui_btn_disabled');
                    P2PWAP.ui.toast('<p class="sub_err">提交失败,请重新提交</p>');
                }
            });

            return false;
        });
    });

})(Zepto);