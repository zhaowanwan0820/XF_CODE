//初始化
;(function($) {
    $(function() {
        var formPost = function(options) {
            var settings = {
                form: $(".j-form-post"),
                msg: "确定要强制还款吗?",
                btText: "已经还款"
            };
            settings = $.extend(settings, options);
            settings.form.each(function() {
                var $t = $(this),
                    $sub = $(this).find("input[type=submit]");
                    $sub.removeAttr("disabled");
                $t.submit(function() {
                    if (confirm(settings.msg)) {
                        $sub.css({
                            "background": "gray"
                        }).attr("disabled", "disabled").val(settings.btText);
                        return true;
                    }
                    return false;
                });
            });

        }
        formPost();
    });

})(jQuery);