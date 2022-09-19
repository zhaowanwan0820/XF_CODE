/*
author : mabaoyue

tab click or hover;
 cur：当前滑签状态；
 noneClass : 隐藏的class
 tagPos : 第几个滑签要显示
 hideFirst : 是否一开始为隐藏状态，默认为不隐藏
 tab ： 点击下拉框对象
 tabCon ： 下拉框内容对象

*/
(function($) {
    $.fn.select = function(options) {
        var settings = {
            evt: "click",
            cur: "",
            noneClass: "none",
            tagPos: 0,
            hideFirst: false,
            hoverClass: 'hover',
            listLabel: "li",
            docClick: '',
            tab: ".j_select",
            tabCon: ".j_selectContent",
            onSelectChange: function() {},
            onItemClick: function() {},
            onSelectDropDown: function() {}
        };
        $.extend(settings, options);
        return this.each(function() {
            var $self = $(this),
                cur = settings.cur,
                tab = settings.tab,
                tabCon = settings.tabCon,
                $tab = $self.find(tab),
                evt = settings.evt,
                tagPos = settings.tagPos,
                noneClass = settings.noneClass,
                $tabCon = $self.find(tabCon),
                $allCon = $("body").find(tabCon).not($tabCon),
                $tabConList = $tabCon.find(settings.listLabel),
                hoverClass = settings.hoverClass,
                $input = $('<input type="hidden" name="' + $self.data("name") + '"  id="' + $self.data("name") + '"/>'),
                tabIsInput = false,
                data_select = !1;
            //$self.data("clickEvent" , 0); 

            if (!$tab.length || !$tab) return;
            if ((/input/i).test($tab[0].tagName)) {
                tabIsInput = true;
            }
            $self.prepend($input);
            $tabCon.addClass(noneClass);
            $tab.removeClass(cur);

            $tab.on(evt, function(e) {
                e.stopPropagation();
                $self.data("clickEvent", 1);
                $allCon.addClass(noneClass);
                $tabCon.toggleClass(noneClass); !! settings.onSelectDropDown && settings.onSelectDropDown();
                return false;
            });
            if ( !! tabIsInput) {
                str = $tab.val();
                $tab.val($tabConList.eq(0).text());
            } else {
                str = $tab.html();

                $tab.html($tabConList.eq(0).text());
            }
            $input.val($tabConList.eq(0).data("value"));
            $tabConList.each(function(){
                 if($(this).data("select")){
                     $tab.html($(this).text());
                     $input.val($(this).data("value"));
                 }
            });

            $tabConList.on(evt, function(e) {

                e.stopPropagation();
                var $t = $(this),
                    str = "",
                    index = $tabConList.index($t);
                if ( !! tabIsInput) {
                    str = $tab.val();
                    $tab.val($t.text());
                } else {
                    str = $tab.html();
                    $tab.html($t.text());
                }
                $input.val($t.data("value"));
                $tabCon.toggleClass(noneClass);
                if ($t.text() == str) {
                    return;
                }
                settings.onSelectChange($self, $input, index); !! settings.onItemClick && settings.onItemClick($self, $input, index);
                return false;
            }).hover(function() {
                $(this).addClass(hoverClass).siblings().removeClass(hoverClass);
            }, function() {
                $(this).removeClass(hoverClass);
            });

            $(document).on(evt, function(e) {
                $tabCon.addClass(noneClass);
            });
        });
    }
})(jQuery);