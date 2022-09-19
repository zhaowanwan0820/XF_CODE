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
    // `usercard_front`'身份证正面',
    // `usercard_back` '身份证反面',
    var reg = /\s+/gi;
    var photo_img1 = $(".mying1").find(".j_small_img"),
        photo_img2 = $(".mying2").find(".j_small_img"),
        selected_city_val = $(".selected_city").html() == "请选择" ? "" : $(".selected_city").html(),
        detail_address = $(".first_city_area .second_text").val(),
        first_city_area = document.getElementsByClassName("first_city_area")
        detail_address = detail_address ? detail_address.replace(reg,'') : "",
        usercard_id = $(".usercard_id").html(),
        flag = true
    /* 从房产所在城市列表页面返回时判断用户之前是否填写过详细地址、房产材料是否上传 */
    if(P2PWAP.cache.get("_myimg_file1_src_")){
      photo_img1.length = 1
      $(".add").eq(0).html('<img src="' + P2PWAP.cache.get("_myimg_file1_src_") + '"class="j_small_img">').show();
      first_img_id = P2PWAP.cache.get("_myimg_file1_id_")
    }
    if(P2PWAP.cache.get("_myimg_file2_src_")){
      $(".add").eq(1).html('<img src="' + P2PWAP.cache.get("_myimg_file2_src_") + '"class="j_small_img">').show();
      photo_img2.length = 1
      second_img_id = P2PWAP.cache.get("_myimg_file2_id_")
    }
    /* 定义变量记录页面：个人信息页 or 房产信息页面 */
    if(!usercard_id){
      flag = true
    }else{
      flag = false
    }
    /* 判断实在个人信息页面还是在房产信息页面 */
    if(flag){
      first_img_id = house_deed_first_id ? house_deed_first_id : P2PWAP.cache.get("_myimg_file1_id_") ,
      second_img_id = house_deed_second_id ? house_deed_second_id : P2PWAP.cache.get("_myimg_file2_id_")
    }else{
      first_img_id = usercard_front_id ? usercard_front_id : P2PWAP.cache.get("_myimg_file1_id_"),
      second_img_id = usercard_back_id ? usercard_back_id : P2PWAP.cache.get("_myimg_file2_id_")
    }
    var updatebtn = function() {
			if(flag){
				if (!photo_img1.length || !photo_img2.length || !selected_city_val || !detail_address) {
					$('.JS_submit_change').attr('disabled', 'disabled').css("background-color","rgb(217,217,217)");
        } else {
            $('.JS_submit_change').removeAttr('disabled', 'disabled').css("background-color","rgb(23,127,222)");
        }
			}else{
				if (!photo_img1.length || !photo_img2.length) {
					$('.JS_submit_change').attr('disabled', 'disabled').css("background-color","rgb(217,217,217)");
				} else {
					$('.JS_submit_change').removeAttr('disabled', 'disabled').css("background-color","rgb(23,127,222)");
				}
			}
    }
    updatebtn()
    var url = window.location.href.split("?")[0].split("/").reverse()[0]
    if(url == "EditHouse"){
      /* 修改房产*/
    if(!page1 || !page2){
      updatebtn()
    }else{
      $(".add").eq(0).html('<img src="' + page1 + '"class="j_small_img">').show();
      $(".add").eq(1).html('<img src="' + page2 + '"class="j_small_img">').show();
      photo_img1 = $(".add").find(".j_small_img")
      photo_img2 = $(".add").find(".j_small_img")
      updatebtn()
    }
    }else if(url == "CheckUser"){
      /* 填写个人信息 */
      if(!id_page1 || !id_page2){
        updatebtn()
      }else{
        $(".person").eq(0).html('<img src="' + id_page1 + '"class="j_small_img">').show();
        $(".person").eq(1).html('<img src="' + id_page2 + '"class="j_small_img">').show();
        photo_img1 = $(".person").find(".j_small_img")
        photo_img2 = $(".person").find(".j_small_img")
        updatebtn()
      }
    }
		$(".first_city_area .second_text").on('input',function(){
      detail_address = $(this).val()
      detail_address = detail_address ? detail_address.replace(reg,'') : ""
      updatebtn()
      P2PWAP.cache.set("_second_text_val_",detail_address,60000)
    })
    var uploadConfig = {
        _switch: "html5", //上传方式选择 html5 flash normal
        datatype: "json", //返回值类型
        template: {
            "html5": '<div class="upfile_root">'
            + '<div class="upfile_c">'
            + '<a class="upfile_word" href="#"></a>'
            + '<input id="Filedata" class="ui_file" type="file" name="file" accept="image/*" capture="camera"/>'
            + ''
            + '</div>'
            +'</div>'
        },
        onsuccess: function(ele, res) { //上传成功回调
          if(!res.data.imagejson){
            P2PWAP.ui.toast("请上传图片")
          }else{
            var cls = ele.parents('.content_h5').attr("data-img");
            var $imgEl = $('.' + cls);
            if(cls == "myimg_file1"){
              P2PWAP.cache.set("_myimg_file1_src_",res.data.url,60000)
              P2PWAP.cache.set("_myimg_file1_id_",res.data.image_id,60000)
            }else{
              P2PWAP.cache.set("_myimg_file2_src_",res.data.url,60000)
              P2PWAP.cache.set("_myimg_file2_id_",res.data.image_id,60000)
            }
            $imgEl.html('<div class="container_loading"><div class="loading" width="0.2rem" height="0.2rem"></div></div>').show()
            $imgEl.addClass("add1")
            $imgEl.siblings().eq(1).hide()
            var uploadImg = new Image();
            uploadImg.onload = function(){
              $imgEl.removeClass("add1")
              $imgEl.siblings().eq(1).show()
              $imgEl.html('<img src="' + res.data.url + '"class="j_small_img">').show();
              /* 判断数据的完整性 */
              photo_img1 = $(".mying1").find(".j_small_img"),
              photo_img2 = $(".mying2").find(".j_small_img"),
              selected_city_val = $(".selected_city").html() == "请选择" ? "" : $(".selected_city").html(),
              detail_address = $(".first_city_area .second_text").val()

              if(cls == "myimg_file1"){
                  first_img_id = res.data.image_id
              } else {
                  second_img_id = res.data.image_id
              }

              detail_address = detail_address ? detail_address.replace(reg,'') : ""
              updatebtn();
            }
            uploadImg.src = res.data.url;
          }
        },
        upload_url: "/user/UploadImage", //上传地址url
        //文件类型限制
        type: "'jpeg|jpg|png|gif'", //允许上传类型
        // size : 1 * 1024 * 1024, // 1M 单个文件大小限制
        size: 10 * 1024 * 1024, // 10M 单个文件大小限制
        post_params: {
            "test": "html5",
            "token": token1,
            "asAttach":1,
            "asPrivate":1
        }, //post参数
        //begin处理样式
        begin: function(ele) {
          var word = ele.find('.upfile_c');
          var link = word.find('.upfile_word')[0];
          if (!link.getAttribute('_init')) {
            link.setAttribute("_init", link.innerHTML);
            link.innerHTML = '';
          }
          word.addClass('upfiling');
        }
    }
    Firstp2p.upload($('.file1'), uploadConfig);
    Firstp2p.upload($('.file2'), uploadConfig);
    // 提交审核
    $('.btn').on('click', '.JS_submit_change', function() {
			$(".btn .JS_submit_change").css("background-color","rgb(217,217,217)").attr("disabled",true)
			var mying1 = $(".mying1 img").attr("src"),
			mying2 = $(".mying2 img").attr("src"),
			selected_city_val = $(".house_property_city .selected_city").html(),
      detail_address = $(".first_city_area .second_text").val()
      detail_address = detail_address ? detail_address.replace(reg,'') : ""
			if(first_city_area.length > 0){
				$.ajax({
          url:"/house/SaveHouse",
          type:"post",
          data:{
              token:token1,
              house_city:selected_city_val,
              house_district:$(".first_city_area .first_text").val(),
              house_address:$(".first_city_area .second_text").val(),
              house_deed_first:first_img_id,
              house_deed_second:second_img_id,
              id:id
          },
          dataType:"json",
          success:function(data){
						if(typeof(data.data) == "number"){
							window.location.href = "/house/PreApply?token="+token1+"&house_id="+data.data+"&selectedCity="+selectedCity
						}else{
							window.location.href = "/house/HouseList?token="+token1
            }
          }
        })
			}else{
        $(".loan_record_popup").css("display","block")
        $.ajax({
          url:"/house/DoApply",
          type:"post",
          data:{
            token:token1,
            usercard_front:first_img_id,
            usercard_back:second_img_id,
            borrow_money:P2PWAP.cache.get('_money_amount_'),
            borrow_deadline_type:borrow_deadline_type,
            payback_mode:payback_mode,
            house_id:house_id,
            annualized:annualized
          },
          success:function(res){
            $(".loan_record_popup").css("display","none")
            window.location.href = "/house/Result?token="+token1+"&result="+res.data
          },
          error:function(err){
            P2PWAP.ui.toast("error",err)
          }
        })
			}
    });
});