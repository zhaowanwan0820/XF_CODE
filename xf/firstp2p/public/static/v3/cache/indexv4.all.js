

/*
scroll move
*/
(function($) {
    $.fn.extend({
        scrollView: function(option) {
            var defaultOption = {
                displayNum: 6,
                scrollNum: 1,
                duration: 500,
                continuous: true,
                displayLabel: "li",
                labelParent: "ul",
                autoScroll: false,
                timer: 3000,
                duringScroll:function(){}
            };
            $.extend(defaultOption, option);
            return this.each(function() {
                var $this = $(this),
                    btWrap = defaultOption.btWrap,
                    displayNum = defaultOption.displayNum,
                    duration = defaultOption.duration,
                    timer = defaultOption.timer,
                    displayLabel = defaultOption.displayLabel,
                    labelParent = defaultOption.labelParent,
                    scrollContainer = $this.find(".scrollDiv"),
                    scrollList = scrollContainer.find(">" + labelParent),
                    scrollItem = scrollList.find(">" + displayLabel),
                    scrollItemLen = scrollItem.length,
                    isEnough = scrollItemLen > displayNum,
                    scrollItemWorH = defaultOption.vertical ? scrollItem.outerHeight(true) : scrollItem.outerWidth(true),
                    isMouseDown = false,
                    rightDirection = true,
                    scrollAxis = defaultOption.vertical ? 'top' : 'left',
                    cssWorH = defaultOption.vertical ? 'height' : 'width',
                    scrollNum = defaultOption.scrollNum || 1,
                    startCount = 0,
                    scrollCount = 0,
                    scrollMax = scrollItemLen - displayNum,
                    animateLock = false,
                    autoScroll = defaultOption.autoScroll,
                    lastScroll = scrollMax % scrollNum;
                if ( !! btWrap) {
                        var leftButton = btWrap.find(".scroll_up"),
                            rightButton = btWrap.find(".scroll_down");
                    } else {
                        var leftButton = $this.find(".scroll_up"),
                            rightButton = $this.find(".scroll_down");
                    }
                if (scrollAxis === 'left') {
                        scrollItem.css({
                            "float": "left",
                            "display": "block"
                        });
                        scrollList.css({
                           "left" : 0
                        });
                        scrollContainer.css("height", scrollItem.outerHeight() + 'px');
                }
                scrollContainer.css({
                        "position": "relative",
                        "overflow": "hidden",
                        "display": "block"
                    });
                scrollContainer.css(cssWorH, displayNum * scrollItemWorH + 'px');
                scrollList.css({
                           "position": "absolute"    
                        });
                scrollList.css({
                        cssWorH: scrollItem.length * scrollItemWorH + 'px'
                    });
                _autoScroll();
                if (isEnough) {
                    rightButton.unbind("mousedown mouseup");
                    leftButton.unbind("mousedown mouseup");
                        rightButton.bind('mousedown', function() {
                            clearInterval(autoScroll);
                            _scrollRight(true);
                        }).bind('mouseup', function() {
                            isMouseDown = false;
                            _autoScroll();
                        });
                        leftButton.bind('mousedown', function() {
                            clearInterval(autoScroll);
                            _scrollLeft(true);
                        }).bind('mouseup', function() {
                            isMouseDown = false;
                            _autoScroll();
                        });
                    }
                scrollItem.hover(function() {
                        clearInterval(autoScroll);
                    }, function() {
                        _autoScroll();
                    });

                function _scrollLeft(mouseDown) {
                        if (scrollCount <= 0 || animateLock) return;
                        rightDirection = false;
                        isMouseDown = mouseDown || false;
                        scrollCount -= scrollCount == lastScroll ? lastScroll : scrollNum;
                        _animate(scrollCount,-1);
                    }

                function _scrollRight(mouseDown) {
                        if (scrollCount >= scrollMax || animateLock) return;
                        isMouseDown = mouseDown || false;
                        rightDirection = true;
                        scrollCount += scrollMax - scrollCount == lastScroll ? lastScroll : scrollNum;
                        _animate(scrollCount,1);
                    };

                function _autoScroll() {
                        if (!autoScroll || !isEnough) return; //if the items is less than display item and no auto scroll
                        autoScroll = setInterval(function() {
                            _scrollRight()
                        }, timer);
                    }

                function _animate(count,dir) {
                        scrollCount = count;
                        animateLock = true;
                        var property = {};
                        property[scrollAxis] = '-' + scrollCount * scrollItemWorH + 'px';
                        scrollList.animate(property, duration, function() {
                            animateLock = false;
                            if (defaultOption.continuous) {
                                if (rightDirection && scrollCount >= scrollMax) {
                                    scrollList.css(scrollAxis, '-' + startCount * scrollItemWorH + 'px');
                                    scrollCount = startCount;
                                } else if (scrollCount <= 0) {
                                    scrollList.css(scrollAxis, '-' + scrollItemLen * scrollItemWorH + 'px');
                                    scrollCount = scrollItemLen;
                                }
                            }
                            if (isMouseDown) {
                                if (rightDirection) {
                                    if (scrollCount >= scrollMax) return;
                                    scrollCount += scrollMax - scrollCount == lastScroll ? lastScroll : scrollNum;
                                    _animate(scrollCount);
                                } else {
                                    if (scrollCount <= 0) return;
                                    scrollCount -= scrollCount == lastScroll ? lastScroll : scrollNum;
                                    _animate(scrollCount);
                                }
                            }
                        });
                        defaultOption.duringScroll(count,dir);
                    };
                if (defaultOption.continuous && isEnough) { //if the items is less than display item and do not need continuous
                        _buildHTML();
                    }

                function _buildHTML() {
                        var len = scrollItemLen;
                        var prepandNodes = scrollItem.slice(len - displayNum, len).clone().prependTo(scrollList);
                        var appendNodes = scrollItem.slice(0, displayNum).clone().appendTo(scrollList);
                        len += displayNum * 2;
                        scrollCount = startCount = displayNum;
                        scrollMax += displayNum * 2;
                        var property = {};
                        property[cssWorH] = len * scrollItemWorH + 'px';
                        property[scrollAxis] = '-' + startCount * scrollItemWorH + 'px';
                        scrollList.css(property);
                    };
                if (defaultOption.eventType) { //Determining if need to show the preview
                        scrollList.bind(defaultOption.eventType, function(e) {
                            defaultOption.eventHandler(e);
                        });
                    }
            });
        }
    })
})(jQuery);;/*
 * Lazy Load - jQuery plugin for lazy loading images
 *
 * Copyright (c) 2007-2013 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.appelsiini.net/projects/lazyload
 *
 * Version:  1.9.3
 *
 */

(function($, window, document, undefined) {
    var $window = $(window);

    $.fn.lazyload = function(options) {
        var elements = this;
        var $container;
        var settings = {
            threshold       : 20,
            failure_limit   : 0,
            event           : "scroll",
            effect          : "show",
            container       : window,
            data_attribute  : "src",
            skip_invisible  : true,
            appear          : null,
            load            : null,
            placeholder     : "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAANSURBVBhXYzh8+PB/AAffA0nNPuCLAAAAAElFTkSuQmCC"
        };

        
        if(options) {
            /* Maintain BC for a couple of versions. */
            if (undefined !== options.failurelimit) {
                options.failure_limit = options.failurelimit;
                delete options.failurelimit;
            }
            if (undefined !== options.effectspeed) {
                options.effect_speed = options.effectspeed;
                delete options.effectspeed;
            }

            $.extend(settings, options);
        }

        /* Cache container as jQuery as object. */
        $container = (settings.container === undefined ||
                      settings.container === window) ? $window : $(settings.container);

        /* Fire one scroll event per scroll. Not one scroll event per image. */
        if (0 === settings.event.indexOf("scroll")) {
            $container.bind(settings.event, function() {
                return update();
            });
        }

        this.each(function() {
            var self = this;
            var $self = $(self);
            $self.data("update",update);
            self.loaded = false;

            /* If no src attribute given use data:uri. */
            if ($self.attr("src") === undefined || $self.attr("src") === false) {
                if ($self.is("img") && !$self.hasClass("j_focus_img")) {
                    $self.attr("src", settings.placeholder);
                }
            }

            /* When appear is triggered load original image. */
            $self.one("appear", function() {
                
                if (!this.loaded) {
                    if (settings.appear) {
                        var elements_left = elements.length;
                        settings.appear.call(self, elements_left, settings);
                    }
                    $("<img />")
                        .bind("load", function() {

                            var original = $self.data(settings.data_attribute);
                            $self.hide();
                            if ($self.is("img")) {
                                $self.attr("src", original);
                            } else {
                                $self.css("background-image", "url('" + original + "')");
                            }
                            $self[settings.effect](settings.effect_speed);

                            self.loaded = true;

                            /* Remove image from array so it is not looped next time. */
                            var temp = $.grep(elements, function(element) {
                                return !element.loaded;
                            });
                            elements = $(temp);

                            if (settings.load) {
                                var elements_left = elements.length;
                                settings.load.call(self, elements_left, settings);
                            }
                        })
                        .attr("src", $self.data(settings.data_attribute));
                }
            });

            /* When wanted event is triggered load original image */
            /* by triggering appear.                              */
            if (0 !== settings.event.indexOf("scroll")) {
                $self.bind(settings.event, function() {
                    if (!self.loaded) {
                        $self.trigger("appear");
                    }
                });
            }
        });

        //计算并更新位置
        function update() {
            var counter = 0;

            elements.each(function() {
                var $this = $(this);
                if(!!this.loaded){
                    return;
                }
                if (settings.skip_invisible && !$this.is(":visible")) {
                    return;
                }
                //alert(!$.rightoffold(this, settings));
                if ($.abovethetop(this, settings) ||
                    $.leftofbegin(this, settings)) {
                        /* Nothing. */
                } else if (!$.belowthefold(this, settings) &&
                    !$.rightoffold(this, settings)) {
                        $this.trigger("appear");
                        /* if we found an image we'll load, reset the counter */
                        counter = 0;
                } else {
                    if (++counter > settings.failure_limit) {
                        //return false;
                    }
                }
            });

        }



        /* Check if something appears when window is resized. */
        $window.bind("resize", function() {
            update();
            //alert(111)
        });

        /* With IOS5 force loading images when navigating with back button. */
        /* Non optimal workaround. */
        if ((/(?:iphone|ipod|ipad).*os 5/gi).test(navigator.appVersion)) {
            $window.bind("pageshow", function(event) {
                if (event.originalEvent && event.originalEvent.persisted) {
                    elements.each(function() {
                        $(this).trigger("appear");
                    });
                }
            });
        }

        /* Force initial check if images should appear. */
        $(document).ready(function() {
            update();
        });

        return this;
    };

    /* Convenience methods in jQuery namespace.           */
    /* Use as  $.belowthefold(element, {threshold : 100, container : window}) */

    $.belowthefold = function(element, settings) {
        var fold;
        //alert(window.innerHeight);
        if (settings.container === undefined || settings.container === window) {
            fold = (window.innerHeight ? window.innerHeight : $window.height()) + $window.scrollTop();
            
            //fold = $window.height() + $window.scrollTop();
        } else {
            fold = $(settings.container).offset().top + $(settings.container).height();
        }

        return fold <= $(element).offset().top - settings.threshold;
    };

    $.rightoffold = function(element, settings) {
        var fold;

        if (settings.container === undefined || settings.container === window) {
            //fold = $window.width() + $window.scrollLeft();
            fold = $(document).width() + $window.scrollLeft();
        } else {
            fold = $(settings.container).offset().left + $(settings.container).width();
        }
        
        return fold <= $(element).offset().left - settings.threshold;
    };

    $.abovethetop = function(element, settings) {
        var fold;

        if (settings.container === undefined || settings.container === window) {
            fold = $window.scrollTop();
        } else {
            fold = $(settings.container).offset().top;
        }

        return fold >= $(element).offset().top + settings.threshold  + $(element).height();
    };

    $.leftofbegin = function(element, settings) {
        var fold;

        if (settings.container === undefined || settings.container === window) {
            fold = $window.scrollLeft();
        } else {
            fold = $(settings.container).offset().left;
        }

        return fold >= $(element).offset().left + settings.threshold + $(element).width();
    };

    $.inviewport = function(element, settings) {
         return !$.rightoffold(element, settings) && !$.leftofbegin(element, settings) &&
                !$.belowthefold(element, settings) && !$.abovethetop(element, settings);
     };

    /* Custom selectors for your convenience.   */
    /* Use as $("img:below-the-fold").something() or */
    /* $("img").filter(":below-the-fold").something() which is faster */

    $.extend($.expr[":"], {
        "below-the-fold" : function(a) { return $.belowthefold(a, {threshold : 0}); },
        "above-the-top"  : function(a) { return !$.belowthefold(a, {threshold : 0}); },
        "right-of-screen": function(a) { return $.rightoffold(a, {threshold : 0}); },
        "left-of-screen" : function(a) { return !$.rightoffold(a, {threshold : 0}); },
        "in-viewport"    : function(a) { return $.inviewport(a, {threshold : 0}); },
        /* Maintain BC for couple of versions. */
        "above-the-fold" : function(a) { return !$.belowthefold(a, {threshold : 0}); },
        "right-of-fold"  : function(a) { return $.rightoffold(a, {threshold : 0}); },
        "left-of-fold"   : function(a) { return !$.rightoffold(a, {threshold : 0}); }
    });

})(jQuery, window, document);
;$(function() {


    // banner焦点图
    if (typeof aImg == "undefined" || typeof aHref == "undefined") {
        $('.slide_pager').hide();

    } else {
        var imgLen = aImg.length;
        var curTab = 0;
        var autoFlag;
        // 最多16个
        if(imgLen > 16) imgLen = 16;
        // 添加元素
        for (var i = 0; i < imgLen; i++) {
            $('.banner_view').append('<li class="JS_banner" data-index='+ (i + 1) +' style="background-image:url(' + aImg[i] + ')"><a target="_blank" href="' + aHref[i] + '"></a></li>');

            if(imgLen == 1 || aHref.length == 1){
                $('.banner_view li').eq(0).show();
                $('.slide_pager').hide();

            }else{
                if (i == 0) {
                    $('.banner_view li').eq(0).show();
                    $('.slide_pager ul').append('<li class="active"></li>')
                } else {
                    $('.slide_pager ul').append('<li></li>')
                }
            }

        }
        // 计算导航位置与图片容器宽度
        $('.slide_pager').css('margin-left', $('.slide_pager').width() / 2 * (-1) + 'px');
        // 移动函数
        function switchFoucs(idx) {
            $('.slide_pager ul li').removeClass('active').eq(idx).addClass('active');
            $('.banner_view li').stop().fadeOut(500).css('z-index','0').eq(idx).stop().fadeIn(500).css('z-index','1');
        }

        // 自动函数
        function autoAni() {
            curTab++;
            if (curTab >= imgLen) curTab = 0;
            switchFoucs(curTab);
        }

        function setAutoAni() {
            autoFlag = setInterval(function() {
                autoAni(curTab);
            }, 5000);
        }

        // 默认初始滚动
        setAutoAni();

        function clearAutoAni() {
            clearInterval(autoFlag);
        }


        // 绑定事件
        $('.slide_pager ul li').mouseover(function(event) {
            curTab = $(this).index();
            switchFoucs(curTab);
        });
        $('.banner_slide').mouseover(function() {
            clearAutoAni();
        });
        $('.banner_slide').mouseout(function() {
            setAutoAni();
        });
    }
    // 合作伙伴
    $("#scroll").scrollView({
            displayNum: 1,
            scrollNum: 1
        }).find("img").lazyload({
        effect: "fadeIn"
    });
    $("#scroll .scroll_up , #scroll .scroll_down").click(function() {
        $(this).parent().find("img").trigger("appear");
    });



    // tab切换
    // $("#tabs").tabs();
    (function() {
        var obj = {};
        $('#tabs').goodTab({
            cur: "current",
            tabLab: ".j_index_tab",
            tabConLab: ".ui_product_tab"

        });
    })();

    //项目进度
    progress_rate($('.progress_rate'));
    function progress_rate(ele) {
        ele.each(function(i, el) {
            var ele = $(el);
            var total = ele.attr('total');
            var has = ele.attr('has');
            var REG = /^[\d\.]+$/;
            var percent = 0;
            if (!(REG.test(total) && REG.test(has))) {
                return;
            }
            total = Math.floor(total.replace(/\..*/, ''));
            has = Math.floor(has.replace(/\..*/, ''));
            percent = (Math.floor((has / total) * 10000) / 100).toFixed(2) + "%";
            ele.find('.ico_yitou').css("width", percent);
            ele.find('.pl5').html(percent);
            // console.log(i, total, has, percent);
        });
    }

    // 埋点
    $('.JS_banner').click(function() {
        var index = this.getAttribute("data-index")
        zhuge.track('首页_点击banner',{
            "位置" : index
        })
    });
});



