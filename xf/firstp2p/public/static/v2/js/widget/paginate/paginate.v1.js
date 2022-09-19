/**
 * 命名空间X
 * @nampespace Firstp2p
 */
if (typeof Firstp2p == "undefined") {
	Firstp2p = {};
}

//闭包
(function(){

   /**
    * @module paginate
    * @author johnsong

    * @param  {String} 可供jquery直接获取的id，例如 "#paginate"(必填)
    * @param  {Object} paginate的配置参数(可选)
    * @constructor
    */

    var paginate = function(el, opts) {

        //检测el是否存在， 不存在就返回
        if(!$(el).length) {
            return;
        }

        /**
         * 组件被应用的元素，供其他方法使用
         * @private
         * @type {jQueryDomObject}
         */
        this._el = $(el);

        /**
         * 组件的默认配置项
         * @private
         * @type {Object}
         */

        this.defaultSettings = {
            items: 1,
            itemsOnPage: 1,
            pages: 0,
            displayedPages: 8,
            edges: 2,
            currentPage: 1,
            hrefTextPrefix: "#page=",
            hrefTextSuffix: "",
            prevText: "Prev",
            nextText: "Next",
            ellipseText: "&hellip;",
            cssStyle: "light-theme",
            inputText: false,
            selectOnClick: true,
            onPageClick: function (pageNumber, $obj) {}
        };

        /**
         * 组件的配置项，供其他方法使用
         * @private
         * @type {Object}
         */
        this._opts = $.extend({} ,this.defaultSettings , opts);

        /**
         * 总记录数
         * @attribute  items
         * @type {Integer} 默认值为1条记录
         */
        this.items = this._opts.items;

        /**
         * 每页显示的记录数
         * @attribute  itemsOnPage
         * @type {Integer} 默认为每页显示1条记录
         */
        this.itemsOnPage = this._opts.itemsOnPage;

        /**
         * 总页数
         * @attribute pages
         * @type {Integer} 默认为0，即通过总记录数和每页显示记录数来计算总页数
         */
        this.pages = this._opts.pages;

        /**
         * 可见页码的数量
         * @attribute displayedPages
         * @type {Integer} 默认为5，设置可以显示的页码的数量
         */
        this.displayedPages = this._opts.displayedPages;

        /**
         * 在开始和结束（即左边和右边）可见页码的数量
         * @attribute edges
         * @type {Integer} 默认为2，设置开始和结束（即左边和右边）可以显示的页码的数量
         */
        this.edges = this._opts.edges;

        /**
         * 当前页页码
         * @attribute currentPage
         * @type {Integer} 默认为2，当前选择页的页码
         */
        this.currentPage = this._opts.currentPage;

        /**
         * 翻页页码的href前缀
         * @attribute hrefTextPrefix
         * @type {String} 默认为"page="，创建href属性时的前缀
         */
        this.hrefTextPrefix = this._opts.hrefTextPrefix;

        /**
         * 翻页页码的href后缀
         * @attribute hrefTextSuffix
         * @type {String} 默认为""即空字符串，创建href属性时的后缀
         */
        this.hrefTextSuffix = this._opts.hrefTextSuffix;

        /**
         * 向前翻页的文字
         * @attribute prevText
         * @type {String} 默认为"Prev"
         */
        this.prevText = this._opts.prevText;

        /**
         * 向后翻页的文字
         * @attribute nextText
         * @type {String} 默认为"Next"
         */
        this.nextText = this._opts.nextText;

        /**
         * 省略页面符号表示
         * @attribute ellipseText
         * @type {String} 默认为"&hellip;"即"..."
         */
        this.ellipseText = this._opts.ellipseText;

        /**
         * 页码css style class名
         * @attribute cssStyle
         * @type {String} 默认为"The class of the CSS theme"
         */
        this.cssStyle = this._opts.cssStyle;

        /**
         * 点击后是否立即选择页面
         * @attribute selectOnClick
         * @type {Boolean} 默认值为true，如果你不想点击后立即选择页面就设置为false
         */
        this.selectOnClick = this._opts.selectOnClick;

        /**
         * 是否显示翻页输入框
         * @attribute inputText
         * @type {Boolean} 默认值为true
         */
        this.inputText = this._opts.inputText;

        /**
         * 点击页码后回调函数
         * @method onPageClick
         * @type {Function} 当页码点击后将触发此回调函数
         * @pageNumber {Integer} 一个可选参数，代表页码
         * @event {Event Type} 一个可选参数，代表事件类型， 如click
         */
        this.onPageClick = this._opts.onPageClick;

        /**
         * 调用组件的初始化
         */
        this.init();
    };

    $.extend(paginate.prototype, {
        /**
         * 组件初始化
         * @method init
         * @return undefined
         */
        init: function() {
            //初始化一些参数
            this.pages = this.pages ? this.pages : Math.ceil(this.items / this.itemsOnPage) ? Math.ceil(this.items / this.itemsOnPage) : 1;
            this.currentPage = this.currentPage - 1;
            this.halfDisplayed = this.displayedPages / 2;

            //调用render方法初始化paginate
            this.render();
        },

        /**
         * 渲染页码
         *
         * @method render
         * @return undefined
         */
        render: function() {
            var interval = this._getInterval(this),
                that = this._el,
                i;
            this.destroy.call(this._el);
            var $panel = that.prop("tagName") === "UL" ? this._el : $("<ul></ul>").appendTo(that);

            // 生成上一页按钮
            if (this.prevText) {
                this._appendItem.call(this, this.currentPage - 1, {text: "上一页", classes: "prev", title: "上一页"});
            }

            //生成左边页码及省略页码
            if (interval.start > 0 && this.edges > 0) {
                var end = Math.min(this.edges, interval.start);
                for (i = 0; i < end; i++) {
                    this._appendItem.call(this, i);
                }
                if (this.edges < interval.start && (interval.start -this.edges != 1)) {
                    $panel.append("<li class='disabled'><span class='ellipse'>" + this.ellipseText + "</span></li>");
                } else if (interval.start - this.edges == 1) {
                    this._appendItem.call(this, this.edges);
                }
            }

            // 生成中间部分页码
            for (i = interval.start; i < interval.end; i++) {
                this._appendItem.call(this, i);
            }

            // 生成右边页码及省略页码
            if (interval.end < this.pages && this.edges > 0) {
                if (this.pages - this.edges > interval.end && (this.pages - this.edges - interval.end != 1)) {
                    $panel.append("<li class='disabled'><span class='ellipse'>" + this.ellipseText + "</span></li>");
                } else if (this.pages - this.edges - interval.end == 1) {
                    this._appendItem.call(this, interval.end++);
                }
                var begin = Math.max(this.pages - this.edges, interval.end);
                for (i = begin; i < this.pages; i++) {
                    this._appendItem.call(this, i);
                }
            }

            // 生成下一页按钮
            if (this.nextText) {
                this._appendItem.call(this, this.currentPage + 1, {text: "下一页", classes: "next", title: "下一页"});
            }

            //是否显示翻页输入框
            if (this.inputText) {
                this._addPageInput.call(this);
            }
        },

        /**
         * 计算要显示的起始页码
         * @required
         * @method _getInterval
         * @return Object
         */
        _getInterval: function() {
            return {
                start: Math.ceil(this.currentPage > this.halfDisplayed ? Math.max(Math.min(this.currentPage - this.halfDisplayed, (this.pages - this.displayedPages)), 0) : 0),
                end: Math.ceil(this.currentPage > this.halfDisplayed ? Math.min(this.currentPage + this.halfDisplayed, this.pages) : Math.min(this.displayedPages, this.pages))
            };
        },

        /**
         * 销毁
         * @method destroy
         * @return dom object
         */

        destroy: function() {
            $(this).find("a").off("click");
            this.empty();
            return this;
        },

        /**
         * 重新渲染
         * @method rerender
         * @return dom object
         */

        rerender: function() {
            this.render.call(this);
            return this;
        },

        /**
         * 增加页码
         * @method _appendItem
         * @return undefined
         */
        _appendItem: function(pageIndex, opts) {
            var that = this,
                self = that._el,
                options,
                $link,
                $linkWrapper = $("<li></li>"),
                $ul = self.find("ul");
            pageIndex = pageIndex < 0 ? 0 : (pageIndex < this.pages ? pageIndex : this.pages - 1);
            options = $.extend({
                text: pageIndex + 1,
                classes: "",
                title: ""
            }, opts || {});
            if (pageIndex == this.currentPage) {
                if (this.disabled) {
                    $linkWrapper.addClass("disabled");
                } else {
                    $linkWrapper.addClass("active");
                }
                $link = $("<span class='current'>" + (options.text) + "</span>");
            } else {
                $link = $("<a href='" + this.hrefTextPrefix + (pageIndex + 1) + location.hash + this.hrefTextSuffix + "' class ='page-link'>" + (options.text) + "</a>");
                $link.bind("click", function(e) {
                    e.preventDefault();
                    that._selectPage(pageIndex, $(this));
                });
            }
            if (options.classes) {
                $link.addClass(options.classes);
            }
            if (options.title) {
                $link.attr("title", options.title);
            }
            $linkWrapper.append($link);
            if ($ul.length) {
                $ul.append($linkWrapper);
            } else {
                self.append($linkWrapper);
            }
        },

        /**
         * 显示翻页输入框
         * @method _addPageInput
         * @return undefined
         */
        _addPageInput: function() {
            var that = this,
                self = that._el.prop("tagName") === "UL" ? this._el : this._el.find("ul");
                $pageInput = $("<li class='to'><span class='graytext'>到</span></li><li class='wrapinput'><input type='' class='pageinput' /></li><li class='pgcoder'><span class='graytext'>页</span></li>");
            self.append($pageInput);
            var pgInputtext = $pageInput.find("input");
            pgInputtext.on("keydown", function(e) {//只允许输入数字
                var keyPressed = e.which,
                    $thisVal = $(this).val(),
                    hasDecimalPoint = $(this).val().indexOf('.') == -1;
                if (keyPressed == 46 || keyPressed == 8 || ((keyPressed == 190 || keyPressed == 110) && (!hasDecimalPoint)) || keyPressed == 9 || keyPressed == 27 || (keyPressed == 65 && e.ctrlKey === true) || (keyPressed >= 35 && keyPressed <= 39)) {
                    return;
                } else {
                    if (e.shiftKey || (keyPressed < 48 || keyPressed > 57) && (keyPressed < 96 || keyPressed > 105)) {
                        e.preventDefault();
                    }
                }
            });
            pgInputtext.on("keyup", function(e) {//回车后跳转页面
                var keyPressed = e.which,
                    $thisVal = parseInt($(this).val(), 10);
                if ($thisVal > that.pages) {
                    $thisVal = that.pages;
                }
                if ($thisVal == 0) {
                    $thisVal = 1;
                }
                if (!isNaN($thisVal) &&keyPressed == 13) {
                    return that._selectPage.call(that, $thisVal - 1, e);
                }
            });
        },

        /**
         * 点选页码
         *
         * @event click callback
         * @param {pageIndex} 页码索引
         * @param {e} 事件对象
         */
        _selectPage: function(pageIndex, $obj) {
            this.currentPage = pageIndex;
            if(this.selectOnClick) {
                this.render.call(this);
            }
            if ($(".pageinput").length) {
                $(".pageinput").focus();
            }
            return this.onPageClick(pageIndex + 1, $obj, this);
        }
    });

    /**
     * 将组件挂载到X上, 项目中调用: X.paginate

     *
        new X.paginate('#mypaginate',{
            pages: 400,
            onPageClick: function (pageNumber, event) {//点击页码后回调
            }
        });
     *
     * @extends X 扩展自X
     * @class  paginate开发指引
     */
    
    Firstp2p.paginate = function(el, opts) {
        return new paginate(el, opts);
    }
})();