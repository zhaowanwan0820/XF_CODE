$(function(){
    var isRegister = window["_isRegister_"];
    var isInvest = window["_isInvest_"];
    var isInvite = window['_isInvite_'];
    var point_img = '<div class="point_img"></div>';
    if(isRegister ==1){
        $(".JS_step_one").addClass('step_num_active');
        $(".JS_name_one").addClass('step_name_active');
        $(".register_success").show();
    }else{
        $(".go_register_btn").show();
    }
    if(isInvest == 1){
        $(".JS_step_two").addClass('step_num_active');
        $(".JS_name_two").addClass('step_name_active');
    }
    if(isInvite == 1){
        $(".JS_step_three").addClass('step_num_active');
        $(".JS_name_three").addClass('step_name_active');
    }
    /***************** 设置cookie *********************/

    function _setCookie(name, value, time, opt_domain) {
        var str1 = time.substring(1, time.length) * 1;
        var str2 = time.substring(0, 1);
        var str_time;
        if (str2 == "s") {
            str_time= str1 * 1000;
        }
        else if (str2 == "h") {
            str_time = str1 * 60 * 60 * 1000;
        }
        else if (str2 == "d") {
            str_time = str1 * 24 * 60 * 60 * 1000;
        }

        var exp = new Date();
        var domainStr = opt_domain ? ';domain=' + opt_domain : '';
        exp.setTime(exp.getTime() + str_time * 1);
        document.cookie = name + "=" + escape(value) + domainStr + ";path=/" + ";expires=" + exp.toGMTString();
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

    var _INVEST_LIST_TIME_ = '_invest_list_time_';
    
    var IS_time_out = _getCookie(_INVEST_LIST_TIME_);
    if(IS_time_out && IS_time_out < 30){
        //继续上次
        var get_invest_list = '';
        var get_now_class = _getCookie("_now_class_");//上次滚动到的li的类名
        for(var i = 0;i < 30;i++){
            get_invest_list += _getCookie("invest_list_num"+i);
        }
        $(".scroll_list_ul").append(get_invest_list);
        var $this = $(".scroll_list");
        var $self = $this.find("ul");
        for(var i=0;i<get_now_class;i++){
            $self.find("li:first").appendTo($self);
        }
    }else{
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/account/InvestList",
            success: function(json){
                for(var i = 0;i < json.data.length;i++){
                    var list_text = ' <li class ="'+i+'">用户<span class="phone_num">'+json.data[i].mobile+'</span>刚刚投资了<span class="invest_num">'+json.data[i].money+'</span>元</li>'
                    $(".scroll_list_ul").append(list_text);
                    _setCookie("invest_list_num"+i, list_text , "m30");

                }
            }
        });
    }


    $(function() {
        var $this = $(".scroll_list");
        var scrollTimer;
        // $this.hover(function() {
        //     clearInterval(scrollTimer);
        // }, function() {
        //     scrollTimer = setInterval(function() {
        //         scrollNews($this);
        //     }, 2000);
        // }).trigger("mouseleave");
        function scrollNews(obj) {
            var $self = obj.find("ul");
            var lineHeight = $self.find("li:first").height();
            $self.animate({
                "marginTop": -lineHeight + "px"
            }, 600, function() {
            $self.css({marginTop: 0}).find("li:first").appendTo($self);
            })
        }
        var is_count_end = 0;
        scrollTimer = setInterval(function() {
            scrollNews($this);
            is_count_end++;
            _setCookie(_INVEST_LIST_TIME_, is_count_end, "m30");
            //记录离开时的高度
            var now_class = $this.find("ul").find("li:first").attr('class');;
            _setCookie("_now_class_", now_class, "m30");
        }, 1000);
    })

})
        