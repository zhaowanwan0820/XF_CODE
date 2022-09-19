/*
 author: baoyue@leju.sina.com.cn
 date:  2011-12-01
 */

/*
 tab click or hover;
 cur：当前滑签状态；
 noneClass : 隐藏的class
 tagPos : 第几个滑签要显示
 hideFirst : 是否一开始为隐藏状态，默认为不隐藏
 tabLab ： 滑签的对象
 tabConLab ： 滑签内容的对象
 */
;(function($) {
    $.fn.goodTab = function(options) {
        var settings = {
            evt: "click",
            cur: "current",
            noneClass: "none",
            tagPos: 0,
            hideFirst: false,
            ajaxImg: false,
            returnFalse: true,
            ajaxImgFuc: function() {},
            clickEvent: "",
            tabLab: ".tab",
            tabConLab: ".tabContent"
        };
        if ( !! options) {
            $.extend(settings, options);
        }
        return this.each(function() {
            var $self = $(this),
                cur = settings.cur,
                $tab = $self.find(settings.tabLab) || $self.find(settings.tabLab.toLowerCase()),
                evt = settings.evt,
                tagPos = settings.tagPos,
                noneClass = settings.noneClass,
                $tabCon = $self.find(settings.tabConLab) || $self.find(settings.tabConLab.toLowerCase()),
                ajaxImg = settings.ajaxImg,
                returnFalse = settings.returnFalse;
            if (!$tab || !$tab.length) return;
            $tabCon.addClass(noneClass);
            $tab.removeClass(cur);
            $tab.each(function(i, v) {
                if ($(v).data("pos")) {
                    tagPos = i;
                }
            });
            $tabCon.eq(tagPos).removeClass(noneClass);
            $tab.eq(tagPos).addClass(cur);
            if (evt != 'hover') {
                $tab.bind(evt, function() {
                    var index = $tab.index($(this));
                    justShow($(this));
                    ret($(this)); 
                    !! settings.clickEvent && settings.clickEvent($(this) , index);
                    if ( !! returnFalse) {
                        return false;
                    } else {
                        return true;
                    }
                });
            } else {
                $tab.hover(function() {
                    justShow($(this));
                }).click(function() {
                    ret($(this));
                });
            }


            function justShow(t) {
                if ( !! !ajaxImg) {
                    doShow(t);
                } else {
                    settings.ajaxImgFuc($tab, $tabCon, t, cur, noneClass);
                }

            }

            function ret(obj) {
                if (obj.attr("href") === '#') {
                    return false;
                }
            }

            function doShow(object) {
                var index = $tab.index(object);
                if ($tab.length === 1 || $tab.eq(0).parent()[0] == $tab.eq(1).parent()[0]) {
                    $tab.each(function(i) {
                        $(this).removeClass(cur);
                        if (i == index) {
                            $(this).addClass(cur);
                        }
                    });
                } else if ($tab.eq(0).parent() !== $tab.eq(1).parent()) {
                    $tab.eq(index).addClass(cur).parent().siblings().find($tab).removeClass(cur);
                }
                $tabCon.addClass(noneClass).eq(index).removeClass(noneClass);
            }
        });
    }
})(jQuery);