(function($) {
     $(function() {

          // 多重下拉列表
          new Firstp2p.mulselect(".cityDom", {
               mulDom: ".cityDom",
               defaultdata: !!$("#cityDom").data("defaultdata") ? $("#cityDom").data("defaultdata").split(":") : ["请选择省", "请选择市", "请选择县"],
               selectsClass: "select",
               url: json,
               jsonsingle: "n",
               jsonmany: "s"
               //selectName : $("#cityDom").data("name")
          });
          var justify = function(){
             var $pro = $("select[name='province']"),
             $city = $("select[name='city']"),
             $area = $("select[name='areaA']"),
             $p = $pro.closest('li'),
             $wrong = $p.find(".n-error"),
             $right = $p.find(".n-ok"),
             len = $(".select").length,
             str = "所在地区不能为空",
             show = function($obj,str){
                $obj.find(".n-msg").html(str);
                $obj.show().siblings().hide();
             }
             if($pro.find("option:selected").val() == 0){
                show($wrong , str);
                return false;
             }
             if($city.find("option:selected").val() == 0){
                show($wrong , str);
                return false;
             }

             if($area.find("option:selected").val() == 0){
                show($wrong , str);
                return false;
             }
             show($right , "");
             return true;

          };

          $("#cityDom").on("change" , "select" , function(){
                justify();

          });

          //placehoder
          $(".int_placeholder").each(function() {
               var p_text = $(this).attr("data-placeholder");
               new Firstp2p.placeholder(this, {
                    placeholder_text: p_text == null ? "请输入" : p_text
               });
          });

          function popup(str) {
             var word = (!!str ? str : '正在提交,请稍后...');
             var html ='';
                  html += '<div class="wee-send">';
                  html += '<div class="send-input">';
                  html += '<div class="error-box">';
                  html += '<div class="error-wrap">';
                  html += '<div class="e-text" >'+ word +'</div>';
                  html += '</div>';
                  html += '</div>';
                  html += '<p></p>';
                  html += '</div>';
                  html += '</div>';
              if($('.weedialog .dialog-content').length <= 0){
                  $.weeboxs.open(html, {
                      boxid: null,
                      boxclass: 'weebox_send_msg',
                      showTitle : true,
                      contentType: 'text',
                      showButton: false,
                      showOk: true,
                      okBtnName: '完成注册',
                      showCancel: false,
                      title: '提交表单',
                      width: 250,
                      height: 120,
                      type: 'wee'
                   });
              }else{
                  $('.weedialog .dialog-content .e-text').html(word);
              }
          }


          $('#delivery').validator({
              rules: {
                  userName : [/^[A-Za-z\u0391-\uFFE5]{2,25}$/ , '请输入2-25个字符，限汉字或字母'],
                  mobile : [/^1[3456789]\d{9}$/ , '手机号格式不正确'],
                  address : [/^[,，。：\.\-\"\“\”\(\)（）A-Za-z\u0391-\uFFE5\d\u0020]{5,80}$/ , '请输入5-80个常用字符'],
                  postalcode : [/^\d{6}$/ , '请输入6位数字'],
                  phonecode : [/^\d{6}$/ , '请输入6位数字'],
              },
              fields:{
                  name : "收货人姓名:required;userName",
                  mobile : "手机号: required;mobile;",
                  address : "详细地址: required;address;",
                  postalcode : "postalcode;",
                  phonecode : "手机验证码 : required;phonecode;",
              },
              isNormalSubmit: true,
              valid: function(form){
                  var $f = $(form),
                  areaStr = "",
                  len = $("#cityDom .select").length;
                  if(!justify()){
                     return false;
                  }
                  $("#cityDom .select").each(function(i , v){
                     if(i != len - 1){
                        areaStr += v.value + ":";
                     }else{
                        areaStr += v.value;
                     }

                  });

                  $("#area").val(areaStr);

                  // var dataObj = {
                  //    "name" : $("#name").val(),
                  //    "mobile" : $("#mobile").val(),
                  //    "area" : $("#area").val(),
                  //    "address" : $("#address").val(),
                  //    "postalcode" : $("#postalcode").val(),
                  //    "sp" : 2,
                  //    "isAjax" : 1
                  // };

                  //this.holdSubmit(true);
                  // $.ajax({
                  //    url : $f.attr("action"),
                  //    data : dataObj,
                  //    dataType: "json",
                  //    type: "post",
                  //    beforeSend: function(){
                  //       popup();
                  //    },
                  //    success :function(data){
                  //       if(data.errorCode === 0){
                  //           popup("表单提交成功");
                  //           setTimeout(function(){
                  //               location.href = data.redirect;
                  //           },2000);

                  //       }else{
                  //           popup("表单提交失败，请重新提交");
                  //       }
                  //    }

                  // });

                  //console.log($f.attr("action"));
                  //$f.action = $f.attr("action");
                  //form.action = "http://www.firstp2p.com";
              },
              invalid : function(form){
                  var $f = $(form);
                  if(!justify()){
                     return false;
                  }
                  //$f.action = $f.attr("action");
              }
          });



     });
})(jQuery);