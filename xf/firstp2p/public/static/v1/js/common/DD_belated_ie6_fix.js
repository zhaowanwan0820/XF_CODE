DD_belatedPNG.fix('.leftBt,.rightBt,i.mt, .mt_01, .mt_02, .pro_links i.badge, .pro_links i.badge:hover, .pro_links i.badge_01, .pro_links i.badge_01:hover, .pro_links i.badge_02, .pro_links i.badge_02:hover, .bottle, .bottle_01, .bottle_02, .product_bd2 .icon_car, .product_bd2 .icon_room, .product_bd2 .icon_personal, .product_bd2 .icon_enterprise, .product_bd2 .icon_assets, .product_bd2 .icon_melting,.ico_one, .ico_two, .ico_three, .ico_four, .ico_five, .ico_six, .ico_seven, .ico_eight, .ico_nine, .ico_zero, .ico_point, .ico_comma, .ico_percent, .ico_rung, .ico_asterisk,.product_bd2 .icon_complete,.partner_title i.icon_partner,.left_but, .right_but,.fix_width .m-nav i,.m-information .g-weixin h3 i, img,background, li, i, a, span,h1 ,h2, h3, h4, h5, h6, dl, dt, dd, input, .line_bg,p,a');


$(function() {
        $backToTopEle = $('<a class="backToTop" href="javascript:void(0)"></a>').appendTo($("html")).click(function() {
            $("html, body").animate({
                    scrollTop: 0
                },
                300);
        }).hide();
	})