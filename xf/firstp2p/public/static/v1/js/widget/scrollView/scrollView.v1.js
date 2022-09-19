

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
                autoScroll: true,
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
})(jQuery);