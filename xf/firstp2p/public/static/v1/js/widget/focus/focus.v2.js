;(function($) {
    $.fn.vslide = function(options, data) {
        var holder = this.find(".slideDiv"),
        $this = this,
        list = holder.find(".bigImg"),
        link = holder.find(".imgNum"),
        imgTitle = holder.find(".imgTitle"),
        options = options,
        settings = {
            autoPlay: true,
            speed: 3000,
            event: 'hover',
            effect: 'y',
            holder: holder,
            list: list,
            link: link,
            imgTitle: imgTitle,
            leftBt: $this.find(".leftBt"),
            rightBt: $this.find(".rightBt")
        },
        options = $.extend({},
        settings, options);
        return this.each(function() {
            var $this = $(this),
            timer = null,
            list = options.list,
            listLi = list.children(),
            link = options.link,
            linkLi = link.children(),
            imgTitle = options.imgTitle,
            imgTitleLi = imgTitle.children(),
            length = listLi.length,
            width = listLi.eq(0).width(),
            height = listLi.eq(0).height(),
            move = false,
            index = 0,
            obj = {},
            leftBt = options.leftBt,
            rightBt = options.rightBt;
            holder.css({
                position: 'relative',
                overflow: "hidden",
                display: "block",
                width: width,
                height: height
            });
            list.hover(function(){
                 clearInterval(timer);
            },function(){
                 play();
                });
            if ( !! leftBt) {
                leftBt.click(function() {
                    rotate(index - 1);
                }).hover(function() {
                    clearInterval(timer);
                },
                function() {
                    play();
                });
            }
            if ( !! rightBt) {
                rightBt.click(function() {
                    rotate(index + 1);
                }).hover(function() {
                    clearInterval(timer);
                },
                function() {
                    play();
                });;
            }
            list.css({
                position: 'absolute',
                "left": 0,
                "top": 0,
                display: "block",
                width: "20000px",
                height: "20000px"
            });
            if (listLi.length <= 1) {
                return;
            } else {
                if ( !! linkLi.length) {
                    var showTitle = true;
                }
                if ( !! imgTitleLi.length) {
                    var showSimg = true;
                }
            }
            if (options.effect == 'x') {
                var moveSpace = width,
                direction = "left";
                listLi.css('float', 'left');
            } else {
                var moveSpace = height,
                direction = "top";
            }
            listLi.clone().prependTo(list);
            if (showTitle) {
                linkLi.removeClass('active');
                linkLi.eq(0).addClass('active');
            }
            if (showSimg) {
                imgTitleLi.addClass('none');
                imgTitleLi.eq(0).removeClass('none');
            }
            function rotate(i) {
                if (i == length) {
                    i = 0;
                };
                if (i < 0) {
                    i = length - 1;
                }
                if (showTitle) {
                    linkLi.removeClass('active');
                    linkLi.eq(i).addClass('active');
                }
                if (showSimg) {
                    imgTitleLi.addClass('none');
                    imgTitleLi.eq(i).removeClass('none');
                }
                if (i == 0) {
                    if ( - parseInt(list.css(direction)) == (length - 1) * moveSpace) {
                        obj[direction] = -moveSpace * length;
                        list.animate(obj, '1000',
                        function() {
                            list.css(direction, 0);
                        });
                    } else {
                        obj[direction] = -moveSpace * i;
                        list.animate(obj, '1000');
                    }
                } else {
                    obj[direction] = -moveSpace * i;
                    list.animate(obj, '1000');
                }
                index = i;
            }
            function play() {
                clearInterval(timer);
                timer = setInterval(function() {
                    index++;
                    rotate(index);
                },
                options.speed);
            }
            play();
            if (options.event == "click") {
                linkLi.click(function() {
                    clearInterval(timer);
                    var i = $(this).index();
                    rotate(i);
                });
                linkLi.hover(function() {
                    clearInterval(timer);
                },
                function() {
                    play();
                });
            } else {
                linkLi.hover(function() {
                    clearInterval(timer);
                    list.stop();
                    var i = $(this).index();
                    rotate(i);
                },
                function() {
                    play();
                });
            }
        })
    }
})(jQuery);