;(function($) {
    $(function() {
        $('#int-ball,#int-renyuan').val("");
        $("#postForm").validator({
            fields: {
                'int-ball': '球队:int-ball;required1;',
                'int-renyuan': '队员:int-renyuan;required2;',
                'realName': '真实姓名: required;chinese;',
                'int-phone': '电话:required;mobile;',
            }
        });
        var imgSrc =   function (src, fn) {
                var image = new Image;
                image.onload = function() {
                    !!fn && fn(this);
                }
                image.src = src;
         }
         imgSrc("/static/worldcup/images/ball.png" , function(that){
               $(that).height(39);
               $('#animate').append(that).addClass('a-bounceinT');
         });
    })
    $(function() {
        $(".qiudui li").click(function() {
            $(this).addClass('qd-active').siblings().removeClass('qd-active');
            var txt = $('.qd-active .qd-you').text();
            $('#int-ball').val(txt);
            //$('#postForm').trigger("validate");
        })

        $(".renyuan li").click(function() {
            $(this).addClass('qd-active').siblings().removeClass('qd-active');
            var txt = $('.qd-active p').text();
            $('#int-renyuan').val(txt);
            //$('#postForm').trigger("validate");

        })
    })
})(jQuery);