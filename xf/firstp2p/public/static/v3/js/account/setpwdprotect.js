(function($) {
    $(function() {
        var $_ipt_slt = $(".ipt_select");
        $_ipt_slt.focus(function() {
            var $t = $(this);
            $(".pwp_select").hide();
            $(".j_select_li").removeClass("zindex");
            $t.parent().find(".pwp_select").show();
            $t.parent().addClass("zindex");
        });
        $(".j_select_li li").click(function(evt) {
            evt.stopPropagation();
            var $t = $(this);
            $p = $t.closest(".j_select_li"),
                $input = $p.find(".ipt_select");
            if ($t.index() != 0) {
                $input.val($t.html());
                $input.trigger("validate");
            } else {
                return false;
            }
            //$p.find(".pwp_select").hide();
        });


        $_ipt_slt.blur(function(evt) {
            evt.stopPropagation();
            var $t = $(this);
            setTimeout(function() {
                $t.parent().find(".pwp_select").hide();
            }, 250);
        });

        $('#passForm').validator({
            focusInvalid: false,
            messages: {
                required: "请填写{0}",
                length: "{0}为1-20位常用字符",
                match: "不可使用相同的{0}",
                ansNeq: "答案与密保问题不能相同"
            },
            rules: {
                length: function(el) {
                    return /^[,，。：\.\-\"\“\”\(\)（）A-Za-z\u0391-\uFFE5\d\u0020]{1,20}$/.test(el.value);
                },
                ansNeq: function(el) {
                    var boolObj = false,
                        $el = $(el);
                    if ($el.attr("name") == 'answer1' && $el.val() != $("#ques1").val()) {
                        boolObj = true;
                    } else if ($el.attr("name") == 'answer2' && $el.val() != $("#ques2").val()) {
                        boolObj = true;
                    } else if ($el.attr("name") == 'answer3' && $el.val() != $("#ques3").val()) {
                        boolObj = true;
                    }
                    return boolObj;
                }
            },
            fields: {
                'answer1': '答案:required;length;ansNeq;',
                'answer2': '答案:required;length;ansNeq;',
                'answer3': '答案:required;length;ansNeq;',
                'ques1': '密保问题:required;length;match[neq,ques2];match[neq,ques3];',
                'ques2': '密保问题:required;length;match[neq,ques1];match[neq,ques3];',
                'ques3': '密保问题:required;length;match[neq,ques1];match[neq,ques2];'
            },
            valid: function(form) {
                var combineStr = function($obj1, $obj2) {
                        return $obj1.val() + "^" + $obj2.val();
                    },
                    obj = {
                        answer1: combineStr($("#ques1"), $("#answer1")),
                        answer2: combineStr($("#ques2"), $("#answer2")),
                        answer3: combineStr($("#ques3"), $("#answer3"))
                    };
                
                $.ajax({
                    type: "post",
                    dataType: "json",
                    data: obj,
                    url:  $(form).attr("action"),
                    success: function(data) {
                        if (data.errorCode == 0) {
                            location.href = data.url;
                        } else {
                            if(!!data.errorData){
                                $.each(data.errorData , function(i , v){
                                    $(form).validator("showMsg","#" + i ,{
                                        "type" : "error",
                                        "msg"  : v
                                    });
                                });
                            }else{
                                Firstp2p.alert({
                                    text : '<div class="tc">'+  data.errorMsg +'</div>',
                                    ok : function(dialog){
                                        dialog.close();
                                    }
                                });
                            }
                        }
                    }
                });


            }
        });
    });
})(jQuery);