;
(function($) {
    $(function() {
        //本地存储
        $(".sub_nav ul").children().each(function(i, v) {
            $(v).data("index", i + 1);
            var $t = $(this),
                item = "firstclick" + $t.data("index");
            if (window.localStorage && localStorage[item] == 1) {
                $t.find(".new").css("display", "none");
            }
        });
        $(".sub_nav ul").on("click", "li", function() {

            var $t = $(this),
                item = "firstclick" + $t.data("index");

            if (window.localStorage) {

                try {
                    localStorage.setItem(item, "1")
                } catch (err) {
                    location.href = $t.data("href");
                };

            }

            location.href = $t.data("href");
        });
        $('.medal_close').click(function() {
            $('.ui-popup-wrap, .new_medal_box').hide();
        });


    });
})(Zepto);