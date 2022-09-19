;(function($){$.fn.bgIframe=$.fn.bgiframe=function(s){if($.browser.msie&&/6.0/.test(navigator.userAgent)){s=$.extend({top:'auto',left:'auto',width:'auto',height:'auto',opacity:true,src:'javascript:false;'},s||{});var prop=function(n){return n&&n.constructor==Number?n+'px':n;},html='<iframe class="bgiframe"frameborder="0"tabindex="-1"src="'+s.src+'"'+'style="display:block;position:absolute;z-index:-1;'+(s.opacity!==false?'filter:Alpha(Opacity=\'0\');':'')+'top:'+(s.top=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderTopWidth)||0)*-1)+\'px\')':prop(s.top))+';'+'left:'+(s.left=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderLeftWidth)||0)*-1)+\'px\')':prop(s.left))+';'+'width:'+(s.width=='auto'?'expression(this.parentNode.offsetWidth+\'px\')':prop(s.width))+';'+'height:'+(s.height=='auto'?'expression(this.parentNode.offsetHeight+\'px\')':prop(s.height))+';'+'"/>';return this.each(function(){if($('> iframe.bgiframe',this).length==0)this.insertBefore(document.createElement('html'),this.firstChild);});}return this;};})(jQuery);

/**
 * weebox.js
 */
;(function($) {
    
    var weebox = function(content, options) {
        var self = this;
        this._dragging = false;
        this._content = content;
        this._options = options;
        this.dh = null;
        this.mh = null;
        this.dt = null;
        this.dc = null;
        this.bo = null;
        this.bc = null;
        this.selector = null;
        this.ajaxurl = null;
        this.options = null;
        this.defaults = {
            boxid: null,
            boxclass: null,
            type: 'dialog',
            title: '',
            width: 0,
            height: 0,
            timeout: 0,
            draggable: true,
            modal: true,
            focus: null,
            position: 'center',
            overlay: 50,
            showTitle: true,
            showButton: true,
            showCancel: true,
            showOk: true,
            okBtnName: '确定',
            cancelBtnName: '取消',
            contentType: 'text',
            contentChange: false,
            clickClose: false,
            zIndex: 999,
            animate: false,
            trigger: null,
            onclose: null,
            onopen: null,
            onok: null
        };
        this.types = new Array(
            "dialog",
            "error",
            "warning",
            "success",
            "prompt",
            "box"
        );
        this.titles = {
            "error": "!! Error !!",
            "warning": "Warning!",
            "success": "Success",
            "prompt": "Please Choose",
            "dialog": "Dialog",
            "box": ""
        };

        this.initOptions = function() {
            if (typeof(self._options) == "undefined") {
                self._options = {};
            }
            if (typeof(self._options.type) == "undefined") {
                self._options.type = 'dialog';
            }
            if (!$.inArray(self._options.type, self.types)) {
                self._options.type = self.types[0];
            }
            if (typeof(self._options.boxclass) == "undefined") {
                self._options.boxclass = self._options.type + "box";
            }
            if (typeof(self._options.title) == "undefined") {
                self._options.title = self.titles[self._options.type];
            }
            if (content.substr(0, 1) == "#") {
                self._options.contentType = 'selector';
                self.selector = content;
            }
            self.options = $.extend({}, self.defaults, self._options);
        };

        this.initBox = function() {
            var html = '';
            if (self.options.type == 'wee') {
                html = '<div class="weedialog">' +
                    '   <div class="dialog-top">' +
                    '       <div class="dialog-tl"></div>' +
                    '       <div class="dialog-tc"></div>' +
                    '       <div class="dialog-tr"></div>' +
                    '   </div>' +
                    '   <table width="100%" border="0" cellspacing="0" cellpadding="0" >' +
                    '       <tr>' +
                    '           <td class="dialog-cl"></td>' +
                    '           <td>' +
                    '               <div class="dialog-header">' +
                    '                   <div class="dialog-title"></div>' +
                    '                   <div class="dialog-close"></div>' +
                    '               </div>' +
                    '               <div class="dialog-content"></div>' +
                    '               <div class="dialog-button">' +
                    // '                    <input type="button" class="dialog-ok" value="确定">' +
                    // '                    <input type="button" class="dialog-cancel" value="取消">' +

                    '                   <a  class="btn-base dialog-ok"><span>' + self.options.okBtnName + '</span></a>' +
                    '                   <a  class="btn-base dialog-cancel" style="display: inline-block;"><span>' + self.options.cancelBtnName + '</span></a>' +
                    '               </div>' +
                    '           </td>' +
                    '           <td class="dialog-cr"></td>' +
                    '       </tr>' +
                    '   </table>' +
                    '   <div class="dialog-bot">' +
                    '       <div class="dialog-bl"></div>' +
                    '       <div class="dialog-bc"></div>' +
                    '       <div class="dialog-br"></div>' +
                    '   </div>' +
                    '</div>';
                $(".dialog-box").find(".dialog-close").click();

            } else {
                html = "<div class='dialog-box'>" +
                    "<div class='dialog-header'>" +
                    "<div class='dialog-title'></div>" +
                    "<div class='dialog-close'></div>" +
                    "</div>" +
                    "<div class='dialog-content'></div>" +
                    "<div style='clear:both'></div>" +
                    "<div class='dialog-button'>" +
                    // "<input type='button' class='dialog-ok' value='确定'>" +
                    // "<input type='button' class='dialog-cancel' value='取消'>" +
                    "<a  class='btn-base dialog-ok'><span>'+self.options.okBtnName+'</span></a>" +
                    "<a  class='btn-base dialog-cancel'><span>'+self.options.cancelBtnName+'</span></a>" +
                    "</div>" +
                    "</div>";
            }
            self.dh = $(html).appendTo('body').hide().css({
                position: 'absolute',
                overflow: 'hidden',
                zIndex: self.options.zIndex
            });
            self.dt = self.dh.find('.dialog-title');
            self.dc = self.dh.find('.dialog-content');
            self.db = self.dh.find('.dialog-button');
            self.bo = self.dh.find('.dialog-ok');
            self.bc = self.dh.find('.dialog-cancel');
            self.db.show();
            if (self.options.boxid) {
                self.dh.attr('id', self.options.boxid);
            }
            if (self.options.boxclass) {
                self.dh.addClass(self.options.boxclass);
            }
            if (self.options.height > 0) {
                self.dc.css('height', self.options.height);
            }
            if (self.options.contentType == 'iframe') {
                self.dc.css('padding', "0");
                self.db.hide();
            }

            if (self.options.width > 0) {
                self.dh.css('width', self.options.width);
            }
            self.dh.bgiframe();
            !!self.options.dialogReady && self.options.dialogReady();
        }

        this.initMask = function() {
            if (self.options.modal) {
                self.mh = $("<div class='dialog-mask'></div>")
                    .appendTo('body').hide().css({
                        opacity: self.options.overlay / 100,
                        filter: 'alpha(opacity=' + self.options.overlay + ')',
                        width: self.bwidth(),
                        height: self.bheight(),
                        zIndex: self.options.zIndex - 1
                    });
            }
        }

        this.initContent = function(content) {
            self.dh.find(".dialog-ok").val(self.options.okBtnName);
            self.dh.find(".dialog-cancel").val(self.options.cancelBtnName);
            self.dh.find('.dialog-title').html(self.options.title);
            if (!self.options.showTitle) {
                self.dh.find('.dialog-header').hide();
            }
            if (!self.options.showButton) {
                self.dh.find('.dialog-button').hide();
            }
            if (!self.options.showCancel) {
                self.dh.find('.dialog-cancel').hide();
            }
            if (!self.options.showOk) {
                self.dh.find(".dialog-ok").hide();
            }
            if (self.options.contentType == "selector") {
                self.selector = self._content;
                self._content = $(self.selector).html();
                self.setContent(self._content);
                //if have checkbox do
                var cs = $(self.selector).find(':checkbox');
                self.dh.find('.dialog-content').find(':checkbox').each(function(i) {
                    this.checked = cs[i].checked;
                });
                $(self.selector).empty();
                self.onopen();
                self.show();
                self.focus();
            } else if (self.options.contentType == "ajax") {
                self.ajaxurl = self._content;
                self.setContent('<div class="dialog-loading"></div>');
                self.show();
                $.get(self.ajaxurl, function(data) {
                    self._content = data;
                    self.setContent(self._content);
                    self.onopen();
                    self.focus();
                    if (self.options.position == 'center') {
                        self.setCenterPosition();
                    }
                });
            } else if (self.options.contentType == "iframe") {
                self.setContent('<iframe frameborder="0" width="100%" height="100%" src="' + self._content + '"></iframe>');
                self.onopen();
                self.show();
                self.focus();
            } else {
                self.setContent(self._content);
                self.onopen();
                self.show();
                self.focus();
            }
        }

        this.initEvent = function() {
            self.dh.find(".dialog-close, .dialog-cancel, .dialog-ok").unbind('click').click(function() {
                self.close();
                if (self.options.type == 'wee') {
                    $(".dialog-box").find(".dialog-close").click();
                }
            });
            if (typeof(self.options.onok) == "function") {
                self.dh.find(".dialog-ok").unbind('click').bind("click" ,function(){
                    self.options.onok(self);
                });
            }
            if (typeof(self.options.oncancel) == "function") {
                self.dh.find(".dialog-cancel").unbind('click').bind("click" , function(){
                    self.options.oncancel(self);
                });
            }
            if (self.options.timeout > 0) {
                window.setTimeout(self.close, (self.options.timeout * 1000));
            }
            this.draggable();
        }

        this.draggable = function() {
            if (self.options.draggable && self.options.showTitle) {
                self.dh.find('.dialog-header').mousedown(function(event) {
                    self._ox = self.dh.position().left;
                    self._oy = self.dh.position().top;
                    self._mx = event.clientX;
                    self._my = event.clientY;
                    self._dragging = true;
                });
                if (self.mh) {
                    var handle = self.mh;
                } else {
                    var handle = $(document);
                }
                $(document).mousemove(function(event) {
                    if (self._dragging == true) {
                        //window.status = "X:"+event.clientX+"Y:"+event.clientY;
                        self.dh.css({
                            left: self._ox + event.clientX - self._mx,
                            top: self._oy + event.clientY - self._my
                        });
                    }
                }).mouseup(function() {
                    self._mx = null;
                    self._my = null;
                    self._dragging = false;
                });
                var e = self.dh.find('.dialog-header').get(0);
                e.unselectable = "on";
                e.onselectstart = function() {
                    return false;
                };
                if (e.style) {
                    e.style.MozUserSelect = "none";
                }
            }
        }

        this.onopen = function() {
            if (typeof(self.options.onopen) == "function") {
                self.options.onopen();
            }
        }

        this.show = function() {
            if (self.options.position == 'center') {
                self.setCenterPosition();
            }
            if (self.options.position == 'element') {
                self.setElementPosition();
            }
            if (self.options.animate) {
                self.dh.fadeIn("slow");
                if (self.mh) {
                    self.mh.fadeIn("normal");
                }
            } else {
                self.dh.show();
                if (self.mh) {
                    self.mh.show();
                }
            }
        }

        this.focus = function() {
            if (self.options.focus) {
                self.dh.find(self.options.focus).focus();
            } else {
                self.dh.find('.dialog-cancel').focus();
            }
        }

        this.find = function(selector) {
            return self.dh.find(selector);
        }

        this.setTitle = function(title) {
            self.dh.find('.dialog-title').html(title);
        }

        this.getTitle = function() {
            return self.dh.find('.dialog-title').html();
        }

        this.setContent = function(content) {
            self.dh.find('.dialog-content').html(content);
        }

        this.getContent = function() {
            return self.dh.find('.dialog-content').html();
        }

        this.hideButton = function(btname) {
            self.dh.find('.dialog-' + btname).hide();
        }

        this.showButton = function(btname) {
            self.dh.find('.dialog-' + btname).show();
        }

        this.setButtonTitle = function(btname, title) {
            self.dh.find('.dialog-' + btname).val(title);
        }

        this.close = function() {
            if (self.animate) {
                self.dh.fadeOut("slow", function() {
                    self.dh.hide();
                });
                if (self.mh) {
                    self.mh.fadeOut("normal", function() {
                        self.mh.hide();
                    });
                }
            } else {
                self.dh.hide();
                if (self.mh) {
                    self.mh.hide();
                }
            }
            if (self.options.contentType == 'selector') {
                if (self.options.contentChange) {
                    //if have checkbox do
                    var cs = self.find(':checkbox');
                    $(self.selector).html(self.getContent());
                    if (cs.length > 0) {
                        $(self.selector).find(':checkbox').each(function(i) {
                            this.checked = cs[i].checked;
                        });
                    }
                } else {
                    $(self.selector).html(self._content);
                }
            }
            if (typeof(self.options.onclose) == "function") {
                self.options.onclose(self);
            }
            self.dh.remove();
            if (self.mh) {
                self.mh.remove();
            }
        }

        this.bheight = function() {
            if ($.browser.msie && $.browser.version < 7) {
                var scrollHeight = Math.max(
                    document.documentElement.scrollHeight,
                    document.body.scrollHeight
                );
                var offsetHeight = Math.max(
                    document.documentElement.offsetHeight,
                    document.body.offsetHeight
                );

                if (scrollHeight < offsetHeight) {
                    return $(window).height();
                } else {
                    return scrollHeight;
                }
            } else {
                return $(document).height();
            }
        }

        this.bwidth = function() {
            if ($.browser.msie && $.browser.version < 7) {
                var scrollWidth = Math.max(
                    document.documentElement.scrollWidth,
                    document.body.scrollWidth
                );
                var offsetWidth = Math.max(
                    document.documentElement.offsetWidth,
                    document.body.offsetWidth
                );

                if (scrollWidth < offsetWidth) {
                    return $(window).width();
                } else {
                    return scrollWidth;
                }
            } else {
                return $(document).width();
            }
        }

        this.setCenterPosition = function() {
            var wnd = $(window),
                doc = $(document),
                pTop = doc.scrollTop(),
                pLeft = doc.scrollLeft(),
                minTop = pTop;
            pTop += (wnd.height() - self.dh.height()) / 2;
            pTop = Math.max(pTop, minTop);
            pLeft += (wnd.width() - self.dh.width()) / 2;
            self.dh.css({
                top: pTop,
                left: pLeft
            });

        }

        //      this.setElementPosition = function() {
        //          var trigger = $("#"+self.options.trigger);          
        //          if (trigger.length == 0) {
        //              alert('请设置位置的相对元素');
        //              self.close();               
        //              return false;
        //          }       
        //          var scrollWidth = 0;
        //          if (!$.browser.msie || $.browser.version >= 7) {
        //              scrollWidth = $(window).width() - document.body.scrollWidth;
        //          }
        //          
        //          var left = Math.max(document.documentElement.scrollLeft, document.body.scrollLeft)+trigger.position().left;
        //          if (left+self.dh.width() > document.body.clientWidth) {
        //              left = trigger.position().left + trigger.width() + scrollWidth - self.dh.width();
        //          } 
        //          var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop)+trigger.position().top;
        //          if (top+self.dh.height()+trigger.height() > document.documentElement.clientHeight) {
        //              top = top - self.dh.height() - 5;
        //          } else {
        //              top = top + trigger.height() + 5;
        //          }
        //          self.dh.css({top: top, left: left});
        //          return true;
        //      }   

        this.setElementPosition = function() {
            var trigger = $(self.options.trigger);
            if (trigger.length == 0) {
                alert('请设置位置的相对元素');
                self.close();
                return false;
            }
            var left = trigger.offset().left;
            var top = trigger.offset().top + 25;
            self.dh.css({
                top: top,
                left: left
            });
            return true;
        }

        //窗口初始化 
        this.initialize = function() {
                self.initOptions();
                self.initMask();
                self.initBox();
                self.initContent();
                self.initEvent();
                return self;
            }
            //初始化
        this.initialize();
    }

    var weeboxs = function() {
        var self = this;
        this._onbox = false;
        this._opening = false;
        this.boxs = new Array();
        this.zIndex = 999;
        this.push = function(box) {
            this.boxs.push(box);
        }
        this.pop = function() {
            if (this.boxs.length > 0) {
                return this.boxs.pop();
            } else {
                return false;
            }
        }
        this.open = function(content, options) {
            self._opening = true;
            if (typeof(options) == "undefined") {
                options = {};
            }
            if (options.boxid) {
                this.close(options.boxid);
            }
            options.zIndex = this.zIndex;
            this.zIndex += 10;
            var box = new weebox(content, options);
            box.dh.click(function() {
                self._onbox = true;
            });
            this.push(box);
            return box;
        }
        this.close = function(id) {
            if (id) {
                for (var i = 0; i < this.boxs.length; i++) {
                    if (this.boxs[i].dh.attr('id') == id) {
                        this.boxs[i].close();
                        this.boxs.splice(i, 1);
                    }
                }
            } else {
                this.pop().close();
            }
        }
        this.length = function() {
            return this.boxs.length;
        }
        this.getTopBox = function() {
            return this.boxs[this.boxs.length - 1];
        }
        this.find = function(selector) {
            return this.getTopBox().dh.find(selector);
        }
        this.setTitle = function(title) {
            this.getTopBox().setTitle(title);
        }
        this.getTitle = function() {
            return this.getTopBox().getTitle();
        }
        this.setContent = function(content) {
            this.getTopBox().setContent(content);
        }
        this.getContent = function() {
            return this.getTopBox().getContent();
        }
        this.hideButton = function(btname) {
            this.getTopBox().hideButton(btname);
        }
        this.showButton = function(btname) {
            this.getTopBox().showButton(btname);
        }
        this.setButtonTitle = function(btname, title) {
            this.getTopBox().setButtonTitle(btname, title);
        }
        $(window).scroll(function() {
            if (self.length() > 0) {
                var box = self.getTopBox();
                if (box.options.position == "center") {
                    self.getTopBox().setCenterPosition();
                }
            }
        });
        $(document).click(function() {
            if (self.length() > 0) {
                var box = self.getTopBox();
                if (!self._opening && !self._onbox && box.options.clickClose) {
                    box.close();
                }
            }
            self._opening = false;
            self._onbox = false;
        });
    }
    $.extend({
        weeboxs: new weeboxs()
    });
})(jQuery);

;
(function($) {
    $(function() {
        if (typeof Firstp2p === "undefined") {
            Firstp2p = {};
        }

        //确认弹出层
        Firstp2p.alert = function(obj) {
            var settings = $.extend({
                title: "提示",
                width: 300,
                showButton: true,
                boxclass: '',
                ok: $.noop,
                text: "",
                close: $.noop
            }, obj);
            html = '',
            instance = null;
            html += '<div>' + settings.text + '</div>';
            instance = $.weeboxs.open(html, {
                boxid: null,
                boxclass: settings.boxclass,
                contentType: 'text',
                showButton: settings.showButton,
                showOk: true,
                okBtnName: '确定',
                showCancel: false,
                title: settings.title,
                width: settings.width,
                type: 'wee',
                onclose: function(object) {
                    settings.close(object);
                },
                onok: function(object) {
                    settings.ok(object);
                }
            });
            return instance;
        };

        //确认取消弹出层
        Firstp2p.confirm = function(obj) {
            var settings = $.extend({
                title : "提示" ,
                ok: $.noop,
                text : "" ,
                cancel : $.noop,
                close : $.noop
            } , obj),
            html = '',
            instance = null;
            html += '<div>'+ settings.text +'</div>';
            instance = $.weeboxs.open(html, {
                boxid: null,
                boxclass: '',
                contentType: 'text',
                showButton: true,
                showOk: true,
                okBtnName: '确定',
                showCancel: true,
                CancelBtnName: '取消',
                title: settings.title,
                width: 300,
                type: 'wee',
                onclose: function(object) {
                    settings.close(object);
                },
                onok: function(object) {
                    settings.ok(object);
                },
                oncancel : function(object){
                    settings.cancel(object);
                }
            });
            return instance;
        };

        Firstp2p.getMessage = function(obj){
            var quhao = obj.mobile_code;
            quhao = quhao.replace(/[^0-9]/, "");
            quhao = quhao == "86" ? "" : (quhao + "-");
            var phone = obj.mobile;
            var phonelabel = quhao + phone;
            phonelabel = phonelabel.replace(/([0-9\-]*)([0-9]{4})([0-9]{4})/, function(_0, _1, _2, _3) {return _1 + "****" + _3 });
            var settings = $.extend({
                title : '请输入短信验证码进行身份验证',
                html : '<div class="wee-send">\
                        <div class="send-input">\
                            <div class="error-box" style="visibility:hidden;">\
                                <div class="error-wrap">\
                                    <div class="e-text" style="width: 305px;">请填写6位数字验证码</div>\
                                </div>\
                            </div>\
                            <p>已向&nbsp;<span class="color_green">' + phonelabel + ' </span>&nbsp;发送验证短信</p>\
                            <input type="text" class="ipt-txt w150" id="pop_code" placeholder="短信验证码" maxlength="10" value="">\
                            <input type="button"  class="reg-sprite btn-blue-h34 btn-gray-h34 j_sendMessage" value="发送">\
                        </div>\
                        </div>',
                msgUrl : '/user/MCode',
                postUrl : '/user/webDoLogin'
            } , obj ),
            errorSpan = "",
            status = "",
            timer = null,
            msglock = false,
            setProperty = function () {
                var button = $(".ui_send_msg .j_sendMessage");
                bgGray();
                _reset();
            },
            bgGray = function() {
                var button = $(".ui_send_msg .j_sendMessage");
                button.addClass("btn-gray-h34");
                button.val("正在获取中...");
                button.attr("disabled", "disabled");
            },
            _set = function(msg) {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'visible');
                errorSpan.find('.e-text').html(msg);
                $(".ui_send_msg .ipt-txt").addClass("err-shadow");
            },
            _reset = function() {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'hidden');
                errorSpan.find('.e-text').html('');
            },
            updateTimeLabel = function(duration) {
                var timeRemained = duration;
                var button = $(".ui_send_msg .j_sendMessage");
                timer = setInterval(function() {
                    button.val(timeRemained + '秒后重新发送');
                    timeRemained -= 1;
                    if (timeRemained == -1) {
                        clearInterval(timer);
                        msglock = false;
                        button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
                    }
                }, 1000);
            },
            callback = function(data) {
                if (!msglock) { 
                    updateTimeLabel(60);    
                    msglock = true; 
                }
                if (!!data && data.code != 1) {
                    _set(data.message);
                } else {
                    _reset();
                }
            },
            getCode = function() {
                var data = {
                    "type": 9,
                    "mobile" : $("#valid_phone").val(),
                    "token" : $("#token").val(),
                    "token_id" : $("#token_id").val(),
                    "country_code" : $("#country_code").val()
                };
                var getcodeUrl = settings.msgUrl;
                $.ajax({
                    url: getcodeUrl,
                    type: "post",
                    data: data,
                    dataType: "json",
                    beforeSend: function() {
                    },
                    success: function(result) {
                        setProperty();
                        callback(result);
                    },
                    error: function() {

                    }
                });
            };

            $.weeboxs.open(settings.html , {
                boxid: null,
                boxclass: 'ui_send_msg',
                contentType: 'text',
                showButton: true,
                showOk: true,
                okBtnName: '确定',
                showCancel: false,
                title: settings.title,
                width: 463,
                height: 125,
                type: 'wee',
                onopen : function(){
                    getCode();
                },
                onclose: function() {
                    //location.href = "/user/login"
                },
                onok: function() {
                    var $text = $(".ui_send_msg .error-box").find('.e-text'),
                        showError = function() {
                            $(".ui_send_msg .error-box").css({
                                'display': 'block',
                                'visibility': 'visible'
                            });
                            $(".ui_send_msg .ipt-txt").addClass("err-shadow");
                        };

                    if (!/^\d{6}$/.test($(".ui_send_msg #pop_code").val())) {
                        showError();
                        $text.html("请填写6位数字验证码");
                        return;
                    }
                    var data = {
                        "code": $(".ui_send_msg #pop_code").val(),
                        "mobile" : $("#valid_phone").val()
                    };
                    $.ajax({
                        url: settings.postUrl,
                        type: "post",
                        data: data,
                        dataType: "json",
                        beforeSend: function() {
                            // $text.html("正在提交，请稍候...");
                        },
                        success: function(data) {
                            // alert(JSON.stringify(data));
                            if (data.errorCode === 0) {
                                $("#loginForm").append('<input type="hidden" name="code" value="'+ $("#pop_code").val() +'" >').unbind("submit").submit();
                                 $.weeboxs.close();
                            } else {
                                showError();
                                $text.html(data.errorMsg);
                                
                            }
                        },
                        error: function() {
                            showError();
                            $text.html("服务器错误，请重新再试！");
                        }
                    });
                    // $.weeboxs.close();
                }
            });
            // 点击“重新发送”按钮获取短信验证码
            $('body').on("click", ".j_sendMessage", function() {
                getCode();
            });

        };

        // 无区号的短信验证码
        Firstp2p.getMsg = function(obj){
            var phone = $("#mobile").val();
            var settings = $.extend({
                title : '填写短信验证码',
                html : '<div class="wee-send">\
                        <div class="send-input">\
                            <div class="error-box" style="visibility:hidden;">\
                                <div class="error-wrap">\
                                    <div class="e-text" style="width: 305px;">请填写6位数字验证码</div>\
                                </div>\
                            </div>\
                            <p>已向&nbsp;<span class="color_green">' + phone + ' </span>&nbsp;发送验证短信</p>\
                            <input type="text" class="ipt-txt w150" id="pop_code" placeholder="短信验证码" maxlength="10" value="">\
                            <input type="button"  class="reg-sprite btn-blue-h34 btn-gray-h34 j_sendMessage" value="发送">\
                        </div>\
                        </div>',
                msgUrl : '/user/MCode',
                postUrl : '/user/CheckPwdCode',
                dataGetCode : {
                    "type": 2,
                    "mobile" : $("#mobile").val(),
                    "active":1
                },
                dataVerrifyCode : {
                    "code": $(".ui_send_msg #pop_code").val(),
                    "phone" : $("#mobile").val()
                },
                callback : function(data) {
                    if (!msglock) { 
                        updateTimeLabel(60);    
                        msglock = true; 
                    }
                    if (!!data && data.code != 1) {
                        _set(data.message);
                    } else {
                        _reset();
                    }
                },
                callbackpost : function(data){
                    window.location.href = data.jump;
                }
            } , obj ),
            errorSpan = "",
            status = "",
            timer = null,
            msglock = false,
            setProperty = function () {
                var button = $(".ui_send_msg .j_sendMessage");
                bgGray();
                _reset();
            },
            bgGray = function() {
                var button = $(".ui_send_msg .j_sendMessage");
                button.addClass("btn-gray-h34");
                button.val("正在获取中...");
                button.attr("disabled", "disabled");
            },
            _set = function(msg) {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'visible');
                errorSpan.find('.e-text').html(msg);
                $(".ui_send_msg .ipt-txt").addClass("err-shadow");
            },
            _reset = function() {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'hidden');
                errorSpan.find('.e-text').html('');
            },
            updateTimeLabel = function(duration) {
                var timeRemained = duration;
                var button = $(".ui_send_msg .j_sendMessage");
                timer = setInterval(function() {
                    button.val(timeRemained + '秒后重新发送');
                    timeRemained -= 1;
                    if (timeRemained == -1) {
                        clearInterval(timer);
                        msglock = false;
                        button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
                    }
                }, 1000);
            },
            getCode = function() {
                var data = settings.dataGetCode;
                var getcodeUrl = settings.msgUrl;
                $.ajax({
                    url: getcodeUrl,
                    type: "post",
                    data: data,
                    dataType: "json",
                    beforeSend: function() {
                    },
                    success: function(result) {
                        setProperty();
                        settings.callback(result);
                    },
                    error: function() {

                    }
                });
            };

            $.weeboxs.open(settings.html , {
                boxid: null,
                boxclass: 'ui_send_msg',
                contentType: 'text',
                showButton: true,
                showOk: true,
                okBtnName: '确定',
                showCancel: false,
                title: settings.title,
                width: 463,
                height: 125,
                type: 'wee',
                onopen : function(){
                    getCode();
                },
                onclose: function() {
                    //location.href = "/user/login"
                },
                onok: function() {
                    var $text = $(".ui_send_msg .error-box").find('.e-text'),
                        showError = function() {
                            $(".ui_send_msg .error-box").css({
                                'display': 'block',
                                'visibility': 'visible'
                            });
                            $(".ui_send_msg .ipt-txt").addClass("err-shadow");
                        };

                    if (!/^\d{6}$/.test($(".ui_send_msg #pop_code").val())) {
                        showError();
                        $text.html("请填写6位数字验证码");
                        return;
                    }

                    $.ajax({
                        url: settings.postUrl,
                        type: "post",
                        data: settings.dataVerrifyCode,
                        dataType: "json",
                        beforeSend: function() {
                            // $text.html("正在提交，请稍候...");
                        },
                        success: function(data) {
                            // alert(JSON.stringify(data));
                            if (data.info.code === "1") {
                                 $.weeboxs.close();
                                 settings.callbackpost(data);
                                 
                            } else {
                                showError();
                                $text.html(data.info.msg);
                                
                            }
                        },
                        error: function() {
                            showError();
                            $text.html("服务器错误，请重新再试！");
                        }
                    });
                    // $.weeboxs.close();
                }
            });
            // 点击“重新发送”按钮获取短信验证码
            $('body').on("click", ".j_sendMessage", function() {
                getCode();
            });

        };

    });

})(jQuery);