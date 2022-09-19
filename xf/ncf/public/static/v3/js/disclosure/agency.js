;
(function($) {
    $(function() {
        $("#tabs").goodTab({
            cur: "selected",
            tabLab: ".j_info_tab",
            tabConLab: ".j_tabConLab"
        });
        if (showCount == 5) {
            $(".j_info_tab").addClass("col-sm-2-4");
        } else {
            $(".j_info_tab").addClass("col-sm-" + 12 / showCount);
        }
    });
})(jQuery);