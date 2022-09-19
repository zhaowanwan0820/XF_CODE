/**
 * 命名空间X
 * @nampespace X
 */

//闭包
(function($) {

    /**
    * @module 组件名称，slider
    * @author mabaoyue
    
    * @param  {String} 可供jquery直接获取的id，例如 "#foo"(必填)
    * @param  {Object} slider的配置参数(可选)
    * @constructor
    */

    var slider = function(el, opts) {

        //检测el是否存在， 不存在就返回
        if (!$(el).length) {
            return;
        }
        /**
         * 组件的配置项，供其他方法使用
         * @private
         * @type {Object}
         */

        this.defaultSettings = {
            scrollBar: ".scrollBar",
            bar : ".bar",
            ol: ".l",
            or: ".r",
            minUnit : 1 , 
            mendStart : 0 ,
            mendEnd :  0,
            onbeforedrag: function() {
                
            },
            ondraging: function() {

            },
            ondragover : function(){
                
            }
        };

        /**
         * 组件被应用的元素，供其他方法使用
         * @private
         * @type {jQueryDom}
         */
        this._el = $(el);


        /**
         * 组件的配置项，供其他方法使用
         * @private
         * @type {Object}
         */
        this._opts = $.extend({}, this.defaultSettings, opts);

        
        /**
         * @property {Object} 拖拽小块
         */

        this._bar = this._el.find(this._opts.bar);

        /**
         * @property {Object} 左侧选中区域
         */

        this._ol = this._el.find(this._opts.ol);

        /**
         * @property {Object} 右侧未选中区域
         */

       this._or = this._el.find(this._opts.or);


       /**
         * @property {Array} 最大拖拽长度
         */

        //this._maxL = this._el.outerWidth();
        this._maxL = this._el.width() - this._bar.width();
        /**
         * @property {Array} 拖拽比例
         */

        this.iScale = 0;

        /**
         * @property {Array} 最小移动单位
         */

        this._minUnit = this._opts.minUnit;

        /**
         * 调用组件的初始化
         */
        this.init();
    };

    $.extend(slider.prototype, {

        /**
         * 组件初始化方法,比如绑定事件之类
         * @method init
         * @return none
         */
        init: function() {
            this._maxL = !!this._opts.maxL ? this._opts.maxL : this._maxL;
            this._ol.width(0);
            this._or.width(this._maxL);
            this.render();
        },

        /**
         * 组件ui的渲染，比如绑定事件
         *
         * @method render
         * @return none
         */
        render: function() {
            //this._onkeydown();
            this._onmousedown();
            this._onclick();
        },

          /**
         * 绑定鼠标事件
         * @method onSetValue 设定滑块的滑动距离，传入参数为比例值
         * @return none
         */
        onSetValue : function(scale){
            var maxL = this._maxL,
            minUnit = this._minUnit,
            step = 0,
            s = this._opts.mendStart,
            e = this._opts.mendEnd;
            if(scale >= 1) scale = 1;
            if(scale <= 0) scale = 0;
            step = maxL * scale;
           
            if(step >= maxL) step = maxL;
            if(step <= 0) step  = 0;
            if(!!s &&  (step - s) <= 0){
                step += this._opts.mendStart;
            }
            if(!!e &&  ((maxL - step) - e) <= 0){
                step -= this._opts.mendStart;
            }
            this._bar.css("left" , step);
            this._ol.width(step);        
            this._or.width(maxL - step);  
        },

         /**
         * 绑定鼠标事件
         * @private 私有方法
         * @method _onmousedown 绑定鼠标拖拽滑块事件
         * @return none
         */
        _onmousedown: function() {
           var self = this,
           bar = this._bar,
           maxL = this._maxL,
           disX = 0,
           ol = this._ol,
           or = this._or,
           minUnit = this._minUnit;
           bar.on("mousedown" ,function (event){
                var event = event || window.event;
                disX = event.clientX - bar.position().left; 
                self._opts.onbeforedrag(self);      
                document.onmousemove = function (event){
                        var event = event || window.event;
                        var iL = event.clientX - disX;
                        iL <= 0 && (iL = 0);
                        iL >= maxL && (iL = maxL);
                        self.iScale = iL / maxL;
                        self.onSetValue(self.iScale);
                        self._opts.ondraging(self.iScale);     
                        return false;
                };              
                document.onmouseup = function (){
                        self._opts.ondragover(self.iScale);
                        document.onmousemove = null;
                        document.onmouseup = null
                };
                return false
           });
        },

         /**
         * 绑定键盘事件
         * @private 私有方法
         * @method _onkeydown 绑定键盘左右加减数字事件
         * @return none
         */
        _onkeydown : function(){
             var  self = this,
             scale = 0,
             show = !1;
             this._el.on("mouseenter" , function(){
                    show = !0;
             }).on("mouseleave" , function(){
                    show = !1;
                   
             });
             $(document).bind("keydown" , function(event){
                       if(!show){
                            var iSpeed = 0 ;
                            if (event.keyCode == 39){
                               iSpeed = 1*self._minUnit;
                            }
                            else if(event.keyCode == 37){
                               iSpeed = -1*self._minUnit;
                            }
                            scale = (self._bar[0].offsetLeft + iSpeed) / self._maxL;
                            if(scale >= 1) scale = 1;
                            if(scale <= 0) scale = 0;
                            self.onSetValue(scale);
                            self._opts.ondragover(scale);
                       }    
              });

        },

        /**
         * 组件ui的展现
         * @private 私有方法
         * @method _onclick 滚动条可移动区域点击事件
         * @return none
         */
        _onclick : function(){
            var self = this;
            this._el.on("click" , self._opts.ol + "," + self._opts.or , function(event){
                  event.stopPropagation();
                  var iTarget = (event || window.event).pageX - self._el[0].offsetLeft;
                  if (iTarget <= 0){
                    iTarget = 0;
                  }
                  else if (iTarget >= self._maxL){
                    iTarget = self._maxL;
                  }
                  self.iScale = iTarget / self._maxL;
                  self.onSetValue(self.iScale);
                  self._opts.ondragover(self.iScale);
            });
            this._bar.on("click", function (event){
                (event || window.event).cancelBubble = true;
            });
        },
        

        /**
         * 组件ui的展现
         * @private 私有方法
         * @method _destroy 销毁组件
         * @return none
         */
        _destroy: function() {
            
        }


    })


    /**
     * 将组件挂载到X上, 项目中调用: X.slider

     *
         X.slider("#ul1");
     * 
     * @extends X 扩展自X
     * @class  slider开发指引
     
     */

    X.slider = function(el, opts) {
        return new slider(el, opts);
    }

})(jQuery);