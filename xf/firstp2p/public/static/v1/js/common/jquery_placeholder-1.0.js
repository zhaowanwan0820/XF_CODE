
/**
 * placeholder组件 解决IE9以下不支持placeholder的bug
 * allOn //是否开启组件全替换
 *      false 浏览器支持的用placeholder 不支持的用组件 
 *      ture  全部都使用组件 placeholder属性为空(因为IE10+和Firefox/Chrome/Safari的表现形式不一致)
 * placeholder_text //提示信息
 * placeholder_color //文本颜色
 * placeholder_evtType // 默认事件
 *      focus 即鼠标点击到输入域时默认文本消失
 *      keydown 光标定位到输入域后键盘输入时默认文本才消失
 * placeholder_zIndex  //层级
 * placeholder_PaddingLeft //离左侧间距
**/
/**
*使用示例

*html

* <input type="password" class="int_placeholder" placeholder="请输入密码" data-placeholder="span请输入密码" />
* <input type="text" class="int_placeholder" placeholder="请输入用户名"  />

*javascript


*        $(function () {
            $(".int_placeholder").each(function () {
                var p_text= $(this).attr("data-placeholder");
                new Firstp2p.placeholder(this, {
                    allOn: true,
                    placeholder_color: "#333",
                    placeholder_paddingLeft: 10,
                    placeholder_text: p_text == null ? "请输入" : p_text
            });
           });
        });
*
*
*
**/



if (typeof Firstp2p == "undefined") {
    Firstp2p = {};
}
//组件闭包开始
(function($) {
    $(function () {
        var placeholder = function (element, options, callback) {
            if (!$(element).length) {
                return;
            };

            //判断是否有内联的写法  data-placeholder="span请输入用户名|keydown"
            //if ($(element).attr('data-placeholder')!=null) {
            //    var arr = uiParse($(element).attr('data-placeholder'));
            //    options = {
            //        placeholder_text: arr[0],
            //        placeholder_evtType: arr[1],
            //        placeholder_color: arr[2],
            //        placeholder_zIndex: arr[3],
            //        placeholder_paddingLeft: arr[4],
            //    };
            //}

            // 默认值
            var defaults = {
                allOn: false,
                placeholder_text: '请输入',
                placeholder_color: '#111',
                placeholder_evtType: 'keydown',
                placeholder_zIndex: 20,
                placeholder_paddingLeft: 5
            };
            this.settings = $.extend({}, defaults, options);
            //判断是否支持placeholder
            if (!this.settings.allOn) {
                if (!('placeholder' in document.createElement('input'))) {
                    this._init(element);
                } else {
                    return;
                }
            } else {
                $(element).removeAttr("placeholder");
                this._init(element);
            }

        };

        //截取输入的值
        var uiParse = function (action) {
            var arr = action.split('|').slice(0),
                len = arr.length,
                res = [],
                exs,
                boo = /^(true|false)$/;
            for (var i = 0; i < len; i++) {
                var item = arr[i];
                if (item == '&') {
                    item = undefined;
                } else if (exs = item.match(boo)) {
                    item = exs[0] == 'true' ? true : false;
                }
                res[i] = item;
            }
            return res;
        };

        $.extend(placeholder.prototype, {
            //组件初始化
            _init: function (element) {
                var _this = this,
                    $elem = $(element);
                //获取当前元素的各项数据
                var top = $elem.offset().top,
                    left = $elem.offset().left,
                    width = $elem.outerWidth(),
                    height = $elem.outerHeight(),
                    fontsize = $elem.css('font-size'),
                    fontfamily = $elem.css('font-family'),
                    paddingLeft = $elem.css('padding-left');
                //计算追加提示后的偏移PX
                var newpaddingLeft = parseInt(paddingLeft, 10) + _this.settings.placeholder_paddingLeft;
                //创建span
                var $placeholder = $('<span class="span_placeholder"></span>');

                $placeholder.css({
                    position: 'absolute',
                    zIndex: _this.settings.placeholder_zIndex,
                    top: top,
                    left: left,
                    color: _this.settings.color,
                    width: (width - newpaddingLeft) + 'px',
                    height: height + 'px',
                    fontSize: fontsize,
                    paddingLeft: newpaddingLeft + 'px',
                    fontFamily: fontfamily
                }).text(_this.settings.placeholder_text).hide();

                // textarea 不加line-heihgt属性
                if ($elem.is('input')) {
                    $placeholder.css({
                        lineHeight: height + 'px'
                    });
                }
                //追加
                $placeholder.appendTo(document.body);
                this._placeEvent($elem, $placeholder);
            },
            //
            //事件
            _placeEvent: function ($elem, $placeholder) {
                var _this = this;
                var val = $elem.val();
                if (val == '') {
                    $placeholder.show();
                }
                //点击消失
                function hideAndFocus() {
                    $placeholder.hide();
                    $elem[0].focus();
                }
                function asFocus() {
                    $placeholder.click(hideAndFocus);
                    $elem.click(hideAndFocus);
                    $elem.blur(function () {
                        var txt = $elem.val();
                        if (txt == '') {
                            $placeholder.show();
                        }
                    });
                }
                function asKeydown() {
                    $placeholder.click(function () {
                        $elem[0].focus();
                    });
                }

                if (_this.settings.placeholder_evtType == 'focus') {
                    asFocus();
                } else if (_this.settings.placeholder_evtType == 'keydown') {
                    asKeydown();
                }

                $elem.keyup(function () {
                    var txt = $elem.val();
                    if (txt == '') {
                        $placeholder.show();
                    } else {
                        $placeholder.hide();
                    }
                });
            }
        });
        //返回
        Firstp2p.placeholder = function (element, options) {
            return new placeholder(element, options);
        };
    });
})(jQuery);