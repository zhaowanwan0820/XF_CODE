define(function(require, exports, module) {

    var $ = require('jquery');
    var p2p = {};
    
/**
 * 命名空间Firstp2p
 * @nampespace Firstp2p
 */
//闭包

   /**
    * @module 组件名称，例如(Widget)
    * @author looping
    
    * @param  {String} 可供jquery直接获取的id，例如 "#foo"(必填)
    * @param  {Object} Widget的配置参数(可选)
    * @constructor
    */

    var formsubmit = function(el, opts) {
        //检测el是否存在， 不存在就返回
        if(!$(el).length) {
            return;
        }
        this._el = $(el);

        /**
         * 组件的默认配置项
         * @private
         * @type {Object}
         */
        this.defaults = {
            submitBtn : $(el).find('input:submit').eq(0),
            canSubmit : true,
            btnDisable : function(el){
                return this.submitBtn.attr('disabled','disabled').css({"background":"gray","color":"#fff"}).val('正在加载中...');
            },
            btnReset : function(el){
                return this.submitBtn.removeAttr('disabled','disabled').css({"background":"#ccc","color":"#000"}).val('提交');
            },
            beforeSend : function(el){
                this.btnDisable(el);
            },
            ajaxFn : function(el){
                var that = this;
                $.ajax({
                    type : 'POST',
                    url: el.attr('action'),
                    beforeSend : function(){
                        that.btnDisable(el);
                    },
                    data: $(el).serialize(),
                    success: function(data){
                        data = $.parseJSON(data);
                        if(typeof dialog != 'undefined'){
                            dialog({
                                content : '提交成功，用户名：'+data.name+'；密码：'+data.pwd,
                                okValue: '确定',
                                cancelValue: '取消',
                                ok : function(){
                                    that.btnReset(el);
                                },
                                cancel: function () {
                                    that.btnReset(el);
                                }
                            }).show();
                        }else{
                            alert('提交成功，用户名：'+data.name+'；密码：'+data.pwd);
                            that.btnReset(el);
                        }
                        
                    }
                });
            }
        }
        /**
         * 配置合并
         */
        this._opts = $.extend(this.defaults, opts);
        
        /**
         * 提交按钮
         * @type {jQueryDomObject} 默认是form表单里的第一个submit按钮
         */
        this.submitBtn = this._opts.submitBtn;

        /**
         * ajax提交函数
         * @type {Funciton} 
         */
        this.ajaxFn = this._opts.ajaxFn;

        /**
         * 提交前函数
         * @type {Funciton} 
         */
        this.beforeSend = this._opts.beforeSend;

        /**
         * 按钮disable样式函数
         * @type {Funciton} 
         */
        this.btnDisable = this._opts.btnDisable;

        /**
         * 按钮还原函数
         * @type {Funciton} 
         */
        this.btnReset = this._opts.btnReset;

        /**
         * 调用组件的初始化
         */
        this._init();
    }

    $.extend(formsubmit.prototype, {

        /**
         * 组件初始化方法,比如绑定事件之类
         * @method _init 
         * @return none
         */
        _init: function() {
            var that = this;
            if(that._opts.canSubmit){
                if($.validator){
                    that._el.attr('ajaxsubmit') ? that.ajaxValidSubmit(that._el) : that.directValidSubmit(that._el); 
                }else{
                    that._el.attr('ajaxsubmit') ? that.ajaxSubmit(that._el) : that.directSubmit(that._el);    
                }
            }
        },

        /**
         * 无验证插件ajax提交
         */
        ajaxSubmit : function(el){
            var that = this;
            el.submit(function(e){
                that.ajaxFn(el);
                return false;
            })
        },

        /**
         * 无验证插件直接提交
         */
        directSubmit : function(el){
            var that = this;
            el.submit(function(e){
                that.beforeSend(el);
                return true;
            })
        },

        /**
         * 验证插件ajax提交
         */
        ajaxValidSubmit : function(el){
            var that = this;
            el.validator( function(){
                that.ajaxFn(el);
            });
        },

        /**
         * 验证插件直接提交
         */
        directValidSubmit : function(el){
            var that = this;            
            el.submit(function(e){
                el.isValid( function(v){
                    if(!!v){
                        that.beforeSend(el);
                        return true;
                    }
                });
                return false;
            });
        },

        /**
         * 私有方法，组件销毁，清理内存
         * @private
         * @method _destory
         * @required
         * @return none
         */
        _destroy: function() {
           
        }


    })

    
    /**
     * 将组件挂载到Firstp2p上, 项目中调用: Firstp2p.Widget

     *
        Firstp2p.formsubmit('form',{

        })
     * 
     * @extends Firstp2p 扩展自Firstp2p
     * @class  formsubmit开发指引
     
     */
 
    p2p.formsubmit = function(el,opts) {

        return  new formsubmit(el,opts);
    }   
    

return p2p.formsubmit;

});