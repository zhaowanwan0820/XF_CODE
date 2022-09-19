define(function (require, exports, module) {
    require("../css/m-base.css");
    require("../css/m-common.css");
    (function($) {
        $(function() {
            (function() {
                var $navli = $("#menu li"),
                    $write = $(".invf_write"),
                    lisum = $("#menu li").width(),
                    ulsum = $("#menu").width(),
                    selsum = $("#menu").find(".select span").width(),
                    jiange = ((lisum - selsum) / 2),
                    selindex = 0;
                $write.css({ width: selsum, left: jiange });
                $navli.bind("click", function() {
                    $(this).addClass("select").siblings().removeClass("select");
                    var index = $navli.index(this);
                    $(".invf_txt>div").eq(index).show()
                        .siblings().hide();


                    //获取当前选中的select的宽度
                    var newselsum = $(this).find(".select span").width(),
                        indesum = ((lisum - newselsum) / 2),
                        //实际间隔
                        j_sum = index - selindex;
                    if (index < selindex) {
                        j_sum = selindex - index;
                    }
                    //条形宽度
                    var juli = j_sum * lisum - jiange + newselsum + indesum,
                        //left或right
                        marginsum = index * lisum + indesum,
                        leftsum = selindex * lisum + jiange,
                        bianju = ($navli.length - selindex - 1) * lisum + jiange,
                        //稍微有点对不齐多加5PX
                        rightjuli = ($navli.length - 1 - index) * lisum + indesum+5;


                    $write.css({ width: "0px" });
                    if (index < selindex) {
                        $write.css({ right: bianju, left: 'auto' });
                        $write.animate({ width: juli }, {
                            duration: 150,
                            complete: function() {
                                $write.animate({ right: rightjuli, width: newselsum }, 150);
                                selindex = index;
                                jiange = indesum;
                            }
                        });
                    } else {
                        $write.css({ left: leftsum, right: 'auto' });
                        $write.animate({ width: juli }, {
                            duration: 150,
                            complete: function() {
                                $write.animate({ left: marginsum, width: newselsum }, 150);
                                selindex = index;
                                jiange = indesum;
                            }
                        });
                    }

                });
            })();
        });

    })(Zepto);
});