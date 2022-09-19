(function ($) {
    $(function () {
        $("#tiqian").bind("click", function () {
            $(this).css({ "color": "grey" }).attr("disabled", "disabled");
            if (window.confirm('确认执行提前还款？')) {
                window.location.href = ROOT + '?m=Deal&a=apply_prepay&deal_id=' + $(this).attr("data-id");
            }
            $(this).css({ "color": "#4e6a81" }).removeAttr("disabled");
        });
    });

})(jQuery);