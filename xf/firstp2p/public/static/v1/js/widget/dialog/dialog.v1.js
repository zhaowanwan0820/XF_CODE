/*

弹窗
 */

;(function(window) {
    var $ = window.jQuery,
        global = window.X,
        imgCache = {},
        zIndex = 1000,
        isIe = !-[1, ];
    function Dialog(content, options) {
        var defaults = { // 默认值。
            title: '标题', // 标题文本，若不想显示title请通过CSS设置其display为none
            isshowtitle: true, // 是否显示标题栏。
            isNeedbtn: true, // 是否显示按钮。
            // closeText:'[关闭]', // 关闭按钮文字，若不想显示关闭按钮请通过CSS设置其display为none
            isautoscroll: false, //内容超出对话框，是否允许有滚动条
            isdrag: false, // 是否移动
            modal: true, // 是否是模态对话框
            center: true, // 是否居中。
            fixed: false, // 是否跟随页面滚动。
            time: 0, // 自动关闭时间，为0表示不会自动关闭。
            id: false, // 对话框的id，若为false，则由系统自动产生一个唯一id。
            _imgindex: 0
        };
        options = $.extend(defaults, options);

        options.id = options.id ? options.id : 'dialog-' + new Date().getTime(); // 唯一ID
        var overlayId = options.id + '-overlay', // 遮罩层ID
            timeId = null, // 自动关闭计时器
            isShow = false,

            isIe6 = isIe && !window.XMLHttpRequest,
            self = this,
            _scrollpos = 0,
            title = "";
        if (options.title && (options.title.length > 0)) {
            title = '<span class="comp_dialog_title">' + options.title + '</span>';
        }
        var barHtml = !options.isshowtitle ? [''] : [
            '<div class="comp_dialog_bar" style="' + (options.isdrag ? "CURSOR: move;" : "") + '">',
            title,
            '<a class="comp_dialog_close"></a>',
            '</div>'
        ];
        var bbarHtml = [
            '<div class="comp_dialog_bbar"></div>'
        ];
        var dialogBuffer = [
            '<div id="' + options.id + '" class="comp_dialog" style="width:' + (options.width == undefined ? (options.type == "imgview" ? 'auto' : 'auto') : options.width) + 'px;"><div class="comp_dialog_box">' + barHtml.join(''),
            '<div class="comp_dialog_content"></div>',
            bbarHtml.join(''),
            '</div>'
        ];
        var dialog = $(dialogBuffer.join('')).hide();

        $('body').append(dialog);

        //reset dialog position

        function resetPos() {
            var left, top;
            /* 是否需要居中定位，必需在已经知道了dialog元素大小的情况下，才能正确居中，也就是要先设置dialog的内容。 */
            if (options.center) {
                dialog = $('#' + options.id);
                left = (options.type === "imgview") ? (($(document).width() - dialog.find("#comp_g_imageview").width()) / 2) : (($(document).width() - dialog.width()) / 2);
                top = (options.type === "imgview") ? (($(window).height() - dialog.find("#comp_g_imageview").height()) / 2) : ($(window).height() - dialog.height()) / 2;
               
                if (!isIe6 && options.fixed) {
                    // dialog.css({display: 'none'});
                    if (top < 0) { //add ryq
                        top = 0;
                    }
                    // dialog.css({display: 'block'});
                } else {
                    // dialog.css({
                    top = top + $(document).scrollTop();
                    left = left + $(document).scrollLeft();
                    // });
                }
                dialog.css({
                    top: top + "px",
                    left: left + "px"
                });
            }
        }

        //init function
        var init = function() {
            /* 是否需要初始化背景遮罩层 */
            if (options.modal) {
                var $lay = $('<div id="' + overlayId + '" class="comp_dialog_overlay"></div>');
                $('body').append($lay);
                $lay.css({
                    'left': 0,
                    'top': 0,
                    'width' : $(document).width(),
                    'height': $(document).height(),
                    'z-index': zIndex,
                    'position': 'absolute'
                }).hide();
            }
            dialog.css({
                'z-index': ++zIndex,
                'position': options.fixed ? 'fixed' : 'absolute'
            });

            //ie6 hack
            if (isIe6 && options.fixed) {
                $('select').css('visibility', 'hidden');
                dialog.css('position', 'absolute');
                resetPos();
            } else {
                // $(window).scroll(function() {
                //     resetPos();
                //     $(".formError").remove();
                //     return false;
                // });
                // $(window).on("resize", function() {
                //     $('#' + overlayId).css({
                //         'left': 0,
                //         'top': 0,
                //         'width': "100%",
                //         'height': $(document).height(),
                //         'position': 'absolute'
                //     });
                //     resetPos();
                // });
            }

            /* dialog move */
            var mouse = {
                x: 0,
                y: 0
            };

            function moveDialog(event) {
                var e = window.event || event;
                var top = parseInt(dialog.css('top')) + (e.clientY - mouse.y);
                var left = parseInt(dialog.css('left')) + (e.clientX - mouse.x);
                dialog.css({
                    top: top,
                    left: left
                });
                mouse.x = e.clientX;
                mouse.y = e.clientY;
            };
            dialog.find('.comp_dialog_bar').mousedown(function(event) {
                if (!options.isdrag) {
                    return;
                }
                var e = window.event || event;
                mouse.x = e.clientX;
                mouse.y = e.clientY;
                $(document).bind('mousemove', moveDialog);
            });
            $(document).mouseup(function(event) {
                $(document).unbind('mousemove', moveDialog);
            });

            /* 绑定一些相关事件。 */
            dialog.find('.comp_dialog_close').bind('click', this.close);

            dialog.bind('mousedown', function() {
                dialog.css('z-index', zIndex);
            }); //++Dialog.__zindex

            // 自动关闭
            if (0 != options.time) {
                timeId = setTimeout(this.close, options.time);
            }
        }

        //set dialog html
        this.setContent = function(c) {
            var div = dialog.find('.comp_dialog_content');
            var bbar = dialog.find('.comp_dialog_bbar');
            if (options.isautoscroll) {
                div.css({
                    "height": 700,
                    "overflow": "auto"
                }); // add ryq
            }
            //$(".comp_dialog").css("padding", "40px"); //4 not imageview mode//      此行注释  by:cheris date:2013-11-22
            //$(".comp_dialog_content").css("padding",10);//4 not imageview mode
            if ('object' == typeof(c)) {
                switch (c.type.toLowerCase()) {
                    case 'id': // 将ID的内容复制过来，原来的还在。
                        div.html($('#' + c.value).html());
                        break;
                    case 'img':
                        $(".comp_dialog_content img").css({
                            "max-width": 700,
                            "MAX-HEIGHT": 500
                        })
                        div.html('加载中...');
                        $('<img alt="" />').load(function() {
                            div.empty().append($(this));
                            resetPos();
                        }).attr('src', c.value);

                        break;
                    case 'imgview':
                        div.html('加载中...');
                        //comp_dialog_content
                        //    c._imgindex=0;//图片索引

                        $('<img id="comp_g_imageview" alt="" src="' + X.Dialog._viewimages[0] + '" />').off().on('load' + '.INIT', function(evt) {
                            evt.preventDefault();
                            div.html($(this));

                            if (!isIe6) {

                                imgCache.width = (typeof imgCache.width === 'undefined') ? Math.min($(this).width(), $(document).width() * .8) : imgCache.width;
                                imgCache.height = (typeof imgCache.height === 'undefined') ? Math.min($(this).height(), $(document.body).height() * .8) : imgCache.height;

                                $(".comp_dialog").css("padding", 0)
                                $(".comp_dialog_content").css("padding", 0);

                                $(this).css({
                                    "width": imgCache.width,
                                    "height": imgCache.height,
                                    "overflow": "hidden"
                                });

                                resetPos();
                            }
                        }).attr('src', X.Dialog._viewimages[0]); //c._imgindex

                        $(".comp_dialog,.comp_dialog_content").css("background", "transparent");
                        //begin
                        this.initbtns();
                        //end
                        
                        break;
                    case 'url':
                        div.html('加载中...');
                        $.ajax({
                            url: c.value,
                            success: function(html) {
                                div.html(html);
                                resetPos();
                            },
                            error: function(xml, textStatus, error) {
                                div.html('出错啦')
                            }
                        });
                        break;
                    case 'iframe':
                        div.append($('<iframe src="' + c.value + '" />'));
                        break;
                    case 'alert':
                        div.html(c.html);

                        var _btnskin = c.buttonskin || "blue"
                        if (c.isNeedbtn) {
                            bbar.append($('<a class="ui_btn comp_dialog_yes" href="javascript:;"><span class="big_' + (_btnskin == "blue" ? "normal" : "default") + '_btn"><span>' + (c.yes_btn_txt == undefined ? "确定" : c.yes_btn_txt) + '</span></span></a>'));
                        } else {
                            bbar.append("");
                            bbar.hide();
                        }

                        break;
                    case 'closewin':
                        div.html(c.html);
                        var _btnskin = c.buttonskin || "blue"
                        bbar.append($('<a class="ui_btn comp_dialog_no" href="javascript:;"><span class="big_default_btn"><span>' + (c.no_btn_txt == undefined ? "关闭" : c.no_btn_txt) + '</span></span></a>'));
                        break;
                    case 'loading':
                        div.html(c.html);
                        break;
                    case 'confirm':
                        div.html(c.html);

                        if (typeof c.buttons !== 'undefined' && $.isArray(c.buttons)) {
                            // TODO
                            if (!c.buttons.length) {
                                // TODO
                            }
                            // TODO
                        } else {
                            bbar.append($('<a class="ui_btn comp_dialog_yes" href="javascript:;"><span class="big_normal_btn"><span>' + (c.yes_btn_txt == undefined ? "确定" : c.yes_btn_txt) + '</span></span></a><a class="ui_btn comp_dialog_no" href="javascript:;"><span class="big_default_btn"><span>' + (c.no_btn_txt == undefined ? "取消" : c.no_btn_txt) + '</span></span></a>'));
                        }
                        break;
                    case 'text':
                    default:
                        div.html(c.value);
                        break;
                }
            } else {
                div.html(c);
            }
        }
        //for img view
        this.initbtns = function() {
            /*图片预览模式*/
            $(".comp_dialog_imgview_prev").show();
            $(".comp_dialog_imgview_next").show();
            if ($(".comp_dialog_sfimgview").size() > 0) return;
            var _prevbtn = $('<div class="comp_dialog_sfimgview comp_dialog_imgview_prev" title="上一个"></div>');
            _prevbtn.appendTo(document.body);
            var _nextbtn = $('<div class="comp_dialog_sfimgview comp_dialog_imgview_next" title="下一个"></div>');
            _nextbtn.appendTo(document.body);

            var _closebtn = $('<div class="comp_dialog_sfimgview comp_dialog_imgview_close"></div>');
            _closebtn.appendTo(document.body);
            _prevbtn.css("top", "50%");
            _nextbtn.css("top", "50%");
            _prevbtn.off().on("click" + '.PREV_BTN', function() {

                if (X.Dialog._imgindex > 0) {

                    $("#comp_g_imageview").css({
                        "width": "auto",
                        "height": "auto"
                    }).off().on('load', function() {

                        $(this).css({
                            "width": Math.min($(this).width(), $(document).width() * .8),
                            "height": Math.min($(this).height(), $(document.body).height() * .8),
                            "overflow": "hidden"
                        });
                        resetPos();
                    }).attr("src", X.Dialog._viewimages[X.Dialog._imgindex -= 1]);

                }

            })

            _closebtn.on("click", function() {
                X.Dialog.close();
                _nextbtn.hide();
                _prevbtn.hide();
            })

            _nextbtn.off().on("click" + '.NEXT_BTN', function() {
                if (X.Dialog._imgindex < X.Dialog._viewimages.length - 1) {

                    $("#comp_g_imageview").css({
                        "width": "auto",
                        "height": "auto"
                    }).off().on('load', function() {

                        // $("#comp_g_imageview").css({"width":"auto","height":"auto"}).off().on('load', function(){
                        $(this).css({
                            "width": Math.min($(this).width(), $(document).width() * .8),
                            "height": Math.min($(this).height(), $(document.body).height() * .8),
                            "overflow": "hidden"
                        });

                        resetPos();
                    }).attr("src", X.Dialog._viewimages[X.Dialog._imgindex += 1]);
                }
            })

        }
        //show dialog
        this.show = function() {
            if (undefined != options.beforeShow && !options.beforeShow()) {
                return;
            }
            // X.Utils.BodyBar();
            _scrollpos = $(window).scrollTop();
            //get opacity
            
            // is show mask
            if (options.modal) {
                $('#' + overlayId).fadeTo('fast',0.2);
            }
            dialog.fadeTo('fast', 1, function() {
                if (undefined != options.afterShow) {
                    options.afterShow();
                }
                isShow = true;
            });
            // 自动关闭
            if (0 != options.time) {
                timeId = setTimeout(this.close, options.time);
            }

            resetPos();
        }

        //resize dialog
        this.resizepos = function() { //ryq add
            resetPos();
        }
        //hide dialog
        this.hide = function() {
            if (!isShow) {
                return;
            }

            //$('select').css('visibility', 'visible');

            if (undefined != options.beforeHide && !options.beforeHide()) {
                return;
            }

            dialog.fadeOut('slow', function() {
                if (undefined != options.afterHide) {
                    options.afterHide();
                }
            });
            if (options.modal) {
                $('#' + overlayId).fadeOut('slow');
            }

            isShow = false;
        }
        //close dialog
        this.close = function() {
            var isCallback;
            if (undefined != options.beforeClose && !options.beforeClose()) {
                return;
            }
            dialog.hide().remove();
            isShow = false;
            if (undefined != options.afterClose) {
                options.afterClose();
            }
            if (options.modal) {
                $('#' + overlayId).hide().remove();
            }
          
            $(window).off("scroll"); //forbiden scroll
            // X.Utils.BodyBar(true);
            clearTimeout(timeId);
            $(".formError").remove();
        }

        init.call(this);
        this.setContent(options);

        dialog.find('.comp_dialog_no').bind('click', {
            btn: "no"
        }, this.close);
        dialog.find('.comp_dialog_yes').bind('click', {
            btn: "yes"
        }, function(evt) {

            if (options.callback != undefined) {
                isCallback = options.callback.call(this, evt.data);
            }

            if (typeof isCallback !== 'undefined' && isCallback) {

                self.close();
            }
        });
        // Dialog.__count++;
        //  Dialog.__zindex++;
    }
    Dialog.__zindex = zIndex - 1;
    Dialog.__count = 1;
    Dialog.version = '1.0';

    function dialog(content, options) {
        var dlg = new Dialog(content, options);
        dlg.show();

        // ryq add ie6,ie7 dialog自适应内容的宽度和高度 start
        var isIe6 = isIe && !window.XMLHttpRequest;
        if (isIe6) {
            //alert($(".comp_dialog .comp_dialog_content").children().first().width());
            var f = $(".comp_dialog .comp_dialog_content").children();
            f.css('float', 'left');
            $(".comp_dialog").css("width", f.width() + 20);
        }
        dlg.resizepos();
        // ryq add ie6,ie7 dialog自适应内容的宽度和高度 end

        return dlg;
    }

    global.Dialog = {
        _self: null,
        _imgindex: 0,
        _viewimages: [],
        Alert: function(config) {
            if (config == undefined) config = {};
            config.type = "alert"; //set mode
            this._self = dialog((config.html == undefined ? "" : config.html), config);
        },
        Closewin: function(config) {
            if (config == undefined) config = {};
            config.type = "closewin"; //set mode
            this._self = dialog((config.html == undefined ? "" : config.html), config);
        },
        Loading: function(config) {
            if (config == undefined) config = {};
            config.type = "loading"; //set mode
            this._self = dialog((config.html == undefined ? "" : config.html), config);
        },
        Confirm: function(config) {
            if (config == undefined) config = {};
            config.type = "confirm"; //set mode
            this._self = dialog((config.html == undefined ? "" : config.html), config);
            return this._self;
        },
        ImageView: function(o, config) {
            if (config == undefined) config = {};
            X.Dialog._viewimages = [];
            X.Dialog._imgindex = 0;
            $(o).each(function(v, k) {
                X.Dialog._viewimages.push($(k).attr("bigurl"));
            })
            config.type = "imgview"; //set mode
            config.opacity = .8; //set opacity
            config.isshowtitle = false;
            this._self = dialog((config.html == undefined ? "" : config.html), config);

        },
        close: function() {
            this._self.close();
        },
        resizepos: function() {
            this._self.resizepos();
        }
    }
}(this));
(function($) {
$(function() {
    window.alert = function(msg) {
        X.Dialog.Alert({
            fixed: !1,
            title: "提示",
            yes_btn_txt: "确定",
            width: msg.width || 440,
            //height: 60,
            html: msg,
            callback: function(data) {
                return true;
            }
        });

    };

     window.alertImg = function(msg) {
        X.Dialog.Alert({
            fixed: !1,
            title: "提示",
            yes_btn_txt: "确定",
	width: msg.width || 370,
            isNeedbtn : false,
            html: msg.msg || msg,
            callback: function(data) {
                return true;
            }
        });

    };
    window.newAlert = function(obj) {
        X.Dialog.Alert({
            fixed: !1,
            title: "提示",
            yes_btn_txt: "确定",
            width: obj.width || 300,
            height: 60,
            html: "<h4>" + obj.msg + "</h4>",
            callback: function(data) { !! obj.fn && obj.fn();
                return true;
            }
        });
    };
    window.newComfirm = function(obj) {
        X.Dialog.Confirm({
            fixed : !1,
            title: obj.title || "提示",
            yes_btn_txt: "确定",
            width: obj.width || 440,
            //height: 160,
            html: obj.msg || obj,
            callback: function(data) {
                !!obj.fn && obj.fn(data);
                //return true;
            }
        });
    }

});
})(jQuery);