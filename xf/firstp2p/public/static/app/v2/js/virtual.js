(function() {
    var keyhtml, input_html = "",
        $thisinput, newvalue = "";
    var virtualKey = function(element, opts) {
        var _this = this;
        var options = {
            delayHiden: function() {},
            focusFn: function() {}
        }
        $.extend(options, opts);
        _this.options = options;
        //定义键盘数组
        var keydata = [1, 2, 3, 0, 4, 5, 6, '删除', 7, 8, 9, '确定'];
        //初始化
        _this.init(keydata);
        if (!$(element).length) {
            return;
        } else {
            $(element).append(input_html);
        }
        if ($(".key_ul").length == 0) {
            $("body").append(keyhtml);
        }
        //点击事件
        _this.keyboardshow();
        _this.keyboardhide();
        _this.kbAddvalue();
        $(".key_bg").bind("tap", function() {
            _this.keyboardhide();

        });
    };

    virtualKey.prototype = {
        //初始化
        init: function(keydata) {
            //绘制键盘展示
            keyhtml = "<div class=\"key_ul\"><div class=\"key_bg disnone\"></div>";
            keyhtml += "<ul class=\"clearfix\">";
            keyhtml += "  <li data-key=1><span>1</span></li>";
            keyhtml += "  <li data-key=2><span>2</span></li>";
            keyhtml += "  <li data-key=3><span>3</span></li>";
            keyhtml += "  <li data-key=4><span>4</span></li>";
            keyhtml += "  <li data-key=5><span>5</span></li>";
            keyhtml += "  <li data-key=6><span>6</span></li>";
            keyhtml += "  <li data-key=7><span>7</span></li>";
            keyhtml += "  <li data-key=8><span>8</span></li>";
            keyhtml += "  <li data-key=9><span>9</span></li>";
            keyhtml += "  <li data-key=.><span>.</span></li>";
            keyhtml += "  <li data-key=0><span>0</span></li>";
            keyhtml += "  <li data-key=\"hide\"><span><i class=\"bg_hide\"></i></span></li>";
            keyhtml += "</ul>";
            keyhtml += " <div class=\"key_right\">";
            keyhtml += "<div class=\"key_del\" data-key=\"del\"><span><i class=\"bg_del\"></i></span></div>";
            keyhtml += "<div class=\"key_sure\" data-key=\"sure\"><span>确定</span></div>";
            keyhtml += "</div>";
            keyhtml += "</div>";

            //绘制输入框
            input_html += "<span class=\"inp_main\">";
            input_html += "<span class=\"btn_key\"></span>";
            input_html += "<i class=\"disnone\">&nbsp;</i>";
            input_html += " <span class=\"inp_text\">" + $(".pl_tip").html() + "</span>";
            input_html += "</span>";
        },
        //键盘弹出
        keyboardshow: function() {
            var _this = this;
            var $inp_main = $(".key_input"),
                $key_ul = $(".key_ul");

            $inp_main.bind("tap", function() {
                _this.options.focusFn();
                $inp_main.find("i").addClass("disnone");
                $(this).find("i").removeClass("disnone");
                $(".key_bg").removeClass("disnone");
                $(this).find(".inp_text").addClass("disnone");
                if (!$key_ul.hasClass('show')) {
                    $key_ul.addClass('show');
                }
                // if (!$key_ul.find("li").hasClass("msg_show")) {
                //     $key_ul.find("li").removeClass("msg_hide").addClass("msg_show");
                //     $(".key_del").removeClass("btn_hide").addClass("btn_show");
                //     $(".key_sure").removeClass("btn_hide").addClass("btn_show");
                //     $(".key_right").addClass("mdiv_show");
                // }
                $thisinput = $(this).find(".btn_key");
                newvalue = $thisinput.html();
            });
        },
        //键盘隐藏 
        keyboardhide: function() {
            var _this = this;
            var $key_ul = $(".key_ul");
            var $key_btn = $(".btn_key");
            var keyVal;
            //$(".key_ul").addClass("disnone");
            $(".key_bg").addClass("disnone");
            if ($key_ul.hasClass('show')) {
                $key_ul.removeClass('show');
            }
            // if ($(".key_ul").find("li").hasClass("msg_show")) {
            //     $(".key_ul").find("li").removeClass("msg_show").addClass("msg_hide");
            //     $(".key_del").removeClass("btn_show").addClass("btn_hide");
            //     $(".key_sure").removeClass("btn_show").addClass("btn_hide");
            //     $(".key_right").removeClass("mdiv_show");
            // }
            $(".inp_main").find("i").addClass("disnone");
            $key_btn.each(function() {
                if ($(this).html().length == 0) {
                    $(this).parent().find(".inp_text").removeClass("disnone");
                }
            });
            keyVal = $key_btn.html();
            if (keyVal.slice(-1) == '.') {
                keyVal = keyVal.slice(0, -1);
                $key_btn.html(keyVal);
            }
            setTimeout(function() {
                _this.options.delayHiden();
            }, 250);
        },
        //点击赋值
        kbAddvalue: function() {
            var _this = this;
            var $key_ul = $(".key_ul"),
                keyboard_key;
            $key_ul.find("ul>li").on("touchstart", function() {
                $(this).addClass("tap_color").siblings().removeClass('tap_color');
            });
            $key_ul.find("ul>li").bind("touchend", function() {
                $(this).removeClass("tap_color");
                keyboard_key = $(this).attr("data-key");
                if (keyboard_key == "del") {
                    newvalue != "" ? newvalue = newvalue.substring(0, newvalue.length - 1) : null;
                    $thisinput.html(newvalue);
                } else if (keyboard_key == "sure") {
                    _this.keyboardhide();
                } else if (keyboard_key == "hide") {
                    _this.keyboardhide();
                } else {
                    var sval = $(this).attr("data-key");
                    if (sval == "." && newvalue.indexOf(".") == "-1") {
                        newvalue += sval;
                    }
                    if (/^(([0-9]|([1-9][0-9]{0,9}))((\.[0-9]{1,2})?))$/.test(newvalue + sval)) {
                        newvalue += sval;
                    }
                    $thisinput.html(newvalue);
                }
            });
            $(".key_right>div").on("touchstart", function() {
                $(this).addClass("tap_color").siblings().removeClass('tap_color');

            });
            $(".key_right>div").bind("touchend", function() {
                $(this).removeClass("tap_color");
                keyboard_key = $(this).attr("data-key");
                if (keyboard_key == "del") {
                    newvalue != "" ? newvalue = newvalue.substring(0, newvalue.length - 1) : null;
                    $thisinput.html(newvalue);
                } else {
                    _this.keyboardhide();
                }
            });
            ////长按删除所有
            $(".key_del>span").bind("longTap", function() {
                newvalue = "";
                $thisinput.html("");
            });
            $(".bg_del").bind("longTap", function() {
                newvalue = "";
                $thisinput.html("");
            });
        }
    }
    window.virtualKey = virtualKey;
})();