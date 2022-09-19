(function($) {
    $(function () {
        var btnstate = 0;
        setTimeout(function(){
            $('.ui-pop').height(document.documentElement.clientHeight);
            // 刷新解码状态
            decodeState();
            btn_change();
        },100);

        /*签署*/
        $(".arr_div").bind("click", function () {
            $(this).find(".arr_state").toggleClass("arrow_yes");
            if (!$(this).find(".arr_state").hasClass("arrow_yes")) {
                $(this).find(".arr_state").removeClass("arrow_yes");
            }
            encodeState();
            btn_change();
        });


        /*判断按钮*/
        function btn_change() {
            //先判断是否输入金额
            $(".arr_state").each(function() {
                if ($(this).hasClass("arrow_yes")) {
                    btnstate++;
                } else {
                    btnstate--;
                }
            });
            if (btnstate == $(".arr_state").length) {
                if (fixMerry()) {
                    $(".int_btn").removeClass("int_gay");
                }
            } else {
                $(".int_btn").addClass("int_gay");
                btnstate = 0;
            }
        }

        //加cookie
        var dealcookie = "wx_jijinphone_cookie";
        /***************** 添加cookie *********************/
        function _addCookie(name, value, second) {
            var exdate = new Date((new Date()).getTime() + second * 1000);
            document.cookie = name + "=" + escape(value) + ";path=/" +
                ((second == null) ? "" : ";expires=" + exdate.toGMTString());
        }

        /***************** 获取cookie *********************/
        function _getCookie(c_name) {
            if (document.cookie.length > 0) {
                var c_start = document.cookie.indexOf(c_name + "=");
                if (c_start != -1) {
                    c_start = c_start + c_name.length + 1;
                    var c_end = document.cookie.indexOf(";", c_start);
                    if (c_end == -1) c_end = document.cookie.length;
                    return unescape(document.cookie.substring(c_start, c_end));
                }
            }
            return "";
        }
        // 记录点击状态
        function encodeState() {
            var state = '';
            var val = $('.jijin_money').val();
            state += val;
            for (var i = 1; i < 5; i++) {
                if ($('.arr_state' + i).hasClass('arrow_yes')) {
                    state += '/1';
                } else {
                    state += '/0';
                }
            }
            _addCookie('recordState', state, 30 * 60);
        }

        // 解码点击状态
        function decodeState() {
            var state = _getCookie('recordState');
            var stateArr = [];
            if (!state) return;
            stateArr = state.split('/');
            $('.jijin_money').val(stateArr[0]);
            for (var i = 1; i < 5; i++) {
                if (stateArr[i] == 1) $('.arr_state' + i).addClass('arrow_yes');
            }
        }
    });
})(Zepto);